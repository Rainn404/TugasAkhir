@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
        <div>
            <span class="page-eyebrow">Reservasi Anda</span>
            <h1 class="page-headline mb-1">Keranjang Pemesanan Perlengkapan</h1>
            <p class="page-subtext">Periksa rincian perlengkapan dan pastikan jadwal acara Anda sudah sesuai.</p>
        </div>
        <div>
            <a href="{{ route('home') }}#katalog" class="btn-hero-secondary">
                <i class="bi bi-plus-lg me-1"></i> Tambah Perlengkapan Lain
            </a>
        </div>
    </div>

    @if(empty($cart))
        <!-- Empty State -->
        <div class="editorial-card text-center py-5 my-4">
            <div class="p-4 p-md-5">
                <i class="bi bi-cart-x text-secondary opacity-50 mb-3 d-block fs-1"></i>
                <h3 class="fw-bold text-black mb-2 font-display">Keranjang Pemesanan Masih Kosong</h3>
                <p class="text-secondary small max-w-md mx-auto mb-4" style="max-width: 480px;">
                    Anda belum memilih perlengkapan untuk acara Anda. Silakan jelajahi katalog dan sesuaikan konfigurasi tenda, kursi, meja, atau panggung yang dibutuhkan.
                </p>
                <a href="{{ route('home') }}#katalog" class="btn-hero-primary">
                    <i class="bi bi-grid me-2"></i> Lihat Katalog Perlengkapan
                </a>
            </div>
        </div>
    @else
        <div class="row g-4">
            <!-- Kolom Kiri: Daftar Item Keranjang -->
            <div class="col-lg-8">
                <!-- Banner Jadwal Acara -->
                <div class="editorial-card p-3 p-md-4 mb-4" style="background-color: #FAF8F5; border: 1px solid #DDD8D0;">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 44px; height: 44px; border: 1px solid #DDD8D0;">
                                <i class="bi bi-calendar3 fs-5 text-black"></i>
                            </div>
                            <div>
                                <small class="text-secondary d-block fw-semibold text-uppercase tracking-wider" style="font-size: 0.75rem;">Tanggal Pelaksanaan Acara</small>
                                <span class="fw-bold text-black fs-6">
                                    {{ \Carbon\Carbon::parse($cartDate)->translatedFormat('l, d F Y') }}
                                </span>
                            </div>
                        </div>
                        <!-- Form Ubah Tanggal Button -->
                        <button class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#changeDateCollapse">
                            <i class="bi bi-pencil me-1"></i> Ubah Tanggal
                        </button>
                    </div>

                    <div class="collapse mt-3" id="changeDateCollapse">
                        <div class="p-3 bg-white rounded-3 border" style="border-color: #DDD8D0 !important;">
                            <form action="{{ route('cart.update-date') }}" method="POST" class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                                @csrf
                                <input type="date" name="event_date" value="{{ $cartDate }}" min="{{ date('Y-m-d') }}" class="form-control form-control-sm rounded-pill px-3" required>
                                <button type="submit" class="btn btn-dark btn-sm rounded-pill px-4 py-2 text-nowrap">Simpan &amp; Validasi Ulang</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Table Card List Perlengkapan -->
                <div class="editorial-card overflow-hidden">
                    <div class="table-responsive">
                        <table class="editorial-table align-middle">
                            <thead>
                                <tr>
                                    <th>Perlengkapan &amp; Konfigurasi</th>
                                    <th class="text-center">Harga Satuan</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart as $key => $item)
                                    <tr>
                                        <td>
                                            <h6 class="fw-bold text-black mb-1">{{ $item['name'] }}</h6>
                                            @if(!empty($item['variant_name']))
                                                <span class="badge-status badge-status--neutral" style="font-size: 0.75rem;">
                                                    {{ $item['variant_name'] }}
                                                </span>
                                            @else
                                                <small class="text-secondary">Standar</small>
                                            @endif
                                        </td>
                                        <td class="text-center text-secondary small">
                                            Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-center" style="min-width: 140px;">
                                            <form action="{{ route('cart.update', $key) }}" method="POST" class="d-flex align-items-center justify-content-center gap-1">
                                                @csrf
                                                <div class="qty-input-group">
                                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" class="form-control form-control-sm text-center fw-bold" style="width: 54px;" aria-label="Jumlah {{ $item['name'] }}">
                                                    <button type="submit" class="btn" title="Perbarui Jumlah" aria-label="Perbarui jumlah {{ $item['name'] }}">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="text-end fw-bold text-black">
                                            Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('cart.remove', $key) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle p-2" onclick="return confirm('Hapus item ini dari keranjang?')" title="Hapus Item" aria-label="Hapus {{ $item['name'] }} dari keranjang" style="width: 34px; height: 34px;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Ringkasan & Checkout -->
            <div class="col-lg-4">
                <div class="editorial-card p-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display" style="border-color: #E7E2DA !important;">Ringkasan Biaya</h5>

                    <div class="d-flex justify-content-between text-secondary small mb-2">
                        <span>Total Item Perlengkapan</span>
                        <span class="text-black fw-semibold">{{ count($cart) }} Item</span>
                    </div>

                    <div class="d-flex justify-content-between text-secondary small mb-3">
                        <span>Tanggal Pelaksanaan</span>
                        <span class="text-black fw-semibold">{{ \Carbon\Carbon::parse($cartDate)->format('d/m/Y') }}</span>
                    </div>

                    <div class="d-flex justify-content-between fw-bold text-black border-top pt-3 fs-5 mb-4" style="border-color: #E7E2DA !important;">
                        <span>Total Sewa</span>
                        <span class="text-black">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>

                    <div class="p-3 rounded-3 small text-secondary mb-4" style="background: #FAF8F5; border: 1px solid #DDD8D0;">
                        <i class="bi bi-shield-check text-black me-1"></i>
                        Stok dijamin aman dan terkunci pada jadwal yang telah Anda pilih.
                    </div>

                    <a href="{{ route('checkout') }}" class="btn-hero-primary w-100 py-3 justify-content-center text-center">
                        <span>Lanjut ke Checkout</span> <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
