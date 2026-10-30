<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'inspected_by_user_id',
        'verified_by_user_id',
        'status',
        'inspected_at',
        'verified_at',
        'fine_chair_amount',
        'fine_status',
        'inspection_notes',
        'admin_verification_notes',
    ];

    protected $casts = [
        'inspected_at' => 'datetime',
        'verified_at' => 'datetime',
        'fine_chair_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }
}

