@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
    <div>
        <span class="page-eyebrow">Kustomisasi Produk</span>
        <h1 class="page-headline mb-1 font-display">Manajemen Variasi Inventaris</h1>
        <p class="page-subtext">Kelola opsi ukuran, jenis cover/penutup, dan tinggi panggung beserta penyesuaian harganya.</p>
    </div>
    <div>
        <button type="button" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addVariantModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Variasi Baru
        </button>
    </div>
</div>

<!-- Modal Tambah Variasi Baru -->
<div class="modal fade" id="addVariantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="modal-title fw-bold text-black font-display fs-6">Tambah Variasi Inventaris Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.inventory-variants.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
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
                <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                    <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">Simpan Variasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Variants Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>Perlengkapan Terkait</th>
                    <th>Nama Variasi</th>
                    <th>Tipe Atribut</th>
                    <th>Nilai Atribut</th>
                    <th class="text-end">Penyesuaian Harga</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($variants as $variant)
                    <tr>
                        <td>
                            <strong class="text-black small d-block">{{ $variant->inventory->name ?? 'Inventaris Dihapus' }}</strong>
                            <small class="text-secondary">{{ $variant->inventory->category->name ?? '' }}</small>
                        </td>
                        <td>
                            <span class="fw-semibold text-black small">{{ $variant->variant_name }}</span>
                        </td>
                        <td class="small font-monospace text-secondary">
                            {{ $variant->attribute_type }}
                        </td>
                        <td class="small font-monospace text-black">
                            {{ $variant->attribute_value }}
                        </td>
                        <td class="text-end small">
                            <span class="fw-bold {{ $variant->price_modifier > 0 ? 'text-black' : 'text-success' }}">
                                {{ $variant->price_modifier > 0 ? '+ Rp ' . number_format($variant->price_modifier, 0, ',', '.') : 'Termasuk (Rp 0)' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <form action="{{ route('admin.inventory-variants.delete', $variant->id) }}" method="POST" onsubmit="return confirm('Hapus variasi ini?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-2" title="Hapus" style="width: 32px; height: 32px;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <i class="bi bi-tags fs-2 d-block mb-1 opacity-50"></i>
                            Belum ada variasi inventaris perlengkapan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($variants->hasPages())
        <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
            {{ $variants->links() }}
        </div>
    @endif
</div>
@endsection
