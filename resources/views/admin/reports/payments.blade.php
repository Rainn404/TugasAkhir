@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
    <div>
        <span class="page-eyebrow">Rekapitulasi Gateway</span>
        <h1 class="page-headline mb-1 font-display">Laporan Transaksi Pembayaran</h1>
        <p class="page-subtext">Rekapitulasi status penerimaan pembayaran melalui Payment Gateway Midtrans.</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1.5 d-print-none fw-semibold">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- Filter Periode & Status (Hidden on Print) -->
<div class="editorial-card p-3 p-md-4 mb-4 d-print-none">
    <form action="{{ route('admin.reports.payments') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control rounded-3">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control rounded-3">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Status Pembayaran</label>
            <select name="payment_status" class="form-select rounded-3">
                <option value="">-- Semua Status --</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid (Lunas)</option>
                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="expired" {{ request('payment_status') === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2 align-items-end" style="height: 100%;">
            <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 w-100 fw-semibold mt-auto">Filter Data</button>
            <a href="{{ route('admin.reports.payments') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold mt-auto">Reset</a>
        </div>
    </form>
</div>

<!-- Header Cetak Khusus Print -->
<div class="d-none d-print-block text-center mb-4">
    <h3 class="fw-bold text-black mb-0">ARSY TENDA</h3>
    <p class="small text-secondary mb-1">Laporan Rekapitulasi Transaksi Pembayaran</p>
    <small class="text-secondary">Dicetak pada: {{ date('d/m/Y H:i') }}</small>
    <hr>
</div>

<!-- Summary Card -->
<div class="editorial-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="stat-kicker">Total Pembayaran Berhasil (Paid):</span>
            <h3 class="stat-number mb-0" style="color: #15803D;">Rp {{ number_format($totalPaid, 0, ',', '.') }}</h3>
        </div>
        <div>
            <span class="badge-status badge-status--neutral">{{ count($payments) }} Transaksi Terdata</span>
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
                    <th>Tanggal Transaksi</th>
                    <th>Pelanggan</th>
                    <th>Metode Pembayaran</th>
                    <th class="text-end">Jumlah Pembayaran</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $pmt)
                    <tr>
                        <td class="font-monospace fw-bold small text-black">
                            {{ $pmt->order->order_number ?? ('#' . $pmt->order_id) }}
                        </td>
                        <td class="text-secondary small">{{ $pmt->created_at->format('d/m/Y H:i') }}</td>
                        <td class="fw-semibold small text-black">{{ $pmt->order->user->name ?? '-' }}</td>
                        <td class="small font-monospace">{{ strtoupper($pmt->payment_type ?? 'Midtrans') }}</td>
                        <td class="text-end fw-bold text-black small">Rp {{ number_format($pmt->gross_amount, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @php
                                $pBadge = [
                                    'pending' => 'badge-status--warning',
                                    'paid' => 'badge-status--success',
                                    'failed' => 'badge-status--danger',
                                    'expired' => 'badge-status--neutral',
                                ];
                            @endphp
                            <span class="badge-status {{ $pBadge[$pmt->payment_status] ?? 'badge-status--neutral' }}" style="font-size: 0.7rem;">
                                {{ strtoupper($pmt->payment_status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            Tidak ada data transaksi pembayaran pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
