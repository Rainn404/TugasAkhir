@extends('layouts.app')

@section('content')

{{-- HERO SECTION --}}
<section class="py-5 my-2">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-black text-white px-3 py-2 rounded-pill small mb-3 tracking-wider text-uppercase">
                    ✦ Layanan Persewaan Perlengkapan Acara Profesional
                </span>
                <h1 class="display-4 fw-black text-black tracking-tight mb-3">
                    Solusi Perlengkapan Acara untuk Berbagai Kebutuhan
                </h1>
                <p class="lead text-secondary mb-4" style="font-size: 1.15rem;">
                    Penyewaan tenda, panggung, meja, kursi, dan blower berkualitas tinggi. Sistem penjadwalan cerdas untuk memastikan ketersediaan inventaris tanpa bentrokan jadwal.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#katalog" class="btn btn-dark btn-lg px-4 py-3 rounded-3 fw-semibold">
                        <i class="bi bi-grid-fill me-2"></i> Pesan Sekarang
                    </a>
                    <a href="{{ route('survey.create') }}" class="btn btn-outline-dark btn-lg px-4 py-3 rounded-3 fw-semibold">
                        <i class="bi bi-geo-alt-fill me-2"></i> Ajukan Survei Lokasi
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card p-4 shadow-sm border bg-white rounded-4">
                    <div class="d-flex align-items-center gap-3 mb-3 border-bottom pb-3">
                        <div class="bg-black text-white p-3 rounded-3">
                            <i class="bi bi-calendar-check fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Cek Ketersediaan Cepat</h6>
                            <small class="text-secondary">Pilih tanggal untuk melihat stok inventaris</small>
                        </div>
                    </div>
                    <form action="{{ route('home') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Acara Anda</label>
                            <input type="date" name="event_date" value="{{ request('event_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" class="form-control form-control-lg rounded-3 fs-6">
                        </div>
                        <button type="submit" class="btn btn-dark w-100 py-2.5 rounded-3 fw-semibold">
                            Tampilkan Perlengkapan Tersedia
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- KATALOG PERLENGKAPAN --}}
<section id="katalog" class="py-5 border-top bg-white">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
            <div>
                <span class="text-uppercase text-secondary fw-bold small tracking-wider">Katalog Inventaris</span>
                <h2 class="fw-bold text-black mb-1">Perlengkapan Acara</h2>
                <p class="text-secondary small mb-0">Pilih dan sesuaikan konfigurasi perlengkapan sesuai kapasitas acara Anda.</p>
            </div>
            <!-- Search Input -->
            <form action="{{ route('home') }}" method="GET" class="d-flex gap-2">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="input-group">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control rounded-start-3" placeholder="Cari perlengkapan...">
                    <button class="btn btn-outline-dark rounded-end-3" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Kategori Tabs -->
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="{{ route('home') }}#katalog"
               class="btn btn-sm {{ !request('category') ? 'btn-dark' : 'btn-light border' }} px-3 py-2 rounded-pill fw-semibold">
                Semua Perlengkapan
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('home', ['category' => $cat->slug]) }}#katalog"
                   class="btn btn-sm {{ request('category') === $cat->slug ? 'btn-dark' : 'btn-light border' }} px-3 py-2 rounded-pill fw-semibold">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>

        <!-- Inventory Grid -->
        @if($inventories->isEmpty())
            <div class="text-center py-5 my-4 border rounded-4 bg-light">
                <i class="bi bi-box-seam text-secondary fs-1 mb-2 d-block"></i>
                <h5 class="fw-bold text-black">Perlengkapan Tidak Ditemukan</h5>
                <p class="text-secondary small">Coba ubah kata kunci pencarian atau filter kategori lainnya.</p>
                <a href="{{ route('home') }}#katalog" class="btn btn-dark btn-sm rounded-3 mt-2">Reset Filter</a>
            </div>
        @else
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
                @foreach($inventories as $item)
                    <div class="col">
                        <div class="card h-100 rounded-3 overflow-hidden border">
                            <!-- Image / Thumbnail -->
                            <div class="position-relative bg-light border-bottom d-flex align-items-center justify-content-center" style="height: 200px;">
                                @if($item->image)
                                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="w-100 h-100 object-fit-cover">
                                @else
                                    <div class="text-center p-3">
                                        @switch($item->category->slug)
                                            @case('tenda')
                                                <i class="bi bi-triangle fs-1 text-secondary opacity-50"></i>
                                                <div class="small fw-semibold text-secondary mt-1">Tenda Event</div>
                                                @break
                                            @case('meja')
                                                <i class="bi bi-square fs-1 text-secondary opacity-50"></i>
                                                <div class="small fw-semibold text-secondary mt-1">Meja Acara</div>
                                                @break
                                            @case('kursi')
                                                <i class="bi bi-layers fs-1 text-secondary opacity-50"></i>
                                                <div class="small fw-semibold text-secondary mt-1">Kursi Tamu</div>
                                                @break
                                            @case('panggung')
                                                <i class="bi bi-display fs-1 text-secondary opacity-50"></i>
                                                <div class="small fw-semibold text-secondary mt-1">Panggung Rigging</div>
                                                @break
                                            @case('blower')
                                                <i class="bi bi-wind fs-1 text-secondary opacity-50"></i>
                                                <div class="small fw-semibold text-secondary mt-1">Misty Fan</div>
                                                @break
                                            @default
                                                <i class="bi bi-box fs-1 text-secondary opacity-50"></i>
                                        @endswitch
                                    </div>
                                @endif
                                <span class="position-absolute top-0 start-0 m-3 badge bg-white text-black border shadow-sm small fw-semibold">
                                    {{ $item->category->name }}
                                </span>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body d-flex flex-column p-3">
                                <h5 class="card-title fw-bold text-black fs-6 mb-1">{{ $item->name }}</h5>
                                <p class="card-text text-secondary small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ $item->description }}
                                </p>
                                
                                <div class="border-top pt-2 mt-auto">
                                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                                        <small class="text-secondary">Mulai dari</small>
                                        <span class="fw-bold text-black fs-6">
                                            Rp {{ number_format($item->price, 0, ',', '.') }} <small class="text-secondary fw-normal">/ {{ $item->unit }}</small>
                                        </span>
                                    </div>
                                    <a href="{{ route('catalog.show', $item->slug) }}" class="btn btn-outline-dark w-100 btn-sm rounded-2 fw-semibold">
                                        Lihat Detail & Konfigurasi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- CARA PEMESANAN (STEPPER ALUR) --}}
