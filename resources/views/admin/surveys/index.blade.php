@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Manajemen Survei Lokasi</h4>
        <p class="text-secondary small mb-0">Kelola permohonan kunjungan, tugaskan petugas lapangan, dan pantau status survei.</p>
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
                    <th class="py-3 px-3">Pemohon</th>
                    <th class="py-3 px-3">Rencana Acara</th>
                    <th class="py-3 px-3">Alamat Lokasi</th>
                    <th class="py-3 px-3 text-center">Petugas Ditugaskan</th>
                    <th class="py-3 px-3 text-center">Status</th>
                    <th class="py-3 px-4 text-center">Tindakan</th>
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
                            <span class="d-block fw-semibold text-black">
                                {{ \Carbon\Carbon::parse($survey->event_date)->format('d M Y') }}
                            </span>
                            @if($survey->survey_date)
                                <small class="text-primary fw-semibold">
                                    <i class="bi bi-calendar-check me-1"></i>Survey: {{ $survey->survey_date->format('d M Y') }}
                                </small>
                            @else
                                <small class="text-secondary">Belum dijadwalkan</small>
                            @endif
                        </td>
                        <td class="py-3 px-3 small text-secondary" style="max-width: 200px;">
                            {{ $survey->address }}
                            @if($survey->latitude && $survey->longitude)
                                <a href="https://maps.google.com/?q={{ $survey->latitude }},{{ $survey->longitude }}" target="_blank" class="d-block small text-primary text-decoration-none mt-1">
                                    <i class="bi bi-geo-alt-fill me-1"></i> Buka Maps
                                </a>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center">
                            @if($survey->assignedWorker)
                                <span class="d-block fw-semibold small text-black">
                                    <i class="bi bi-person-badge me-1 text-primary"></i>{{ $survey->assignedWorker->name }}
                                </span>
                                @if($survey->worker_instructions)
                                    <small class="text-secondary fst-italic" title="{{ $survey->worker_instructions }}">
                                        "{{ Str::limit($survey->worker_instructions, 40) }}"
                                    </small>
                                @endif
                            @else
                                <span class="badge bg-light text-secondary border small">
                                    <i class="bi bi-person-x me-1"></i> Belum Ditugaskan
                                </span>
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
                            <div class="d-flex justify-content-center gap-1">
                                <!-- Tombol Tugaskan Pekerja -->
                                <button type="button" class="btn btn-sm btn-dark rounded-2" data-bs-toggle="modal" data-bs-target="#assignWorkerModal{{ $survey->id }}" title="Tugaskan Petugas Lapangan">
                                    <i class="bi bi-person-badge-fill me-1"></i> Tugaskan
                                </button>
                                <!-- Tombol Update Status -->
                                <button type="button" class="btn btn-sm btn-outline-dark rounded-2" data-bs-toggle="modal" data-bs-target="#updateSurveyModal{{ $survey->id }}" title="Update Status">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>

                            {{-- Modal: Tugaskan Pekerja --}}
                            <div class="modal fade text-start" id="assignWorkerModal{{ $survey->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-bottom">
                                            <h6 class="modal-title fw-bold text-black">
                                                <i class="bi bi-person-badge me-2"></i>Tugaskan Petugas Lapangan
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.surveys.assign-worker', $survey->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <!-- Ringkasan Survei -->
                                                <div class="bg-light rounded-3 p-3 mb-3 border">
                                                    <div class="row g-2 small">
                                                        <div class="col-6">
                                                            <span class="text-secondary d-block">Nama Acara</span>
                                                            <strong class="text-black">{{ $survey->event_name }}</strong>
                                                        </div>
                                                        <div class="col-6">
                                                            <span class="text-secondary d-block">Pemohon</span>
                                                            <strong class="text-black">{{ $survey->user->name ?? '-' }}</strong>
                                                            <span class="text-secondary d-block">{{ $survey->phone }}</span>
                                                        </div>
                                                        <div class="col-12">
                                                            <span class="text-secondary d-block">Alamat Lokasi</span>
                                                            <strong class="text-black">{{ $survey->address }}</strong>
                                                            @if($survey->latitude && $survey->longitude)
                                                                <a href="https://maps.google.com/?q={{ $survey->latitude }},{{ $survey->longitude }}" target="_blank" class="d-inline-block ms-2 small text-primary">
                                                                    <i class="bi bi-geo-alt-fill"></i> Lihat Maps
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Pilih Pekerja -->
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-black">
                                                        Pilih Petugas Lapangan yang Ditugaskan <span class="text-danger">*</span>
                                                    </label>
                                                    <select name="assigned_worker_id" class="form-select rounded-3" required>
                                                        <option value="">-- Pilih petugas lapangan --</option>
                                                        @foreach($workers as $worker)
                                                            <option value="{{ $worker->id }}"
                                                                {{ $survey->assigned_worker_id == $worker->id ? 'selected' : '' }}>
                                                                {{ $worker->name }}
                                                                ({{ strtoupper($worker->role) }})
                                                                @if($worker->phone) — {{ $worker->phone }} @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if($workers->isEmpty())
                                                        <small class="text-danger mt-1 d-block">
                                                            Belum ada pekerja terdaftar. <a href="{{ route('admin.workers') }}">Tambahkan pekerja terlebih dahulu.</a>
                                                        </small>
                                                    @endif
                                                </div>

                                                <!-- Jadwal Kunjungan Survei -->
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-black">Tanggal Kunjungan Survei</label>
                                                    <input type="date" name="survey_date" class="form-control rounded-3"
                                                        value="{{ $survey->survey_date?->format('Y-m-d') }}"
                                                        min="{{ date('Y-m-d') }}">
                                                    <small class="text-secondary">Tanggal tim lapangan akan datang ke lokasi.</small>
                                                </div>

                                                <!-- Instruksi untuk Pekerja -->
                                                <div class="mb-0">
                                                    <label class="form-label small fw-bold text-black">Instruksi / Catatan untuk Petugas</label>
                                                    <textarea name="worker_instructions" rows="3" class="form-control rounded-3"
                                                        placeholder="Contoh: Ukur lahan minimal 10x20m. Periksa akses jalan untuk truk. Pastikan area bebas dari hambatan atap.">{{ $survey->worker_instructions }}</textarea>
                                                    <small class="text-secondary">Instruksi ini akan menjadi panduan petugas saat melakukan survei lapangan.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-light btn-sm rounded-2" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-dark btn-sm rounded-2 px-3 fw-bold">
                                                    <i class="bi bi-send-check me-1"></i> Tugaskan Petugas
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Modal: Update Status Survei --}}
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
                                                    <label class="form-label small fw-semibold text-black">Jadwal Tanggal Survei</label>
                                                    <input type="date" name="survey_date" class="form-control rounded-3"
                                                        value="{{ $survey->survey_date?->format('Y-m-d') }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-black">Catatan Hasil Survei / Instruksi</label>
                                                    <textarea name="admin_notes" rows="3" class="form-control rounded-3"
                                                        placeholder="Misal: Survei dijadwalkan tgl 20/09 jam 14:00. Hasil: Rekomendasi Tenda 6x12m.">{{ old('admin_notes', $survey->admin_notes) }}</textarea>
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
                        <td colspan="7" class="text-center text-secondary py-5">
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
