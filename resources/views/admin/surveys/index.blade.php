@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Manajemen Survei Lokasi</h4>
        <p class="text-secondary small mb-0">Kelola permohonan kunjungan dan pengukuran lokasi lapangan oleh pelanggan.</p>
    </div>
</div>

<!-- Filter Status -->
<div class="card p-3 border rounded-3 bg-white mb-4 shadow-sm">
    <form action="{{ route('admin.surveys') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-8">
            <select name="status" class="form-select form-select-sm rounded-2">
                <option value="">-- Semua Status Survei --</option>
                <option value="menunggu" {{ request('status') === 'menunggu' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                <option value="dijadwalkan" {{ request('status') === 'dijadwalkan' ? 'selected' : '' }}>Dijadwalkan</option>
                <option value="diproses" {{ request('status') === 'diproses' ? 'selected' : '' }}>Diproses (Tim di Lapangan)</option>
                <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                <option value="dibatalkan" {{ request('status') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-dark btn-sm rounded-2 w-100">Filter Status</button>
            <a href="{{ route('admin.surveys') }}" class="btn btn-outline-secondary btn-sm rounded-2">Reset</a>
        </div>
    </form>
</div>

<!-- Surveys Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">Nama Acara</th>
                    <th class="py-3 px-3">Pemohon (Customer)</th>
                    <th class="py-3 px-3">Rencana Acara</th>
                    <th class="py-3 px-3">Alamat Lokasi</th>
                    <th class="py-3 px-3 text-center">Status</th>
                    <th class="py-3 px-4 text-center">Tindakan & Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($surveys as $survey)
                    <tr>
                        <td class="py-3 px-4">
                            <strong class="text-black small d-block">{{ $survey->event_name }}</strong>
                            <small class="text-secondary">Diajukan: {{ $survey->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td class="py-3 px-3 small">
                            <span class="d-block fw-semibold text-black">{{ $survey->user->name ?? '-' }}</span>
                            <span class="text-secondary"><i class="bi bi-whatsapp me-1"></i>{{ $survey->phone }}</span>
                        </td>
                        <td class="py-3 px-3 small">
                            <span class="d-block fw-semibold text-black">{{ \Carbon\Carbon::parse($survey->event_date)->format('d M Y') }}</span>
                            <small class="text-secondary">{{ $survey->estimated_time }}</small>
                        </td>
                        <td class="py-3 px-3 small text-secondary" style="max-width: 220px;">
                            {{ $survey->address }}
                            @if($survey->latitude && $survey->longitude)
                                <a href="https://maps.google.com/?q={{ $survey->latitude }},{{ $survey->longitude }}" target="_blank" class="d-block small text-primary text-decoration-none mt-1">
                                    <i class="bi bi-geo-alt-fill me-1"></i> Buka Maps
                                </a>
                            @endif
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
                        <td class="py-3 px-4 text-center">
                            <!-- Button trigger Modal Update Status -->
                            <button type="button" class="btn btn-outline-dark btn-sm rounded-2" data-bs-toggle="modal" data-bs-target="#updateSurveyModal{{ $survey->id }}">
                                Update Status
                            </button>

                            <!-- Modal Update Status Survey -->
                            <div class="modal fade text-start" id="updateSurveyModal{{ $survey->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-bottom">
                                            <h6 class="modal-title fw-bold text-black">Update Status Survei #{{ $survey->id }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.surveys.update-status', $survey->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-black">Pilih Status Survei</label>
                                                    <select name="status" class="form-select rounded-3" required>
                                                        <option value="menunggu" {{ $survey->status === 'menunggu' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                                                        <option value="dijadwalkan" {{ $survey->status === 'dijadwalkan' ? 'selected' : '' }}>Dijadwalkan</option>
                                                        <option value="diproses" {{ $survey->status === 'diproses' ? 'selected' : '' }}>Diproses (Kunjungan Tim)</option>
                                                        <option value="selesai" {{ $survey->status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                                                        <option value="ditolak" {{ $survey->status === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                                                        <option value="dibatalkan" {{ $survey->status === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-black">Catatan Hasil Survei / Instruksi</label>
                                                    <textarea name="admin_notes" rows="3" class="form-control rounded-3" placeholder="Misal: Survei dijadwalkan tgl 20/09 jam 14:00. Hasil: Rekomendasi Tenda 6x12m.">{{ old('admin_notes', $survey->admin_notes) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-light btn-sm rounded-2" data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-dark btn-sm rounded-2 px-3 fw-bold">Simpan Status</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <i class="bi bi-geo-alt fs-2 d-block mb-1 opacity-50"></i>
                            Tidak ada data survei lokasi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($surveys->hasPages())
        <div class="p-3 border-top">
            {{ $surveys->links() }}
        </div>
    @endif
</div>
@endsection
