@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="page-editorial-header mb-4">
        <span class="page-eyebrow">Pengaturan Akun</span>
        <h1 class="page-headline mb-1">Profil Pengguna</h1>
        <p class="page-subtext">Kelola informasi kontak dan kredensial keamanan akun Anda.</p>
    </div>

    <!-- Navigation Tabs -->
    <nav class="dashboard-nav-pills" aria-label="Menu Dasbor Pelanggan">
        <a class="dashboard-pill" href="{{ route('dashboard.index') }}">
            <i class="bi bi-grid"></i> <span>Ringkasan</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.orders') }}">
            <i class="bi bi-receipt"></i> <span>Pesanan Saya</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.events') }}">
            <i class="bi bi-calendar-event"></i> <span>Kegiatan Acara</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.surveys') }}">
            <i class="bi bi-geo-alt"></i> <span>Survei Lokasi</span>
        </a>
        <a class="dashboard-pill active" href="{{ route('dashboard.profile') }}">
            <i class="bi bi-person"></i> <span>Profil Akun</span>
        </a>
    </nav>

    <!-- Form Profil -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="editorial-card p-4 p-md-5">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                    <i class="bi bi-person-gear text-secondary"></i> Perbarui Data Akun
                </h5>

                <form action="{{ route('dashboard.profile.update') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="profileName" class="form-label small fw-semibold text-black">Nama Lengkap</label>
                        <input type="text" name="name" id="profileName" autocomplete="name" class="form-control rounded-3" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="profileEmail" class="form-label small fw-semibold text-secondary">Alamat Email (Tidak Dapat Diubah)</label>
                        <input type="email" id="profileEmail" autocomplete="username" class="form-control rounded-3" style="background: #FAF8F5;" value="{{ $user->email }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="profilePhone" class="form-label small fw-semibold text-black">Nomor WhatsApp / HP</label>
                        <input type="text" name="phone" id="profilePhone" autocomplete="tel" class="form-control rounded-3" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="mb-4">
                        <label for="profileAddress" class="form-label small fw-semibold text-black">Alamat Utama</label>
                        <textarea name="address" id="profileAddress" rows="2" class="form-control rounded-3" placeholder="Alamat rumah / domisili...">{{ old('address', $user->address) }}</textarea>
                    </div>

                    <h6 class="fw-bold text-black pt-3 border-top mb-3" style="border-color: #E7E2DA !important;">Ganti Password (Kosongkan jika tidak ingin diubah)</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="profilePassword" class="form-label small fw-semibold text-black">Password Baru</label>
                            <input type="password" name="password" id="profilePassword" autocomplete="new-password" class="form-control rounded-3" placeholder="Minimal 8 karakter">
                        </div>
                        <div class="col-md-6">
                            <label for="profilePasswordConfirm" class="form-label small fw-semibold text-black">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" id="profilePasswordConfirm" autocomplete="new-password" class="form-control rounded-3" placeholder="Ulangi password">
                        </div>
                    </div>

                    <button type="submit" class="btn-hero-primary">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan Profil
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
