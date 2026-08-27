<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'variant_name',
        'attribute_type',
        'attribute_value',
        'price_modifier',
        'stock_modifier',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'stock_modifier' => 'integer',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
