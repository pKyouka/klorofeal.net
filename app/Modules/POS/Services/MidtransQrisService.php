<?php

namespace App\Modules\POS\Services;

use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Transaction;
use RuntimeException;

class MidtransQrisService
{
    public function createQrisCharge(string $orderId, int $grossAmount, string $itemDescription = 'POS QRIS Payment'): array
    {
        if ($grossAmount < 1) {
            throw new RuntimeException('Nominal transaksi tidak valid untuk QRIS Midtrans.');
        }

        if ($this->isMockEnabled()) {
            return [
                'order_id' => $orderId,
                'transaction_status' => 'pending',
                'expires_at' => now()->addMinutes((int) config('services.midtrans.qris_expiry_minutes', 15))->toIso8601String(),
                'qr_url' => $this->buildMockQrDataUrl($orderId, $grossAmount),
                'raw' => [
                    'mock' => true,
                    'order_id' => $orderId,
                    'gross_amount' => $grossAmount,
                    'transaction_status' => 'pending',
                ],
            ];
        }

        $this->configure();

        $basePayload = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
        ];

        $acquirerCandidates = $this->resolveAcquirerCandidates();
        $data = null;
        $lastException = null;
        $failedChannels = [];

        foreach ($acquirerCandidates as $acquirer) {
            $payload = $basePayload;
            if ($acquirer !== null) {
                $payload['qris'] = ['acquirer' => $acquirer];
            }

            try {
                $response = CoreApi::charge($payload);
                $data = json_decode(json_encode($response, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
                break;
            } catch (\Throwable $exception) {
                $message = $exception->getMessage();
                $lastException = $exception;

                Log::error('Midtrans QRIS charge failed.', [
                    'order_id' => $orderId,
                    'acquirer' => $acquirer,
                    'message' => $message,
                ]);

                if ($this->isChannelNotActivatedError($message) || $this->isPayloadFormatError($message)) {
                    $failedChannels[] = $acquirer ?? '(default)';
                    continue;
                }

                throw new RuntimeException($this->mapMidtransErrorMessage($message));
            }
        }

        if (! is_array($data)) {
            if ($failedChannels !== []) {
                throw new RuntimeException('QRIS Midtrans gagal diproses pada acquirer: ' . implode(', ', $failedChannels) . '. Pastikan channel/acquirer QRIS aktif dan didukung di merchant Midtrans Anda.');
            }

            throw new RuntimeException($this->mapMidtransErrorMessage($lastException?->getMessage() ?? 'Gagal membuat pembayaran QRIS Midtrans.'));
        }

        $qrUrl = null;
        foreach ((array) ($data['actions'] ?? []) as $action) {
            if (($action['name'] ?? null) === 'generate-qr-code' && ! empty($action['url'])) {
                $qrUrl = (string) $action['url'];
                break;
            }
        }

        if (! $qrUrl) {
            throw new RuntimeException('QR URL Midtrans tidak tersedia.');
        }

        return [
            'order_id' => (string) ($data['order_id'] ?? $orderId),
            'transaction_status' => (string) ($data['transaction_status'] ?? 'pending'),
            'expires_at' => (string) ($data['expiry_time'] ?? ''),
            'qr_url' => $qrUrl,
            'raw' => $data,
        ];
    }

    public function getTransactionStatus(string $orderId): array
    {
        if ($this->isMockEnabled()) {
            return [
                'order_id' => $orderId,
                'transaction_status' => 'settlement',
                'fraud_status' => 'accept',
                'payment_type' => 'qris',
                'gross_amount' => 0,
                'raw' => [
                    'mock' => true,
                    'order_id' => $orderId,
                    'transaction_status' => 'settlement',
                    'fraud_status' => 'accept',
                ],
            ];
        }

        $this->configure();

        try {
            $response = Transaction::status($orderId);
            $data = json_decode(json_encode($response, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            Log::error('Midtrans status check failed.', [
                'order_id' => $orderId,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Gagal memeriksa status pembayaran Midtrans.');
        }

        return $this->normalizeStatusPayload($data, $orderId);
    }

    public function normalizeNotificationPayload(array $payload): array
    {
        return $this->normalizeStatusPayload($payload, (string) ($payload['order_id'] ?? ''));
    }

    public function verifyNotificationSignature(array $payload): bool
    {
        if ($this->isMockEnabled()) {
            return true;
        }

        $signature = trim((string) ($payload['signature_key'] ?? ''));
        $orderId = trim((string) ($payload['order_id'] ?? ''));
        $statusCode = trim((string) ($payload['status_code'] ?? ''));
        $grossAmount = trim((string) ($payload['gross_amount'] ?? ''));
        $serverKey = trim((string) config('services.midtrans.server_key'));

        if ($signature === '' || $orderId === '' || $statusCode === '' || $grossAmount === '' || $serverKey === '') {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expected, $signature);
    }

    public function isPaid(array $status): bool
    {
        $transactionStatus = strtolower((string) ($status['transaction_status'] ?? ''));
        $fraudStatus = strtolower((string) ($status['fraud_status'] ?? ''));

        if ($transactionStatus === 'settlement') {
            return true;
        }

        if ($transactionStatus === 'capture' && ($fraudStatus === '' || $fraudStatus === 'accept')) {
            return true;
        }

        return false;
    }

    private function normalizeStatusPayload(array $data, string $fallbackOrderId = ''): array
    {
        return [
            'order_id' => (string) ($data['order_id'] ?? $fallbackOrderId),
            'transaction_status' => strtolower((string) ($data['transaction_status'] ?? '')),
            'fraud_status' => strtolower((string) ($data['fraud_status'] ?? '')),
            'payment_type' => (string) ($data['payment_type'] ?? ''),
            'gross_amount' => (int) round((float) ($data['gross_amount'] ?? 0)),
            'raw' => $data,
        ];
    }

    private function configure(): void
    {
        $serverKey = trim((string) config('services.midtrans.server_key'));
        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur.');
        }

        $isProduction = (bool) config('services.midtrans.is_production', false);

        if (str_starts_with($serverKey, 'SB-Mid-server-') && $isProduction) {
            throw new RuntimeException('MIDTRANS_IS_PRODUCTION=true tetapi server key yang dipakai adalah Sandbox (SB-Mid-server-).');
        }

        if (str_starts_with($serverKey, 'Mid-server-') && ! $isProduction) {
            throw new RuntimeException('MIDTRANS_IS_PRODUCTION=false tetapi server key yang dipakai adalah Production (Mid-server-).');
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = $isProduction;
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    private function isMockEnabled(): bool
    {
        return (bool) config('services.midtrans.mock_success', false);
    }

    private function buildMockQrDataUrl(string $orderId, int $grossAmount): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="420" height="420" viewBox="0 0 420 420">'
            . '<rect width="420" height="420" fill="#ffffff"/>'
            . '<rect x="20" y="20" width="380" height="380" rx="20" fill="#f8fafc" stroke="#0f766e" stroke-width="4"/>'
            . '<text x="210" y="95" text-anchor="middle" font-family="Arial, sans-serif" font-size="22" font-weight="700" fill="#0f766e">MIDTRANS MOCK QRIS</text>'
            . '<text x="210" y="135" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#334155">Development Mode</text>'
            . '<text x="210" y="205" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" fill="#0f172a">' . htmlspecialchars($orderId, ENT_QUOTES) . '</text>'
            . '<text x="210" y="240" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" font-weight="700" fill="#0f172a">Rp ' . number_format($grossAmount, 0, ',', '.') . '</text>'
            . '<text x="210" y="290" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" fill="#64748b">Tidak hit Midtrans real API</text>'
            . '<text x="210" y="315" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" fill="#64748b">Dipakai untuk testing flow POS</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * @return array<int, string|null>
     */
    private function resolveAcquirerCandidates(): array
    {
        $candidates = [];

        $single = trim((string) config('services.midtrans.qris_acquirer', ''));
        if ($single !== '') {
            $candidates[] = $this->normalizeAcquirerName($single);
        }

        $multiple = config('services.midtrans.qris_acquirers', []);
        if (is_array($multiple)) {
            foreach ($multiple as $entry) {
                $value = $this->normalizeAcquirerName((string) $entry);
                if ($value !== '') {
                    $candidates[] = $value;
                }
            }
        }

        $candidates = array_values(array_unique($candidates));

        // Always keep one fallback attempt without explicit acquirer.
        $candidates[] = null;
        return $candidates;
    }

    private function normalizeAcquirerName(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'shopeepay', 'airpay_shopee', 'airpay-shopee' => 'airpay shopee',
            default => $normalized,
        };
    }

    private function isChannelNotActivatedError(string $message): bool
    {
        return str_contains($message, 'Payment channel is not activated');
    }

    private function isPayloadFormatError(string $message): bool
    {
        return str_contains($message, 'Payload format is not supported');
    }

    private function mapMidtransErrorMessage(string $message): string
    {
        if (str_contains($message, 'Unknown Merchant server_key/id')) {
            return 'Server key Midtrans tidak dikenali. Pastikan MIDTRANS_SERVER_KEY benar dan sesuai mode production/sandbox.';
        }

        if ($this->isChannelNotActivatedError($message)) {
            return 'Channel QRIS Midtrans belum aktif. Aktifkan payment channel QRIS di dashboard Midtrans.';
        }

        if ($this->isPayloadFormatError($message)) {
            return 'Payload Midtrans tidak didukung untuk konfigurasi acquirer saat ini. Coba acquirer lain atau gunakan default QRIS Midtrans.';
        }

        return 'Gagal membuat pembayaran QRIS Midtrans: ' . $message;
    }
}
