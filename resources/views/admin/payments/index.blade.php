@extends('layouts.admin')

@section('content')
<div class="page-editorial-header mb-4">
    <span class="page-eyebrow">Integrasi Gateway</span>
    <h1 class="page-headline mb-1 font-display">Manajemen Transaksi Pembayaran</h1>
    <p class="page-subtext">Pantau seluruh mutasi pembayaran digital melalui Payment Gateway Midtrans.</p>
</div>

<!-- Filter Status -->
<div class="editorial-card p-3 p-md-4 mb-4">
    <form action="{{ route('admin.payments') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-8">
            <select name="payment_status" class="form-select rounded-3">
                <option value="">-- Semua Status Pembayaran --</option>
                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending (Menunggu)</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid (Lunas)</option>
                <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Failed (Gagal)</option>
                <option value="expired" {{ request('payment_status') === 'expired' ? 'selected' : '' }}>Expired (Kedaluwarsa)</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 w-100 fw-semibold">Filter Data</button>
            <a href="{{ route('admin.payments') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold">Reset</a>
        </div>
    </form>
</div>

<!-- Payments Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Acara</th>
                    <th>Metode Bayar</th>
                    <th class="text-end">Jumlah Tagihan</th>
                    <th class="text-center">Status</th>
                    <th>Waktu Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $pmt)
                    <tr>
                        <td>
                            @if($pmt->order)
                                <a href="{{ route('admin.orders.show', $pmt->order->id) }}" class="fw-bold text-black font-monospace small text-decoration-none">
                                    {{ $pmt->order->order_number }}
                                </a>
                            @else
                                <span class="text-secondary small font-monospace">Order #{{ $pmt->order_id }}</span>
                            @endif
                        </td>
                        <td class="small">
                            {{ $pmt->order->user->name ?? '-' }}
                        </td>
                        <td class="small">
                            {{ $pmt->order->event->event_name ?? '-' }}
                        </td>
                        <td class="small font-monospace">
                            {{ strtoupper($pmt->payment_type ?? 'Midtrans Gateway') }}
                        </td>
                        <td class="text-end fw-bold text-black small">
                            Rp {{ number_format($pmt->gross_amount, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @php
                                $pBadge = [
                                    'pending' => 'badge-status--warning',
                                    'paid' => 'badge-status--success',
                                    'failed' => 'badge-status--danger',
                                    'expired' => 'badge-status--neutral',
                                ];
                            @endphp
                            <span class="badge-status {{ $pBadge[$pmt->payment_status] ?? 'badge-status--neutral' }}" style="font-size: 0.72rem;">
                                {{ strtoupper($pmt->payment_status) }}
                            </span>
                        </td>
                        <td class="small text-secondary">
                            {{ $pmt->paid_at ? \Carbon\Carbon::parse($pmt->paid_at)->format('d/m/Y H:i') : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="bi bi-credit-card fs-2 d-block mb-1 opacity-50"></i>
                            Tidak ada data transaksi pembayaran.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
