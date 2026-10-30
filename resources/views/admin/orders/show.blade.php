@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
    <div>
        <a href="{{ route('admin.orders') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1 mb-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Pesanan
        </a>
        <h1 class="page-headline mb-0 font-monospace">Kelola Pesanan #{{ $order->order_number }}</h1>
    </div>
    <div class="d-flex align-items-center gap-2">
        @php
            $bStyles = [
                'pending_payment' => 'badge-status--warning',
                'paid' => 'badge-status--success',
                'confirmed' => 'badge-status--success',
                'preparing' => 'badge-status--info',
                'ongoing' => 'badge-status--neutral',
                'completed' => 'badge-status--success',
                'cancelled' => 'badge-status--danger',
                'payment_conflict' => 'badge-status--danger',
            ];
        @endphp
        <span class="badge-status {{ $bStyles[$order->status] ?? 'badge-status--neutral' }}">
            Status: {{ strtoupper(str_replace('_', ' ', $order->status)) }}
        </span>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Detail Acara & Peta & Form Status -->
    <div class="col-lg-7">
        <!-- Form Pembaruan Status Pesanan -->
        <div class="editorial-card p-4 mb-4">
            <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display fs-6 d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                <i class="bi bi-arrow-clockwise text-secondary"></i> Perbarui Status Tahapan Pesanan
            </h5>
            @if($order->status === 'payment_conflict')
                <div class="alert alert-danger small mb-0 rounded-3" role="alert">
                    Status pesanan dikunci karena lebih dari satu attempt Midtrans terkonfirmasi lunas. Rekonsiliasi transaksi terlebih dahulu.
                </div>
            @elseif(in_array($order->status, ['completed', 'cancelled']))
                <div class="alert alert-secondary small mb-0 rounded-3" role="status">
                    <i class="bi bi-info-circle me-1"></i> Pesanan ini berstatus <strong>{{ strtoupper($order->status) }}</strong> (tahap akhir) dan tidak dapat diubah lagi.
                </div>
            @else
                <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="d-flex flex-column flex-sm-row gap-2">
                    @csrf
                    <select name="status" class="form-select rounded-3 fw-semibold">
                        @if(auth()->user()->role === 'pekerja')
                            @if($order->status === 'confirmed')
                                <option value="confirmed" selected>-- Pilih Tahapan Operasional --</option>
                                <option value="preparing">Disiapkan (Muat Truk &amp; Packing)</option>
                            @elseif($order->status === 'preparing')
                                <option value="preparing" selected>-- Pilih Tahapan Operasional --</option>
                                <option value="ongoing">Sedang Digunakan (Terpasang di Lokasi)</option>
                            @else
                                <option value="{{ $order->status }}" selected>{{ strtoupper(str_replace('_', ' ', $order->status)) }} (Dikelola Administrator)</option>
                            @endif
                        @else
                            {{-- Admin View: Hanya tampilkan transisi yang valid --}}
                            <option value="{{ $order->status }}" selected>Status Saat Ini: {{ strtoupper(str_replace('_', ' ', $order->status)) }}</option>
                            @if($order->status === 'pending_payment')
                                <option value="paid">Dibayar (Lunas Manual oleh Admin)</option>
                                <option value="cancelled">Dibatalkan</option>
                            @elseif($order->status === 'paid')
                                <option value="confirmed">Dikonfirmasi (Siap Jadwal)</option>
                                <option value="cancelled">Dibatalkan</option>
                            @elseif($order->status === 'confirmed')
                                <option value="preparing">Disiapkan (Muat Truk &amp; Packing)</option>
                                <option value="cancelled">Dibatalkan</option>
                            @elseif($order->status === 'preparing')
                                <option value="ongoing">Sedang Digunakan (Terpasang di Lokasi)</option>
                                <option value="cancelled">Dibatalkan</option>
                            @elseif($order->status === 'ongoing')
                                <option value="completed">Selesai (Setelah Verifikasi Pengembalian)</option>
                                <option value="cancelled">Dibatalkan</option>
                            @endif
                        @endif
                    </select>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold text-nowrap">
                        Update Status
                    </button>
                </form>
            @endif
        </div>

        <!-- Detail Informasi Acara -->
        <div class="editorial-card p-4 mb-4">
            <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display fs-6 d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                <i class="bi bi-calendar-event text-secondary"></i> Informasi Kegiatan Acara
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
                        <span class="text-secondary d-block">Catatan Khusus:</span>
                        <span class="text-secondary fst-italic">{{ $order->notes }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Peta Titik Lokasi Pengiriman -->
        <div class="editorial-card p-4 mb-4">
            <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display fs-6 d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                <i class="bi bi-geo-alt text-secondary"></i> Titik Lokasi Peta Pengiriman
            </h5>
            <div id="adminOrderMap" class="rounded-3 border mb-2 overflow-hidden" style="height: 220px; border-color: #DDD8D0 !important;"
                 data-latitude="{{ (float) ($order->event->latitude ?? -3.7955) }}"
                 data-longitude="{{ (float) ($order->event->longitude ?? 114.7712) }}"
                 data-event-name="{{ $order->event->event_name }}"
                 data-address="{{ $order->event->address }}"></div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-secondary font-monospace" style="font-size: 0.75rem;">Koordinat: {{ $order->event->latitude }}, {{ $order->event->longitude }}</small>
                <a href="https://www.google.com/maps/dir/?api=1&origin={{ config('services.google_maps.base_location.latitude') }},{{ config('services.google_maps.base_location.longitude') }}&destination={{ $order->event->latitude }},{{ $order->event->longitude }}"
                   target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-sm rounded-pill px-3" style="font-size: 0.75rem;">
                    <i class="bi bi-geo-alt me-1"></i> Buka Google Maps
                </a>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Rincian Pelanggan, Inventaris, Pembayaran -->
    <div class="col-lg-5">
        <!-- Informasi Pelanggan -->
        <div class="editorial-card p-4 mb-4">
            <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display fs-6 d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                <i class="bi bi-person text-secondary"></i> Data Pelanggan
            </h5>
            <div class="small">
                <div class="mb-1"><span class="text-secondary">Nama:</span> <strong class="text-black">{{ $order->user->name ?? '-' }}</strong></div>
                <div class="mb-1"><span class="text-secondary">Email:</span> <strong class="text-black">{{ $order->user->email ?? '-' }}</strong></div>
                <div class="mb-1"><span class="text-secondary">Telepon:</span> <strong class="text-black">{{ $order->user->phone ?? '-' }}</strong></div>
            </div>
        </div>

        <!-- Rincian Perlengkapan Dipesan (Manifest) -->
        <div class="editorial-card p-4 mb-4">
            <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display fs-6 d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                <i class="bi bi-box-seam text-secondary"></i> Manifest Perlengkapan
            </h5>
            <div class="list-group list-group-flush mb-3">
                @foreach($order->orderDetails as $detail)
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start small border-bottom last-border-0" style="border-color: #F0ECE6 !important; background: transparent;">
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

            <div class="d-flex justify-content-between fw-bold text-black border-top pt-2 fs-6" style="border-color: #E7E2DA !important;">
                <span>Total Tagihan:</span>
                <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Status Pembayaran -->
        <div class="editorial-card p-4">
            <h5 class="fw-bold text-black mb-3 pb-2 border-bottom font-display fs-6 d-flex align-items-center gap-2" style="border-color: #E7E2DA !important;">
                <i class="bi bi-credit-card text-secondary"></i> Status Transaksi Pembayaran
            </h5>
            <div class="small text-secondary">
                <div class="d-flex justify-content-between mb-1">
                    <span>Status Payment:</span>
                    <strong class="text-black">{{ strtoupper($order->payment_status ?? 'PENDING') }}</strong>
                </div>
                @if($order->status === 'payment_conflict')
                    <div class="alert alert-danger small mt-2 mb-2 rounded-3" role="alert">
                        Beberapa attempt Midtrans terkonfirmasi lunas. Pesanan dikunci sampai transaksi direkonsiliasi.
                    </div>
                @endif
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
                @foreach($order->payment?->attempts ?? [] as $attempt)
                    <div class="border-top mt-2 pt-2 small" style="border-color: #E7E2DA !important;">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="font-monospace">{{ $attempt->midtrans_order_id }}</span>
                            <strong>{{ strtoupper($attempt->status) }}</strong>
                        </div>
                        @if($attempt->transaction_id)
                            <div class="text-secondary">Transaction ID: {{ $attempt->transaction_id }}</div>
                        @endif
                        @if($attempt->last_error)
                            <div class="text-danger">{{ $attempt->last_error }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Baris Baru: Penugasan Petugas & Pemeriksaan Pengembalian -->
<div class="row g-4 mt-1">
    <!-- Penugasan Tim Lapangan -->
    <div class="col-lg-6">
        <div class="editorial-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="fw-bold text-black font-display fs-6 mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-secondary"></i> Penugasan Tim Lapangan
                </h5>
                @if(auth()->user()->role === 'admin')
                    <button type="button" class="btn btn-dark btn-sm rounded-pill px-3 py-1" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#assignTaskModal">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Tugas
                    </button>
                @endif
            </div>

            @if($order->assignments->isEmpty())
                <div class="text-center py-4 text-secondary">
                    <i class="bi bi-person-x fs-2 d-block mb-1 opacity-50"></i>
                    <p class="small mb-0">Belum ada petugas yang ditugaskan untuk pesanan ini.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="editorial-table align-middle">
                        <thead>
                            <tr>
                                <th>Tugas</th>
                                <th>Petugas</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->assignments as $assignment)
                                <tr>
                                    <td>
                                        <strong class="text-black small d-block">{{ $taskTypes[$assignment->task_type] ?? $assignment->task_type }}</strong>
                                        @if($assignment->scheduled_at)
                                            <small class="text-secondary">{{ $assignment->scheduled_at->format('d/m/Y H:i') }}</small>
                                        @endif
                                        @if($assignment->instructions)
                                            <small class="text-secondary d-block fst-italic">"{{ Str::limit($assignment->instructions, 30) }}"</small>
                                        @endif
                                    </td>
                                    <td class="small">
                                        <span class="d-block fw-semibold text-black">{{ $assignment->worker->name ?? '-' }}</span>
                                        <span class="text-secondary">{{ $assignment->worker->phone ?? '' }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $taskStatusBadges = [
                                                'pending' => 'badge-status--warning',
                                                'in_progress' => 'badge-status--info',
                                                'completed' => 'badge-status--success',
                                                'cancelled' => 'badge-status--danger',
                                            ];
                                        @endphp
                                        <span class="badge-status {{ $taskStatusBadges[$assignment->status] ?? 'badge-status--neutral' }}" style="font-size: 0.7rem;">
                                            {{ strtoupper(str_replace('_', ' ', $assignment->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if(auth()->user()->role === 'admin' || $assignment->user_id === auth()->id())
                                            <button type="button" class="btn btn-outline-dark btn-sm rounded-circle p-1" style="width: 28px; height: 28px;" data-bs-toggle="modal" data-bs-target="#updateTaskModal{{ $assignment->id }}" title="Update Progres Tugas" aria-label="Update progres tugas {{ $assignment->task_type }} untuk {{ $assignment->worker->name ?? 'petugas' }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Modal Update Status Tugas --}}
                                <div class="modal fade text-start" id="updateTaskModal{{ $assignment->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                                                <h5 class="modal-title fw-bold text-black font-display fs-6">Update Progres: {{ $taskTypes[$assignment->task_type] ?? $assignment->task_type }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('admin.order-assignments.update-status', $assignment->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold text-black">Status Progres</label>
                                                        <select name="status" class="form-select rounded-3" required>
                                                            <option value="pending" {{ $assignment->status === 'pending' ? 'selected' : '' }}>Pending (Menunggu)</option>
                                                            <option value="in_progress" {{ $assignment->status === 'in_progress' ? 'selected' : '' }}>In Progress (Sedang Dikerjakan)</option>
                                                            <option value="completed" {{ $assignment->status === 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
                                                            <option value="cancelled" {{ $assignment->status === 'cancelled' ? 'selected' : '' }}>Cancelled (Dibatalkan)</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-0">
                                                        <label class="form-label small fw-semibold text-black">Catatan Petugas Lapangan</label>
                                                        <textarea name="worker_notes" rows="3" class="form-control rounded-3" placeholder="Contoh: Barang sudah selesai dimuat ke truk dan siap kirim.">{{ old('worker_notes', $assignment->worker_notes) }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                                                    <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">Simpan Progres</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Pemeriksaan Pengembalian & Denda Kursi -->
    <div class="col-lg-6">
        <div class="editorial-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #E7E2DA !important;">
                <div>
                    <h5 class="fw-bold text-black font-display fs-6 mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-box-arrow-in-left text-secondary"></i> Pemeriksaan Pengembalian Barang
                    </h5>
                    <small class="text-secondary" style="font-size: 0.72rem;">Denda kerusakan/kehilangan hanya berlaku pada kategori Kursi.</small>
                </div>
                @if($order->returnInspection)
                    @php
                        $retStatusBadge = [
                            'draft' => 'badge-status--warning',
                            'submitted' => 'badge-status--info',
                            'verified' => 'badge-status--success',
                        ];
                    @endphp
                    <span class="badge-status {{ $retStatusBadge[$order->returnInspection->status] ?? 'badge-status--neutral' }}" style="font-size: 0.7rem;">
                        {{ strtoupper($order->returnInspection->status) }}
                    </span>
                @else
                    <span class="badge-status badge-status--neutral" style="font-size: 0.7rem;">BELUM DIPERIKSA</span>
                @endif
            </div>

            @if($order->returnInspection)
                <div class="mb-3 p-3 rounded-3 border" style="background: #FAF8F5; border-color: #E7E2DA !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-secondary">Pemeriksa: <strong>{{ $order->returnInspection->inspector->name ?? '-' }}</strong></span>
                        <span class="small text-secondary">{{ $order->returnInspection->inspected_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($order->returnInspection->fine_chair_amount > 0)
                        <div class="d-flex justify-content-between align-items-center py-1.5 px-2.5 rounded-2 bg-white border border-danger mb-2">
                            <span class="small fw-bold text-danger"><i class="bi bi-exclamation-octagon me-1"></i> Total Denda Kursi:</span>
                            <span class="fw-bold text-danger">Rp {{ number_format($order->returnInspection->fine_chair_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between small text-secondary">
                            <span>Status Denda:</span>
                            <strong class="{{ $order->returnInspection->fine_status === 'paid' ? 'text-success' : 'text-danger' }}">
                                {{ strtoupper($order->returnInspection->fine_status) }}
                            </strong>
                        </div>
                    @else
                        <div class="small text-success fw-semibold"><i class="bi bi-check-circle me-1"></i> Tidak ada denda kerusakan kursi.</div>
                    @endif

                    @if($order->returnInspection->inspection_notes)
                        <div class="small text-secondary mt-2 fst-italic">"{{ $order->returnInspection->inspection_notes }}"</div>
                    @endif
                </div>

                <!-- Tabel Rincian Kondisi Aktual Barang -->
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle" style="font-size: 0.78rem;">
                        <thead>
                            <tr class="text-secondary border-bottom">
                                <th>Item</th>
                                <th class="text-center">Sewa</th>
                                <th class="text-center text-success">Baik</th>
                                <th class="text-center text-warning">Perbaikan</th>
                                <th class="text-center text-danger">Rusak</th>
                                <th class="text-center text-danger">Hilang</th>
                                <th class="text-end">Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->returnInspection->items as $item)
                                <tr>
                                    <td>
                                        <strong class="text-black">{{ $item->inventory->name ?? 'Item' }}</strong>
                                    </td>
                                    <td class="text-center">{{ $item->quantity_rented }}</td>
                                    <td class="text-center fw-bold text-success">{{ $item->quantity_good }}</td>
                                    <td class="text-center">{{ $item->quantity_repair }}</td>
                                    <td class="text-center {{ $item->quantity_damaged > 0 ? 'fw-bold text-danger' : '' }}">{{ $item->quantity_damaged }}</td>
                                    <td class="text-center {{ $item->quantity_lost > 0 ? 'fw-bold text-danger' : '' }}">{{ $item->quantity_lost }}</td>
                                    <td class="text-end fw-bold {{ $item->subtotal_fine > 0 ? 'text-danger' : 'text-secondary' }}">
                                        Rp {{ number_format($item->subtotal_fine, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(auth()->user()->role === 'admin' && $order->returnInspection->status !== 'verified')
                    <div class="p-3 border rounded-3 bg-white" style="border-color: #E7E2DA !important;">
                        <h6 class="fw-bold text-black font-display small mb-2">Verifikasi Admin</h6>
                        <form action="{{ route('admin.orders.verify-return-inspection', $order->id) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small text-secondary mb-1">Status Pembayaran Denda Kursi</label>
                                <select name="fine_status" class="form-select form-select-sm rounded-2">
                                    <option value="unpaid" {{ $order->returnInspection->fine_status === 'unpaid' ? 'selected' : '' }}>Belum Dibayar (Unpaid)</option>
                                    <option value="paid" {{ $order->returnInspection->fine_status === 'paid' ? 'selected' : '' }}>Lunas / Dibayar (Paid)</option>
                                    <option value="waived" {{ $order->returnInspection->fine_status === 'waived' ? 'selected' : '' }}>Dibebaskan / Dimaafkan (Waived)</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <textarea name="admin_verification_notes" rows="2" class="form-control form-control-sm rounded-2" placeholder="Catatan verifikasi admin..."></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="action" value="approve" class="btn btn-dark btn-sm rounded-pill px-3 fw-semibold">
                                    <i class="bi bi-check-lg me-1"></i> Setujui &amp; Sesuaikan Stok
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                                    <i class="bi bi-x-lg me-1"></i> Tolak / Revisi
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            @endif

            <!-- Tombol Input / Edit Pemeriksaan -->
            <div class="mt-3">
                <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1.5 w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#inputReturnModal">
                    <i class="bi bi-clipboard2-check me-1"></i> {{ $order->returnInspection ? 'Perbarui Data Pemeriksaan' : 'Input Hasil Pemeriksaan Pengembalian' }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tugaskan Petugas (Admin Only) --}}
@if(auth()->user()->role === 'admin')
<div class="modal fade text-start" id="assignTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="modal-title fw-bold text-black font-display fs-6">
                    <i class="bi bi-person-plus me-2"></i>Tugaskan Petugas untuk Pesanan {{ $order->order_number }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.orders.assign-task', $order->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-black">Pilih Petugas Lapangan <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select rounded-3" required>
                            <option value="">-- Pilih petugas --</option>
                            @foreach($workers as $worker)
                                <option value="{{ $worker->id }}">{{ $worker->name }} ({{ strtoupper($worker->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-black">Jenis Tugas Operasional <span class="text-danger">*</span></label>
                        <select name="task_type" class="form-select rounded-3" required>
                            @foreach($taskTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-black">Jadwal Pelaksanaan Tugas</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control rounded-3" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-black">Instruksi Khusus untuk Petugas</label>
                        <textarea name="instructions" rows="3" class="form-control rounded-3" placeholder="Contoh: Pastikan tenda diikat kencang pada pasak cor beton karena area berangin kencang."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                    <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">Tugaskan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal Input Pemeriksaan Pengembalian --}}
<div class="modal fade text-start" id="inputReturnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom" style="border-color: #E7E2DA !important;">
                <h5 class="modal-title fw-bold text-black font-display fs-6">
                    <i class="bi bi-box-seam me-2"></i>Pemeriksaan Kondisi Pengembalian Barang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.orders.return-inspection', $order->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info small rounded-3 mb-3">
                        <strong>Aturan Denda Usaha Arsy Tenda:</strong>
                        Denda <em>hanya</em> diterapkan pada kategori <strong>Kursi</strong> yang rusak (Rp {{ number_format($systemSettings['chair_fine_damaged'], 0, ',', '.') }}/unit) atau hilang (Rp {{ number_format($systemSettings['chair_fine_lost'], 0, ',', '.') }}/unit). Perlengkapan lain tidak dikenakan denda.
                    </div>

                    <div class="table-responsive border rounded-3 mb-3" style="border-color: #E7E2DA !important;">
                        <table class="table table-sm align-middle mb-0" style="font-size: 0.8rem;">
                            <thead class="bg-light">
                                <tr>
                                    <th>Perlengkapan</th>
                                    <th class="text-center" style="width: 70px;">Sewa</th>
                                    <th style="width: 80px;">Baik</th>
                                    <th style="width: 80px;">Perbaikan</th>
                                    <th style="width: 80px;">Rusak</th>
                                    <th style="width: 80px;">Hilang</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderDetails as $idx => $detail)
                                    @php
                                        $existingRetItem = $order->returnInspection?->items?->firstWhere('order_detail_id', $detail->id);
                                        $isChair = strtolower($detail->inventory->category?->slug ?? '') === 'kursi' || str_contains(strtolower($detail->inventory->name ?? ''), 'kursi');
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="hidden" name="items[{{ $idx }}][order_detail_id]" value="{{ $detail->id }}">
                                            <strong class="text-black d-block">{{ $detail->inventory->name }}</strong>
                                            @if($isChair)
                                                <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">KATEGORI KURSI (KENA DENDA)</span>
                                            @else
                                                <span class="text-secondary" style="font-size: 0.7rem;">Non-Kursi (Bebas Denda)</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold">{{ $detail->quantity }}</td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][quantity_good]" class="form-control form-control-sm text-center"
                                                min="0" max="{{ $detail->quantity }}" value="{{ $existingRetItem->quantity_good ?? $detail->quantity }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][quantity_repair]" class="form-control form-control-sm text-center"
                                                min="0" max="{{ $detail->quantity }}" value="{{ $existingRetItem->quantity_repair ?? 0 }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][quantity_damaged]" class="form-control form-control-sm text-center"
                                                min="0" max="{{ $detail->quantity }}" value="{{ $existingRetItem->quantity_damaged ?? 0 }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][quantity_lost]" class="form-control form-control-sm text-center"
                                                min="0" max="{{ $detail->quantity }}" value="{{ $existingRetItem->quantity_lost ?? 0 }}" required>
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $idx }}][condition_notes]" class="form-control form-control-sm"
                                                placeholder="Catatan kondisi" value="{{ $existingRetItem->condition_notes ?? '' }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <small class="text-secondary d-block mb-3">Total (Baik + Perbaikan + Rusak + Hilang) untuk masing-masing item harus sama persis dengan jumlah sewa.</small>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-black">Catatan Umum Pemeriksaan Pengembalian</label>
                        <textarea name="inspection_notes" rows="2" class="form-control rounded-3" placeholder="Contoh: Seluruh tenda dan panggung kembali dalam kondisi utuh dan kering. Terdapat 2 kursi patah saat acara.">{{ old('inspection_notes', $order->returnInspection?->inspection_notes) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: #E7E2DA !important;">
                    <button type="button" class="btn btn-light rounded-pill px-3 py-1.5" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 py-1.5 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Pemeriksaan Pengembalian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
@if(config('services.google_maps.client_key'))
<script>
window.gm_authFailure = function () {
    console.warn('Google Maps billing / auth failure detected.');
    const el = document.getElementById('adminOrderMap');
    if (!el) return;
    const lat = el.dataset.latitude || '-3.7955';
    const lng = el.dataset.longitude || '114.7712';
    const eventName = el.dataset.eventName || 'Lokasi Acara';
    el.innerHTML = `
        <div class="d-flex flex-column align-items-center justify-content-center text-center p-3 h-100" style="background-color: #FAF8F5; color: #555;">
            <i class="bi bi-geo-alt-fill text-warning fs-3 mb-1"></i>
            <strong class="text-black small mb-1">${eventName}</strong>
            <p class="small text-secondary mb-2" style="font-size: 0.75rem;">
                Google Maps memerlukan Billing Account aktif di Google Cloud Console untuk menampilkan peta inline.
            </p>
            <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm rounded-pill px-3" style="font-size: 0.75rem;">
                <i class="bi bi-box-arrow-up-right me-1"></i> Buka Titik di Google Maps
            </a>
        </div>
    `;
};

window.initAdminOrderMap = function () {
    const mapElement = document.getElementById('adminOrderMap');
    if (!mapElement || !window.google || !window.google.maps) return;

    const lat = Number.parseFloat(mapElement.dataset.latitude);
    const lng = Number.parseFloat(mapElement.dataset.longitude);
    const eventName = mapElement.dataset.eventName || 'Lokasi Pengiriman Acara';
    const address = mapElement.dataset.address || '';

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

    try {
        const map = new google.maps.Map(mapElement, {
            center: { lat: lat, lng: lng },
            zoom: 15,
            mapId: 'DEMO_MAP_ID',
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true,
        });

        const infoContent = `
            <div style="font-family: sans-serif; font-size: 13px; line-height: 1.4; padding: 4px; max-width: 240px;">
                <strong style="color: #191919;">${eventName}</strong>
                ${address ? `<div style="color: #555; font-size: 12px; margin-top: 4px;">${address}</div>` : ''}
                <div style="color: #888; font-size: 11px; margin-top: 4px; font-family: monospace;">${lat.toFixed(5)}, ${lng.toFixed(5)}</div>
            </div>
        `;

        const infoWindow = new google.maps.InfoWindow({
            content: infoContent
        });

        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            const marker = new google.maps.marker.AdvancedMarkerElement({
                position: { lat: lat, lng: lng },
                map: map,
                title: eventName,
            });
            marker.addListener('click', () => {
                infoWindow.open({ anchor: marker, map: map });
            });
            infoWindow.open({ anchor: marker, map: map });
        } else {
            const marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: eventName,
                animation: google.maps.Animation.DROP,
            });
            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });
            infoWindow.open(map, marker);
        }
    } catch (e) {
        console.error('Google Maps init error:', e);
    }
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.client_key') }}&libraries=geometry,marker&callback=initAdminOrderMap&loading=async" async defer></script>
@endif
@endpush
@endsection
