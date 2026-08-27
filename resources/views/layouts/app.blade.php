<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Arsy Tenda') }} - Solusi Persewaan Perlengkapan Acara</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Leaflet CSS (Map Picker) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        :root {
            --bs-font-sans-serif: 'Inter', sans-serif;
            --arsy-primary: #111111;
            --arsy-secondary: #333333;
            --arsy-bg: #ffffff;
            --arsy-soft-bg: #f8f9fa;
            --arsy-border: #e5e5e5;
            --arsy-text-primary: #111111;
            --arsy-text-secondary: #666666;
        }

        body {
            font-family: var(--bs-font-sans-serif);
            background-color: var(--arsy-soft-bg);
            color: var(--arsy-text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .btn-primary, .bg-primary {
            background-color: var(--arsy-primary) !important;
            border-color: var(--arsy-primary) !important;
            color: #ffffff !important;
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: #222222 !important;
            border-color: #222222 !important;
        }

        .btn-outline-primary {
            border-color: var(--arsy-primary) !important;
            color: var(--arsy-primary) !important;
        }

        .btn-outline-primary:hover {
            background-color: var(--arsy-primary) !important;
            color: #ffffff !important;
        }

        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--arsy-primary);
        }

        .nav-link {
            font-weight: 500;
            font-size: 0.925rem;
            color: var(--arsy-text-secondary) !important;
            transition: color 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--arsy-primary) !important;
        }

        .card {
            border: 1px solid var(--arsy-border);
            border-radius: 12px;
            background-color: var(--arsy-bg);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .badge-available {
            background-color: #d1e7dd;
            color: #0f5132;
            font-weight: 600;
        }

        .badge-limited {
            background-color: #fff3cd;
            color: #664d03;
            font-weight: 600;
        }

        .badge-unavailable {
            background-color: #f8d7da;
            color: #842029;
            font-weight: 600;
        }

        /* Stepper Styling */
        .order-stepper {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 2rem;
        }
        .order-stepper::before {
            content: "";
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--arsy-border);
            z-index: 1;
        }
        .stepper-step {
            position: relative;
            z-index: 2;
            text-align: center;
            background: transparent;
            padding: 0 8px;
        }
        .stepper-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--arsy-bg);
            border: 2px solid var(--arsy-border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 6px;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--arsy-text-secondary);
        }
        .stepper-step.active .stepper-circle {
            background: var(--arsy-primary);
            border-color: var(--arsy-primary);
            color: #ffffff;
        }
        .stepper-step.completed .stepper-circle {
            background: #198754;
            border-color: #198754;
            color: #ffffff;
        }
        .stepper-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--arsy-text-secondary);
            text-transform: uppercase;
        }
        .stepper-step.active .stepper-label {
            color: var(--arsy-primary);
            font-weight: 700;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Navbar Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top border-bottom py-3">
        <div class="container">
            <!-- Brand Logo (Monochrome Typography) -->
            <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="{{ route('home') }}">
                <span class="bg-black text-white px-2 py-1 fw-bold rounded font-monospace" style="font-size: 0.85rem; letter-spacing: 2px;">ARSY</span>
                <span class="fw-bold text-black" style="font-size: 1.2rem; letter-spacing: 1px;">TENDA</span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active fw-bold' : '' }}" href="{{ route('home') }}">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('catalog.*') ? 'active fw-bold' : '' }}" href="{{ route('home') }}#katalog">Perlengkapan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}#cara-pemesanan">Cara Pemesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('survey.create') ? 'active fw-bold' : '' }}" href="{{ route('survey.create') }}">Survei Lokasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}#tentang-kami">Tentang Kami</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    @auth
                        @if(auth()->user()->role === 'customer')
                            <a href="{{ route('cart.index') }}" class="btn btn-outline-dark btn-sm position-relative px-3 py-2 rounded-3 me-2">
                                <i class="bi bi-cart3 me-1"></i> Keranjang
                                @if(session()->has('cart') && count(session('cart')) > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        {{ count(session('cart')) }}
                                    </span>
                                @endif
                            </a>
                            <a href="{{ route('dashboard.index') }}" class="btn btn-dark btn-sm px-3 py-2 rounded-3">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        @elseif(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm px-3 py-2 rounded-3">
                                <i class="bi bi-gear-fill me-1"></i> Panel Admin
                            </a>
                        @endif

                        <form action="{{ route('logout') }}" method="POST" class="d-inline ms-1">
                            @csrf
                            <button type="submit" class="btn btn-light btn-sm text-secondary border px-2 py-2 rounded-3" title="Logout">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-dark btn-sm px-3 py-2 rounded-3">Masuk</a>
                        <a href="{{ route('register') }}" class="btn btn-dark btn-sm px-3 py-2 rounded-3">Daftar</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Global Alerts -->
    <div class="container mt-3">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 d-flex align-items-center border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
                <div class="small fw-medium">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 d-flex align-items-center border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
                <div class="small fw-medium">{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show rounded-3 d-flex align-items-center border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2 fs-5 text-warning"></i>
                <div class="small fw-medium">{{ session('warning') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>

    <!-- Main Body Content -->
    <main class="flex-grow-1 py-4">
        @yield('content')
    </main>

    <!-- Clean Monochrome Footer -->
    <footer class="bg-black text-light py-5 mt-auto border-top border-dark">
        <div class="container">
            <div class="row g-4 justify-content-between pb-4 border-bottom border-secondary border-opacity-25">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="bg-white text-black px-2 py-1 fw-bold rounded font-monospace" style="font-size: 0.85rem; letter-spacing: 2px;">ARSY</span>
                        <span class="fw-bold text-white fs-5" style="letter-spacing: 1px;">TENDA</span>
                    </div>
                    <p class="text-secondary small mb-0">
                        Sistem Informasi Pemesanan dan Manajemen Inventaris Perlengkapan Acara Berbasis Web Terintegrasi Payment Gateway dan Peta Lokasi.
                    </p>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <h6 class="text-white fw-bold small text-uppercase mb-3 tracking-wider">Navigasi</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('home') }}" class="text-secondary text-decoration-none hover-white">Beranda</a></li>
                        <li class="mb-2"><a href="{{ route('home') }}#katalog" class="text-secondary text-decoration-none hover-white">Perlengkapan</a></li>
                        <li class="mb-2"><a href="{{ route('survey.create') }}" class="text-secondary text-decoration-none hover-white">Survei Lokasi</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <h6 class="text-white fw-bold small text-uppercase mb-3 tracking-wider">Kategori</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('home', ['category' => 'tenda']) }}" class="text-secondary text-decoration-none">Tenda Event</a></li>
                        <li class="mb-2"><a href="{{ route('home', ['category' => 'kursi']) }}" class="text-secondary text-decoration-none">Kursi & Meja</a></li>
                        <li class="mb-2"><a href="{{ route('home', ['category' => 'panggung']) }}" class="text-secondary text-decoration-none">Panggung Rigging</a></li>
                        <li class="mb-2"><a href="{{ route('home', ['category' => 'blower']) }}" class="text-secondary text-decoration-none">Blower & Fan</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold small text-uppercase mb-3 tracking-wider">Kontak & Lokasi</h6>
                    <p class="text-secondary small mb-1"><i class="bi bi-geo-alt me-2"></i> Pelaihari, Tanah Laut, Kalsel</p>
                    <p class="text-secondary small mb-1"><i class="bi bi-telephone me-2"></i> +62 812-3456-7890</p>
                    <p class="text-secondary small"><i class="bi bi-envelope me-2"></i> info@arsytenda.com</p>
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center pt-4 small text-secondary">
                <p class="mb-0">&copy; {{ date('Y') }} Arsy Tenda. Seluruh Hak Cipta Dilindungi.</p>
                <p class="mb-0">Penyewaan Perlengkapan Acara & Event Organizer</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('scripts')
</body>
</html>
