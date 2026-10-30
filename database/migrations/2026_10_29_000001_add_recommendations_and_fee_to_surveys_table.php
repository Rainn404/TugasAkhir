<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->text('survey_result')->nullable()->after('worker_instructions');
            $table->json('recommendations_json')->nullable()->after('survey_result');
            $table->timestamp('recommended_at')->nullable()->after('recommendations_json');
            $table->timestamp('recommendation_expires_at')->nullable()->after('recommended_at');
            $table->decimal('survey_fee', 12, 2)->default(0.00)->after('recommendation_expires_at');
            $table->string('survey_payment_status')->default('waived')->after('survey_fee'); // waived, pending, paid
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn([
                'survey_result',
                'recommendations_json',
                'recommended_at',
                'recommendation_expires_at',
                'survey_fee',
                'survey_payment_status',
            ]);
        });
    }
};

