@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <span class="text-uppercase text-secondary fw-bold small tracking-wider">Pengaturan Akun</span>
            <h2 class="fw-bold text-black mb-1">Profil Pengguna</h2>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills gap-2 mb-4 border-bottom pb-3">
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.index') }}">
                <i class="bi bi-grid me-1"></i> Ringkasan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.orders') }}">
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
            <a class="nav-link active bg-black rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.profile') }}">
                <i class="bi bi-person me-1"></i> Profil Akun
            </a>
        </li>
    </ul>

    <!-- Form Profil -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card p-4 p-md-5 border rounded-4 bg-white shadow-sm">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">
                    <i class="bi bi-person-gear me-2"></i> Perbarui Data Akun
                </h5>

                <form action="{{ route('dashboard.profile.update') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-black">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control rounded-3" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Alamat Email (Tidak Dapat Diubah)</label>
                        <input type="email" class="form-control rounded-3 bg-light" value="{{ $user->email }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-black">Nomor WhatsApp / HP</label>
                        <input type="text" name="phone" class="form-control rounded-3" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-black">Alamat Utama</label>
                        <textarea name="address" rows="2" class="form-control rounded-3" placeholder="Alamat rumah / domisili...">{{ old('address', $user->address) }}</textarea>
                    </div>

                    <h6 class="fw-bold text-black pt-3 border-top mb-3">Ganti Password (Kosongkan jika tidak ingin diubah)</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Password Baru</label>
                            <input type="password" name="password" class="form-control rounded-3" placeholder="Minimal 8 karakter">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-control rounded-3" placeholder="Ulangi password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark px-4 py-2.5 rounded-3 fw-bold">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan Profil
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
