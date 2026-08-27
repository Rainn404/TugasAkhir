@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 p-md-5 border rounded-4 bg-white shadow-sm">
                <!-- Brand Logo Monochrome -->
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center gap-2 mb-2">
                        <span class="bg-black text-white px-2 py-1 fw-bold rounded font-monospace" style="font-size: 0.85rem; letter-spacing: 2px;">ARSY</span>
                        <span class="fw-bold text-black fs-5" style="letter-spacing: 1px;">TENDA</span>
                    </div>
                    <h4 class="fw-bold text-black mb-1">Daftar Akun Baru</h4>
                    <p class="text-secondary small mb-0">Buat akun untuk memesan perlengkapan dan mengajukan survei</p>
                </div>

                <form action="{{ route('register') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-black">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control rounded-3 @error('name') is-invalid @enderror" placeholder="Nama Anda" required autofocus>
                        @error('name')
                            <div class="invalid-feedback small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label small fw-semibold text-black">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control rounded-3 @error('email') is-invalid @enderror" placeholder="nama@email.com" required>
                            @error('email')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label small fw-semibold text-black">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="form-control rounded-3 @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx" required>
                            @error('phone')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label small fw-semibold text-black">Alamat Domisili (Opsional)</label>
                        <textarea name="address" id="address" rows="2" class="form-control rounded-3" placeholder="Alamat rumah / domisili...">{{ old('address') }}</textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label small fw-semibold text-black">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control rounded-3 @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required>
                            @error('password')
                                <div class="invalid-feedback small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label small fw-semibold text-black">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control rounded-3" placeholder="Ulangi password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark btn-lg w-100 py-2.5 rounded-3 fw-bold shadow-sm">
                        Daftar Akun Sekarang
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-secondary">
                    Sudah memiliki akun?
                    <a href="{{ route('login') }}" class="fw-bold text-black text-decoration-none">Masuk di sini</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
