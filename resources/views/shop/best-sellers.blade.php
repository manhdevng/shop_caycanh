@extends('layouts.shop')
@section('content')

@php
    // Giá + dòng mô tả nhỏ của thẻ sản phẩm — copy đúng logic từ shop/index.blade.php
    // (D2, F8): sản phẩm có ≥2 phân loại khác giá -> "Từ X₫" + "N lựa chọn {tên nhóm}";
    // ngược lại dùng base_price như cũ (0 -> "Liên hệ giá" xử lý riêng ở nơi hiển thị).
    $priceLineFor = function ($product) {
        if ($product->hasPriceRange()) {
            return 'Từ ' . number_format($product->variants->min('price'), 0, ',', '.') . '₫';
        }
        if ($product->base_price > 0) {
            return number_format($product->base_price, 0, ',', '.') . '₫';
        }
        return null;
    };

    $specLineFor = function ($product) {
        $count = $product->variants->count();
        if ($count > 0) {
            return $count . ' lựa chọn ' . \Illuminate\Support\Str::lower($product->effective_variant_label);
        }
        return optional($product->categories->first())->name;
    };

    // Danh sách id sản phẩm đã yêu thích của user hiện tại — tính 1 LẦN duy nhất
    // ở đây (không phải trong mỗi lần lặp thẻ sản phẩm) để tránh N+1 query khi
    // vẽ nút trái tim trên từng thẻ sản phẩm bên dưới (giống shop/index.blade.php).
    $wishlistedIds = auth()->check() ? auth()->user()->wishlistedProducts->pluck('id')->all() : [];
@endphp

<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 24px 0;font-size:13px;color:#8A8680;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <a href="{{ route('shop.index') }}" style="color:#8A8680">Trang chủ</a>
    <span>&rsaquo;</span>
    <span style="color:#1C1C1A">Sản phẩm bán chạy</span>
</nav>

<section style="max-width:1400px;margin:0 auto;padding:20px 24px 44px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 16px">Sản phẩm bán chạy</h1>
    <p style="font-size:16px;line-height:1.6;color:#6B6B66;max-width:560px;margin:0 0 14px">Xếp hạng dựa trên số lượng bán thực tế trong 30 ngày gần nhất — những lựa chọn được khách hàng yêu thích nhất tại cửa hàng.</p>
    <p style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#8A8680;margin:0">{{ $products->total() }} sản phẩm</p>
</section>

<section style="max-width:1400px;margin:0 auto;padding:0 24px clamp(64px,8vw,96px)">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:32px 20px" class="grid grid-cols-2 md:grid-cols-4">
        @forelse($products as $item)
            @php $soldCount = $soldCounts[$item->id] ?? null; @endphp
            <div>
                <div style="position:relative">
                    <a href="{{ route('shop.show', $item->id) }}" style="position:relative;display:block;aspect-ratio:1/1">
                        @include('shop.partials.badge', ['product' => $item, 'bestSellerIds' => $bestSellerIds])
                        @unless($item->in_stock)
                            <span style="position:absolute;top:10px;left:10px;background:#6B7280;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;padding:4px 9px;border-radius:3px;z-index:1">Hết hàng</span>
                        @endunless
                        @if($item->main_image)
                            <img src="{{ asset('storage/' . $item->main_image) }}" alt="{{ $item->name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                        @else
                            <div class="placeholder-pattern" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                                <span style="font-family:ui-monospace,Menlo,monospace;font-size:10px;color:#A8A196;text-align:center;padding:0 12px">{{ $item->name }}</span>
                            </div>
                        @endif
                    </a>
                    @include('shop.partials.wishlist-button', ['product' => $item, 'wishlistedIds' => $wishlistedIds])
                </div>
                <p style="font-size:15px;font-weight:500;color:#1C1C1A;margin:14px 0 5px">{{ $item->name }}</p>
                <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;margin:0 0 8px">
                    {{ $specLineFor($item) }}
                    @if($soldCount)
                        <span style="color:#5C2323">&middot; Đã bán {{ $soldCount }}</span>
                    @endif
                </p>
                <p style="font-size:15px;font-weight:600;color:#1C1C1A;margin:0 0 10px">
                    @if($priceLineFor($item))
                        {{ $priceLineFor($item) }}
                    @else
                        <span style="font-size:12px;color:#8A8680;font-weight:400;font-style:italic">Liên hệ giá</span>
                    @endif
                </p>
                @if($item->variants->isNotEmpty())
                    <a href="{{ route('shop.show', $item->id) }}" style="display:block;text-align:center;width:100%;padding:11px 14px;border-radius:999px;background:#FFFFFF;color:#1C1C1A;border:1px solid #1C1C1A;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase">Chọn {{ $item->effective_variant_label }}</a>
                @elseif($item->base_price <= 0)
                    <a href="{{ route('shop.show', $item->id) }}" style="display:block;text-align:center;width:100%;padding:11px 14px;border-radius:999px;background:#FFFFFF;color:#8A8680;border:1px solid #E5E2DC;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase">Liên hệ</a>
                @else
                    <button type="button" onclick="addToCart({{ $item->id }}, this)" style="width:100%;padding:11px 14px;border-radius:999px;background:#FFFFFF;color:#1C1C1A;border:1px solid #1C1C1A;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.05em;text-transform:uppercase;cursor:pointer">Thêm vào giỏ</button>
                @endif
            </div>
        @empty
            <div style="grid-column:1/-1;text-align:center;color:#8A8680;font-size:14px;padding:60px 24px;border:1px dashed #E5E2DC;border-radius:16px">
                <p style="margin:0 0 18px">Chưa có sản phẩm bán chạy nào trong 30 ngày gần đây.</p>
                <a href="{{ route('shop.index') }}" style="display:inline-block;font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;background:#5C2323;color:#FFFFFF;padding:12px 28px;border-radius:999px">Về trang chủ</a>
            </div>
        @endforelse
    </div>

    @if($products->hasPages())
        <div style="margin-top:56px">
            {{ $products->links() }}
        </div>
    @endif
</section>

<script>
    // ==== Bật/tắt yêu thích trực tiếp trên thẻ sản phẩm (không reload) ====
    function toggleWishlist(productId, btn) {
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
                btn.disabled = false;
                const icon = btn.querySelector('.wishlist-heart-icon');
                if (icon) icon.setAttribute('fill', data.liked ? '#5C2323' : 'none');
                btn.setAttribute('aria-label', (data.liked ? 'Bỏ yêu thích' : 'Yêu thích'));
                if (typeof showToast === 'function') {
                    showToast(data.message);
                }
            })
            .catch(() => {
                // Chưa đăng nhập (401) hoặc lỗi khác -> chuyển hướng đăng nhập thay vì im lặng thất bại.
                window.location.href = "{{ route('login') }}";
            });
    }
</script>

@endsection
