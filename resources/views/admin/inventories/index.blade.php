@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Manajemen Inventaris Perlengkapan</h4>
        <p class="text-secondary small mb-0">Kelola stok fisik, harga sewa, satuan, dan foto perlengkapan acara.</p>
    </div>
    <button type="button" class="btn btn-dark btn-sm rounded-3 px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Inventaris Baru
    </button>
</div>

<!-- Modal Tambah Inventaris Baru -->
<div class="modal fade" id="addInventoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold text-black">Tambah Inventaris Perlengkapan Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.inventories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Kategori Perlengkapan <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select rounded-3" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Nama Perlengkapan <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Tenda Roder 10x20 m" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-black">Harga Dasar Sewa (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control rounded-3" placeholder="Contoh: 1500000" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-black">Total Stok Fisik <span class="text-danger">*</span></label>
                            <input type="number" name="stock" class="form-control rounded-3" placeholder="Contoh: 10" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-black">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control rounded-3" placeholder="unit / set / pcs / meter" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-black">Deskripsi Perlengkapan</label>
                            <textarea name="description" rows="2" class="form-control rounded-3" placeholder="Keterangan spesifikasi perlengkapan..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-black">Upload Foto Produk (Opsional)</label>
                            <input type="file" name="image" class="form-control rounded-3" accept="image/*">
                            <small class="text-secondary">Format: JPG, PNG, WEBP (Maks. 2MB)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light btn-sm rounded-2" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark btn-sm rounded-2 px-3 fw-bold">Simpan Perlengkapan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Inventories Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">Perlengkapan</th>
                    <th class="py-3 px-3">Kategori</th>
                    <th class="py-3 px-3 text-end">Harga Dasar</th>
                    <th class="py-3 px-3 text-center">Stok Total</th>
                    <th class="py-3 px-3 text-center">Variasi</th>
                    <th class="py-3 px-3 text-center">Status</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventories as $inv)
                    <tr>
                        <td class="py-3 px-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-light border d-flex align-items-center justify-content-center overflow-hidden" style="width: 48px; height: 48px; min-width: 48px;">
                                    @if($inv->image)
                                        <img src="{{ asset('storage/' . $inv->image) }}" alt="{{ $inv->name }}" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <i class="bi bi-box-seam text-secondary"></i>
                                    @endif
                                </div>
                                <div>
                                    <strong class="text-black small d-block">{{ $inv->name }}</strong>
                                    <small class="text-secondary">{{ $inv->slug }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-3 small">{{ $inv->category->name ?? '-' }}</td>
                        <td class="py-3 px-3 text-end fw-bold text-black small">
                            Rp {{ number_format($inv->price, 0, ',', '.') }} / {{ $inv->unit }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="badge {{ $inv->stock > 10 ? 'bg-success' : ($inv->stock > 0 ? 'bg-warning text-dark' : 'bg-danger') }} small">
                                {{ $inv->stock }} {{ $inv->unit }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-center small">
                            <span class="badge bg-light text-dark border">{{ $inv->variants->count() }} Variasi</span>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="badge {{ $inv->status === 'available' ? 'bg-success' : 'bg-secondary' }} small">
                                {{ strtoupper($inv->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <!-- Button Trigger Edit Modal -->
                                <button type="button" class="btn btn-outline-dark btn-sm rounded-2 py-1 px-2" data-bs-toggle="modal" data-bs-target="#editModal{{ $inv->id }}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- Button Delete -->
                                <form action="{{ route('admin.inventories.delete', $inv->id) }}" method="POST" onsubmit="return confirm('Hapus inventaris ini beserta seluruh variasinya?')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-2 py-1 px-2" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Modal Edit Inventaris -->
                            <div class="modal fade text-start" id="editModal{{ $inv->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-bottom">
                                            <h6 class="modal-title fw-bold text-black">Edit Inventaris #{{ $inv->id }} - {{ $inv->name }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.inventories.update', $inv->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold text-black">Kategori</label>
                                                        <select name="category_id" class="form-select rounded-3" required>
                                                            @foreach($categories as $cat)
                                                                <option value="{{ $cat->id }}" {{ $inv->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold text-black">Nama Perlengkapan</label>
                                                        <input type="text" name="name" class="form-control rounded-3" value="{{ $inv->name }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-semibold text-black">Harga Dasar (Rp)</label>
                                                        <input type="number" name="price" class="form-control rounded-3" value="{{ $inv->price }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-semibold text-black">Total Stok</label>
                                                        <input type="number" name="stock" class="form-control rounded-3" value="{{ $inv->stock }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-semibold text-black">Satuan</label>
                                                        <input type="text" name="unit" class="form-control rounded-3" value="{{ $inv->unit }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold text-black">Status Inventaris</label>
                                                        <select name="status" class="form-select rounded-3" required>
                                                            <option value="available" {{ $inv->status === 'available' ? 'selected' : '' }}>Tersedia (Available)</option>
                                                            <option value="maintenance" {{ $inv->status === 'maintenance' ? 'selected' : '' }}>Dalam Perbaikan (Maintenance)</option>
                                                            <option value="inactive" {{ $inv->status === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold text-black">Ganti Foto Produk (Opsional)</label>
                                                        <input type="file" name="image" class="form-control rounded-3" accept="image/*">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label small fw-semibold text-black">Deskripsi</label>
                                                        <textarea name="description" rows="2" class="form-control rounded-3">{{ $inv->description }}</textarea>
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="bi bi-box fs-2 d-block mb-1 opacity-50"></i>
                            Belum ada inventaris perlengkapan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($inventories->hasPages())
        <div class="p-3 border-top">
            {{ $inventories->links() }}
        </div>
    @endif
</div>
@endsection
