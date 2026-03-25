<?php

use App\Modules\Products\Services\ExternalBarcodeSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('external-barcodes:sync {--source=* : Data source(s): openfoodfacts, github-indonesia-list} {--max-pages= : Override max OpenFoodFacts pages}', function (ExternalBarcodeSyncService $syncService) {
    $sources = (array) $this->option('source');
    $maxPagesOption = $this->option('max-pages');

    $maxPages = null;
    if ($maxPagesOption !== null && $maxPagesOption !== '') {
        if (! is_numeric($maxPagesOption) || (int) $maxPagesOption < 1) {
            $this->error('Nilai --max-pages harus angka lebih dari 0.');

            return 1;
        }

        $maxPages = (int) $maxPagesOption;
    }

    $this->info('Memulai sinkronisasi external barcode...');

    try {
        $result = $syncService->sync($sources, $maxPages);
    } catch (\Throwable $exception) {
        $this->error('Sinkronisasi gagal: ' . $exception->getMessage());

        return 1;
    }

    foreach ($result['sources'] as $source => $stats) {
        $this->line(sprintf(
            '[%s] processed=%d inserted=%d updated=%d invalid=%d',
            $source,
            $stats['processed'],
            $stats['inserted'],
            $stats['updated'],
            $stats['invalid'],
        ));
    }

    $this->info(sprintf(
        'Selesai. processed=%d inserted=%d updated=%d invalid=%d',
        $result['processed'],
        $result['inserted'],
        $result['updated'],
        $result['invalid'],
    ));

    return 0;
})->purpose('Synchronize Indonesian external barcode references from OpenFoodFacts and GitHub datasets.');

Schedule::command('external-barcodes:sync')
    ->dailyAt('01:30')
    ->withoutOverlapping();
