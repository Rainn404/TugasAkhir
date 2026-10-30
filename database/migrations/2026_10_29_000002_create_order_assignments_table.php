<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Petugas / pekerja
            $table->string('task_type'); // persiapan, pengiriman, pemasangan, pemeriksaan_kembali
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('assigned'); // assigned, in_progress, completed, cancelled
            $table->text('instructions')->nullable();
            $table->text('worker_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_assignments');
    }
};

