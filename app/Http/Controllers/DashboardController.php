<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    /**
     * Customer Dashboard overview.
     */
    public function index()
    {
        $userId = Auth::id();

        $stats = [
            'active_orders' => Order::where('user_id', $userId)->whereIn('status', ['paid', 'confirmed', 'preparing', 'ongoing'])->count(),
            'pending_payments' => Order::where('user_id', $userId)->where('status', 'pending_payment')->count(),
            'upcoming_events' => Event::where('user_id', $userId)->whereDate('event_date', '>=', now()->toDateString())->count(),
            'surveys_count' => Survey::where('user_id', $userId)->count(),
        ];

        $recentOrders = Order::where('user_id', $userId)
            ->with(['event', 'payment'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentSurveys = Survey::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard.index', compact('stats', 'recentOrders', 'recentSurveys'));
    }

    /**
     * Show single order detail for invoice/payment.
     */
    public function showOrder($order_number)
    {
        $order = Order::where('order_number', $order_number)
            ->with(['event', 'orderDetails.inventory', 'orderDetails.variant', 'payment', 'user'])
            ->firstOrFail();

        // If not admin and not the order owner, abort
        if (Auth::user()->role !== 'admin' && $order->user_id !== Auth::id()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        return view('dashboard.order', compact('order'));
    }

    /**
     * Customer's events list.
     */
    public function events()
    {
        $events = Event::where('user_id', Auth::id())
            ->with('order')
            ->orderBy('event_date', 'desc')
            ->paginate(10);

        return view('dashboard.events', compact('events'));
    }

    /**
     * Customer's orders list.
     */
    public function orders(Request $request)
    {
        $query = Order::where('user_id', Auth::id())
            ->with(['event', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('dashboard.orders', compact('orders'));
    }

    /**
     * Customer's survey requests.
     */
    public function surveys()
    {
        $surveys = Survey::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('dashboard.surveys', compact('surveys'));
    }

    /**
     * Customer profile page.
     */
    public function profile()
    {
        $user = Auth::user();
        return view('dashboard.profile', compact('user'));
    }

    /**
     * Update customer profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $userData = [
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
