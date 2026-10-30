<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryVariant;
use App\Services\InventoryAvailabilityService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private InventoryAvailabilityService $availabilityService)
    {
    }

    /**
     * Display the cart page.
     */
    public function index()
    {
        $cart = session()->get('cart', []);
        $cartDate = session()->get('cart_date');
        
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['subtotal'];
        }

        return view('cart.index', compact('cart', 'cartDate', 'total'));
    }

    /**
     * Add an item to the cart with date-based stock validation.
     */
    public function add(Request $request)
    {
        $request->validate([
            'inventory_id' => ['required', 'exists:inventories,id'],
            'variant_id' => ['nullable', 'exists:inventory_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'event_date.required' => 'Tanggal acara harus ditentukan terlebih dahulu.',
            'event_date.after_or_equal' => 'Tanggal acara tidak boleh di masa lalu.',
        ]);

        $inventoryId = $request->inventory_id;
        $variantId = $request->variant_id;
        $quantity = $request->quantity;
        $eventDate = $request->event_date;

        $inventory = Inventory::findOrFail($inventoryId);
        $variant = $variantId
            ? InventoryVariant::where('inventory_id', $inventoryId)->findOrFail($variantId)
            : null;

        // Generate cart item key
        $key = $inventoryId . '_' . ($variantId ?? '0');

        $cart = session()->get('cart', []);
        $currentCartDate = session()->get('cart_date');

        // If date changes, validate/clear cart or warning
        if ($currentCartDate && $currentCartDate !== $eventDate) {
            // Option: Reset cart on date change to keep all items on the same date
            $cart = [];
            session()->put('cart', []);
            session()->put('cart_date', $eventDate);
            $currentCartDate = $eventDate;
        } elseif (!$currentCartDate) {
            session()->put('cart_date', $eventDate);
            $currentCartDate = $eventDate;
        }

        // Calculate existing quantity in cart for this item
        $existingQty = isset($cart[$key]) ? $cart[$key]['quantity'] : 0;
        $totalRequestedQty = $existingQty + $quantity;

        // Date-based stock check
        $availableStock = $this->availabilityService
            ->checkAvailability($inventory, $eventDate, $variantId)['available_stock'];

        if ($availableStock < $totalRequestedQty) {
            return back()->with('error', "Stok untuk {$inventory->name}" . ($variant ? " ({$variant->variant_name})" : "") . " pada tanggal " . date('d-m-Y', strtotime($eventDate)) . " tidak mencukupi. Tersisa: {$availableStock} {$inventory->unit}.");
        }

        // Calculate unit price and subtotal
        $unitPrice = $inventory->price + ($variant ? $variant->price_modifier : 0);
        $subtotal = $unitPrice * $totalRequestedQty;

        // Prepare variant options breakdown
        $options = [];
        if ($variant) {
            $options[$variant->attribute_type] = $variant->attribute_value;
        }

        // Save to cart
        $cart[$key] = [
            'inventory_id' => $inventoryId,
            'variant_id' => $variantId,
            'name' => $inventory->name,
            'variant_name' => $variant ? $variant->variant_name : null,
            'quantity' => $totalRequestedQty,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'unit' => $inventory->unit,
            'options' => $options,
        ];

        session()->put('cart', $cart);
        session()->forget('checkout_key');

        return redirect()->route('cart.index')->with('success', 'Item berhasil ditambahkan ke keranjang sewa.');
    }

    /**
     * Update quantity of an item in the cart.
     */
    public function update(Request $request, $key)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = session()->get('cart', []);
        $cartDate = session()->get('cart_date');

        if (!isset($cart[$key]) || !$cartDate) {
            return redirect()->route('cart.index')->with('error', 'Item tidak ditemukan.');
        }

        $item = $cart[$key];
        $inventory = Inventory::findOrFail($item['inventory_id']);
        $newQty = $request->quantity;

        // Validate stock availability
        $availableStock = $this->availabilityService
            ->checkAvailability($inventory, $cartDate, $item['variant_id'])['available_stock'];

        if ($availableStock < $newQty) {
            return redirect()->route('cart.index')->with('error', "Stok untuk {$item['name']} pada tanggal tersebut tidak mencukupi. Tersisa: {$availableStock} {$item['unit']}.");
        }

        // Update quantity and subtotal
        $cart[$key]['quantity'] = $newQty;
        $cart[$key]['subtotal'] = $item['unit_price'] * $newQty;

        session()->put('cart', $cart);
        session()->forget('checkout_key');

        return redirect()->route('cart.index')->with('success', 'Keranjang berhasil diperbarui.');
    }

    /**
     * Remove an item from the cart.
     */
    public function remove($key)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('cart', $cart);
            session()->forget('checkout_key');
        }

        if (empty($cart)) {
            session()->forget(['cart', 'cart_date']);
        }

        return redirect()->route('cart.index')->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    /**
     * Update cart date and re-validate all items.
     */
    public function updateDate(Request $request)
    {
        $request->validate([
            'event_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $newDate = $request->event_date;
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            session()->put('cart_date', $newDate);
            return redirect()->route('cart.index')->with('success', 'Tanggal acara berhasil diatur.');
        }

        // Validate all items against the new date
        $warnings = [];
        foreach ($cart as $key => $item) {
            $inventory = Inventory::findOrFail($item['inventory_id']);
            $available = $this->availabilityService
                ->checkAvailability($inventory, $newDate, $item['variant_id'])['available_stock'];

            if ($available < $item['quantity']) {
                if ($available > 0) {
                    $cart[$key]['quantity'] = $available;
                    $cart[$key]['subtotal'] = $item['unit_price'] * $available;
                    $warnings[] = "Kuantitas {$item['name']} dikurangi menjadi {$available} {$item['unit']} karena keterbatasan stok pada tanggal baru.";
                } else {
                    unset($cart[$key]);
                    $warnings[] = "{$item['name']} dihapus dari keranjang karena stok kosong pada tanggal baru.";
                }
            }
        }

        session()->put('cart', $cart);
        session()->put('cart_date', $newDate);
        session()->forget('checkout_key');

        if (empty($cart)) {
            session()->forget('cart_date');
        }

        if (!empty($warnings)) {
            return redirect()->route('cart.index')->with('warning', 'Beberapa item disesuaikan: ' . implode(' ', $warnings));
        }

        return redirect()->route('cart.index')->with('success', 'Tanggal acara berhasil diubah dan semua stok divalidasi.');
    }
}
