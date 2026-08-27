<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - {{ config('app.name', 'Arsy Tenda') }}</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
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
            background-color: #f4f6f8;
            color: var(--arsy-text-primary);
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 260px;
            min-height: 100vh;
            background-color: #111111;
            color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            padding-bottom: 2rem;
            overflow-y: auto;
        }

        .admin-main {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                margin-left: -260px;
                transition: margin 0.3s ease;
            }
            .admin-sidebar.show {
                margin-left: 0;
            }
            .admin-main {
                margin-left: 0;
            }
        }

        .sidebar-heading {
            font-size: 0.685rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888888;
            padding: 1rem 1.25rem 0.35rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #b0b0b0;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
        }

        .sidebar-link.active {
            color: #ffffff;
            background-color: #222222;
            font-weight: 600;
            border-left: 3px solid #ffffff;
        }

        .sidebar-link i {
            font-size: 1.1rem;
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .admin-topbar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--arsy-border);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card {
            border: 1px solid var(--arsy-border);
            border-radius: 10px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .table th {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666666;
            background-color: #f8f9fa;
            border-bottom: 1px solid var(--arsy-border);
        }

        .table td {
            font-size: 0.875rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--arsy-border);
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Admin Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="p-3 border-bottom border-dark">
            <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-decoration-none text-white">
                <span class="bg-white text-black px-2 py-1 fw-bold rounded font-monospace" style="font-size: 0.8rem; letter-spacing: 1.5px;">ARSY</span>
                <span class="fw-bold text-white fs-6" style="letter-spacing: 1px;">TENDA ADMIN</span>
            </a>
        </div>

        <div class="py-2">
            <!-- Dashboard Link -->
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>

            <!-- Operasional -->
            <div class="sidebar-heading">OPERASIONAL</div>
            <a href="{{ route('admin.orders') }}" class="sidebar-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Pesanan
            </a>
            <a href="{{ route('admin.events') }}" class="sidebar-link {{ request()->routeIs('admin.events*') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i> Kegiatan Acara
            </a>
            <a href="{{ route('admin.surveys') }}" class="sidebar-link {{ request()->routeIs('admin.surveys*') ? 'active' : '' }}">
                <i class="bi bi-geo-alt"></i> Survei Lokasi
            </a>

            <!-- Inventaris -->
            <div class="sidebar-heading">INVENTARIS</div>
            <a href="{{ route('admin.inventories') }}" class="sidebar-link {{ request()->routeIs('admin.inventories*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Inventaris
            </a>
            <a href="{{ route('admin.inventory-variants') }}" class="sidebar-link {{ request()->routeIs('admin.inventory-variants*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> Variasi Inventaris
            </a>

            <!-- Transaksi -->
            <div class="sidebar-heading">TRANSAKSI</div>
            <a href="{{ route('admin.payments') }}" class="sidebar-link {{ request()->routeIs('admin.payments*') ? 'active' : '' }}">
                <i class="bi bi-credit-card"></i> Pembayaran
            </a>

            <!-- Data Pelanggan & Pekerja -->
            <div class="sidebar-heading">DATA PENGGUNA</div>
            <a href="{{ route('admin.customers') }}" class="sidebar-link {{ request()->routeIs('admin.customers*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Pelanggan
            </a>
            <a href="{{ route('admin.workers') }}" class="sidebar-link {{ request()->routeIs('admin.workers*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> Pekerja / Tim Lapangan
            </a>

            <!-- Laporan -->
            <div class="sidebar-heading">LAPORAN</div>
            <a href="{{ route('admin.reports.orders') }}" class="sidebar-link {{ request()->routeIs('admin.reports.orders') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i> Laporan Pesanan
            </a>
            <a href="{{ route('admin.reports.inventories') }}" class="sidebar-link {{ request()->routeIs('admin.reports.inventories') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Laporan Inventaris
            </a>
            <a href="{{ route('admin.reports.payments') }}" class="sidebar-link {{ request()->routeIs('admin.reports.payments') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-ruled"></i> Laporan Pembayaran
            </a>

            <!-- Sistem -->
            <div class="sidebar-heading">SISTEM</div>
            <a href="{{ route('home') }}" class="sidebar-link" target="_blank">
                <i class="bi bi-globe"></i> Lihat Website
            </a>
            <form action="{{ route('logout') }}" method="POST" class="mt-2 px-3">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm w-100 py-1.5 rounded-2 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <button class="btn btn-sm btn-light d-lg-none" type="button" onclick="document.getElementById('adminSidebar').classList.toggle('show')">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="d-none d-lg-block">
                <span class="text-secondary small">Panel Operasional & Manajemen Inventaris</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge {{ auth()->user()->role === 'admin' ? 'bg-dark' : 'bg-primary' }} text-white fw-normal px-2.5 py-1.5">
                    <i class="bi {{ auth()->user()->role === 'admin' ? 'bi-shield-check' : 'bi-person-badge' }} me-1"></i>
                    {{ auth()->user()->role === 'admin' ? 'Administrator' : 'Petugas Lapangan' }}: {{ auth()->user()->name }}
                </span>
            </div>
        </header>

        <!-- Container -->
        <main class="p-4 flex-grow-1">
            <!-- Global Alerts -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3 d-flex align-items-center border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
                    <div class="small fw-medium">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 d-flex align-items-center border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
                    <div class="small fw-medium">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('scripts')
</body>
</html>
