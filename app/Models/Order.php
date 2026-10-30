<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'checkout_key',
        'user_id',
        'event_id',
        'rental_amount',
        'delivery_fee',
        'survey_fee',
        'total_amount',
        'status',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'rental_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'survey_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OrderAssignment::class);
    }

    public function returnInspection(): HasOne
    {
        return $this->hasOne(OrderReturn::class);
    }
}
