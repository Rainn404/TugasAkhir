<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Services\InventoryAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }

    public function test_customer_can_submit_survey_request(): void
    {
        $response = $this->actingAs($this->customer)->post('/survey/request', [
            'event_name' => 'Rencana Wisuda Akbar',
            'phone' => '08123456789',
            'event_date' => '2026-10-01',
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
}
