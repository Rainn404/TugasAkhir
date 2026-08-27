@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Manajemen Kegiatan Acara</h4>
        <p class="text-secondary small mb-0">Pantau seluruh agenda dan jadwal pelaksanaan acara pelanggan.</p>
    </div>
</div>

<!-- Filter Box -->
<div class="card p-3 border rounded-3 bg-white mb-4 shadow-sm">
    <form action="{{ route('admin.events') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
            <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm rounded-2">
        </div>
        <div class="col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-dark btn-sm rounded-2 w-100">Filter Tanggal Acara</button>
            <a href="{{ route('admin.events') }}" class="btn btn-outline-secondary btn-sm rounded-2">Reset</a>
        </div>
    </form>
</div>

<!-- Events Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">Nama Acara</th>
                    <th class="py-3 px-3">Jenis Acara</th>
                    <th class="py-3 px-3">Tanggal Pelaksanaan</th>
                    <th class="py-3 px-3">Waktu</th>
                    <th class="py-3 px-3">Pelanggan</th>
                    <th class="py-3 px-3">Alamat Lokasi</th>
                    <th class="py-3 px-4 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $evt)
                    <tr>
                        <td class="py-3 px-4">
                            <strong class="text-black small d-block">{{ $evt->event_name }}</strong>
                            @if($evt->order)
                                <a href="{{ route('admin.orders.show', $evt->order->id) }}" class="small font-monospace text-decoration-none text-secondary">
                                    {{ $evt->order->order_number }}
                                </a>
                            @endif
                        </td>
                        <td class="py-3 px-3 small">{{ $evt->event_type }}</td>
                        <td class="py-3 px-3 small fw-semibold text-black">
                            {{ \Carbon\Carbon::parse($evt->event_date)->translatedFormat('d M Y') }}
                        </td>
                        <td class="py-3 px-3 small text-secondary">
                            {{ $evt->start_time }} - {{ $evt->end_time }}
                        </td>
                        <td class="py-3 px-3 small">
                            <span class="d-block fw-semibold text-black">{{ $evt->user->name ?? '-' }}</span>
                            <small class="text-secondary">{{ $evt->user->phone ?? '-' }}</small>
                        </td>
                        <td class="py-3 px-3 small text-secondary" style="max-width: 220px;">
                            {{ $evt->address }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="badge bg-dark small">{{ strtoupper($evt->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="bi bi-calendar-x fs-2 d-block mb-1 opacity-50"></i>
                            Tidak ada data kegiatan acara.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($events->hasPages())
        <div class="p-3 border-top">
            {{ $events->links() }}
        </div>
    @endif
</div>
@endsection
