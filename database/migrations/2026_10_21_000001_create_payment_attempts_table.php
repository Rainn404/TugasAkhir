<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('midtrans_order_id')->unique();
            $table->string('transaction_id')->nullable()->index();
            $table->string('payment_type')->nullable();
            $table->decimal('gross_amount', 12, 2);
            $table->string('status')->default('creating')->index();
            $table->string('snap_token')->nullable();
            $table->json('payload_json')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['payment_id', 'status']);
        });

        $knownStatuses = ['creating', 'pending', 'paid', 'failed', 'expired', 'cancelled', 'cancel_requested', 'unknown', 'conflict'];

        foreach (DB::table('payments')->orderBy('id')->cursor() as $payment) {
            $payload = is_array($payment->payload_json)
                ? $payment->payload_json
                : (json_decode($payment->payload_json ?? '[]', true) ?: []);
            $orderNumber = DB::table('orders')->where('id', $payment->order_id)->value('order_number');

            foreach ($payload['previous_attempts'] ?? [] as $attempt) {
                $midtransOrderId = $attempt['midtrans_order_id'] ?? null;
                if (!$midtransOrderId || DB::table('payment_attempts')->where('midtrans_order_id', $midtransOrderId)->exists()) {
                    continue;
                }

                $attemptStatus = $attempt['payment_status'] ?? 'expired';
                DB::table('payment_attempts')->insert([
                    'payment_id' => $payment->id,
                    'midtrans_order_id' => $midtransOrderId,
                    'transaction_id' => $attempt['transaction_id'] ?? null,
                    'payment_type' => $attempt['payment_type'] ?? null,
                    'gross_amount' => $payment->gross_amount,
                    'status' => in_array($attemptStatus, $knownStatuses, true) ? $attemptStatus : 'unknown',
                    'snap_token' => $attempt['snap_token'] ?? null,
                    'payload_json' => json_encode($attempt),
                    'last_error' => data_get($attempt, 'cancel_result.message'),
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ]);
            }

            $midtransOrderId = $payload['midtrans_order_id'] ?? $orderNumber;
            if (!$midtransOrderId || DB::table('payment_attempts')->where('midtrans_order_id', $midtransOrderId)->exists()) {
                continue;
            }

            $attemptStatus = $payment->payment_status ?? 'pending';
            DB::table('payment_attempts')->insert([
                'payment_id' => $payment->id,
                'midtrans_order_id' => $midtransOrderId,
                'transaction_id' => $payment->transaction_id,
                'payment_type' => $payment->payment_type,
                'gross_amount' => $payment->gross_amount,
                'status' => in_array($attemptStatus, $knownStatuses, true) ? $attemptStatus : 'unknown',
                'snap_token' => $payment->snap_token,
                'payload_json' => json_encode($payload),
                'paid_at' => $payment->paid_at,
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};