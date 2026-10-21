@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header / Nav Back -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <a href="{{ auth()->user()->role === 'admin' ? route('admin.orders') : route('dashboard.orders') }}" class="btn btn-outline-dark btn-sm rounded-2 mb-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Pesanan
            </a>
            <div class="d-flex align-items-center gap-3">
                <h2 class="fw-bold text-black mb-0 font-monospace">{{ $order->order_number }}</h2>
                @php
                    $statusMap = [
                        'pending_payment' => ['label' => 'MENUNGGU PEMBAYARAN', 'class' => 'bg-warning text-dark'],
                        'paid' => ['label' => 'DIBAYAR', 'class' => 'bg-success text-white'],
                        'confirmed' => ['label' => 'DIKONFIRMASI', 'class' => 'bg-success text-white'],
                        'preparing' => ['label' => 'DISIAPKAN', 'class' => 'bg-info text-dark'],
                        'ongoing' => ['label' => 'SEDANG DIGUNAKAN', 'class' => 'bg-primary text-white'],
                        'completed' => ['label' => 'SELESAI', 'class' => 'bg-dark text-white'],
                        'cancelled' => ['label' => 'DIBATALKAN', 'class' => 'bg-danger text-white'],
                    ];
                    $st = $statusMap[$order->status] ?? ['label' => strtoupper($order->status), 'class' => 'bg-secondary text-white'];
                @endphp
                <span class="badge {{ $st['class'] }} px-3 py-2 rounded-pill small fw-semibold">
                    {{ $st['label'] }}
                </span>
            </div>
        </div>

        @if($order->status === 'pending_payment' && $order->payment && $order->payment->snap_token)
            <button id="pay-button" class="btn btn-dark btn-lg px-4 py-2.5 rounded-3 fw-bold shadow">
                <i class="bi bi-credit-card-fill me-2"></i> Bayar Sekarang (Midtrans)
            </button>
        @endif
    </div>

    <div class="row g-4">
        <!-- Kolom Kiri: Rincian Acara & Lokasi -->
        <div class="col-lg-7">
            <!-- Rincian Acara -->
            <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">
                    <i class="bi bi-calendar-check me-2"></i> Informasi Acara
                </h5>
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
                            <span class="text-secondary d-block">Catatan:</span>
                            <span class="text-secondary fst-italic">{{ $order->notes }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Peta Lokasi Acara -->
            <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">
                    <i class="bi bi-geo-alt me-2"></i> Titik Lokasi Peta
                </h5>
                <div id="orderMap" style="height: 240px;" class="rounded-3 border mb-2"></div>
                <small class="text-secondary font-monospace">
                    Koordinat: {{ $order->event->latitude }}, {{ $order->event->longitude }}
                </small>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Item & Pembayaran -->
        <div class="col-lg-5">
            <!-- Rincian Perlengkapan -->
            <div class="card p-4 border rounded-4 bg-white shadow-sm mb-4">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">
                    <i class="bi bi-box-seam me-2"></i> Perlengkapan Dipesan
                </h5>
                <div class="list-group list-group-flush mb-3">
                    @foreach($order->orderDetails as $detail)
                        <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold text-black mb-0 small">{{ $detail->inventory->name ?? 'Perlengkapan' }}</h6>
                                @if($detail->variant)
                                    <small class="text-secondary d-block">{{ $detail->variant->variant_name }}</small>
                                @endif
                                <small class="text-secondary">{{ $detail->quantity }} {{ $detail->inventory->unit ?? 'unit' }} &times; Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</small>
                            </div>
                            <span class="fw-bold text-black small">
                                Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between fw-bold text-black border-top pt-3 fs-5">
                    <span>Total Pembayaran</span>
                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Status Pembayaran -->
            <div class="card p-4 border rounded-4 bg-white shadow-sm">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom">
                    <i class="bi bi-credit-card me-2"></i> Rincian Pembayaran
                </h5>
                <div class="small text-secondary mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Status Pembayaran:</span>
                        <strong class="text-black">{{ strtoupper($order->payment_status ?? 'PENDING') }}</strong>
                    </div>
                    @if($order->payment)
                        <div class="d-flex justify-content-between mb-1">
                            <span>Metode:</span>
                            <span class="text-black">{{ strtoupper($order->payment->payment_type ?? 'Midtrans Gateway') }}</span>
                        </div>
                        @if($order->payment->paid_at)
                            <div class="d-flex justify-content-between mb-1">
                                <span>Waktu Bayar:</span>
                                <span class="text-black">{{ \Carbon\Carbon::parse($order->payment->paid_at)->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                    @endif
                </div>

                @if($order->status === 'pending_payment' && $order->payment && $order->payment->snap_token)
                    <button class="btn btn-dark w-100 py-2.5 rounded-3 fw-bold mt-2" onclick="payWithSnap()">
                        <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
                    </button>
                @elseif($order->status === 'pending_payment')
                    <form action="{{ route('payments.retry', $order->order_number) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-dark w-100 py-2.5 rounded-3 fw-bold">
                            <i class="bi bi-arrow-repeat me-1"></i> Siapkan Pembayaran
                        </button>
                    </form>
                    <small class="text-secondary d-block mt-2">Token pembayaran belum tersedia. Klik untuk mencoba menghubungkan pesanan ke Midtrans.</small>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const lat = {{ (float) ($order->event->latitude ?? -3.7955) }};
    const lng = {{ (float) ($order->event->longitude ?? 114.7712) }};

    const map = L.map('orderMap').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker([lat, lng]).addTo(map).bindPopup('{{ addslashes($order->event->event_name) }}').openPopup();
});

function payWithSnap() {
    const snapToken = "{{ $order->payment->snap_token ?? '' }}";
    if (!snapToken) {
        alert('Token pembayaran belum tersedia. Hubungi admin.');
        return;
    }

    snap.pay(snapToken, {
        onSuccess: function (result) {
            window.location.reload();
        },
        onPending: function (result) {
            window.location.reload();
        },
        onError: function (result) {
            alert('Pembayaran gagal atau dibatalkan.');
            window.location.reload();
        },
        onClose: function () {
            // Optional callback
        }
    });
}

const payBtn = document.getElementById('pay-button');
if (payBtn) {
    payBtn.addEventListener('click', payWithSnap);
}
</script>
@endpush
@endsection
