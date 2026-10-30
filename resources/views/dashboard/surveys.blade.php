@extends('layouts.app')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
        <div>
            <span class="page-eyebrow">Survei Lapangan</span>
            <h1 class="page-headline mb-1">Riwayat Pengajuan Survei Lokasi</h1>
            <p class="page-subtext">Pantau proses verifikasi dan jadwal peninjauan lokasi acara oleh tim teknis kami.</p>
        </div>
        <div>
            <a href="{{ route('survey.create') }}" class="btn-hero-primary">
                <i class="bi bi-plus-lg me-1"></i> Ajukan Survei Baru
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="dashboard-nav-pills" aria-label="Menu Dasbor Pelanggan">
        <a class="dashboard-pill" href="{{ route('dashboard.index') }}">
            <i class="bi bi-grid"></i> <span>Ringkasan</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.orders') }}">
            <i class="bi bi-receipt"></i> <span>Pesanan Saya</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.events') }}">
            <i class="bi bi-calendar-event"></i> <span>Kegiatan Acara</span>
        </a>
        <a class="dashboard-pill active" href="{{ route('dashboard.surveys') }}">
            <i class="bi bi-geo-alt"></i> <span>Survei Lokasi</span>
        </a>
        <a class="dashboard-pill" href="{{ route('dashboard.profile') }}">
            <i class="bi bi-person"></i> <span>Profil Akun</span>
        </a>
    </nav>

    <!-- Surveys Table -->
    <div class="editorial-card overflow-hidden">
        @if($surveys->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-geo-alt text-secondary opacity-50 fs-1 d-block mb-2"></i>
                <h5 class="fw-bold text-black font-display">Belum Ada Pengajuan Survei</h5>
                <p class="text-secondary small mb-3">Butuh bantuan pengukuran tenda? Ajukan survei lokasi gratis sekarang.</p>
                <a href="{{ route('survey.create') }}" class="btn-hero-primary">Ajukan Survei</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="editorial-table align-middle">
                    <thead>
                        <tr>
                            <th>Nama Rencana Acara</th>
                            <th>Rencana Tanggal</th>
                            <th>Alamat Lokasi</th>
                            <th class="text-center">Status</th>
                            <th>Catatan Tim Lapangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($surveys as $survey)
                            <tr>
                                <td>
                                    <span class="fw-bold text-black small d-block">{{ $survey->event_name }}</span>
                                    <small class="text-secondary">Diajukan: {{ $survey->created_at->format('d/m/Y') }}</small>
                                </td>
                                <td class="small text-secondary">
                                    {{ \Carbon\Carbon::parse($survey->event_date)->format('d M Y') }}
                                    <small class="d-block">{{ $survey->estimated_time }}</small>
                                </td>
                                <td class="small text-secondary" style="max-width: 250px;">
                                    {{ $survey->address }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $sBadge = [
                                            'menunggu' => 'badge-status--warning',
                                            'dijadwalkan' => 'badge-status--info',
                                            'diproses' => 'badge-status--neutral',
                                            'selesai' => 'badge-status--success',
                                            'ditolak' => 'badge-status--danger',
                                            'dibatalkan' => 'badge-status--danger',
                                        ];
                                    @endphp
                                    <span class="badge-status {{ $sBadge[$survey->status] ?? 'badge-status--neutral' }}" style="font-size: 0.72rem;">
                                        {{ strtoupper($survey->status) }}
                                    </span>
                                </td>
                                <td class="small text-secondary">
                                    {{ $survey->admin_notes ?? '-' }}

                                    @if($survey->survey_result || !empty($survey->recommendations_json))
                                        <div class="mt-2 p-2.5 rounded-3 border" style="background: #FAF8F5; border-color: #E7E2DA !important;">
                                            @if($survey->survey_result)
                                                <div class="mb-2">
                                                    <strong class="text-black d-block font-display" style="font-size: 0.78rem;">Hasil Peninjauan Lokasi:</strong>
                                                    <span class="text-secondary" style="font-size: 0.75rem;">{{ $survey->survey_result }}</span>
                                                </div>
                                            @endif

                                            @if(!empty($survey->recommendations_json))
                                                <strong class="text-black d-block font-display mb-1" style="font-size: 0.78rem;">Rekomendasi Perlengkapan:</strong>
                                                <ul class="list-unstyled mb-2" style="font-size: 0.75rem;">
                                                    @foreach($survey->recommendations_json as $rec)
                                                        <li class="d-flex justify-content-between text-black py-0.5 border-bottom" style="border-color: #F0ECE6 !important;">
                                                            <span>&bull; {{ $rec['name'] ?? 'Item' }}</span>
                                                            <strong>{{ $rec['quantity'] ?? 1 }} {{ $rec['unit'] ?? 'unit' }}</strong>
                                                        </li>
                                                    @endforeach
                                                </ul>

                                                @if($survey->isRecommendationActive())
                                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                                                        <small class="text-success" style="font-size: 0.72rem;">
                                                            <i class="bi bi-clock-history me-1"></i> Berlaku s.d {{ $survey->recommendation_expires_at?->format('d/m/Y H:i') }}
                                                        </small>
                                                        <form action="{{ route('dashboard.surveys.apply-recommendation', $survey->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="btn-hero-primary btn-sm py-1 px-2.5" style="font-size: 0.75rem;">
                                                                <i class="bi bi-cart-plus me-1"></i> Lanjutkan Pemesanan dari Rekomendasi
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <small class="text-danger d-block" style="font-size: 0.72rem;">
                                                        <i class="bi bi-exclamation-circle me-1"></i> Rekomendasi ini sudah kedaluwarsa. Silakan ajukan survei ulang jika diperlukan.
                                                    </small>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($surveys->hasPages())
                <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
                    {{ $surveys->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
