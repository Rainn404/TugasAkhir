@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <span class="text-uppercase text-secondary fw-bold small tracking-wider">Survei Lapangan</span>
            <h2 class="fw-bold text-black mb-1">Riwayat Pengajuan Survei Lokasi</h2>
        </div>
        <a href="{{ route('survey.create') }}" class="btn btn-dark btn-sm rounded-3">
            <i class="bi bi-plus-lg me-1"></i> Ajukan Survei Baru
        </a>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills gap-2 mb-4 border-bottom pb-3">
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.index') }}">
                <i class="bi bi-grid me-1"></i> Ringkasan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.orders') }}">
                <i class="bi bi-receipt me-1"></i> Pesanan Saya
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.events') }}">
                <i class="bi bi-calendar-event me-1"></i> Kegiatan Acara
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active bg-black rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.surveys') }}">
                <i class="bi bi-geo-alt me-1"></i> Survei Lokasi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-secondary bg-white border rounded-3 px-3 py-2 small fw-semibold" href="{{ route('dashboard.profile') }}">
                <i class="bi bi-person me-1"></i> Profil Akun
            </a>
        </li>
    </ul>

    <!-- Surveys Table -->
    <div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
        @if($surveys->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-geo-alt text-secondary opacity-50 fs-1 d-block mb-2"></i>
                <h5 class="fw-bold text-black">Belum Ada Pengajuan Survei</h5>
                <p class="text-secondary small mb-3">Butuh bantuan pengukuran tenda? Ajukan survei lokasi gratis sekarang.</p>
                <a href="{{ route('survey.create') }}" class="btn btn-dark btn-sm rounded-3">Ajukan Survei</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="py-3 px-4 text-secondary small fw-bold">Nama Rencana Acara</th>
                            <th class="py-3 px-3 text-secondary small fw-bold">Rencana Tanggal</th>
                            <th class="py-3 px-3 text-secondary small fw-bold">Alamat Lokasi</th>
                            <th class="py-3 px-3 text-secondary small fw-bold text-center">Status</th>
                            <th class="py-3 px-4 text-secondary small fw-bold">Catatan Tim Lapangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($surveys as $survey)
                            <tr>
                                <td class="py-3 px-4">
                                    <span class="fw-bold text-black small d-block">{{ $survey->event_name }}</span>
                                    <small class="text-secondary">Diajukan: {{ $survey->created_at->format('d/m/Y') }}</small>
                                </td>
                                <td class="py-3 px-3 small text-secondary">
                                    {{ \Carbon\Carbon::parse($survey->event_date)->format('d M Y') }}
                                    <small class="d-block">{{ $survey->estimated_time }}</small>
                                </td>
                                <td class="py-3 px-3 small text-secondary" style="max-width: 250px;">
                                    {{ $survey->address }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @php
                                        $sBadge = [
                                            'menunggu' => 'bg-warning text-dark',
                                            'dijadwalkan' => 'bg-info text-dark',
                                            'diproses' => 'bg-primary text-white',
                                            'selesai' => 'bg-success text-white',
                                            'ditolak' => 'bg-danger text-white',
                                            'dibatalkan' => 'bg-secondary text-white',
                                        ];
                                    @endphp
                                    <span class="badge {{ $sBadge[$survey->status] ?? 'bg-light text-dark' }} small">
                                        {{ strtoupper($survey->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 small text-secondary">
                                    {{ $survey->admin_notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($surveys->hasPages())
                <div class="p-3 border-top">
                    {{ $surveys->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
