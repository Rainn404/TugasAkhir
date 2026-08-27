<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
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
        Log::info('PaymentService Webhook Received: ', $payload);

        // Verify Signature
        if (!$this->midtransService->verifyNotification($payload)) {
            Log::warning('Midtrans signature verification failed.');
            return [false, 'Invalid Signature Key', 400];
        }

        $orderNumber = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        $order = Order::where('order_number', $orderNumber)->first();
        if (!$order) {
            Log::warning("Order not found: {$orderNumber}");
            return [false, 'Order not found', 404];
        }

        $payment = $order->payment ?? Payment::create([
            'order_id' => $order->id,
            'gross_amount' => $order->total_amount,
        ]);

        $orderStatus = $order->status;
        $paymentStatus = 'pending';
        $paidAt = null;

        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            $paymentStatus = 'paid';
            $orderStatus = 'paid';
            $paidAt = now();
        } elseif ($transactionStatus === 'pending') {
            $paymentStatus = 'pending';
            $orderStatus = 'pending_payment';
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $paymentStatus = in_array($transactionStatus, ['cancel', 'expire']) ? 'expired' : 'failed';
            $orderStatus = 'cancelled';
        }

        // Update payment and order
        $payment->update([
            'transaction_id' => $transactionId,
            'payment_type' => $paymentType,
            'payment_status' => $paymentStatus,
            'payload_json' => $payload,
            'paid_at' => $paidAt ?? $payment->paid_at,
        ]);

        $order->update([
            'status' => $orderStatus,
            'payment_status' => $paymentStatus,
        ]);

        Log::info("Order {$orderNumber} successfully updated to {$paymentStatus} / {$orderStatus}");

        return [true, 'Notification processed successfully', 200];
    }
}
