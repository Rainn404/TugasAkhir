<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'payment_id',
        'midtrans_order_id',
        'transaction_id',
        'payment_type',
        'gross_amount',
        'status',
        'snap_token',
        'payload_json',
        'last_error',
        'cancel_requested_at',
        'paid_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'payload_json' => 'array',
        'cancel_requested_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}