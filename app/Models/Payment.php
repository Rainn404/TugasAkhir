<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PaymentAttempt;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'payment_type',
        'gross_amount',
        'payment_status',
        'snap_token',
        'payload_json',
        'paid_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'payload_json' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function latestAttempt(): HasOne
    {
        return $this->hasOne(PaymentAttempt::class)->latestOfMany();
    }
}
