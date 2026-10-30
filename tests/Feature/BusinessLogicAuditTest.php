<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderDetail;
use App\Models\OrderReturn;
use App\Models\Survey;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class BusinessLogicAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::set('chair_fine_damaged', 25000);
        SystemSetting::set('chair_fine_lost', 50000);
        SystemSetting::set('stock_hold_hours', 5);
        SystemSetting::set('delivery_rate_per_km', 5000);
        SystemSetting::set('free_delivery_radius_km', 15);
    }

    public function test_five_hour_stock_hold_releases_expired_orders_but_preserves_unknown_payment(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $category = Category::create(['name' => 'Tenda', 'slug' => 'tenda']);
        $inventory = Inventory::create([
            'category_id' => $category->id,
            'name' => 'Tenda Sarnafil 3x3',
            'slug' => 'tenda-sarnafil-3x3',
            'stock' => 10,
            'unit' => 'unit',
            'price' => 200000,
            'status' => 'available',
        ]);

        $eventDate = now()->addDays(3)->toDateString();

        $event1 = Event::create([
            'user_id' => $customer->id,
            'event_name' => 'Acara Lama',
            'event_date' => $eventDate,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'event_type' => 'Pernikahan',
            'address' => 'Pelaihari',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
        ]);

        // Order 1: Expired (> 5 hours) with regular pending payment -> Should be cancelled
        $orderExpired = Order::create([
            'user_id' => $customer->id,
            'event_id' => $event1->id,
            'order_number' => 'ORD-EXP-001',
            'total_amount' => 200000,
            'rental_amount' => 200000,
            'delivery_fee' => 0,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        Order::where('id', $orderExpired->id)->update(['created_at' => now()->subHours(6)]);
        $event1->update(['order_id' => $orderExpired->id]);

        OrderDetail::create([
            'order_id' => $orderExpired->id,
            'inventory_id' => $inventory->id,
            'quantity' => 4,
            'unit_price' => 200000,
            'subtotal' => 800000,
        ]);

        $event2 = Event::create([
            'user_id' => $customer->id,
            'event_name' => 'Acara Unknown',
            'event_date' => $eventDate,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'event_type' => 'Pameran',
            'address' => 'Pelaihari',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
        ]);

        // Order 2: Expired (> 5 hours) but payment is UNKNOWN -> Must NOT be cancelled (preserves hold)
        $orderUnknown = Order::create([
            'user_id' => $customer->id,
            'event_id' => $event2->id,
            'order_number' => 'ORD-UNK-002',
            'total_amount' => 200000,
            'rental_amount' => 200000,
            'delivery_fee' => 0,
            'status' => 'pending_payment',
            'payment_status' => 'unknown',
        ]);
        Order::where('id', $orderUnknown->id)->update(['created_at' => now()->subHours(6)]);
        $event2->update(['order_id' => $orderUnknown->id]);

        OrderDetail::create([
            'order_id' => $orderUnknown->id,
            'inventory_id' => $inventory->id,
            'quantity' => 3,
            'unit_price' => 200000,
            'subtotal' => 600000,
        ]);

        // Run hold release command
        Artisan::call('orders:release-expired-holds');

        $this->assertEquals('cancelled', $orderExpired->fresh()->status);
        $this->assertEquals('pending_payment', $orderUnknown->fresh()->status);
    }

    public function test_worker_role_permission_isolation(): void
    {
        $worker = User::factory()->create(['role' => 'pekerja']);

        // Worker blocked from admin-only routes and redirected to admin dashboard
        $this->actingAs($worker)->get('/admin/workers')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($worker)->get('/admin/reports/orders')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($worker)->get('/admin/inventories')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');

        // Worker allowed to access operations
        $this->actingAs($worker)->get('/admin')->assertStatus(200);
        $this->actingAs($worker)->get('/admin/surveys')->assertStatus(200);
        $this->actingAs($worker)->get('/admin/orders')->assertStatus(200);
    }

    public function test_survey_recommendation_submission_and_customer_cart_conversion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $worker = User::factory()->create(['role' => 'pekerja']);
        $customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create(['name' => 'Tenda', 'slug' => 'tenda']);
        $item = Inventory::create([
            'category_id' => $category->id,
            'name' => 'Tenda Plafon 4x6',
            'slug' => 'tenda-plafon-4x6',
            'stock' => 10,
            'unit' => 'unit',
            'price' => 250000,
            'status' => 'available',
        ]);

        $survey = Survey::create([
            'user_id' => $customer->id,
            'event_name' => 'Pesta Kebun',
            'event_date' => now()->addDays(5)->toDateString(),
            'address' => 'Jl. A. Yani No. 10',
            'latitude' => -3.80,
            'longitude' => 114.78,
            'phone' => '08123456789',
            'status' => 'dijadwalkan',
            'assigned_worker_id' => $worker->id,
        ]);

        // Worker submits survey findings and equipment recommendation
        $response = $this->actingAs($worker)->post("/admin/surveys/{$survey->id}/recommendation", [
            'survey_result' => 'Lahan tanah rata ukuran 8x12m. Bebas kabel udara.',
            'items' => [
                [
                    'inventory_id' => $item->id,
                    'quantity' => 2,
                    'notes' => 'Gunakan pasak standar.',
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $survey->refresh();
        $this->assertEquals('selesai', $survey->status);
        $this->assertTrue($survey->isRecommendationActive());
        $this->assertCount(1, $survey->recommendations_json);

        // Customer applies recommendation into cart
        $applyResponse = $this->actingAs($customer)
            ->post("/dashboard/surveys/{$survey->id}/apply-recommendation");

        $applyResponse->assertRedirect(route('cart.index'));
        $this->assertEquals(now()->addDays(5)->toDateString(), session('cart_date'));
        $cart = session('cart');
        $this->assertArrayHasKey($item->id . '_0', $cart);
        $this->assertEquals(2, $cart[$item->id . '_0']['quantity']);
    }

    public function test_order_operational_task_assignment_and_worker_progress(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $worker = User::factory()->create(['role' => 'pekerja']);
        $customer = User::factory()->create(['role' => 'customer']);

        $event = Event::create([
            'user_id' => $customer->id,
            'event_name' => 'Acara Penugasan',
            'event_date' => now()->addDays(2)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'event_type' => 'Seminar',
            'address' => 'Pelaihari',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'event_id' => $event->id,
            'order_number' => 'ORD-TASK-001',
            'total_amount' => 500000,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $event->update(['order_id' => $order->id]);

        // Admin assigns worker to delivery task
        $assignResponse = $this->actingAs($admin)->post("/admin/orders/{$order->id}/assign-task", [
            'user_id' => $worker->id,
            'task_type' => 'pengiriman',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'instructions' => 'Kirim armada pukul 08.00 pagi.',
        ]);

        $assignResponse->assertSessionHas('success');
        $assignment = OrderAssignment::where('order_id', $order->id)->where('task_type', 'pengiriman')->first();
        $this->assertNotNull($assignment);
        $this->assertEquals('pending', $assignment->status);

        // Worker updates progress
        $progressResponse = $this->actingAs($worker)->post("/admin/order-assignments/{$assignment->id}/status", [
            'status' => 'in_progress',
            'worker_notes' => 'Barang sudah dimuat dan sedang dalam perjalanan.',
        ]);

        $progressResponse->assertSessionHas('success');
        $this->assertEquals('in_progress', $assignment->fresh()->status);
    }

    public function test_return_inspection_applies_fines_strictly_to_chairs_and_guards_order_completion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $worker = User::factory()->create(['role' => 'pekerja']);
        $customer = User::factory()->create(['role' => 'customer']);

        $chairCat = Category::create(['name' => 'Kursi', 'slug' => 'kursi']);
        $tentCat = Category::create(['name' => 'Tenda', 'slug' => 'tenda']);

        $chair = Inventory::create([
            'category_id' => $chairCat->id,
            'name' => 'Kursi Futura',
            'slug' => 'kursi-futura',
            'stock' => 50,
            'unit' => 'unit',
            'price' => 10000,
            'status' => 'available',
        ]);

        $tent = Inventory::create([
            'category_id' => $tentCat->id,
            'name' => 'Tenda Roder 10x10',
            'slug' => 'tenda-roder-10x10',
            'stock' => 5,
            'unit' => 'unit',
            'price' => 1500000,
            'status' => 'available',
        ]);

        $event = Event::create([
            'user_id' => $customer->id,
            'event_name' => 'Acara Resepsi Selesai',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'event_type' => 'Resepsi',
            'address' => 'Pelaihari',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'event_id' => $event->id,
            'order_number' => 'ORD-RETURN-001',
            'total_amount' => 1700000,
            'status' => 'ongoing',
            'payment_status' => 'paid',
        ]);
        $event->update(['order_id' => $order->id]);

        $chairDetail = OrderDetail::create([
            'order_id' => $order->id,
            'inventory_id' => $chair->id,
            'quantity' => 20,
            'unit_price' => 10000,
            'subtotal' => 200000,
        ]);

        $tentDetail = OrderDetail::create([
            'order_id' => $order->id,
            'inventory_id' => $tent->id,
            'quantity' => 1,
            'unit_price' => 1500000,
            'subtotal' => 1500000,
        ]);

        // Attempting to complete order BEFORE return inspection must fail
        $completeAttempt = $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", [
            'status' => 'completed',
        ]);
        $completeAttempt->assertSessionHas('error');
        $this->assertEquals('ongoing', $order->fresh()->status);

        // Submit return inspection:
        // Chair: 15 good, 3 damaged (fine 3 * 25k = 75k), 2 lost (fine 2 * 50k = 100k) -> total chair fine 175k
        // Tent: 0 good, 1 damaged -> BUT tent has NO fine according to business rules! (fine = 0)
        $inspectResponse = $this->actingAs($admin)->post("/admin/orders/{$order->id}/return-inspection", [
            'inspection_notes' => 'Acara selesai, barang ditarik kembali.',
            'items' => [
                [
                    'order_detail_id' => $chairDetail->id,
                    'quantity_good' => 15,
                    'quantity_repair' => 0,
                    'quantity_damaged' => 3,
                    'quantity_lost' => 2,
                    'condition_notes' => '3 kursi retak kaki, 2 kursi hilang di lokasi.',
                ],
                [
                    'order_detail_id' => $tentDetail->id,
                    'quantity_good' => 0,
                    'quantity_repair' => 0,
                    'quantity_damaged' => 1,
                    'quantity_lost' => 0,
                    'condition_notes' => 'Kain terpal tergores kecil, masuk perbaikan.',
                ],
            ],
        ]);

        $inspectResponse->assertSessionHas('success');
        $return = OrderReturn::where('order_id', $order->id)->first();
        $this->assertNotNull($return);
        $this->assertEquals(175000, (float) $return->fine_chair_amount);

        // Tent return item fine must be 0
        $tentReturnItem = $return->items->firstWhere('inventory_id', $tent->id);
        $this->assertEquals(0, (float) $tentReturnItem->subtotal_fine);

        // Chair return item fine must be 175000
        $chairReturnItem = $return->items->firstWhere('inventory_id', $chair->id);
        $this->assertEquals(175000, (float) $chairReturnItem->subtotal_fine);

        // Still cannot complete while return is unverified or fine unpaid
        $completeAttempt2 = $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", [
            'status' => 'completed',
        ]);
        $completeAttempt2->assertSessionHas('error');

        // Admin verifies return inspection and marks fine paid
        $initialChairStock = $chair->fresh()->stock; // 50
        $verifyResponse = $this->actingAs($admin)->post("/admin/orders/{$order->id}/verify-return-inspection", [
            'action' => 'approve',
            'fine_status' => 'paid',
            'admin_verification_notes' => 'Denda Rp 175.000 telah diselesaikan oleh penyewa.',
        ]);
        $verifyResponse->assertSessionHas('success');

        // Check that 2 lost chairs permanently decreased inventory stock
        $this->assertEquals($initialChairStock - 2, $chair->fresh()->stock);

        // Now admin can successfully close/complete the order!
        $completeSuccess = $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", [
            'status' => 'completed',
        ]);
        $completeSuccess->assertSessionHas('success');
        $this->assertEquals('completed', $order->fresh()->status);
    }
}
