@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <span class="text-uppercase text-secondary fw-bold small tracking-wider">Riwayat Transaksi</span>
            <h2 class="fw-bold text-black mb-1">Daftar Pesanan Saya</h2>
        </div>
        <a href="{{ route('home') }}#katalog" class="btn btn-dark btn-sm rounded-3">
            <i class="bi bi-cart-plus me-1"></i> Buat Pesanan Baru
        </a>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills gap-2 mb-4 border-bottom pb-3">
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.index') }}">
                <i class="bi bi-grid me-1"></i> Ringkasan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active bg-black rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.orders') }}">
                <i class="bi bi-receipt me-1"></i> Pesanan Saya
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.events') }}">
                <i class="bi bi-calendar-event me-1"></i> Kegiatan Acara
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.surveys') }}">
                <i class="bi bi-geo-alt me-1"></i> Survei Lokasi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.profile') }}">
                <i class="bi bi-person me-1"></i> Profil Akun
            </a>
        </li>
    </ul>

    <!-- Filter Status -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('dashboard.orders') }}" class="btn btn-sm {{ !request('status') ? 'btn-dark' : 'btn-light border' }} px-3 rounded-pill">Semua</a>
        <a href="{{ route('dashboard.orders', ['status' => 'pending_payment']) }}" class="btn btn-sm {{ request('status') === 'pending_payment' ? 'btn-dark' : 'btn-light border' }} px-3 rounded-pill">Menunggu Bayar</a>
        <a href="{{ route('dashboard.orders', ['status' => 'paid']) }}" class="btn btn-sm {{ request('status') === 'paid' ? 'btn-dark' : 'btn-light border' }} px-3 rounded-pill">Dibayar</a>
        <a href="{{ route('dashboard.orders', ['status' => 'confirmed']) }}" class="btn btn-sm {{ request('status') === 'confirmed' ? 'btn-dark' : 'btn-light border' }} px-3 rounded-pill">Dikonfirmasi</a>
        <a href="{{ route('dashboard.orders', ['status' => 'ongoing']) }}" class="btn btn-sm {{ request('status') === 'ongoing' ? 'btn-dark' : 'btn-light border' }} px-3 rounded-pill">Sedang Digunakan</a>
        <a href="{{ route('dashboard.orders', ['status' => 'completed']) }}" class="btn btn-sm {{ request('status') === 'completed' ? 'btn-dark' : 'btn-light border' }} px-3 rounded-pill">Selesai</a>
    </div>

    <!-- Orders Table -->
    <div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
        @if($orders->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-receipt text-secondary opacity-50 fs-1 d-block mb-2"></i>
                <h5 class="fw-bold text-black">Belum Ada Pesanan</h5>
                <p class="text-secondary small mb-3">Pilih perlengkapan yang Anda inginkan dari katalog kami.</p>
                <a href="{{ route('home') }}#katalog" class="btn btn-dark btn-sm rounded-3">Lihat Katalog</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="py-3 px-4 text-secondary small fw-bold">Nomor Pesanan</th>
                            <th class="py-3 px-3 text-secondary small fw-bold">Nama Acara</th>
                            <th class="py-3 px-3 text-secondary small fw-bold">Tanggal Acara</th>
                            <th class="py-3 px-3 text-secondary small fw-bold text-end">Total Pembayaran</th>
                            <th class="py-3 px-3 text-secondary small fw-bold text-center">Status</th>
                            <th class="py-3 px-4 text-secondary small fw-bold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td class="py-3 px-4">
                                    <span class="fw-bold text-black font-monospace small">{{ $order->order_number }}</span>
                                    <small class="text-secondary d-block">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="fw-semibold text-black small d-block">{{ $order->event->event_name ?? '-' }}</span>
                                    <small class="text-secondary">{{ $order->event->event_type ?? '-' }}</small>
                                </td>
                                <td class="py-3 px-3 small text-secondary">
                                    {{ \Carbon\Carbon::parse($order->event->event_date)->format('d M Y') }}
                                </td>
                                <td class="py-3 px-3 text-end fw-bold text-black small">
                                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @php
                                        $badges = [
                                            'pending_payment' => 'badge-limited',
                                            'paid' => 'badge-available',
                                            'confirmed' => 'badge-available',
                                            'preparing' => 'bg-info text-dark',
                                            'ongoing' => 'bg-primary text-white',
                                            'completed' => 'badge-available',
                                            'cancelled' => 'badge-unavailable',
                                        ];
                                    @endphp
                                    <span class="badge {{ $badges[$order->status] ?? 'bg-light text-dark' }} small">
                                        {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="{{ route('dashboard.order', $order->order_number) }}" class="btn btn-outline-dark btn-sm rounded-2">
                                        Detail & Pembayaran
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div class="p-3 border-top">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
