@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 gap-2 gap-md-3 page-editorial-header">
    <div>
        <span class="page-eyebrow">Operasional Arsy Tenda</span>
        <h1 class="page-headline mb-1 font-display">Dashboard Administrator</h1>
        <p class="page-subtext">Ringkasan operasional dan statistik bisnis persewaan Arsy Tenda.</p>
    </div>
    <div>
        <span class="badge-status badge-status--neutral px-3 py-2">
            <i class="bi bi-calendar3 me-1"></i> {{ date('l, d F Y') }}
        </span>
    </div>
</div>

<!-- Statistic Cards -->
<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="editorial-stat-card">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <span class="stat-kicker">Total Pendapatan</span>
                <span class="badge-status badge-status--neutral p-1 p-sm-1.5"><i class="bi bi-cash-stack fs-6 text-black"></i></span>
            </div>
            <div class="stat-number my-1">Rp {{ number_format($stats['total_sales'], 0, ',', '.') }}</div>
            <p class="stat-hint">Akumulasi pesanan lunas</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="editorial-stat-card">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <span class="stat-kicker" style="color: #B45309;">Menunggu Bayar</span>
                <span class="badge-status badge-status--warning p-1 p-sm-1.5"><i class="bi bi-clock-history fs-6" style="color: #B45309;"></i></span>
            </div>
            <div class="stat-number my-1" style="color: #B45309;">{{ $stats['pending_payment_count'] }}</div>
            <p class="stat-hint">Pesanan belum lunas</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="editorial-stat-card">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <span class="stat-kicker" style="color: #15803D;">Pesanan Aktif</span>
                <span class="badge-status badge-status--success p-1 p-sm-1.5"><i class="bi bi-check2-circle fs-6" style="color: #15803D;"></i></span>
            </div>
            <div class="stat-number my-1" style="color: #15803D;">{{ $stats['active_orders'] }}</div>
            <p class="stat-hint">Tahap persiapan &amp; acara</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="editorial-stat-card">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <span class="stat-kicker">Survei Lapangan</span>
                <span class="badge-status badge-status--neutral p-1 p-sm-1.5"><i class="bi bi-geo-alt fs-6 text-black"></i></span>
            </div>
            <div class="stat-number my-1">{{ $stats['active_surveys_count'] }}</div>
            <p class="stat-hint">Menunggu / dijadwalkan</p>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Pesanan Terbaru -->
    <div class="col-lg-7">
        <div class="editorial-card overflow-hidden h-100">
            <div class="editorial-card-header">
                <h5 class="fw-bold text-black mb-0 font-display fs-6">Pesanan Terbaru</h5>
                <a href="{{ route('admin.orders') }}" class="small text-secondary text-decoration-none fw-semibold">Lihat Semua →</a>
            </div>

            <div class="table-responsive">
                <table class="editorial-table align-middle">
                    <thead>
                        <tr>
                            <th>No. Order</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr>
                                <td class="font-monospace fw-bold small text-black">{{ $order->order_number }}</td>
                                <td class="small">{{ $order->user->name ?? '-' }}</td>
                                <td class="fw-semibold small text-black">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    @php
                                        $badges = [
                                            'pending_payment' => 'badge-status--warning',
                                            'paid' => 'badge-status--success',
                                            'confirmed' => 'badge-status--success',
                                            'preparing' => 'badge-status--info',
                                            'ongoing' => 'badge-status--neutral',
                                            'completed' => 'badge-status--success',
                                            'cancelled' => 'badge-status--danger',
                                        ];
                                    @endphp
                                    <span class="badge-status {{ $badges[$order->status] ?? 'badge-status--neutral' }}" style="font-size: 0.7rem;">
                                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-0.5 text-nowrap" style="font-size: 0.75rem;">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4 small">Belum ada pesanan masuk.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Jadwal Acara Terdekat -->
    <div class="col-lg-5">
        <div class="editorial-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="fw-bold text-black mb-0 font-display fs-6">Jadwal Acara Terdekat</h5>
                <a href="{{ route('admin.events') }}" class="small text-secondary text-decoration-none fw-semibold">Lihat Semua →</a>
            </div>

            <div class="list-group list-group-flush">
                @forelse($upcomingEvents as $evt)
                    <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center border-bottom last-border-0" style="border-color: #F0ECE6 !important; background: transparent;">
                        <div>
                            <h6 class="fw-bold text-black mb-0 small">{{ $evt->event_name }}</h6>
                            <small class="text-secondary">{{ $evt->user->name ?? '-' }} &bull; {{ $evt->event_type }}</small>
                        </div>
                        <span class="badge-status badge-status--neutral" style="font-size: 0.72rem;">
                            {{ \Carbon\Carbon::parse($evt->event_date)->format('d M') }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-secondary py-4 small">Tidak ada acara mendatang.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Inventaris Stok Rendah -->
    <div class="col-lg-6">
        <div class="editorial-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="fw-bold text-black mb-0 font-display fs-6">Inventaris Stok Menipis</h5>
                <a href="{{ route('admin.inventories') }}" class="small text-secondary text-decoration-none fw-semibold">Kelola →</a>
            </div>

            <ul class="list-group list-group-flush mb-0">
                @forelse($lowStockInventories as $inv)
                    <li class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center border-bottom last-border-0" style="border-color: #F0ECE6 !important; background: transparent;">
                        <div>
                            <span class="fw-semibold text-black small d-block">{{ $inv->name }}</span>
                            <small class="text-secondary">{{ $inv->category->name }}</small>
                        </div>
                        <span class="badge-status {{ $inv->stock <= 5 ? 'badge-status--danger' : 'badge-status--warning' }}" style="font-size: 0.72rem;">
                            Tersisa: {{ $inv->stock }} {{ $inv->unit }}
                        </span>
                    </li>
                @empty
                    <li class="list-group-item px-0 py-3 text-center text-secondary small" style="background: transparent;">Seluruh stok inventaris dalam kondisi aman.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Survei Terbaru -->
    <div class="col-lg-6">
        <div class="editorial-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="fw-bold text-black mb-0 font-display fs-6">Pengajuan Survei Lokasi Terbaru</h5>
                <a href="{{ route('admin.surveys') }}" class="small text-secondary text-decoration-none fw-semibold">Kelola →</a>
            </div>

            <ul class="list-group list-group-flush mb-0">
                @forelse($recentSurveys as $srv)
                    <li class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center border-bottom last-border-0" style="border-color: #F0ECE6 !important; background: transparent;">
                        <div>
                            <span class="fw-semibold text-black small d-block">{{ $srv->event_name }}</span>
                            <small class="text-secondary">{{ $srv->user->name ?? '-' }} &bull; {{ \Carbon\Carbon::parse($srv->event_date)->format('d M Y') }}</small>
                        </div>
                        <span class="badge-status {{ $srv->status === 'menunggu' ? 'badge-status--warning' : 'badge-status--neutral' }}" style="font-size: 0.72rem;">
                            {{ strtoupper($srv->status) }}
                        </span>
                    </li>
                @empty
                    <li class="list-group-item px-0 py-3 text-center text-secondary small" style="background: transparent;">Belum ada pengajuan survei.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
