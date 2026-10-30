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

        // Barang yang sedang dalam perbaikan atau tidak aktif tidak boleh dihitung sebagai stok layak pakai
        if (in_array($inventory->status, ['maintenance', 'inactive'], true)) {
            return [
                'total_stock' => 0,
                'used_stock' => 0,
                'available_stock' => 0,
                'status' => 'UNAVAILABLE',
                'status_label' => $inventory->status === 'maintenance' ? 'Dalam Perbaikan' : 'Tidak Aktif',
                'status_color' => 'danger',
            ];
        }

        $totalStock = (int) $inventory->stock;
        if ($variantId) {
            $variant = $inventory->relationLoaded('variants')
                ? $inventory->variants->firstWhere('id', $variantId)
                : $inventory->variants()->whereKey($variantId)->first();
            if ($variant) {
                $totalStock += (int) $variant->stock_modifier;
            }
        }
        $totalStock = max(0, $totalStock);

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

        // Calculate booked stock for the event date across active orders.
        // Aturan:
        // 1. Pesanan terkonfirmasi/lunas/proses selalu mengunci stok.
        // 2. Pesanan pending_payment mengunci stok maksimal 5 jam dari saat pemesanan.
        // 3. Namun jika transaksi pembayaran belum dipastikan status akhirnya (unknown / conflict),
        //    stok TIDAK boleh dilepas meskipun sudah > 5 jam.
        $usedStock = (int) OrderDetail::where('inventory_id', $inventory->id)
            ->when($variantId, function ($query, $vId) {
                return $query->where('inventory_variant_id', $vId);
            })
            ->whereHas('order', function ($orderQuery) use ($date) {
                $orderQuery->where(function ($statusQuery) {
                    $statusQuery->whereIn('status', ['paid', 'confirmed', 'preparing', 'ongoing'])
                        ->orWhere(function ($pendingQuery) {
                            $pendingQuery->whereIn('status', ['pending_payment', 'cancelled'])
                                ->where(function ($holdQuery) {
                                    $holdQuery->where(function ($activeHold) {
                                        $activeHold->where('created_at', '>=', now()->subHours(5))
                                            ->whereIn('payment_status', ['pending', 'unknown', 'conflict', 'paid']);
                                    })->orWhereIn('payment_status', ['unknown', 'conflict', 'paid']);
                                });
                        });
                })
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
            $inventory = Inventory::with('variants')->find($item['inventory_id']);
            if (!$inventory) {
                return [false, "Perlengkapan '{$item['name']}' tidak ditemukan di sistem."];
            }

            $variantId = $item['variant_id'] ?? null;
            if ($variantId && !$inventory->variants->contains('id', (int) $variantId)) {
                return [false, "Variasi untuk perlengkapan '{$item['name']}' tidak sesuai."];
            }

            $availability = $this->checkAvailability($inventory, $eventDate, $variantId);

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
