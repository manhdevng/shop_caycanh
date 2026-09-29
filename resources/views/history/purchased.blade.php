@extends('layouts.shop')

@section('title', 'Sản phẩm đã mua · Cây Cảnh Shop')
@section('content')
<nav aria-label="breadcrumb" style="max-width:1400px;margin:0 auto;padding:18px 20px 0;font-size:13px;color:#8A8680"><a href="{{ route('shop.index') }}">Trang chủ</a> <span aria-hidden="true">›</span> <span style="color:#1C1C1A">Sản phẩm đã mua</span></nav>
<section style="max-width:1200px;margin:0 auto;padding:24px 20px 80px;min-width:0">
    <h1 style="font-family:'Anton',sans-serif;font-size:clamp(32px,5vw,54px);line-height:1.1;text-transform:uppercase;margin:0 0 8px">Sản phẩm đã mua</h1>
    <p style="font-size:14px;color:#8A8680;margin:0 0 28px">Những sản phẩm bạn đã chọn cho không gian xanh của mình.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,220px),1fr));gap:20px">
        @forelse($purchased as $entry)
            @php $product = data_get($entry, 'product'); $lastOrder = data_get($entry, 'last_order'); @endphp
            @if($product)
                <article style="border:1px solid #E5E2DC;border-radius:16px;overflow:hidden;min-width:0">
                    <a href="{{ route('shop.show', $product) }}" style="display:block;aspect-ratio:1;background:#F7F5F0;overflow:hidden">
                        @if($product->main_image)<img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}" style="width:100%;height:100%;object-fit:cover">@else<span class="placeholder-pattern" style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:#8A8680"><i data-lucide="sprout" style="width:32px;height:32px"></i></span>@endif
                    </a>
                    <div style="padding:16px;min-width:0">
                        <a href="{{ route('shop.show', $product) }}" style="font-size:15px;font-weight:600;overflow-wrap:anywhere">{{ $product->name }}</a>
                        <p style="font-family:'Space Mono',monospace;font-size:10px;color:#8A8680;margin:10px 0 3px">Mua lần cuối {{ data_get($entry, 'last_purchased_at')?->format('d/m/Y') }}</p>
                        <p style="font-size:12px;color:#4A6B1F;margin:0 0 14px">Đã mua {{ data_get($entry, 'times', 1) }} lần</p>
                        <div style="display:flex;gap:7px;flex-wrap:wrap">
                            <a href="{{ route('shop.show', $product) }}" style="padding:8px 12px;background:#5C2323;color:#FFFFFF;border-radius:999px;font-size:11px">Mua lại</a>
                            @if(data_get($entry, 'can_review'))<a href="{{ route('shop.show', $product) }}" style="padding:8px 12px;border:1px solid #E5E2DC;border-radius:999px;font-size:11px">Đánh giá</a>@endif
                            @if($lastOrder)<a href="{{ route('orders.show', $lastOrder) }}" style="padding:8px 12px;border:1px solid #E5E2DC;border-radius:999px;font-size:11px">Xem đơn</a>@endif
                        </div>
                    </div>
                </article>
            @endif
        @empty
            <div style="grid-column:1/-1;text-align:center;border:1px dashed #E5E2DC;border-radius:16px;padding:64px 20px"><i data-lucide="shopping-bag" style="width:36px;height:36px;color:#8A8680;margin:auto"></i><p style="color:#8A8680">Bạn chưa mua sản phẩm nào.</p><a href="{{ route('shop.index') }}" style="display:inline-block;padding:12px 20px;border-radius:999px;background:#5C2323;color:#FFFFFF;font-size:12px">Khám phá sản phẩm</a></div>
        @endforelse
    </div>
</section>
@endsection
