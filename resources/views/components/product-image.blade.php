@props(['inventory', 'display' => 'catalog'])

@php
    $imageUrl = null;
    $alt = $inventory->name;

    if ($inventory->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($inventory->image)) {
        $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($inventory->image);
    } elseif (file_exists(public_path('assets/images/products/' . $inventory->slug . '.jpg'))) {
        $imageUrl = asset('assets/images/products/' . $inventory->slug . '.jpg');
    } elseif (file_exists(public_path('assets/images/products/' . $inventory->slug . '.png'))) {
        $imageUrl = asset('assets/images/products/' . $inventory->slug . '.png');
    } elseif ($inventory->category && file_exists(public_path('assets/images/categories/' . $inventory->category->slug . '.jpg'))) {
        $imageUrl = asset('assets/images/categories/' . $inventory->category->slug . '.jpg');
    } elseif ($inventory->category && in_array($inventory->category->slug, ['meja', 'kursi']) && file_exists(public_path('assets/images/categories/kursi-meja.jpg'))) {
        $imageUrl = asset('assets/images/categories/kursi-meja.jpg');
    } elseif (file_exists(public_path('assets/images/categories/tenda.jpg'))) {
        $imageUrl = asset('assets/images/categories/tenda.jpg');
    }
@endphp

@if($imageUrl)
    <img
        class="product-image {{ $display === 'detail' ? 'product-image--detail' : '' }}"
        src="{{ $imageUrl }}"
        alt="{{ $alt }}"
        width="{{ $display === 'detail' ? 800 : 400 }}"
        height="{{ $display === 'detail' ? 600 : 300 }}"
        @if($display !== 'detail') loading="lazy" decoding="async" @endif
    >
@else
    <div class="image-missing {{ $display === 'detail' ? 'image-missing--detail' : '' }}" role="img" aria-label="Foto {{ $inventory->name }} belum tersedia">
        <span>Foto produk belum tersedia</span>
    </div>
@endif
