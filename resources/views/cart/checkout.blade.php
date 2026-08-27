@extends('layouts.app')

@section('content')
<div class="container py-4">

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

    <div class="row g-5">
        <!-- Kolom Kiri: Form Data Acara & Lokasi -->
        <div class="col-lg-7">
            <form action="{{ route('checkout.store') }}" method="POST" id="checkoutForm">
                @csrf

                <!-- 1. Informasi Customer -->
                <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
                    <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">
                        <i class="bi bi-person-circle me-2"></i> Informasi Penyewa
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Nama Lengkap</label>
                            <input type="text" class="form-control rounded-3 bg-light" value="{{ auth()->user()->name }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email</label>
                            <input type="email" class="form-control rounded-3 bg-light" value="{{ auth()->user()->email }}" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Nomor WhatsApp / Telepon</label>
                            <input type="text" class="form-control rounded-3 bg-light" value="{{ auth()->user()->phone ?? '081234567890' }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- 2. Informasi Acara -->
                <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
                    <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">
                        <i class="bi bi-calendar-event me-2"></i> Informasi Kegiatan Acara
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-black">Nama Acara <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" class="form-control rounded-3" placeholder="Contoh: Pernikahan Ahmad & Siti / Bazar UMKM" value="{{ old('event_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-black">Jenis Acara <span class="text-danger">*</span></label>
                            <select name="event_type" class="form-select rounded-3" required>
                                <option value="Pernikahan" {{ old('event_type') == 'Pernikahan' ? 'selected' : '' }}>Pernikahan</option>
                                <option value="Pameran / Bazar" {{ old('event_type') == 'Pameran / Bazar' ? 'selected' : '' }}>Pameran / Bazar</option>
                                <option value="Konser / Musik" {{ old('event_type') == 'Konser / Musik' ? 'selected' : '' }}>Konser / Musik</option>
                                <option value="Seminar / Formal" {{ old('event_type') == 'Seminar / Formal' ? 'selected' : '' }}>Seminar / Formal</option>
                                <option value="Lainnya" {{ old('event_type') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Jam Mulai Acara <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control rounded-3" value="{{ old('start_time', '08:00') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Jam Selesai Acara <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control rounded-3" value="{{ old('end_time', '18:00') }}" required>
                        </div>
                    </div>
                </div>

                <!-- 3. Lokasi Acara & Google Maps Picker -->
                <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
                    <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">
                        <i class="bi bi-geo-alt me-2"></i> Lokasi Pengiriman & Pemasangan
                    </h5>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-black">Alamat Lengkap Lokasi Acara <span class="text-danger">*</span></label>
                        <textarea name="address" id="addressInput" rows="2" class="form-control rounded-3" placeholder="Masukkan nama jalan, nomor bangunan, patokan lokasi..." required>{{ old('address') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary d-flex justify-content-between">
                            <span>Geser pin peta ke titik lokasi acara yang tepat:</span>
                            <span id="coordsDisplay" class="font-monospace text-black">Lat: -3.795, Lng: 114.77</span>
                        </label>
                        <div id="map" style="height: 280px;" class="rounded-3 border"></div>
                    </div>

                    <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude', '-3.7955') }}">
                    <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude', '114.7712') }}">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-black">Catatan Tambahan (Opsional)</label>
                        <textarea name="notes" rows="2" class="form-control rounded-3" placeholder="Catatan khusus akses jalan, kelistrikan, atau instruksi pemasangan...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-none d-lg-block">
                    <button type="submit" class="btn btn-dark btn-lg w-100 py-3 rounded-3 fw-bold shadow">
                        <i class="bi bi-credit-card me-2"></i> Konfirmasi & Lanjutkan Pembayaran
                    </button>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Detail Perlengkapan & Ringkasan Checkout -->
        <div class="col-lg-5">
            <div class="card p-4 border rounded-4 bg-white shadow-sm sticky-top" style="top: 90px;">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">Rincian Perlengkapan</h5>

                <div class="mb-3">
                    <small class="text-secondary d-block">Tanggal Pelaksanaan:</small>
                    <span class="fw-bold text-black">{{ \Carbon\Carbon::parse($cartDate)->translatedFormat('l, d F Y') }}</span>
                </div>

                <!-- Items Breakdown List -->
                <div class="border rounded-3 p-3 bg-light mb-4" style="max-height: 240px; overflow-y: auto;">
                    @foreach($cart as $item)
                        <div class="d-flex justify-content-between align-items-start py-2 border-bottom last-border-0">
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
                    <span>Biaya Pengiriman & Pemasangan</span>
                    <span class="text-success fw-semibold">Termasuk (Gratis)</span>
                </div>

                <div class="d-flex justify-content-between fw-bold text-black border-top pt-3 fs-5 mb-4">
                    <span>Total Pembayaran</span>
                    <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>

                <div class="p-3 bg-light border rounded-3 small text-secondary mb-4">
                    <i class="bi bi-lock-fill text-black me-1"></i>
                    Pembayaran aman didukung oleh Payment Gateway Midtrans (QRIS, VA Bank, Gopay, ShopeePay).
                </div>

                <div class="d-block d-lg-none">
                    <button type="button" class="btn btn-dark btn-lg w-100 py-3 rounded-3 fw-bold shadow" onclick="document.getElementById('checkoutForm').submit()">
                        <i class="bi bi-credit-card me-2"></i> Konfirmasi & Lanjutkan Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const defaultLat = parseFloat(document.getElementById('latInput').value) || -3.7955;
    const defaultLng = parseFloat(document.getElementById('lngInput').value) || 114.7712;

    const map = L.map('map').setView([defaultLat, defaultLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

    function updateCoordinates(lat, lng) {
        document.getElementById('latInput').value = lat.toFixed(6);
        document.getElementById('lngInput').value = lng.toFixed(6);
        document.getElementById('coordsDisplay').textContent = `Lat: ${lat.toFixed(4)}, Lng: ${lng.toFixed(4)}`;
    }

    marker.on('dragend', function (e) {
        const pos = marker.getLatLng();
        updateCoordinates(pos.lat, pos.lng);
    });

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        updateCoordinates(e.latlng.lat, e.latlng.lng);
    });
});
</script>
@endpush
@endsection
