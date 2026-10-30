@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
    <div>
        <span class="page-eyebrow">Katalog Gudang</span>
        <h1 class="page-headline mb-1 font-display">Manajemen Inventaris Perlengkapan</h1>
        <p class="page-subtext">Kelola stok fisik, harga sewa, satuan unit, dan foto perlengkapan acara.</p>
    </div>
    <div>
        <button type="button" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Inventaris Baru
        </button>
    </div>
</div>

<!-- Modal Tambah Inventaris Baru -->
<div class="modal fade" id="addInventoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="modal-title fw-bold text-black font-display fs-6">Tambah Inventaris Perlengkapan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.inventories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="invCategoryId" class="form-label small fw-semibold text-black">Kategori Perlengkapan <span class="text-danger">*</span></label>
                            <select name="category_id" id="invCategoryId" class="form-select rounded-3" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="invNameInput" class="form-label small fw-semibold text-black">Nama Perlengkapan <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="invNameInput" class="form-control rounded-3" placeholder="Contoh: Tenda Roder 10x20 m" required>
                        </div>
                        <div class="col-md-4">
                            <label for="invPriceInput" class="form-label small fw-semibold text-black">Harga Dasar Sewa (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="invPriceInput" class="form-control rounded-3" placeholder="Contoh: 1500000" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label for="invStockInput" class="form-label small fw-semibold text-black">Total Stok Fisik <span class="text-danger">*</span></label>
                            <input type="number" name="stock" id="invStockInput" class="form-control rounded-3" placeholder="Contoh: 10" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label for="invUnitInput" class="form-label small fw-semibold text-black">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="unit" id="invUnitInput" class="form-control rounded-3" placeholder="unit / set / pcs / meter" required>
                        </div>
                        <div class="col-12">
                            <label for="invDescTextarea" class="form-label small fw-semibold text-black">Deskripsi Perlengkapan</label>
                            <textarea name="description" id="invDescTextarea" rows="2" class="form-control rounded-3" placeholder="Keterangan spesifikasi perlengkapan..."></textarea>
                        </div>
                        <div class="col-12">
                            <label for="invImageInput" class="form-label small fw-semibold text-black">Upload Foto Produk (Opsional)</label>
                            <input type="file" name="image" id="invImageInput" class="form-control rounded-3" accept="image/*">
                            <small class="text-secondary">Format: JPG, PNG, WEBP (Maks. 2MB)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                    <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">Simpan Perlengkapan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Inventories Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>Perlengkapan</th>
                    <th>Kategori</th>
                    <th class="text-end">Harga Dasar</th>
                    <th class="text-center">Stok Total</th>
                    <th class="text-center">Variasi</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventories as $inv)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-light border d-flex align-items-center justify-content-center overflow-hidden" style="width: 48px; height: 48px; min-width: 48px; border-color: #DDD8D0 !important;">
                                    @if($inv->image)
                                        <img src="{{ asset('storage/' . $inv->image) }}" alt="{{ $inv->name }}" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <i class="bi bi-box-seam text-secondary"></i>
                                    @endif
                                </div>
                                <div>
                                    <strong class="text-black small d-block">{{ $inv->name }}</strong>
                                    <small class="text-secondary font-monospace" style="font-size: 0.75rem;">{{ $inv->slug }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $inv->category->name ?? '-' }}</td>
                        <td class="text-end fw-bold text-black small">
                            Rp {{ number_format($inv->price, 0, ',', '.') }} / {{ $inv->unit }}
                        </td>
                        <td class="text-center">
                            <span class="badge-status {{ $inv->stock > 10 ? 'badge-status--success' : ($inv->stock > 0 ? 'badge-status--warning' : 'badge-status--danger') }}" style="font-size: 0.72rem;">
                                {{ $inv->stock }} {{ $inv->unit }}
                            </span>
                        </td>
                        <td class="text-center small">
                            <span class="badge-status badge-status--neutral" style="font-size: 0.72rem;">{{ $inv->variants->count() }} Variasi</span>
                        </td>
                        <td class="text-center">
                            <span class="badge-status {{ $inv->status === 'available' ? 'badge-status--success' : 'badge-status--neutral' }}" style="font-size: 0.72rem;">
                                {{ strtoupper($inv->status) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <!-- Button Trigger Edit Modal -->
                                <button type="button" class="btn btn-outline-dark btn-sm rounded-circle p-2" data-bs-toggle="modal" data-bs-target="#editModal{{ $inv->id }}" title="Edit" aria-label="Edit inventaris {{ $inv->name }}" style="width: 32px; height: 32px;">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- Button Delete -->
                                <form action="{{ route('admin.inventories.delete', $inv->id) }}" method="POST" onsubmit="return confirm('Hapus inventaris ini beserta seluruh variasinya?')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-2" title="Hapus" aria-label="Hapus inventaris {{ $inv->name }}" style="width: 32px; height: 32px;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>

                            <!-- Modal Edit Inventaris -->
                            <div class="modal fade text-start" id="editModal{{ $inv->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                                            <h5 class="modal-title fw-bold text-black font-display fs-6">Edit Inventaris #{{ $inv->id }} - {{ $inv->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.inventories.update', $inv->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="modal-body p-4">
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
                                            <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                                                <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">Simpan Perubahan</button>
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
        <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
            {{ $inventories->links() }}
        </div>
    @endif
</div>
@endsection
