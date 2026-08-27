@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-black mb-1">Data Pelanggan</h4>
        <p class="text-secondary small mb-0">Daftar akun pelanggan terdaftar di sistem Arsy Tenda.</p>
    </div>
</div>

<!-- Customers Table -->
<div class="card border rounded-4 bg-white overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4">Nama Pelanggan</th>
                    <th class="py-3 px-3">Email</th>
                    <th class="py-3 px-3">Nomor Telepon</th>
                    <th class="py-3 px-3">Alamat</th>
                    <th class="py-3 px-3 text-center">Total Pesanan</th>
                    <th class="py-3 px-4 text-center">Tanggal Daftar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $cust)
                    <tr>
                        <td class="py-3 px-4">
                            <strong class="text-black small d-block">{{ $cust->name }}</strong>
                        </td>
                        <td class="py-3 px-3 small">{{ $cust->email }}</td>
                        <td class="py-3 px-3 small font-monospace">{{ $cust->phone ?? '-' }}</td>
                        <td class="py-3 px-3 small text-secondary" style="max-width: 250px;">
                            {{ $cust->address ?? '-' }}
                        </td>
                        <td class="py-3 px-3 text-center">
                            <span class="badge bg-dark small">{{ $cust->orders_count }} Pesanan</span>
                        </td>
                        <td class="py-3 px-4 text-center small text-secondary">
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
        <div class="p-3 border-top">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
