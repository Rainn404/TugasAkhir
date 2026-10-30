@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="editorial-card p-4 p-md-5">
                <!-- Brand Logo & Header -->
                <div class="text-center mb-4">
                    <a href="{{ route('home') }}" class="brand-arsy text-decoration-none mb-3 d-inline-flex">
                        <span class="brand-mark">ARSY</span>
                        <span class="brand-name">TENDA</span>
                    </a>
                    <h1 class="page-headline mb-1 font-display fs-4">Daftar Akun Baru</h1>
                    <p class="page-subtext small">Buat akun untuk memesan perlengkapan dan mengajukan survei lokasi.</p>
                </div>

                <form action="{{ route('register') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-black">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" autocomplete="name" value="{{ old('name') }}" class="form-control rounded-3 @error('name') is-invalid @enderror" placeholder="Nama Anda" required autofocus>
                        @error('name')
                            <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-semibold text-black">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" autocomplete="username" value="{{ old('email') }}" class="form-control rounded-3 @error('email') is-invalid @enderror" placeholder="nama@email.com" required>
                            @error('email')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold text-black">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" autocomplete="tel" value="{{ old('phone') }}" class="form-control rounded-3 @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx" required>
                            @error('phone')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label small fw-semibold text-black">Alamat Domisili (Opsional)</label>
                        <textarea name="address" id="address" autocomplete="street-address" rows="2" class="form-control rounded-3" placeholder="Alamat rumah / domisili...">{{ old('address') }}</textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label small fw-semibold text-black">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" autocomplete="new-password" class="form-control rounded-start-3 @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required>
                                <button type="button" class="btn btn-outline-secondary rounded-end-3 password-toggle" data-target="password" aria-label="Lihat password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label small fw-semibold text-black">Konfirmasi Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="form-control rounded-start-3" placeholder="Ulangi password" required>
                                <button type="button" class="btn btn-outline-secondary rounded-end-3 password-toggle" data-target="password_confirmation" aria-label="Lihat password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-hero-primary w-100 py-2.5 justify-content-center text-center">
                        Daftar Akun Sekarang
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-secondary" style="border-color: #E7E2DA !important;">
                    Sudah memiliki akun?
                    <a href="{{ route('login') }}" class="fw-bold text-black text-decoration-none ms-1">Masuk di sini</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.password-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (!input || !icon) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('bi-eye', isPassword);
                icon.classList.toggle('bi-eye-slash', !isPassword);
                this.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Lihat password');
            });
        });
    });
</script>
@endpush
