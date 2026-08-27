<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'unit',
        'image',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(InventoryVariant::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    /**
     * Get available stock for a specific date and optional variant.
     */
    public function getAvailableStock(string $date, ?int $variantId = null): int
    {
        $baseStock = $this->stock;

        if ($variantId) {
            $variant = $this->variants()->find($variantId);
            if ($variant) {
                $baseStock += $variant->stock_modifier;
            }
        }

        $ordered = OrderDetail::where('inventory_id', $this->id)
            ->where('inventory_variant_id', $variantId)
            ->whereHas('order', function ($query) use ($date) {
                $query->where('status', '!=', 'cancelled')
                      ->whereHas('event', function ($q) use ($date) {
                          $q->where('event_date', $date);
                      });
            })
            ->sum('quantity');

        return max(0, $baseStock - $ordered);
    }
}
