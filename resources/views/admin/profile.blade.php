@extends('layouts.admin')

@section('content')
<div class="page-editorial-header mb-4">
    <span class="page-eyebrow">Pengaturan Akun</span>
    <h1 class="page-headline mb-1 font-display">Profil Saya</h1>
    <p class="page-subtext">Kelola informasi identitas, kontak, dan kata sandi akun {{ auth()->user()->role === 'admin' ? 'Administrator' : 'Petugas Lapangan' }} Anda.</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-4 d-flex align-items-center border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
        <div class="small fw-medium">{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <!-- Card Ringkasan Profil -->
        <div class="editorial-card p-4 text-center">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: #FAF8F5; border: 1px solid #E7E2DA;">
                <i class="bi {{ $user->role === 'admin' ? 'bi-shield-check' : 'bi-person-badge' }} fs-1 text-black"></i>
            </div>
            <h5 class="fw-bold text-black font-display mb-1">{{ $user->name }}</h5>
            <p class="text-secondary small mb-2">{{ $user->email }}</p>
            <div>
                <span class="badge-status {{ $user->role === 'admin' ? 'badge-status--neutral' : 'badge-status--info' }}">
                    {{ $user->role === 'admin' ? 'ADMINISTRATOR' : 'PETUGAS LAPANGAN' }}
                </span>
            </div>
            <hr class="my-3" style="border-color: #E7E2DA;">
            <div class="text-start small text-secondary">
                <div class="mb-1"><i class="bi bi-telephone me-2"></i>{{ $user->phone ?? 'Belum ada nomor telepon' }}</div>
                <div class="mb-1"><i class="bi bi-geo-alt me-2"></i>{{ $user->address ?? 'Belum ada alamat' }}</div>
                <div><i class="bi bi-calendar3 me-2"></i>Bergabung: {{ $user->created_at->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Form Edit Profil -->
        <div class="editorial-card p-4">
            <h5 class="fw-bold text-black font-display fs-6 mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
                Perbarui Informasi Akun
            </h5>

            <form action="{{ route('admin.profile.update') }}" method="POST">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-black">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3 @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-black">Alamat Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control rounded-3 @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-black">Nomor WhatsApp / HP</label>
                        <input type="text" name="phone" class="form-control rounded-3 @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-black">Alamat / Posisi</label>
                        <input type="text" name="address" class="form-control rounded-3 @error('address') is-invalid @enderror" value="{{ old('address', $user->address) }}" placeholder="Kota / Wilayah">
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <h6 class="fw-bold text-black font-display small mt-4 mb-3 pt-3 border-top" style="border-color: #E7E2DA !important;">
                    Ganti Password (Kosongkan jika tidak ingin mengubah)
                </h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-black">Password Baru</label>
                        <input type="password" name="password" class="form-control rounded-3 @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-black">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-control rounded-3" placeholder="Ulangi password baru">
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

