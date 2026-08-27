@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Laporan Transaksi Pembayaran</h4>
        <p class="text-secondary small mb-0">Rekapitulasi status penerimaan pembayaran melalui Payment Gateway.</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-2 d-print-none">
        <i class="bi bi-printer me-1"></i> Cetak Laporan
    </button>
</div>

<!-- Filter Periode & Status (Hidden on Print) -->
<div class="card p-3 border rounded-3 bg-white mb-4 shadow-sm d-print-none">
    <form action="{{ route('admin.reports.payments') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm rounded-2">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm rounded-2">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Status Pembayaran</label>
            <select name="payment_status" class="form-select form-select-sm rounded-2">
                <option value="">-- Semua Status --</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid (Lunas)</option>
                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="expired" {{ request('payment_status') === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2 align-items-end" style="height: 100%;">
            <button type="submit" class="btn btn-dark btn-sm rounded-2 w-100 mt-auto py-1.5">Filter Data</button>
            <a href="{{ route('admin.reports.payments') }}" class="btn btn-outline-secondary btn-sm rounded-2 mt-auto py-1.5">Reset</a>
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
<div class="card p-3 border rounded-3 bg-white mb-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="text-secondary small d-block">Total Pembayaran Berhasil (Paid):</span>
            <h4 class="fw-bold text-success mb-0">Rp {{ number_format($totalPaid, 0, ',', '.') }}</h4>
        </div>
        <div>
            <span class="badge bg-dark small">{{ count($payments) }} Transaksi Terdata</span>
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
                    <th class="py-3 px-3">Tanggal Transaksi</th>
                    <th class="py-3 px-3">Pelanggan</th>
                    <th class="py-3 px-3">Metode Bayar</th>
                    <th class="py-3 px-3 text-end">Jumlah Tagihan</th>
                    <th class="py-3 px-3 text-center">Status</th>
                    <th class="py-3 px-4">Waktu Lunas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $pmt)
                    <tr>
                        <td class="py-3 px-4 font-monospace fw-bold">{{ $pmt->order->order_number ?? '-' }}</td>
                        <td class="py-3 px-3 text-secondary">{{ $pmt->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-3 px-3 fw-semibold">{{ $pmt->order->user->name ?? '-' }}</td>
                        <td class="py-3 px-3 font-monospace">{{ strtoupper($pmt->payment_type ?? 'Midtrans') }}</td>
                        <td class="py-3 px-3 text-end fw-bold">Rp {{ number_format($pmt->gross_amount, 0, ',', '.') }}</td>
                        <td class="py-3 px-3 text-center">
                            @php
                                $pBadge = [
                                    'pending' => 'bg-warning text-dark',
                                    'paid' => 'bg-success text-white',
                                    'failed' => 'bg-danger text-white',
                                    'expired' => 'bg-secondary text-white',
                                ];
                            @endphp
                            <span class="badge {{ $pBadge[$pmt->payment_status] ?? 'bg-light text-dark' }}">
                                {{ strtoupper($pmt->payment_status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-secondary">
                            {{ $pmt->paid_at ? \Carbon\Carbon::parse($pmt->paid_at)->format('d/m/Y H:i') : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            Tidak ada data transaksi pembayaran pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
