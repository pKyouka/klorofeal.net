<?php

namespace App\Modules\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\POS\Models\QrisPayment;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Requests\StoreSaleRequest;
use App\Modules\POS\Services\MidtransQrisService;
use App\Modules\POS\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly MidtransQrisService $midtransQrisService,
    ) {
    }

    public function index(): View
    {
        return view('sales.index', [
            'sales' => $this->saleService->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('sales.create', [
            'products' => [],
            'lastSale' => $this->saleService->latestSaleForUser((int) $request->user()->id),
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $keyword = (string) $request->query('q', '');
        $products = $this->saleService->searchProducts($keyword);

        return response()->json([
            'data' => $products,
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $items = $request->validated('items', []);
        $taxAmount = (float) $request->validated('tax_amount', 0);
        $paymentMethod = (string) $request->validated('payment_method');
        $qrisPayment = null;

        if ($paymentMethod === 'qris') {
            $midtransOrderId = trim((string) $request->validated('midtrans_order_id', ''));

            if ($midtransOrderId === '') {
                throw ValidationException::withMessages([
                    'payment_method' => 'Midtrans QRIS belum dibuat. Silakan generate QRIS terlebih dahulu.',
                ]);
            }

            $qrisPayment = QrisPayment::query()
                ->where('order_id', $midtransOrderId)
                ->where('user_id', (int) $request->user()->id)
                ->first();

            if ($qrisPayment === null) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Data pembayaran QRIS tidak ditemukan. Silakan generate QRIS lagi.',
                ]);
            }

            if ($qrisPayment->sale_id !== null) {
                throw ValidationException::withMessages([
                    'payment_method' => 'QRIS ini sudah dipakai untuk transaksi lain.',
                ]);
            }

            if (! $qrisPayment->is_paid) {
                try {
                    $status = $this->midtransQrisService->getTransactionStatus($midtransOrderId);
                    $this->syncQrisPaymentStatus($qrisPayment, $status);
                    $qrisPayment->refresh();
                } catch (RuntimeException $exception) {
                    throw ValidationException::withMessages([
                        'payment_method' => $exception->getMessage(),
                    ]);
                }
            }

            if (! $qrisPayment->is_paid) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Pembayaran QRIS belum selesai. Mohon selesaikan pembayaran terlebih dahulu.',
                ]);
            }

            $preview = $this->saleService->previewSale($items, $taxAmount);
            $expectedAmount = (int) round((float) $preview['grand_total']);
            if ((int) round((float) $qrisPayment->gross_amount) !== $expectedAmount) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Nominal pembayaran QRIS tidak sesuai dengan total transaksi saat ini.',
                ]);
            }
        }

        $sale = $this->saleService->createSale(
            userId: (int) $request->user()->id,
            items: $items,
            paymentMethod: $paymentMethod,
            taxAmount: $taxAmount,
        );

        if ($paymentMethod === 'qris' && $qrisPayment !== null) {
            $qrisPayment->update([
                'sale_id' => $sale->id,
                'paid_at' => $qrisPayment->paid_at ?? now(),
            ]);
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Transaction completed successfully.');
    }

    public function createQrisPayment(StoreSaleRequest $request): JsonResponse
    {
        if ($request->validated('payment_method') !== 'qris') {
            return response()->json([
                'message' => 'Payment method harus QRIS.',
            ], 422);
        }

        $preview = $this->saleService->previewSale(
            $request->validated('items', []),
            (float) $request->validated('tax_amount', 0),
        );

        $grossAmount = (int) round((float) $preview['grand_total']);
        if ($grossAmount < 1) {
            return response()->json([
                'message' => 'Nominal transaksi tidak valid untuk pembayaran QRIS.',
            ], 422);
        }

        $orderId = sprintf(
            'POSQRIS-%d-%s-%s',
            (int) $request->user()->id,
            now()->format('YmdHis'),
            Str::upper(Str::random(5)),
        );

        try {
            $charge = $this->midtransQrisService->createQrisCharge(
                orderId: $orderId,
                grossAmount: $grossAmount,
                itemDescription: 'POS Invoice ' . $orderId,
            );
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            $statusCode = (
                str_contains($message, 'Channel QRIS Midtrans belum aktif')
                || str_contains($message, 'Server key Midtrans tidak dikenali')
                || str_contains($message, 'Payload Midtrans tidak didukung')
                || str_contains($message, 'QRIS Midtrans gagal diproses pada acquirer')
            )
                ? 422
                : 502;

            return response()->json([
                'message' => $message,
            ], $statusCode);
        }

        $expiresAt = null;
        if (! empty($charge['expires_at'])) {
            try {
                $expiresAt = Carbon::parse((string) $charge['expires_at']);
            } catch (\Throwable) {
                $expiresAt = null;
            }
        }

        QrisPayment::query()->updateOrCreate(
            ['order_id' => $orderId],
            [
                'user_id' => (int) $request->user()->id,
                'sale_id' => null,
                'gross_amount' => $grossAmount,
                'transaction_status' => strtolower((string) ($charge['transaction_status'] ?? 'pending')),
                'fraud_status' => null,
                'payment_type' => 'qris',
                'qr_url' => (string) ($charge['qr_url'] ?? ''),
                'expires_at' => $expiresAt,
                'is_paid' => false,
                'paid_at' => null,
                'payload' => (array) ($charge['raw'] ?? []),
            ],
        );

        return response()->json([
            'order_id' => $charge['order_id'],
            'qr_url' => $charge['qr_url'],
            'expires_at' => $charge['expires_at'],
            'transaction_status' => $charge['transaction_status'],
        ]);
    }

    public function checkQrisPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'max:120'],
        ]);

        $orderId = trim((string) $validated['order_id']);
        $qrisPayment = QrisPayment::query()
            ->where('order_id', $orderId)
            ->where('user_id', (int) $request->user()->id)
            ->first();

        if ($qrisPayment === null) {
            return response()->json([
                'paid' => false,
                'message' => 'Data pembayaran QRIS tidak ditemukan. Silakan generate ulang.',
            ], 422);
        }

        if ($qrisPayment->sale_id !== null) {
            return response()->json([
                'paid' => false,
                'message' => 'QRIS ini sudah dipakai untuk transaksi sebelumnya. Silakan generate QRIS baru.',
            ], 422);
        }

        if ($qrisPayment->is_paid) {
            return response()->json([
                'paid' => true,
                'transaction_status' => $qrisPayment->transaction_status,
                'fraud_status' => $qrisPayment->fraud_status,
                'gross_amount' => (int) round((float) $qrisPayment->gross_amount),
                'message' => 'Pembayaran QRIS berhasil terverifikasi (webhook).',
            ]);
        }

        try {
            $status = $this->midtransQrisService->getTransactionStatus($orderId);
            $this->syncQrisPaymentStatus($qrisPayment, $status);
            $qrisPayment->refresh();
        } catch (RuntimeException $exception) {
            return response()->json([
                'paid' => false,
                'message' => $exception->getMessage(),
            ], 502);
        }
        $paid = (bool) $qrisPayment->is_paid;

        return response()->json([
            'paid' => $paid,
            'transaction_status' => $qrisPayment->transaction_status,
            'fraud_status' => $qrisPayment->fraud_status,
            'gross_amount' => (int) round((float) $qrisPayment->gross_amount),
            'message' => $paid
                ? 'Pembayaran QRIS berhasil terverifikasi.'
                : 'Pembayaran QRIS masih pending. Silakan cek lagi setelah pembayaran diselesaikan.',
        ]);
    }

    public function midtransWebhook(Request $request): JsonResponse
    {
        $payload = (array) $request->all();
        if ($payload === []) {
            return response()->json([
                'message' => 'Payload webhook kosong.',
            ], 400);
        }

        if (! $this->midtransQrisService->verifyNotificationSignature($payload)) {
            return response()->json([
                'message' => 'Signature Midtrans tidak valid.',
            ], 403);
        }

        $status = $this->midtransQrisService->normalizeNotificationPayload($payload);
        $orderId = trim((string) ($status['order_id'] ?? ''));
        if ($orderId === '') {
            return response()->json([
                'message' => 'order_id Midtrans tidak ditemukan.',
            ], 422);
        }

        $qrisPayment = QrisPayment::query()->where('order_id', $orderId)->first();
        if ($qrisPayment === null) {
            return response()->json([
                'message' => 'Webhook diterima. Order tidak ditemukan di sistem lokal.',
            ]);
        }

        $this->syncQrisPaymentStatus($qrisPayment, $status);

        return response()->json([
            'message' => 'Webhook Midtrans diproses.',
            'order_id' => $orderId,
            'paid' => (bool) $qrisPayment->fresh()?->is_paid,
        ]);
    }

    public function show(Sale $sale): View
    {
        return view('sales.show', [
            'sale' => $sale->load(['items.product', 'cashier']),
        ]);
    }

    public function receipt(Request $request, Sale $sale): View
    {
        return view('sales.receipt', [
            'sale' => $sale->load(['items.product', 'cashier']),
            'autoprint' => $request->boolean('autoprint'),
        ]);
    }

    private function syncQrisPaymentStatus(QrisPayment $qrisPayment, array $status): void
    {
        $raw = (array) ($status['raw'] ?? []);
        $isPaid = $qrisPayment->is_paid || $this->midtransQrisService->isPaid($status);
        $paidAt = $qrisPayment->paid_at;

        if ($isPaid && $paidAt === null) {
            $candidate = trim((string) ($raw['settlement_time'] ?? $raw['transaction_time'] ?? ''));
            if ($candidate !== '') {
                try {
                    $paidAt = Carbon::parse($candidate);
                } catch (\Throwable) {
                    $paidAt = now();
                }
            } else {
                $paidAt = now();
            }
        }

        $grossAmount = (int) ($status['gross_amount'] ?? 0);
        if ($grossAmount < 1) {
            $grossAmount = (int) round((float) $qrisPayment->gross_amount);
        }

        $qrisPayment->update([
            'transaction_status' => (string) ($status['transaction_status'] ?? $qrisPayment->transaction_status),
            'fraud_status' => (string) ($status['fraud_status'] ?? $qrisPayment->fraud_status),
            'payment_type' => (string) ($status['payment_type'] ?? $qrisPayment->payment_type),
            'gross_amount' => $grossAmount,
            'is_paid' => $isPaid,
            'paid_at' => $paidAt,
            'payload' => $raw,
        ]);
    }
}
