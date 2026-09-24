@if($newestPlants->isNotEmpty())
<section id="luoi-san-pham" data-sc-act="flow" style="max-width:1400px;margin:0 auto;padding:clamp(56px,8vw,100px) 24px 0">
    <div data-sc-in style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:28px;gap:16px;flex-wrap:wrap">
        <h2 style="font-family:'Anton',sans-serif;font-size:clamp(22px,3vw,30px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0">Cây cảnh mới nhập</h2>
        <a href="{{ route('shop.index', ['type' => 'plant', 'sort' => 'featured']) }}" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66">Xem tất cả &rarr;</a>
    </div>
    <div data-sc-in data-sc-stagger="60" style="display:grid;grid-template-columns:repeat(4,1fr);gap:32px 24px" class="grid grid-cols-2 md:grid-cols-4">
        @foreach($newestPlants as $item)
            <div>
                <div style="position:relative">
                    <a href="{{ route('shop.show', $item->id) }}" class="sc-leaf" style="position:relative;display:block;aspect-ratio:1/1">
                        @include('shop.partials.badge', ['product' => $item, 'bestSellerIds' => $bestSellerIds])
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
                <p style="font-size:15px;font-weight:500;color:#1C1C1A;margin:14px 0 6px">{{ $item->name }}</p>
                <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;margin:0 0 8px">{{ $specLineFor($item) }}</p>
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
        @endforeach
    </div>
</section>
@endif
