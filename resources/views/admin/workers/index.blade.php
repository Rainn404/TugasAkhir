@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
    <div>
        <span class="page-eyebrow">Tim Operasional</span>
        <h1 class="page-headline mb-1 font-display">Manajemen Pekerja &amp; Tim Lapangan</h1>
        <p class="page-subtext">Daftarkan akun petugas lapangan agar dapat mengelola jadwal survei dan manifest perlengkapan di lokasi.</p>
    </div>
    @if(auth()->user()->role === 'admin')
        <div>
            <button type="button" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addWorkerModal">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah Pekerja Baru
            </button>
        </div>
    @endif
</div>

<!-- Info Alert -->
<div class="editorial-card p-3 mb-4" style="background: #FAF8F5;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill text-black fs-5"></i>
        <div class="small text-secondary">
            <strong>Hak Akses Otomatis:</strong> Setiap alamat email yang didaftarkan di halaman ini akan otomatis dikenali oleh sistem sebagai <strong>Petugas Lapangan</strong> saat login, sehingga dapat melihat agenda kegiatan, rincian survei, dan memperbarui status di lokasi.
        </div>
    </div>
</div>

<!-- Modal Tambah Pekerja Baru -->
<div class="modal fade" id="addWorkerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="modal-title fw-bold text-black font-display fs-6">Tambah Akun Pekerja / Petugas Lapangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.workers.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="workerNameInput" class="form-label small fw-semibold text-black">Nama Lengkap Pekerja <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="workerNameInput" class="form-control rounded-3" placeholder="Contoh: Rian (Crew Lapangan)" required>
                        </div>
                        <div class="col-md-6">
                            <label for="workerEmailInput" class="form-label small fw-semibold text-black">Alamat Email Login <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="workerEmailInput" class="form-control rounded-3" placeholder="emailpekerja@arsytenda.com" required>
                            <small class="text-secondary">Email ini yang digunakan pekerja untuk login.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="workerPhoneInput" class="form-label small fw-semibold text-black">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="workerPhoneInput" class="form-control rounded-3" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="col-md-6">
                            <label for="workerAddressInput" class="form-label small fw-semibold text-black">Alamat Domisili / Basecamp</label>
                            <input type="text" name="address" id="workerAddressInput" class="form-control rounded-3" placeholder="Contoh: Gudang Pelaihari">
                        </div>
                        <div class="col-md-6">
                            <label for="workerPasswordInput" class="form-label small fw-semibold text-black">Password Akun <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="workerPasswordInput" class="form-control rounded-3" placeholder="Minimal 8 karakter" required>
                        </div>
                        <div class="col-md-6">
                            <label for="workerPasswordConfirmInput" class="form-label small fw-semibold text-black">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="workerPasswordConfirmInput" class="form-control rounded-3" placeholder="Ulangi password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                    <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">Simpan Akun Pekerja</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Workers Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>Nama Pekerja</th>
                    <th>Email Login</th>
                    <th>Nomor Telepon</th>
                    <th>Alamat / Basecamp</th>
                    <th class="text-center">Role Akses</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workers as $worker)
                    <tr>
                        <td>
                            <strong class="text-black small d-block">{{ $worker->name }}</strong>
                            <small class="text-secondary">Dibuat: {{ $worker->created_at->format('d M Y') }}</small>
                        </td>
                        <td class="small font-monospace text-black">
                            {{ $worker->email }}
                        </td>
                        <td class="small font-monospace text-secondary">
                            {{ $worker->phone ?? '-' }}
                        </td>
                        <td class="small text-secondary" style="max-width: 220px;">
                            {{ $worker->address ?? '-' }}
                        </td>
                        <td class="text-center">
                            <span class="badge-status {{ $worker->role === 'admin' ? 'badge-status--neutral' : 'badge-status--info' }}" style="font-size: 0.72rem;">
                                {{ strtoupper($worker->role) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if(auth()->user()->role === 'admin')
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- Button Edit Modal -->
                                    <button type="button" class="btn btn-outline-dark btn-sm rounded-circle p-1" style="width: 30px; height: 30px;" data-bs-toggle="modal" data-bs-target="#editWorkerModal{{ $worker->id }}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    @if($worker->id !== auth()->id())
                                        <!-- Button Delete -->
                                        <form action="{{ route('admin.workers.delete', $worker->id) }}" method="POST" onsubmit="return confirm('Hapus akun pekerja ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1" style="width: 30px; height: 30px;" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Modal Edit Pekerja -->
                                <div class="modal fade text-start" id="editWorkerModal{{ $worker->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                                                <h5 class="modal-title fw-bold text-black font-display fs-6">Edit Akun Pekerja #{{ $worker->id }} - {{ $worker->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('admin.workers.update', $worker->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body p-4">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label small fw-semibold text-black">Nama Lengkap</label>
                                                            <input type="text" name="name" class="form-control rounded-3" value="{{ $worker->name }}" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small fw-semibold text-black">Alamat Email Login</label>
                                                            <input type="email" name="email" class="form-control rounded-3" value="{{ $worker->email }}" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small fw-semibold text-black">Nomor Telepon / WA</label>
                                                            <input type="text" name="phone" class="form-control rounded-3" value="{{ $worker->phone }}" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small fw-semibold text-black">Alamat / Posisi</label>
                                                            <input type="text" name="address" class="form-control rounded-3" value="{{ $worker->address }}">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small fw-semibold text-black">Password Baru (Opsional)</label>
                                                            <input type="password" name="password" class="form-control rounded-3" placeholder="Kosongkan jika tidak ingin diubah">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small fw-semibold text-black">Konfirmasi Password Baru</label>
                                                            <input type="password" name="password_confirmation" class="form-control rounded-3" placeholder="Ulangi password">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                                                    <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-secondary small">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <i class="bi bi-person-badge fs-2 d-block mb-1 opacity-50"></i>
                            Belum ada akun pekerja terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($workers->hasPages())
        <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
            {{ $workers->links() }}
        </div>
    @endif
</div>
@endsection
