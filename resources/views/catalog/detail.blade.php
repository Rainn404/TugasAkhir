@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-secondary text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('home') }}#katalog" class="text-secondary text-decoration-none">Katalog Perlengkapan</a></li>
            <li class="breadcrumb-item active text-black fw-semibold" aria-current="page">{{ $inventory->name }}</li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Kolom Kiri: Foto Perlengkapan -->
        <div class="col-lg-6">
            <div class="card border rounded-4 overflow-hidden bg-white sticky-top" style="top: 90px;">
                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 380px;">
                    @if($inventory->image)
                        <img src="{{ asset('storage/' . $inventory->image) }}" alt="{{ $inventory->name }}" class="w-100 h-100 object-fit-cover">
                    @else
                        <div class="text-center p-4">
                            @switch($inventory->category->slug)
                                @case('tenda')
                                    <i class="bi bi-triangle fs-1 text-secondary opacity-50" style="font-size: 5rem !important;"></i>
                                    @break
                                @case('meja')
                                    <i class="bi bi-square fs-1 text-secondary opacity-50" style="font-size: 5rem !important;"></i>
                                    @break
                                @case('kursi')
                                    <i class="bi bi-layers fs-1 text-secondary opacity-50" style="font-size: 5rem !important;"></i>
                                    @break
                                @case('panggung')
                                    <i class="bi bi-display fs-1 text-secondary opacity-50" style="font-size: 5rem !important;"></i>
                                    @break
                                @case('blower')
                                    <i class="bi bi-wind fs-1 text-secondary opacity-50" style="font-size: 5rem !important;"></i>
                                    @break
                                @default
                                    <i class="bi bi-box fs-1 text-secondary opacity-50" style="font-size: 5rem !important;"></i>
                            @endswitch
                            <div class="text-secondary fw-semibold mt-2">{{ $inventory->category->name }}</div>
                        </div>
                    @endif
                </div>
                <div class="p-4 border-top">
                    <h6 class="fw-bold text-black mb-2">Deskripsi Perlengkapan</h6>
                    <p class="text-secondary small mb-0 lh-base">
                        {{ $inventory->description ?? 'Perlengkapan berkualitas tinggi siap sewa untuk mendukung kesuksesan berbagai jenis acara Anda.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Konfigurator Perlengkapan & Pemesanan -->
        <div class="col-lg-6">
            <div class="card p-4 p-md-5 border rounded-4 bg-white shadow-sm">
                <span class="badge bg-black text-white px-3 py-1.5 rounded-pill small mb-2 d-inline-block" style="width: fit-content;">
                    {{ $inventory->category->name }}
                </span>
                <h2 class="fw-bold text-black mb-2">{{ $inventory->name }}</h2>
                
                <div class="d-flex align-items-baseline gap-2 mb-4 pb-3 border-bottom">
                    <span class="text-secondary small">Harga Dasar:</span>
                    <span class="fs-4 fw-bold text-black" id="basePriceDisplay">
                        Rp {{ number_format($inventory->price, 0, ',', '.') }}
                    </span>
                    <span class="text-secondary small">/ {{ $inventory->unit }}</span>
                </div>

                <!-- Form Konfigurasi -->
                <form action="{{ route('cart.add') }}" method="POST" id="configuratorForm">
                    @csrf
                    <input type="hidden" name="inventory_id" value="{{ $inventory->id }}">

                    <!-- 1. Tentukan Tanggal Acara -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-black small">
                            <i class="bi bi-calendar-event me-1"></i> Tanggal Acara <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="event_date" id="eventDateInput"
                               value="{{ session('cart_date', date('Y-m-d', strtotime('+1 day'))) }}"
                               min="{{ date('Y-m-d') }}"
                               class="form-control rounded-3" required>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-secondary">Stok dicek secara *real-time* per tanggal acara.</small>
                            <span id="availabilityBadge" class="badge badge-available px-2.5 py-1.5 rounded-pill small">
                                <i class="bi bi-check-circle me-1"></i> Stok Tersedia
                            </span>
                        </div>
                    </div>

                    <!-- 2. Konfigurasi Variasi (Ukuran / Penutup / Varian) -->
                    @if($inventory->variants->isNotEmpty())
                        <div class="mb-4">
                            <label class="form-label fw-bold text-black small">
                                <i class="bi bi-sliders me-1"></i> Pilih Konfigurasi / Variasi
                            </label>
                            
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check p-3 border rounded-3 variant-option active bg-light" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="variant_id" id="variant_default" value="" checked data-price-mod="0">
                                    <label class="form-check-label d-flex justify-content-between w-100 cursor-pointer" for="variant_default">
                                        <span class="fw-semibold text-black">Standar (Tanpa Variasi Tambahan)</span>
                                        <span class="text-secondary small">+ Rp 0</span>
                                    </label>
                                </div>

                                @foreach($inventory->variants as $variant)
                                    <div class="form-check p-3 border rounded-3 variant-option" style="cursor: pointer;">
                                        <input class="form-check-input" type="radio" name="variant_id" id="variant_{{ $variant->id }}" value="{{ $variant->id }}" data-price-mod="{{ $variant->price_modifier }}">
                                        <label class="form-check-label d-flex justify-content-between w-100 cursor-pointer" for="variant_{{ $variant->id }}">
                                            <span class="fw-semibold text-black">{{ $variant->variant_name }}</span>
                                            <span class="text-secondary small">
                                                {{ $variant->price_modifier > 0 ? '+ Rp ' . number_format($variant->price_modifier, 0, ',', '.') : 'Termasuk' }}
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 3. Tentukan Jumlah (Quantity Stepper) -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-black small">
                            <i class="bi bi-123 me-1"></i> Jumlah Unit Sewa
                        </label>
                        <div class="input-group" style="max-width: 180px;">
                            <button type="button" class="btn btn-outline-dark px-3" id="btnMinus">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" name="quantity" id="quantityInput" value="1" min="1" max="{{ $inventory->stock }}" class="form-control text-center fw-bold fs-6" readonly>
                            <button type="button" class="btn btn-outline-dark px-3" id="btnPlus">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <small class="text-secondary mt-1 d-block">Satuan: {{ $inventory->unit }}</small>
                    </div>

                    <!-- 4. Rincian Kalkulasi Harga -->
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>Harga Satuan Unit</span>
                            <span id="unitPriceDisplay">Rp {{ number_format($inventory->price, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between small text-secondary mb-2">
                            <span>Jumlah Dipesan</span>
                            <span id="qtyDisplay">1 {{ $inventory->unit }}</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold text-black border-top pt-2 fs-5">
                            <span>Subtotal</span>
                            <span id="subtotalDisplay">Rp {{ number_format($inventory->price, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <button type="submit" class="btn btn-dark btn-lg w-100 py-3 rounded-3 fw-bold shadow-sm" id="btnAddToCart">
                        <i class="bi bi-cart-plus me-2"></i> Tambahkan ke Keranjang Pesanan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const basePrice = {{ (float) $inventory->price }};
    const maxStock = {{ (int) $inventory->stock }};
    const qtyInput = document.getElementById('quantityInput');
    const btnMinus = document.getElementById('btnMinus');
    const btnPlus = document.getElementById('btnPlus');
    const unitPriceDisplay = document.getElementById('unitPriceDisplay');
    const qtyDisplay = document.getElementById('qtyDisplay');
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const variantRadios = document.querySelectorAll('input[name="variant_id"]');

    function calculateTotal() {
        let currentQty = parseInt(qtyInput.value) || 1;
        let selectedModifier = 0;

        variantRadios.forEach(radio => {
            if (radio.checked) {
                selectedModifier = parseFloat(radio.getAttribute('data-price-mod')) || 0;
            }
        });

        const effectiveUnitPrice = basePrice + selectedModifier;
        const subtotal = effectiveUnitPrice * currentQty;

        unitPriceDisplay.textContent = 'Rp ' + effectiveUnitPrice.toLocaleString('id-ID');
        qtyDisplay.textContent = currentQty + ' {{ $inventory->unit }}';
        subtotalDisplay.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    }

    btnMinus.addEventListener('click', function () {
        let val = parseInt(qtyInput.value) || 1;
        if (val > 1) {
            qtyInput.value = val - 1;
            calculateTotal();
        }
    });

    btnPlus.addEventListener('click', function () {
        let val = parseInt(qtyInput.value) || 1;
        if (val < maxStock) {
            qtyInput.value = val + 1;
            calculateTotal();
        }
    });

    variantRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.variant-option').forEach(el => el.classList.remove('active', 'bg-light'));
            if (this.checked) {
                this.closest('.variant-option').classList.add('active', 'bg-light');
            }
            calculateTotal();
        });
    });

    calculateTotal();
});
</script>
@endpush
@endsection
