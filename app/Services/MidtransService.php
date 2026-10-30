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
    protected string $apiBaseUrl;

    public function __construct()
    {
        $this->serverKey = (string) config('services.midtrans.server_key', '');
        $this->clientKey = (string) config('services.midtrans.client_key', '');
        $this->isProduction = filter_var(config('services.midtrans.is_production', false), FILTER_VALIDATE_BOOLEAN);
        
        $this->baseUrl = $this->isProduction 
            ? 'https://app.midtrans.com' 
            : 'https://app.sandbox.midtrans.com';
        $this->apiBaseUrl = $this->isProduction
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    /**
     * Create Snap Token from Order.
     */
    public function createSnapToken(Order $order, ?string $midtransOrderId = null): ?array
    {
        if ($this->serverKey === '') {
            return ['state' => 'rejected', 'message' => 'Midtrans Server Key belum dikonfigurasi.'];
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
                'order_id' => $midtransOrderId ?? $order->order_number,
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
            ->connectTimeout(5)
            ->timeout(15)
            ->post($url, $body);

            $responseData = $response->json() ?? [];
            if ($response->successful() && !empty($responseData['token'])) {
                return [
                    'state' => 'created',
                    'token' => $responseData['token'],
                    'midtrans_order_id' => $midtransOrderId ?? $order->order_number,
                    'payload' => $responseData,
                ];
            }

            $providerStatus = $this->getTransactionStatus($midtransOrderId ?? $order->order_number);
            $this->logProviderFailure('snap_create', $midtransOrderId ?? $order->order_number, $response->status(), $responseData['status_message'] ?? null);

            if ($providerStatus['success'] ?? false) {
                return [
                    'state' => $this->mapProviderStatus($providerStatus['data']['transaction_status'] ?? null),
                    'provider_status' => $providerStatus['data']['transaction_status'] ?? null,
                    'status_payload' => $providerStatus['data'],
                    'message' => 'Midtrans created a transaction but did not return a usable Snap token.',
                    'http_status' => $response->status(),
                ];
            }

            if ($providerStatus['not_found'] ?? false) {
                return [
                    'state' => 'rejected',
                    'message' => $this->safeProviderMessage($responseData['status_message'] ?? null)
                        ?? 'Midtrans confirms that no transaction was created.',
                    'http_status' => $response->status(),
                ];
            }

            return [
                'state' => $response->clientError() ? 'rejected' : 'unknown',
                'message' => $this->safeProviderMessage($responseData['status_message'] ?? null)
                    ?? 'Midtrans did not confirm whether a transaction was created.',
                'http_status' => $response->status(),
            ];
        } catch (\Exception $e) {
            $midtransOrderId ??= $order->order_number;
            Log::warning('Midtrans Snap connection failed.', [
                'operation' => 'snap_create',
                'order_id' => $midtransOrderId,
                'exception' => class_basename($e),
            ]);

            $providerStatus = $this->getTransactionStatus($midtransOrderId);
            if ($providerStatus['success'] ?? false) {
                return [
                    'state' => $this->mapProviderStatus($providerStatus['data']['transaction_status'] ?? null),
                    'provider_status' => $providerStatus['data']['transaction_status'] ?? null,
                    'status_payload' => $providerStatus['data'],
                    'message' => 'Midtrans transaction exists, but the Snap token response was interrupted.',
                ];
            }

            return [
                'state' => ($providerStatus['not_found'] ?? false) ? 'rejected' : 'unknown',
                'message' => ($providerStatus['not_found'] ?? false)
                    ? 'Midtrans confirms that no transaction was created.'
                    : 'Connection failed and Midtrans could not confirm transaction creation.',
            ];
        }
    }

    public function cancelTransaction(string $midtransOrderId): array
    {
        if ($this->serverKey === '') {
            return ['success' => false, 'resolved' => false, 'message' => 'Midtrans Server Key belum dikonfigurasi.'];
        }

        $url = $this->apiBaseUrl . '/v2/' . rawurlencode($midtransOrderId) . '/cancel';
        $response = null;
        $responseData = [];
        $retriedAfterServerError = false;

        try {
            $response = Http::acceptJson()
                ->withBasicAuth($this->serverKey, '')
                ->connectTimeout(5)
                ->timeout(12)
                ->post($url);

            $responseData = $response->json() ?? [];

            if ($response->status() === 500) {
                $retriedAfterServerError = true;
                usleep(1_000_000);
                $response = Http::acceptJson()
                    ->withBasicAuth($this->serverKey, '')
                    ->connectTimeout(5)
                    ->timeout(12)
                    ->post($url);
                $responseData = $response->json() ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Midtrans cancel connection failed.', [
                'operation' => 'cancel',
                'order_id' => $midtransOrderId,
                'exception' => class_basename($e),
            ]);
        }

        $status = $this->getTransactionStatus($midtransOrderId);
        $expireFallback = null;
        if ($retriedAfterServerError
            && ($status['success'] ?? false)
            && ($status['data']['transaction_status'] ?? null) === 'pending'
        ) {
            usleep(1_000_000);
            $expireFallback = $this->expireTransaction($midtransOrderId);
            $status = $this->getTransactionStatus($midtransOrderId);
        }

        $result = $this->resolveCancellation(
            $midtransOrderId,
            $response?->status(),
            $responseData,
            $status,
            $retriedAfterServerError,
            $expireFallback
        );
        $result['cancel_attempts'] = $retriedAfterServerError ? 2 : 1;
        $result['retried_after_http_500'] = $retriedAfterServerError;
        if ($expireFallback !== null) {
            $result['expire_fallback'] = [
                'http_status' => $expireFallback['http_status'] ?? null,
                'status_code' => $expireFallback['data']['status_code'] ?? null,
                'status_message' => $this->safeProviderMessage($expireFallback['data']['status_message'] ?? null),
            ];
        }

        return $result;
    }

    private function expireTransaction(string $midtransOrderId): array
    {
        try {
            $response = Http::acceptJson()
                ->withBasicAuth($this->serverKey, '')
                ->connectTimeout(5)
                ->timeout(12)
                ->post($this->apiBaseUrl . '/v2/' . rawurlencode($midtransOrderId) . '/expire');
            $data = $response->json() ?? [];

            if (!$response->successful()) {
                $this->logProviderFailure('expire_fallback', $midtransOrderId, $response->status(), $data['status_message'] ?? null);
            }

            return [
                'success' => $response->successful(),
                'http_status' => $response->status(),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::warning('Midtrans expire fallback connection failed.', [
                'operation' => 'expire_fallback',
                'order_id' => $midtransOrderId,
                'exception' => class_basename($e),
            ]);

            return [
                'success' => false,
                'message' => 'Could not connect to Midtrans expire API.',
            ];
        }
    }

    public function getTransactionStatus(string $midtransOrderId): array
    {
        if ($this->serverKey === '') {
            return ['success' => false, 'message' => 'Midtrans Server Key belum dikonfigurasi.'];
        }

        try {
            $response = Http::acceptJson()
                ->withBasicAuth($this->serverKey, '')
                ->retry(2, 300, null, false)
                ->connectTimeout(5)
                ->timeout(10)
                ->get($this->apiBaseUrl . '/v2/' . rawurlencode($midtransOrderId) . '/status');

            $data = $response->json() ?? [];
            $isNotFound = $response->status() === 404 || (string)($data['status_code'] ?? '') === '404';
            if ($isNotFound) {
                return [
                    'success' => false,
                    'not_found' => true,
                    'http_status' => $response->status(),
                    'message' => $this->safeProviderMessage($data['status_message'] ?? 'Transaction not found'),
                ];
            }

            if ($response->successful()) {
                if (($data['order_id'] ?? null) !== $midtransOrderId) {
                    Log::warning('Midtrans status response order_id did not match request.', [
                        'requested_order_id' => $midtransOrderId,
                        'response_order_id' => $data['order_id'] ?? null,
                    ]);
                    return ['success' => false, 'not_found' => false, 'http_status' => $response->status(), 'message' => 'Midtrans status response order ID mismatch.'];
                }

                return ['success' => true, 'data' => $data, 'http_status' => $response->status()];
            }

            $this->logProviderFailure('status', $midtransOrderId, $response->status(), $data['status_message'] ?? null);
            return [
                'success' => false,
                'not_found' => $response->status() === 404,
                'http_status' => $response->status(),
                'message' => $this->safeProviderMessage($data['status_message'] ?? null),
            ];
        } catch (\Exception $e) {
            Log::warning('Midtrans status connection failed.', [
                'operation' => 'status',
                'order_id' => $midtransOrderId,
                'exception' => class_basename($e),
            ]);
            return ['success' => false, 'not_found' => false, 'message' => 'Could not connect to Midtrans status API.'];
        }
    }

    private function resolveCancellation(
        string $midtransOrderId,
        ?int $httpStatus,
        array $cancelResponse,
        array $statusResult,
        bool $retriedAfterServerError = false,
        ?array $expireFallback = null
    ): array
    {
        $providerStatus = $statusResult['data']['transaction_status'] ?? null;
        if ($statusResult['not_found'] ?? false) {
            return [
                'success' => false,
                'resolved' => false,
                'provider_status' => null,
                'http_status' => $statusResult['http_status'] ?? $httpStatus,
                'message' => 'Pembatalan belum terkonfirmasi: transaksi tidak ditemukan oleh Get Status API. Status dipertahankan; jangan buat pembayaran baru sebelum rekonsiliasi.',
            ];
        }

        if (in_array($providerStatus, ['settlement', 'capture'], true)) {
            return [
                'success' => false,
                'resolved' => true,
                'provider_status' => 'paid',
                'status_payload' => $statusResult['data'],
                'message' => 'Midtrans reports this transaction as paid; it cannot be cancelled.',
            ];
        }

        if (in_array($providerStatus, ['cancel', 'cancelled', 'expire', 'expired'], true)) {
            return [
                'success' => true,
                'resolved' => true,
                'provider_status' => $providerStatus,
                'status_payload' => $statusResult['data'],
            ];
        }

        if ($providerStatus === 'deny') {
            return [
                'success' => true,
                'resolved' => true,
                'provider_status' => 'failed',
                'status_payload' => $statusResult['data'],
            ];
        }

        $message = $cancelResponse['status_message'] ?? $statusResult['message'] ?? 'Midtrans has not confirmed cancellation.';
        if ($expireFallback !== null && $providerStatus === 'pending') {
            $expireMessage = $expireFallback['data']['status_message']
                ?? $expireFallback['message']
                ?? 'Expire API did not confirm the request.';
            $message = sprintf(
                'Cancel gagal (HTTP 500), dan Midtrans masih melaporkan pending setelah percobaan ulang. Expire API%s: %s. Status hanya berubah setelah Get Status mengonfirmasi hasil terminal.',
                isset($expireFallback['http_status']) ? ' HTTP ' . $expireFallback['http_status'] : '',
                $this->safeProviderMessage($expireMessage) ?? 'tidak ada detail dari provider'
            );
        } elseif ($retriedAfterServerError && ($providerStatus === 'pending' || $httpStatus === 500)) {
            $providerName = $this->isProduction ? 'Midtrans' : 'Midtrans Sandbox';
            $message = sprintf(
                'Setelah percobaan ulang karena HTTP 500, status transaksi%s. Eskalasikan gangguan ini ke dukungan %s bila status tetap pending; pembayaran baru tetap diblokir sampai status terminal terkonfirmasi.',
                $providerStatus ? " masih {$providerStatus}" : ' belum dapat dipastikan',
                $providerName
            );
        } elseif ($providerStatus === 'pending') {
            $message = sprintf(
                'Pembatalan belum terkonfirmasi; Get Status API masih melaporkan pending%s.',
                $httpStatus === 500 && $retriedAfterServerError ? ' setelah percobaan ulang HTTP 500' : ''
            );
        }
        $this->logProviderFailure('cancel', $midtransOrderId, $httpStatus, $message);

        return [
            'success' => false,
            'resolved' => $providerStatus !== null,
            'provider_status' => $providerStatus,
            'http_status' => $httpStatus ?? ($statusResult['http_status'] ?? null),
            'message' => sprintf(
                'Cancel belum terkonfirmasi%s: %s',
                $httpStatus ? " (HTTP {$httpStatus})" : '',
                $this->safeProviderMessage($message) ?? 'status provider tidak diketahui'
            ),
            'status_payload' => $statusResult['data'] ?? null,
        ];
    }

    private function mapProviderStatus(?string $status): string
    {
        return match ($status) {
            'settlement', 'capture' => 'paid',
            'pending' => 'pending',
            'cancel', 'cancelled' => 'cancelled',
            'expire', 'expired' => 'expired',
            'deny' => 'failed',
            default => 'unknown',
        };
    }

    private function safeProviderMessage(?string $message): ?string
    {
        if (!$message) {
            return null;
        }

        return mb_substr(trim(strip_tags($message)), 0, 240);
    }

    private function logProviderFailure(string $operation, string $midtransOrderId, ?int $httpStatus, ?string $message): void
    {
        Log::warning('Midtrans API request was not successful.', [
            'operation' => $operation,
            'order_id' => $midtransOrderId,
            'http_status' => $httpStatus,
            'provider_message' => $this->safeProviderMessage($message),
        ]);
    }

    /**
     * Verify Midtrans Signature Key to prevent tampering.
     */
    public function verifyNotification(array $payload): bool
    {
        if ($this->serverKey === ''
            || !isset($payload['signature_key'], $payload['order_id'], $payload['status_code'], $payload['gross_amount'])
            || !is_string($payload['signature_key'])
            || !is_scalar($payload['order_id'])
            || !is_scalar($payload['status_code'])
            || !is_scalar($payload['gross_amount'])
        ) {
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
