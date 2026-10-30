<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Operasional - {{ config('app.name', 'Arsy Tenda') }}</title>

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
<body class="admin-shell">
    <a class="skip-link" href="#admin-content">Lewati ke konten</a>
    <div class="admin-wrap">

    <!-- Admin Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar" aria-label="Navigasi operasional">
        <div class="sidebar-brand-wrap d-flex align-items-center justify-content-between">
            <a href="{{ route('admin.dashboard') }}" class="brand-arsy brand-arsy--light text-decoration-none" aria-label="Arsy Tenda Admin">
                <span class="brand-mark">ARSY</span>
                <span class="brand-name">TENDA</span>
            </a>
            <button class="btn btn-sm text-white-50 d-lg-none admin-sidebar-close-btn" type="button" data-admin-sidebar-close aria-label="Tutup menu operasional">
                <i class="bi bi-x-lg fs-6"></i>
            </button>
        </div>

        <nav class="sidebar-nav" aria-label="Navigasi operasional">
            <!-- Dashboard Link -->
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
            </a>

            <!-- Operasional -->
            <div class="sidebar-group-label">Operasional</div>
            <a href="{{ route('admin.orders') }}" class="sidebar-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> <span>Pesanan</span>
            </a>
            <a href="{{ route('admin.events') }}" class="sidebar-link {{ request()->routeIs('admin.events*') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i> <span>Kegiatan Acara</span>
            </a>
            <a href="{{ route('admin.surveys') }}" class="sidebar-link {{ request()->routeIs('admin.surveys*') ? 'active' : '' }}">
                <i class="bi bi-geo-alt"></i> <span>Survei Lokasi</span>
            </a>

            @if(auth()->user()->role === 'admin')
            <!-- Inventaris -->
            <div class="sidebar-group-label">Inventaris</div>
            <a href="{{ route('admin.inventories') }}" class="sidebar-link {{ request()->routeIs('admin.inventories*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> <span>Inventaris</span>
            </a>
            <a href="{{ route('admin.inventory-variants') }}" class="sidebar-link {{ request()->routeIs('admin.inventory-variants*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> <span>Variasi Inventaris</span>
            </a>

            <!-- Transaksi -->
            <div class="sidebar-group-label">Transaksi</div>
            <a href="{{ route('admin.payments') }}" class="sidebar-link {{ request()->routeIs('admin.payments*') ? 'active' : '' }}">
                <i class="bi bi-credit-card"></i> <span>Pembayaran</span>
            </a>

            <!-- Data Pelanggan & Pekerja -->
            <div class="sidebar-group-label">Data Pengguna</div>
            <a href="{{ route('admin.customers') }}" class="sidebar-link {{ request()->routeIs('admin.customers*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> <span>Pelanggan</span>
            </a>
            <a href="{{ route('admin.workers') }}" class="sidebar-link {{ request()->routeIs('admin.workers*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> <span>Pekerja / Tim Lapangan</span>
            </a>

            <!-- Laporan -->
            <div class="sidebar-group-label">Laporan</div>
            <a href="{{ route('admin.reports.orders') }}" class="sidebar-link {{ request()->routeIs('admin.reports.orders') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> <span>Laporan Pesanan</span>
            </a>
            <a href="{{ route('admin.reports.inventories') }}" class="sidebar-link {{ request()->routeIs('admin.reports.inventories') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i> <span>Laporan Inventaris</span>
            </a>
            <a href="{{ route('admin.reports.payments') }}" class="sidebar-link {{ request()->routeIs('admin.reports.payments') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-ruled"></i> <span>Laporan Pembayaran</span>
            </a>

            <!-- Konten & Tampilan Web -->
            <div class="sidebar-group-label">Konten &amp; Media</div>
            <a href="{{ route('admin.content.index') }}" class="sidebar-link {{ request()->routeIs('admin.content*') ? 'active' : '' }}">
                <i class="bi bi-images"></i> <span>Gambar &amp; Portofolio</span>
            </a>
            @endif

            <!-- Sistem -->
            <div class="sidebar-group-label">Sistem</div>
            <a href="{{ route('home') }}" class="sidebar-link" target="_blank">
                <i class="bi bi-globe"></i> <span>Lihat Website</span>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="mt-3 px-3">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm w-100 py-1.5 rounded-pill d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
                </button>
            </form>
        </nav>
    </aside>
    <button class="admin-sidebar-overlay" id="adminSidebarOverlay" type="button" aria-label="Tutup menu operasional" data-admin-sidebar-close></button>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-light d-lg-none admin-sidebar-toggle-btn" type="button" data-admin-sidebar-toggle aria-controls="adminSidebar" aria-expanded="false" aria-label="Buka menu operasional">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-lg-block">
                    <span class="text-secondary small fw-semibold">Panel Operasional &amp; Manajemen Persewaan</span>
                </div>
                <div class="d-lg-none d-flex align-items-center">
                    <span class="brand-mark-sm">ARSY</span>
                    <span class="brand-name-sm">TENDA</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="admin-role-badge {{ auth()->user()->role === 'admin' ? 'admin-role-badge--admin' : 'admin-role-badge--worker' }}" title="{{ auth()->user()->name }} ({{ auth()->user()->role === 'admin' ? 'Administrator' : 'Pekerja' }})">
                    <i class="bi {{ auth()->user()->role === 'admin' ? 'bi-shield-check' : 'bi-person-badge' }}"></i>
                    <span class="admin-role-name">
                        <span class="d-none d-sm-inline">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Pekerja' }}: </span>
                        <strong>{{ auth()->user()->name }}</strong>
                    </span>
                </div>
            </div>
        </header>

        <!-- Container -->
        <main id="admin-content" class="admin-content flex-grow-1">
            <!-- Global Alerts -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 d-flex align-items-center border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
                    <div class="small fw-medium">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3 d-flex align-items-center border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
                    <div class="small fw-medium">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
