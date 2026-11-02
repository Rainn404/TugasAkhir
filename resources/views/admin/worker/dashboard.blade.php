@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 gap-2 gap-md-3 page-editorial-header">
    <div>
        <span class="page-eyebrow">Panel Operasional Lapangan</span>
        <h1 class="page-headline mb-1 font-display">Dashboard Petugas Lapangan</h1>
        <p class="page-subtext">Selamat bertugas, <strong>{{ auth()->user()->name }}</strong>! Pantau jadwal tugas lapangan, daftar survei lokasi, dan tahapan instalasi perlengkapan acara Anda.</p>
    </div>
    <div>
        <span class="badge-status badge-status--neutral px-3 py-2">
            <i class="bi bi-calendar3 me-1"></i> {{ date('l, d F Y') }}
        </span>
    </div>
</div>

<!-- Statistik Tugas Pekerja -->
<div class="row g-2 g-md-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="editorial-stat-card">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <span class="stat-kicker" style="color: #B45309;">Tugas Menunggu</span>
                <span class="badge-status badge-status--warning p-1 p-sm-1.5"><i class="bi bi-clock-history fs-6" style="color: #B45309;"></i></span>
            </div>
            <div class="stat-number my-1" style="color: #B45309;">{{ $stats['pending_tasks'] }}</div>
            <p class="stat-hint">Belum dimulai</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="editorial-stat-card">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <span class="stat-kicker" style="color: #2563EB;">Sedang Berjalan</span>
                <span class="badge-status badge-status--info p-1 p-sm-1.5"><i class="bi bi-gear-wide-connected fs-6 text-primary"></i></span>
            </div>
            <div class="stat-number my-1" style="color: #2563EB;">{{ $stats['in_progress_tasks'] }}</div>
            <p class="stat-hint">Sedang dikerjakan</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="editorial-stat-card">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <span class="stat-kicker" style="color: #15803D;">Tugas Selesai</span>
                <span class="badge-status badge-status--success p-1 p-sm-1.5"><i class="bi bi-check2-circle fs-6" style="color: #15803D;"></i></span>
            </div>
            <div class="stat-number my-1" style="color: #15803D;">{{ $stats['completed_tasks'] }}</div>
            <p class="stat-hint">Telah dituntaskan</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="editorial-stat-card">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <span class="stat-kicker">Survei Lapangan</span>
                <span class="badge-status badge-status--neutral p-1 p-sm-1.5"><i class="bi bi-geo-alt fs-6 text-black"></i></span>
            </div>
            <div class="stat-number my-1">{{ $stats['active_surveys'] }}</div>
            <p class="stat-hint">Tugas survei aktif</p>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Kolom Kiri: Tugas Operasional Pesanan -->
    <div class="col-lg-8">
        <div class="editorial-card overflow-hidden h-100">
            <div class="editorial-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-list-check fs-5 text-secondary"></i>
                    <h5 class="fw-bold text-black mb-0 font-display fs-6">Tugas Operasional Pesanan Saya</h5>
                </div>
                <a href="{{ route('admin.orders') }}" class="small text-secondary text-decoration-none fw-semibold">
                    Pesanan Saya ({{ $myAssignments->count() }}) &rarr;
                </a>
            </div>

            @php
                $taskLabels = [
                    'persiapan' => 'Persiapan Barang',
                    'pengiriman' => 'Pengiriman Lokasi',
                    'pemasangan' => 'Pemasangan Tenda / Alat',
                    'pemeriksaan_kembali' => 'Pemeriksaan Pengembalian',
                ];
                $taskBadgeStyles = [
                    'persiapan' => 'background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE;',
                    'pengiriman' => 'background: #FDF4FF; color: #A21CAF; border: 1px solid #F5D0FE;',
                    'pemasangan' => 'background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;',
                    'pemeriksaan_kembali' => 'background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A;',
                ];
                $statusBadges = [
                    'pending' => 'badge-status--warning',
                    'in_progress' => 'badge-status--info',
                    'completed' => 'badge-status--success',
                    'cancelled' => 'badge-status--danger',
                ];
                $statusLabels = [
                    'pending' => 'Menunggu',
                    'in_progress' => 'Berjalan',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                ];
            @endphp

            @if($myAssignments->isEmpty())
                <div class="text-center text-secondary py-5">
                    <i class="bi bi-clipboard-check fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <h6 class="fw-bold text-black mb-1 font-display">Belum Ada Tugas Operasional</h6>
                    <p class="small text-secondary mb-0">Administrator belum menugaskan tugas operasional pesanan kepada Anda saat ini.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="editorial-table align-middle">
                        <thead>
                            <tr>
                                <th>Jenis Tugas</th>
                                <th>Pesanan &amp; Acara</th>
                                <th>Jadwal Pelaksanaan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myAssignments as $assignment)
                                <tr>
                                    <td>
                                        <span class="badge rounded-pill px-2.5 py-1 fw-bold text-nowrap" style="{{ $taskBadgeStyles[$assignment->task_type] ?? 'background:#F3F4F6;color:#374151;' }}; font-size: 0.72rem;">
                                            {{ $taskLabels[$assignment->task_type] ?? $assignment->task_type }}
                                        </span>
                                        @if($assignment->instructions)
                                            <small class="d-block text-secondary mt-1 text-truncate" style="max-width: 170px;" title="{{ $assignment->instructions }}">
                                                <i class="bi bi-info-circle me-0.5"></i> {{ $assignment->instructions }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $assignment->order_id) }}" class="fw-bold text-black font-monospace small text-decoration-none d-block">
                                            #{{ $assignment->order->order_number ?? '-' }}
                                        </a>
                                        <span class="small text-black fw-medium text-truncate d-block" style="max-width: 220px;" title="{{ $assignment->order->event->event_name ?? '-' }}">
                                            {{ $assignment->order->event->event_name ?? 'Acara' }}
                                        </span>
                                        <small class="text-secondary text-truncate d-block" style="max-width: 220px;">
                                            <i class="bi bi-geo-alt"></i> {{ $assignment->order->event->address ?? '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($assignment->scheduled_at)
                                            <span class="small fw-semibold text-black d-block">
                                                {{ \Carbon\Carbon::parse($assignment->scheduled_at)->format('d M Y') }}
                                            </span>
                                            <small class="text-secondary">
                                                {{ \Carbon\Carbon::parse($assignment->scheduled_at)->format('H:i') }} WITA
                                            </small>
                                        @else
                                            <span class="small text-secondary">Menyesuaikan Jadwal</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge-status {{ $statusBadges[$assignment->status] ?? 'badge-status--neutral' }}" style="font-size: 0.7rem;">
                                            {{ $statusLabels[$assignment->status] ?? strtoupper($assignment->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-2.5 py-1 me-1" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#workerTaskModal{{ $assignment->id }}">
                                            <i class="bi bi-pencil-square me-1"></i> Update
                                        </button>
                                        <a href="{{ route('admin.orders.show', $assignment->order_id) }}" class="btn btn-dark btn-sm rounded-pill px-2.5 py-1" style="font-size: 0.75rem;">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Kolom Kanan: Jadwal Acara Terdekat Saya -->
    <div class="col-lg-4">
        <div class="editorial-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-event fs-5 text-secondary"></i>
                    <h5 class="fw-bold text-black mb-0 font-display fs-6">Jadwal Acara Tugas Saya</h5>
                </div>
                <a href="{{ route('admin.events') }}" class="small text-secondary text-decoration-none fw-semibold">Lihat Semua &rarr;</a>
            </div>

            <div class="list-group list-group-flush">
                @forelse($myEvents as $evt)
                    <div class="list-group-item px-0 py-2.5 border-bottom last-border-0" style="border-color: #F0ECE6 !important; background: transparent;">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="fw-bold text-black mb-0 small text-truncate" style="max-width: 180px;" title="{{ $evt->event_name }}">{{ $evt->event_name }}</h6>
                            <span class="badge-status badge-status--neutral" style="font-size: 0.72rem;">
                                {{ \Carbon\Carbon::parse($evt->event_date)->format('d M') }}
                            </span>
                        </div>
                        <small class="text-secondary d-block"><i class="bi bi-clock me-1"></i>{{ $evt->start_time }} - {{ $evt->end_time }} WITA</small>
                        <small class="text-secondary d-block text-truncate" title="{{ $evt->address }}"><i class="bi bi-geo-alt me-1"></i>{{ $evt->address }}</small>
                    </div>
                @empty
                    <div class="text-center text-secondary py-4 small">Tidak ada jadwal acara mendatang pada tugas Anda.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Survei Lokasi yang Ditugaskan Kepada Saya -->
<div class="editorial-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-geo-alt-fill fs-5 text-secondary"></i>
            <div>
                <h5 class="fw-bold text-black mb-0 font-display fs-6">Tugas Survei Lokasi Saya</h5>
                <small class="text-secondary">Daftar survei lapangan yang ditugaskan administrator untuk Anda tinjau langsung ke lokasi acara.</small>
            </div>
        </div>
        <a href="{{ route('admin.surveys') }}" class="small text-secondary text-decoration-none fw-semibold">Kelola Seluruh Survei &rarr;</a>
    </div>

    @if($mySurveys->isEmpty())
        <div class="text-center text-secondary py-4">
            <i class="bi bi-geo fs-1 d-block mb-2 text-secondary opacity-50"></i>
            <span class="small d-block">Saat ini tidak ada survei lokasi yang ditugaskan kepada Anda.</span>
        </div>
    @else
        <div class="row g-3">
            @foreach($mySurveys as $srv)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 rounded-4 border p-3" style="border-color: #E7E2DA !important; background: #FAF8F5;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge-status {{ in_array($srv->status, ['dijadwalkan', 'diproses']) ? 'badge-status--info' : ($srv->status === 'selesai' ? 'badge-status--success' : 'badge-status--neutral') }}" style="font-size: 0.72rem;">
                                {{ strtoupper($srv->status) }}
                            </span>
                            @if($srv->survey_date)
                                <small class="text-black fw-semibold font-monospace" style="font-size: 0.75rem;">
                                    <i class="bi bi-calendar3"></i> {{ \Carbon\Carbon::parse($srv->survey_date)->format('d/m/Y') }}
                                </small>
                            @endif
                        </div>

                        <h6 class="fw-bold text-black mb-1 font-display fs-6">{{ $srv->event_name }}</h6>
                        <small class="text-secondary d-block mb-2">
                            <i class="bi bi-person"></i> {{ $srv->user->name ?? '-' }} ({{ $srv->user->phone ?? '-' }})
                        </small>

                        <div class="small text-secondary mb-3">
                            <div class="text-truncate mb-1" title="{{ $srv->address }}">
                                <i class="bi bi-geo-alt text-danger me-1"></i> {{ $srv->address }}
                            </div>
                            @if($srv->worker_instructions)
                                <div class="p-2 rounded-2 mt-2" style="background: #FFFFFF; border: 1px solid #E7E2DA; font-size: 0.75rem;">
                                    <strong class="text-black d-block mb-0.5"><i class="bi bi-chat-left-text me-1"></i>Instruksi Admin:</strong>
                                    {{ $srv->worker_instructions }}
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2 mt-auto pt-2 border-top" style="border-color: #E7E2DA !important;">
                            @if($srv->latitude && $srv->longitude)
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $srv->latitude }},{{ $srv->longitude }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-sm rounded-pill flex-fill" style="font-size: 0.75rem;">
                                    <i class="bi bi-geo-alt"></i> Peta
                                </a>
                            @endif
                            <a href="{{ route('admin.surveys') }}" class="btn btn-dark btn-sm rounded-pill flex-fill" style="font-size: 0.75rem;">
                                <i class="bi bi-file-earmark-text"></i> Rekomendasi
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Modals for Worker Task Update --}}
@foreach($myAssignments as $assignment)
<div class="modal fade" id="workerTaskModal{{ $assignment->id }}" tabindex="-1" aria-labelledby="workerTaskModalLabel{{ $assignment->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="modal-title fw-bold text-black font-display fs-6" id="workerTaskModalLabel{{ $assignment->id }}">
                    Update Progres Tugas: {{ $taskLabels[$assignment->task_type] ?? $assignment->task_type }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('admin.order-assignments.update-status', $assignment->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-black">Status Progres</label>
                        <select name="status" class="form-select rounded-3" required>
                            <option value="pending" {{ $assignment->status === 'pending' ? 'selected' : '' }}>Menunggu Pelaksanaan</option>
                            <option value="in_progress" {{ $assignment->status === 'in_progress' ? 'selected' : '' }}>Sedang Dikerjakan (In Progress)</option>
                            <option value="completed" {{ $assignment->status === 'completed' ? 'selected' : '' }}>Selesai Dilaksanakan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-black">Catatan Lapangan / Kendala (Opsional)</label>
                        <textarea name="worker_notes" rows="3" class="form-control rounded-3" placeholder="Informasikan kondisi lapangan, waktu tiba, atau kendala pemasangan...">{{ $assignment->worker_notes }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 fw-semibold">Simpan Progres</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

