<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryVariant;
use App\Models\Survey;
use App\Models\User;
use App\Services\InventoryAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SurveyController extends Controller
{
    /**
     * Show form to request a site survey.
     */
    public function create()
    {
        return view('survey.create');
    }

    /**
     * Store new survey request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'address' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'phone' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        Survey::create([
            'user_id' => Auth::id(),
            'event_name' => $request->event_name,
            'event_date' => $request->event_date,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'phone' => $request->phone,
            'notes' => $request->notes,
            'status' => 'menunggu',
        ]);

        return redirect()->route('dashboard.surveys')
            ->with('success', 'Permintaan survei lokasi berhasil diajukan. Tim kami akan segera menghubungi Anda.');
    }

    /**
     * Update survey status / scheduling by Admin.
     */
    public function updateStatus(Request $request, Survey $survey)
    {
        if (Auth::user()->role === 'pekerja') {
            if ($survey->assigned_worker_id !== Auth::id()) {
                abort(403, 'Akses tidak diizinkan. Anda hanya dapat memperbarui survei yang ditugaskan kepada Anda.');
            }

            $request->validate([
                'status' => ['required', 'in:dijadwalkan,diproses,selesai'],
                'survey_date' => ['nullable', 'date'],
                'admin_notes' => ['nullable', 'string'],
            ], [
                'status.in' => 'Petugas lapangan hanya dapat memperbarui status operasional survei (dijadwalkan, diproses, selesai).',
            ]);
        } else {
            $request->validate([
                'status' => ['required', 'in:menunggu,dijadwalkan,diproses,selesai,ditolak,dibatalkan'],
                'survey_date' => ['nullable', 'date'],
                'admin_notes' => ['nullable', 'string'],
            ]);
        }

        $survey->update([
            'status' => $request->status,
            'survey_date' => $request->survey_date ?? $survey->survey_date,
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('success', 'Status dan jadwal survei lokasi berhasil diperbarui.');
    }

    /**
     * Assign a worker/petugas lapangan to conduct the survey.
     */
    public function assignWorker(Request $request, Survey $survey)
    {
        $request->validate([
            'assigned_worker_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['pekerja', 'admin']);
                }),
            ],
            'worker_instructions' => ['nullable', 'string', 'max:1000'],
            'survey_date' => ['nullable', 'date'],
        ], [
            'assigned_worker_id.exists' => 'Akun yang dipilih bukan akun petugas lapangan atau administrator.',
        ]);

        $worker = User::findOrFail($request->assigned_worker_id);

        $survey->update([
            'assigned_worker_id' => $request->assigned_worker_id,
            'worker_instructions' => $request->worker_instructions,
            'survey_date' => $request->survey_date ?? $survey->survey_date,
            'status' => 'dijadwalkan',
        ]);

        return back()->with('success', "Petugas lapangan <strong>{$worker->name}</strong> berhasil ditugaskan untuk survei <em>{$survey->event_name}</em>.");
    }

    /**
     * Submit survey findings and equipment recommendations by assigned worker or admin.
     */
    public function submitRecommendation(Request $request, Survey $survey)
    {
        if (Auth::user()->role === 'pekerja' && $survey->assigned_worker_id !== Auth::id()) {
            abort(403, 'Akses tidak diizinkan. Anda hanya dapat mengisi rekomendasi untuk survei yang ditugaskan kepada Anda.');
        }

        $request->validate([
            'survey_result' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_id' => ['required', 'exists:inventories,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $recommendations = [];
        foreach ($request->items as $item) {
            $inv = Inventory::findOrFail($item['inventory_id']);
            $recommendations[] = [
                'inventory_id' => $inv->id,
                'name' => $inv->name,
                'unit' => $inv->unit,
                'price' => (float) $inv->price,
                'quantity' => (int) $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ];
        }

        $survey->update([
            'survey_result' => $request->survey_result,
            'recommendations_json' => $recommendations,
            'recommended_at' => now(),
            'recommendation_expires_at' => now()->addDays(7),
            'status' => 'selesai',
        ]);

        return back()->with('success', 'Hasil survei dan rekomendasi perlengkapan berhasil disimpan (berlaku selama 7 hari).');
    }

    /**
     * Customer applies the recommended items to their rental cart with date-based stock validation.
     */
    public function applyRecommendation(Request $request, Survey $survey, InventoryAvailabilityService $availabilityService)
    {
        if ($survey->user_id !== Auth::id()) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if (!$survey->isRecommendationActive()) {
            return back()->with('error', 'Rekomendasi survei ini sudah kedaluwarsa atau belum tersedia.');
        }

        $eventDate = $survey->event_date->format('Y-m-d');
        $cart = [];

        foreach ($survey->recommendations_json as $rec) {
            $inventory = Inventory::findOrFail($rec['inventory_id']);
            $quantity = (int) $rec['quantity'];

            // Cek ketersediaan stok
            $stockInfo = $availabilityService->checkAvailability($inventory, $eventDate);
            if ($stockInfo['available_stock'] < $quantity) {
                return back()->with('error', "Stok untuk {$inventory->name} pada tanggal " . date('d-m-Y', strtotime($eventDate)) . " tidak mencukupi (tersedia: {$stockInfo['available_stock']}). Hubungi admin untuk penyesuaian.");
            }

            $key = $inventory->id . '_0';
            $unitPrice = (float) $inventory->price;
            $cart[$key] = [
                'inventory_id' => $inventory->id,
                'variant_id' => null,
                'name' => $inventory->name,
                'variant_name' => null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
                'unit' => $inventory->unit,
                'options' => [],
            ];
        }

        session()->put('cart', $cart);
        session()->put('cart_date', $eventDate);
        session()->put('survey_id', $survey->id);
        session()->forget('checkout_key');

        return redirect()->route('cart.index')
            ->with('success', 'Rekomendasi survei berhasil dimuat ke keranjang sewa untuk tanggal acara ' . date('d/m/Y', strtotime($eventDate)) . '. Silakan periksa rincian sebelum checkout.');
    }
}
