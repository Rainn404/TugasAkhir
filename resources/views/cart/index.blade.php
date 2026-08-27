@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="text-uppercase text-secondary fw-bold small tracking-wider">Reservasi Anda</span>
            <h2 class="fw-bold text-black mb-0">Keranjang Pemesanan Perlengkapan</h2>
        </div>
        <a href="{{ route('home') }}#katalog" class="btn btn-outline-dark btn-sm rounded-3">
            <i class="bi bi-plus-lg me-1"></i> Tambah Perlengkapan Lain
        </a>
    </div>

    @if(empty($cart))
        <!-- Empty State -->
        <div class="card text-center py-5 border rounded-4 bg-white shadow-sm my-4">
            <div class="p-4">
                <i class="bi bi-cart-x text-secondary opacity-50 mb-3 d-block" style="font-size: 4rem;"></i>
                <h4 class="fw-bold text-black mb-2">Keranjang Pemesanan Masih Kosong</h4>
                <p class="text-secondary small max-w-md mx-auto mb-4">
                    Anda belum memilih perlengkapan untuk acara Anda. Silakan jelajahi katalog dan sesuaikan konfigurasi tenda, kursi, atau panggung yang dibutuhkan.
                </p>
                <a href="{{ route('home') }}#katalog" class="btn btn-dark px-4 py-2.5 rounded-3 fw-semibold">
                    <i class="bi bi-grid me-2"></i> Lihat Katalog Perlengkapan
                </a>
            </div>
        </div>
    @else
        <div class="row g-4">
            <!-- Kolom Kiri: Daftar Item Keranjang -->
            <div class="col-lg-8">
                <!-- Banner Jadwal Acara -->
                <div class="card p-3 border rounded-3 bg-white mb-3 d-flex flex-row justify-content-between align-items-center">
                    <div>
                        <small class="text-secondary d-block fw-semibold">Tanggal Pelaksanaan Acara</small>
                        <span class="fw-bold text-black fs-6">
                            <i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::parse($cartDate)->translatedFormat('l, d F Y') }}
                        </span>
                    </div>
                    <!-- Form Ubah Tanggal -->
                    <button class="btn btn-outline-dark btn-sm rounded-2" type="button" data-bs-toggle="collapse" data-bs-target="#changeDateCollapse">
                        <i class="bi bi-pencil me-1"></i> Ubah Tanggal
                    </button>
                </div>

                <div class="collapse mb-3" id="changeDateCollapse">
                    <div class="card p-3 border rounded-3 bg-light">
                        <form action="{{ route('cart.update-date') }}" method="POST" class="d-flex gap-2 align-items-center">
                            @csrf
                            <input type="date" name="event_date" value="{{ $cartDate }}" min="{{ date('Y-m-d') }}" class="form-control form-control-sm rounded-2" required>
                            <button type="submit" class="btn btn-dark btn-sm rounded-2 text-nowrap">Simpan & Validasi Ulang</button>
                        </form>
                    </div>
                </div>

                <!-- Card List Perlengkapan -->
                <div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light border-bottom">
                                <tr>
                                    <th class="py-3 px-4 text-secondary small fw-bold">Perlengkapan & Konfigurasi</th>
                                    <th class="py-3 px-3 text-secondary small fw-bold text-center">Harga Satuan</th>
                                    <th class="py-3 px-3 text-secondary small fw-bold text-center">Jumlah</th>
                                    <th class="py-3 px-4 text-secondary small fw-bold text-end">Subtotal</th>
                                    <th class="py-3 px-3 text-secondary small fw-bold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart as $key => $item)
                                    <tr>
                                        <td class="py-3 px-4">
                                            <h6 class="fw-bold text-black mb-1">{{ $item['name'] }}</h6>
                                            @if(!empty($item['variant_name']))
                                                <span class="badge bg-light text-black border small fw-normal">
                                                    {{ $item['variant_name'] }}
                                                </span>
                                            @else
                                                <small class="text-secondary">Standar</small>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 text-center text-secondary small">
                                            Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-3 text-center" style="min-width: 140px;">
                                            <form action="{{ route('cart.update', $key) }}" method="POST" class="d-flex align-items-center justify-content-center gap-1">
                                                @csrf
                                                <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" class="form-control form-control-sm text-center fw-bold" style="width: 60px;">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm px-2 py-1" title="Update Qty">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="py-3 px-4 text-end fw-bold text-black">
                                            Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <form action="{{ route('cart.remove', $key) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm border-0 rounded-2" onclick="return confirm('Hapus item ini dari keranjang?')" title="Hapus Item">
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
                <div class="card p-4 border rounded-4 bg-white shadow-sm sticky-top" style="top: 90px;">
                    <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">Ringkasan Biaya</h5>

                    <div class="d-flex justify-content-between text-secondary small mb-2">
                        <span>Total Item Perlengkapan</span>
                        <span class="text-black fw-semibold">{{ count($cart) }} Item</span>
                    </div>

                    <div class="d-flex justify-content-between text-secondary small mb-3">
                        <span>Tanggal Pelaksanaan</span>
                        <span class="text-black fw-semibold">{{ \Carbon\Carbon::parse($cartDate)->format('d/m/Y') }}</span>
                    </div>

                    <div class="d-flex justify-content-between fw-bold text-black border-top pt-3 fs-5 mb-4">
                        <span>Total Sewa</span>
                        <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>

                    <div class="p-3 bg-light rounded-3 border small text-secondary mb-4">
                        <i class="bi bi-shield-check text-black me-1"></i>
                        Stok dijamin aman dan terkunci pada jadwal yang telah Anda pilih.
                    </div>

                    <a href="{{ route('checkout') }}" class="btn btn-dark btn-lg w-100 py-3 rounded-3 fw-bold shadow-sm">
                        Lanjut ke Checkout <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
