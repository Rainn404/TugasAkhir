<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Inventory;
use App\Models\InventoryVariant;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Services\InventoryAvailabilityService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArsyTendaSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Category $category;
    protected Inventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Arsy Tenda',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        $this->customer = User::create([
            'name' => 'Customer Demo',
            'email' => 'customer@example.com',
            'role' => 'customer',
            'phone' => '08123456789',
            'address' => 'Pelaihari, Tanah Laut',
            'password' => bcrypt('password'),
        ]);

        $this->category = Category::create([
            'name' => 'Tenda',
            'slug' => 'tenda',
            'description' => 'Kategori Tenda',
        ]);

        $this->inventory = Inventory::create([
            'category_id' => $this->category->id,
            'name' => 'Tenda Roder 10x20 m',
            'slug' => 'tenda-roder-10x20-m',
            'description' => 'Tenda roder VIP kokoh',
            'price' => 1500000,
            'stock' => 5,
            'unit' => 'unit',
            'status' => 'available',
        ]);
    }

    public function test_homepage_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('ARSY');
        $response->assertSee('TENDA');
        $response->assertSee('Tenda Roder 10x20 m');
    }

    public function test_detail_catalog_configurator_loads(): void
    {
        $response = $this->get('/perlengkapan/' . $this->inventory->slug);
        $response->assertStatus(200);
        $response->assertSee('Tenda Roder 10x20 m');
        $response->assertSee('Harga Dasar');
    }

    public function test_schedule_based_availability_service(): void
    {
        $service = new InventoryAvailabilityService();
        $date = '2026-09-10';

        // 1. Initial availability: all 5 units available
        $initial = $service->checkAvailability($this->inventory, $date);
        $this->assertEquals(5, $initial['available_stock']);
        $this->assertEquals('AVAILABLE', $initial['status']);

        // 2. Simulate 4 units booked on that date
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Pernikahan',
            'event_type' => 'Pernikahan',
            'event_date' => $date,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Jl. Pelaihari No. 1',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 6000000,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'inventory_id' => $this->inventory->id,
            'quantity' => 4,
            'unit_price' => 1500000,
            'subtotal' => 6000000,
        ]);

        // 3. Now remaining available on 2026-09-10 should be 1 (LIMITED)
        $afterBooking = $service->checkAvailability($this->inventory, $date);
        $this->assertEquals(1, $afterBooking['available_stock']);
        $this->assertEquals('LIMITED', $afterBooking['status']);

        // 4. On another date, all 5 units must still be available
        $otherDate = $service->checkAvailability($this->inventory, '2026-09-15');
        $this->assertEquals(5, $otherDate['available_stock']);
        $this->assertEquals('AVAILABLE', $otherDate['status']);

        $expiredOrder = Order::create([
            'order_number' => 'ORD-EXPIRED-STOCK-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'expired',
        ]);
        OrderDetail::create([
            'order_id' => $expiredOrder->id,
            'inventory_id' => $this->inventory->id,
            'quantity' => 1,
            'unit_price' => 1500000,
            'subtotal' => 1500000,
        ]);

        $afterExpiredAttempt = $service->checkAvailability($this->inventory, $date);
        $this->assertEquals(1, $afterExpiredAttempt['available_stock']);
    }

    public function test_variant_stock_modifier_is_applied_during_checkout_availability(): void
    {
        $variant = InventoryVariant::create([
            'inventory_id' => $this->inventory->id,
            'variant_name' => 'Kapasitas terbatas',
            'attribute_type' => 'kapasitas',
            'attribute_value' => 'Terbatas',
            'price_modifier' => 0,
            'stock_modifier' => -4,
        ]);

        $result = app(InventoryAvailabilityService::class)->validateCartAvailability([[
            'inventory_id' => $this->inventory->id,
            'variant_id' => $variant->id,
            'name' => $this->inventory->name,
            'quantity' => 2,
        ]], date('Y-m-d', strtotime('+30 days')));

        $this->assertFalse($result[0]);
    }

    public function test_customer_can_submit_survey_request(): void
    {
        $futureDate = date('Y-m-d', strtotime('+7 days'));

        $response = $this->actingAs($this->customer)->post('/survey/request', [
            'event_name' => 'Rencana Wisuda Akbar',
            'phone' => '08123456789',
            'event_date' => $futureDate,
            'estimated_time' => '08:00 - 15:00',
            'address' => 'Gedung Serbaguna Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'notes' => 'Akses jalan lebar untuk truk',
        ]);

        $response->assertRedirect('/dashboard/surveys');
        $this->assertDatabaseHas('surveys', [
            'event_name' => 'Rencana Wisuda Akbar',
            'status' => 'menunggu',
        ]);
    }

    public function test_customer_can_retry_payment_when_snap_token_is_missing(): void
    {
        config([
            'services.midtrans.is_production' => false,
            'services.midtrans.client_key' => 'SB-Mid-client-test-key',
            'services.midtrans.server_key' => 'SB-Mid-server-test-secret',
        ]);

        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Uji Pembayaran',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-RETRY-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
        ]);

        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-test-token',
            ], 201),
        ]);

        $this->actingAs($this->customer)
            ->get(route('dashboard.order', $order->order_number))
            ->assertOk()
            ->assertSee('Siapkan Pembayaran');

        $this->actingAs($this->customer)
            ->post(route('payments.retry', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'snap_token' => 'snap-test-token',
        ]);

        $detailResponse = $this->actingAs($this->customer)
            ->get(route('dashboard.order', $order->order_number))
            ->assertOk();

        $detailResponse->assertSee('Bayar Sekarang')
            ->assertSee('snap-test-token')
            ->assertSee('data-snap-token="snap-test-token"', false)
            ->assertSee('data-payment-status-url="' . route('payments.status', $order->order_number) . '"', false)
            ->assertSee('order-payment.js')
            ->assertSee('https://app.sandbox.midtrans.com/snap/snap.js')
            ->assertSee('SB-Mid-client-test-key')
            ->assertDontSee('SB-Mid-server-test-secret')
            ->assertSee('data-pay-with-snap', false)
            ->assertDontSee('<script>', false);

        // Verifikasi CSP header sekarang ada dan mengizinkan domain Midtrans Sandbox
        $csp = $detailResponse->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp, 'Halaman order harus mengirim CSP header untuk melindungi Snap popup.');
        $this->assertStringContainsString('https://app.sandbox.midtrans.com', $csp,
            'CSP harus mengizinkan domain Midtrans Sandbox untuk Snap popup.');
        $this->assertStringContainsString('frame-ancestors', $csp,
            'CSP harus memiliki frame-ancestors untuk mencegah Snap dimuat dalam iframe.');
        $this->assertStringContainsString("'nonce-", $csp,
            'CSP script-src harus menyertakan nonce yang dibuat secara aman per request.');
        $this->assertStringContainsString("font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:", $csp,
            'CSP font-src harus mengizinkan Bootstrap Icons CDN dari jsdelivr.');
        // Catatan: unsafe-inline pada script-src diperlukan oleh Snap.js karena ia
        // menyuntikkan script secara dinamis dari berbagai payment channel (GoPay, Xendit, dll.)
        $this->assertStringContainsString("'unsafe-inline'", $csp,
            "script-src harus mengandung 'unsafe-inline' agar Snap.js dapat menyuntikkan script payment channel.");
    }

    public function test_unknown_attempt_without_token_resolves_to_failed_when_midtrans_returns_not_found(): void
    {
        config([
            'services.midtrans.is_production' => false,
            'services.midtrans.client_key' => 'SB-Mid-client-test-key',
            'services.midtrans.server_key' => 'SB-Mid-server-test-secret',
        ]);

        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Unknown 404',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-UNKNOWN-NF-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'unknown',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'unknown',
        ]);

        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-UNKNOWN-NF-001-A1',
            'gross_amount' => 1500000,
            'status' => 'unknown',
            'snap_token' => null,
            'payload_json' => ['state' => 'unknown'],
        ]);

        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-UNKNOWN-NF-001-A1/status' => Http::response([
                'status_code' => '404',
                'status_message' => "Transaction doesn't exist.",
            ], 200),
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-new-recovered-token',
            ], 201),
        ]);

        // Status check harus me-resolve attempt menjadi failed dan return 200
        $this->actingAs($this->customer)
            ->postJson(route('payments.status', $order->order_number))
            ->assertOk()
            ->assertJson([
                'order_status' => 'pending_payment',
                'payment_status' => 'failed',
                'attempt_status' => 'failed',
            ]);

        $this->assertDatabaseHas('payment_attempts', [
            'id' => $attempt->id,
            'status' => 'failed',
        ]);

        // Customer sekarang dapat melihat tombol Siapkan Pembayaran
        $this->actingAs($this->customer)
            ->get(route('dashboard.order', $order->order_number))
            ->assertOk()
            ->assertSee('Siapkan Pembayaran');

        // Customer dapat me-retry pembayaran dan mendapatkan token Snap baru
        $this->actingAs($this->customer)
            ->post(route('payments.retry', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_status' => 'pending',
            'snap_token' => 'snap-new-recovered-token',
        ]);

        $this->actingAs($this->customer)
            ->get(route('dashboard.order', $order->order_number))
            ->assertOk()
            ->assertSee('Bayar Sekarang')
            ->assertSee('snap-new-recovered-token');
    }

    public function test_order_page_rejects_client_key_from_the_wrong_environment(): void
    {
        config([
            'services.midtrans.is_production' => false,
            'services.midtrans.client_key' => 'Mid-client-production-key',
        ]);

        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Key Environment',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-CLIENT-KEY-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
            'snap_token' => 'snap-test-token',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-CLIENT-KEY-001-A1',
            'gross_amount' => 1500000,
            'status' => 'pending',
            'snap_token' => 'snap-test-token',
        ]);

        $this->actingAs($this->customer)
            ->get(route('dashboard.order', $order->order_number))
            ->assertOk()
            ->assertSee('Client Key Midtrans tidak cocok dengan mode Sandbox')
            ->assertDontSee('https://app.sandbox.midtrans.com/snap/snap.js')
            ->assertDontSee('data-pay-with-snap', false);
    }

    public function test_order_creation_stores_valid_sandbox_snap_token_on_unique_attempt(): void
    {
        config(['services.midtrans.is_production' => false]);
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-order-service-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test',
            ], 201),
        ]);

        $order = app(OrderService::class)->createOrder(
            [[
                'inventory_id' => $this->inventory->id,
                'variant_id' => null,
                'name' => $this->inventory->name,
                'quantity' => 1,
                'unit_price' => 1500000,
                'subtotal' => 1500000,
                'options' => null,
            ]],
            date('Y-m-d', strtotime('+30 days')),
            [
                'event_name' => 'Acara Token Test',
                'event_type' => 'Pernikahan',
                'start_time' => '08:00',
                'end_time' => '18:00',
                'address' => 'Pelaihari',
                'latitude' => -3.795,
                'longitude' => 114.77,
            ],
            $this->customer->id
        );

        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $attempt = PaymentAttempt::where('payment_id', $payment->id)->firstOrFail();

        $this->assertSame('pending', $attempt->status);
        $this->assertSame('snap-order-service-token', $attempt->snap_token);
        $this->assertSame($attempt->id, data_get($payment->payload_json, 'active_attempt_id'));
        $this->assertSame('pending', $payment->payment_status);

        Http::assertSent(function ($request) use ($attempt) {
            return $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
                && $request['transaction_details']['order_id'] === $attempt->midtrans_order_id
                && $request['transaction_details']['gross_amount'] === 1500000;
        });
    }

    public function test_repeated_checkout_key_reuses_the_existing_order_and_attempt(): void
    {
        config([
            'services.midtrans.is_production' => false,
            'services.midtrans.server_key' => 'SB-Mid-server-test-secret',
        ]);
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-idempotent-test-token',
            ], 201),
        ]);

        $cart = [[
            'inventory_id' => $this->inventory->id,
            'variant_id' => null,
            'name' => $this->inventory->name,
            'quantity' => 1,
            'unit_price' => 1,
            'subtotal' => 1,
        ]];
        $eventData = [
            'event_name' => 'Acara Checkout Idempotent',
            'event_type' => 'Pernikahan',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
        ];

        $firstOrder = app(OrderService::class)->createOrder(
            $cart,
            date('Y-m-d', strtotime('+30 days')),
            $eventData,
            $this->customer->id,
            'checkout-key-repeat-test'
        );
        $secondOrder = app(OrderService::class)->createOrder(
            $cart,
            date('Y-m-d', strtotime('+30 days')),
            $eventData,
            $this->customer->id,
            'checkout-key-repeat-test'
        );

        $this->assertSame($firstOrder->id, $secondOrder->id);
        $this->assertSame('1500000.00', $firstOrder->fresh()->total_amount);
        $this->assertSame('1500000.00', $firstOrder->orderDetails()->firstOrFail()->unit_price);
        $this->assertSame(1, Order::where('user_id', $this->customer->id)->count());
        $this->assertSame(1, PaymentAttempt::count());
        Http::assertSentCount(1);
    }

    public function test_ambiguous_snap_creation_is_unresolved_and_not_retried_blindly(): void
    {
        config(['services.midtrans.is_production' => false]);
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::failedConnection(),
            'api.sandbox.midtrans.com/v2/*/status' => Http::failedConnection(),
        ]);

        $order = app(OrderService::class)->createOrder(
            [[
                'inventory_id' => $this->inventory->id,
                'variant_id' => null,
                'name' => $this->inventory->name,
                'quantity' => 1,
                'unit_price' => 1500000,
                'subtotal' => 1500000,
                'options' => null,
            ]],
            date('Y-m-d', strtotime('+30 days')),
            [
                'event_name' => 'Acara Token Ambigu',
                'event_type' => 'Pernikahan',
                'start_time' => '08:00',
                'end_time' => '18:00',
                'address' => 'Pelaihari',
                'latitude' => -3.795,
                'longitude' => 114.77,
            ],
            $this->customer->id
        );

        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $attempt = PaymentAttempt::where('payment_id', $payment->id)->firstOrFail();

        $this->assertSame('unknown', $attempt->status);
        $this->assertSame('unknown', $payment->payment_status);
        $this->assertNull($attempt->snap_token);
        Http::assertSentCount(3);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/snap/v1/transactions') && $request->method() !== 'POST');
    }

    public function test_only_verified_midtrans_webhook_marks_order_as_paid(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Webhook',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-WEBHOOK-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
            'snap_token' => 'snap-webhook-test-token',
            'payload_json' => ['midtrans_order_id' => 'ORD-WEBHOOK-001-A12345678'],
        ]);

        $payload = [
            'order_id' => 'ORD-WEBHOOK-001-A12345678',
            'status_code' => '200',
            'gross_amount' => '1500000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'MIDTRANS-TRANSACTION-001',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('services.midtrans.server_key')
        );

        $this->postJson(route('payment.webhook'), $payload)
            ->assertOk()
            ->assertJson(['message' => 'Notification processed successfully']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'transaction_id' => 'MIDTRANS-TRANSACTION-001',
            'payment_status' => 'paid',
        ]);
    }

    public function test_capture_is_paid_only_after_midtrans_accepts_fraud_review(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Capture Review',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-CAPTURE-REVIEW-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
        ]);
        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-CAPTURE-REVIEW-001-A1',
            'gross_amount' => 1500000,
            'status' => 'pending',
        ]);

        $payload = $this->midtransWebhookPayload(
            $attempt->midtrans_order_id,
            'capture',
            'MIDTRANS-CAPTURE-REVIEW-001'
        );
        $payload['fraud_status'] = 'challenge';
        $this->postJson(route('payment.webhook'), $payload)->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        $payload['fraud_status'] = 'accept';
        $this->postJson(route('payment.webhook'), $payload)->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);
    }

    public function test_late_pending_notification_does_not_reopen_terminal_attempt(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Notifikasi Terlambat',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-LATE-PENDING-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'cancelled',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'cancelled',
        ]);
        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-LATE-PENDING-001-A1',
            'gross_amount' => 1500000,
            'status' => 'cancelled',
        ]);

        $this->postJson(route('payment.webhook'), $this->midtransWebhookPayload(
            $attempt->midtrans_order_id,
            'pending',
            'MIDTRANS-LATE-PENDING-001'
        ))->assertOk();

        $this->assertDatabaseHas('payment_attempts', [
            'id' => $attempt->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_status' => 'cancelled',
        ]);

        $statusResult = app(\App\Services\PaymentService::class)->processStatusResponse([
            'order_id' => $attempt->midtrans_order_id,
            'gross_amount' => '1500000.00',
            'transaction_status' => 'pending',
        ]);

        $this->assertSame('Stale Midtrans status response ignored', $statusResult[1]);
        $this->assertDatabaseHas('payment_attempts', [
            'id' => $attempt->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_pending_notification_does_not_reopen_cancelled_order(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Order Dibatalkan',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-CANCELLED-ORDER-PENDING-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
        ]);
        $attempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-CANCELLED-ORDER-PENDING-001-A1',
            'gross_amount' => 1500000,
            'status' => 'pending',
        ]);

        $this->postJson(route('payment.webhook'), $this->midtransWebhookPayload(
            $attempt->midtrans_order_id,
            'pending',
            'MIDTRANS-CANCELLED-ORDER-001'
        ))->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]);
    }

    public function test_webhook_signature_is_rejected_when_server_key_is_missing(): void
    {
        config(['services.midtrans.server_key' => '']);
        $payload = [
            'order_id' => 'ORD-NO-KEY-001-A1',
            'status_code' => '200',
            'gross_amount' => '1500000.00',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount']
        );

        $this->assertFalse((new \App\Services\MidtransService())->verifyNotification($payload));
    }

    public function test_webhook_rejects_an_invalid_signature_with_a_configured_server_key(): void
    {
        config(['services.midtrans.server_key' => 'SB-Mid-server-test-secret']);

        $this->postJson(route('payment.webhook'), [
            'order_id' => 'ORD-BAD-SIGNATURE-001-A1',
            'status_code' => '200',
            'gross_amount' => '1500000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid-signature',
        ])
            ->assertBadRequest()
            ->assertJson(['message' => 'Invalid Signature Key']);
    }

    public function test_customer_can_refresh_payment_status_from_midtrans(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Periksa Status',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-STATUS-REFRESH-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-STATUS-REFRESH-001-A1',
            'gross_amount' => 1500000,
            'status' => 'pending',
        ]);

        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-STATUS-REFRESH-001-A1/status' => Http::response([
                'order_id' => 'ORD-STATUS-REFRESH-001-A1',
                'transaction_id' => 'MIDTRANS-STATUS-REFRESH-001',
                'transaction_status' => 'settlement',
                'payment_type' => 'bank_transfer',
                'gross_amount' => '1500000.00',
            ], 200),
        ]);

        $otherCustomer = User::create([
            'name' => 'Other Customer',
            'email' => 'other-customer@example.com',
            'role' => 'customer',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($otherCustomer)
            ->postJson(route('payments.status', $order->order_number))
            ->assertForbidden();

        $this->actingAs($this->customer)
            ->postJson(route('payments.status', $order->order_number))
            ->assertOk()
            ->assertJson([
                'order_status' => 'paid',
                'payment_status' => 'paid',
                'attempt_status' => 'paid',
            ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/ORD-STATUS-REFRESH-001-A1/status'));
    }

    public function test_unconfirmed_cancel_without_snap_token_shows_status_check_not_payment_cancel(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Status Belum Pasti',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-UNCONFIRMED-UI-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'unknown',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'unknown',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-UNCONFIRMED-UI-001-A1',
            'gross_amount' => 1500000,
            'status' => 'cancel_unconfirmed',
            'last_error' => 'Could not connect to Midtrans status API.',
        ]);

        $this->actingAs($this->customer)
            ->get(route('dashboard.order', $order->order_number))
            ->assertOk()
            ->assertSee('Periksa Status Pembayaran')
            ->assertDontSee('Coba Batalkan Kembali')
            ->assertDontSee('Could not connect to Midtrans status API.');
    }

    public function test_failed_status_check_keeps_unconfirmed_payment_unknown(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Status API Offline',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-STATUS-OFFLINE-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'unknown',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'unknown',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-STATUS-OFFLINE-001-A1',
            'gross_amount' => 1500000,
            'status' => 'cancel_unconfirmed',
        ]);
        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-STATUS-OFFLINE-001-A1/status' => Http::failedConnection(),
        ]);

        $this->actingAs($this->customer)
            ->postJson(route('payments.status', $order->order_number))
            ->assertStatus(202)
            ->assertJson([
                'order_status' => 'pending_payment',
                'payment_status' => 'unknown',
                'attempt_status' => 'cancel_unconfirmed',
            ]);
    }

    public function test_late_and_repeated_attempt_webhooks_reconcile_double_settlement(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Rekonsiliasi',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-RECONCILE-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-RECONCILE-001-AOLD',
            'gross_amount' => 1500000,
            'status' => 'pending',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-RECONCILE-001-ANEW',
            'gross_amount' => 1500000,
            'status' => 'pending',
        ]);

        $oldSettled = $this->midtransWebhookPayload('ORD-RECONCILE-001-AOLD', 'settlement', 'MIDTRANS-OLD-001');
        $this->postJson(route('payment.webhook'), $oldSettled)->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'payment_conflict', 'payment_status' => 'conflict']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'payment_status' => 'conflict']);

        $newSettled = $this->midtransWebhookPayload('ORD-RECONCILE-001-ANEW', 'settlement', 'MIDTRANS-NEW-001');
        $this->postJson(route('payment.webhook'), $newSettled)->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'payment_conflict', 'payment_status' => 'conflict']);

        $latePending = $this->midtransWebhookPayload('ORD-RECONCILE-001-ANEW', 'pending', 'MIDTRANS-NEW-001');
        $this->postJson(route('payment.webhook'), $latePending)->assertOk();
        $this->postJson(route('payment.webhook'), $newSettled)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'payment_conflict', 'payment_status' => 'conflict']);
        $this->assertSame(2, PaymentAttempt::where('payment_id', $payment->id)->where('status', 'paid')->count());
    }

    public function test_status_api_settlement_is_processed_without_webhook_signature(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Status API',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-STATUS-API-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-STATUS-API-001-A1',
            'gross_amount' => 1500000,
            'status' => 'pending',
        ]);

        $result = app(\App\Services\PaymentService::class)->processStatusResponse([
            'order_id' => 'ORD-STATUS-API-001-A1',
            'transaction_id' => 'MIDTRANS-STATUS-001',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'gross_amount' => '1500000.00',
        ]);

        $this->assertSame([true, 'Midtrans status response processed successfully', 200], $result);
        $this->assertDatabaseHas('payment_attempts', [
            'midtrans_order_id' => 'ORD-STATUS-API-001-A1',
            'status' => 'paid',
            'transaction_id' => 'MIDTRANS-STATUS-001',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);
    }

    public function test_signed_webhook_with_wrong_amount_is_not_credited(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Nominal Salah',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-AMOUNT-MISMATCH-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-AMOUNT-MISMATCH-001-A1',
            'gross_amount' => 1500000,
            'status' => 'pending',
        ]);

        $payload = $this->midtransWebhookPayload('ORD-AMOUNT-MISMATCH-001-A1', 'settlement', 'MIDTRANS-WRONG-AMOUNT');
        $payload['gross_amount'] = '1500001.00';
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('services.midtrans.server_key')
        );

        $this->postJson(route('payment.webhook'), $payload)
            ->assertStatus(409)
            ->assertJson(['message' => 'Payment amount mismatch; order requires reconciliation']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'payment_conflict',
            'payment_status' => 'conflict',
        ]);
        $this->assertDatabaseHas('payment_attempts', [
            'payment_id' => $payment->id,
            'status' => 'unknown',
        ]);
    }

    public function test_unreachable_midtrans_status_api_blocks_new_attempt(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Status Unknown',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-UNKNOWN-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'unknown',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'unknown',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-UNKNOWN-001-A1',
            'gross_amount' => 1500000,
            'status' => 'unknown',
        ]);

        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-UNKNOWN-001-A1/status' => Http::failedConnection(),
        ]);

        $this->actingAs($this->customer)
            ->post(route('payments.retry', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('error');

        $this->assertSame(1, PaymentAttempt::where('payment_id', $payment->id)->count());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/snap/v1/transactions'));
    }

    public function test_missing_legacy_attempt_stays_unconfirmed_and_other_pending_attempt_stays_open(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Legacy Attempt',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-LEGACY-MIXED-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
            'payload_json' => ['active_attempt_id' => 2],
        ]);
        $missingAttempt = PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-LEGACY-MIXED-001-AOLD',
            'gross_amount' => 1500000,
            'status' => 'unknown',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-LEGACY-MIXED-001-ANEW',
            'gross_amount' => 1500000,
            'status' => 'pending',
            'snap_token' => 'still-active-token',
        ]);

        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-LEGACY-MIXED-001-AOLD/status' => Http::response([
                'status_code' => '404',
                'status_message' => 'Transaction not found',
            ], 404),
            'api.sandbox.midtrans.com/v2/ORD-LEGACY-MIXED-001-ANEW/status' => Http::response(
                $this->midtransWebhookPayload('ORD-LEGACY-MIXED-001-ANEW', 'pending', 'MIDTRANS-ACTIVE-001'),
                200
            ),
        ]);

        $this->actingAs($this->customer)
            ->post(route('payments.retry', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payment_attempts', [
            'id' => $missingAttempt->id,
            'status' => 'unknown',
        ]);
        $this->assertDatabaseHas('payment_attempts', [
            'midtrans_order_id' => 'ORD-LEGACY-MIXED-001-ANEW',
            'status' => 'pending',
        ]);
        $this->assertSame(2, PaymentAttempt::where('payment_id', $payment->id)->count());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/snap/v1/transactions'));
    }

    public function test_pending_sandbox_attempt_is_not_duplicated_when_cancel_fails(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Ganti Metode',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-CHANGE-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
            'snap_token' => 'old-snap-token',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-CHANGE-001-AOLD',
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
            'status' => 'pending',
            'snap_token' => 'old-snap-token',
        ]);

        $pendingStatus = $this->midtransWebhookPayload('ORD-CHANGE-001-AOLD', 'pending', 'MIDTRANS-CHANGE-001');
        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-CHANGE-001-AOLD/cancel' => Http::response([
                'status_code' => '200',
                'status_message' => 'Transaction is cancelled',
            ], 200),
            'api.sandbox.midtrans.com/v2/ORD-CHANGE-001-AOLD/status' => Http::sequence()
                ->push($pendingStatus, 200)
                ->push($pendingStatus, 200)
                ->push($pendingStatus, 200),
        ]);

        $this->actingAs($this->customer)
            ->post(route('payments.cancel', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payment_attempts', [
            'midtrans_order_id' => 'ORD-CHANGE-001-AOLD',
            'status' => 'cancel_unconfirmed',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_status' => 'pending',
            'snap_token' => 'old-snap-token',
        ]);

        $this->actingAs($this->customer)
            ->post(route('payments.retry', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            // HTTP 200 cancel tidak cukup jika Get Status masih pending.
            // Retry diblokir: pengguna harus menyelesaikan cancel terlebih dahulu.
            ->assertSessionHas('error');

        // Masih 1 attempt saja, tidak ada attempt baru yang dibuat
        $this->assertSame(1, PaymentAttempt::where('payment_id', $payment->id)->count());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/snap/v1/transactions'));

        // snap_token lama tetap ada di database (belum dihapus)
        $this->assertDatabaseHas('payments', [
            'order_id'   => $order->id,
            'snap_token' => 'old-snap-token',
        ]);
    }

    public function test_confirmed_cancel_allows_new_unique_payment_attempt(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Cancel Terkonfirmasi',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-CANCEL-OK-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
            'snap_token' => 'old-token',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-CANCEL-OK-001-AOLD',
            'gross_amount' => 1500000,
            'status' => 'pending',
            'snap_token' => 'old-token',
        ]);

        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-CANCEL-OK-001-AOLD/cancel' => Http::response([
                'status_code' => '200',
                'status_message' => 'Transaction is canceled',
            ], 200),
            'api.sandbox.midtrans.com/v2/ORD-CANCEL-OK-001-AOLD/status' => Http::sequence()
                ->push($this->midtransWebhookPayload('ORD-CANCEL-OK-001-AOLD', 'pending', 'MIDTRANS-CANCEL-001'), 200)
                ->push($this->midtransWebhookPayload('ORD-CANCEL-OK-001-AOLD', 'cancel', 'MIDTRANS-CANCEL-001'), 200),
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'replacement-token',
            ], 201),
        ]);

        $this->actingAs($this->customer)
            ->post(route('payments.cancel', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('success');

        $this->actingAs($this->customer)
            ->post(route('payments.retry', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('success');

        $this->assertSame(2, PaymentAttempt::where('payment_id', $payment->id)->count());
        $this->assertDatabaseHas('payment_attempts', [
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-CANCEL-OK-001-AOLD',
            'status' => 'cancelled',
        ]);
        $newAttempt = PaymentAttempt::where('payment_id', $payment->id)->latest('id')->firstOrFail();
        $this->assertNotSame('ORD-CANCEL-OK-001-AOLD', $newAttempt->midtrans_order_id);
        $this->assertSame('replacement-token', $newAttempt->snap_token);
    }

    public function test_production_cancel_error_keeps_existing_payment_unchanged(): void
    {
        config(['services.midtrans.is_production' => true]);

        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Cancel Ditolak',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-CANCEL-REJECT-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
            'snap_token' => 'existing-snap-token',
            'payload_json' => ['midtrans_order_id' => 'ORD-CANCEL-REJECT-001'],
        ]);

        Http::fake([
            'api.midtrans.com/v2/ORD-CANCEL-REJECT-001/cancel' => Http::response([
                'status_code' => '500',
                'status_message' => 'Temporarily unavailable',
            ], 500),
            'api.midtrans.com/v2/ORD-CANCEL-REJECT-001/expire' => Http::response([
                'status_code' => '500',
                'status_message' => 'Temporarily unavailable',
            ], 500),
            'api.midtrans.com/v2/ORD-CANCEL-REJECT-001/status' => Http::response(
                $this->midtransWebhookPayload('ORD-CANCEL-REJECT-001', 'pending', 'MIDTRANS-PRODUCTION-001'),
                200
            ),
        ]);

        $this->actingAs($this->customer)
            ->post(route('payments.cancel', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_status' => 'pending',
            'snap_token' => 'existing-snap-token',
        ]);
    }

    public function test_http_500_cancel_sets_attempt_to_cancel_unconfirmed_and_blocks_retry(): void
    {
        $event = Event::create([
            'user_id'    => $this->customer->id,
            'event_name' => 'Acara Cancel Unconfirmed',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time'   => '18:00',
            'address'    => 'Pelaihari',
            'latitude'   => -3.795,
            'longitude'  => 114.77,
            'status'     => 'scheduled',
        ]);
        $order = Order::create([
            'order_number'   => 'ORD-CUNCONF-001',
            'user_id'        => $this->customer->id,
            'event_id'       => $event->id,
            'total_amount'   => 1500000,
            'status'         => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id'       => $order->id,
            'gross_amount'   => 1500000,
            'payment_status' => 'pending',
            'snap_token'     => 'old-snap-token',
        ]);
        PaymentAttempt::create([
            'payment_id'        => $payment->id,
            'midtrans_order_id' => 'ORD-CUNCONF-001-AOLD',
            'gross_amount'      => 1500000,
            'status'            => 'pending',
            'snap_token'        => 'old-snap-token',
        ]);

        $pendingStatus = $this->midtransWebhookPayload('ORD-CUNCONF-001-AOLD', 'pending', 'MIDTRANS-CUNCONF-001');
        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-CUNCONF-001-AOLD/cancel' => Http::response([
                'status_code'    => '500',
                'status_message' => 'Sorry. Our system is recovering from unexpected issues. Please retry.',
            ], 500),
            'api.sandbox.midtrans.com/v2/ORD-CUNCONF-001-AOLD/expire' => Http::response([
                'status_code' => '500',
                'status_message' => 'Sandbox temporarily unavailable',
            ], 500),
            'api.sandbox.midtrans.com/v2/ORD-CUNCONF-001-AOLD/status' => Http::sequence()
                ->push($pendingStatus, 200)
                ->push($pendingStatus, 200)
                ->push($pendingStatus, 200),
        ]);

        // Cancel gagal HTTP 500 → retry satu kali, lalu status tetap belum terkonfirmasi.
        $this->actingAs($this->customer)
            ->post(route('payments.cancel', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('error');

        $this->assertCount(2, Http::recorded(
            fn ($request) => str_ends_with($request->url(), '/ORD-CUNCONF-001-AOLD/cancel')
        ));
        $this->assertDatabaseHas('payment_attempts', [
            'midtrans_order_id' => 'ORD-CUNCONF-001-AOLD',
            'status'            => 'cancel_unconfirmed',
        ]);

        // Order masih pending_payment
        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'pending_payment',
        ]);

        // snap_token tetap ada (belum dihapus)
        $this->assertDatabaseHas('payments', [
            'order_id'   => $order->id,
            'snap_token' => 'old-snap-token',
        ]);

        // Retry baru diblokir selama status cancel_unconfirmed
        $this->actingAs($this->customer)
            ->post(route('payments.retry', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('error');

        // Masih 1 attempt saja
        $this->assertSame(1, PaymentAttempt::where('payment_id', $payment->id)->count());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/snap/v1/transactions'));
    }

    public function test_cancel_500_expires_pending_transaction_and_allows_method_change(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Ganti Metode Setelah Expire',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-EXPIRE-FALLBACK-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
            'snap_token' => 'old-snap-token',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-EXPIRE-FALLBACK-001-AOLD',
            'gross_amount' => 1500000,
            'status' => 'pending',
            'snap_token' => 'old-snap-token',
        ]);

        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-EXPIRE-FALLBACK-001-AOLD/cancel' => Http::response([
                'status_code' => '500',
                'status_message' => 'Sandbox temporarily unavailable',
            ], 500),
            'api.sandbox.midtrans.com/v2/ORD-EXPIRE-FALLBACK-001-AOLD/status' => Http::sequence()
                ->push($this->midtransWebhookPayload('ORD-EXPIRE-FALLBACK-001-AOLD', 'pending', 'MIDTRANS-EXPIRE-001'), 200)
                ->push($this->midtransWebhookPayload('ORD-EXPIRE-FALLBACK-001-AOLD', 'pending', 'MIDTRANS-EXPIRE-001'), 200)
                ->push($this->midtransWebhookPayload('ORD-EXPIRE-FALLBACK-001-AOLD', 'expire', 'MIDTRANS-EXPIRE-001'), 200),
            'api.sandbox.midtrans.com/v2/ORD-EXPIRE-FALLBACK-001-AOLD/expire' => Http::response([
                'status_code' => '407',
                'status_message' => 'Success, transaction has expired',
                'order_id' => 'ORD-EXPIRE-FALLBACK-001-AOLD',
                'transaction_id' => 'MIDTRANS-EXPIRE-001',
                'transaction_status' => 'expire',
                'gross_amount' => '1500000.00',
            ], 200),
        ]);

        $this->actingAs($this->customer)
            ->post(route('payments.cancel', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_attempts', [
            'midtrans_order_id' => 'ORD-EXPIRE-FALLBACK-001-AOLD',
            'status' => 'expired',
        ]);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/ORD-EXPIRE-FALLBACK-001-AOLD/expire'));

        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-EXPIRE-FALLBACK-001-AOLD/status' => Http::response(
                $this->midtransWebhookPayload('ORD-EXPIRE-FALLBACK-001-AOLD', 'expire', 'MIDTRANS-EXPIRE-001'),
                200
            ),
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'replacement-method-token',
            ], 201),
        ]);

        $this->actingAs($this->customer)
            ->post(route('payments.retry', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('success');

        $this->assertSame(2, PaymentAttempt::where('payment_id', $payment->id)->count());
        $this->assertSame(
            'replacement-method-token',
            PaymentAttempt::where('payment_id', $payment->id)->latest('id')->value('snap_token')
        );
    }

    public function test_cancel_unconfirmed_attempt_can_continue_with_existing_snap_token(): void
    {
        $event = Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Lanjutkan Pembayaran',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-CONTINUE-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 1500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gross_amount' => 1500000,
            'payment_status' => 'pending',
            'snap_token' => 'existing-snap-token',
        ]);
        PaymentAttempt::create([
            'payment_id' => $payment->id,
            'midtrans_order_id' => 'ORD-CONTINUE-001-A1',
            'gross_amount' => 1500000,
            'status' => 'cancel_unconfirmed',
            'snap_token' => 'existing-snap-token',
            'last_error' => 'Sandbox HTTP 500; transaction remains pending.',
            'payload_json' => [
                'status_check' => [
                    'transaction_status' => 'pending',
                    'gross_amount' => '1500000.00',
                    'expiry_time' => '2026-10-21 14:41:12',
                    'va_numbers' => [
                        ['bank' => 'bri', 'va_number' => '097811079493825699'],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->customer)
            ->withSession(['error' => 'Sandbox HTTP 500; transaction remains pending.'])
            ->get(route('dashboard.order', $order->order_number));

        $response->assertOk()
            ->assertSee('Lanjutkan Pembayaran yang Sama')
            ->assertSee('existing-snap-token', false)
            ->assertSee('Mode Sandbox')
            ->assertSee('Simulator QRIS Midtrans')
            ->assertSee('Simulator BRI VA Midtrans')
            ->assertSee('097811079493825699')
            ->assertSee('Nominal VA')
            ->assertSee('Sandbox HTTP 500; transaction remains pending.');
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'Sandbox HTTP 500; transaction remains pending.')
        );
    }

    public function test_cancel_unconfirmed_attempt_can_be_retried_and_succeeds(): void
    {
        $event = Event::create([
            'user_id'    => $this->customer->id,
            'event_name' => 'Acara Retry Cancel Sukses',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time'   => '18:00',
            'address'    => 'Pelaihari',
            'latitude'   => -3.795,
            'longitude'  => 114.77,
            'status'     => 'scheduled',
        ]);
        $order = Order::create([
            'order_number'   => 'ORD-RETRY-CANCEL-001',
            'user_id'        => $this->customer->id,
            'event_id'       => $event->id,
            'total_amount'   => 1500000,
            'status'         => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'order_id'       => $order->id,
            'gross_amount'   => 1500000,
            'payment_status' => 'pending',
            'snap_token'     => 'old-snap-token',
        ]);
        // Attempt sudah dalam status cancel_unconfirmed dari percobaan sebelumnya
        PaymentAttempt::create([
            'payment_id'          => $payment->id,
            'midtrans_order_id'   => 'ORD-RETRY-CANCEL-001-AOLD',
            'gross_amount'        => 1500000,
            'status'              => 'cancel_unconfirmed',
            'snap_token'          => 'old-snap-token',
            'cancel_requested_at' => now()->subMinutes(2),
        ]);

        $pendingStatus = $this->midtransWebhookPayload('ORD-RETRY-CANCEL-001-AOLD', 'pending', 'MIDTRANS-RC-001');
        Http::fake([
            'api.sandbox.midtrans.com/v2/ORD-RETRY-CANCEL-001-AOLD/cancel' => Http::response([
                'status_code'    => '200',
                'status_message' => 'Transaction is canceled.',
            ], 200),
            'api.sandbox.midtrans.com/v2/ORD-RETRY-CANCEL-001-AOLD/status' => Http::sequence()
                ->push($pendingStatus, 200)
                ->push($this->midtransWebhookPayload('ORD-RETRY-CANCEL-001-AOLD', 'cancel', 'MIDTRANS-RC-001'), 200)
                ->push($pendingStatus, 200),
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'replacement-token-after-cancel',
            ], 201),
        ]);

        // Cancel berhasil kali kedua
        $this->actingAs($this->customer)
            ->post(route('payments.cancel', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_attempts', [
            'midtrans_order_id' => 'ORD-RETRY-CANCEL-001-AOLD',
            'status'            => 'cancelled',
        ]);

        // Sekarang retry boleh membuat attempt baru
        $this->actingAs($this->customer)
            ->post(route('payments.retry', $order->order_number))
            ->assertRedirect(route('dashboard.order', $order->order_number))
            ->assertSessionHas('success');

        $this->assertSame(2, PaymentAttempt::where('payment_id', $payment->id)->count());
        $this->assertDatabaseHas('payment_attempts', [
            'payment_id' => $payment->id,
            'snap_token' => 'replacement-token-after-cancel',
        ]);
    }

    public function test_customer_can_register_then_login_with_new_account(): void
    {
        $email = 'newcustomer@example.com';

        $registerResponse = $this->post('/register', [
            'name' => 'Pelanggan Baru',
            'email' => $email,
            'phone' => '081299988877',
            'address' => 'Banjarmasin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registerResponse->assertRedirect('/login');
        $registerResponse->assertSessionHas('success', 'Pendaftaran berhasil! Silakan masuk dengan akun Anda.');
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => 'customer',
        ]);

        $loginResponse = $this->post('/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect('/');
        $this->assertAuthenticatedAs(User::where('email', $email)->first());
    }

    public function test_admin_can_access_dashboard_and_orders(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Administrator');

        $ordersResponse = $this->actingAs($this->admin)->get('/admin/orders');
        $ordersResponse->assertStatus(200);
        $ordersResponse->assertSee('Manajemen Pesanan');
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin');
        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    public function test_admin_can_add_worker_and_worker_can_access_panel(): void
    {
        // 1. Admin adds worker
        $response = $this->actingAs($this->admin)->post('/admin/workers', [
            'name' => 'Budi Staff Lapangan',
            'email' => 'budi.lapangan@arsytenda.com',
            'phone' => '081233445566',
            'address' => 'Pelaihari',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'email' => 'budi.lapangan@arsytenda.com',
            'role' => 'pekerja',
        ]);

        // 2. Worker logs in with that email
        $worker = User::where('email', 'budi.lapangan@arsytenda.com')->first();
        $this->assertNotNull($worker);

        auth()->logout();
        $this->flushSession();

        $loginResponse = $this->post('/login', [
            'email' => 'budi.lapangan@arsytenda.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect(route('admin.dashboard'));

        // 3. Worker can access operational panel
        $panelResponse = $this->actingAs($worker)->get('/admin');
        $panelResponse->assertStatus(200);
    }

    private function midtransWebhookPayload(string $orderId, string $transactionStatus, string $transactionId): array
    {
        $payload = [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '1500000.00',
            'transaction_status' => $transactionStatus,
            'payment_type' => 'bank_transfer',
            'transaction_id' => $transactionId,
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('services.midtrans.server_key')
        );

        return $payload;
    }
}
