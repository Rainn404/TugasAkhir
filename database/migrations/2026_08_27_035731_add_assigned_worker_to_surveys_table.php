<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            // Worker yang ditugaskan melakukan survei lapangan
            $table->foreignId('assigned_worker_id')
                ->nullable()
                ->after('admin_notes')
                ->constrained('users')
                ->nullOnDelete();

            // Catatan / instruksi khusus untuk pekerja yang ditugaskan
            $table->text('worker_instructions')->nullable()->after('assigned_worker_id');
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['assigned_worker_id']);
            $table->dropColumn(['assigned_worker_id', 'worker_instructions']);
        });
    }
};
