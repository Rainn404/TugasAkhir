<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('inspected_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('submitted'); // submitted, verified, completed
            $table->timestamp('inspected_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->decimal('fine_chair_amount', 12, 2)->default(0.00); // Denda khusus kursi
            $table->string('fine_status')->default('none'); // none, pending_payment, paid, waived
            $table->text('inspection_notes')->nullable();
            $table->text('admin_verification_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained('order_returns')->cascadeOnDelete();
            $table->foreignId('order_detail_id')->constrained('order_details')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->integer('quantity_rented');
            $table->integer('quantity_good')->default(0);
            $table->integer('quantity_damaged')->default(0);
            $table->integer('quantity_lost')->default(0);
            $table->integer('quantity_repair')->default(0);
            $table->decimal('fine_per_item', 12, 2)->default(0.00); // Khusus kursi
            $table->decimal('subtotal_fine', 12, 2)->default(0.00);
            $table->text('condition_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');
    }
};

