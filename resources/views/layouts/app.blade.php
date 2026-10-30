<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Arsy Tenda') }} - Solusi Persewaan Perlengkapan Acara</title>

    <!-- Google Fonts: Plus Jakarta Sans & Playfair Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="site-shell">
    <a class="skip-link" href="#main-content">Lewati ke konten</a>

    <!-- Floating Glassmorphism Navbar Header -->
    <header class="site-header-floating">
        <nav class="navbar navbar-expand-lg site-navbar-pill" aria-label="Navigasi utama">
            <div class="navbar-pill-inner">
                <!-- Brand Logo (Pill Capsule) -->
                <a href="{{ route('home') }}" class="brand-arsy" aria-label="Arsy Tenda Beranda">
                    <span class="brand-mark">ARSY</span>
                    <span class="brand-name">TENDA</span>
                </a>

                <!-- Mobile Menu Toggle Button -->
                <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarFloatingMenu" aria-controls="navbarFloatingMenu" aria-expanded="false" aria-label="Buka menu navigasi">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation Links & Actions -->
                <div class="collapse navbar-collapse" id="navbarFloatingMenu">
                    <ul class="navbar-nav mx-auto site-nav-links">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('home') && !request()->hasAny(['category', 'search', 'event_date']) ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->has('category') || request()->has('search') ? 'active' : '' }}" href="{{ route('home') }}#katalog">Katalog</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}#cara-pemesanan">Cara Sewa</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}#portofolio">Portofolio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}#tentang-kami">Tentang</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}#kontak">Kontak</a>
                        </li>
                    </ul>

                    <div class="nav-actions d-flex align-items-center gap-2">
                        @auth
                            @if(auth()->user()->role === 'customer')
                                <a href="{{ route('cart.index') }}" class="btn-nav-cart position-relative" title="Keranjang Belanja" aria-label="Keranjang Belanja">
                                    <i class="bi bi-cart3"></i>
                                    @if(session()->has('cart') && count(session('cart')) > 0)
                                        <span class="cart-badge">{{ count(session('cart')) }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('dashboard.index') }}" class="btn-nav-action-pill">
                                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                </a>
                            @elseif(auth()->user()->role === 'admin')
                                <a href="{{ route('admin.dashboard') }}" class="btn-nav-action-pill">
                                    <i class="bi bi-gear-fill me-1"></i> Panel Admin
                                </a>
                            @endif

                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn-nav-icon-only" title="Logout" aria-label="Keluar">
                                    <i class="bi bi-box-arrow-right"></i>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="btn-nav-login">Masuk</a>
                            <a href="https://wa.me/6281234567890?text=Halo%20Arsy%20Tenda,%20saya%20ingin%20konsultasi%20perlengkapan%20acara" target="_blank" rel="noopener noreferrer" class="btn-nav-contact">
                                <i class="bi bi-telephone-fill"></i>
                                <span>Hubungi Kami</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Global Flash Alerts -->
    @if(session('success') || session('error') || session('warning'))
        <div class="container site-alerts-container mt-3">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
                    <div class="small fw-medium">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-4 d-flex align-items-center border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
                    <div class="small fw-medium">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show rounded-4 d-flex align-items-center border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2 fs-5 text-warning"></i>
                    <div class="small fw-medium">{{ session('warning') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
    @endif

    <!-- Main Content Area -->
    <main id="main-content" class="site-main">
        @yield('content')
    </main>

    <!-- Editorial Dark Footer -->
    <footer id="kontak" class="site-footer bg-dark-editorial">
        <div class="container footer-content-wrap">
            <div class="footer-main-grid">
                <!-- Col 1: Brand Info -->
                <div class="footer-col footer-col-brand">
                    <div class="footer-brand mb-3">
                        <a href="{{ route('home') }}" class="brand-arsy brand-arsy--light" aria-label="Arsy Tenda Beranda">
                            <span class="brand-mark">ARSY</span>
                            <span class="brand-name">TENDA</span>
                        </a>
                    </div>
                    <p class="footer-brand-desc">
                        Menyediakan perlengkapan acara dengan kualitas terbaik untuk berbagai kebutuhan acara Anda.
                    </p>
                    <div class="footer-social-links">
                        <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="social-icon-btn" aria-label="Instagram Arsy Tenda">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://wa.me/6281234567890" target="_blank" rel="noopener noreferrer" class="social-icon-btn" aria-label="WhatsApp Arsy Tenda">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                        <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="social-icon-btn" aria-label="YouTube Arsy Tenda">
                            <i class="bi bi-youtube"></i>
                        </a>
                    </div>
                </div>

                <!-- Col 2: Navigasi -->
                <div class="footer-col footer-col-nav">
                    <h3 class="footer-col-title">Navigasi</h3>
                    <ul class="footer-nav-list">
                        <li><a href="{{ route('home') }}">Beranda</a></li>
                        <li><a href="{{ route('home') }}#katalog">Katalog</a></li>
                        <li><a href="{{ route('home') }}#cara-pemesanan">Cara Sewa</a></li>
                        <li><a href="{{ route('home') }}#portofolio">Portofolio</a></li>
                        <li><a href="{{ route('home') }}#tentang-kami">Tentang</a></li>
                        <li><a href="{{ route('home') }}#kontak">Kontak</a></li>
                    </ul>
                </div>

                <!-- Col 3: Kategori -->
                <div class="footer-col footer-col-categories">
                    <h3 class="footer-col-title">Kategori</h3>
                    <ul class="footer-nav-list">
                        <li><a href="{{ route('home', ['category' => 'tenda']) }}#katalog">Tenda</a></li>
                        <li><a href="{{ route('home', ['category' => 'meja']) }}#katalog">Meja</a></li>
                        <li><a href="{{ route('home', ['category' => 'kursi']) }}#katalog">Kursi</a></li>
                        <li><a href="{{ route('home', ['category' => 'panggung']) }}#katalog">Panggung</a></li>
                        <li><a href="{{ route('home', ['category' => 'blower']) }}#katalog">Blower</a></li>
                    </ul>
                </div>

                <!-- Col 4: Kontak & Lokasi -->
                <div class="footer-col footer-col-contact">
                    <h3 class="footer-col-title">Kontak</h3>
                    <div class="footer-contact-list">
                        <a href="tel:+6281234567890" class="contact-item">
                            <i class="bi bi-telephone"></i>
                            <span>+62 812-3456-7890</span>
                        </a>
                        <a href="mailto:info@arsytenda.com" class="contact-item">
                            <i class="bi bi-envelope"></i>
                            <span>info@arsytenda.com</span>
                        </a>
                        <div class="contact-item">
                            <i class="bi bi-geo-alt"></i>
                            <span>Pelaihari, Tanah Laut, Kalsel</span>
                        </div>
                    </div>
                    <div class="footer-service-area">
                        Melayani area Pelaihari dan sekitarnya
                    </div>
                </div>
            </div>

            <!-- Footer Bottom Line -->
            <div class="footer-bottom-bar">
                <p class="footer-copyright">&copy; {{ date('Y') }} Arsy Tenda. Seluruh Hak Cipta Dilindungi.</p>
                <div class="footer-legal-links">
                    <a href="#">Syarat &amp; Ketentuan</a>
                    <span class="legal-divider">&middot;</span>
                    <a href="#">Kebijakan Privasi</a>
                </div>
            </div>

            <!-- Discreet Photo Attribution -->
            <div class="footer-credits">
                Foto referensi:
                <a href="https://commons.wikimedia.org/wiki/File:Marquee_in_Queens_Gardens,_Hull_-_geograph.org.uk_-_5519812.jpg" target="_blank" rel="noopener noreferrer">Ian S (CC BY-SA 2.0)</a>,
                <a href="https://www.flickr.com/photos/31392461@N06/2937890463" target="_blank" rel="noopener noreferrer">glarblog (CC BY 2.0)</a>,
                <a href="https://commons.wikimedia.org/wiki/File:Le_Badaboum_Paris_concert_stage.jpg" target="_blank" rel="noopener noreferrer">Shawndeney (CC0)</a>.
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Pill Widget -->
    <a href="https://wa.me/6281234567890?text=Halo%20Arsy%20Tenda,%20saya%20ingin%20bertanya%20seputar%20sewa%20perlengkapan%20acara"
       class="floating-whatsapp-widget"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Hubungi WhatsApp Arsy Tenda">
        <i class="bi bi-whatsapp"></i>
    </a>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
