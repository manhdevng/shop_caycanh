@extends('layouts.shop')
@section('content')

@php
    // Cùng công thức hiển thị giá với shop/index.blade.php ($priceLineFor) —
    // không tự bịa cách tính giá khác cho trang yêu thích.
    $priceLineFor = function ($product) {
        if ($product->hasPriceRange()) {
            return 'Từ ' . number_format($product->variants->min('price'), 0, ',', '.') . '₫';
        }
        if ($product->base_price > 0) {
            return number_format($product->base_price, 0, ',', '.') . '₫';
        }
        return null;
    };
@endphp

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">Sản phẩm yêu thích</span>
</nav>

<section style="max-width:1400px;margin:0 auto;padding:20px 24px 44px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 16px">Sản phẩm yêu thích</h1>
    <p style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0">{{ $products->total() }} sản phẩm</p>
</section>

<section style="max-width:1400px;margin:0 auto;padding:0 24px clamp(64px,8vw,96px)">
    <div id="wishlist-grid" style="display:{{ $products->count() ? 'grid' : 'none' }};grid-template-columns:repeat(4,1fr);gap:32px 20px" class="grid grid-cols-2 md:grid-cols-4">
        @foreach($products as $item)
            <div id="wishlist-item-{{ $item->id }}">
                <div style="position:relative">
                    <a href="{{ route('shop.show', $item->id) }}" style="position:relative;display:block;aspect-ratio:1/1">
                        @if($item->main_image)
                            <img src="{{ asset('storage/' . $item->main_image) }}" alt="{{ $item->name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                        @else
                            <div class="placeholder-pattern" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                                <span style="font-family:ui-monospace,Menlo,monospace;font-size:10px;color:#A8A196;text-align:center;padding:0 12px">{{ $item->name }}</span>
                            </div>
                        @endif
                    </a>
                    <button type="button" class="wishlist-toggle-btn" data-product-id="{{ $item->id }}" onclick="toggleWishlistFromList({{ $item->id }}, this)" aria-label="Bỏ yêu thích &quot;{{ $item->name }}&quot;" style="position:absolute;bottom:10px;right:10px;width:34px;height:34px;border-radius:999px;background:#FFFFFF;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.12);z-index:2">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="#5C2323" stroke="#5C2323" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.2l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8Z"></path></svg>
                    </button>
                </div>
                <a href="{{ route('shop.show', $item->id) }}">
                    <p style="font-size:15px;font-weight:500;color:#1C1C1A;margin:14px 0 5px">{{ $item->name }}</p>
                </a>
                <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;margin:0 0 8px">{{ optional($item->categories->first())->name ?? '—' }}</p>
                <p style="font-size:15px;font-weight:600;color:#1C1C1A;margin:0 0 4px">
                    @if($priceLineFor($item))
                        {{ $priceLineFor($item) }}
                    @else
                        <span style="font-size:12px;color:#8A8680;font-weight:400;font-style:italic">Liên hệ giá</span>
                    @endif
                </p>
                @if(optional($item->pivot)->created_at)
                    <p style="font-size:11px;color:#8A8680;margin:0">Đã thích lúc {{ \Illuminate\Support\Carbon::parse($item->pivot->created_at)->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <div id="wishlist-empty" style="display:{{ $products->count() ? 'none' : 'block' }};text-align:center;padding:80px 0">
        <div style="font-size:40px;margin-bottom:16px">🤍</div>
        <p style="font-size:15px;color:#6B6B66;margin:0 0 24px">Bạn chưa có sản phẩm yêu thích nào.</p>
        <a href="{{ route('shop.index') }}" style="display:inline-block;padding:14px 28px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Khám phá sản phẩm</a>
    </div>

    @if($products->hasPages())
        <div id="wishlist-pagination" style="margin-top:40px">
            {{ $products->links() }}
        </div>
    @endif
</section>

@push('scripts')
<script>
    // ==== Bỏ yêu thích trực tiếp trên trang danh sách (không reload) ====
    function toggleWishlistFromList(productId, btn) {
        btn.disabled = true;

        const urlTemplate = "{{ route('wishlist.toggle', ['product' => '__ID__']) }}";

        fetch(urlTemplate.replace('__ID__', productId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
            .then(res => {
                if (!res.ok) throw new Error('request-failed');
                return res.json();
            })
            .then(data => {
                const card = document.getElementById(`wishlist-item-${productId}`);
                if (card) card.remove();

                if (typeof showToast === 'function') {
                    showToast(data.message);
                }

                const grid = document.getElementById('wishlist-grid');
                if (grid && grid.children.length === 0) {
                    grid.style.display = 'none';

                    const pagination = document.getElementById('wishlist-pagination');
                    if (pagination) pagination.style.display = 'none';

                    const empty = document.getElementById('wishlist-empty');
                    if (empty) empty.style.display = 'block';
                }
            })
            .catch(() => {
                // Hết phiên đăng nhập hoặc lỗi khác -> chuyển hướng đăng nhập thay vì im lặng thất bại.
                window.location.href = "{{ route('login') }}";
            });
    }
</script>
@endpush
@endsection
