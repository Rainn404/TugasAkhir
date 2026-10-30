<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class OrderService
{
    protected InventoryAvailabilityService $availabilityService;
    protected MidtransService $midtransService;
    protected PaymentService $paymentService;

    public function __construct(
        InventoryAvailabilityService $availabilityService,
        MidtransService $midtransService,
        PaymentService $paymentService
    ) {
        $this->availabilityService = $availabilityService;
        $this->midtransService = $midtransService;
        $this->paymentService = $paymentService;
    }

    /**
     * Create event and order transaction atomically.
     *
     * @param array $cart
     * @param string $cartDate
     * @param array $eventData
     * @param int $userId
     * @return Order
     * @throws \Exception
     */
    public function createOrder(array $cart, string $cartDate, array $eventData, int $userId, ?string $checkoutKey = null): Order
    {
        if ($checkoutKey !== null) {
            $existingOrder = Order::where('user_id', $userId)
                ->where('checkout_key', $checkoutKey)
                ->first();

            if ($existingOrder) {
                return $existingOrder;
            }
        }

        try {
            $creation = DB::transaction(function () use ($cart, $cartDate, $eventData, $userId, $checkoutKey) {
            if ($checkoutKey !== null) {
                $existingOrder = Order::where('user_id', $userId)
                    ->where('checkout_key', $checkoutKey)
                    ->lockForUpdate()
                    ->first();
                if ($existingOrder) {
                    return ['order' => $existingOrder, 'created' => false];
                }
            }

            $inventoryIds = collect($cart)
                ->pluck('inventory_id')
                ->filter()
                ->unique()
                ->sort()
                ->values();
            $lockedInventories = Inventory::with('variants')
                ->whereIn('id', $inventoryIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $validation = $this->availabilityService->validateCartAvailability($cart, $cartDate);
            if (!$validation[0]) {
                throw new \Exception($validation[1]);
            }

            $pricedItems = [];
            $totalAmount = 0;
            foreach ($cart as $item) {
                $inventory = $lockedInventories->get((int) $item['inventory_id']);
                if (!$inventory) {
                    throw new \Exception('Perlengkapan tidak ditemukan di sistem.');
                }

                $variantId = $item['variant_id'] ?? null;
                $variant = $variantId ? $inventory->variants->firstWhere('id', (int) $variantId) : null;
                if ($variantId && !$variant) {
                    throw new \Exception('Variasi perlengkapan tidak sesuai dengan barang yang dipilih.');
                }

                $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
                if ($quantity === false || $quantity < 1) {
                    throw new \Exception('Kuantitas perlengkapan tidak valid.');
                }

                $unitPrice = (float) $inventory->price + (float) ($variant?->price_modifier ?? 0);
                $subtotal = $unitPrice * $quantity;
                $totalAmount += $subtotal;
                $pricedItems[] = [
                    ...$item,
                    'inventory_id' => $inventory->id,
                    'variant_id' => $variant?->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }

            $rentalAmount = $totalAmount;
            $deliveryFee = 0.00;
            $surveyFee = (float) ($eventData['survey_fee'] ?? 0.00);

            if (isset($eventData['latitude'], $eventData['longitude'])) {
                $baseLat = (float) config('services.google_maps.base_location.latitude', -3.7955);
                $baseLng = (float) config('services.google_maps.base_location.longitude', 114.7712);
                $destLat = (float) $eventData['latitude'];
                $destLng = (float) $eventData['longitude'];

                if ($destLat != 0.0 && $destLng != 0.0) {
                    $mapsService = app(GoogleMapsService::class);
                    $distKm = $mapsService->calculateHaversineDistance($baseLat, $baseLng, $destLat, $destLng);
                    $freeRadius = (float) \App\Models\SystemSetting::get('free_delivery_radius_km', 15);
                    $ratePerKm = (float) \App\Models\SystemSetting::get('delivery_rate_per_km', 5000);

                    if ($distKm > $freeRadius) {
                        $extraKm = ceil($distKm - $freeRadius);
                        $deliveryFee = $extraKm * $ratePerKm;
                    }
                }
            }

            // Keamanan tarif: Biaya pengiriman dihitung secara otoritatif oleh server berdasarkan
            // koordinat lokasi tujuan dan pengaturan sistem (bebas biaya dalam radius 15 km).
            // Input manipulasi 'delivery_fee' dari client tidak dipercaya.

            $grandTotal = $rentalAmount + $deliveryFee + $surveyFee;

            $event = Event::create([
                'user_id' => $userId,
                'event_name' => $eventData['event_name'],
                'event_type' => $eventData['event_type'],
                'event_date' => $cartDate,
                'start_time' => $eventData['start_time'],
                'end_time' => $eventData['end_time'],
                'address' => $eventData['address'],
                'latitude' => $eventData['latitude'],
                'longitude' => $eventData['longitude'],
                'notes' => $eventData['notes'] ?? null,
                'status' => 'scheduled',
            ]);

            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            while (Order::where('order_number', $orderNumber)->exists()) {
                $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            }

            $order = Order::create([
                'order_number' => $orderNumber,
                'checkout_key' => $checkoutKey,
                'user_id' => $userId,
                'event_id' => $event->id,
                'rental_amount' => $rentalAmount,
                'delivery_fee' => $deliveryFee,
                'survey_fee' => $surveyFee,
                'total_amount' => $grandTotal,
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'notes' => $eventData['notes'] ?? null,
            ]);

            foreach ($pricedItems as $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'inventory_id' => $item['inventory_id'],
                    'inventory_variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'options_json' => $item['options'] ?? null,
                ]);
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'gross_amount' => $grandTotal,
                'payment_status' => 'pending',
            ]);

            $order->load(['user', 'orderDetails.inventory', 'orderDetails.variant']);
            $midtransOrderId = $orderNumber . '-A' . strtoupper(Str::random(8));
            $attempt = PaymentAttempt::create([
                'payment_id' => $payment->id,
                'midtrans_order_id' => $midtransOrderId,
                'gross_amount' => $grandTotal,
                'status' => 'creating',
            ]);
            $order->load(['user', 'orderDetails.inventory', 'orderDetails.variant']);

            return ['order' => $order, 'created' => true];
            });
        } catch (QueryException $exception) {
            if ($checkoutKey !== null) {
                $existingOrder = Order::where('user_id', $userId)
                    ->where('checkout_key', $checkoutKey)
                    ->first();
                if ($existingOrder) {
                    return $existingOrder;
                }
            }

            throw $exception;
        }

        $order = $creation['order'];
        if (!$creation['created']) {
            return $order;
        }

        $payment = $order->payment()->firstOrFail();
        $attempt = $payment->attempts()->latest('id')->firstOrFail();
        $order->load(['user', 'orderDetails.inventory', 'orderDetails.variant']);
        $snapResult = $this->midtransService->createSnapToken($order, $attempt->midtrans_order_id);

        if (isset($snapResult['status_payload']) && is_array($snapResult['status_payload'])) {
            $statusResult = $this->paymentService->processStatusResponse($snapResult['status_payload']);
            if ($statusResult[0] || $statusResult[2] === 409) {
                return $order->refresh();
            }

            $snapResult['state'] = 'unknown';
            $snapResult['message'] = 'Status transaksi Midtrans belum dapat diproses dan perlu diperiksa kembali.';
        }

        DB::transaction(function () use ($attempt, $payment, $order, $snapResult) {
            $lockedAttempt = PaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (($snapResult['state'] ?? null) === 'created' && !empty($snapResult['token'])) {
                if (!in_array($lockedAttempt->status, ['paid', 'failed', 'rejected', 'cancelled', 'expired'], true)) {
                    $lockedAttempt->update([
                        'status' => 'pending',
                        'snap_token' => $snapResult['token'],
                        'payload_json' => $snapResult['payload'],
                    ]);
                    if (!in_array($lockedPayment->payment_status, ['paid', 'conflict'], true)) {
                        $lockedPayment->update([
                            'payment_status' => 'pending',
                            'snap_token' => $snapResult['token'],
                            'payload_json' => ['active_attempt_id' => $lockedAttempt->id],
                        ]);
                    }
                }
            } else {
                $attemptStatus = $snapResult['state'] ?? 'unknown';
                if (!in_array($lockedAttempt->status, ['paid', 'failed', 'rejected', 'cancelled', 'expired'], true)) {
                    $summaryStatus = match ($attemptStatus) {
                        'pending' => 'pending',
                        'paid' => 'paid',
                        'rejected', 'failed' => 'failed',
                        default => 'unknown',
                    };
                    $lockedAttempt->update([
                        'status' => $attemptStatus,
                        'last_error' => $snapResult['message'] ?? 'Token response was not confirmed; check Midtrans status before retrying.',
                        'payload_json' => $snapResult,
                    ]);
                    $lockedPayment->update([
                        'payment_status' => $summaryStatus,
                        'payload_json' => ['active_attempt_id' => $lockedAttempt->id],
                    ]);
                    $lockedOrder->update([
                        'payment_status' => $summaryStatus,
                        'status' => $summaryStatus === 'paid' ? 'paid' : 'pending_payment',
                    ]);
                }
            }
        });

        return $order->refresh();
    }
}
