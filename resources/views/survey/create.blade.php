@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card p-4 p-md-5 border rounded-4 bg-white shadow-sm">
                <span class="badge bg-black text-white px-3 py-1.5 rounded-pill small mb-2 d-inline-block" style="width: fit-content;">
                    LAYANAN SURVEI LAPANGAN
                </span>
                <h2 class="fw-bold text-black mb-2">Formulir Pengajuan Survei Lokasi</h2>
                <p class="text-secondary small mb-4 pb-3 border-bottom">
                    Isi detail rencana acara dan titik lokasi Anda. Tim teknis Arsy Tenda akan menghubungi Anda untuk mengonfirmasi jadwal kunjungan survei lapangan.
                </p>

                <form action="{{ route('survey.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-black">Nama Rencana Acara <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" class="form-control rounded-3" placeholder="Contoh: Pernikahan Dimas & Rini" value="{{ old('event_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-black">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control rounded-3" placeholder="08xxxxxxxxxx" value="{{ old('phone', auth()->user()->phone ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Rencana Tanggal Acara <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" min="{{ date('Y-m-d') }}" class="form-control rounded-3" value="{{ old('event_date', date('Y-m-d', strtotime('+3 days'))) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-black">Perkiraan Jam Acara <span class="text-danger">*</span></label>
                            <input type="text" name="estimated_time" class="form-control rounded-3" placeholder="Contoh: 09:00 - 17:00" value="{{ old('estimated_time', '09:00 - 17:00') }}" required>
                        </div>
                    </div>

                    <!-- Alamat & Map Picker -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-black">Alamat Lokasi yang Akan Disurvei <span class="text-danger">*</span></label>
                        <textarea name="address" rows="2" class="form-control rounded-3 mb-3" placeholder="Masukkan alamat lengkap lokasi (nama jalan, nomor rumah/gedung, kelurahan/desa)..." required>{{ old('address') }}</textarea>

                        <label class="form-label small fw-semibold text-secondary d-flex justify-content-between">
                            <span>Tandai titik lokasi pada peta:</span>
                            <span id="coordsDisplay" class="font-monospace text-black">Lat: -3.795, Lng: 114.77</span>
                        </label>
                        <div id="surveyMap" style="height: 260px;" class="rounded-3 border mb-3"></div>

                        <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude', '-3.7955') }}">
                        <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude', '114.7712') }}">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-black">Catatan Tambahan untuk Tim Lapangan</label>
                        <textarea name="notes" rows="2" class="form-control rounded-3" placeholder="Misal: Lahan berupa tanah berumput, akses masuk lewat gang samping...">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-dark btn-lg w-100 py-3 rounded-3 fw-bold shadow-sm">
                        <i class="bi bi-send-check me-2"></i> Kirim Pengajuan Survei Lokasi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const defaultLat = parseFloat(document.getElementById('latInput').value) || -3.7955;
    const defaultLng = parseFloat(document.getElementById('lngInput').value) || 114.7712;

    const map = L.map('surveyMap').setView([defaultLat, defaultLng], 14);

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
