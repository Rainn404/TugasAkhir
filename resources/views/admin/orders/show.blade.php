@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.orders') }}" class="btn btn-outline-dark btn-sm rounded-2 mb-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Pesanan
        </a>
        <h4 class="fw-bold text-black mb-0">Kelola Pesanan #{{ $order->order_number }}</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-dark px-3 py-2 rounded-pill small">
            Status: {{ strtoupper(str_replace('_', ' ', $order->status)) }}
        </span>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Detail Acara & Peta & Form Status -->
    <div class="col-lg-7">
        <!-- Form Pembaruan Status Pesanan -->
        <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
            <h6 class="fw-bold text-black mb-3 pb-2 border-bottom">
                <i class="bi bi-arrow-clockwise me-2"></i> Perbarui Status Tahapan Pesanan
            </h6>
            <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="d-flex gap-2">
                @csrf
                <select name="status" class="form-select form-select-sm rounded-2 fw-semibold">
                    <option value="pending_payment" {{ $order->status === 'pending_payment' ? 'selected' : '' }}>Menunggu Pembayaran</option>
                    <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>Dibayar (Lunas)</option>
                    <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Dikonfirmasi (Siap Jadwal)</option>
                    <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>Disiapkan (Muat Truk & Packing)</option>
                    <option value="ongoing" {{ $order->status === 'ongoing' ? 'selected' : '' }}>Sedang Digunakan (Terpasang di Lokasi)</option>
                    <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Selesai (Bongkar & Kembali ke Gudang)</option>
                    <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
                <button type="submit" class="btn btn-dark btn-sm rounded-2 px-3 fw-bold text-nowrap">
                    Update Status
                </button>
            </form>
        </div>

        <!-- Detail Informasi Acara -->
        <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
            <h6 class="fw-bold text-black mb-3 pb-2 border-bottom">
                <i class="bi bi-calendar-event me-2"></i> Informasi Kegiatan Acara
            </h6>
            <div class="row g-3 small">
                <div class="col-sm-6">
                    <span class="text-secondary d-block">Nama Acara:</span>
                    <strong class="text-black fs-6">{{ $order->event->event_name }}</strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-secondary d-block">Jenis Acara:</span>
                    <strong class="text-black">{{ $order->event->event_type }}</strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-secondary d-block">Tanggal Pelaksanaan:</span>
                    <strong class="text-black">{{ \Carbon\Carbon::parse($order->event->event_date)->translatedFormat('l, d F Y') }}</strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-secondary d-block">Waktu:</span>
                    <strong class="text-black">{{ $order->event->start_time }} - {{ $order->event->end_time }} WITA</strong>
                </div>
                <div class="col-12">
                    <span class="text-secondary d-block">Alamat Lokasi:</span>
                    <strong class="text-black">{{ $order->event->address }}</strong>
                </div>
                @if($order->notes)
                    <div class="col-12">
                        <span class="text-secondary d-block">Catatan Khusus:</span>
                        <span class="text-secondary">{{ $order->notes }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Peta Titik Lokasi Pengiriman -->
        <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
            <h6 class="fw-bold text-black mb-3 pb-2 border-bottom">
                <i class="bi bi-geo-alt me-2"></i> Titik Lokasi Peta Pengiriman
            </h6>
            <div id="adminOrderMap" style="height: 220px;" class="rounded-3 border mb-2"></div>
            <small class="text-secondary font-monospace">Koordinat: {{ $order->event->latitude }}, {{ $order->event->longitude }}</small>
        </div>
    </div>

    <!-- Kolom Kanan: Rincian Pelanggan, Inventaris, Pembayaran -->
    <div class="col-lg-5">
        <!-- Informasi Pelanggan -->
        <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
            <h6 class="fw-bold text-black mb-3 pb-2 border-bottom">
                <i class="bi bi-person me-2"></i> Data Pelanggan
            </h6>
            <div class="small">
                <div class="mb-1"><span class="text-secondary">Nama:</span> <strong class="text-black">{{ $order->user->name ?? '-' }}</strong></div>
                <div class="mb-1"><span class="text-secondary">Email:</span> <strong class="text-black">{{ $order->user->email ?? '-' }}</strong></div>
                <div class="mb-1"><span class="text-secondary">Telepon:</span> <strong class="text-black">{{ $order->user->phone ?? '-' }}</strong></div>
            </div>
        </div>

        <!-- Rincian Perlengkapan Dipesan (Manifest) -->
        <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
            <h6 class="fw-bold text-black mb-3 pb-2 border-bottom">
                <i class="bi bi-box-seam me-2"></i> Manifest Perlengkapan
            </h6>
            <div class="list-group list-group-flush mb-3">
                @foreach($order->orderDetails as $detail)
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start small">
                        <div>
                            <strong class="text-black d-block">{{ $detail->inventory->name ?? 'Perlengkapan' }}</strong>
                            @if($detail->variant)
                                <span class="text-secondary d-block">{{ $detail->variant->variant_name }}</span>
                            @endif
                            <span class="text-secondary">{{ $detail->quantity }} {{ $detail->inventory->unit ?? 'unit' }} &times; Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</span>
                        </div>
                        <span class="fw-bold text-black">
                            Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-between fw-bold text-black border-top pt-2 fs-6">
                <span>Total Tagihan:</span>
                <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Status Pembayaran -->
        <div class="card p-4 border rounded-4 bg-white shadow-sm">
            <h6 class="fw-bold text-black mb-3 pb-2 border-bottom">
                <i class="bi bi-credit-card me-2"></i> Status Transaksi Pembayaran
            </h6>
            <div class="small text-secondary">
                <div class="d-flex justify-content-between mb-1">
                    <span>Status Payment:</span>
                    <strong class="text-black">{{ strtoupper($order->payment_status ?? 'PENDING') }}</strong>
                </div>
                @if($order->payment)
                    <div class="d-flex justify-content-between mb-1">
                        <span>Metode:</span>
                        <span class="text-black">{{ strtoupper($order->payment->payment_type ?? 'Midtrans Gateway') }}</span>
                    </div>
                    @if($order->payment->transaction_id)
                        <div class="d-flex justify-content-between mb-1">
                            <span>Trans. ID:</span>
                            <span class="font-monospace text-black">{{ $order->payment->transaction_id }}</span>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lat = {{ (float) ($order->event->latitude ?? -3.7955) }};
    const lng = {{ (float) ($order->event->longitude ?? 114.7712) }};

    const map = L.map('adminOrderMap').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker([lat, lng]).addTo(map).bindPopup('{{ addslashes($order->event->event_name) }}').openPopup();
});
</script>
@endpush
@endsection
