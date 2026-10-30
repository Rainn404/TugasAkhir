@extends('layouts.admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 page-editorial-header">
    <div>
        <span class="page-eyebrow">Audit Logistik</span>
        <h1 class="page-headline mb-1 font-display">Laporan Inventaris Perlengkapan</h1>
        <p class="page-subtext">Rekapitulasi total stok fisik gudang, alokasi penggunaan acara, dan sisa unit tersedia.</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1.5 d-print-none fw-semibold">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- Header Cetak Khusus Print -->
<div class="d-none d-print-block text-center mb-4">
    <h3 class="fw-bold text-black mb-0">ARSY TENDA</h3>
    <p class="small text-secondary mb-1">Laporan Status dan Stok Inventaris Perlengkapan</p>
    <small class="text-secondary">Dicetak pada: {{ date('d/m/Y H:i') }}</small>
    <hr>
</div>

<!-- Report Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>Nama Perlengkapan</th>
                    <th>Kategori</th>
                    <th class="text-end">Harga Dasar</th>
                    <th class="text-center">Total Stok Fisik</th>
                    <th class="text-center">Sedang Digunakan</th>
                    <th class="text-center">Stok Tersedia</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventories as $inv)
                    @php
                        $usedStock = $inv->orderDetails->sum('quantity');
                        $availableStock = max(0, $inv->stock - $usedStock);
                    @endphp
                    <tr>
                        <td>
                            <strong class="text-black d-block small">{{ $inv->name }}</strong>
                            <small class="text-secondary">{{ $inv->variants->count() }} Varian Terdaftar</small>
                        </td>
                        <td class="small">{{ $inv->category->name ?? '-' }}</td>
                        <td class="text-end fw-semibold small text-black">
                            Rp {{ number_format($inv->price, 0, ',', '.') }} / {{ $inv->unit }}
                        </td>
                        <td class="text-center fw-bold small text-black">
                            {{ $inv->stock }} {{ $inv->unit }}
                        </td>
                        <td class="text-center small fw-bold" style="color: #B45309;">
                            {{ $usedStock }} {{ $inv->unit }}
                        </td>
                        <td class="text-center small fw-bold" style="color: #15803D;">
                            {{ $availableStock }} {{ $inv->unit }}
                        </td>
                        <td class="text-center">
                            <span class="badge-status {{ $inv->status === 'available' ? 'badge-status--success' : 'badge-status--neutral' }}" style="font-size: 0.72rem;">
                                {{ strtoupper($inv->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            Tidak ada data inventaris.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
