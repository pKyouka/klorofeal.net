<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Models\ExternalBarcode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Process\Process;

class ExternalBarcodeSyncService
{
    public const SOURCE_OPENFOODFACTS = 'openfoodfacts';
    public const SOURCE_GITHUB_ID = 'github-indonesia-list';

    public function sync(array $requestedSources = [], ?int $maxOpenFoodFactsPages = null): array
    {
        $sources = $this->normalizeSources($requestedSources);

        $summary = [
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'invalid' => 0,
            'sources' => [],
        ];

        foreach ($sources as $source) {
            $stats = [
                'processed' => 0,
                'inserted' => 0,
                'updated' => 0,
                'invalid' => 0,
            ];

            foreach ($this->recordsForSource($source, $maxOpenFoodFactsPages) as $record) {
                $stats['processed']++;

                $upserted = $this->upsertRecord($record, $source);
                if ($upserted === 'inserted') {
                    $stats['inserted']++;
                } elseif ($upserted === 'updated') {
                    $stats['updated']++;
                } else {
                    $stats['invalid']++;
                }
            }

            $summary['processed'] += $stats['processed'];
            $summary['inserted'] += $stats['inserted'];
            $summary['updated'] += $stats['updated'];
            $summary['invalid'] += $stats['invalid'];
            $summary['sources'][$source] = $stats;
        }

        return $summary;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeSources(array $requestedSources): array
    {
        if ($requestedSources === []) {
            return [self::SOURCE_OPENFOODFACTS, self::SOURCE_GITHUB_ID];
        }

        $allowed = [self::SOURCE_OPENFOODFACTS, self::SOURCE_GITHUB_ID];
        $sources = [];

        foreach ($requestedSources as $source) {
            $normalized = Str::lower(trim((string) $source));
            if (in_array($normalized, $allowed, true)) {
                $sources[] = $normalized;
            }
        }

        $sources = array_values(array_unique($sources));

        return $sources === [] ? [self::SOURCE_OPENFOODFACTS, self::SOURCE_GITHUB_ID] : $sources;
    }

    /**
     * @return iterable<int, array<string, mixed>>
     */
    private function recordsForSource(string $source, ?int $maxOpenFoodFactsPages): iterable
    {
        return match ($source) {
            self::SOURCE_OPENFOODFACTS => $this->openFoodFactsRecords($maxOpenFoodFactsPages),
            self::SOURCE_GITHUB_ID => $this->githubIndonesiaRecords(),
            default => [],
        };
    }

    /**
     * @return iterable<int, array<string, mixed>>
     */
    private function openFoodFactsRecords(?int $maxPagesOverride): iterable
    {
        $pageSize = max(10, (int) config('services.openfoodfacts.page_size', 100));
        $maxPages = $maxPagesOverride !== null
            ? max(1, $maxPagesOverride)
            : max(1, (int) config('services.openfoodfacts.max_pages', 300));

        $url = (string) config('services.openfoodfacts.search_url', 'https://world.openfoodfacts.org/cgi/search.pl');

        $seen = [];
        $consecutiveFailures = 0;

        for ($page = 1; $page <= $maxPages; $page++) {
            $query = [
                'action' => 'process',
                'json' => 1,
                'page' => $page,
                'page_size' => $pageSize,
                'tagtype_0' => 'countries',
                'tag_contains_0' => 'contains',
                'tag_0' => 'indonesia',
                'fields' => 'code,product_name,product_name_en,product_name_id,generic_name,brands,countries,countries_tags',
            ];

            $response = $this->requestJsonWithFallback($url, $query);
            if ($response === null) {
                $consecutiveFailures++;
                if ($consecutiveFailures >= 5) {
                    break;
                }

                continue;
            }

            $consecutiveFailures = 0;
            $products = (array) data_get($response, 'products', []);

            if ($products === []) {
                break;
            }

            foreach ($products as $payload) {
                if (! is_array($payload)) {
                    continue;
                }

                if (! $this->isIndonesianProduct($payload)) {
                    continue;
                }

                $barcode = $this->normalizeBarcode((string) data_get($payload, 'code', ''));
                if ($barcode === null || isset($seen[$barcode])) {
                    continue;
                }

                $name = $this->extractName($payload);
                if ($name === null) {
                    continue;
                }

                $seen[$barcode] = true;

                $brand = trim((string) data_get($payload, 'brands', ''));

                yield [
                    'barcode' => $barcode,
                    'product_name' => Str::limit($name, 255, ''),
                    'brand' => $brand !== '' ? Str::limit($brand, 255, '') : null,
                    'country_code' => 'ID',
                    'source_meta' => [
                        'api' => 'openfoodfacts',
                    ],
                ];
            }
        }
    }

    /**
     * @return iterable<int, array<string, mixed>>
     */
    private function githubIndonesiaRecords(): iterable
    {
        $url = (string) config(
            'services.github_datasets.indonesia_barcodes_xlsx_url',
            'https://raw.githubusercontent.com/wpangestu/list-barcode-product-indonesia/master/List%20barcode%201000%20Product.xlsx'
        );

        $tmpPath = tempnam(sys_get_temp_dir(), 'barcode_id_');
        if ($tmpPath === false) {
            throw new RuntimeException('Tidak dapat menyiapkan file sementara untuk sinkronisasi GitHub.');
        }

        try {
            $downloaded = $this->downloadFileWithFallback($url, $tmpPath);
            if (! $downloaded) {
                return;
            }

            $rows = $this->parseGithubXlsxRows($tmpPath);
            $seen = [];

            foreach ($rows as $row) {
                $barcode = $this->normalizeBarcode((string) ($row['barcode'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));

                if ($barcode === null || $name === '' || isset($seen[$barcode])) {
                    continue;
                }

                $seen[$barcode] = true;

                yield [
                    'barcode' => $barcode,
                    'product_name' => Str::limit($name, 255, ''),
                    'brand' => null,
                    'country_code' => 'ID',
                    'source_meta' => [
                        'repo' => 'wpangestu/list-barcode-product-indonesia',
                        'file_url' => $url,
                    ],
                ];
            }
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    private function downloadFileWithFallback(string $url, string $targetPath): bool
    {
        $body = null;

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'KlorofealSync/1.0',
            ])
                ->connectTimeout(8)
                ->timeout(30)
                ->retry(2, 250)
                ->get($url);

            if ($response->ok()) {
                $body = $response->body();
            }
        } catch (ConnectionException) {
            // Fallback for local certificate issues.
        }

        if ($body === null) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'KlorofealSync/1.0',
                ])
                    ->connectTimeout(8)
                    ->timeout(30)
                    ->retry(2, 250)
                    ->withOptions(['verify' => false])
                    ->get($url);

