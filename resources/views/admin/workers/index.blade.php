@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Manajemen Pekerja & Tim Lapangan</h4>
        <p class="text-secondary small mb-0">Daftarkan email petugas lapangan agar saat login otomatis mendapatkan hak akses operasional.</p>
    </div>
    @if(auth()->user()->role === 'admin')
        <button type="button" class="btn btn-dark btn-sm rounded-3 px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addWorkerModal">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Pekerja Baru
        </button>
    @endif
</div>

<!-- Info Alert -->
<div class="alert alert-light border rounded-3 p-3 mb-4 shadow-sm">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill text-black fs-5"></i>
        <div class="small">
            <strong>Hak Akses Otomatis:</strong> Setiap alamat email yang didaftarkan di halaman ini akan otomatis dikenali oleh sistem sebagai <strong>Petugas Lapangan</strong> saat login, sehingga dapat melihat agenda kegiatan, manifest muatan perlengkapan, dan memperbarui status lapangan.
        </div>
    </div>
</div>

<!-- Modal Tambah Pekerja Baru -->
<div class="modal fade" id="addWorkerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold text-black">Tambah Akun Pekerja / Petugas Lapangan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.workers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Nama Lengkap Pekerja <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Rian (Crew Lapangan)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Alamat Email Login <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="emailpekerja@arsytenda.com" required>
                            <small class="text-secondary">Email ini yang digunakan pekerja untuk login.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control rounded-3" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Alamat Domisili / Basecamp</label>
                            <input type="text" name="address" class="form-control rounded-3" placeholder="Contoh: Gudang Pelaihari">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Password Akun <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control rounded-3" placeholder="Minimal 8 karakter" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control rounded-3" placeholder="Ulangi password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light btn-sm rounded-2" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark btn-sm rounded-2 px-3 fw-bold">Simpan Akun Pekerja</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Workers Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">Nama Pekerja</th>
                    <th class="py-3 px-3">Email Login</th>
                    <th class="py-3 px-3">Nomor Telepon</th>
                    <th class="py-3 px-3">Alamat / Basecamp</th>
                    <th class="py-3 px-3 text-center">Role Akses</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workers as $worker)
                    <tr>
                        <td class="py-3 px-4">
                            <strong class="text-black small d-block">{{ $worker->name }}</strong>
                            <small class="text-secondary">Dibuat: {{ $worker->created_at->format('d M Y') }}</small>
                        </td>
                        <td class="py-3 px-3 small font-monospace text-black">
                            {{ $worker->email }}
                        </td>
                        <td class="py-3 px-3 small font-monospace">
                            {{ $worker->phone ?? '-' }}
                        </td>
                        <td class="py-3 px-3 small text-secondary" style="max-width: 220px;">
                            {{ $worker->address ?? '-' }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="badge {{ $worker->role === 'admin' ? 'bg-dark' : 'bg-primary' }} small">
                                {{ strtoupper($worker->role) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if(auth()->user()->role === 'admin')
                                <div class="d-flex justify-content-center gap-1">
                                    <!-- Button Edit Modal -->
                                    <button type="button" class="btn btn-outline-dark btn-sm rounded-2 py-1 px-2" data-bs-toggle="modal" data-bs-target="#editWorkerModal{{ $worker->id }}" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    @if($worker->id !== auth()->id())
                                        <!-- Button Delete -->
                                        <form action="{{ route('admin.workers.delete', $worker->id) }}" method="POST" onsubmit="return confirm('Hapus akun pekerja ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-2 py-1 px-2" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Modal Edit Pekerja -->
                                <div class="modal fade text-start" id="editWorkerModal{{ $worker->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header border-bottom">
                                                <h6 class="modal-title fw-bold text-black">Edit Akun Pekerja #{{ $worker->id }} - {{ $worker->name }}</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('admin.workers.update', $worker->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
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
                                                <div class="modal-footer border-top">
                                                    <button type="button" class="btn btn-light btn-sm rounded-2" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-dark btn-sm rounded-2 px-3 fw-bold">Simpan Perubahan</button>
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
        <div class="p-3 border-top">
            {{ $workers->links() }}
        </div>
    @endif
</div>
@endsection
