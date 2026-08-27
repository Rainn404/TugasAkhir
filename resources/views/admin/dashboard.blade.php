@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Dashboard Administrator</h4>
        <p class="text-secondary small mb-0">Ringkasan operasional dan statistik persewaan Arsy Tenda.</p>
    </div>
    <span class="badge bg-dark px-3 py-2 rounded-pill small">
        <i class="bi bi-calendar3 me-1"></i> {{ date('l, d F Y') }}
    </span>
</div>

<!-- Statistic Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card p-3 border rounded-3 bg-white shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-semibold">Total Pendapatan</span>
                <span class="p-2 rounded bg-light"><i class="bi bi-cash-stack fs-5 text-black"></i></span>
            </div>
            <h4 class="fw-bold text-black mb-0">Rp {{ number_format($stats['total_sales'], 0, ',', '.') }}</h4>
            <small class="text-secondary">Akumulasi pesanan valid</small>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card p-3 border rounded-3 bg-white shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-warning small fw-semibold">Menunggu Pembayaran</span>
                <span class="p-2 rounded bg-warning bg-opacity-10"><i class="bi bi-clock-history fs-5 text-warning"></i></span>
            </div>
            <h4 class="fw-bold text-warning mb-0">{{ $stats['pending_payment_count'] }}</h4>
            <small class="text-secondary">Pesanan belum lunas</small>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card p-3 border rounded-3 bg-white shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-success small fw-semibold">Pesanan Aktif / Lunas</span>
                <span class="p-2 rounded bg-success bg-opacity-10"><i class="bi bi-check2-circle fs-5 text-success"></i></span>
            </div>
            <h4 class="fw-bold text-success mb-0">{{ $stats['active_orders'] }}</h4>
            <small class="text-secondary">Dalam proses persiapan & acara</small>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card p-3 border rounded-3 bg-white shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-semibold">Survei Lapangan Aktif</span>
                <span class="p-2 rounded bg-light"><i class="bi bi-geo-alt fs-5 text-black"></i></span>
            </div>
            <h4 class="fw-bold text-black mb-0">{{ $stats['active_surveys_count'] }}</h4>
            <small class="text-secondary">Menunggu / dijadwalkan</small>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Pesanan Terbaru -->
    <div class="col-lg-7">
        <div class="card p-4 border rounded-4 bg-white shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-black mb-0">Pesanan Terbaru</h6>
                <a href="{{ route('admin.orders') }}" class="small text-secondary text-decoration-none">Lihat Semua →</a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>No. Order</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr>
                                <td class="font-monospace fw-bold">{{ $order->order_number }}</td>
                                <td>{{ $order->user->name ?? '-' }}</td>
                                <td class="fw-semibold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ strtoupper($order->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-outline-dark btn-sm py-0 px-2 rounded">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-3">Belum ada pesanan masuk.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Jadwal Acara Terdekat -->
    <div class="col-lg-5">
        <div class="card p-4 border rounded-4 bg-white shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-black mb-0">Jadwal Acara Terdekat</h6>
                <a href="{{ route('admin.events') }}" class="small text-secondary text-decoration-none">Lihat Semua →</a>
            </div>

            <div class="list-group list-group-flush">
                @forelse($upcomingEvents as $evt)
                    <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold text-black mb-0 small">{{ $evt->event_name }}</h6>
                            <small class="text-secondary">{{ $evt->user->name ?? '-' }} &bull; {{ $evt->event_type }}</small>
                        </div>
                        <span class="badge bg-dark small">
                            {{ \Carbon\Carbon::parse($evt->event_date)->format('d M') }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-secondary py-3 small">Tidak ada acara mendatang.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Inventaris Stok Rendah -->
    <div class="col-lg-6">
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-black mb-0">Inventaris Stok Terbatas / Menipis</h6>
                <a href="{{ route('admin.inventories') }}" class="small text-secondary text-decoration-none">Kelola →</a>
            </div>

            <ul class="list-group list-group-flush">
                @forelse($lowStockInventories as $inv)
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold text-black small d-block">{{ $inv->name }}</span>
                            <small class="text-secondary">{{ $inv->category->name }}</small>
                        </div>
                        <span class="badge {{ $inv->stock <= 5 ? 'bg-danger' : 'bg-warning text-dark' }} small">
                            Tersisa: {{ $inv->stock }} {{ $inv->unit }}
                        </span>
                    </li>
                @empty
                    <li class="list-group-item px-0 py-3 text-center text-secondary small">Seluruh stok inventaris dalam kondisi aman.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Survei Terbaru -->
    <div class="col-lg-6">
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-black mb-0">Pengajuan Survei Lokasi Terbaru</h6>
                <a href="{{ route('admin.surveys') }}" class="small text-secondary text-decoration-none">Kelola →</a>
            </div>

            <ul class="list-group list-group-flush">
                @forelse($recentSurveys as $srv)
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold text-black small d-block">{{ $srv->event_name }}</span>
                            <small class="text-secondary">{{ $srv->user->name ?? '-' }} &bull; {{ \Carbon\Carbon::parse($srv->event_date)->format('d M Y') }}</small>
                        </div>
                        <span class="badge {{ $srv->status === 'menunggu' ? 'bg-warning text-dark' : 'bg-dark' }} small">
                            {{ strtoupper($srv->status) }}
                        </span>
                    </li>
                @empty
                    <li class="list-group-item px-0 py-3 text-center text-secondary small">Belum ada pengajuan survei.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
