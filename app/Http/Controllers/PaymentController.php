<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle webhook callback notification from Midtrans.
     */
    public function handleNotification(Request $request)
    {
        $result = $this->paymentService->processWebhookNotification($request->all());

        return response()->json([
            'message' => $result[1]
        ], $result[2]);
    }

    public function retrySnapToken(string $orderNumber, MidtransService $midtransService)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['payment', 'user', 'orderDetails.inventory', 'orderDetails.variant'])
            ->firstOrFail();

        if (Auth::user()->role !== 'admin' && $order->user_id !== Auth::id()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($order->status !== 'pending_payment' || $order->payment_status !== 'pending') {
            return back()->with('error', 'Pesanan ini tidak lagi menunggu pembayaran.');
        }

        $payment = $order->payment ?? Payment::create([
            'order_id' => $order->id,
            'gross_amount' => $order->total_amount,
            'payment_status' => 'pending',
        ]);

        if ($payment->snap_token) {
            return redirect()->route('dashboard.order', $orderNumber)
                ->with('success', 'Token pembayaran tersedia. Silakan klik Bayar Sekarang.');
        }

        $snapResult = $midtransService->createSnapToken($order);

        if (!$snapResult || empty($snapResult['token'])) {
            return redirect()->route('dashboard.order', $orderNumber)
                ->with('error', 'Token pembayaran belum berhasil dibuat. Periksa konfigurasi key sandbox Midtrans atau coba lagi nanti.');
        }

        $payment->update([
            'snap_token' => $snapResult['token'],
            'payload_json' => $snapResult,
        ]);

        return redirect()->route('dashboard.order', $orderNumber)
            ->with('success', 'Pembayaran siap. Klik Bayar Sekarang untuk memilih metode pembayaran.');
    }
}
