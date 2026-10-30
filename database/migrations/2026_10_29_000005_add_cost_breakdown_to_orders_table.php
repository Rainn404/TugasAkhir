<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('rental_amount', 12, 2)->default(0.00)->after('event_id');
            $table->decimal('delivery_fee', 12, 2)->default(0.00)->after('rental_amount');
            $table->decimal('survey_fee', 12, 2)->default(0.00)->after('delivery_fee');
        });

        // Backfill existing orders: rental_amount = total_amount
        \Illuminate\Support\Facades\DB::statement('UPDATE orders SET rental_amount = total_amount WHERE rental_amount = 0');
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['rental_amount', 'delivery_fee', 'survey_fee']);
        });
    }
};

