<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@arsytenda.test',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'customer@arsytenda.test',
        ]);

        $this->worker = User::factory()->create([
            'role' => 'pekerja',
            'email' => 'worker@arsytenda.test',
        ]);

        Storage::fake('public');
    }

    public function test_guest_cannot_access_content_management()
    {
        $response = $this->get(route('admin.content.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_content_management()
    {
        $response = $this->actingAs($this->customer)->get(route('admin.content.index'));
        $response->assertRedirect(route('home'));
    }

    public function test_worker_cannot_access_content_management()
    {
        $response = $this->actingAs($this->worker)->get(route('admin.content.index'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_view_content_management()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.content.index'));
        $response->assertStatus(200);
        $response->assertSee('Manajemen Gambar Beranda & Portofolio');
        $response->assertSee('Banner Utama Beranda (Hero Section)');
        $response->assertSee('Kolase Portofolio Acara');
        $response->assertSee('Slot 1');
        $response->assertSee('Slot 4');
    }

    public function test_admin_can_update_hero_image()
    {
        $file = UploadedFile::fake()->create('hero-custom.jpg', 300, 'image/jpeg');

        $response = $this->actingAs($this->admin)->post(route('admin.content.update-hero'), [
            'image' => $file,
            'alt' => 'Tenda Pernikahan Mewah Kustom',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $storedPath = SystemSetting::get('homepage_hero_image');
        $this->assertNotNull($storedPath);
        Storage::disk('public')->assertExists($storedPath);
        $this->assertEquals('Tenda Pernikahan Mewah Kustom', SystemSetting::get('homepage_hero_alt'));

        // Check homepage reflects custom image
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee($storedPath);
        $homeResponse->assertSee('Tenda Pernikahan Mewah Kustom');
    }

    public function test_admin_can_update_portfolio_slot_image()
    {
        $file = UploadedFile::fake()->create('portfolio-slot2.webp', 250, 'image/webp');

        $response = $this->actingAs($this->admin)->post(route('admin.content.update-portfolio', ['slot' => 2]), [
            'image' => $file,
            'alt' => 'Panggung Konser Mini Kustom',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $storedPath = SystemSetting::get('portfolio_image_2');
        $this->assertNotNull($storedPath);
        Storage::disk('public')->assertExists($storedPath);
        $this->assertEquals('Panggung Konser Mini Kustom', SystemSetting::get('portfolio_alt_2'));

        // Check homepage reflects custom portfolio image
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee($storedPath);
        $homeResponse->assertSee('Panggung Konser Mini Kustom');
    }

    public function test_admin_can_reset_image_to_default()
    {
        $file = UploadedFile::fake()->create('hero-custom.jpg', 300, 'image/jpeg');
        $this->actingAs($this->admin)->post(route('admin.content.update-hero'), [
            'image' => $file,
            'alt' => 'Foto Sebelum Reset',
        ]);

        $storedPath = SystemSetting::get('homepage_hero_image');
        Storage::disk('public')->assertExists($storedPath);

        // Perform reset
        $resetResponse = $this->actingAs($this->admin)->post(route('admin.content.reset', [
            'key' => 'homepage_hero_image',
        ]));

        $resetResponse->assertRedirect();
        $resetResponse->assertSessionHas('success');

        $this->assertNull(SystemSetting::get('homepage_hero_image'));
        Storage::disk('public')->assertMissing($storedPath);

        // Homepage should fallback to default asset
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('assets/images/hero-tent.jpg');
    }
}
