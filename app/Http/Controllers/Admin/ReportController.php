<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Orders report.
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'event', 'payment', 'orderDetails.inventory']);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();
        $totalRevenue = $orders->where('status', '!=', 'cancelled')->sum('total_amount');

        return view('admin.reports.orders', compact('orders', 'totalRevenue'));
    }

    /**
     * Inventory stock & usage report.
     */
    public function inventories(Request $request)
    {
        $inventories = Inventory::with(['category', 'variants', 'orderDetails' => function ($q) {
            $q->whereHas('order', function ($oq) {
                $oq->whereIn('status', ['paid', 'confirmed', 'preparing', 'ongoing']);
            });
        }])->get();

        return view('admin.reports.inventories', compact('inventories'));
    }

    /**
     * Payments report.
     */
    public function payments(Request $request)
    {
        $query = Payment::with(['order.user', 'order.event']);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();
        $totalPaid = $payments->where('payment_status', 'paid')->sum('gross_amount');

        return view('admin.reports.payments', compact('payments', 'totalPaid'));
    }
}
