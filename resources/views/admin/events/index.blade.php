@extends('layouts.admin')

@section('content')
<div class="page-editorial-header mb-4">
    @if(auth()->user()->role === 'pekerja')
        <span class="page-eyebrow">Tugas Lapangan</span>
        <h1 class="page-headline mb-1 font-display">Jadwal Kerja</h1>
        <p class="page-subtext">Pantau agenda kegiatan acara dan jadwal pelaksanaan yang ditugaskan kepada Anda.</p>
    @else
        <span class="page-eyebrow">Jadwal Lapangan</span>
        <h1 class="page-headline mb-1 font-display">Manajemen Kegiatan Acara</h1>
        <p class="page-subtext">Pantau seluruh agenda dan jadwal pelaksanaan acara yang telah terkonfirmasi.</p>
    @endif
</div>

<!-- Filter Box -->
<div class="editorial-card p-3 p-md-4 mb-4">
    <form action="{{ route('admin.events') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
            <input type="date" name="date" value="{{ request('date') }}" class="form-control rounded-3">
        </div>
        <div class="col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 w-100 fw-semibold">Filter Tanggal Acara</button>
            <a href="{{ route('admin.events') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold">Reset</a>
        </div>
    </form>
</div>

<!-- Events Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>Nama Acara</th>
                    <th>Jenis Acara</th>
                    <th>Tanggal Pelaksanaan</th>
                    <th>Waktu</th>
                    <th>Pelanggan</th>
                    <th>Alamat Lokasi</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $evt)
                    <tr>
                        <td>
                            <strong class="text-black small d-block">{{ $evt->event_name }}</strong>
                            @if($evt->order)
                                <a href="{{ route('admin.orders.show', $evt->order->id) }}" class="small font-monospace text-decoration-none text-secondary">
                                    {{ $evt->order->order_number }}
                                </a>
                            @endif
                        </td>
                        <td class="small">{{ $evt->event_type }}</td>
                        <td class="small fw-semibold text-black">
                            {{ \Carbon\Carbon::parse($evt->event_date)->translatedFormat('d M Y') }}
                        </td>
                        <td class="small text-secondary">
                            {{ $evt->start_time }} - {{ $evt->end_time }}
                        </td>
                        <td class="small">
                            <span class="d-block fw-semibold text-black">{{ $evt->user->name ?? '-' }}</span>
                            <small class="text-secondary">{{ $evt->user->phone ?? '-' }}</small>
                        </td>
                        <td class="small text-secondary" style="max-width: 220px;">
                            {{ $evt->address }}
                        </td>
                        <td class="text-center">
                            <span class="badge-status {{ $evt->status === 'completed' ? 'badge-status--success' : ($evt->status === 'cancelled' ? 'badge-status--danger' : 'badge-status--neutral') }}" style="font-size: 0.72rem;">
                                {{ strtoupper($evt->status) }}
                            </span>
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
        <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
            {{ $events->links() }}
        </div>
    @endif
</div>
@endsection
