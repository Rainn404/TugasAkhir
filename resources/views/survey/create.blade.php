@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="editorial-card p-4 p-md-5">
                <span class="page-eyebrow">Layanan Survei Lapangan</span>
                <h1 class="page-headline mb-2 font-display">Formulir Pengajuan Survei Lokasi</h1>
                <p class="page-subtext mb-4 pb-3 border-bottom" style="border-color: #E7E2DA !important;">
                    Isi detail rencana acara dan titik lokasi Anda. Tim teknis Arsy Tenda akan menghubungi Anda untuk mengonfirmasi jadwal kunjungan survei lapangan secara akurat.
                </p>

                <form action="{{ route('survey.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label for="surveyEventName" class="form-label small fw-semibold text-black">Nama Rencana Acara <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" id="surveyEventName" class="form-control rounded-3" placeholder="Contoh: Pernikahan Dimas &amp; Rini" value="{{ old('event_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="surveyPhone" class="form-label small fw-semibold text-black">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="surveyPhone" autocomplete="tel" class="form-control rounded-3" placeholder="08xxxxxxxxxx" value="{{ old('phone', auth()->user()->phone ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="surveyEventDate" class="form-label small fw-semibold text-black">Rencana Tanggal Acara <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" id="surveyEventDate" min="{{ date('Y-m-d') }}" class="form-control rounded-3" value="{{ old('event_date', date('Y-m-d', strtotime('+3 days'))) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="surveyEstimatedTime" class="form-label small fw-semibold text-black">Perkiraan Jam Acara <span class="text-danger">*</span></label>
                            <input type="text" name="estimated_time" id="surveyEstimatedTime" class="form-control rounded-3" placeholder="Contoh: 09:00 - 17:00" value="{{ old('estimated_time', '09:00 - 17:00') }}" required>
                        </div>
                    </div>

                    <!-- Alamat & Google Maps Picker -->
                    <div class="mb-4">
                        <label for="addressInput" class="form-label small fw-semibold text-black">Alamat Lokasi yang Akan Disurvei <span class="text-danger">*</span></label>
                        <textarea name="address" id="addressInput" rows="2" class="form-control rounded-3 mb-3" placeholder="Masukkan alamat lengkap lokasi (nama jalan, nomor rumah/gedung, patokan lokasi)..." required>{{ old('address') }}</textarea>

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
                                <i class="bi bi-pin-map text-danger me-1"></i> Geser pin atau klik peta untuk menentukan titik presisi:
                            </small>
                            <span id="coordsDisplay" class="font-monospace text-black badge-status badge-status--neutral" style="font-size: 0.75rem;">
                                Lat: -3.7955, Lng: 114.7712
                            </span>
                        </div>

                        <!-- Google Map Container -->
                        <div id="surveyMap" class="rounded-3 border mb-3 overflow-hidden" style="height: 320px; border-color: #DDD8D0 !important;"
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
                                        <span class="small text-secondary d-block" style="font-size: 0.75rem;">Rute Logistik dari {{ config('services.google_maps.base_location.name') }}</span>
                                        <span class="fw-bold text-black fs-6" id="routeDistance">-</span>
                                        <span class="text-secondary small ms-1" id="routeDuration">(-)</span>
                                    </div>
                                </div>
                                <span class="badge bg-white text-black border rounded-pill px-3 py-1.5 small" style="border-color: #DDD8D0 !important;">
                                    Google Routes API
                                </span>
                            </div>
                            <small class="text-secondary d-block pt-1 border-top" id="routeShippingNote" style="border-color: #E7E2DA !important; font-size: 0.8125rem;">
                                Area Pelaihari dan sekitarnya: Konfirmasi akses armada dan jadwal keberangkatan akan difinalkan saat survei teknis lokasi.
                            </small>
                        </div>

                        <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude', '-3.7955') }}">
                        <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude', '114.7712') }}">
                    </div>

                    <div class="mb-4">
                        <label for="surveyNotes" class="form-label small fw-semibold text-black">Catatan Tambahan untuk Tim Lapangan</label>
                        <textarea name="notes" id="surveyNotes" rows="2" class="form-control rounded-3" placeholder="Misal: Lahan berupa tanah berumput, akses masuk lewat gang samping...">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="btn-hero-primary w-100 py-3 justify-content-center text-center">
                        <i class="bi bi-send-check me-2"></i> Kirim Pengajuan Survei Lokasi
                    </button>
                </form>
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
