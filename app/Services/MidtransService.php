<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    protected string $serverKey;
    protected string $clientKey;
    protected bool $isProduction;
    protected string $baseUrl;

    public function __construct()
    {
        $this->serverKey = (string) config('services.midtrans.server_key', '');
        $this->clientKey = (string) config('services.midtrans.client_key', '');
        $this->isProduction = filter_var(config('services.midtrans.is_production', false), FILTER_VALIDATE_BOOLEAN);
        
        $this->baseUrl = $this->isProduction 
            ? 'https://app.midtrans.com' 
            : 'https://app.sandbox.midtrans.com';
    }

    /**
     * Create Snap Token from Order.
     */
    public function createSnapToken(Order $order): ?array
    {
        if ($this->serverKey === '') {
            Log::error('Midtrans server key is not configured.');
            return null;
        }

        $url = $this->baseUrl . '/snap/v1/transactions';
        $user = $order->user;

        $items = [];
        foreach ($order->orderDetails as $detail) {
            $items[] = [
                'id' => 'item_' . $detail->inventory_id . ($detail->inventory_variant_id ? '_' . $detail->inventory_variant_id : ''),
                'price' => (int) $detail->unit_price,
                'quantity' => (int) $detail->quantity,
                'name' => substr($detail->inventory->name . ($detail->variant ? ' - ' . $detail->variant->variant_name : ''), 0, 50),
            ];
        }

        // Format body
        $body = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) $order->total_amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '08123456789',
                'billing_address' => [
                    'first_name' => $user->name,
                    'address' => $user->address ?? 'Alamat',
                    'phone' => $user->phone ?? '08123456789',
                ]
            ],
            'item_details' => $items,
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minutes',
                'duration' => 60,
            ]
        ];

        try {
            // Make POST request with Basic Auth using Laravel HTTP Client
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->withBasicAuth($this->serverKey, '')
            ->post($url, $body);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Midtrans Snap Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify Midtrans Signature Key to prevent tampering.
     */
    public function verifyNotification(array $payload): bool
    {
        if (!isset($payload['signature_key'], $payload['order_id'], $payload['status_code'], $payload['gross_amount'])) {
            return false;
        }

        $orderId = $payload['order_id'];
        $statusCode = $payload['status_code'];
        $grossAmount = $payload['gross_amount'];
        
        // Midtrans signature key formula: SHA512(order_id + status_code + gross_amount + ServerKey)
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        return hash_equals($expectedSignature, $payload['signature_key']);
    }
}
