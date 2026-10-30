<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Process notification payload from Midtrans (Idempotent).
     *
     * @param array $payload
     * @return array [bool $success, string $message, int $statusCode]
     */
    public function processWebhookNotification(array $payload): array
    {
        if (!$this->midtransService->verifyNotification($payload)) {
            Log::warning('Midtrans signature verification failed.');
            return [false, 'Invalid Signature Key', 400];
        }

        $orderNumber = $payload['order_id'] ?? null;
        $attempt = PaymentAttempt::where('midtrans_order_id', $orderNumber)->first();

        if (!$attempt) {
            $attempt = $this->findOrBackfillLegacyAttempt($orderNumber);
        }

        if (!$attempt) {
            Log::warning('Verified Midtrans notification has no matching payment attempt.', ['order_id' => $orderNumber]);
            return [false, 'Payment attempt not found', 404];
        }

        return DB::transaction(function () use ($attempt, $payload, $orderNumber) {
            $attempt = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($attempt->payment_id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            $incomingStatus = $this->mapTransactionStatus(
                $payload['transaction_status'] ?? null,
                $payload['fraud_status'] ?? null
            );
            if ($incomingStatus === 'unknown') {
                Log::warning('Midtrans notification contains an unsupported transaction status.', [
                    'order_id' => $orderNumber,
                    'transaction_status' => $payload['transaction_status'] ?? null,
                ]);
                return [false, 'Unsupported transaction status', 422];
            }

            if (!isset($payload['gross_amount']) || !$this->amountsMatch($payload['gross_amount'], $attempt->gross_amount)) {
                $attempt->update([
                    'status' => 'unknown',
                    'last_error' => 'Signed Midtrans gross_amount does not match the server-side attempt amount.',
                    'payload_json' => array_merge($attempt->payload_json ?? [], ['rejected_notification' => $payload]),
                ]);
                $payment->update(['payment_status' => 'conflict']);
                $order->update(['payment_status' => 'conflict', 'status' => 'payment_conflict']);
                Log::critical('Midtrans notification amount mismatch.', [
                    'order_id' => $orderNumber,
                    'attempt_id' => $attempt->id,
                ]);

                return [false, 'Payment amount mismatch; order requires reconciliation', 409];
            }

            if ($this->isStaleStatus($attempt, $incomingStatus)) {
                return [true, 'Stale notification ignored', 200];
            }

            // Jika attempt sedang dalam status cancel_unconfirmed dan Midtrans masih melaporkan
            // 'pending', jangan overwrite kembali ke 'pending' — cancel sudah pernah diminta.
            // Hanya update status jika Midtrans memberikan konfirmasi definitif (cancelled, paid, expired, failed).
            $preserveCancelUnconfirmed = $attempt->status === 'cancel_unconfirmed'
                && $incomingStatus === 'pending';

            if (!$preserveCancelUnconfirmed) {
                $attempt->update([
                    'transaction_id' => $payload['transaction_id'] ?? $attempt->transaction_id,
                    'payment_type'   => $payload['payment_type'] ?? $attempt->payment_type,
                    'status'         => $incomingStatus,
                    'payload_json'   => array_merge($attempt->payload_json ?? [], ['notification' => $payload]),
                    'paid_at'        => $incomingStatus === 'paid' ? ($attempt->paid_at ?? now()) : $attempt->paid_at,
                ]);
            } else {
                // Catat notifikasi di payload_json tapi pertahankan status cancel_unconfirmed
                $attempt->update([
                    'payload_json' => array_merge($attempt->payload_json ?? [], ['notification' => $payload]),
                ]);
            }

            $this->reconcilePaymentAndOrder($payment, $order);

            return [true, 'Notification processed successfully', 200];
        });
    }

    public function processStatusResponse(array $payload): array
    {
        $midtransOrderId = $payload['order_id'] ?? null;
        if (!is_string($midtransOrderId) || $midtransOrderId === '') {
            return [false, 'Midtrans status response is missing order_id', 422];
        }

        $attempt = PaymentAttempt::where('midtrans_order_id', $midtransOrderId)->first();
        if (!$attempt) {
            $attempt = $this->findOrBackfillLegacyAttempt($midtransOrderId);
        }

        if (!$attempt) {
            Log::warning('Midtrans status response has no matching payment attempt.', ['order_id' => $midtransOrderId]);
            return [false, 'Payment attempt not found', 404];
        }

        return DB::transaction(function () use ($attempt, $payload, $midtransOrderId) {
            $attempt = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($attempt->payment_id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            $incomingStatus = $this->mapTransactionStatus(
                $payload['transaction_status'] ?? null,
                $payload['fraud_status'] ?? null
            );
            if ($incomingStatus === 'unknown') {
                Log::warning('Midtrans status response contains an unsupported transaction status.', [
                    'order_id' => $midtransOrderId,
                    'transaction_status' => $payload['transaction_status'] ?? null,
                ]);
                return [false, 'Unsupported transaction status in Midtrans status response', 422];
            }

            if (!isset($payload['gross_amount']) || !$this->amountsMatch($payload['gross_amount'], $attempt->gross_amount)) {
                $attempt->update([
                    'status' => 'unknown',
                    'last_error' => 'Midtrans status gross_amount does not match the server-side attempt amount.',
                    'payload_json' => array_merge($attempt->payload_json ?? [], ['rejected_status_response' => $payload]),
                ]);
                $payment->update(['payment_status' => 'conflict']);
                $order->update(['payment_status' => 'conflict', 'status' => 'payment_conflict']);
                Log::critical('Midtrans status response amount mismatch.', [
                    'order_id' => $midtransOrderId,
                    'attempt_id' => $attempt->id,
                ]);

                return [false, 'Payment amount mismatch; order requires reconciliation', 409];
            }

            if ($this->isStaleStatus($attempt, $incomingStatus)) {
                return [true, 'Stale Midtrans status response ignored', 200];
            }

            $preserveCancelUnconfirmed = $attempt->status === 'cancel_unconfirmed'
                && $incomingStatus === 'pending';
            $terminalWithoutPayment = in_array($incomingStatus, ['cancelled', 'expired', 'failed'], true);
            $attempt->update([
                'transaction_id' => $payload['transaction_id'] ?? $attempt->transaction_id,
                'payment_type' => $payload['payment_type'] ?? $attempt->payment_type,
                'status' => $preserveCancelUnconfirmed ? 'cancel_unconfirmed' : $incomingStatus,
                'snap_token' => $terminalWithoutPayment ? null : $attempt->snap_token,
                'payload_json' => array_merge($attempt->payload_json ?? [], ['status_check' => $payload]),
                'last_error' => $preserveCancelUnconfirmed ? $attempt->last_error : null,
                'paid_at' => $incomingStatus === 'paid' ? ($attempt->paid_at ?? now()) : $attempt->paid_at,
            ]);

            $this->reconcilePaymentAndOrder($payment, $order);

            return [true, 'Midtrans status response processed successfully', 200];
        });
    }

    public function reconcilePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($lockedPayment->order_id)->lockForUpdate()->firstOrFail();
            $this->reconcilePaymentAndOrder($lockedPayment, $order);
        });
    }

    private function findOrBackfillLegacyAttempt(string $midtransOrderId): ?PaymentAttempt
    {
        $payment = Payment::where('payload_json->midtrans_order_id', $midtransOrderId)->first();
        if (!$payment) {
            $order = Order::where('order_number', $midtransOrderId)->with('payment')->first();
            $payment = $order?->payment;
        }

        if (!$payment) {
            return null;
        }

        $legacyPayload = $payment->payload_json ?? [];
        $legacyOrderNumber = $payment->order()->value('order_number');
        $legacyCurrentId = data_get($legacyPayload, 'midtrans_order_id', $legacyOrderNumber);
        if ($legacyCurrentId !== $midtransOrderId) {
            return null;
        }

        $legacyStatus = in_array($payment->payment_status, ['pending', 'paid', 'failed', 'expired', 'cancelled', 'unknown'], true)
            ? $payment->payment_status
            : 'unknown';

        return PaymentAttempt::firstOrCreate(['midtrans_order_id' => $midtransOrderId], [
            'payment_id' => $payment->id,
            'midtrans_order_id' => $midtransOrderId,
            'transaction_id' => $payment->transaction_id,
            'payment_type' => $payment->payment_type,
            'gross_amount' => $payment->gross_amount,
            'status' => $legacyStatus,
            'snap_token' => $payment->snap_token,
            'payload_json' => $legacyPayload,
        ]);
    }

    private function reconcilePaymentAndOrder(Payment $payment, Order $order): void
    {
        $attempts = $payment->attempts()->get();
        $paidAttempts = $attempts->where('status', 'paid');

        if ($paidAttempts->count() > 1) {
            $payment->update(['payment_status' => 'conflict']);
            $order->update(['payment_status' => 'conflict', 'status' => 'payment_conflict']);
            Log::critical('Multiple Midtrans attempts settled for one order.', [
                'order_id' => $order->order_number,
                'attempt_ids' => $paidAttempts->pluck('midtrans_order_id')->all(),
            ]);
            return;
        }

        if ($paidAttempts->count() === 1) {
            $winner = $paidAttempts->first();
            $otherOpenAttempts = $attempts->contains(function (PaymentAttempt $attempt) use ($winner) {
                return $attempt->id !== $winner->id
                    && in_array($attempt->status, ['creating', 'pending', 'cancel_requested', 'cancel_unconfirmed', 'unknown'], true);
            });
            $hasPaymentConflict = $otherOpenAttempts || $order->status === 'cancelled';
            $payment->update([
                'transaction_id' => $winner->transaction_id,
                'payment_type' => $winner->payment_type,
                'payment_status' => $hasPaymentConflict ? 'conflict' : 'paid',
                'paid_at' => $winner->paid_at,
                'snap_token' => $winner->snap_token,
            ]);

            if ($hasPaymentConflict) {
                $order->update(['payment_status' => 'conflict', 'status' => 'payment_conflict']);
            } elseif ($order->status !== 'payment_conflict') {
                $order->update(['payment_status' => 'paid', 'status' => 'paid']);
            }

            return;
        }

        if ($order->status === 'paid' || $order->status === 'payment_conflict') {
            return;
        }

        $activeAttempt = $attempts->sortByDesc('id')->first();
        $lastProviderStatus = data_get($activeAttempt?->payload_json, 'status_check.transaction_status')
            ?? data_get($activeAttempt?->payload_json, 'notification.transaction_status');
        $summaryStatus = match ($activeAttempt?->status) {
            'pending', 'creating', 'cancel_requested' => 'pending',
            'cancel_unconfirmed' => $lastProviderStatus === 'pending' ? 'pending' : 'unknown',
            'unknown' => 'unknown',
            'failed', 'rejected' => 'failed',
            'cancelled' => 'cancelled',
            'expired' => 'expired',
            default => 'pending',
        };

        $payment->update([
            'transaction_id' => $activeAttempt?->transaction_id,
            'payment_type' => $activeAttempt?->payment_type,
            'payment_status' => $summaryStatus,
            'paid_at' => null,
            'snap_token' => $activeAttempt?->snap_token,
        ]);

        if ($order->status === 'cancelled') {
            $order->update(['payment_status' => $summaryStatus]);
            return;
        }

        $order->update([
            'payment_status' => $summaryStatus,
            'status' => 'pending_payment',
        ]);
    }

    private function mapTransactionStatus(?string $transactionStatus, ?string $fraudStatus = null): string
    {
        return match ($transactionStatus) {
            'settlement' => 'paid',
            'capture' => match ($fraudStatus) {
                'accept' => 'paid',
                'challenge' => 'pending',
                'deny' => 'failed',
                default => 'unknown',
            },
            'pending' => 'pending',
            'deny' => 'failed',
            'cancel', 'cancelled' => 'cancelled',
            'expire', 'expired' => 'expired',
            'refund', 'partial_refund' => 'refunded',
            default => 'unknown',
        };
    }

    private function isStaleStatus(PaymentAttempt $attempt, string $incomingStatus): bool
    {
        if ($incomingStatus === 'paid') {
            return false;
        }

        return in_array($attempt->status, ['paid', 'failed', 'rejected', 'cancelled', 'expired'], true);
    }

    private function amountsMatch(string|int|float $receivedAmount, string|int|float $expectedAmount): bool
    {
        if (!is_numeric($receivedAmount) || !is_numeric($expectedAmount)) {
            return false;
        }

        return (int) round((float) $receivedAmount * 100) === (int) round((float) $expectedAmount * 100);
    }
}
