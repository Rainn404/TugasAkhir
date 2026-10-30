@extends('layouts.admin')

@section('content')
<div class="page-editorial-header mb-4">
    <span class="page-eyebrow">Survei Lapangan</span>
    <h1 class="page-headline mb-1 font-display">Manajemen Survei Lokasi</h1>
    <p class="page-subtext">Kelola permohonan kunjungan teknis, tugaskan tim lapangan, dan pantau status pengukuran lokasi.</p>
</div>

<!-- Filter Status -->
<div class="editorial-card p-3 p-md-4 mb-4">
    <form action="{{ route('admin.surveys') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-8">
            <select name="status" class="form-select rounded-3">
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
            <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 w-100 fw-semibold">Filter Status</button>
            <a href="{{ route('admin.surveys') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold">Reset</a>
        </div>
    </form>
</div>

<!-- Surveys Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>Nama Acara</th>
                    <th>Pemohon</th>
                    <th>Rencana Acara</th>
                    <th>Alamat Lokasi</th>
                    <th class="text-center">Petugas Ditugaskan</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($surveys as $survey)
                    <tr>
                        <td>
                            <strong class="text-black small d-block">{{ $survey->event_name }}</strong>
                            <small class="text-secondary">Diajukan: {{ $survey->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td class="small">
                            <span class="d-block fw-semibold text-black">{{ $survey->user->name ?? '-' }}</span>
                            <span class="text-secondary"><i class="bi bi-whatsapp me-1 text-success"></i>{{ $survey->phone }}</span>
                        </td>
                        <td class="small">
                            <span class="d-block fw-semibold text-black">
                                {{ \Carbon\Carbon::parse($survey->event_date)->format('d M Y') }}
                            </span>
                            @if($survey->survey_date)
                                <small class="text-black fw-semibold d-block">
                                    <i class="bi bi-calendar-check me-1"></i>Survei: {{ $survey->survey_date->format('d M Y') }}
                                </small>
                            @else
                                <small class="text-secondary d-block">Belum dijadwalkan</small>
                            @endif
                        </td>
                        <td class="small text-secondary" style="max-width: 200px;">
                            {{ $survey->address }}
                            @if($survey->latitude && $survey->longitude)
                                <a href="https://maps.google.com/?q={{ $survey->latitude }},{{ $survey->longitude }}" target="_blank" class="d-block small text-black fw-semibold text-decoration-none mt-1">
                                    <i class="bi bi-geo-alt-fill me-1"></i> Buka Maps
                                </a>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($survey->assignedWorker)
                                <span class="d-block fw-semibold small text-black">
                                    <i class="bi bi-person-badge me-1"></i>{{ $survey->assignedWorker->name }}
                                </span>
                                @if($survey->worker_instructions)
                                    <small class="text-secondary fst-italic d-block" title="{{ $survey->worker_instructions }}">
                                        "{{ Str::limit($survey->worker_instructions, 35) }}"
                                    </small>
                                @endif
                            @else
                                <span class="badge-status badge-status--neutral" style="font-size: 0.7rem;">
                                    <i class="bi bi-person-x me-1"></i> Belum Ditugaskan
                                </span>
                            @endif
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
                            <span class="badge-status {{ $sBadge[$survey->status] ?? 'badge-status--neutral' }}" style="font-size: 0.7rem;">
                                {{ strtoupper($survey->status) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                @if(auth()->user()->role === 'admin')
                                    <!-- Tombol Tugaskan Pekerja -->
                                    <button type="button" class="btn btn-dark btn-sm rounded-pill px-2.5 py-1 text-nowrap" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#assignWorkerModal{{ $survey->id }}" title="Tugaskan Petugas Lapangan">
                                        <i class="bi bi-person-badge-fill me-1"></i> Tugaskan
                                    </button>
                                @endif
                                <!-- Tombol Rekomendasi -->
                                <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-2.5 py-1 text-nowrap" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#recommendationModal{{ $survey->id }}" title="Hasil Survei & Rekomendasi Perlengkapan">
                                    <i class="bi bi-clipboard-check me-1"></i> Rekomendasi
                                </button>
                                <!-- Tombol Update Status -->
                                <button type="button" class="btn btn-outline-dark btn-sm rounded-circle p-1" style="width: 28px; height: 28px;" data-bs-toggle="modal" data-bs-target="#updateSurveyModal{{ $survey->id }}" title="Update Status" aria-label="Update status survei #{{ $survey->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>

                            @if(auth()->user()->role === 'admin')
                            {{-- Modal: Tugaskan Pekerja --}}
                            <div class="modal fade text-start" id="assignWorkerModal{{ $survey->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                                            <h5 class="modal-title fw-bold text-black font-display fs-6">
                                                <i class="bi bi-person-badge me-2"></i>Tugaskan Petugas Lapangan
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.surveys.assign-worker', $survey->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body p-4">
                                                <!-- Ringkasan Survei -->
                                                <div class="rounded-3 p-3 mb-3 border" style="background: #FAF8F5; border-color: #DDD8D0 !important;">
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
                                                                <a href="https://maps.google.com/?q={{ $survey->latitude }},{{ $survey->longitude }}" target="_blank" class="d-inline-block ms-2 small text-black fw-semibold">
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
                                            <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                                                <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">
                                                    <i class="bi bi-send-check me-1"></i> Tugaskan Petugas
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- Modal: Hasil Survei & Rekomendasi Perlengkapan --}}
                            <div class="modal fade text-start" id="recommendationModal{{ $survey->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                                            <h5 class="modal-title fw-bold text-black font-display fs-6">
                                                <i class="bi bi-clipboard-check me-2"></i>Hasil Survei &amp; Rekomendasi Perlengkapan
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.surveys.recommendation', $survey->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body p-4">
                                                @if(!empty($survey->recommendations_json))
                                                    <div class="alert alert-info small rounded-3 mb-3">
                                                        <strong>Status Rekomendasi:</strong>
                                                        @if($survey->isRecommendationActive())
                                                            <span class="badge bg-success ms-1">AKTIF</span>
                                                            (Berlaku sampai {{ $survey->recommendation_expires_at?->format('d/m/Y H:i') }} WITA)
                                                        @else
                                                            <span class="badge bg-danger ms-1">KEDALUWARSA</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-black">Catatan Hasil Survei Kondisi Lokasi <span class="text-danger">*</span></label>
                                                    <textarea name="survey_result" rows="3" class="form-control rounded-3" required
                                                        placeholder="Contoh: Luas area 12x18m di atas paving block, akses mobil box aman, sumber listrik tersedia 2200W, disarankan tenda roder atau sarnafil.">{{ old('survey_result', $survey->survey_result) }}</textarea>
                                                </div>

                                                <label class="form-label small fw-bold text-black mb-2">Daftar Rekomendasi Perlengkapan Acara <span class="text-danger">*</span></label>
                                                <div class="table-responsive border rounded-3 mb-3" style="border-color: #E7E2DA !important;">
                                                    <table class="table table-sm align-middle mb-0" style="font-size: 0.85rem;">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th style="width: 50%;">Pilih Perlengkapan</th>
                                                                <th style="width: 25%;">Jumlah</th>
                                                                <th style="width: 25%;">Catatan Khusus</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @for($i = 0; $i < 4; $i++)
                                                                @php
                                                                    $existingItem = $survey->recommendations_json[$i] ?? null;
                                                                @endphp
                                                                <tr>
                                                                    <td>
                                                                        <select name="items[{{ $i }}][inventory_id]" class="form-select form-select-sm rounded-2" {{ $i === 0 ? 'required' : '' }}>
                                                                            <option value="">-- Pilih Item (Opsional) --</option>
                                                                            @foreach($allInventories as $inv)
                                                                                <option value="{{ $inv->id }}" {{ ($existingItem['inventory_id'] ?? null) == $inv->id ? 'selected' : '' }}>
                                                                                    {{ $inv->name }} (Rp {{ number_format($inv->price, 0, ',', '.') }}/{{ $inv->unit }})
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm rounded-2"
                                                                            placeholder="Qty" min="1" value="{{ $existingItem['quantity'] ?? ($i === 0 ? 1 : '') }}" {{ $i === 0 ? 'required' : '' }}>
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" name="items[{{ $i }}][notes]" class="form-control form-control-sm rounded-2"
                                                                            placeholder="Keterangan" value="{{ $existingItem['notes'] ?? '' }}">
                                                                    </td>
                                                                </tr>
                                                            @endfor
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <small class="text-secondary d-block">Minimal isi baris pertama. Rekomendasi ini akan dapat langsung dimasukkan pelanggan ke keranjang sewa mereka.</small>
                                            </div>
                                            <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                                                <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">
                                                    <i class="bi bi-save me-1"></i> Simpan Hasil &amp; Rekomendasi
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
                                        <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                                            <h5 class="modal-title fw-bold text-black font-display fs-6">Update Status Survei #{{ $survey->id }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.surveys.update-status', $survey->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body p-4">
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
                                            <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                                                <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">Simpan Status</button>
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
        <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
            {{ $surveys->links() }}
        </div>
    @endif
</div>
@endsection
