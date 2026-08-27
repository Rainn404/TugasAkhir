@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Manajemen Variasi Inventaris</h4>
        <p class="text-secondary small mb-0">Kelola varian perlengkapan (Ukuran, Jenis Penutup/Cover, Tinggi Panggung, dll).</p>
    </div>
    <button type="button" class="btn btn-dark btn-sm rounded-3 px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addVariantModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Variasi Baru
    </button>
</div>

<!-- Modal Tambah Variasi Baru -->
<div class="modal fade" id="addVariantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold text-black">Tambah Variasi Inventaris Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.inventory-variants.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-black">Pilih Perlengkapan Utama <span class="text-danger">*</span></label>
                        <select name="inventory_id" class="form-select rounded-3" required>
                            <option value="">-- Pilih Perlengkapan --</option>
                            @foreach($inventories as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->name }} ({{ $inv->category->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-black">Nama Tampilan Variasi <span class="text-danger">*</span></label>
                        <input type="text" name="variant_name" class="form-control rounded-3" placeholder="Contoh: Ukuran 6x12 m / Penutup Full / Cover VIP Gold" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-black">Tipe Atribut <span class="text-danger">*</span></label>
                            <input type="text" name="attribute_type" class="form-control rounded-3" placeholder="size / cover / height / type" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-black">Nilai Atribut <span class="text-danger">*</span></label>
                            <input type="text" name="attribute_value" class="form-control rounded-3" placeholder="6x12 m / Full / VIP Gold" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-black">Penyesuaian Harga / Price Modifier (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="price_modifier" class="form-control rounded-3" value="0" placeholder="0 jika gratis" required>
                        <small class="text-secondary">Nominal tambahan harga sewa jika customer memilih variasi ini.</small>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light btn-sm rounded-2" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark btn-sm rounded-2 px-3 fw-bold">Simpan Variasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Variants Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">Perlengkapan Terkait</th>
                    <th class="py-3 px-3">Nama Variasi</th>
                    <th class="py-3 px-3">Tipe Atribut</th>
                    <th class="py-3 px-3">Nilai Atribut</th>
                    <th class="py-3 px-3 text-end">Penyesuaian Harga</th>
                    <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($variants as $variant)
                    <tr>
                        <td class="py-3 px-4">
                            <strong class="text-black small d-block">{{ $variant->inventory->name ?? 'Inventaris Dihapus' }}</strong>
                            <small class="text-secondary">{{ $variant->inventory->category->name ?? '' }}</small>
                        </td>
                        <td class="py-3 px-3">
                            <span class="fw-semibold text-black small">{{ $variant->variant_name }}</span>
                        </td>
                        <td class="py-3 px-3 small font-monospace text-secondary">
                            {{ $variant->attribute_type }}
                        </td>
                        <td class="py-3 px-3 small font-monospace text-black">
                            {{ $variant->attribute_value }}
                        </td>
                        <td class="py-3 px-3 text-end small">
                            <span class="fw-bold {{ $variant->price_modifier > 0 ? 'text-black' : 'text-success' }}">
                                {{ $variant->price_modifier > 0 ? '+ Rp ' . number_format($variant->price_modifier, 0, ',', '.') : 'Termasuk (Rp 0)' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('admin.inventory-variants.delete', $variant->id) }}" method="POST" onsubmit="return confirm('Hapus variasi ini?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-2 py-1 px-2" title="Hapus">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <i class="bi bi-tags fs-2 d-block mb-1 opacity-50"></i>
                            Belum ada variasi inventaris yang terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($variants->hasPages())
        <div class="p-3 border-top">
            {{ $variants->links() }}
        </div>
    @endif
</div>
@endsection
