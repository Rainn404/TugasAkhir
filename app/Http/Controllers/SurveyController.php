<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $request->validate([
            'status' => ['required', 'in:menunggu,dijadwalkan,diproses,selesai,ditolak,dibatalkan'],
            'survey_date' => ['nullable', 'date'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $survey->update([
            'status' => $request->status,
            'survey_date' => $request->survey_date,
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
            'assigned_worker_id' => ['required', 'exists:users,id'],
            'worker_instructions' => ['nullable', 'string', 'max:1000'],
            'survey_date' => ['nullable', 'date'],
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
}
