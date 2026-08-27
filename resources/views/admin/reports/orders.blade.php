@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Laporan Pesanan & Penjualan</h4>
        <p class="text-secondary small mb-0">Rekapitulasi transaksi pemesanan perlengkapan acara.</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-2 d-print-none">
        <i class="bi bi-printer me-1"></i> Cetak Laporan
    </button>
</div>

<!-- Filter Periode & Status (Hidden on Print) -->
<div class="card p-3 border rounded-3 bg-white mb-4 shadow-sm d-print-none">
    <form action="{{ route('admin.reports.orders') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm rounded-2">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm rounded-2">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Status Pesanan</label>
            <select name="status" class="form-select form-select-sm rounded-2">
                <option value="">-- Semua Status --</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid (Dibayar)</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>Menunggu Bayar</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2 align-items-end" style="height: 100%;">
            <button type="submit" class="btn btn-dark btn-sm rounded-2 w-100 mt-auto py-1.5">Filter Data</button>
            <a href="{{ route('admin.reports.orders') }}" class="btn btn-outline-secondary btn-sm rounded-2 mt-auto py-1.5">Reset</a>
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
<div class="card p-3 border rounded-3 bg-white mb-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-secondary small d-block">Total Pendapatan Valid:</span>
            <h4 class="fw-bold text-black mb-0">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h4>
        </div>
        <div>
            <span class="badge bg-dark small">{{ count($orders) }} Transaksi Terdata</span>
        </div>
    </div>
</div>

<!-- Report Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">No. Pesanan</th>
                    <th class="py-3 px-3">Tanggal Pesan</th>
                    <th class="py-3 px-3">Pelanggan</th>
                    <th class="py-3 px-3">Nama Acara</th>
                    <th class="py-3 px-3">Tanggal Acara</th>
                    <th class="py-3 px-3 text-end">Total Biaya</th>
                    <th class="py-3 px-4 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="py-3 px-4 font-monospace fw-bold">{{ $order->order_number }}</td>
                        <td class="py-3 px-3 text-secondary">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-3 fw-semibold">{{ $order->user->name ?? '-' }}</td>
                        <td class="py-3 px-3">{{ $order->event->event_name ?? '-' }}</td>
                        <td class="py-3 px-3">{{ $order->event ? \Carbon\Carbon::parse($order->event->event_date)->format('d/m/Y') : '-' }}</td>
                        <td class="py-3 px-3 text-end fw-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="badge bg-light text-dark border">{{ strtoupper($order->status) }}</span>
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
