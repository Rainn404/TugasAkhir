<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\User;
use App\Services\GoogleMapsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleMapsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'phone' => '081234567890',
        ]);
    }

    public function test_maps_config_endpoint_returns_client_key_and_base_location(): void
    {
        $response = $this->getJson(route('maps.config'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'client_key',
                'base_location' => [
                    'name',
                    'address',
                    'latitude',
                    'longitude',
                ],
            ]);

        // Ensure server_key is NEVER exposed in the JSON response
        $this->assertArrayNotHasKey('server_key', $response->json());
    }

    public function test_geocode_endpoint_validates_input_address(): void
    {
        $response = $this->postJson(route('maps.geocode'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address']);
    }

    public function test_geocode_endpoint_returns_coordinates_on_success(): void
    {
        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'OK',
                'results' => [
                    [
                        'formatted_address' => 'Jl. A. Yani, Angsau, Pelaihari, Kabupaten Tanah Laut, Kalimantan Selatan',
                        'geometry' => [
                            'location' => [
                                'lat' => -3.7991234,
                                'lng' => 114.7785678,
                            ],
                        ],
                        'place_id' => 'ChIJ_1234567890',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('maps.geocode'), [
            'address' => 'Jl. A. Yani Pelaihari',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'latitude' => -3.7991234,
                'longitude' => 114.7785678,
                'formatted_address' => 'Jl. A. Yani, Angsau, Pelaihari, Kabupaten Tanah Laut, Kalimantan Selatan',
                'place_id' => 'ChIJ_1234567890',
            ]);
    }

    public function test_geocode_endpoint_handles_zero_results_gracefully(): void
    {
        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'ZERO_RESULTS',
                'results' => [],
            ], 200),
        ]);

        $response = $this->postJson(route('maps.geocode'), [
            'address' => 'Tempat Antah Berantah 99999999',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('tidak ditemukan', $response->json('message'));
    }

    public function test_reverse_geocode_endpoint_returns_formatted_address(): void
    {
        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'OK',
                'results' => [
                    [
                        'formatted_address' => 'Komplek Perkantoran Gagas, Pelaihari, Tanah Laut',
                        'place_id' => 'ChIJ_abcdef',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('maps.reverse-geocode'), [
            'latitude' => -3.7955,
            'longitude' => 114.7712,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'formatted_address' => 'Komplek Perkantoran Gagas, Pelaihari, Tanah Laut',
            ]);
    }

    public function test_calculate_route_endpoint_uses_routes_api(): void
    {
        Http::fake([
            'https://routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'routes' => [
                    [
                        'distanceMeters' => 5200,
                        'duration' => '720s',
                        'polyline' => [
                            'encodedPolyline' => '`_ib@~xvbT...',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('maps.calculate-route'), [
            'destination_latitude' => -3.8200,
            'destination_longitude' => 114.7900,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'distance_meters' => 5200,
                'distance_km' => 5.2,
                'duration_text' => '12 menit',
                'polyline' => '`_ib@~xvbT...',
                'is_fallback' => false,
            ]);

        $this->assertNotEmpty($response->json('shipping_note'));
    }

    public function test_calculate_route_endpoint_falls_back_when_routes_api_fails(): void
    {
        Http::fake([
            'https://routes.googleapis.com/directions/v2:computeRoutes' => Http::response([
                'error' => ['message' => 'API Unavailable'],
            ], 500),
        ]);

        $response = $this->postJson(route('maps.calculate-route'), [
            'destination_latitude' => -3.8500,
            'destination_longitude' => 114.8000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'is_fallback' => true,
            ]);

        $this->assertGreaterThan(0, $response->json('distance_km'));
        $this->assertNotEmpty($response->json('duration_text'));
    }

    public function test_customer_can_submit_survey_with_google_maps_coordinates(): void
    {
        $this->actingAs($this->customer);

        $response = $this->post(route('survey.store'), [
            'event_name' => 'Survei Lapangan Pesta Pernikahan Pelaihari',
            'event_date' => now()->addDays(5)->toDateString(),
            'address' => 'Jl. Datu Daim, Sarang Halang, Pelaihari, Tanah Laut',
            'latitude' => -3.8051234,
            'longitude' => 114.7812345,
            'phone' => '081234567890',
            'notes' => 'Tenda 10x20 meter di halaman depan rumah',
        ]);

        $response->assertRedirect(route('dashboard.surveys'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('surveys', [
            'user_id' => $this->customer->id,
            'event_name' => 'Survei Lapangan Pesta Pernikahan Pelaihari',
            'latitude' => -3.8051234,
            'longitude' => 114.7812345,
            'address' => 'Jl. Datu Daim, Sarang Halang, Pelaihari, Tanah Laut',
            'status' => 'menunggu',
        ]);
    }

    public function test_customer_can_checkout_and_store_google_maps_coordinates_in_event(): void
    {
        $category = Category::create([
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);

        $inventory = Inventory::create([
            'category_id' => $category->id,
            'name' => 'Tenda Roder Elegan',
            'slug' => 'tenda-roder-elegan',
            'unit' => 'unit',
            'price' => 1500000,
            'stock' => 5,
            'status' => 'available',
        ]);

        $this->actingAs($this->customer);

        $cartDate = now()->addDays(7)->toDateString();
        $cart = [
            $inventory->id . '_default' => [
                'inventory_id' => $inventory->id,
                'name' => $inventory->name,
                'unit' => $inventory->unit,
                'unit_price' => 1500000,
                'quantity' => 1,
                'subtotal' => 1500000,
            ],
        ];

        session()->put('cart', $cart);
        session()->put('cart_date', $cartDate);

        $response = $this->post(route('checkout.store'), [
            'event_name' => 'Acara Syukuran Akbar Pelaihari',
            'event_type' => 'Syukuran',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'address' => 'Jl. Kolonel Soepirman No. 12, Pelaihari',
            'latitude' => -3.7987654,
            'longitude' => 114.7723456,
            'notes' => 'Pasang tenda H-1 sebelum acara',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('events', [
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Syukuran Akbar Pelaihari',
            'latitude' => -3.7987654,
            'longitude' => 114.7723456,
            'address' => 'Jl. Kolonel Soepirman No. 12, Pelaihari',
        ]);
    }

    public function test_user_order_detail_renders_google_maps_and_no_leaflet(): void
    {
        $event = \App\Models\Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Peta User',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Jl. A. Yani No. 1, Pelaihari',
            'latitude' => -3.795,
            'longitude' => 114.77,
            'status' => 'scheduled',
        ]);

        $order = \App\Models\Order::create([
            'order_number' => 'ORD-MAP-USER-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('dashboard.order', $order->order_number))
            ->assertOk();

        $response->assertSee('maps.googleapis.com/maps/api/js', false)
            ->assertSee('initOrderMap')
            ->assertDontSee('leaflet.js', false)
            ->assertDontSee('leaflet.css', false)
            ->assertDontSee('openstreetmap.org', false);
    }

    public function test_admin_order_detail_renders_google_maps_and_no_leaflet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $event = \App\Models\Event::create([
            'user_id' => $this->customer->id,
            'event_name' => 'Acara Peta Admin',
            'event_type' => 'Pernikahan',
            'event_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '08:00',
            'end_time' => '18:00',
            'address' => 'Jl. A. Yani No. 2, Pelaihari',
            'latitude' => -3.796,
            'longitude' => 114.771,
            'status' => 'scheduled',
        ]);

        $order = \App\Models\Order::create([
            'order_number' => 'ORD-MAP-ADMIN-001',
            'user_id' => $this->customer->id,
            'event_id' => $event->id,
            'total_amount' => 500000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->id))
            ->assertOk();

        $response->assertSee('maps.googleapis.com/maps/api/js', false)
            ->assertSee('initAdminOrderMap')
            ->assertDontSee('leaflet.js', false)
            ->assertDontSee('leaflet.css', false)
            ->assertDontSee('openstreetmap.org', false);
    }

    public function test_checkout_delivery_fee_calculated_by_server_and_client_tampering_ignored(): void
    {
        $category = \App\Models\Category::firstOrCreate(['slug' => 'tenda-test'], ['name' => 'Tenda']);
        $inventory = \App\Models\Inventory::create([
            'category_id' => $category->id,
            'name' => 'Tenda Roder Elegan',
            'slug' => 'tenda-roder-test-' . uniqid(),
            'unit' => 'unit',
            'price' => 300000,
            'stock' => 5,
            'status' => 'available',
        ]);

        $eventDate = date('Y-m-d', strtotime('+7 days'));
        $cartKey = "{$inventory->id}_";
        $session = [
            'cart' => [
                $cartKey => [
                    'inventory_id' => $inventory->id,
                    'variant_id' => null,
                    'name' => $inventory->name,
                    'variant_name' => null,
                    'unit_price' => 300000,
                    'quantity' => 1,
                    'subtotal' => 300000,
                ],
            ],
            'cart_date' => $eventDate,
        ];

        // Lokasi berjarak ~35 km dari Pelaihari (melebihi radius gratis 15 km)
        // Dest: -3.5000, 114.7712 (kurang lebih 33-35 km)
        $checkoutData = [
            'event_name' => 'Acara Jarak Jauh',
            'event_type' => 'Pernikahan',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'address' => 'Jl. Trans Kalimantan Km 35',
            'latitude' => -3.5000,
            'longitude' => 114.7712,
            'delivery_fee' => 0, // Client mencoba memalsukan gratis ongkir
        ];

        $response = $this->actingAs($this->customer)
            ->withSession($session)
            ->post(route('checkout.store'), $checkoutData);

        $order = \App\Models\Order::where('user_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        // Server harus menolak nilai 0 dari client dan menghitung ongkir sebenarnya
        $this->assertGreaterThan(0, (float) $order->delivery_fee);
        $this->assertEquals(300000 + $order->delivery_fee, (float) $order->total_amount);
    }
}
