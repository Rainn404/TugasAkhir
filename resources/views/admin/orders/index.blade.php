@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Manajemen Pesanan</h4>
        <p class="text-secondary small mb-0">Kelola dan pantau seluruh transaksi penyewaan perlengkapan acara.</p>
    </div>
</div>

<!-- Filter Box -->
<div class="card p-3 border rounded-3 bg-white mb-4 shadow-sm">
    <form action="{{ route('admin.orders') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <select name="status" class="form-select form-select-sm rounded-2">
                <option value="">-- Semua Status Pesanan --</option>
                <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Dibayar</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                <option value="preparing" {{ request('status') === 'preparing' ? 'selected' : '' }}>Disiapkan</option>
                <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Sedang Digunakan</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm rounded-2" placeholder="Tanggal Acara">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-dark btn-sm rounded-2 w-100">Filter Data</button>
            <a href="{{ route('admin.orders') }}" class="btn btn-outline-secondary btn-sm rounded-2">Reset</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">No. Pesanan</th>
                    <th class="py-3 px-3">Pelanggan</th>
                    <th class="py-3 px-3">Nama Acara</th>
                    <th class="py-3 px-3">Tanggal Acara</th>
                    <th class="py-3 px-3 text-end">Total Biaya</th>
                    <th class="py-3 px-3 text-center">Status</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="py-3 px-4">
                            <span class="font-monospace fw-bold text-black small">{{ $order->order_number }}</span>
                            <small class="text-secondary d-block">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td class="py-3 px-3">
                            <span class="fw-semibold text-black small d-block">{{ $order->user->name ?? '-' }}</span>
                            <small class="text-secondary">{{ $order->user->phone ?? '-' }}</small>
                        </td>
                        <td class="py-3 px-3">
                            <span class="fw-semibold text-black small d-block">{{ $order->event->event_name ?? '-' }}</span>
                            <small class="text-secondary">{{ $order->event->event_type ?? '-' }}</small>
                        </td>
                        <td class="py-3 px-3 small text-secondary">
                            {{ $order->event ? \Carbon\Carbon::parse($order->event->event_date)->format('d M Y') : '-' }}
                        </td>
                        <td class="py-3 px-3 text-end fw-bold text-black small">
                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            @php
                                $bStyles = [
                                    'pending_payment' => 'badge-limited',
                                    'paid' => 'badge-available',
                                    'confirmed' => 'badge-available',
                                    'preparing' => 'bg-info text-dark',
                                    'ongoing' => 'bg-primary text-white',
                                    'completed' => 'bg-dark text-white',
                                    'cancelled' => 'badge-unavailable',
                                ];
                            @endphp
                            <span class="badge {{ $bStyles[$order->status] ?? 'bg-light text-dark' }} small">
                                {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-outline-dark btn-sm rounded-2">
                                Kelola Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-1 opacity-50"></i>
                            Tidak ada data pesanan yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="p-3 border-top">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
