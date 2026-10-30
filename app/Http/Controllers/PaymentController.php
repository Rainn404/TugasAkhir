<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle webhook callback notification from Midtrans.
     */
    public function handleNotification(Request $request)
    {
        $result = $this->paymentService->processWebhookNotification($request->all());

        return response()->json([
            'message' => $result[1]
        ], $result[2]);
    }

    public function refreshStatus(string $orderNumber, MidtransService $midtransService): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with('payment.attempts')
            ->firstOrFail();

        if (Auth::user()->role !== 'admin' && $order->user_id !== Auth::id()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (!$order->payment) {
            return response()->json(['message' => 'Data pembayaran tidak ditemukan.'], 404);
        }

        $this->refreshPaymentAttempts($order->payment, $midtransService);
        $order->refresh();
        $order->payment->refresh();
        $attempt = $order->payment->attempts()->latest('id')->first();

        $isUnresolved = in_array($attempt?->status, ['creating', 'unknown', 'cancel_requested', 'cancel_unconfirmed'], true);

        return response()->json([
            'order_status' => $order->status,
            'payment_status' => $order->payment->payment_status,
            'attempt_status' => $attempt?->status,
        ], $isUnresolved ? 202 : 200);
    }

    public function retrySnapToken(string $orderNumber, MidtransService $midtransService)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['payment.attempts', 'user', 'orderDetails.inventory', 'orderDetails.variant'])
            ->firstOrFail();

        if (Auth::user()->role !== 'admin' && $order->user_id !== Auth::id()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($order->status !== 'pending_payment' || in_array($order->payment_status, ['paid', 'conflict'], true)) {
            return back()->with('error', 'Pesanan ini tidak lagi menunggu pembayaran.');
        }

        $payment = $order->payment ?? Payment::create([
            'order_id' => $order->id,
            'gross_amount' => $order->total_amount,
            'payment_status' => 'pending',
        ]);

        $latestAttempt = $payment->attempts()->latest('id')->first();
        if (!$latestAttempt && ($payment->snap_token || data_get($payment->payload_json, 'midtrans_order_id') || $payment->transaction_id)) {
            $latestAttempt = $this->backfillLegacyAttempt($payment, $orderNumber);
        }
        $this->refreshPaymentAttempts($payment, $midtransService);
        $payment->refresh();
        $order->refresh();
        if (in_array($order->status, ['paid', 'payment_conflict'], true)) {
            return redirect()->route('dashboard.order', $orderNumber)
                ->with('error', 'Status pembayaran berubah saat diperiksa. Lihat ringkasan dan riwayat attempt.');
        }

        $openAttempts = $payment->attempts()
            ->whereIn('status', ['creating', 'pending', 'cancel_requested', 'cancel_unconfirmed', 'unknown'])
            ->orderByDesc('id')
            ->get();
        if ($openAttempts->isNotEmpty()) {
            if ($openAttempts->contains(fn (PaymentAttempt $attempt) => $attempt->status === 'unknown')) {
                return redirect()->route('dashboard.order', $orderNumber)
                    ->with('error', 'Status satu atau lebih attempt Midtrans belum dapat dipastikan. Retry diblokir agar tidak membuat pembayaran ganda.');
            }

            // Attempt yang sedang dalam status cancel_unconfirmed tidak boleh dipakai untuk bayar
            // maupun dibuat attempt baru — pengguna harus menyelesaikan cancel terlebih dahulu.
            if ($openAttempts->contains(fn (PaymentAttempt $attempt) => $attempt->status === 'cancel_unconfirmed')) {
                return redirect()->route('dashboard.order', $orderNumber)
                    ->with('error', 'Pembatalan sebelumnya belum dikonfirmasi Midtrans. Coba batalkan kembali melalui tombol di halaman ini sebelum membuat pembayaran baru.');
            }

            $activeAttempt = $openAttempts->first();
            if ($activeAttempt->snap_token) {
                $payment->update(['snap_token' => $activeAttempt->snap_token]);
                return redirect()->route('dashboard.order', $orderNumber)
                    ->with('success', 'Token pembayaran aktif. Klik Bayar Sekarang untuk melanjutkan.');
            }

            return redirect()->route('dashboard.order', $orderNumber)
                ->with('error', 'Attempt Midtrans masih aktif tetapi belum memiliki token. Periksa status atau batalkan attempt sebelum retry.');
        }

        $attempt = DB::transaction(function () use ($payment, $orderNumber) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::whereKey($lockedPayment->order_id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->status !== 'pending_payment' || in_array($lockedOrder->payment_status, ['paid', 'conflict'], true)) {
                return null;
            }

            $latest = $lockedPayment->attempts()->latest('id')->first();

            if ($latest && in_array($latest->status, ['creating', 'pending', 'cancel_requested', 'cancel_unconfirmed', 'unknown'], true)) {
                return null;
            }

            do {
                $midtransOrderId = $orderNumber . '-A' . strtoupper(Str::random(10));
            } while (PaymentAttempt::where('midtrans_order_id', $midtransOrderId)->exists());

            return PaymentAttempt::create([
                'payment_id' => $lockedPayment->id,
                'midtrans_order_id' => $midtransOrderId,
                'gross_amount' => $lockedPayment->gross_amount,
                'status' => 'creating',
            ]);
        });

        if (!$attempt) {
            return redirect()->route('dashboard.order', $orderNumber)
                ->with('error', 'Sudah ada attempt pembayaran aktif atau belum terselesaikan. Periksa status Midtrans sebelum membuat attempt baru.');
        }

        $snapResult = $midtransService->createSnapToken($order, $attempt->midtrans_order_id);

        if (isset($snapResult['status_payload']) && is_array($snapResult['status_payload'])) {
            $statusResult = $this->paymentService->processStatusResponse($snapResult['status_payload']);
            if ($statusResult[0] || $statusResult[2] === 409) {
                return redirect()->route('dashboard.order', $orderNumber)
                    ->with($statusResult[2] === 409 ? 'error' : 'success', $statusResult[1]);
            }

            $snapResult['state'] = 'unknown';
            $snapResult['message'] = 'Status transaksi Midtrans belum dapat diproses dan perlu diperiksa kembali.';
        }

        if (($snapResult['state'] ?? null) !== 'created' || empty($snapResult['token'])) {
            $attemptStatus = $snapResult['state'] ?? 'unknown';
            $attempt->update([
                'status' => in_array($attemptStatus, ['rejected', 'failed'], true) ? 'failed' : $attemptStatus,
                'last_error' => $snapResult['message'] ?? 'Snap token creation was not confirmed.',
                'payload_json' => $snapResult,
            ]);
            $payment->update([
                'payment_status' => $attempt->status === 'failed' ? 'failed' : 'unknown',
                'snap_token' => null,
                'payload_json' => ['active_attempt_id' => $attempt->id],
            ]);
            $order->update(['payment_status' => $payment->payment_status]);

            return redirect()->route('dashboard.order', $orderNumber)
                ->with('error', $snapResult['message'] ?? 'Pembuatan token belum terkonfirmasi. Periksa status provider sebelum mencoba lagi.');
        }

        $attempt->update([
            'status' => 'pending',
            'snap_token' => $snapResult['token'],
            'payload_json' => $snapResult['payload'],
        ]);
        $payment->update([
            'payment_status' => 'pending',
            'snap_token' => $snapResult['token'],
            'payload_json' => ['active_attempt_id' => $attempt->id],
        ]);

        return redirect()->route('dashboard.order', $orderNumber)
            ->with('success', 'Pembayaran siap. Klik Bayar Sekarang untuk memilih metode pembayaran.');
    }

    public function cancelPendingPayment(string $orderNumber, MidtransService $midtransService)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with('payment.attempts')
            ->firstOrFail();

        if (Auth::user()->role !== 'admin' && $order->user_id !== Auth::id()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $payment = $order->payment;
        $attempt = $payment?->attempts()->latest('id')->first();
        if (!$attempt && $payment?->snap_token) {
            $attempt = $this->backfillLegacyAttempt($payment, $orderNumber);
        }
        if ($order->status !== 'pending_payment' || in_array($order->payment_status, ['paid', 'conflict'], true) || !$attempt) {
            return back()->with('error', 'Pembayaran ini tidak tersedia untuk dibatalkan.');
        }

        $this->refreshPaymentAttempts($payment, $midtransService);
        $payment->refresh();
        $order->refresh();
        $attempt->refresh();

        if (in_array($order->status, ['paid', 'payment_conflict'], true) || $attempt->status === 'paid') {
            return redirect()->route('dashboard.order', $orderNumber)
                ->with('error', 'Status pembayaran telah berubah. Pembatalan dihentikan; periksa riwayat attempt.');
        }
        if (in_array($attempt->status, ['rejected', 'failed', 'cancelled', 'expired'], true)) {
            return redirect()->route('dashboard.order', $orderNumber)
                ->with('warning', 'Attempt ini sudah tidak aktif menurut Midtrans. Siapkan pembayaran baru bila diperlukan.');
        }

        $cancelResult = $midtransService->cancelTransaction($attempt->midtrans_order_id);
        $statusProcessingResult = null;
        if (!empty($cancelResult['status_payload']) && is_array($cancelResult['status_payload'])) {
            $statusProcessingResult = $this->paymentService->processStatusResponse($cancelResult['status_payload']);
        }

        if (($cancelResult['provider_status'] ?? null) === 'paid' && ($statusProcessingResult[0] ?? false)) {
            return redirect()->route('dashboard.order', $orderNumber)
                ->with('error', 'Pembayaran telah terkonfirmasi oleh Midtrans dan tidak dapat dibatalkan.');
        }

        $attempt->refresh();
        $payment->refresh();
        $order->refresh();
        if ($attempt->status === 'paid' || in_array($order->status, ['paid', 'payment_conflict'], true)) {
            return redirect()->route('dashboard.order', $orderNumber)
                ->with('error', 'Status pembayaran telah berubah. Pembatalan dihentikan; periksa riwayat attempt.');
        }

        if (!$cancelResult['success'] || !($statusProcessingResult[0] ?? false)) {
            if (($statusProcessingResult[2] ?? null) === 409) {
                return redirect()->route('dashboard.order', $orderNumber)
                    ->with('error', $statusProcessingResult[1]);
            }

            $providerStatus = $cancelResult['provider_status'] ?? null;
            $attempt->update([
                'status' => 'cancel_unconfirmed',
                'cancel_requested_at' => now(),
                'last_error' => $cancelResult['message'] ?? ($statusProcessingResult[1] ?? 'Pembatalan belum terkonfirmasi.'),
                'payload_json' => array_merge($attempt->payload_json ?? [], ['cancel_result' => $cancelResult]),
            ]);
            if ($providerStatus === null) {
                $payment->update(['payment_status' => 'unknown']);
                $order->update(['payment_status' => 'unknown']);
            }

            $customerMessage = $providerStatus === 'pending'
                ? 'Midtrans masih melaporkan transaksi menunggu. Pembayaran baru belum dibuat; periksa kembali status transaksi sebelum melanjutkan.'
                : 'Midtrans belum dapat memastikan status transaksi. Belum ada pembayaran baru dibuat. Periksa status kembali setelah layanan merespons.';

            return redirect()->route('dashboard.order', $orderNumber)->with(
                'error',
                $customerMessage
            );
        }

        $expectedTerminalStatus = match ($cancelResult['provider_status']) {
            'cancel', 'cancelled' => 'cancelled',
            'expire', 'expired' => 'expired',
            'failed' => 'failed',
            default => null,
        };
        if ($expectedTerminalStatus === null || $attempt->status !== $expectedTerminalStatus) {
            return redirect()->route('dashboard.order', $orderNumber)
                ->with('error', 'Status terminal belum tersimpan sesuai respons Midtrans. Pembayaran baru tetap diblokir sampai status diperiksa kembali.');
        }

        return redirect()->route('dashboard.order', $orderNumber)
            ->with('success', 'Status terminal attempt telah dikonfirmasi oleh Midtrans. Pembayaran baru dapat disiapkan jika diperlukan.');
    }

    private function backfillLegacyAttempt(Payment $payment, string $orderNumber): PaymentAttempt
    {
        $midtransOrderId = data_get($payment->payload_json, 'midtrans_order_id') ?? $orderNumber;
        $legacyStatus = match ($payment->payment_status) {
            'paid' => 'paid',
            'failed' => 'failed',
            'expired' => 'expired',
            'cancelled' => 'cancelled',
            'unknown' => 'unknown',
            default => 'pending',
        };

        return PaymentAttempt::firstOrCreate(['midtrans_order_id' => $midtransOrderId], [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'payment_type' => $payment->payment_type,
            'gross_amount' => $payment->gross_amount,
            'status' => $legacyStatus,
            'snap_token' => $payment->snap_token,
            'payload_json' => $payment->payload_json,
            'paid_at' => $payment->paid_at,
        ]);
    }

    private function refreshPaymentAttempts(Payment $payment, MidtransService $midtransService): void
    {
        $attempts = $payment->attempts()
            ->whereIn('status', ['creating', 'pending', 'cancel_requested', 'cancel_unconfirmed', 'unknown'])
            ->orderBy('id')
            ->get();

        foreach ($attempts as $attempt) {
            $statusResult = $midtransService->getTransactionStatus($attempt->midtrans_order_id);

            if ($statusResult['success'] ?? false) {
                $statusProcessingResult = $this->paymentService->processStatusResponse($statusResult['data']);
                if (!$statusProcessingResult[0]) {
                    if ($statusProcessingResult[2] === 409) {
                        continue;
                    }

                    $attempt->update([
                        'status' => $attempt->status === 'cancel_unconfirmed' ? 'cancel_unconfirmed' : 'unknown',
                        'last_error' => 'Midtrans status response could not be processed: ' . $statusProcessingResult[1],
                    ]);
                }
                continue;
            }

            if ($statusResult['not_found'] ?? false) {
                $isOnlyAttemptWithoutToken = $attempt->snap_token === null
                    && $payment->attempts()->where('id', '!=', $attempt->id)->whereIn('status', ['pending', 'paid'])->doesntExist();

                $attempt->update([
                    'status' => ($isOnlyAttemptWithoutToken && in_array($attempt->status, ['unknown', 'creating'], true))
                        ? 'failed'
                        : ($attempt->status === 'cancel_unconfirmed' ? 'cancel_unconfirmed' : 'unknown'),
                    'last_error' => $statusResult['message'] ?? 'Midtrans status API did not find the transaction.',
                ]);
                continue;
            }

            $attempt->update([
                'status' => $attempt->status === 'cancel_unconfirmed' ? 'cancel_unconfirmed' : 'unknown',
                'last_error' => $statusResult['message'] ?? 'Midtrans status could not be confirmed.',
            ]);
        }

        $this->paymentService->reconcilePayment($payment);
    }
}
