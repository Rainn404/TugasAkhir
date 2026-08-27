<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
            $table->string('variant_name'); // e.g. "Ukuran", "Penutup"
            $table->string('attribute_type'); // size, cover, capacity, type
            $table->string('attribute_value'); // e.g. "6x12 m", "Penutup Full"
            $table->decimal('price_modifier', 12, 2)->default(0); // additional cost or replacement price
            $table->integer('stock_modifier')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_variants');
    }
};