<section id="cara-pemesanan" class="py-5 bg-light border-top">
    <div class="container py-3">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="text-uppercase text-secondary fw-bold small tracking-wider">Alur Penyewaan</span>
            <h2 class="fw-bold text-black mb-2">Cara Pemesanan Perlengkapan</h2>
            <p class="text-secondary small">Proses penyewaan terstruktur untuk menjamin perlengkapan Anda siap dan tepat waktu.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 p-4 border bg-white rounded-3">
                    <div class="badge bg-black text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                        01
                    </div>
                    <h6 class="fw-bold text-black mb-2">Tentukan Acara & Jadwal</h6>
                    <p class="text-secondary small mb-0">Tentukan nama kegiatan, tanggal pelaksanaan, serta durasi sewa yang Anda butuhkan.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 p-4 border bg-white rounded-3">
                    <div class="badge bg-black text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                        02
                    </div>
                    <h6 class="fw-bold text-black mb-2">Pilih & Konfigurasi</h6>
                    <p class="text-secondary small mb-0">Pilih tenda, kursi, meja, panggung, dan sesuaikan ukuran serta jenis penutup sesuai kebutuhan.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 p-4 border bg-white rounded-3">
                    <div class="badge bg-black text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                        03
                    </div>
                    <h6 class="fw-bold text-black mb-2">Cek Ketersediaan Sistem</h6>
                    <p class="text-secondary small mb-0">Sistem otomatis menghitung ketersediaan stok inventaris pada jadwal yang Anda pilih tanpa bentrok.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 p-4 border bg-white rounded-3">
                    <div class="badge bg-black text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                        04
                    </div>
                    <h6 class="fw-bold text-black mb-2">Tentukan Titik Lokasi</h6>
                    <p class="text-secondary small mb-0">Tandai lokasi acara menggunakan peta interaktif Google Maps / OpenStreetMap untuk pengiriman.</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 p-4 border bg-white rounded-3">
                    <div class="badge bg-black text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                        05
                    </div>
                    <h6 class="fw-bold text-black mb-2">Pembayaran Online Aman</h6>
                    <p class="text-secondary small mb-0">Lakukan pembayaran melalui Payment Gateway terintegrasi (QRIS, Transfer Bank, E-Wallet).</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 p-4 border bg-white rounded-3">
                    <div class="badge bg-black text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px;">
                        06
                    </div>
                    <h6 class="fw-bold text-black mb-2">Pemasangan & Pelaksanaan</h6>
                    <p class="text-secondary small mb-0">Tim profesional Arsy Tenda akan memasang perlengkapan tepat waktu di lokasi acara Anda.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- TENTANG KAMI & CTA SURVEI --}}
<section id="tentang-kami" class="py-5 bg-white border-top">
    <div class="container py-3">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-uppercase text-secondary fw-bold small tracking-wider">Tentang Arsy Tenda</span>
                <h2 class="fw-bold text-black mb-3">Mitra Terpercaya Perlengkapan Event Anda</h2>
                <p class="text-secondary mb-3">
                    Arsy Tenda adalah penyedia jasa sewa perlengkapan pesta dan event profesional yang melayani berbagai jenis acara, mulai dari pernikahan, pameran, konser, bazar, peresmian gedung, hingga acara instansi kedinasan.
                </p>
                <div class="row g-3 mt-1">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle fs-4 text-black"></i>
                            <span class="small fw-semibold">Peralatan Bersih & Terawat</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle fs-4 text-black"></i>
                            <span class="small fw-semibold">Pemasangan Tepat Waktu</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle fs-4 text-black"></i>
                            <span class="small fw-semibold">Bebas Bentrokan Jadwal</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle fs-4 text-black"></i>
                            <span class="small fw-semibold">Layanan Survei Lapangan</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card p-4 p-md-5 bg-black text-white rounded-4 border-0 shadow">
                    <span class="badge bg-white text-black px-3 py-2 rounded-pill small mb-3 tracking-wider d-inline-block" style="width: fit-content;">
                        SURVEI LOKASI
                    </span>
                    <h3 class="fw-bold text-white mb-2">Ragu Menentukan Ukuran Tenda?</h3>
                    <p class="text-secondary small mb-4">
                        Ajukan survei lokasi dan tim teknis kami akan datang ke lokasi Anda untuk melakukan pengukuran lahan dan merekomendasikan perlengkapan yang paling sesuai.
                    </p>
                    <a href="{{ route('survey.create') }}" class="btn btn-light btn-lg px-4 py-3 rounded-3 fw-bold text-black">
                        <i class="bi bi-geo-alt-fill me-2"></i> Ajukan Survei Lokasi Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
