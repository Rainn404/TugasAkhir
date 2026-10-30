@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="editorial-card p-4 p-md-5">
                <!-- Brand Logo & Header -->
                <div class="text-center mb-4">
                    <a href="{{ route('home') }}" class="brand-arsy text-decoration-none mb-3 d-inline-flex">
                        <span class="brand-mark">ARSY</span>
                        <span class="brand-name">TENDA</span>
                    </a>
                    <h1 class="page-headline mb-1 font-display fs-4">Masuk ke Akun Anda</h1>
                    <p class="page-subtext small">Silakan login untuk memesan perlengkapan dan memantau status acara Anda.</p>
                </div>

                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-black">Alamat Email</label>
                        <input type="email" name="email" id="email" autocomplete="username" value="{{ old('email') }}" class="form-control rounded-3 @error('email') is-invalid @enderror" placeholder="nama@email.com" required autofocus>
                        @error('email')
                            <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-black">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" autocomplete="current-password" class="form-control rounded-start-3 @error('password') is-invalid @enderror" placeholder="••••••••" required>
                            <button type="button" class="btn btn-outline-secondary rounded-end-3 password-toggle" data-target="password" aria-label="Lihat password">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label small text-secondary" for="remember">
                                Ingat Saya
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-hero-primary w-100 py-2.5 justify-content-center text-center">
                        Masuk Sekarang
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-secondary" style="border-color: #E7E2DA !important;">
                    Belum memiliki akun?
                    <a href="{{ route('register') }}" class="fw-bold text-black text-decoration-none ms-1">Daftar Akun Baru</a>
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
