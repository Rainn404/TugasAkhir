@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
        <div>
            <span class="page-eyebrow">Portal Pelanggan</span>
            <h1 class="page-headline mb-1">Dashboard Penyewa</h1>
            <p class="page-subtext">Selamat datang kembali, <strong>{{ auth()->user()->name }}</strong>. Pantau jadwal dan status reservasi acara Anda.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('home') }}#katalog" class="btn-hero-secondary">
                <i class="bi bi-cart-plus me-1"></i> Sewa Baru
            </a>
            <a href="{{ route('survey.create') }}" class="btn-hero-primary">
                <i class="bi bi-geo-alt me-1"></i> Ajukan Survei
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="dashboard-nav-pills" aria-label="Menu Dasbor Pelanggan">
        <a class="dashboard-pill active" href="{{ route('dashboard.index') }}">
            <i class="bi bi-grid"></i> <span>Ringkasan</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.orders') }}">
            <i class="bi bi-receipt"></i> <span>Pesanan Saya</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.events') }}">
            <i class="bi bi-calendar-event"></i> <span>Kegiatan Acara</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.surveys') }}">
            <i class="bi bi-geo-alt"></i> <span>Survei Lokasi</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.profile') }}">
            <i class="bi bi-person"></i> <span>Profil Akun</span>
        </a>
    </nav>

    <!-- 4 Statistic Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="editorial-stat-card">
                <span class="stat-kicker">Pesanan Aktif</span>
                <div class="stat-number">{{ $stats['active_orders'] }}</div>
                <p class="stat-hint">Sedang diproses</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="editorial-stat-card">
                <span class="stat-kicker" style="color: #B45309;">Menunggu Bayar</span>
                <div class="stat-number" style="color: #B45309;">{{ $stats['pending_payments'] }}</div>
                <p class="stat-hint">Perlu pembayaran</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="editorial-stat-card">
                <span class="stat-kicker">Acara Mendatang</span>
                <div class="stat-number">{{ $stats['upcoming_events'] }}</div>
                <p class="stat-hint">Jadwal terkonfirmasi</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="editorial-stat-card">
                <span class="stat-kicker">Survei Diajukan</span>
                <div class="stat-number">{{ $stats['surveys_count'] }}</div>
                <p class="stat-hint">Pengajuan lokasi</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Pesanan Terbaru -->
        <div class="col-lg-7">
            <div class="editorial-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
                    <h5 class="fw-bold text-black mb-0 font-display">Pesanan Sewa Terbaru</h5>
                    <a href="{{ route('dashboard.orders') }}" class="small text-secondary text-decoration-none fw-semibold">Lihat Semua →</a>
                </div>

                @if($recentOrders->isEmpty())
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-receipt fs-2 opacity-50 d-block mb-1"></i>
                        <small>Belum ada pesanan aktif.</small>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($recentOrders as $order)
                            <a href="{{ route('dashboard.order', $order->order_number) }}" class="list-group-item list-group-item-action px-0 py-3 d-flex justify-content-between align-items-center border-bottom last-border-0" style="border-color: #F0ECE6 !important; background: transparent;">
                                <div>
                                    <div class="fw-bold text-black font-monospace small mb-1">{{ $order->order_number }}</div>
                                    <small class="text-secondary d-block">{{ $order->event->event_name ?? 'Acara' }} &bull; {{ \Carbon\Carbon::parse($order->event->event_date)->format('d M Y') }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-black small mb-1">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</div>
                                    @php
                                        $badgeStyles = [
                                            'pending_payment' => 'badge-status--warning',
                                            'paid' => 'badge-status--success',
                                            'confirmed' => 'badge-status--success',
                                            'preparing' => 'badge-status--info',
                                            'ongoing' => 'badge-status--neutral',
                                            'completed' => 'badge-status--success',
                                            'cancelled' => 'badge-status--danger',
                                        ];
                                    @endphp
                                    <span class="badge-status {{ $badgeStyles[$order->status] ?? 'badge-status--neutral' }}" style="font-size: 0.72rem;">
                                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Survei Terbaru -->
        <div class="col-lg-5">
            <div class="editorial-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
                    <h5 class="fw-bold text-black mb-0 font-display">Status Survei Lokasi</h5>
                    <a href="{{ route('dashboard.surveys') }}" class="small text-secondary text-decoration-none fw-semibold">Lihat Semua →</a>
                </div>

                @if($recentSurveys->isEmpty())
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-geo-alt fs-2 opacity-50 d-block mb-1"></i>
                        <small>Belum ada pengajuan survei.</small>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($recentSurveys as $survey)
                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-bottom last-border-0" style="border-color: #F0ECE6 !important; background: transparent;">
                                <div>
                                    <h6 class="fw-bold text-black mb-1 small">{{ $survey->event_name }}</h6>
                                    <small class="text-secondary d-block">{{ \Carbon\Carbon::parse($survey->event_date)->format('d M Y') }}</small>
                                </div>
                                <span class="badge-status {{ $survey->status === 'selesai' ? 'badge-status--success' : ($survey->status === 'menunggu' ? 'badge-status--warning' : 'badge-status--neutral') }}" style="font-size: 0.72rem;">
                                    {{ strtoupper($survey->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
