@extends('layouts.shop')

@section('title', 'Lịch sử của tôi · Cây Cảnh Shop')

@section('content')

@php
    // Cùng công thức hiển thị giá với shop/index.blade.php và wishlist/index.blade.php
    // ($priceLineFor) — không tự bịa cách tính giá khác cho trang lịch sử.
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
    <span style="color:#1C1C1A">Lịch sử của tôi</span>
</nav>

<section style="max-width:1400px;margin:0 auto;padding:20px 24px 16px">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 8px">Lịch sử của tôi</h1>
    <p style="font-size:14px;color:#6B6B66;margin:0">Sản phẩm bạn đã xem và đã mua gần đây trên Cây Cảnh Shop.</p>
</section>

{{-- ==== Khu vực: Sản phẩm đã xem gần đây ==== --}}
<section style="max-width:1400px;margin:0 auto;padding:24px 24px 0">
    <h2 style="font-family:'Anton',sans-serif;font-size:clamp(20px,2.4vw,26px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 20px">Sản phẩm đã xem gần đây</h2>

    @forelse($recentlyViewed as $item)
        @if($loop->first)
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:32px 20px" class="grid grid-cols-2 md:grid-cols-4">
        @endif
                <div>
                    <a href="{{ route('shop.show', $item->id) }}" style="position:relative;display:block;aspect-ratio:1/1">
                        @if($item->main_image)
                            <img src="{{ asset('storage/' . $item->main_image) }}" alt="{{ $item->name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                        @else
                            <div class="placeholder-pattern" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                                <span style="font-family:ui-monospace,Menlo,monospace;font-size:10px;color:#A8A196;text-align:center;padding:0 12px">{{ $item->name }}</span>
                            </div>
                        @endif
                    </a>
                    <a href="{{ route('shop.show', $item->id) }}">
                        <p style="font-size:15px;font-weight:500;color:#1C1C1A;margin:14px 0 5px">{{ $item->name }}</p>
                    </a>
                    <p style="font-size:15px;font-weight:600;color:#1C1C1A;margin:0 0 4px">
                        @if($priceLineFor($item))
                            {{ $priceLineFor($item) }}
                        @else
                            <span style="font-size:12px;color:#8A8680;font-weight:400;font-style:italic">Liên hệ giá</span>
                        @endif
                    </p>
                </div>
        @if($loop->last)
            </div>
        @endif
    @empty
        <div style="text-align:center;padding:64px 0;border:1px dashed #E5E2DC;border-radius:16px">
            <p style="font-size:15px;color:#6B6B66;margin:0 0 20px">Bạn chưa xem sản phẩm nào gần đây.</p>
            <a href="{{ route('shop.index') }}" style="display:inline-block;padding:12px 26px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Khám phá sản phẩm</a>
        </div>
    @endforelse
</section>

{{-- ==== Khu vực: Sản phẩm đã mua ==== --}}
<section style="max-width:1400px;margin:0 auto;padding:56px 24px clamp(64px,8vw,96px)">
    <h2 style="font-family:'Anton',sans-serif;font-size:clamp(20px,2.4vw,26px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 20px">Sản phẩm đã mua</h2>

    @forelse($purchasedProducts as $item)
        @if($loop->first)
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:32px 20px" class="grid grid-cols-2 md:grid-cols-4">
        @endif
                <div>
                    <a href="{{ route('shop.show', $item->id) }}" style="position:relative;display:block;aspect-ratio:1/1">
                        @if($item->main_image)
                            <img src="{{ asset('storage/' . $item->main_image) }}" alt="{{ $item->name }}" style="width:100%;height:100%;object-fit:cover;display:block">
                        @else
                            <div class="placeholder-pattern" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                                <span style="font-family:ui-monospace,Menlo,monospace;font-size:10px;color:#A8A196;text-align:center;padding:0 12px">{{ $item->name }}</span>
                            </div>
                        @endif
                    </a>
                    <a href="{{ route('shop.show', $item->id) }}">
                        <p style="font-size:15px;font-weight:500;color:#1C1C1A;margin:14px 0 5px">{{ $item->name }}</p>
                    </a>
                    <p style="font-size:15px;font-weight:600;color:#1C1C1A;margin:0 0 4px">
                        @if($priceLineFor($item))
                            {{ $priceLineFor($item) }}
                        @else
                            <span style="font-size:12px;color:#8A8680;font-weight:400;font-style:italic">Liên hệ giá</span>
                        @endif
                    </p>
                </div>
        @if($loop->last)
            </div>
        @endif
    @empty
        <div style="text-align:center;padding:64px 0;border:1px dashed #E5E2DC;border-radius:16px">
            <p style="font-size:15px;color:#6B6B66;margin:0 0 20px">Bạn chưa mua sản phẩm nào.</p>
            <a href="{{ route('shop.index') }}" style="display:inline-block;padding:12px 26px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase">Khám phá sản phẩm</a>
        </div>
    @endforelse
</section>

@endsection
