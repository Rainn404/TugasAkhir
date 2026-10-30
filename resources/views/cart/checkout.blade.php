@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="page-editorial-header mb-4">
        <span class="page-eyebrow">Langkah Konfirmasi</span>
        <h1 class="page-headline mb-1">Rincian Acara &amp; Pengiriman</h1>
        <p class="page-subtext">Lengkapi detail kegiatan dan tentukan titik lokasi pemasangan perlengkapan acara.</p>
    </div>

    <!-- 6-Step Event Rental Stepper Indicator -->
    <div class="order-stepper mb-5">
        <div class="stepper-step completed">
            <div class="stepper-circle"><i class="bi bi-check-lg"></i></div>
            <div class="stepper-label">01 Acara</div>
        </div>
        <div class="stepper-step completed">
            <div class="stepper-circle"><i class="bi bi-check-lg"></i></div>
            <div class="stepper-label">02 Perlengkapan</div>
        </div>
        <div class="stepper-step completed">
            <div class="stepper-circle"><i class="bi bi-check-lg"></i></div>
            <div class="stepper-label">03 Konfigurasi</div>
        </div>
        <div class="stepper-step active">
            <div class="stepper-circle">04</div>
            <div class="stepper-label">04 Lokasi</div>
        </div>
        <div class="stepper-step active">
            <div class="stepper-circle">05</div>
            <div class="stepper-label">05 Checkout</div>
        </div>
        <div class="stepper-step">
            <div class="stepper-circle">06</div>
            <div class="stepper-label">06 Pembayaran</div>
        </div>
    </div>

    <div class="row g-4 g-lg-5">
        <!-- Kolom Kiri: Form Data Acara & Lokasi -->
        <div class="col-lg-7">
            <form action="{{ route('checkout.store') }}" method="POST" id="checkoutForm">
                @csrf

                <!-- 1. Informasi Customer -->
                <div class="editorial-card p-4 mb-4">
                    <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                        <i class="bi bi-person-circle text-secondary"></i> Informasi Penyewa
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="customerName" class="form-label small fw-semibold text-secondary">Nama Lengkap</label>
                            <input type="text" id="customerName" class="form-control rounded-3" style="background: #FAF8F5;" value="{{ auth()->user()->name }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="customerEmail" class="form-label small fw-semibold text-secondary">Email</label>
                            <input type="email" id="customerEmail" class="form-control rounded-3" style="background: #FAF8F5;" value="{{ auth()->user()->email }}" readonly>
                        </div>
                        <div class="col-12">
                            <label for="customerPhone" class="form-label small fw-semibold text-secondary">Nomor WhatsApp / Telepon</label>
                            <input type="text" id="customerPhone" class="form-control rounded-3" style="background: #FAF8F5;" value="{{ auth()->user()->phone ?? '081234567890' }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- 2. Informasi Acara -->
                <div class="editorial-card p-4 mb-4">
                    <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                        <i class="bi bi-calendar-event text-secondary"></i> Informasi Kegiatan Acara
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="eventNameInput" class="form-label small fw-semibold text-black">Nama Acara <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" id="eventNameInput" class="form-control rounded-3" placeholder="Contoh: Pernikahan Ahmad &amp; Siti / Bazar UMKM" value="{{ old('event_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="eventTypeSelect" class="form-label small fw-semibold text-black">Jenis Acara <span class="text-danger">*</span></label>
                            <select name="event_type" id="eventTypeSelect" class="form-select rounded-3" required>
                                <option value="Pernikahan" {{ old('event_type') == 'Pernikahan' ? 'selected' : '' }}>Pernikahan</option>
                                <option value="Pameran / Bazar" {{ old('event_type') == 'Pameran / Bazar' ? 'selected' : '' }}>Pameran / Bazar</option>
                                <option value="Konser / Musik" {{ old('event_type') == 'Konser / Musik' ? 'selected' : '' }}>Konser / Musik</option>
                                <option value="Seminar / Formal" {{ old('event_type') == 'Seminar / Formal' ? 'selected' : '' }}>Seminar / Formal</option>
                                <option value="Lainnya" {{ old('event_type') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="startTimeInput" class="form-label small fw-semibold text-black">Jam Mulai Acara <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="startTimeInput" class="form-control rounded-3" value="{{ old('start_time', '08:00') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="endTimeInput" class="form-label small fw-semibold text-black">Jam Selesai Acara <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="endTimeInput" class="form-control rounded-3" value="{{ old('end_time', '18:00') }}" required>
                        </div>
                    </div>
                </div>

                <!-- 3. Lokasi Acara & Google Maps Picker -->
                <div class="editorial-card p-4 mb-4">
                    <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                        <i class="bi bi-geo-alt text-secondary"></i> Lokasi Pengiriman &amp; Pemasangan
                    </h5>

                    <div class="mb-3">
                        <label for="addressInput" class="form-label small fw-semibold text-black">Alamat Lengkap Lokasi Acara <span class="text-danger">*</span></label>
                        <textarea name="address" id="addressInput" rows="2" class="form-control rounded-3 mb-3" placeholder="Masukkan nama jalan, nomor bangunan, patokan lokasi..." required>{{ old('address') }}</textarea>

                        <!-- Google Maps Search & Geolocation Bar -->
                        <div class="d-flex flex-column flex-sm-row gap-2 mb-2">
                            <div class="input-group flex-grow-1">
                                <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search"></i></span>
                                <input type="text" id="mapSearchInput" class="form-control border-start-0" placeholder="Ketik nama jalan / patokan untuk mencari di peta...">
                                <button type="button" class="btn btn-outline-dark" id="btnMapSearch">
                                    <i class="bi bi-search me-1"></i> Cari
                                </button>
                            </div>
                            <button type="button" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-1 text-nowrap rounded-3" id="btnMapGps" title="Gunakan lokasi GPS saya">
                                <i class="bi bi-crosshair"></i>
                                <span class="small">Lokasi Saya</span>
                            </button>
                        </div>

                        <!-- Dynamic Alert Box -->
                        <div id="mapAlertBox" class="alert alert-warning py-2 px-3 small rounded-3 d-none mb-2" role="alert"></div>

                        <!-- Map Label & Coordinates Display -->
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-secondary fw-semibold">
                                <i class="bi bi-pin-map text-danger me-1"></i> Geser pin atau klik peta ke titik lokasi acara yang tepat:
                            </small>
                            <span id="coordsDisplay" class="font-monospace text-black badge-status badge-status--neutral" style="font-size: 0.75rem;">
                                Lat: -3.7955, Lng: 114.7712
                            </span>
                        </div>

                        <!-- Google Map Container -->
                        <div id="map" class="rounded-3 border mb-3 overflow-hidden" style="height: 280px; border-color: #DDD8D0 !important;"
                            data-google-map
                            data-base-lat="{{ config('services.google_maps.base_location.latitude') }}"
                            data-base-lng="{{ config('services.google_maps.base_location.longitude') }}"
                            data-base-name="{{ config('services.google_maps.base_location.name') }}"
                            data-input-lat="latInput"
                            data-input-lng="lngInput"
                            data-input-address="addressInput"
                            data-geocode-url="{{ route('maps.geocode') }}"
                            data-reverse-geocode-url="{{ route('maps.reverse-geocode') }}"
                            data-route-url="{{ route('maps.calculate-route') }}">
                        </div>

                        <!-- Routes & Distance Info Box -->
                        <div id="mapRouteInfoBox" class="editorial-card p-3 mb-3 d-none" style="background-color: #FAF8F5; border-color: #E7E2DA;">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-white border" style="width: 36px; height: 36px; border-color: #DDD8D0 !important;">
                                        <i class="bi bi-truck text-black fs-6"></i>
                                    </div>
                                    <div>
                                        <span class="small text-secondary d-block" style="font-size: 0.75rem;">Jarak Pengiriman dari {{ config('services.google_maps.base_location.name') }}</span>
                                        <span class="fw-bold text-black fs-6" id="routeDistance">-</span>
                                        <span class="text-secondary small ms-1" id="routeDuration">(-)</span>
                                    </div>
                                </div>
                                <span class="badge bg-white text-black border rounded-pill px-3 py-1.5 small" style="border-color: #DDD8D0 !important;">
                                    Google Routes API
                                </span>
                            </div>
                            <small class="text-secondary d-block pt-1 border-top" id="routeShippingNote" style="border-color: #E7E2DA !important; font-size: 0.8125rem;">
                                Pengiriman &amp; pemasangan standar sudah termasuk dalam pesanan acara Anda.
                            </small>
                        </div>
                    </div>

                    <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude', '-3.7955') }}">
                    <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude', '114.7712') }}">

                    <div class="mb-3">
                        <label for="notesTextarea" class="form-label small fw-semibold text-black">Catatan Tambahan (Opsional)</label>
                        <textarea name="notes" id="notesTextarea" rows="2" class="form-control rounded-3" placeholder="Catatan khusus akses jalan, kelistrikan, atau instruksi pemasangan...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-none d-lg-block">
                    <button type="submit" class="btn-hero-primary w-100 py-3 justify-content-center text-center">
                        <i class="bi bi-credit-card me-2"></i> Konfirmasi &amp; Lanjutkan Pembayaran
                    </button>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Detail Perlengkapan & Ringkasan Checkout -->
        <div class="col-lg-5">
            <div class="editorial-card p-4 sticky-top" style="top: 100px;">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display" style="border-color: #E7E2DA !important;">Rincian Perlengkapan</h5>

                <div class="mb-3 d-flex align-items-center justify-content-between">
                    <small class="text-secondary">Tanggal Pelaksanaan:</small>
                    <span class="fw-bold text-black small">{{ \Carbon\Carbon::parse($cartDate)->translatedFormat('l, d F Y') }}</span>
                </div>

                <!-- Items Breakdown List -->
                <div class="rounded-3 p-3 mb-4" style="max-height: 240px; overflow-y: auto; background: #FAF8F5; border: 1px solid #DDD8D0;">
                    @foreach($cart as $item)
                        <div class="d-flex justify-content-between align-items-start py-2 border-bottom last-border-0" style="border-color: #E7E2DA !important;">
                            <div>
                                <h6 class="fw-bold text-black mb-0 small">{{ $item['name'] }}</h6>
                                @if(!empty($item['variant_name']))
                                    <small class="text-secondary d-block">{{ $item['variant_name'] }}</small>
                                @endif
                                <small class="text-secondary">{{ $item['quantity'] }} {{ $item['unit'] }} &times; Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</small>
                            </div>
                            <span class="fw-bold text-black small">
                                Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <!-- Price Summary -->
                <div class="d-flex justify-content-between text-secondary small mb-2">
                    <span>Subtotal Perlengkapan</span>
                    <span class="text-black fw-semibold">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between text-secondary small mb-3">
                    <span>Biaya Pengiriman &amp; Pemasangan</span>
                    <span class="text-success fw-semibold">Termasuk (Gratis)</span>
                </div>

                <div class="d-flex justify-content-between fw-bold text-black border-top pt-3 fs-5 mb-4" style="border-color: #E7E2DA !important;">
                    <span>Total Pembayaran</span>
                    <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>

                <div class="p-3 rounded-3 small text-secondary mb-4" style="background: #FAF8F5; border: 1px solid #DDD8D0;">
                    <i class="bi bi-lock-fill text-black me-1"></i>
                    Pembayaran aman didukung oleh Payment Gateway Midtrans (QRIS, VA Bank, Gopay, ShopeePay).
                </div>

                <div class="d-block d-lg-none">
                    <button type="submit" form="checkoutForm" class="btn-hero-primary w-100 py-3 justify-content-center text-center">
                        <i class="bi bi-credit-card me-2"></i> Konfirmasi &amp; Lanjutkan Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/google-maps-picker.js') }}"></script>
@if(config('services.google_maps.client_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.client_key') }}&libraries=geometry,places,marker&callback=initGoogleMap&loading=async" async defer></script>
@endif
@endpush
@endsection
