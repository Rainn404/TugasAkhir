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
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Admin Dashboard landing page.
     */
    public function dashboard()
    {
        if (Auth::user()->role === 'pekerja') {
            return redirect()->route('admin.worker.dashboard');
        }

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
        $recentSurveys = Survey::with(['user', 'assignedWorker'])->orderBy('created_at', 'desc')->take(5)->get();
        $upcomingEvents = Event::with('user')->whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date', 'asc')->take(5)->get();
        $lowStockInventories = Inventory::with('category')->where('stock', '<=', 10)->orderBy('stock', 'asc')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'recentSurveys', 'upcomingEvents', 'lowStockInventories'));
    }

    /**
     * Worker / Petugas Lapangan Dashboard landing page.
     */
    public function workerDashboard()
    {
        $workerId = Auth::id();

        // Tugas operasional yang ditugaskan kepada petugas ini
        $myAssignments = OrderAssignment::with(['order.event', 'order.user'])
            ->where('user_id', $workerId)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
            ->orderBy('scheduled_at', 'asc')
            ->get();

        // Survei lokasi yang ditugaskan kepada petugas ini
        $mySurveys = Survey::with('user')
            ->where('assigned_worker_id', $workerId)
            ->orderByRaw("CASE WHEN status IN ('dijadwalkan', 'diproses') THEN 1 ELSE 2 END")
            ->orderBy('survey_date', 'asc')
            ->get();

        // Ringkasan metrik tugas pekerja
        $stats = [
            'pending_tasks' => $myAssignments->where('status', 'pending')->count(),
            'in_progress_tasks' => $myAssignments->where('status', 'in_progress')->count(),
            'completed_tasks' => $myAssignments->where('status', 'completed')->count(),
            'active_surveys' => $mySurveys->whereIn('status', ['dijadwalkan', 'diproses'])->count(),
            'today_tasks' => $myAssignments->filter(function ($a) {
                return $a->scheduled_at && \Carbon\Carbon::parse($a->scheduled_at)->isToday();
            })->count(),
        ];

        // Kegiatan acara terdekat yang melibatkan tugas pekerja ini
        $myEvents = Event::with('user')
            ->whereHas('order.assignments', function ($q) use ($workerId) {
                $q->where('user_id', $workerId);
            })
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date', 'asc')
            ->take(5)
            ->get();

        return view('admin.worker.dashboard', compact('myAssignments', 'mySurveys', 'stats', 'myEvents'));
    }

    /**
     * List all orders (scoped to assigned orders for workers).
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'event', 'payment']);

        if (Auth::user()->role === 'pekerja') {
            $query->whereHas('assignments', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->whereDate('event_date', $request->date);
            });
        }

        if ($request->filled('task')) {
            $query->whereHas('assignments', function ($q) use ($request) {
                $q->where('task_type', $request->task);
                if (Auth::user()->role === 'pekerja') {
                    $q->where('user_id', Auth::id());
                }
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Show single order detail (guarded for workers).
     */
    public function showOrder(Order $order)
    {
        if (Auth::user()->role === 'pekerja') {
            $isAssigned = $order->assignments()->where('user_id', Auth::id())->exists();
            if (!$isAssigned) {
                abort(403, 'Akses tidak diizinkan. Anda hanya dapat melihat detail pesanan yang ditugaskan kepada Anda.');
            }
        }

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
     * Update order status with strict role permissions and lifecycle state transitions.
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        if ($order->status === 'payment_conflict') {
            return back()->with('error', 'Pesanan terkunci untuk rekonsiliasi. Verifikasi seluruh attempt pembayaran terlebih dahulu.');
        }

        $request->validate([
            'status' => ['required', 'in:draft,pending_payment,paid,confirmed,preparing,ongoing,completed,cancelled'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $targetStatus = $request->status;
        $currentStatus = $order->status;

        // 1. Guard jika tidak ada perubahan status
        if ($targetStatus === $currentStatus) {
            return back()->with('info', "Status pesanan tidak berubah (" . strtoupper(str_replace('_', ' ', $currentStatus)) . ").");
        }

        // 2. Guard status terminal
        if (in_array($currentStatus, ['completed', 'cancelled'], true)) {
            return back()->with('error', "Pesanan berstatus " . strtoupper($currentStatus) . " sudah bersifat final dan tidak dapat diubah lagi.");
        }

        // 3. Guard wewenang Petugas Lapangan (Pekerja)
        if ($user->role === 'pekerja') {
            $isAssigned = $order->assignments()->where('user_id', $user->id)->exists();
            if (!$isAssigned) {
                return back()->with('error', 'Akses ditolak. Anda tidak memiliki penugasan operasional pada pesanan ini.');
            }

            $allowedForWorker = [
                'confirmed' => ['preparing'],
                'preparing' => ['ongoing'],
            ];

            if (!isset($allowedForWorker[$currentStatus]) || !in_array($targetStatus, $allowedForWorker[$currentStatus], true)) {
                return back()->with('error', 'Petugas lapangan hanya berwenang memperbarui tahapan operasional pesanan (dari Dikonfirmasi ke Disiapkan, atau Disiapkan ke Sedang Digunakan). Status pembayaran dan pembatalan hanya dapat dikelola oleh Administrator.');
            }
        }

        // 4. Guard matriks transisi status yang valid
        $allowedTransitions = [
            'pending_payment' => ['paid', 'cancelled'],
            'paid' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ongoing', 'cancelled'],
            'ongoing' => ['completed', 'cancelled'],
        ];

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($targetStatus, $allowedTransitions[$currentStatus], true)) {
            return back()->with('error', "Transisi status dari " . strtoupper(str_replace('_', ' ', $currentStatus)) . " ke " . strtoupper(str_replace('_', ' ', $targetStatus)) . " tidak diizinkan dalam alur operasional.");
        }

        // 5. Verifikasi khusus status 'paid' secara manual oleh Admin
        if ($targetStatus === 'paid') {
            if ($user->role !== 'admin') {
                return back()->with('error', 'Hanya Administrator yang dapat mengonfirmasi pembayaran pesanan secara manual.');
            }

            $payment = $order->payment ?? Payment::create([
                'order_id' => $order->id,
                'gross_amount' => $order->total_amount,
                'payment_status' => 'pending',
            ]);

            $payment->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'payment_type' => $payment->payment_type ?? 'manual_settlement',
                'payload_json' => array_merge($payment->payload_json ?? [], [
                    'manual_verification' => [
                        'admin_id' => $user->id,
                        'admin_name' => $user->name,
                        'verified_at' => now()->toIso8601String(),
                        'notes' => $request->admin_notes ?? 'Dikonfirmasi lunas oleh Admin.',
                    ]
                ]),
            ]);

            $order->update([
                'status' => 'paid',
                'payment_status' => 'paid',
            ]);

            return back()->with('success', "Status pesanan {$order->order_number} berhasil dikonfirmasi LUNAS oleh Administrator.");
        }

        // 6. Guard khusus status 'completed': pemeriksaan barang kembali wajib diverifikasi
        if ($targetStatus === 'completed') {
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
            'status' => $targetStatus,
        ]);

        return back()->with('success', "Status pesanan {$order->order_number} berhasil diperbarui menjadi " . strtoupper(str_replace('_', ' ', $targetStatus)) . ".");
    }

    /**
     * Assign a worker to an order operational task (Admin only).
     */
    public function assignOrderTask(Request $request, Order $order)
    {
        if (in_array($order->status, ['cancelled', 'completed'], true)) {
            return back()->with('error', "Tugas operasional tidak dapat ditambahkan pada pesanan yang berstatus " . strtoupper($order->status) . ".");
        }

        $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['pekerja', 'admin']);
                }),
            ],
            'task_type' => ['required', 'in:persiapan,pengiriman,pemasangan,pemeriksaan_kembali'],
            'scheduled_at' => ['nullable', 'date'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ], [
            'user_id.exists' => 'Akun yang dipilih tidak valid atau bukan akun petugas/admin.',
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
        if (in_array($order->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'Pemeriksaan pengembalian tidak dapat diubah karena pesanan sudah berstatus ' . strtoupper($order->status) . '.');
        }

        if ($order->returnInspection && $order->returnInspection->status === 'verified') {
            return back()->with('error', 'Pemeriksaan pengembalian untuk pesanan ini sudah diverifikasi secara resmi oleh Administrator dan tidak dapat diubah lagi.');
        }

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

        if (Auth::user()->role === 'pekerja') {
            $query->whereHas('order.assignments', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

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
            'role' => ['nullable', 'in:admin,pekerja'],
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

        if ($request->filled('role')) {
            $data['role'] = $request->role;
        }

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

    /**
     * Display page for managing homepage and portfolio media images.
     */
    public function content()
    {
        $media = [
            'hero' => [
                'key' => 'homepage_hero_image',
                'name' => 'Foto Banner Hero Beranda',
                'desc' => 'Foto utama beranda yang tampil di samping teks pembuka.',
                'image' => SystemSetting::get('homepage_hero_image'),
                'url' => SystemSetting::getImageUrl('homepage_hero_image', 'assets/images/hero-tent.jpg'),
                'alt' => SystemSetting::get('homepage_hero_alt', 'Tenda Pesta Arsy Tenda Pelaihari'),
                'default' => 'assets/images/hero-tent.jpg',
            ],
            'portfolio' => [
                1 => [
                    'slot' => 1,
                    'key' => 'portfolio_image_1',
                    'alt_key' => 'portfolio_alt_1',
                    'name' => 'Slot 1: Foto Utama (Tenda Resepsi)',
                    'desc' => 'Foto besar utama di sisi kiri atas kolase portofolio.',
                    'image' => SystemSetting::get('portfolio_image_1'),
                    'url' => SystemSetting::getImageUrl('portfolio_image_1', 'assets/images/references/marquee.jpg'),
                    'alt' => SystemSetting::get('portfolio_alt_1', 'Pemasangan Tenda Resepsi Elegan Arsy Tenda'),
                    'default' => 'assets/images/references/marquee.jpg',
                ],
                2 => [
                    'slot' => 2,
                    'key' => 'portfolio_image_2',
                    'alt_key' => 'portfolio_alt_2',
                    'name' => 'Slot 2: Panggung & Rigging Acara',
                    'desc' => 'Foto panggung acara di sisi kanan atas kolase.',
                    'image' => SystemSetting::get('portfolio_image_2'),
                    'url' => SystemSetting::getImageUrl('portfolio_image_2', 'assets/images/references/event-stage.jpg'),
                    'alt' => SystemSetting::get('portfolio_alt_2', 'Instalasi Panggung Rigging Acara'),
                    'default' => 'assets/images/references/event-stage.jpg',
                ],
                3 => [
                    'slot' => 3,
                    'key' => 'portfolio_image_3',
                    'alt_key' => 'portfolio_alt_3',
                    'name' => 'Slot 3: Penataan Meja & Kursi',
                    'desc' => 'Foto jamuan tamu dan penataan meja kursi di kanan tengah.',
                    'image' => SystemSetting::get('portfolio_image_3'),
                    'url' => SystemSetting::getImageUrl('portfolio_image_3', 'assets/images/references/table-setup.jpg'),
                    'alt' => SystemSetting::get('portfolio_alt_3', 'Penataan Meja Kursi Jamuan Tamu'),
                    'default' => 'assets/images/references/table-setup.jpg',
                ],
                4 => [
                    'slot' => 4,
                    'key' => 'portfolio_image_4',
                    'alt_key' => 'portfolio_alt_4',
                    'name' => 'Slot 4: Area Luas (Tenda Roder)',
                    'desc' => 'Foto bentang luas memanjang di dasar kolase portofolio.',
                    'image' => SystemSetting::get('portfolio_image_4'),
                    'url' => SystemSetting::getImageUrl('portfolio_image_4', 'assets/images/hero-tent.jpg'),
                    'alt' => SystemSetting::get('portfolio_alt_4', 'Suasana Tenda Roder Acara Besar'),
                    'default' => 'assets/images/hero-tent.jpg',
                ],
            ],
        ];

        return view('admin.content.index', compact('media'));
    }

    /**
     * Update homepage hero banner image.
     */
    public function updateHeroImage(Request $request)
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('image')) {
            $oldImage = SystemSetting::get('homepage_hero_image');
            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }
            $path = $request->file('image')->store('content', 'public');
            SystemSetting::set('homepage_hero_image', $path, 'Foto banner utama hero beranda');
        }

        if ($request->filled('alt')) {
            SystemSetting::set('homepage_hero_alt', $request->alt, 'Teks deskripsi foto hero beranda');
        }

        return back()->with('success', 'Gambar Hero Beranda berhasil diperbarui.');
    }

    /**
     * Update a specific portfolio collage slot.
     */
    public function updatePortfolioImage(Request $request, int $slot)
    {
        if (!in_array($slot, [1, 2, 3, 4])) {
            abort(404, 'Slot portofolio tidak valid.');
        }

        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('image')) {
            $key = "portfolio_image_{$slot}";
            $oldImage = SystemSetting::get($key);
            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }
            $path = $request->file('image')->store('content', 'public');
            SystemSetting::set($key, $path, "Foto portofolio kolase slot {$slot}");
        }

        if ($request->filled('alt')) {
            SystemSetting::set("portfolio_alt_{$slot}", $request->alt, "Deskripsi foto portofolio slot {$slot}");
        }

        return back()->with('success', "Gambar Portofolio Slot {$slot} berhasil diperbarui.");
    }

    /**
     * Reset a content image back to its system default asset.
     */
    public function resetContentImage(string $key)
    {
        $allowed = [
            'homepage_hero_image',
            'portfolio_image_1',
            'portfolio_image_2',
            'portfolio_image_3',
            'portfolio_image_4',
        ];

        if (!in_array($key, $allowed)) {
            abort(404, 'Kunci media tidak valid.');
        }

        $current = SystemSetting::get($key);
        if ($current && Storage::disk('public')->exists($current)) {
            Storage::disk('public')->delete($current);
        }

        SystemSetting::where('key', $key)->delete();

        return back()->with('success', 'Gambar berhasil dikembalikan ke tampilan default.');
    }

    /**
     * User profile inside administrative/worker panel.
     */
    public function profile()
    {
        $user = Auth::user();
        return view('admin.profile', compact('user'));
    }

    /**
     * Update user profile inside administrative/worker panel.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
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

        $user->update($data);

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
