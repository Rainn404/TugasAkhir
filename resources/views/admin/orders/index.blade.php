@extends('layouts.admin')

@section('content')
<div class="page-editorial-header mb-4">
    @if(auth()->user()->role === 'pekerja')
        <span class="page-eyebrow">Tugas Lapangan</span>
        @if(request('task') === 'pemeriksaan_kembali')
            <h1 class="page-headline mb-1 font-display">Pemeriksaan Barang</h1>
            <p class="page-subtext">Daftar pesanan dengan penugasan pemeriksaan kembali perlengkapan dan inventaris yang ditugaskan kepada Anda.</p>
        @elseif(request('status') === 'completed')
            <h1 class="page-headline mb-1 font-display">Riwayat Pekerjaan</h1>
            <p class="page-subtext">Daftar riwayat pesanan dan penugasan lapangan yang telah selesai dikerjakan.</p>
        @else
            <h1 class="page-headline mb-1 font-display">Tugas Saya</h1>
            <p class="page-subtext">Daftar pesanan aktif yang ditugaskan kepada Anda untuk penanganan operasional di lokasi acara.</p>
        @endif
    @else
        <span class="page-eyebrow">Manajemen Transaksi</span>
        <h1 class="page-headline mb-1 font-display">Manajemen Pesanan</h1>
        <p class="page-subtext">Kelola dan pantau seluruh transaksi penyewaan perlengkapan acara dan status pembayarannya.</p>
    @endif
</div>

<!-- Filter Box -->
<div class="editorial-card p-3 p-md-4 mb-4">
    <form action="{{ route('admin.orders') }}" method="GET" class="row g-2 align-items-center">
        @if(request()->filled('task'))
            <input type="hidden" name="task" value="{{ request('task') }}">
        @endif
        <div class="col-md-4">
            <select name="status" class="form-select rounded-3">
                <option value="">-- Semua Status Pesanan --</option>
                <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Dibayar</option>
                <option value="payment_conflict" {{ request('status') === 'payment_conflict' ? 'selected' : '' }}>Perlu Rekonsiliasi Pembayaran</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                <option value="preparing" {{ request('status') === 'preparing' ? 'selected' : '' }}>Disiapkan</option>
                <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Sedang Digunakan</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="date" name="date" value="{{ request('date') }}" class="form-control rounded-3" placeholder="Tanggal Acara">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 w-100 fw-semibold">Filter Data</button>
            <a href="{{ auth()->user()->role === 'pekerja' ? (request()->filled('task') ? route('admin.orders', ['task' => request('task')]) : (request('status') === 'completed' ? route('admin.orders', ['status' => 'completed']) : route('admin.orders'))) : route('admin.orders') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold">Reset</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Nama Acara</th>
                    <th>Tanggal Acara</th>
                    <th class="text-end">Total Biaya</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <span class="font-monospace fw-bold text-black small">{{ $order->order_number }}</span>
                            <small class="text-secondary d-block">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td>
                            <span class="fw-semibold text-black small d-block">{{ $order->user->name ?? '-' }}</span>
                            <small class="text-secondary">{{ $order->user->phone ?? '-' }}</small>
                        </td>
                        <td style="max-width: 220px;">
                            <span class="fw-semibold text-black small d-block text-truncate" title="{{ $order->event->event_name ?? '-' }}">{{ $order->event->event_name ?? '-' }}</span>
                            <small class="text-secondary text-truncate d-block">{{ $order->event->event_type ?? '-' }}</small>
                        </td>
                        <td class="small text-secondary">
                            {{ $order->event ? \Carbon\Carbon::parse($order->event->event_date)->format('d M Y') : '-' }}
                        </td>
                        <td class="text-end fw-bold text-black small tabular-nums">
                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        </td>
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
                                    'payment_conflict' => 'badge-status--danger',
                                ];
                            @endphp
                            <span class="badge-status {{ $bStyles[$order->status] ?? 'badge-status--neutral' }}" style="font-size: 0.7rem;">
                                {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1 text-nowrap" style="font-size: 0.8125rem;">
                                Kelola Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2" style="color: #77716A !important;"></i>
                            <span class="d-block small">Tidak ada data pesanan yang sesuai filter.</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
