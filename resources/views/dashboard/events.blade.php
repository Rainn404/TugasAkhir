@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <span class="text-uppercase text-secondary fw-bold small tracking-wider">Jadwal & Lokasi</span>
            <h2 class="fw-bold text-black mb-1">Kegiatan Acara Saya</h2>
        </div>
        <a href="{{ route('home') }}#katalog" class="btn btn-dark btn-sm rounded-3">
            <i class="bi bi-plus-lg me-1"></i> Rencanakan Acara Baru
        </a>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills gap-2 mb-4 border-bottom pb-3">
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.index') }}">
                <i class="bi bi-grid me-1"></i> Ringkasan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.orders') }}">
                <i class="bi bi-receipt me-1"></i> Pesanan Saya
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active bg-black rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.events') }}">
                <i class="bi bi-calendar-event me-1"></i> Kegiatan Acara
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.surveys') }}">
                <i class="bi bi-geo-alt me-1"></i> Survei Lokasi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.profile') }}">
                <i class="bi bi-person me-1"></i> Profil Akun
            </a>
        </li>
    </ul>

    <!-- Events Grid -->
    <div class="row g-4">
        @forelse($events as $event)
            <div class="col-md-6">
                <div class="card p-4 border rounded-4 bg-white shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-light text-black border small fw-normal mb-1">{{ $event->event_type }}</span>
                            <h5 class="fw-bold text-black mb-0">{{ $event->event_name }}</h5>
                        </div>
                        <span class="badge bg-dark small">{{ strtoupper($event->status) }}</span>
                    </div>

                    <div class="small text-secondary mb-3">
                        <div class="mb-1"><i class="bi bi-calendar3 me-2 text-black"></i> {{ \Carbon\Carbon::parse($event->event_date)->translatedFormat('l, d F Y') }}</div>
                        <div class="mb-1"><i class="bi bi-clock me-2 text-black"></i> {{ $event->start_time }} - {{ $event->end_time }} WITA</div>
                        <div><i class="bi bi-geo-alt me-2 text-black"></i> {{ $event->address }}</div>
                    </div>

                    @if($event->order)
                        <div class="border-top pt-3 mt-auto d-flex justify-content-between align-items-center">
                            <small class="text-secondary font-monospace">{{ $event->order->order_number }}</small>
                            <a href="{{ route('dashboard.order', $event->order->order_number) }}" class="btn btn-outline-dark btn-sm rounded-2">
                                Lihat Rincian Sewa →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card text-center py-5 border rounded-4 bg-white">
                    <i class="bi bi-calendar-x text-secondary opacity-50 fs-1 d-block mb-2"></i>
                    <h5 class="fw-bold text-black">Belum Ada Kegiatan Terdaftar</h5>
                    <p class="text-secondary small">Jadwal kegiatan Anda akan otomatis tercatat ketika Anda membuat pesanan sewa.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if($events->hasPages())
        <div class="mt-4">
            {{ $events->links() }}
        </div>
    @endif
</div>
@endsection
