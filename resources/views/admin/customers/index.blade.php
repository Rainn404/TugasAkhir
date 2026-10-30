@extends('layouts.admin')

@section('content')
<div class="page-editorial-header mb-4">
    <span class="page-eyebrow">Direktori Pengguna</span>
    <h1 class="page-headline mb-1 font-display">Data Pelanggan</h1>
    <p class="page-subtext">Daftar akun pelanggan terdaftar dan riwayat jumlah pesanan persewaan mereka.</p>
</div>

<!-- Customers Table -->
<div class="editorial-card overflow-hidden">
    <div class="table-responsive">
        <table class="editorial-table align-middle">
            <thead>
                <tr>
                    <th>Nama Pelanggan</th>
                    <th>Email</th>
                    <th>Nomor Telepon</th>
                    <th>Alamat</th>
                    <th class="text-center">Total Pesanan</th>
                    <th class="text-center">Tanggal Daftar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $cust)
                    <tr>
                        <td>
                            <strong class="text-black small d-block">{{ $cust->name }}</strong>
                        </td>
                        <td class="small">{{ $cust->email }}</td>
                        <td class="small font-monospace text-secondary">{{ $cust->phone ?? '-' }}</td>
                        <td class="small text-secondary" style="max-width: 250px;">
                            {{ $cust->address ?? '-' }}
                        </td>
                        <td class="text-center">
                            <span class="badge-status badge-status--neutral" style="font-size: 0.72rem;">{{ $cust->orders_count }} Pesanan</span>
                        </td>
                        <td class="text-center small text-secondary">
                            {{ $cust->created_at->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-5">
                            <i class="bi bi-people fs-2 d-block mb-1 opacity-50"></i>
                            Belum ada pelanggan terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
        <div class="p-3 border-top" style="border-color: #E7E2DA !important;">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
