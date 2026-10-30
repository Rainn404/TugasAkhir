@extends('layouts.app')

@section('content')
@php
    $midtransIsProduction = filter_var(config('services.midtrans.is_production'), FILTER_VALIDATE_BOOLEAN);
    $midtransClientKey = (string) config('services.midtrans.client_key', '');
    $midtransClientKeyMatchesEnvironment = $midtransIsProduction
        ? str_starts_with($midtransClientKey, 'Mid-client-') && !str_starts_with($midtransClientKey, 'SB-Mid-client-')
        : str_starts_with($midtransClientKey, 'SB-Mid-client-');
    $midtransSnapScriptUrl = $midtransIsProduction
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
@endphp
<div class="container py-5">
    <!-- Header / Nav Back -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
        <div>
            <a href="{{ auth()->user()->role === 'admin' ? route('admin.orders') : route('dashboard.orders') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1 mb-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Pesanan
            </a>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <h1 class="page-headline mb-0 font-monospace">{{ $order->order_number }}</h1>
                @php
                    $statusMap = [
                        'pending_payment' => ['label' => 'MENUNGGU PEMBAYARAN', 'class' => 'badge-status--warning'],
                        'paid' => ['label' => 'DIBAYAR', 'class' => 'badge-status--success'],
                        'confirmed' => ['label' => 'DIKONFIRMASI', 'class' => 'badge-status--success'],
                        'preparing' => ['label' => 'DISIAPKAN', 'class' => 'badge-status--info'],
                        'ongoing' => ['label' => 'SEDANG DIGUNAKAN', 'class' => 'badge-status--neutral'],
                        'completed' => ['label' => 'SELESAI', 'class' => 'badge-status--success'],
                        'cancelled' => ['label' => 'DIBATALKAN', 'class' => 'badge-status--danger'],
                        'payment_conflict' => ['label' => 'PERLU REKONSILIASI', 'class' => 'badge-status--danger'],
                    ];
                    $st = $statusMap[$order->status] ?? ['label' => strtoupper($order->status), 'class' => 'badge-status--neutral'];
                @endphp
                <span class="badge-status {{ $st['class'] }}">
                    {{ $st['label'] }}
                </span>
            </div>
        </div>

        @if($order->status === 'pending_payment' && in_array($currentPaymentAttempt?->status, ['pending', 'cancel_unconfirmed'], true) && $currentPaymentAttempt->snap_token && $midtransClientKeyMatchesEnvironment)
            <button class="btn-hero-primary" data-pay-with-snap data-snap-token="{{ $currentPaymentAttempt->snap_token }}" data-payment-status-url="{{ route('payments.status', $order->order_number) }}">
                <i class="bi bi-credit-card-fill me-2"></i> {{ $currentPaymentAttempt->status === 'cancel_unconfirmed' ? 'Lanjutkan Pembayaran yang Sama' : 'Bayar Sekarang (Midtrans)' }}
            </button>
        @endif
    </div>

    <div class="row g-4 g-lg-5">
        <!-- Kolom Kiri: Rincian Acara & Lokasi -->
        <div class="col-lg-7">
            <!-- Rincian Acara -->
            <div class="editorial-card p-4 mb-4">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                    <i class="bi bi-calendar-check text-secondary"></i> Informasi Acara
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
            <div class="editorial-card p-4 mb-4">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                    <i class="bi bi-geo-alt text-secondary"></i> Titik Lokasi Peta
                </h5>
                <div id="orderMap" class="rounded-3 border mb-2 overflow-hidden" style="height: 240px; border-color: #DDD8D0 !important;"
                    data-latitude="{{ (float) ($order->event->latitude ?? -3.7955) }}"
                    data-longitude="{{ (float) ($order->event->longitude ?? 114.7712) }}"
                    data-event-name="{{ $order->event->event_name }}"
                    data-address="{{ $order->event->address }}"></div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-secondary font-monospace" style="font-size: 0.75rem;">
                        Koordinat: {{ $order->event->latitude }}, {{ $order->event->longitude }}
                    </small>
                    <a href="https://www.google.com/maps/dir/?api=1&origin={{ config('services.google_maps.base_location.latitude') }},{{ config('services.google_maps.base_location.longitude') }}&destination={{ $order->event->latitude }},{{ $order->event->longitude }}"
                       target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-sm rounded-pill px-3" style="font-size: 0.75rem;">
                        <i class="bi bi-geo-alt me-1"></i> Rute Google Maps
                    </a>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Item & Pembayaran -->
        <div class="col-lg-5">
            <!-- Rincian Perlengkapan -->
            <div class="editorial-card p-4 mb-4">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                    <i class="bi bi-box-seam text-secondary"></i> Perlengkapan Dipesan
                </h5>
                <div class="list-group list-group-flush mb-3">
                    @foreach($order->orderDetails as $detail)
                        <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-start border-bottom last-border-0" style="border-color: #F0ECE6 !important; background: transparent;">
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

                <div class="d-flex justify-content-between fw-bold text-black border-top pt-3 fs-5" style="border-color: #E7E2DA !important;">
                    <span>Total Pembayaran</span>
                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Status Pembayaran -->
            <div class="editorial-card p-4">
                <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                    <i class="bi bi-credit-card text-secondary"></i> Rincian Pembayaran
                </h5>
                @if(!$midtransIsProduction && $order->status === 'pending_payment')
                    <div class="alert alert-info small rounded-3" role="status">
                        <strong>Mode Sandbox:</strong> transaksi ini hanya untuk simulasi, bukan pembayaran sungguhan. Jangan bayar atau scan QR dengan aplikasi uang sungguhan. Gunakan
                        <a href="https://simulator.sandbox.midtrans.com/v2/qris/index" target="_blank" rel="noopener noreferrer" class="fw-semibold">Simulator QRIS Midtrans</a>
                        atau
                        <a href="https://simulator.sandbox.midtrans.com/openapi/va/index?bank=bri" target="_blank" rel="noopener noreferrer" class="fw-semibold">Simulator BRI VA Midtrans</a>.
                    </div>
                @endif
                <div id="payment-snap-status" class="alert d-none py-2 small rounded-3" role="status" aria-live="polite"></div>
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

                @if(isset($paymentAttempts) && $paymentAttempts->isNotEmpty())
                    <div class="border-top pt-3 mt-3" style="border-color: #E7E2DA !important;">
                        <h6 class="fw-semibold text-black small mb-2">Riwayat Attempt Midtrans</h6>
                        @foreach($paymentAttempts as $attempt)
                            <div class="py-2 {{ !$loop->last ? 'border-bottom' : '' }} small" style="border-color: #F0ECE6 !important;">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1">
                                    <span class="font-monospace text-secondary text-break">{{ $attempt->midtrans_order_id }}</span>
                                    <span class="badge-status {{ $attempt->status === 'cancel_unconfirmed' ? 'badge-status--warning' : ($attempt->status === 'expired' ? 'badge-status--neutral' : 'badge-status--neutral') }} align-self-start align-self-sm-center" style="font-size: 0.7rem;">
                                        {{ strtoupper(str_replace('_', ' ', $attempt->status)) }}
                                    </span>
                                </div>
                                @if($attempt->transaction_id)
                                    <div class="font-monospace text-secondary mt-1">Transaction ID: {{ $attempt->transaction_id }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @php
                    $providerStatus = data_get($currentPaymentAttempt?->payload_json, 'status_check');
                    $vaNumbers = data_get($providerStatus, 'va_numbers', []);
                @endphp
                @if(is_array($vaNumbers) && count($vaNumbers))
                    <div class="border-top pt-3 mt-2 small" style="border-color: #E7E2DA !important;">
                        <h6 class="fw-semibold text-black mb-2">Virtual Account dari Midtrans</h6>
                        @foreach($vaNumbers as $va)
                            <div class="d-flex justify-content-between gap-2">
                                <span class="text-secondary">{{ strtoupper($va['bank'] ?? 'BANK') }} VA</span>
                                <span class="font-monospace text-black">{{ $va['va_number'] ?? '-' }}</span>
                            </div>
                        @endforeach
                        @if(data_get($providerStatus, 'gross_amount'))
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-secondary">Nominal VA</span>
                                <span class="text-black">Rp {{ number_format((float) data_get($providerStatus, 'gross_amount'), 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if(data_get($providerStatus, 'expiry_time'))
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-secondary">Batas waktu</span>
                                <span class="text-black">{{ data_get($providerStatus, 'expiry_time') }} WIB</span>
                            </div>
                        @endif
                        @if(data_get($providerStatus, 'transaction_status') === 'expire')
                            <div class="alert alert-secondary py-2 mt-2 mb-0 rounded-3">
                                VA ini sudah kedaluwarsa. Siapkan token pembayaran baru sebelum membayar.
                            </div>
                        @endif
                    </div>
                @endif

                @if($order->status === 'pending_payment' && $currentPaymentAttempt?->status === 'pending')
                    @if($currentPaymentAttempt->snap_token && $midtransClientKeyMatchesEnvironment)
                        <button class="btn-hero-primary w-100 py-2.5 justify-content-center text-center mt-3" data-pay-with-snap data-snap-token="{{ $currentPaymentAttempt->snap_token }}" data-payment-status-url="{{ route('payments.status', $order->order_number) }}">
                            <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
                        </button>
                    @elseif(!$midtransClientKeyMatchesEnvironment && $currentPaymentAttempt->snap_token)
                        <div class="alert alert-danger small mt-2 mb-0 rounded-3" role="alert">
                            Client Key Midtrans tidak cocok dengan mode {{ $midtransIsProduction ? 'Production' : 'Sandbox' }}. Periksa PAYMENT_GATEWAY_CLIENT_KEY di .env.
                        </div>
                    @endif
                    <form action="{{ route('payments.cancel', $order->order_number) }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill py-2" data-confirm-cancel-payment>
                            <i class="bi bi-arrow-repeat me-1"></i> Batalkan &amp; Ganti Metode
                        </button>
                    </form>
                    <small class="text-secondary d-block mt-2 text-center">Pembayaran yang dibatalkan tidak dapat dilanjutkan. Siapkan pembayaran baru untuk memilih metode lain.</small>
                @elseif($order->status === 'pending_payment' && $currentPaymentAttempt?->status === 'cancel_unconfirmed')
                    <div class="alert alert-warning small mt-2 rounded-3" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Status transaksi belum dapat dipastikan oleh Midtrans. Pesanan belum dinyatakan lunas atau batal, dan pembayaran baru diblokir agar tidak terjadi tagihan ganda.
                    </div>
                    @if($currentPaymentAttempt->snap_token && $midtransClientKeyMatchesEnvironment)
                        <button class="btn-hero-primary w-100 py-2.5 justify-content-center text-center mt-2" data-pay-with-snap data-snap-token="{{ $currentPaymentAttempt->snap_token }}" data-payment-status-url="{{ route('payments.status', $order->order_number) }}">
                            <i class="bi bi-credit-card me-1"></i> Lanjutkan Pembayaran yang Sama
                        </button>
                    @elseif($currentPaymentAttempt->snap_token)
                        <div class="alert alert-danger small mt-2 mb-0 rounded-3" role="alert">
                            Client Key Midtrans tidak cocok dengan mode {{ $midtransIsProduction ? 'Production' : 'Sandbox' }}. Periksa PAYMENT_GATEWAY_CLIENT_KEY di .env.
                        </div>
                    @endif
                    <button type="button" class="btn {{ $currentPaymentAttempt->snap_token && $midtransClientKeyMatchesEnvironment ? 'btn-outline-secondary' : 'btn-hero-primary' }} w-100 rounded-pill py-2 mt-2" data-check-payment-status data-payment-status-url="{{ route('payments.status', $order->order_number) }}">
                        <i class="bi bi-arrow-repeat me-1"></i> Periksa Status Pembayaran
                    </button>
                    <small class="text-secondary d-block mt-2 text-center">Pembayaran baru tersedia setelah Midtrans mengonfirmasi transaksi lama berstatus terminal.</small>
                @elseif($order->status === 'pending_payment' && $currentPaymentAttempt && in_array($currentPaymentAttempt->status, ['creating', 'unknown', 'cancel_requested'], true))
                    <div class="alert alert-warning small mt-2 rounded-3" role="alert">
                        Status transaksi belum dapat dipastikan. Pembayaran baru diblokir sampai Midtrans mengonfirmasi statusnya.
                    </div>
                    <button type="button" class="btn-hero-primary w-100 py-2.5 justify-content-center text-center mt-2" data-check-payment-status data-payment-status-url="{{ route('payments.status', $order->order_number) }}">
                        <i class="bi bi-arrow-repeat me-1"></i> Periksa Status Pembayaran
                    </button>
                @elseif($order->status === 'pending_payment' && (!$currentPaymentAttempt || in_array($currentPaymentAttempt->status, ['failed', 'expired', 'cancelled', 'rejected'], true)))
                    <form action="{{ route('payments.retry', $order->order_number) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="btn-hero-primary w-100 py-2.5 justify-content-center text-center">
                            <i class="bi bi-arrow-repeat me-1"></i> Siapkan Pembayaran
                        </button>
                    </form>
                    <small class="text-secondary d-block mt-2 text-center">Token pembayaran belum tersedia. Klik untuk mencoba menghubungkan pesanan ke Midtrans.</small>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($midtransClientKeyMatchesEnvironment)
<script id="midtrans-snap-script"
    src="{{ $midtransSnapScriptUrl }}"
    data-client-key="{{ $midtransClientKey }}"
    @if(!empty($cspNonce)) nonce="{{ $cspNonce }}" @endif>
</script>
@endif
<script src="{{ asset('js/order-payment.js') }}" @if(!empty($cspNonce)) nonce="{{ $cspNonce }}" @endif defer></script>
@if(config('services.google_maps.client_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.client_key') }}&libraries=geometry,marker&callback=initOrderMap&loading=async"
    @if(!empty($cspNonce)) nonce="{{ $cspNonce }}" @endif async defer></script>
@endif
@endpush
@endsection
