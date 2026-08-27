<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Inventory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users
        User::create([
            'name' => 'Admin Arsy Tenda',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'phone' => '08123456789',
            'address' => 'Jl. Admin No. 1, Jakarta',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'Petugas Lapangan Arsy Tenda',
            'email' => 'pekerja@example.com',
            'role' => 'admin',
            'phone' => '081298765432',
            'address' => 'Gudang Arsy Tenda, Pelaihari',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'Customer Arsy Tenda',
            'email' => 'customer@example.com',
            'role' => 'customer',
            'phone' => '08987654321',
            'address' => 'Jl. Customer No. 22, Bogor',
            'password' => bcrypt('password'),
        ]);

        // 2. Create Categories
        $categories = [
            'Tenda' => ['slug' => 'tenda', 'description' => 'Penyewaan berbagai macam jenis tenda untuk segala acara.', 'icon' => 'tent'],
            'Meja' => ['slug' => 'meja', 'description' => 'Penyewaan meja bundar dan meja panjang lengkap dengan cover.', 'icon' => 'table'],
            'Kursi' => ['slug' => 'kursi', 'description' => 'Penyewaan kursi plastik dan kursi futura premium.', 'icon' => 'chair'],
            'Panggung' => ['slug' => 'panggung', 'description' => 'Penyewaan panggung rigging dan panggung portable untuk pameran atau musik.', 'icon' => 'stage'],
            'Blower' => ['slug' => 'blower', 'description' => 'Penyewaan blower air / misty fan pendingin ruangan terbuka.', 'icon' => 'fan'],
        ];

        $categoryModels = [];
        foreach ($categories as $name => $data) {
            $categoryModels[$name] = Category::create([
                'name' => $name,
                'slug' => $data['slug'],
                'description' => $data['description'],
                'icon' => $data['icon'],
            ]);
        }

        // 3. Create Inventories & Variants
        // Category: Tenda
        $roder = Inventory::create([
            'category_id' => $categoryModels['Tenda']->id,
            'name' => 'Tenda Roder',
            'slug' => 'tenda-roder',
            'description' => 'Tenda roder bentangan kokoh tanpa tiang tengah, cocok untuk pameran besar dan pernikahan VIP.',
            'price' => 1500000.00,
            'stock' => 5,
            'unit' => 'unit',
            'image' => null,
            'status' => 'available',
        ]);
        
        $roder->variants()->createMany([
            ['variant_name' => 'Ukuran 10x10 m', 'attribute_type' => 'size', 'attribute_value' => '10x10 m', 'price_modifier' => 0.00, 'stock_modifier' => 0],
            ['variant_name' => 'Ukuran 10x20 m', 'attribute_type' => 'size', 'attribute_value' => '10x20 m', 'price_modifier' => 1000000.00, 'stock_modifier' => 0],
            ['variant_name' => 'Ukuran 10x30 m', 'attribute_type' => 'size', 'attribute_value' => '10x30 m', 'price_modifier' => 2000000.00, 'stock_modifier' => 0],
            ['variant_name' => 'Cover VIP White', 'attribute_type' => 'cover', 'attribute_value' => 'VIP White', 'price_modifier' => 500000.00, 'stock_modifier' => 0],
        ]);

        $sarnafil = Inventory::create([
            'category_id' => $categoryModels['Tenda']->id,
            'name' => 'Tenda Sarnafil',
            'slug' => 'tenda-sarnafil',
            'description' => 'Tenda kerucut modern yang sering digunakan untuk bazar, pameran outdoor, maupun posko.',
            'price' => 300000.00,
            'stock' => 15,
            'unit' => 'unit',
            'image' => null,
            'status' => 'available',
        ]);

        $sarnafil->variants()->createMany([
            ['variant_name' => 'Ukuran 3x3 m', 'attribute_type' => 'size', 'attribute_value' => '3x3 m', 'price_modifier' => 0.00, 'stock_modifier' => 0],
            ['variant_name' => 'Ukuran 5x5 m', 'attribute_type' => 'size', 'attribute_value' => '5x5 m', 'price_modifier' => 200000.00, 'stock_modifier' => 0],
        ]);

        // Category: Meja
        $mejaBundar = Inventory::create([
            'category_id' => $categoryModels['Meja']->id,
            'name' => 'Meja Bundar',
            'slug' => 'meja-bundar',
            'description' => 'Meja bundar kayu kokoh untuk dinner party.',
            'price' => 50000.00,
            'stock' => 50,
            'unit' => 'pcs',
            'image' => null,
            'status' => 'available',
        ]);

        $mejaBundar->variants()->createMany([
            ['variant_name' => 'Tanpa Cover', 'attribute_type' => 'cover', 'attribute_value' => 'Tanpa Cover', 'price_modifier' => 0.00, 'stock_modifier' => 0],
            ['variant_name' => 'Cover Putih Polos', 'attribute_type' => 'cover', 'attribute_value' => 'Putih Polos', 'price_modifier' => 15000.00, 'stock_modifier' => 0],
            ['variant_name' => 'Cover VIP Gold', 'attribute_type' => 'cover', 'attribute_value' => 'VIP Gold', 'price_modifier' => 25000.00, 'stock_modifier' => 0],
        ]);

        $mejaPanjang = Inventory::create([
            'category_id' => $categoryModels['Meja']->id,
            'name' => 'Meja Panjang (IBM)',
            'slug' => 'meja-panjang',
            'description' => 'Meja panjang lipat untuk seminar, prasmanan, maupun registrasi.',
            'price' => 40000.00,
            'stock' => 80,
            'unit' => 'pcs',
            'image' => null,
            'status' => 'available',
        ]);

        $mejaPanjang->variants()->createMany([
            ['variant_name' => 'Tanpa Cover', 'attribute_type' => 'cover', 'attribute_value' => 'Tanpa Cover', 'price_modifier' => 0.00, 'stock_modifier' => 0],
            ['variant_name' => 'Cover Hitam Stretch', 'attribute_type' => 'cover', 'attribute_value' => 'Hitam Stretch', 'price_modifier' => 10000.00, 'stock_modifier' => 0],
        ]);

        // Category: Kursi
        $futura = Inventory::create([
            'category_id' => $categoryModels['Kursi']->id,
            'name' => 'Kursi Futura',
            'slug' => 'kursi-futura',
            'description' => 'Kursi banquet dengan busa tebal berlapis kain beledu, cocok untuk seminar dan pernikahan.',
            'price' => 8000.00,
            'stock' => 500,
            'unit' => 'pcs',
            'image' => null,
            'status' => 'available',
        ]);

        $futura->variants()->createMany([
            ['variant_name' => 'Tanpa Cover', 'attribute_type' => 'cover', 'attribute_value' => 'Tanpa Cover', 'price_modifier' => 0.00, 'stock_modifier' => 0],
            ['variant_name' => 'Cover Putih Ketat', 'attribute_type' => 'cover', 'attribute_value' => 'Putih Ketat', 'price_modifier' => 3000.00, 'stock_modifier' => 0],
            ['variant_name' => 'Cover VIP + Pita Gold', 'attribute_type' => 'cover', 'attribute_value' => 'VIP + Pita Gold', 'price_modifier' => 5000.00, 'stock_modifier' => 0],
        ]);

        $kursiPlastik = Inventory::create([
            'category_id' => $categoryModels['Kursi']->id,
            'name' => 'Kursi Plastik',
            'slug' => 'kursi-plastik',
            'description' => 'Kursi plastik merk Napolly yang ringan dan praktis untuk acara santai.',
            'price' => 4000.00,
            'stock' => 300,
            'unit' => 'pcs',
            'image' => null,
            'status' => 'available',
        ]);

        $kursiPlastik->variants()->createMany([
            ['variant_name' => 'Tanpa Cover', 'attribute_type' => 'cover', 'attribute_value' => 'Tanpa Cover', 'price_modifier' => 0.00, 'stock_modifier' => 0],
            ['variant_name' => 'Cover Polos Putih', 'attribute_type' => 'cover', 'attribute_value' => 'Polos Putih', 'price_modifier' => 2000.00, 'stock_modifier' => 0],
        ]);

        // Category: Panggung
        $rigging = Inventory::create([
            'category_id' => $categoryModels['Panggung']->id,
            'name' => 'Panggung Rigging',
            'slug' => 'panggung-rigging',
            'description' => 'Panggung konser outdoor berangka besi truss kokoh lengkap dengan atap terpal.',
            'price' => 2500000.00,
            'stock' => 3,
            'unit' => 'set',
            'image' => null,
            'status' => 'available',
        ]);

        $rigging->variants()->createMany([
            ['variant_name' => 'Ukuran 6x8 m', 'attribute_type' => 'size', 'attribute_value' => '6x8 m', 'price_modifier' => 0.00, 'stock_modifier' => 0],
            ['variant_name' => 'Ukuran 8x10 m', 'attribute_type' => 'size', 'attribute_value' => '8x10 m', 'price_modifier' => 1500000.00, 'stock_modifier' => 0],
        ]);

        $portableStage = Inventory::create([
            'category_id' => $categoryModels['Panggung']->id,
            'name' => 'Panggung Portable',
            'slug' => 'panggung-portable',
            'description' => 'Panggung modul modular yang mudah dipasang di dalam gedung untuk acara formal/seminar.',
            'price' => 500000.00,
            'stock' => 10,
            'unit' => 'set',
            'image' => null,
            'status' => 'available',
        ]);

        $portableStage->variants()->createMany([
            ['variant_name' => 'Tinggi 50 cm', 'attribute_type' => 'height', 'attribute_value' => '50 cm', 'price_modifier' => 0.00, 'stock_modifier' => 0],
            ['variant_name' => 'Tinggi 1 m', 'attribute_type' => 'height', 'attribute_value' => '1 m', 'price_modifier' => 150000.00, 'stock_modifier' => 0],
        ]);

        // Category: Blower
        $mistyFan = Inventory::create([
            'category_id' => $categoryModels['Blower']->id,
            'name' => 'Misty Fan (Blower Air)',
            'slug' => 'misty-fan',
            'description' => 'Kipas angin blower air berembun untuk menyejukkan udara panas di area outdoor.',
            'price' => 250000.00,
            'stock' => 12,
            'unit' => 'unit',
            'image' => null,
            'status' => 'available',
        ]);

        $mistyFan->variants()->createMany([
            ['variant_name' => 'Standar', 'attribute_type' => 'type', 'attribute_value' => 'Standar', 'price_modifier' => 0.00, 'stock_modifier' => 0],
            ['variant_name' => 'Premium Quiet', 'attribute_type' => 'type', 'attribute_value' => 'Premium Quiet', 'price_modifier' => 100000.00, 'stock_modifier' => 0],
        ]);
    }
}
