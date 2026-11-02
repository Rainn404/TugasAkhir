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
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        // 1. Worker blocked from admin-only routes and redirected to worker dashboard
        $this->actingAs($worker)->get('/admin/workers')
            ->assertRedirect(route('admin.worker.dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($worker)->get('/admin/reports/orders')
            ->assertRedirect(route('admin.worker.dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($worker)->get('/admin/inventories')
            ->assertRedirect(route('admin.worker.dashboard'))
            ->assertSessionHas('error');

        // 2. Worker visiting /admin is redirected to worker dashboard
        $this->actingAs($worker)->get('/admin')
            ->assertRedirect(route('admin.worker.dashboard'));

        // 3. Worker can access worker dashboard
        $this->actingAs($worker)->get(route('admin.worker.dashboard'))
            ->assertStatus(200)
            ->assertSee('Dashboard Petugas Lapangan');

        // 4. Setup unassigned order
        $event = Event::create([
            'user_id' => $customer->id,
            'event_name' => 'Acara Uji Role',
            'event_date' => now()->addDays(4)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'event_type' => 'Pernikahan',
            'address' => 'Pelaihari',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'event_id' => $event->id,
            'order_number' => 'ORD-ROLE-TEST',
            'total_amount' => 500000,
            'rental_amount' => 500000,
            'delivery_fee' => 0,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $event->update(['order_id' => $order->id]);

        // 5. Unassigned order NOT visible in worker order list
        $ordersResponse = $this->actingAs($worker)->get('/admin/orders');
        $ordersResponse->assertStatus(200);
        $ordersResponse->assertDontSee($order->order_number);

        // 6. Worker cannot view detail of unassigned order (403 Forbidden)
        $this->actingAs($worker)->get("/admin/orders/{$order->id}")
            ->assertStatus(403);

        // 7. Worker cannot change order status without assignment
        $failAttempt = $this->actingAs($worker)->post("/admin/orders/{$order->id}/status", [
            'status' => 'paid',
        ]);
        $failAttempt->assertSessionHas('error');
        $this->assertEquals('pending_payment', $order->fresh()->status);

        // 8. Admin marks paid manually
        $adminPaidAttempt = $this->actingAs($admin)->post("/admin/orders/{$order->id}/status", [
            'status' => 'paid',
            'admin_notes' => 'Pembayaran tunai di kantor Arsy Tenda.',
        ]);
        $adminPaidAttempt->assertSessionHas('success');
        $this->assertEquals('paid', $order->fresh()->status);

        // Admin confirms order
        $order->update(['status' => 'confirmed']);

        // 9. Admin CANNOT assign task to a customer (validation fails)
        $invalidAssign = $this->actingAs($admin)->post("/admin/orders/{$order->id}/assign-task", [
            'user_id' => $customer->id,
            'task_type' => 'persiapan',
        ]);
        $invalidAssign->assertSessionHasErrors('user_id');

        // 10. Admin assigns worker to this order
        $validAssign = $this->actingAs($admin)->post("/admin/orders/{$order->id}/assign-task", [
            'user_id' => $worker->id,
            'task_type' => 'persiapan',
            'instructions' => 'Siapkan terpal dan tiang.',
        ]);
        $validAssign->assertSessionHas('success');

        // 11. Now worker CAN view order in order list and detail
        $this->actingAs($worker)->get('/admin/orders')
            ->assertStatus(200)
            ->assertSee($order->order_number);

        $this->actingAs($worker)->get("/admin/orders/{$order->id}")
            ->assertStatus(200)
            ->assertSee($order->order_number);

        // 12. Worker WITH assignment can advance confirmed to preparing
        $workerAdvance = $this->actingAs($worker)->post("/admin/orders/{$order->id}/status", [
            'status' => 'preparing',
        ]);
        $workerAdvance->assertSessionHas('success');
        $this->assertEquals('preparing', $order->fresh()->status);

        // 13. Worker cannot set survey status to administrative values like 'ditolak'
        $survey = Survey::create([
            'user_id' => $customer->id,
            'event_name' => 'Survei Role Test',
            'event_date' => now()->addDays(5)->toDateString(),
            'event_type' => 'Pernikahan',
            'address' => 'Pelaihari',
            'phone' => '081234567890',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
            'status' => 'dijadwalkan',
            'assigned_worker_id' => $worker->id,
        ]);

        $invalidSurveyStatus = $this->actingAs($worker)->post("/admin/surveys/{$survey->id}/status", [
            'status' => 'ditolak',
        ]);
        $invalidSurveyStatus->assertSessionHasErrors('status');

        $validSurveyStatus = $this->actingAs($worker)->post("/admin/surveys/{$survey->id}/status", [
            'status' => 'diproses',
        ]);
        $validSurveyStatus->assertSessionHas('success');
        $this->assertEquals('diproses', $survey->fresh()->status);
    }

    public function test_cross_worker_task_and_survey_security_isolation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $workerA = User::factory()->create(['role' => 'pekerja', 'name' => 'Petugas A']);
        $workerB = User::factory()->create(['role' => 'pekerja', 'name' => 'Petugas B']);
        $customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Tenda Segitiga',
            'slug' => 'tenda-segitiga',
            'description' => 'Tenda sewa',
        ]);
        $inventory = Inventory::create([
            'category_id' => $category->id,
            'name' => 'Tenda Roder 10x20',
            'slug' => 'tenda-roder-10x20',
            'unit' => 'unit',
            'price' => 500000,
            'stock' => 5,
            'status' => 'available',
        ]);

        $event = Event::create([
            'user_id' => $customer->id,
            'event_name' => 'Acara Cross Worker',
            'event_date' => now()->addDays(6)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'event_type' => 'Pernikahan',
            'address' => 'Pelaihari',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'event_id' => $event->id,
            'order_number' => 'ORD-CW-01',
            'total_amount' => 500000,
            'rental_amount' => 500000,
            'delivery_fee' => 0,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $event->update(['order_id' => $order->id]);

        $orderDetail = OrderDetail::create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'quantity' => 1,
            'unit_price' => 500000,
            'subtotal' => 500000,
        ]);

        // Admin assigns Worker A to persiapan
        $assignmentA = OrderAssignment::create([
            'order_id' => $order->id,
            'user_id' => $workerA->id,
            'task_type' => 'persiapan',
            'status' => 'pending',
        ]);

        // 1. Worker B cannot update status of Worker A's task (403 Forbidden)
        $this->actingAs($workerB)->post("/admin/order-assignments/{$assignmentA->id}/status", [
            'status' => 'in_progress',
        ])->assertStatus(403);
        $this->assertEquals('pending', $assignmentA->fresh()->status);

        // Worker A can update own task
        $this->actingAs($workerA)->post("/admin/order-assignments/{$assignmentA->id}/status", [
            'status' => 'in_progress',
        ])->assertSessionHas('success');
        $this->assertEquals('in_progress', $assignmentA->fresh()->status);

        // 2. Worker A only has 'persiapan', not 'pemeriksaan_kembali'.
        // Worker A cannot submit return inspection (403 Forbidden)
        $this->actingAs($workerA)->post("/admin/orders/{$order->id}/return-inspection", [
            'items' => [
                [
                    'order_detail_id' => $orderDetail->id,
                    'quantity_good' => 1,
                    'quantity_damaged' => 0,
                    'quantity_lost' => 0,
                    'quantity_repair' => 0,
                ]
            ]
        ])->assertStatus(403);

        // 3. Worker cannot jump order status to 'completed' or 'cancelled'
        $order->update(['status' => 'ongoing']);
        $this->actingAs($workerA)->post("/admin/orders/{$order->id}/status", [
            'status' => 'completed',
        ])->assertSessionHas('error');
        $this->assertEquals('ongoing', $order->fresh()->status);

        $this->actingAs($workerA)->post("/admin/orders/{$order->id}/status", [
            'status' => 'cancelled',
        ])->assertSessionHas('error');
        $this->assertEquals('ongoing', $order->fresh()->status);

        // 4. Survey cross-worker protection:
        // Survey assigned to Worker A
        $survey = Survey::create([
            'user_id' => $customer->id,
            'event_name' => 'Survei Worker A',
            'event_date' => now()->addDays(7)->toDateString(),
            'event_type' => 'Pameran',
            'address' => 'Pelaihari',
            'phone' => '081234567890',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
            'status' => 'dijadwalkan',
            'assigned_worker_id' => $workerA->id,
        ]);

        // Worker B cannot submit recommendations for Worker A's survey (403 Forbidden)
        $this->actingAs($workerB)->post("/admin/surveys/{$survey->id}/recommendation", [
            'survey_result' => 'Hasil survei',
            'items' => [
                ['inventory_id' => $inventory->id, 'quantity' => 2]
            ]
        ])->assertStatus(403);
        $this->assertEquals('dijadwalkan', $survey->fresh()->status);

        // Worker B cannot update status of Worker A's survey (403 Forbidden)
        $this->actingAs($workerB)->post("/admin/surveys/{$survey->id}/status", [
            'status' => 'diproses',
        ])->assertStatus(403);
        $this->assertEquals('dijadwalkan', $survey->fresh()->status);

        // 5. Worker B does not see Worker A's event in /admin/events
        $eventsResponse = $this->actingAs($workerB)->get('/admin/events');
        $eventsResponse->assertStatus(200);
        $eventsResponse->assertDontSee($event->event_name);

        // Worker A DOES see the event
        $eventsResponseA = $this->actingAs($workerA)->get('/admin/events');
        $eventsResponseA->assertStatus(200);
        $eventsResponseA->assertSee($event->event_name);

        // 6. Admin cannot assign new tasks to a cancelled or completed order
        $order->update(['status' => 'cancelled']);
        $assignCancelled = $this->actingAs($admin)->post("/admin/orders/{$order->id}/assign-task", [
            'user_id' => $workerB->id,
            'task_type' => 'pengiriman',
        ]);
        $assignCancelled->assertSessionHas('error');

        // 7. Verified return inspection or completed order cannot be overwritten
        $order->update(['status' => 'completed']);
        OrderReturn::create([
            'order_id' => $order->id,
            'inspected_by_user_id' => $workerA->id,
            'status' => 'verified',
            'inspected_at' => now(),
        ]);

        $overwriteAttempt = $this->actingAs($admin)->post("/admin/orders/{$order->id}/return-inspection", [
            'items' => [
                [
                    'order_detail_id' => $orderDetail->id,
                    'quantity_good' => 1,
                    'quantity_damaged' => 0,
                    'quantity_lost' => 0,
                    'quantity_repair' => 0,
                ]
            ]
        ]);
        $overwriteAttempt->assertSessionHas('error');
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

    public function test_payment_retry_blocked_when_five_hour_hold_expired_or_stock_exhausted(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $category = Category::create(['name' => 'Tenda', 'slug' => 'tenda-khusus']);
        $inventory = Inventory::create([
            'category_id' => $category->id,
            'name' => 'Tenda Roder Eksklusif',
            'slug' => 'tenda-roder-eksklusif',
            'stock' => 2,
            'unit' => 'unit',
            'price' => 1500000,
            'status' => 'available',
        ]);

        $eventDate = now()->addDays(5)->toDateString();
        $event = Event::create([
            'user_id' => $customer->id,
            'event_name' => 'Acara Pernikahan Akbar',
            'event_date' => $eventDate,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'event_type' => 'Pernikahan',
            'address' => 'Pelaihari',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'event_id' => $event->id,
            'order_number' => 'ORD-EXP-RETRY',
            'total_amount' => 3000000,
            'rental_amount' => 3000000,
            'delivery_fee' => 0,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $event->update(['order_id' => $order->id]);

        OrderDetail::create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'quantity' => 2,
            'unit_price' => 1500000,
            'subtotal' => 3000000,
        ]);

        // Case A: Masa tahan 5 jam telah habis (> 5 jam)
        Order::where('id', $order->id)->update(['created_at' => now()->subHours(6)]);

        $response = $this->actingAs($customer)->post(route('payments.retry', $order->order_number));
        $response->assertRedirect(route('dashboard.order', $order->order_number));
        $response->assertSessionHas('error');
        $this->assertEquals('cancelled', $order->fresh()->status);

        // Case B: Pesanan baru (dalam 5 jam), tetapi inventaris tiba-tiba masuk maintenance / stok habis
        $order2 = Order::create([
            'user_id' => $customer->id,
            'event_id' => $event->id,
            'order_number' => 'ORD-STOCK-EXHAUST',
            'total_amount' => 3000000,
            'rental_amount' => 3000000,
            'delivery_fee' => 0,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'created_at' => now()->subHours(1),
        ]);

        OrderDetail::create([
            'order_id' => $order2->id,
            'inventory_id' => $inventory->id,
            'quantity' => 2,
            'unit_price' => 1500000,
            'subtotal' => 3000000,
        ]);

        // Admin menandai inventaris masuk perbaikan (maintenance)
        $inventory->update(['status' => 'maintenance']);

        $response2 = $this->actingAs($customer)->post(route('payments.retry', $order2->order_number));
        $response2->assertRedirect(route('dashboard.order', $order2->order_number));
        $response2->assertSessionHas('error');
        $this->assertEquals('cancelled', $order2->fresh()->status);
    }

    public function test_sidebar_navigation_and_profile_access_per_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Test', 'email' => 'admintest@example.com']);
        $worker = User::factory()->create(['role' => 'pekerja', 'name' => 'Petugas Test', 'email' => 'workertest@example.com']);

        // 1. Admin Sidebar Navigation Check
        $adminResp = $this->actingAs($admin)->get(route('admin.dashboard'));
        $adminResp->assertStatus(200);
        $adminResp->assertSee('Dashboard Admin');
        $adminResp->assertSee('Pesanan');
        $adminResp->assertSee('Kegiatan Acara');
        $adminResp->assertSee('Survei Lokasi');
        $adminResp->assertSee('Inventaris');
        $adminResp->assertSee('Variasi Inventaris');
        $adminResp->assertSee('Pembayaran');
        $adminResp->assertSee('Pelanggan');
        $adminResp->assertSee('Pekerja / Tim Lapangan');
        $adminResp->assertSee('Laporan Pesanan');
        $adminResp->assertSee('Laporan Inventaris');
        $adminResp->assertSee('Laporan Pembayaran');
        $adminResp->assertSee('Gambar &amp; Portofolio', false);
        $adminResp->assertSee('Lihat Website');
        $adminResp->assertSee('Profil Saya');
        $adminResp->assertSee('Logout');
        // Admin should not see worker-specific sidebar items
        $adminResp->assertDontSee('Dashboard Petugas');
        $adminResp->assertDontSee('Tugas Saya');
        $adminResp->assertDontSee('Jadwal Kerja');
        $adminResp->assertDontSee('Pemeriksaan Barang');
        $adminResp->assertDontSee('Riwayat Pekerjaan');

        // 2. Worker Sidebar Navigation Check
        $workerResp = $this->actingAs($worker)->get(route('admin.worker.dashboard'));
        $workerResp->assertStatus(200);
        $workerResp->assertSee('Dashboard Petugas');
        $workerResp->assertSee('Tugas Saya');
        $workerResp->assertSee('Jadwal Kerja');
        $workerResp->assertSee('Survei Lokasi');
        $workerResp->assertSee('Pemeriksaan Barang');
        $workerResp->assertSee('Riwayat Pekerjaan');
        $workerResp->assertSee('Profil Saya');
        $workerResp->assertSee('Logout');
        // Worker must not see admin management items
        $workerResp->assertDontSee('Dashboard Admin');
        $workerResp->assertDontSee('Pekerja / Tim Lapangan');
        $workerResp->assertDontSee('Variasi Inventaris');
        $workerResp->assertDontSee('Laporan Pesanan');
        $workerResp->assertDontSee('Laporan Inventaris');
        $workerResp->assertDontSee('Laporan Pembayaran');
        $workerResp->assertDontSee('Gambar &amp; Portofolio', false);

        // 3. Worker can access and update their profile
        $profileView = $this->actingAs($worker)->get(route('admin.profile'));
        $profileView->assertStatus(200);
        $profileView->assertSee('Profil Saya');
        $profileView->assertSee('PETUGAS LAPANGAN');

        $profileUpdate = $this->actingAs($worker)->post(route('admin.profile.update'), [
            'name' => 'Petugas Diperbarui',
            'email' => $worker->email,
            'phone' => '089999888777',
            'address' => 'Basecamp Baru Pelaihari',
        ]);
        $profileUpdate->assertSessionHas('success');
        $this->assertEquals('Petugas Diperbarui', $worker->fresh()->name);
        $this->assertEquals('089999888777', $worker->fresh()->phone);

        // 4. Admin workers management shows PETUGAS LAPANGAN badge and can update role
        $workersList = $this->actingAs($admin)->get(route('admin.workers'));
        $workersList->assertStatus(200);
        $workersList->assertSee('PETUGAS LAPANGAN');

        $roleUpdate = $this->actingAs($admin)->post(route('admin.workers.update', $worker->id), [
            'name' => $worker->name,
            'email' => $worker->email,
            'phone' => $worker->phone,
            'role' => 'admin',
        ]);
        $roleUpdate->assertSessionHas('success');
        $this->assertEquals('admin', $worker->fresh()->role);
    }
}
