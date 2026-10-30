@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
    <div>
        <span class="page-eyebrow">Pengaturan Visual &amp; Media</span>
        <h1 class="page-headline mb-1 font-display">Manajemen Gambar Beranda &amp; Portofolio</h1>
        <p class="page-subtext">Sesuaikan foto banner utama (Hero) dan 4 foto kolase dokumentasi portofolio acara yang tampil di halaman depan website.</p>
    </div>
    <div>
        <a href="{{ route('home') }}#portofolio" target="_blank" class="btn btn-outline-dark rounded-pill px-4 py-2 fw-semibold">
            <i class="bi bi-box-arrow-up-right me-1"></i> Lihat di Website
        </a>
    </div>
</div>

<!-- SECTION 1: HERO IMAGE -->
<div class="card rounded-4 border-0 shadow-sm p-4 mb-4" style="background: #FFFFFF;">
    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4" style="border-color: #E7E2DA !important;">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-dark text-white" style="width: 36px; height: 36px;">
                <i class="bi bi-card-image"></i>
            </div>
            <div>
                <h2 class="fs-6 fw-bold mb-0 text-black font-display">Banner Utama Beranda (Hero Section)</h2>
                <small class="text-secondary">Foto tenda utama di bagian atas halaman beranda di samping tagline.</small>
            </div>
        </div>
        <div>
            @if($media['hero']['image'])
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i> Foto Kustom Aktif
                </span>
            @else
                <span class="badge bg-light text-secondary border rounded-pill px-3 py-1.5 fw-semibold">
                    <i class="bi bi-image me-1"></i> Default Sistem
                </span>
            @endif
        </div>
    </div>

    <div class="row g-4 align-items-center">
        <!-- Preview Box -->
        <div class="col-lg-5">
            <div class="position-relative rounded-3 overflow-hidden border shadow-xs" style="aspect-ratio: 16/11; background: #FAF8F5; border-color: #E7E2DA !important;">
                <img
                    id="preview-hero"
                    src="{{ $media['hero']['url'] }}"
                    alt="{{ $media['hero']['alt'] }}"
                    class="w-100 h-100 object-fit-cover"
                >
                <div class="position-absolute bottom-0 start-0 end-0 p-2 text-white small" style="background: linear-gradient(transparent, rgba(0,0,0,0.65)); font-size: 0.75rem;">
                    <i class="bi bi-info-circle me-1"></i> Rekomendasi rasio 16:11 atau 4:3 (Maksimal 4MB)
                </div>
            </div>
        </div>

        <!-- Form Edit Hero -->
        <div class="col-lg-7">
            <form action="{{ route('admin.content.update-hero') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="heroImageInput" class="form-label small fw-semibold text-black">Ganti Foto Banner Hero</label>
                    <input
                        type="file"
                        name="image"
                        id="heroImageInput"
                        class="form-control rounded-3"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        data-preview-target="#preview-hero"
                    >
                    <small class="text-secondary d-block mt-1">Format didukung: JPG, JPEG, PNG, WEBP. Maksimal 4 MB.</small>
                </div>

                <div class="mb-3">
                    <label for="heroAltInput" class="form-label small fw-semibold text-black">Keterangan / Teks Alternatif (Alt Text)</label>
                    <input
                        type="text"
                        name="alt"
                        id="heroAltInput"
                        class="form-control rounded-3"
                        value="{{ old('alt', $media['hero']['alt']) }}"
                        placeholder="Contoh: Tenda Pesta Arsy Tenda Pelaihari"
                    >
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 pt-2">
                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Simpan Foto Hero
                    </button>
                </div>
            </form>

            @if($media['hero']['image'])
                <form action="{{ route('admin.content.reset', 'homepage_hero_image') }}" method="POST" class="mt-2" onsubmit="return confirm('Kembalikan foto hero ke gambar default sistem?')">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-3 py-1.5 fw-semibold btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset ke Foto Default
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- SECTION 2: PORTFOLIO COLLAGE -->
<div class="d-flex align-items-center justify-content-between mb-3 mt-5">
    <div>
        <h2 class="fs-5 fw-bold mb-1 text-black font-display">Kolase Portofolio Acara (4 Slot Galeri)</h2>
        <p class="text-secondary small mb-0">Kolase foto di halaman beranda tersusun dari 4 slot dengan posisi dan peruntukan berbeda.</p>
    </div>
</div>

<div class="row g-4">
    @foreach($media['portfolio'] as $slot => $item)
        <div class="col-lg-6">
            <div class="card rounded-4 border-0 shadow-sm p-4 h-100" style="background: #FFFFFF;">
                <!-- Card Header -->
                <div class="d-flex align-items-start justify-content-between border-bottom pb-3 mb-3" style="border-color: #E7E2DA !important;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-black text-white rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.72rem;">SLOT {{ $slot }}</span>
                            <h3 class="fs-6 fw-bold mb-0 text-black font-display">{{ $item['name'] }}</h3>
                        </div>
                        <small class="text-secondary" style="font-size: 0.8rem;">{{ $item['desc'] }}</small>
                    </div>
                    <div>
                        @if($item['image'])
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.72rem;">
                                Kustom
                            </span>
                        @else
                            <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.72rem;">
                                Default
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Preview Frame -->
                <div class="position-relative rounded-3 overflow-hidden border mb-3 shadow-xs" style="aspect-ratio: 16/10; background: #FAF8F5; border-color: #E7E2DA !important;">
                    <img
                        id="preview-slot-{{ $slot }}"
                        src="{{ $item['url'] }}"
                        alt="{{ $item['alt'] }}"
                        class="w-100 h-100 object-fit-cover"
                    >
                </div>

                <!-- Form Upload per Slot -->
                <form action="{{ route('admin.content.update-portfolio', $slot) }}" method="POST" enctype="multipart/form-data" class="mt-auto">
                    @csrf
                    <div class="mb-3">
                        <label for="slotImage{{ $slot }}" class="form-label small fw-semibold text-black">Upload Gambar Baru</label>
                        <input
                            type="file"
                            name="image"
                            id="slotImage{{ $slot }}"
                            class="form-control form-control-sm rounded-3"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            data-preview-target="#preview-slot-{{ $slot }}"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="slotAlt{{ $slot }}" class="form-label small fw-semibold text-black">Keterangan / Alt Text</label>
                        <input
                            type="text"
                            name="alt"
                            id="slotAlt{{ $slot }}"
                            class="form-control form-control-sm rounded-3"
                            value="{{ old('alt', $item['alt']) }}"
                            placeholder="Deskripsi foto slot {{ $slot }}..."
                        >
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-1">
                        <button type="submit" class="btn btn-dark btn-sm rounded-pill px-3 py-1.5 fw-semibold">
                            <i class="bi bi-check2 me-1"></i> Simpan Slot {{ $slot }}
                        </button>
                    </div>
                </form>

                @if($item['image'])
                    <div class="mt-2 text-end">
                        <form action="{{ route('admin.content.reset', $item['key']) }}" method="POST" class="d-inline" onsubmit="return confirm('Kembalikan foto slot {{ $slot }} ke default?')">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.75rem;" title="Reset ke default sistem">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset ke Default
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Instant Client-Side Image Preview Handler
        const fileInputs = document.querySelectorAll('input[type="file"][data-preview-target]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function () {
                const targetSelector = this.getAttribute('data-preview-target');
                const previewImg = document.querySelector(targetSelector);
                if (previewImg && this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });
    });
</script>
@endpush
@endsection
