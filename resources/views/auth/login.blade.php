@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4 p-md-5 border rounded-4 bg-white shadow-sm">
                <!-- Brand Logo Monochrome -->
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center gap-2 mb-2">
                        <span class="bg-black text-white px-2 py-1 fw-bold rounded font-monospace" style="font-size: 0.85rem; letter-spacing: 2px;">ARSY</span>
                        <span class="fw-bold text-black fs-5" style="letter-spacing: 1px;">TENDA</span>
                    </div>
                    <h4 class="fw-bold text-black mb-1">Masuk ke Akun Anda</h4>
                    <p class="text-secondary small mb-0">Silakan login untuk memesan perlengkapan acara</p>
                </div>

                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-black">Alamat Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control rounded-3 @error('email') is-invalid @enderror" placeholder="nama@email.com" required autofocus>
                        @error('email')
                            <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-black">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control rounded-start-3 @error('password') is-invalid @enderror" placeholder="••••••••" required>
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

                    <button type="submit" class="btn btn-dark btn-lg w-100 py-2.5 rounded-3 fw-bold shadow-sm">
                        Masuk Sekarang
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-secondary">
                    Belum memiliki akun?
                    <a href="{{ route('register') }}" class="fw-bold text-black text-decoration-none">Daftar Akun Baru</a>
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
