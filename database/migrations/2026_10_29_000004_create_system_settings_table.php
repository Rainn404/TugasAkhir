<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed default business settings
        $defaults = [
            [
                'key' => 'delivery_rate_per_km',
                'value' => '5000',
                'description' => 'Tarif ongkir per kilometer di luar radius standar (Rp/km)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'free_delivery_radius_km',
                'value' => '15',
                'description' => 'Radius pengiriman standar / gratis dari kantor Arsy Tenda (km)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'chair_fine_damaged',
                'value' => '25000',
                'description' => 'Tarif denda per unit kursi yang rusak (Rp)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'chair_fine_lost',
                'value' => '50000',
                'description' => 'Tarif denda per unit kursi yang hilang (Rp)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'survey_fee_default',
                'value' => '0',
                'description' => 'Biaya survei lokasi default (Rp, 0 jika gratis)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'stock_hold_hours',
                'value' => '5',
                'description' => 'Maksimal durasi penguncian stok pesanan belum bayar (jam)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('system_settings')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

