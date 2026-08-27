<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    protected InventoryAvailabilityService $availabilityService;
    protected MidtransService $midtransService;

    public function __construct(
        InventoryAvailabilityService $availabilityService,
        MidtransService $midtransService
    ) {
        $this->availabilityService = $availabilityService;
        $this->midtransService = $midtransService;
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
    public function createOrder(array $cart, string $cartDate, array $eventData, int $userId): Order
    {
        return DB::transaction(function () use ($cart, $cartDate, $eventData, $userId) {
            // 1. Double check availability with lock
            $validation = $this->availabilityService->validateCartAvailability($cart, $cartDate);
            if (!$validation[0]) {
                throw new \Exception($validation[1]);
            }

            // 2. Calculate server-side total
            $totalAmount = 0;
            foreach ($cart as $item) {
                $totalAmount += $item['subtotal'];
            }

            // 3. Create Event
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

            // 4. Generate Unique Order Number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            while (Order::where('order_number', $orderNumber)->exists()) {
                $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            }

            // 5. Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'event_id' => $event->id,
                'total_amount' => $totalAmount,
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'notes' => $eventData['notes'] ?? null,
            ]);

            // 6. Create Order Details
            foreach ($cart as $item) {
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

            // 7. Initialize Payment Record & Request Snap Token
            $payment = Payment::create([
                'order_id' => $order->id,
                'gross_amount' => $totalAmount,
                'payment_status' => 'pending',
            ]);

            $order->load(['user', 'orderDetails.inventory', 'orderDetails.variant']);
            $snapResult = $this->midtransService->createSnapToken($order);

            if ($snapResult && isset($snapResult['token'])) {
                $payment->update([
                    'snap_token' => $snapResult['token'],
                    'payload_json' => $snapResult,
                ]);
            }

            return $order;
        });
    }
}
