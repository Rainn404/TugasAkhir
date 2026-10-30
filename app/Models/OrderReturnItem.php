<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_return_id',
        'order_detail_id',
        'inventory_id',
        'quantity_rented',
        'quantity_good',
        'quantity_damaged',
        'quantity_lost',
        'quantity_repair',
        'fine_per_item',
        'subtotal_fine',
        'condition_notes',
    ];

    protected $casts = [
        'fine_per_item' => 'decimal:2',
        'subtotal_fine' => 'decimal:2',
    ];

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}

