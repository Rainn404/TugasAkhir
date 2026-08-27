@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <span class="text-uppercase text-secondary fw-bold small tracking-wider">Portal Pelanggan</span>
            <h2 class="fw-bold text-black mb-1">Dashboard Penyewa</h2>
            <p class="text-secondary small mb-0">Selamat datang kembali, <strong>{{ auth()->user()->name }}</strong>.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('home') }}#katalog" class="btn btn-outline-dark btn-sm rounded-3">
                <i class="bi bi-cart-plus me-1"></i> Sewa Baru
            </a>
            <a href="{{ route('survey.create') }}" class="btn btn-dark btn-sm rounded-3">
                <i class="bi bi-geo-alt me-1"></i> Ajukan Survei
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills gap-2 mb-4 border-bottom pb-3">
        <li class="nav-item">
            <a class="nav-link active bg-black rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.index') }}">
                <i class="bi bi-grid me-1"></i> Ringkasan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.orders') }}">
                <i class="bi bi-receipt me-1"></i> Pesanan Saya
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.events') }}">
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

    <!-- 4 Statistic Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card p-3 border rounded-3 bg-white">
                <span class="text-secondary small fw-semibold">Pesanan Aktif</span>
                <h3 class="fw-bold text-black mt-1 mb-0">{{ $stats['active_orders'] }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card p-3 border rounded-3 bg-white">
                <span class="text-warning small fw-semibold">Menunggu Pembayaran</span>
                <h3 class="fw-bold text-warning mt-1 mb-0">{{ $stats['pending_payments'] }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card p-3 border rounded-3 bg-white">
                <span class="text-secondary small fw-semibold">Acara Mendatang</span>
                <h3 class="fw-bold text-black mt-1 mb-0">{{ $stats['upcoming_events'] }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card p-3 border rounded-3 bg-white">
                <span class="text-secondary small fw-semibold">Survei Diajukan</span>
                <h3 class="fw-bold text-black mt-1 mb-0">{{ $stats['surveys_count'] }}</h3>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Pesanan Terbaru -->
        <div class="col-lg-7">
            <div class="card border rounded-4 bg-white p-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-black mb-0">Pesanan Sewa Terbaru</h5>
                    <a href="{{ route('dashboard.orders') }}" class="small text-secondary text-decoration-none">Lihat Semua →</a>
                </div>

                @if($recentOrders->isEmpty())
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-receipt fs-2 opacity-50 d-block mb-1"></i>
                        <small>Belum ada pesanan aktif.</small>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($recentOrders as $order)
                            <a href="{{ route('dashboard.order', $order->order_number) }}" class="list-group-item list-group-item-action px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold text-black font-monospace small mb-1">{{ $order->order_number }}</div>
                                    <small class="text-secondary d-block">{{ $order->event->event_name ?? 'Acara' }} &bull; {{ \Carbon\Carbon::parse($order->event->event_date)->format('d M Y') }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-black small">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</div>
                                    @php
                                        $badgeStyles = [
                                            'pending_payment' => 'badge-limited',
                                            'paid' => 'badge-available',
                                            'confirmed' => 'badge-available',
                                            'preparing' => 'bg-info text-dark',
                                            'ongoing' => 'bg-primary text-white',
                                            'completed' => 'badge-available',
                                            'cancelled' => 'badge-unavailable',
                                        ];
                                    @endphp
                                    <span class="badge {{ $badgeStyles[$order->status] ?? 'bg-light text-dark' }} small">
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
            <div class="card border rounded-4 bg-white p-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-black mb-0">Status Survei Lokasi</h5>
                    <a href="{{ route('dashboard.surveys') }}" class="small text-secondary text-decoration-none">Lihat Semua →</a>
                </div>

                @if($recentSurveys->isEmpty())
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-geo-alt fs-2 opacity-50 d-block mb-1"></i>
                        <small>Belum ada pengajuan survei.</small>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($recentSurveys as $survey)
                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold text-black mb-1 small">{{ $survey->event_name }}</h6>
                                    <small class="text-secondary d-block">{{ \Carbon\Carbon::parse($survey->event_date)->format('d M Y') }}</small>
                                </div>
                                <span class="badge {{ $survey->status === 'selesai' ? 'bg-success' : ($survey->status === 'menunggu' ? 'bg-warning text-dark' : 'bg-dark') }} small">
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
