@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
    <div>
        <span class="page-eyebrow">Rekapitulasi Finansial</span>
        <h1 class="page-headline mb-1 font-display">Laporan Pesanan &amp; Penjualan</h1>
        <p class="page-subtext">Rekapitulasi transaksi pemesanan perlengkapan acara dan total omset masuk.</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1.5 d-print-none fw-semibold">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- Filter Periode & Status (Hidden on Print) -->
<div class="editorial-card p-3 p-md-4 mb-4 d-print-none">
    <form action="{{ route('admin.reports.orders') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control rounded-3">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control rounded-3">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Status Pesanan</label>
            <select name="status" class="form-select rounded-3">
                <option value="">-- Semua Status --</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid (Dibayar)</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>Menunggu Bayar</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2 align-items-end" style="height: 100%;">
            <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 w-100 fw-semibold mt-auto">Filter Data</button>
            <a href="{{ route('admin.reports.orders') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold mt-auto">Reset</a>
        </div>
    </form>
</div>

<!-- Header Cetak Khusus Print -->
<div class="d-none d-print-block text-center mb-4">
    <h3 class="fw-bold text-black mb-0">ARSY TENDA</h3>
    <p class="small text-secondary mb-1">Laporan Rekapitulasi Pemesanan Perlengkapan Acara</p>
    <small class="text-secondary">Dicetak pada: {{ date('d/m/Y H:i') }}</small>
    <hr>
</div>

<!-- Summary Card -->
<div class="editorial-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="stat-kicker">Total Pendapatan Valid:</span>
            <h3 class="stat-number mb-0">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
        </div>
        <div>
            <span class="badge-status badge-status--neutral">{{ count($orders) }} Transaksi Terdata</span>
        </div>
    </div>
</div>

<!-- Report Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Tanggal Pesan</th>
                    <th>Pelanggan</th>
                    <th>Nama Acara</th>
                    <th>Tanggal Acara</th>
                    <th class="text-end">Total Biaya</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="font-monospace fw-bold small text-black">{{ $order->order_number }}</td>
                        <td class="text-secondary small">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="fw-semibold small text-black">{{ $order->user->name ?? '-' }}</td>
                        <td class="small">{{ $order->event->event_name ?? '-' }}</td>
                        <td class="small text-secondary">{{ $order->event ? \Carbon\Carbon::parse($order->event->event_date)->format('d/m/Y') : '-' }}</td>
                        <td class="text-end fw-bold text-black small">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @php
                                $bStyles = [
                                    'pending_payment' => 'badge-status--warning',
                                    'paid' => 'badge-status--success',
                                    'confirmed' => 'badge-status--success',
                                    'preparing' => 'badge-status--info',
                                    'ongoing' => 'badge-status--neutral',
                                    'completed' => 'badge-status--success',
                                    'cancelled' => 'badge-status--danger',
                                ];
                            @endphp
                            <span class="badge-status {{ $bStyles[$order->status] ?? 'badge-status--neutral' }}" style="font-size: 0.7rem;">
                                {{ strtoupper($order->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            Tidak ada data pesanan pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