                if ($response->ok()) {
                    $body = $response->body();
                }
            } catch (ConnectionException) {
                return false;
            }
        }

        if ($body === null) {
            return false;
        }

        return file_put_contents($targetPath, $body) !== false;
    }

    /**
     * @return array<int, array{barcode: string, name: string}>
     */
    private function parseGithubXlsxRows(string $xlsxPath): array
    {
        if (class_exists('ZipArchive')) {
            return $this->parseXlsxRowsWithZipArchive($xlsxPath);
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return $this->parseXlsxRowsWithPowerShell($xlsxPath);
        }

        throw new RuntimeException('Parser XLSX tidak tersedia. Aktifkan ekstensi zip atau jalankan sinkronisasi di Windows.');
    }

    /**
     * @return array<int, array{barcode: string, name: string}>
     */
    private function parseXlsxRowsWithZipArchive(string $xlsxPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            throw new RuntimeException('Gagal membuka file XLSX GitHub.');
        }

        $sharedStrings = $this->extractSharedStringsFromZip($zip);

        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! is_string($sheetXmlContent) || $sheetXmlContent === '') {
            return [];
        }

        $sheetXml = simplexml_load_string($sheetXmlContent);
        if (! $sheetXml instanceof SimpleXMLElement) {
            return [];
        }

        $sheetXml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = $sheetXml->xpath('//x:sheetData/x:row') ?: [];

        $parsed = [];

        foreach ($rows as $row) {
            $cells = [];
            foreach ($row->xpath('./x:c') ?: [] as $cell) {
                $reference = (string) ($cell['r'] ?? '');
                $column = preg_replace('/\d+/', '', $reference) ?: '';
                if ($column === '') {
                    continue;
                }

                $cells[$column] = $this->extractCellValue($cell, $sharedStrings);
            }

            $barcode = trim((string) ($cells['A'] ?? ''));
            $name = trim((string) ($cells['B'] ?? ''));

            if ($barcode === '' || $name === '') {
                continue;
            }

            $parsed[] = [
                'barcode' => $barcode,
                'name' => $name,
            ];
        }

        return $parsed;
    }

    /**
     * @return array<int, string>
     */
    private function extractSharedStringsFromZip(\ZipArchive $zip): array
    {
        $xmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if (! is_string($xmlContent) || $xmlContent === '') {
            return [];
        }

        $xml = simplexml_load_string($xmlContent);
        if (! $xml instanceof SimpleXMLElement) {
            return [];
        }

        $xml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $items = $xml->xpath('//x:si') ?: [];

        $shared = [];
        foreach ($items as $item) {
            $texts = $item->xpath('.//x:t') ?: [];
            $value = '';
            foreach ($texts as $text) {
                $value .= (string) $text;
            }
            $shared[] = $value;
        }

        return $shared;
    }

    private function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $cell->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $type = (string) ($cell['t'] ?? '');
        if ($type === 's') {
            $index = (int) ($cell->v ?? 0);
            return (string) ($sharedStrings[$index] ?? '');
        }

        if ($type === 'inlineStr') {
            $texts = $cell->xpath('./x:is/x:t') ?: [];
            $value = '';
            foreach ($texts as $text) {
                $value .= (string) $text;
            }

            return $value;
        }

        return (string) ($cell->v ?? '');
    }

    /**
     * @return array<int, array{barcode: string, name: string}>
     */
    private function parseXlsxRowsWithPowerShell(string $xlsxPath): array
    {
        $script = <<<'PS'
param([string]$XlsxPath)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem

function Read-EntryText($entry) {
    $stream = $entry.Open()
    $reader = New-Object System.IO.StreamReader($stream)

    try {
        return $reader.ReadToEnd()
    } finally {
        $reader.Dispose()
        $stream.Dispose()
    }
}

$archive = [System.IO.Compression.ZipFile]::OpenRead($XlsxPath)

try {
    $shared = @()
    $sharedEntry = $archive.GetEntry('xl/sharedStrings.xml')

    if ($null -ne $sharedEntry) {
        [xml]$sharedXml = Read-EntryText $sharedEntry

        foreach ($si in $sharedXml.sst.si) {
            $textParts = @()

            if ($null -ne $si.t) {
                $textParts += [string]$si.t
            }

            if ($null -ne $si.r) {
                foreach ($r in $si.r) {
                    if ($null -ne $r.t) {
                        $textParts += [string]$r.t
                    }
                }
            }

            $shared += ($textParts -join '')
        }
    }

    $sheetEntry = $archive.GetEntry('xl/worksheets/sheet1.xml')
    if ($null -eq $sheetEntry) {
        '[]'
        exit 0
    }

    [xml]$sheetXml = Read-EntryText $sheetEntry

    $rows = New-Object System.Collections.Generic.List[Object]

    foreach ($row in $sheetXml.worksheet.sheetData.row) {
        $barcode = ''
        $name = ''

        foreach ($cell in $row.c) {
            $ref = [string]$cell.r
            if ([string]::IsNullOrWhiteSpace($ref)) {
                continue
            }

            $column = $ref -replace '\d', ''
            $value = ''

            if ($cell.t -eq 's') {
                $index = [int]$cell.v
                if ($index -ge 0 -and $index -lt $shared.Count) {
                    $value = $shared[$index]
                }
            } elseif ($cell.t -eq 'inlineStr') {
                $value = [string]$cell.is.t
            } else {
                $value = [string]$cell.v
            }

            if ($column -eq 'A') {
                $barcode = $value
            } elseif ($column -eq 'B') {
                $name = $value
            }
        }

        if (-not [string]::IsNullOrWhiteSpace($barcode) -and -not [string]::IsNullOrWhiteSpace($name)) {
            $rows.Add([PSCustomObject]@{
                barcode = $barcode
                name = $name
            })
        }
    }

    $rows | ConvertTo-Json -Compress
} finally {
    $archive.Dispose()
}
PS;

        $tempScriptPath = tempnam(sys_get_temp_dir(), 'xlsx_parse_');
        if ($tempScriptPath === false) {
            throw new RuntimeException('Tidak dapat menyiapkan parser PowerShell sementara.');
        }

        $scriptPath = $tempScriptPath . '.ps1';
        if (file_put_contents($scriptPath, $script) === false) {
            @unlink($tempScriptPath);
            throw new RuntimeException('Tidak dapat menulis script parser PowerShell.');
        }
        @unlink($tempScriptPath);

        try {
            $process = new Process([
                'powershell',
                '-NoProfile',
                '-ExecutionPolicy',
                'Bypass',
                '-File',
                $scriptPath,
                $xlsxPath,
            ]);
            $process->setTimeout(180);
            $process->mustRun();

            $json = trim($process->getOutput());
            if ($json === '') {
                return [];
            }

            $decoded = json_decode($json, true);
            if (! is_array($decoded)) {
                return [];
            }

            if ($this->isRowShape($decoded)) {
                return [$decoded];
            }

            return array_values(array_filter($decoded, fn ($row) => $this->isRowShape($row)));
        } finally {
            @unlink($scriptPath);
        }
    }

    /**
     * @param mixed $candidate
     */
    private function isRowShape($candidate): bool
    {
        return is_array($candidate)
            && array_key_exists('barcode', $candidate)
            && array_key_exists('name', $candidate);
    }

    private function requestJsonWithFallback(string $url, array $query): ?array
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'User-Agent' => 'KlorofealSync/1.0',
                ])
                ->connectTimeout(8)
                ->timeout(25)
                ->retry(2, 250)
                ->get($url, $query);

            if ($response->ok()) {
                return $response->json();
            }
        } catch (ConnectionException) {
            // Fallback for local certificate issues.
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'User-Agent' => 'KlorofealSync/1.0',
                ])
                ->connectTimeout(8)
                ->timeout(25)
                ->retry(2, 250)
                ->withOptions(['verify' => false])
                ->get($url, $query);

            return $response->ok() ? $response->json() : null;
        } catch (ConnectionException) {
            return null;
        }
    }

    private function upsertRecord(array $record, string $source): string
    {
        $barcode = $this->normalizeBarcode((string) ($record['barcode'] ?? ''));
        $name = trim((string) ($record['product_name'] ?? ''));

        if ($barcode === null || $name === '') {
            return 'invalid';
        }

        $now = Carbon::now();

        $existing = ExternalBarcode::query()->where('barcode', $barcode)->first();
        if (! $existing) {
            ExternalBarcode::query()->create([
                'barcode' => $barcode,
                'product_name' => Str::limit($name, 255, ''),
                'brand' => $this->nullableTrim($record['brand'] ?? null),
                'country_code' => $this->nullableTrim($record['country_code'] ?? null),
                'source_primary' => $source,
                'sources' => [$source],
                'source_meta' => [$source => (array) ($record['source_meta'] ?? [])],
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);

            return 'inserted';
        }

        $existingSources = (array) ($existing->sources ?? []);
        $sources = array_values(array_unique(array_merge($existingSources, [$source])));

        $sourceMeta = (array) ($existing->source_meta ?? []);
        $sourceMeta[$source] = array_merge(
            (array) ($sourceMeta[$source] ?? []),
            (array) ($record['source_meta'] ?? [])
        );

        $existing->fill([
            'product_name' => $this->choosePreferredName((string) $existing->product_name, $name),
            'brand' => $existing->brand ?: $this->nullableTrim($record['brand'] ?? null),
            'country_code' => $existing->country_code ?: $this->nullableTrim($record['country_code'] ?? null),
            'source_primary' => (string) $existing->source_primary,
            'sources' => $sources,
            'source_meta' => $sourceMeta,
            'last_seen_at' => $now,
        ]);
        $existing->save();

        return 'updated';
    }

    private function choosePreferredName(string $current, string $candidate): string
    {
        $current = trim($current);
        $candidate = trim($candidate);

        if ($current === '') {
            return Str::limit($candidate, 255, '');
        }

        if ($candidate === '') {
            return Str::limit($current, 255, '');
        }

        // Keep the longer readable name when candidate has more detail.
        return Str::limit(strlen($candidate) > strlen($current) ? $candidate : $current, 255, '');
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : Str::limit($trimmed, 255, '');
    }

    private function isIndonesianProduct(array $payload): bool
    {
        $countriesText = Str::lower((string) data_get($payload, 'countries', ''));
        if (Str::contains($countriesText, 'indonesia')) {
            return true;
        }

        $tags = array_map(
            static fn ($value) => Str::lower((string) $value),
            (array) data_get($payload, 'countries_tags', [])
        );

        return in_array('en:indonesia', $tags, true);
    }

    private function extractName(array $payload): ?string
    {
        $candidates = [
            (string) data_get($payload, 'product_name_id', ''),
            (string) data_get($payload, 'product_name', ''),
            (string) data_get($payload, 'product_name_en', ''),
            (string) data_get($payload, 'generic_name', ''),
        ];

        foreach ($candidates as $candidate) {
            $name = trim($candidate);
            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    private function normalizeBarcode(string $raw): ?string
    {
        $barcode = preg_replace('/\D+/', '', $raw) ?: '';
        $length = strlen($barcode);

        if (! in_array($length, [8, 12, 13, 14], true)) {
            return null;
        }

        if (! $this->isValidGtin($barcode)) {
            return null;
        }

        return $barcode;
    }

    private function isValidGtin(string $barcode): bool
    {
        $digits = array_map('intval', str_split($barcode));
        $checkDigit = array_pop($digits);
        $sum = 0;
        $weight = 3;

        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $sum += $digits[$i] * $weight;
            $weight = $weight === 3 ? 1 : 3;
        }

        $computed = (10 - ($sum % 10)) % 10;

        return $computed === $checkDigit;
    }
}
