@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Manajemen Transaksi Pembayaran</h4>
        <p class="text-secondary small mb-0">Pantau seluruh catatan transaksi dan integrasi Payment Gateway Midtrans.</p>
    </div>
</div>

<!-- Filter Status -->
<div class="card p-3 border rounded-3 bg-white mb-4 shadow-sm">
    <form action="{{ route('admin.payments') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-8">
            <select name="payment_status" class="form-select form-select-sm rounded-2">
                <option value="">-- Semua Status Pembayaran --</option>
                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid (Lunas)</option>
                <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Failed (Gagal)</option>
                <option value="expired" {{ request('payment_status') === 'expired' ? 'selected' : '' }}>Expired (Kadaluarsa)</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-dark btn-sm rounded-2 w-100">Filter Data</button>
            <a href="{{ route('admin.payments') }}" class="btn btn-outline-secondary btn-sm rounded-2">Reset</a>
        </div>
    </form>
</div>

<!-- Payments Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">No. Pesanan</th>
                    <th class="py-3 px-3">Pelanggan</th>
                    <th class="py-3 px-3">Acara</th>
                    <th class="py-3 px-3">Metode Bayar</th>
                    <th class="py-3 px-3 text-end">Jumlah Tagihan</th>
                    <th class="py-3 px-3 text-center">Status</th>
                    <th class="py-3 px-4">Waktu Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $pmt)
                    <tr>
                        <td class="py-3 px-4">
                            @if($pmt->order)
                                <a href="{{ route('admin.orders.show', $pmt->order->id) }}" class="fw-bold text-black font-monospace small text-decoration-none">
                                    {{ $pmt->order->order_number }}
                                </a>
                            @else
                                <span class="text-secondary small font-monospace">Order #{{ $pmt->order_id }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 small">
                            {{ $pmt->order->user->name ?? '-' }}
                        </td>
                        <td class="py-3 px-3 small">
                            {{ $pmt->order->event->event_name ?? '-' }}
                        </td>
                        <td class="py-3 px-3 small font-monospace">
                            {{ strtoupper($pmt->payment_type ?? 'Midtrans Gateway') }}
                        </td>
                        <td class="py-3 px-3 text-end fw-bold text-black small">
                            Rp {{ number_format($pmt->gross_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            @php
                                $pBadge = [
                                    'pending' => 'bg-warning text-dark',
                                    'paid' => 'bg-success text-white',
                                    'failed' => 'bg-danger text-white',
                                    'expired' => 'bg-secondary text-white',
                                ];
                            @endphp
                            <span class="badge {{ $pBadge[$pmt->payment_status] ?? 'bg-light text-dark' }} small">
                                {{ strtoupper($pmt->payment_status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 small text-secondary">
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
        <div class="p-3 border-top">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
