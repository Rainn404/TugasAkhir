<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\OrderDetail;
use Carbon\Carbon;

class InventoryAvailabilityService
{
    /**
     * Get availability summary for an inventory item on a given date.
     *
     * @param int|Inventory $inventory
     * @param string|null $date
     * @param int|null $variantId
     * @return array
     */
    public function checkAvailability($inventory, ?string $date = null, ?int $variantId = null): array
    {
        if (!$inventory instanceof Inventory) {
            $inventory = Inventory::with('variants')->findOrFail($inventory);
        }

        $totalStock = (int) $inventory->stock;

        // If no date is given, default to general availability
        if (!$date) {
            $status = $totalStock > 0 ? 'AVAILABLE' : 'UNAVAILABLE';
            $statusLabel = $totalStock > 0 ? 'Tersedia' : 'Habis';
            $statusColor = $totalStock > 0 ? 'success' : 'danger';

            return [
                'total_stock' => $totalStock,
                'used_stock' => 0,
                'available_stock' => $totalStock,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
            ];
        }

        // Calculate booked stock for the event date across active orders
        $usedStock = (int) OrderDetail::where('inventory_id', $inventory->id)
            ->when($variantId, function ($query, $vId) {
                return $query->where('inventory_variant_id', $vId);
            })
            ->whereHas('order', function ($orderQuery) use ($date) {
                $orderQuery->whereIn('status', ['paid', 'confirmed', 'preparing', 'ongoing', 'pending_payment'])
                    ->whereHas('event', function ($eventQuery) use ($date) {
                        $eventQuery->whereDate('event_date', Carbon::parse($date)->toDateString());
                    });
            })
            ->sum('quantity');

        $availableStock = max(0, $totalStock - $usedStock);

        if ($availableStock === 0) {
            $status = 'UNAVAILABLE';
            $statusLabel = 'Tidak Tersedia';
            $statusColor = 'danger'; // Merah
        } elseif ($availableStock < $totalStock && ($availableStock <= ceil($totalStock * 0.4) || $availableStock <= 2)) {
            $status = 'LIMITED';
            $statusLabel = 'Terbatas';
            $statusColor = 'warning'; // Kuning
        } else {
            $status = 'AVAILABLE';
            $statusLabel = 'Tersedia';
            $statusColor = 'success'; // Hijau
        }

        return [
            'total_stock' => $totalStock,
            'used_stock' => $usedStock,
            'available_stock' => $availableStock,
            'status' => $status,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
        ];
    }

    /**
     * Validate if an entire cart has sufficient stock for a given date.
     *
     * @param array $cartItems
     * @param string $eventDate
     * @return array [bool $isValid, string|null $errorMessage]
     */
    public function validateCartAvailability(array $cartItems, string $eventDate): array
    {
        foreach ($cartItems as $item) {
            $inventory = Inventory::find($item['inventory_id']);
            if (!$inventory) {
                return [false, "Perlengkapan '{$item['name']}' tidak ditemukan di sistem."];
            }

            $availability = $this->checkAvailability($inventory, $eventDate, $item['variant_id'] ?? null);

            if ($availability['available_stock'] < $item['quantity']) {
                $unitName = $inventory->unit ?? 'unit';
                $formattedDate = Carbon::parse($eventDate)->translatedFormat('d F Y');
                return [
                    false,
                    "Stok {$item['name']} tidak mencukupi pada tanggal {$formattedDate}. Stok tersedia: {$availability['available_stock']} {$unitName} (Dibutuhkan: {$item['quantity']} {$unitName})."
                ];
            }
        }

        return [true, null];
    }
}
