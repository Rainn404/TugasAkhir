@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
        <div>
            <span class="page-eyebrow">Riwayat Transaksi</span>
            <h1 class="page-headline mb-1">Daftar Pesanan Saya</h1>
            <p class="page-subtext">Semua riwayat pemesanan perlengkapan dan status pembayaran terkini.</p>
        </div>
        <div>
            <a href="{{ route('home') }}#katalog" class="btn-hero-primary">
                <i class="bi bi-cart-plus me-1"></i> Buat Pesanan Baru
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="dashboard-nav-pills" aria-label="Menu Dasbor Pelanggan">
        <a class="dashboard-pill" href="{{ route('dashboard.index') }}">
            <i class="bi bi-grid"></i> <span>Ringkasan</span>
        </a>
        <a class="dashboard-pill active" href="{{ route('dashboard.orders') }}">
            <i class="bi bi-receipt"></i> <span>Pesanan Saya</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.events') }}">
            <i class="bi bi-calendar-event"></i> <span>Kegiatan Acara</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.surveys') }}">
            <i class="bi bi-geo-alt"></i> <span>Survei Lokasi</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.profile') }}">
            <i class="bi bi-person"></i> <span>Profil Akun</span>
        </a>
    </nav>

    <!-- Filter Status Pills -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('dashboard.orders') }}" class="btn btn-sm {{ !request('status') ? 'btn-dark' : 'btn-outline-secondary' }} px-3 rounded-pill fw-semibold">Semua</a>
        <a href="{{ route('dashboard.orders', ['status' => 'pending_payment']) }}" class="btn btn-sm {{ request('status') === 'pending_payment' ? 'btn-dark' : 'btn-outline-secondary' }} px-3 rounded-pill fw-semibold">Menunggu Bayar</a>
        <a href="{{ route('dashboard.orders', ['status' => 'paid']) }}" class="btn btn-sm {{ request('status') === 'paid' ? 'btn-dark' : 'btn-outline-secondary' }} px-3 rounded-pill fw-semibold">Dibayar</a>
        <a href="{{ route('dashboard.orders', ['status' => 'confirmed']) }}" class="btn btn-sm {{ request('status') === 'confirmed' ? 'btn-dark' : 'btn-outline-secondary' }} px-3 rounded-pill fw-semibold">Dikonfirmasi</a>
        <a href="{{ route('dashboard.orders', ['status' => 'ongoing']) }}" class="btn btn-sm {{ request('status') === 'ongoing' ? 'btn-dark' : 'btn-outline-secondary' }} px-3 rounded-pill fw-semibold">Sedang Digunakan</a>
        <a href="{{ route('dashboard.orders', ['status' => 'completed']) }}" class="btn btn-sm {{ request('status') === 'completed' ? 'btn-dark' : 'btn-outline-secondary' }} px-3 rounded-pill fw-semibold">Selesai</a>
    </div>

    <!-- Orders Table -->
    <div class="editorial-card overflow-hidden">
        @if($orders->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-receipt fs-1 d-block mb-2" style="color: #77716A !important;"></i>
                <h5 class="fw-bold text-black font-display">Belum Ada Pesanan</h5>
                <p class="text-secondary small mb-3">Pilih perlengkapan yang Anda inginkan dari katalog kami.</p>
                <a href="{{ route('home') }}#katalog" class="btn-hero-primary">Lihat Katalog</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="editorial-table align-middle">
                    <thead>
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Nama Acara</th>
                            <th>Tanggal Acara</th>
                            <th class="text-end">Total Pembayaran</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>
                                    <span class="fw-bold text-black font-monospace small">{{ $order->order_number }}</span>
                                    <small class="text-secondary d-block">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td style="max-width: 220px;">
                                    <span class="fw-semibold text-black small d-block text-truncate" title="{{ $order->event->event_name ?? '-' }}">{{ $order->event->event_name ?? '-' }}</span>
                                    <small class="text-secondary text-truncate d-block">{{ $order->event->event_type ?? '-' }}</small>
                                </td>
                                <td class="small text-secondary">
                                    {{ \Carbon\Carbon::parse($order->event->event_date)->format('d M Y') }}
                                </td>
                                <td class="text-end fw-bold text-black small tabular-nums">
                                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
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
                                    <span class="badge-status {{ $badges[$order->status] ?? 'badge-status--neutral' }}" style="font-size: 0.72rem;">
                                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('dashboard.order', $order->order_number) }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1 text-nowrap">
                                        Detail &amp; Bayar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
