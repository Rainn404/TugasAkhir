<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Inventory;
use App\Models\InventoryVariant;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\Payment;
use App\Models\Survey;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Admin Dashboard landing page.
     */
    public function dashboard()
    {
        $isWorker = Auth::user()->role === 'pekerja';
        $stats = [
            'total_sales' => $isWorker ? 0 : Order::where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_orders' => Order::count(),
            'active_orders' => Order::whereIn('status', ['confirmed', 'preparing', 'ongoing'])->count(),
            'pending_payment_count' => Order::where('status', 'pending_payment')->count(),
            'paid_count' => Order::where('status', 'paid')->count(),
            'total_inventories' => Inventory::count(),
            'active_surveys_count' => $isWorker
                ? Survey::where('assigned_worker_id', Auth::id())->whereIn('status', ['dijadwalkan', 'diproses'])->count()
                : Survey::whereIn('status', ['menunggu', 'dijadwalkan', 'diproses'])->count(),
            'my_surveys_count' => Survey::where('assigned_worker_id', Auth::id())->count(),
            'upcoming_events_count' => Event::whereDate('event_date', '>=', now()->toDateString())->count(),
        ];

        $recentOrders = Order::with(['user', 'event'])->orderBy('created_at', 'desc')->take(6)->get();
        $recentSurveys = $isWorker
            ? Survey::with('user')->where('assigned_worker_id', Auth::id())->orderBy('created_at', 'desc')->take(5)->get()
            : Survey::with('user')->orderBy('created_at', 'desc')->take(5)->get();
        $upcomingEvents = Event::with('user')->whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date', 'asc')->take(5)->get();
        $lowStockInventories = $isWorker ? collect() : Inventory::with('category')->where('stock', '<=', 10)->orderBy('stock', 'asc')->take(5)->get();

        $myAssignments = $isWorker
            ? OrderAssignment::with(['order.event', 'order.user'])
                ->where('user_id', Auth::id())
                ->orderByRaw("CASE WHEN status = 'pending' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
                ->orderBy('scheduled_at', 'asc')
                ->take(10)
                ->get()
            : collect();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'recentSurveys', 'upcomingEvents', 'lowStockInventories', 'isWorker', 'myAssignments'));
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
        $order->load([
            'user',
            'event',
            'orderDetails.inventory.category',
            'orderDetails.variant',
            'payment.attempts',
            'assignments.worker',
            'returnInspection.items.inventory.category',
            'returnInspection.inspector',
            'returnInspection.verifier',
        ]);

        $workers = User::whereIn('role', ['admin', 'pekerja'])->orderBy('name')->get();
        $taskTypes = OrderAssignment::getTaskTypeLabels();
        $systemSettings = [
            'chair_fine_damaged' => (float) SystemSetting::get('chair_fine_damaged', 25000),
            'chair_fine_lost' => (float) SystemSetting::get('chair_fine_lost', 50000),
        ];

        return view('admin.orders.show', compact('order', 'workers', 'taskTypes', 'systemSettings'));
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        if ($order->status === 'payment_conflict') {
            return back()->with('error', 'Pesanan terkunci untuk rekonsiliasi. Verifikasi seluruh attempt pembayaran terlebih dahulu.');
        }

        $request->validate([
            'status' => ['required', 'in:draft,pending_payment,paid,confirmed,preparing,ongoing,completed,cancelled'],
        ]);

        // Guard: Pesanan tidak dapat diselesaikan sebelum pemeriksaan barang kembali diverifikasi oleh admin
        if ($request->status === 'completed') {
            $order->load('returnInspection');
            $returnInspection = $order->returnInspection;
            if (!$returnInspection || $returnInspection->status !== 'verified') {
                return back()->with('error', 'Pesanan tidak dapat diselesaikan sebelum pemeriksaan barang kembali (return inspection) selesai dan diverifikasi oleh admin.');
            }

            if ($returnInspection->fine_chair_amount > 0 && !in_array($returnInspection->fine_status, ['paid', 'waived'], true)) {
                return back()->with('error', 'Pesanan memiliki denda kursi sebesar Rp ' . number_format($returnInspection->fine_chair_amount, 0, ',', '.') . ' yang belum diselesaikan (status: ' . $returnInspection->fine_status . ').');
            }
        }

        $order->update([
            'status' => $request->status,
        ]);

        return back()->with('success', "Status pesanan {$order->order_number} berhasil diperbarui menjadi " . strtoupper($request->status) . ".");
    }

    /**
     * Assign a worker to an order operational task (Admin only).
     */
    public function assignOrderTask(Request $request, Order $order)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'task_type' => ['required', 'in:persiapan,pengiriman,pemasangan,pemeriksaan_kembali'],
            'scheduled_at' => ['nullable', 'date'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignment = OrderAssignment::updateOrCreate(
            [
                'order_id' => $order->id,
                'task_type' => $request->task_type,
            ],
            [
                'user_id' => $request->user_id,
                'scheduled_at' => $request->scheduled_at,
                'instructions' => $request->instructions,
                'status' => 'pending',
            ]
        );

        $worker = User::find($request->user_id);
        $taskLabel = OrderAssignment::getTaskTypeLabels()[$request->task_type] ?? $request->task_type;

        return back()->with('success', "Tugas '{$taskLabel}' berhasil ditugaskan kepada {$worker->name}.");
    }

    /**
     * Update progress of an operational task (Worker or Admin).
     */
    public function updateTaskStatus(Request $request, OrderAssignment $assignment)
    {
        if (Auth::user()->role === 'pekerja' && $assignment->user_id !== Auth::id()) {
            abort(403, 'Akses tidak diizinkan. Anda hanya dapat memperbarui tugas yang ditugaskan kepada Anda.');
        }

        $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
            'worker_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $completedAt = $request->status === 'completed' ? ($assignment->completed_at ?? now()) : null;

        $assignment->update([
            'status' => $request->status,
            'worker_notes' => $request->worker_notes,
            'completed_at' => $completedAt,
        ]);

        return back()->with('success', 'Status tugas operasional berhasil diperbarui.');
    }

    /**
     * Submit return inspection and report actual item conditions & chair fines.
     */
    public function submitReturnInspection(Request $request, Order $order)
    {
        if (Auth::user()->role === 'pekerja') {
            $hasInspectionTask = $order->assignments()
                ->where('user_id', Auth::id())
                ->where('task_type', 'pemeriksaan_kembali')
                ->exists();
            if (!$hasInspectionTask) {
                abort(403, 'Akses tidak diizinkan. Anda tidak ditugaskan untuk pemeriksaan pengembalian pesanan ini.');
            }
        }

        $order->load(['orderDetails.inventory.category']);

        $request->validate([
            'inspection_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_detail_id' => ['required', 'exists:order_details,id'],
            'items.*.quantity_good' => ['required', 'integer', 'min:0'],
            'items.*.quantity_damaged' => ['required', 'integer', 'min:0'],
            'items.*.quantity_lost' => ['required', 'integer', 'min:0'],
            'items.*.quantity_repair' => ['required', 'integer', 'min:0'],
            'items.*.condition_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $damagedFineRate = (float) SystemSetting::get('chair_fine_damaged', 25000);
        $lostFineRate = (float) SystemSetting::get('chair_fine_lost', 50000);

        $totalChairFine = 0;

        DB::beginTransaction();
        try {
            $orderReturn = OrderReturn::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'inspected_by_user_id' => Auth::id(),
                    'status' => 'submitted',
                    'inspected_at' => now(),
                    'inspection_notes' => $request->inspection_notes,
                ]
            );

            $orderReturn->items()->delete();

            foreach ($request->items as $itemData) {
                $detail = $order->orderDetails->firstWhere('id', $itemData['order_detail_id']);
                if (!$detail) continue;

                $totalReturned = $itemData['quantity_good'] + $itemData['quantity_damaged'] + $itemData['quantity_lost'] + $itemData['quantity_repair'];
                if ($totalReturned !== $detail->quantity) {
                    throw new \Exception("Jumlah kondisi barang untuk {$detail->inventory->name} (total {$totalReturned}) harus sama persis dengan jumlah sewa ({$detail->quantity}).");
                }

                $isChair = strtolower($detail->inventory->category?->slug ?? '') === 'kursi'
                    || str_contains(strtolower($detail->inventory->name ?? ''), 'kursi');

                // Denda HANYA berlaku untuk kategori Kursi
                $itemFine = 0;
                if ($isChair) {
                    $itemFine = ($itemData['quantity_damaged'] * $damagedFineRate) + ($itemData['quantity_lost'] * $lostFineRate);
                    $totalChairFine += $itemFine;
                }

                OrderReturnItem::create([
                    'order_return_id' => $orderReturn->id,
                    'order_detail_id' => $detail->id,
                    'inventory_id' => $detail->inventory_id,
                    'quantity_rented' => $detail->quantity,
                    'quantity_good' => $itemData['quantity_good'],
                    'quantity_damaged' => $itemData['quantity_damaged'],
                    'quantity_lost' => $itemData['quantity_lost'],
                    'quantity_repair' => $itemData['quantity_repair'],
                    'fine_per_item' => $isChair ? $damagedFineRate : 0,
                    'subtotal_fine' => $itemFine,
                    'condition_notes' => $itemData['condition_notes'] ?? null,
                ]);
            }

            $orderReturn->update([
                'fine_chair_amount' => $totalChairFine,
                'fine_status' => $totalChairFine > 0 ? 'unpaid' : 'waived',
            ]);

            DB::commit();
            return back()->with('success', 'Laporan pemeriksaan pengembalian berhasil disimpan. Total denda kursi: Rp ' . number_format($totalChairFine, 0, ',', '.') . '. Menunggu verifikasi admin.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Admin verifies return inspection and adjusts inventory stock.
     */
    public function verifyReturnInspection(Request $request, Order $order)
    {
        $return = $order->returnInspection;
        if (!$return) {
            return back()->with('error', 'Belum ada pemeriksaan pengembalian untuk diverifikasi.');
        }

        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'fine_status' => ['nullable', 'in:unpaid,paid,waived'],
            'admin_verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->action === 'reject') {
            $return->update([
                'status' => 'draft',
                'admin_verification_notes' => $request->admin_verification_notes,
            ]);
            return back()->with('warning', 'Pemeriksaan pengembalian ditolak dan dikembalikan ke status draft untuk perbaikan.');
        }

        DB::beginTransaction();
        try {
            // Apply inventory stock adjustments
            foreach ($return->items as $returnItem) {
                $inventory = Inventory::find($returnItem->inventory_id);
                if (!$inventory) continue;

                // Barang hilang mengurangi stok inventaris permanen
                if ($returnItem->quantity_lost > 0) {
                    $inventory->stock = max(0, $inventory->stock - $returnItem->quantity_lost);
                    $inventory->save();
                }
            }

            $fineStatus = $request->fine_status ?? ($return->fine_chair_amount > 0 ? $return->fine_status : 'waived');

            $return->update([
                'status' => 'verified',
                'verified_by_user_id' => Auth::id(),
                'verified_at' => now(),
                'fine_status' => $fineStatus,
                'admin_verification_notes' => $request->admin_verification_notes,
            ]);

            DB::commit();
            return back()->with('success', 'Pemeriksaan pengembalian berhasil diverifikasi oleh admin. Stok inventaris telah disesuaikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memverifikasi: ' . $e->getMessage());
        }
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
        $query = Survey::with(['user', 'assignedWorker']);

        // Pekerja hanya dapat melihat survei yang ditugaskan kepada dirinya
        if (Auth::user()->role === 'pekerja') {
            $query->where('assigned_worker_id', Auth::id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $surveys = $query->orderBy('created_at', 'desc')->paginate(15);

        // Daftar pekerja yang bisa ditugaskan (role admin atau pekerja)
        $workers = User::whereIn('role', ['admin', 'pekerja'])->orderBy('name')->get();
        $allInventories = Inventory::where('status', 'available')->orderBy('name')->get();

        return view('admin.surveys.index', compact('surveys', 'workers', 'allInventories'));
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
