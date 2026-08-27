<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('transaction_id')->nullable()->unique();
            $table->string('payment_type')->nullable(); // bank_transfer, qris, gopay, credit_card, etc.
            $table->decimal('gross_amount', 12, 2);
            $table->string('payment_status')->default('pending'); // pending, paid, failed, expired, refunded
            $table->string('snap_token')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
