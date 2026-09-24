{{--
    Khối E — Sản phẩm bán chạy (thay thế banner-bestsellers ở cùng một beat).
    CHỈ dùng được khi include từ nhánh $showFeatured của shop/index.blade.php,
    vì phụ thuộc các biến do view cha đó định nghĩa/truyền xuống:
    - $homeBestSellers: Collection tối đa 4 Product (đã with categories/variants,
      sắp theo bán chạy nhất trước), có thể rỗng.
    - $homeSoldCounts: mảng product_id => tổng số lượng đã bán trong 30 ngày,
      có thể rỗng hoặc thiếu key cho một sản phẩm.
    - $priceLineFor, $specLineFor, $wishlistedIds (closure/mảng khai báo trong
      @php ở đầu shop/index.blade.php).

    Có dữ liệu -> vẽ lưới 4 thẻ sản phẩm bán chạy thật; thẻ "mọc" từ đáy lên
    qua [data-grow] (public/js/home-motion.js), tấm nền đứng yên.
    Không có dữ liệu -> rơi về banner-bestsellers.blade.php (đã có sẵn từ trước),
    banner đó cũng đứng yên, không quét ngang.
--}}
@if($homeBestSellers->isNotEmpty())
<section id="ban-chay">
    <div style="background:#F7F4EF">
        <div style="max-width:1400px;margin:0 auto;padding:clamp(32px,4vw,56px) 24px clamp(56px,8vw,100px)">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:28px;gap:16px;flex-wrap:wrap">
                <div>
                    <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#5C2323;margin:0 0 10px">Được yêu thích nhất</p>
                    <h2 style="font-family:'Anton',sans-serif;font-size:clamp(22px,3vw,30px);letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0">Sản phẩm bán chạy</h2>
                    <p style="font-size:14px;color:#6B6B66;margin:8px 0 0">Mua nhiều nhất trong 30 ngày qua</p>
                </div>
                <a href="{{ route('shop.bestSellers') }}" style="font-family:'Space Mono',monospace;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66">Xem tất cả &rarr;</a>
            </div>
            <div data-grow style="display:grid;grid-template-columns:repeat(4,1fr);gap:32px 24px" class="grid grid-cols-2 md:grid-cols-4">
                @foreach($homeBestSellers as $item)
                    <div>
                        <div style="position:relative">
                            <a href="{{ route('shop.show', $item->id) }}" class="sc-leaf" style="position:relative;display:block;aspect-ratio:1/1">
                                {{-- Bản đồ bốn góc của thẻ ảnh (đừng đặt thêm nhãn nào chồng lên
                                     các vị trí đã có chủ ở đây):
                                     - trên-trái  : nhãn thứ hạng #1..#4 (riêng của khối này)
                                     - trên-phải  : badge dùng chung (Mới / Số lượng có hạn / Quà tặng)
                                     - dưới-trái  : nhãn "Hết hàng" (chỉ hiện khi hết hàng)
                                     - dưới-phải  : nút yêu thích (shop.partials.wishlist-button) --}}
                                <span style="position:absolute;top:10px;left:10px;background:#1C1C1A;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.04em;padding:4px 9px;border-radius:999px;z-index:1">#{{ $loop->iteration }}</span>
                                @include('shop.partials.badge', ['product' => $item, 'bestSellerIds' => []])
                                @unless($item->in_stock)
                                    <span style="position:absolute;bottom:10px;left:10px;background:#6B7280;color:#FFFFFF;font-family:'Space Mono',monospace;font-size:10px;letter-spacing:0.04em;text-transform:uppercase;padding:4px 9px;border-radius:3px;z-index:1">Hết hàng</span>
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
                        <p style="font-size:15px;font-weight:500;color:#1C1C1A;margin:14px 0 6px">{{ $item->name }}</p>
                        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;color:#6B6B66;margin:0 0 8px">{{ $specLineFor($item) }}</p>
                        @if(($homeSoldCounts[$item->id] ?? 0) > 0)
                            <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.06em;color:#5C2323;margin:0 0 8px">Đã bán {{ $homeSoldCounts[$item->id] }}</p>
                        @endif
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
        </div>
    </div>
</section>
@else
    @include('shop.partials.home.banner-bestsellers')
@endif
