<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Inventory;
use App\Models\InventoryVariant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Admin Dashboard landing page.
     */
    public function dashboard()
    {
        $stats = [
            'total_sales' => Order::where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_orders' => Order::count(),
            'active_orders' => Order::whereIn('status', ['confirmed', 'preparing', 'ongoing'])->count(),
            'pending_payment_count' => Order::where('status', 'pending_payment')->count(),
            'paid_count' => Order::where('status', 'paid')->count(),
            'total_inventories' => Inventory::count(),
            'active_surveys_count' => Survey::whereIn('status', ['menunggu', 'dijadwalkan', 'diproses'])->count(),
            'upcoming_events_count' => Event::whereDate('event_date', '>=', now()->toDateString())->count(),
        ];

        $recentOrders = Order::with(['user', 'event'])->orderBy('created_at', 'desc')->take(6)->get();
        $recentSurveys = Survey::with('user')->orderBy('created_at', 'desc')->take(5)->get();
        $upcomingEvents = Event::with('user')->whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date', 'asc')->take(5)->get();
        $lowStockInventories = Inventory::with('category')->where('stock', '<=', 10)->orderBy('stock', 'asc')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'recentSurveys', 'upcomingEvents', 'lowStockInventories'));
    }

    /**
     * List all orders.
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'event', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->whereDate('event_date', $request->date);
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Show single order detail.
     */
    public function showOrder(Order $order)
    {
        $order->load(['user', 'event', 'orderDetails.inventory', 'orderDetails.variant', 'payment']);
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => ['required', 'in:draft,pending_payment,paid,confirmed,preparing,ongoing,completed,cancelled'],
        ]);

        $order->update([
            'status' => $request->status,
        ]);

        return back()->with('success', "Status pesanan {$order->order_number} berhasil diperbarui menjadi " . strtoupper($request->status) . ".");
    }

    /**
     * List all operational events.
     */
    public function events(Request $request)
    {
        $query = Event::with(['user', 'order']);

        if ($request->filled('date')) {
            $query->whereDate('event_date', $request->date);
        }

        $events = $query->orderBy('event_date', 'desc')->paginate(15);

        return view('admin.events.index', compact('events'));
    }

    /**
     * List all survey requests.
     */
    public function surveys(Request $request)
    {
        $query = Survey::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $surveys = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.surveys.index', compact('surveys'));
    }

    /**
     * Manage inventories.
     */
    public function inventories(Request $request)
    {
        $query = Inventory::with(['category', 'variants']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $inventories = $query->orderBy('category_id')->paginate(15);
        $categories = Category::all();

        return view('admin.inventories.index', compact('inventories', 'categories'));
    }

    /**
     * Store new inventory item.
     */
    public function storeInventory(Request $request)
    {
        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('inventories', 'public');
        }

        Inventory::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(4),
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'unit' => $request->unit,
            'image' => $imagePath,
            'status' => 'available',
        ]);

        return back()->with('success', 'Peralatan baru berhasil ditambahkan.');
    }

    /**
     * Update existing inventory item.
     */
    public function updateInventory(Request $request, Inventory $inventory)
    {
        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'status' => ['required', 'in:available,maintenance,inactive'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $imagePath = $inventory->image;
        if ($request->hasFile('image')) {
            if ($inventory->image && Storage::disk('public')->exists($inventory->image)) {
                Storage::disk('public')->delete($inventory->image);
            }
            $imagePath = $request->file('image')->store('inventories', 'public');
        }

        $inventory->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'unit' => $request->unit,
            'status' => $request->status,
            'image' => $imagePath,
        ]);

        return back()->with('success', 'Data peralatan berhasil diperbarui.');
    }

    /**
     * Delete an inventory item.
     */
    public function deleteInventory(Inventory $inventory)
    {
        if ($inventory->image && Storage::disk('public')->exists($inventory->image)) {
            Storage::disk('public')->delete($inventory->image);
        }

        $inventory->variants()->delete();
        $inventory->delete();

        return back()->with('success', 'Peralatan dan variannya berhasil dihapus.');
    }

    /**
     * Manage inventory variants.
     */
    public function inventoryVariants(Request $request)
    {
        $query = InventoryVariant::with('inventory');

        if ($request->filled('inventory_id')) {
            $query->where('inventory_id', $request->inventory_id);
        }

        $variants = $query->paginate(20);
        $inventories = Inventory::orderBy('name')->get();

        return view('admin.inventories.variants', compact('variants', 'inventories'));
    }

    /**
     * Store a new inventory variant.
     */
    public function storeVariant(Request $request)
    {
        $request->validate([
            'inventory_id' => ['required', 'exists:inventories,id'],
            'variant_name' => ['required', 'string', 'max:255'],
            'attribute_type' => ['required', 'string', 'max:50'],
            'attribute_value' => ['required', 'string', 'max:100'],
            'price_modifier' => ['required', 'numeric'],
            'stock_modifier' => ['nullable', 'integer'],
        ]);

        InventoryVariant::create([
            'inventory_id' => $request->inventory_id,
            'variant_name' => $request->variant_name,
            'attribute_type' => $request->attribute_type,
            'attribute_value' => $request->attribute_value,
            'price_modifier' => $request->price_modifier,
            'stock_modifier' => $request->stock_modifier ?? 0,
        ]);

        return back()->with('success', 'Variasi inventaris berhasil ditambahkan.');
    }

    /**
     * Delete an inventory variant.
     */
    public function deleteVariant(InventoryVariant $variant)
    {
        $variant->delete();
        return back()->with('success', 'Variasi berhasil dihapus.');
    }

    /**
     * List all payments.
     */
    public function payments(Request $request)
    {
        $query = Payment::with(['order.user', 'order.event']);

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.payments.index', compact('payments'));
    }

    /**
     * List all registered customers.
     */
    public function customers(Request $request)
    {
        $customers = User::where('role', 'customer')
            ->withCount('orders')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * List all workers / field staff.
     */
    public function workers(Request $request)
    {
        $workers = User::whereIn('role', ['admin', 'pekerja'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.workers.index', compact('workers'));
    }

    /**
     * Store a new worker account.
     */
    public function storeWorker(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'pekerja',
            'phone' => $request->phone,
            'address' => $request->address,
            'password' => bcrypt($request->password),
        ]);

        return back()->with('success', 'Akun pekerja / tim lapangan berhasil ditambahkan.');
    }

    /**
     * Update an existing worker account.
     */
    public function updateWorker(Request $request, User $worker)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $worker->id],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $worker->update($data);

        return back()->with('success', "Data pekerja {$worker->name} berhasil diperbarui.");
    }

    /**
     * Delete a worker account.
     */
    public function deleteWorker(User $worker)
    {
        if ($worker->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $worker->delete();

        return back()->with('success', 'Akun pekerja berhasil dihapus.');
    }
}
