<?php

namespace App\Http\Controllers;

use App\Services\InventoryAvailabilityService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    protected OrderService $orderService;
    protected InventoryAvailabilityService $availabilityService;

    public function __construct(
        OrderService $orderService,
        InventoryAvailabilityService $availabilityService
    ) {
        $this->orderService = $orderService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Show the checkout form with stepper.
     */
    public function checkout()
    {
        $cart = session()->get('cart', []);
        $cartDate = session()->get('cart_date');

        if (empty($cart) || !$cartDate) {
            return redirect()->route('cart.index')->with('error', 'Keranjang sewa Anda masih kosong atau belum memilih tanggal.');
        }

        // Validate availability
        $validation = $this->availabilityService->validateCartAvailability($cart, $cartDate);
        if (!$validation[0]) {
            return redirect()->route('cart.index')->with('error', $validation[1]);
        }

        $checkoutKey = session()->get('checkout_key');
        if (!is_string($checkoutKey) || !Str::isUuid($checkoutKey)) {
            $checkoutKey = (string) Str::uuid();
            session()->put('checkout_key', $checkoutKey);
        }

        $total = 0;
        foreach ($cart as $item) {
            $total += $item['subtotal'];
        }

        return view('cart.checkout', compact('cart', 'cartDate', 'total', 'checkoutKey'));
    }

    /**
     * Process checkout and create Event, Order, OrderDetails, and Payment via OrderService.
     */
    public function store(Request $request)
    {
        $cart = session()->get('cart', []);
        $cartDate = session()->get('cart_date');
        $checkoutKey = session()->get('checkout_key');

        if (empty($cart) || !$cartDate) {
            return redirect()->route('cart.index')->with('error', 'Keranjang sewa Anda masih kosong.');
        }

        if (!is_string($checkoutKey) || !Str::isUuid($checkoutKey)) {
            $checkoutKey = (string) Str::uuid();
            session()->put('checkout_key', $checkoutKey);
        }

        $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'address' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $order = $this->orderService->createOrder(
                $cart,
                $cartDate,
                $request->only(['event_name', 'event_type', 'start_time', 'end_time', 'address', 'latitude', 'longitude', 'notes']),
                Auth::id(),
                $checkoutKey
            );

            // Clear session cart after successful order creation
            session()->forget(['cart', 'cart_date', 'checkout_key']);

            return redirect()->route('dashboard.order', $order->order_number)
                ->with('success', 'Pesanan berhasil dibuat! Silakan selesaikan pembayaran.');

        } catch (\Exception $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }
    }
}
