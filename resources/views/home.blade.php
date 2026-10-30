@extends('layouts.app')

@section('content')
<div class="home-page-container">

    {{-- SECTION 1: HERO SECTION --}}
    <section class="section-hero">
        <div class="container hero-content-grid">
            <div class="hero-text-col" data-reveal>
                <div class="hero-kicker-label">ARSY TENDA &middot; EVENT RENTAL</div>
                <h1 class="hero-main-title">Perlengkapan Acara<br>untuk <span class="hero-italic-serif">Momen Istimewa</span></h1>
                <p class="hero-description-text">
                    Sewa tenda, meja, kursi, panggung, dan perlengkapan acara lainnya dengan pilihan yang dapat disesuaikan dengan kebutuhan acaramu.
                </p>
                <div class="hero-cta-buttons">
                    <a href="#katalog" class="btn-hero-primary">
                        <span>Jelajahi Katalog</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="{{ route('survey.create') }}" class="btn-hero-secondary">
                        <span>Ajukan Survei</span>
                    </a>
                </div>
                <div class="hero-trust-badges" aria-label="Keunggulan layanan">
                    <div class="trust-badge-item">
                        <i class="bi bi-geo-alt"></i>
                        <span>Melayani area<br>Pelaihari dan sekitarnya</span>
                    </div>
                    <div class="trust-badge-item">
                        <i class="bi bi-patch-check"></i>
                        <span>Perlengkapan berkualitas<br>&amp; siap untuk berbagai acara</span>
                    </div>
                </div>
            </div>

            <div class="hero-visual-col" data-reveal>
                <div class="hero-visual-wrapper">
                    <div class="hero-image-frame">
                        <img
                            src="{{ asset('assets/images/hero-tent.jpg') }}"
                            alt="Tenda Pesta Arsy Tenda Pelaihari"
                            class="hero-tent-photo"
                            width="640"
                            height="480"
                            fetchpriority="high"
                        >
                    </div>
                    <!-- Aesthetic Floating Overlay Box (Overlapping bottom edge like in reference image 2) -->
                    <div class="hero-image-overlay-box">
                        <span class="overlay-box-kicker">Layanan</span>
                        <div class="overlay-box-title">Tenda &amp; perlengkapan acara</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 2: FLOATING DATE CHECK PANEL --}}
    <section class="section-date-checker" data-reveal>
        <div class="container">
            <div class="date-checker-card">
                <div class="date-checker-info">
                    <h2 class="date-checker-title">Cari perlengkapan untuk acara kamu</h2>
                    <p class="date-checker-subtitle">Pilih tanggal acara untuk melihat ketersediaan produk.</p>
                </div>
                <form action="{{ route('home') }}#katalog" method="GET" class="date-checker-form">
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    <div class="date-field-group">
                        <label for="event_date_input" class="date-field-label">Tanggal acara</label>
                        <div class="date-input-wrapper">
                            <i class="bi bi-calendar3 date-icon-decor"></i>
                            <input
                                id="event_date_input"
                                type="date"
                                name="event_date"
                                value="{{ request('event_date', date('Y-m-d')) }}"
                                min="{{ date('Y-m-d') }}"
                                class="date-native-input"
                                required
                            >
                        </div>
                    </div>
                    <button type="submit" class="btn-check-availability">
                        <i class="bi bi-search"></i>
                        <span>Cek Ketersediaan</span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- SECTION 3: KOLEKSI PERLENGKAPAN (CATALOG) --}}
    <section id="katalog" class="section-catalog-collection">
        <div class="container">
            <div class="catalog-header-split" data-reveal>
                <div class="catalog-heading-left">
                    <div class="section-kicker-label">KOLEKSI PERLENGKAPAN</div>
                    <h2 class="catalog-section-title">Temukan kebutuhan acaramu.</h2>
                    <p class="catalog-section-subtitle">
                        Berbagai pilihan perlengkapan acara dengan kualitas terbaik untuk menciptakan momen yang berkesan.
                    </p>
                </div>
                <div class="catalog-search-right">
                    <form action="{{ route('home') }}#katalog" method="GET" class="catalog-search-bar" role="search">
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif
                        @if(request('event_date'))
                            <input type="hidden" name="event_date" value="{{ request('event_date') }}">
                        @endif
                        <i class="bi bi-search search-lens-icon"></i>
                        <input
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari perlengkapan..."
                            class="catalog-search-input"
                            aria-label="Cari perlengkapan acara"
                        >
                    </form>
                </div>
            </div>

            <div class="catalog-filter-bar" data-reveal>
                <div class="filter-pills-row">
                    <a href="{{ route('home', request()->only(['search', 'event_date'])) }}#katalog"
                       class="filter-pill-item {{ !request('category') ? 'active' : '' }}">
                        Semua
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('home', array_merge(request()->only(['search', 'event_date']), ['category' => $cat->slug])) }}#katalog"
                           class="filter-pill-item {{ request('category') === $cat->slug ? 'active' : '' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
                <div class="catalog-product-count">
                    <span>{{ $inventories->count() }} Produk</span>
                </div>
            </div>

            @if($inventories->isEmpty())
                <div class="catalog-empty-notice" data-reveal>
                    <i class="bi bi-box-seam fs-1 text-secondary opacity-50 mb-3"></i>
                    <h3>Perlengkapan tidak ditemukan</h3>
                    <p>Coba gunakan kata kunci lain atau pilih kategori yang berbeda.</p>
                    <a href="{{ route('home') }}#katalog" class="btn-empty-reset">Tampilkan Semua Produk</a>
                </div>
            @else
                <div class="catalog-four-column-grid">
                    @foreach($inventories as $item)
                        <article class="product-catalog-card" data-reveal>
                            <div class="card-thumbnail-holder">
                                <x-product-image :inventory="$item" />
                                <span class="card-category-badge">{{ $item->category->name }}</span>
                            </div>
                            <div class="card-content-holder">
                                <h3 class="card-item-title">{{ $item->name }}</h3>
                                <p class="card-item-description">
                                    {{ Str::limit($item->description, 60) }}
                                </p>
                                <div class="card-action-footer">
                                    <div class="card-price-display">
                                        Rp {{ number_format($item->price, 0, ',', '.') }}
                                        <span class="price-unit-text">/ {{ $item->unit }}</span>
                                    </div>
                                    <a href="{{ route('catalog.show', ['slug' => $item->slug, 'event_date' => request('event_date')]) }}"
                                       class="btn-card-view-detail"
                                       aria-label="Lihat detail {{ $item->name }}">
                                        <span>Lihat Detail</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- SECTION 4: URUTAN PEMESANAN (HOW IT WORKS) --}}
    <section id="cara-pemesanan" class="section-booking-process">
        <div class="container">
            <div class="process-section-header" data-reveal>
                <div class="section-kicker-label">CARA PEMESANAN</div>
                <h2 class="process-main-title">Urutan pemesanan yang jelas</h2>
                <p class="process-subtitle">
                    Proses pemesanan mudah dan transparan, dari pilih perlengkapan hingga acara siap dilaksanakan.
                </p>
            </div>

            <div class="process-steps-row">
                <div class="process-step-box" data-reveal>
                    <div class="step-header-meta">
                        <span class="step-num-badge">01</span>
                        <div class="step-icon-wrap"><i class="bi bi-calendar3"></i></div>
                    </div>
                    <h3 class="step-box-title">Pilih tanggal &amp; perlengkapan</h3>
                    <p class="step-box-desc">Tentukan tanggal acara dan produk yang dibutuhkan.</p>
                </div>

                <div class="process-step-box" data-reveal>
                    <div class="step-header-meta">
                        <span class="step-num-badge">02</span>
                        <div class="step-icon-wrap"><i class="bi bi-card-checklist"></i></div>
                    </div>
                    <h3 class="step-box-title">Atur kebutuhan acara</h3>
                    <p class="step-box-desc">Sesuaikan jumlah, ukuran, dan detail acara.</p>
                </div>

                <div class="process-step-box" data-reveal>
                    <div class="step-header-meta">
                        <span class="step-num-badge">03</span>
                        <div class="step-icon-wrap"><i class="bi bi-geo-alt"></i></div>
                    </div>
                    <h3 class="step-box-title">Lengkapi detail lokasi</h3>
                    <p class="step-box-desc">Informasikan lokasi acara dan waktu pemasangan.</p>
                </div>

                <div class="process-step-box" data-reveal>
                    <div class="step-header-meta">
                        <span class="step-num-badge">04</span>
                        <div class="step-icon-wrap"><i class="bi bi-check2-circle"></i></div>
                    </div>
                    <h3 class="step-box-title">Konfirmasi pesanan</h3>
                    <p class="step-box-desc">Tim kami akan menghubungi untuk konfirmasi dan jadwal.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 5: PORTOFOLIO ACARA (GALLERY) --}}
    <section id="portofolio" class="section-portfolio-gallery">
        <div class="container portfolio-grid-split">
            <div class="portfolio-text-side" data-reveal>
                <div class="section-kicker-label">PORTOFOLIO</div>
                <h2 class="portfolio-main-title">
                    Rencana acara dimulai dari detail yang tepat.
                </h2>
                <p class="portfolio-description">
                    Lihat beberapa hasil pemasangan perlengkapan acara yang pernah kami kerjakan untuk berbagai jenis acara.
                </p>
                <a href="#katalog" class="btn-portfolio-action">
                    <span>Lihat Semua Portofolio</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="portfolio-collage-side" data-reveal>
                <div class="collage-container">
                    <div class="collage-item collage-item--main">
                        <img src="{{ asset('assets/images/references/marquee.jpg') }}"
                             alt="Pemasangan Tenda Resepsi Elegan Arsy Tenda"
                             width="600"
                             height="450"
                             loading="lazy">
                    </div>
                    <div class="collage-item collage-item--stage">
                        <img src="{{ asset('assets/images/references/event-stage.jpg') }}"
                             alt="Instalasi Panggung Rigging Acara"
                             width="400"
                             height="300"
                             loading="lazy">
                    </div>
                    <div class="collage-item collage-item--dining">
                        <img src="{{ asset('assets/images/references/table-setup.jpg') }}"
                             alt="Penataan Meja Kursi Jamuan Tamu"
                             width="400"
                             height="300"
                             loading="lazy">
                    </div>
                    <div class="collage-item collage-item--wide">
                        <img src="{{ asset('assets/images/hero-tent.jpg') }}"
                             alt="Suasana Tenda Roder Acara Besar"
                             width="800"
                             height="400"
                             loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 6: TENTANG KAMI --}}
    <section id="tentang-kami" class="py-5" data-reveal style="background-color: #FAF8F5; border-top: 1px solid #E7E2DA; border-bottom: 1px solid #E7E2DA;">
        <div class="container py-lg-3">
            <div class="row g-4 g-lg-5 align-items-center">
                <div class="col-lg-6">
                    <div class="section-kicker-label mb-2">TENTANG ARSY TENDA</div>
                    <h2 class="font-display fw-bold mb-3" style="font-size: clamp(1.75rem, 3vw, 2.35rem); line-height: 1.2; color: #191919;">
                        Penyedia Persewaan Tenda &amp; Perlengkapan Acara Terpercaya
                    </h2>
                    <p class="text-secondary mb-3" style="font-size: 0.95rem; line-height: 1.7;">
                        Arsy Tenda berpusat di Pelaihari, Kabupaten Tanah Laut, Kalimantan Selatan. Kami menyediakan solusi lengkap persewaan tenda berkualitas, panggung, meja, dan kursi untuk mewujudkan berbagai agenda acara—dari resepsi pernikahan, syukuran keluarga, pameran UMKM, hingga acara kedinasan dan formal.
                    </p>
                    <p class="text-secondary mb-4" style="font-size: 0.95rem; line-height: 1.7;">
                        Dengan dukungan tim teknisi lapangan berpengalaman, armada logistik siap jalan, dan layanan survei lokasi presisi via peta interaktif, kami memastikan perlengkapan terpasang kokoh, bersih, dan tepat waktu demi kelancaran hari istimewa Anda.
                    </p>
                    <div class="d-flex flex-wrap gap-4 pt-3 border-top" style="border-color: #DDD8D0 !important;">
                        <div>
                            <span class="d-block fw-bold text-black fs-5 tabular-nums">100%</span>
                            <small class="text-secondary" style="font-size: 0.8rem;">Peralatan Bersih &amp; Terawat</small>
                        </div>
                        <div>
                            <span class="d-block fw-bold text-black fs-5">Pelaihari &amp; Sekitarnya</span>
                            <small class="text-secondary" style="font-size: 0.8rem;">Jangkauan Layanan</small>
                        </div>
                        <div>
                            <span class="d-block fw-bold text-black fs-5">Tepat Waktu</span>
                            <small class="text-secondary" style="font-size: 0.8rem;">Pemasangan Profesional</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="editorial-card p-4 p-md-5" style="background: #FFFFFF;">
                        <h4 class="font-display fw-bold text-black mb-3 fs-5">Komitmen Pelayanan Kami</h4>
                        <div class="d-flex gap-3 mb-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-light border flex-shrink-0" style="width: 44px; height: 44px; border-color: #DDD8D0 !important;">
                                <i class="bi bi-shield-check text-black fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-black mb-1">Stok &amp; Kualitas Terjamin</h6>
                                <p class="text-secondary small mb-0">Seluruh unit tenda dan perlengkapan dipelihara berkala agar selalu siap pakai dan higienis.</p>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mb-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-light border flex-shrink-0" style="width: 44px; height: 44px; border-color: #DDD8D0 !important;">
                                <i class="bi bi-geo-alt-fill text-black fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-black mb-1">Survei Lokasi &amp; Rute Terukur</h6>
                                <p class="text-secondary small mb-0">Peninjauan langsung ke lokasi acara untuk memastikan akses armada dan penataan layout akurat.</p>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-light border flex-shrink-0" style="width: 44px; height: 44px; border-color: #DDD8D0 !important;">
                                <i class="bi bi-clock-history text-black fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-black mb-1">Pemesanan Terbuka &amp; Pasti</h6>
                                <p class="text-secondary small mb-0">Ketersediaan dicek per tanggal acara dengan transparansi rincian biaya tanpa biaya tersembunyi.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 7: BANNER SURVEI LOKASI --}}
    <section id="ajukan-survei" class="section-survey-banner" data-reveal>
        <div class="container">
            <div class="survey-banner-box">
                <div class="survey-banner-text">
                    <div class="banner-kicker-label">BUTUH BANTUAN?</div>
                    <h2 class="banner-main-title">Perlu meninjau lokasi acara?</h2>
                    <p class="banner-subtitle">
                        Ajukan permintaan survei untuk membantu menentukan kebutuhan perlengkapan dan penataan acara.
                    </p>
                </div>
                <div class="survey-banner-cta">
                    <a href="{{ route('survey.create') }}" class="btn-survey-action-white">
                        <span>Ajukan Survei Lokasi</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
