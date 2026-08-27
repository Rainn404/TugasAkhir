@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Laporan Inventaris Perlengkapan</h4>
        <p class="text-secondary small mb-0">Rekapitulasi total stok fisik, alokasi penggunaan, dan sisa unit tersedia.</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-2 d-print-none">
        <i class="bi bi-printer me-1"></i> Cetak Laporan
    </button>
</div>

<!-- Header Cetak Khusus Print -->
<div class="d-none d-print-block text-center mb-4">
    <h3 class="fw-bold text-black mb-0">ARSY TENDA</h3>
    <p class="small text-secondary mb-1">Laporan Status dan Stok Inventaris Perlengkapan</p>
    <small class="text-secondary">Dicetak pada: {{ date('d/m/Y H:i') }}</small>
    <hr>
</div>

<!-- Report Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">Nama Perlengkapan</th>
                    <th class="py-3 px-3">Kategori</th>
                    <th class="py-3 px-3 text-end">Harga Dasar</th>
                    <th class="py-3 px-3 text-center">Total Stok Fisik</th>
                    <th class="py-3 px-3 text-center">Sedang Digunakan</th>
                    <th class="py-3 px-3 text-center">Stok Tersedia</th>
                    <th class="py-3 px-4 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventories as $inv)
                    @php
                        $usedStock = $inv->orderDetails->sum('quantity');
                        $availableStock = max(0, $inv->stock - $usedStock);
                    @endphp
                    <tr>
                        <td class="py-3 px-4">
                            <strong class="text-black d-block">{{ $inv->name }}</strong>
                            <small class="text-secondary">{{ $inv->variants->count() }} Varian Terdaftar</small>
                        </td>
                        <td class="py-3 px-3">{{ $inv->category->name ?? '-' }}</td>
                        <td class="py-3 px-3 text-end fw-semibold">
                            Rp {{ number_format($inv->price, 0, ',', '.') }} / {{ $inv->unit }}
                        </td>
                        <td class="py-3 px-3 text-center fw-bold">
                            {{ $inv->stock }} {{ $inv->unit }}
                        </td>
                        <td class="py-3 px-3 text-center text-warning fw-bold">
                            {{ $usedStock }} {{ $inv->unit }}
                        </td>
                        <td class="py-3 px-3 text-center text-success fw-bold">
                            {{ $availableStock }} {{ $inv->unit }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="badge {{ $inv->status === 'available' ? 'bg-success' : 'bg-secondary' }}">
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
