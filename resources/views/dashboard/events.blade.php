@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
        <div>
            <span class="page-eyebrow">Jadwal &amp; Lokasi</span>
            <h1 class="page-headline mb-1">Kegiatan Acara Saya</h1>
            <p class="page-subtext">Daftar agenda acara yang telah dijadwalkan bersama Arsy Tenda.</p>
        </div>
        <div>
            <a href="{{ route('home') }}#katalog" class="btn-hero-primary">
                <i class="bi bi-plus-lg me-1"></i> Rencanakan Acara Baru
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="dashboard-nav-pills" aria-label="Menu Dasbor Pelanggan">
        <a class="dashboard-pill" href="{{ route('dashboard.index') }}">
            <i class="bi bi-grid"></i> <span>Ringkasan</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.orders') }}">
            <i class="bi bi-receipt"></i> <span>Pesanan Saya</span>
        </a>
        <a class="dashboard-pill active" href="{{ route('dashboard.events') }}">
            <i class="bi bi-calendar-event"></i> <span>Kegiatan Acara</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.surveys') }}">
            <i class="bi bi-geo-alt"></i> <span>Survei Lokasi</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.profile') }}">
            <i class="bi bi-person"></i> <span>Profil Akun</span>
        </a>
    </nav>

    <!-- Events Grid -->
    <div class="row g-4">
        @forelse($events as $event)
            <div class="col-md-6">
                <div class="editorial-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge-status badge-status--neutral mb-2" style="font-size: 0.72rem;">{{ $event->event_type }}</span>
                                <h4 class="fw-bold text-black mb-0 font-display fs-5">{{ $event->event_name }}</h4>
                            </div>
                            <span class="badge-status {{ $event->status === 'completed' ? 'badge-status--success' : ($event->status === 'cancelled' ? 'badge-status--danger' : 'badge-status--neutral') }}" style="font-size: 0.72rem;">
                                {{ strtoupper($event->status) }}
                            </span>
                        </div>

                        <div class="small text-secondary mb-3">
                            <div class="mb-1.5 d-flex align-items-center gap-2">
                                <i class="bi bi-calendar3 text-black"></i>
                                <span>{{ \Carbon\Carbon::parse($event->event_date)->translatedFormat('l, d F Y') }}</span>
                            </div>
                            <div class="mb-1.5 d-flex align-items-center gap-2">
                                <i class="bi bi-clock text-black"></i>
                                <span>{{ $event->start_time }} - {{ $event->end_time }} WITA</span>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-geo-alt text-black mt-0.5"></i>
                                <span>{{ $event->address }}</span>
                            </div>
                        </div>
                    </div>

                    @if($event->order)
                        <div class="border-top pt-3 mt-3 d-flex justify-content-between align-items-center" style="border-color: #E7E2DA !important;">
                            <small class="text-secondary font-monospace">{{ $event->order->order_number }}</small>
                            <a href="{{ route('dashboard.order', $event->order->order_number) }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1">
                                Rincian Sewa →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="editorial-card text-center py-5">
                    <i class="bi bi-calendar-x text-secondary opacity-50 fs-1 d-block mb-2"></i>
                    <h5 class="fw-bold text-black font-display">Belum Ada Kegiatan Terdaftar</h5>
                    <p class="text-secondary small mb-0">Jadwal kegiatan Anda akan otomatis tercatat ketika Anda membuat pesanan sewa perlengkapan.</p>
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
