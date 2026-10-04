{{--
    Hàng sản phẩm đặt NGAY SAU cảnh "Khu vườn nở theo mùa" — cùng lý do với
    grid-indoor: cảnh sân vườn là ảnh cảm hứng, không phải ảnh của một SKU.

    Lấy theo danh mục "Ngoài trời (Outdoor)" thật (nhóm "Cây cảnh sân vườn &
    ngoài trời" hiện chưa có sản phẩm). Tiêu đề đọc $outdoorNoun do
    ShopController tính từ product_type thật của danh mục: hiện toàn là hoa nên
    hàng tên "Hoa cho sân vườn & ban công" chứ không gọi là cây. Hết hàng thì
    product-row tự ẩn cả khối.
--}}
@php $outdoorNoun = $outdoorNoun ?? 'cây'; @endphp
@include('shop.partials.home.product-row', [
    'rowProducts' => $outdoorProducts,
    'rowKicker' => \Illuminate\Support\Str::ucfirst($outdoorNoun) . ' ngoài trời',
    'rowTitle' => \Illuminate\Support\Str::ucfirst($outdoorNoun) . ' cho sân vườn & ban công',
    'rowNote' => 'Ưa nắng, bền với nắng mưa, cho khoảng sân đổi màu theo mùa.',
    'rowHref' => $outdoorCategory
        ? \App\Http\Controllers\ShopController::catalogUrl(['categories' => [$outdoorCategory->id]])
        : \App\Http\Controllers\ShopController::catalogUrl(),
    'rowLinkText' => 'Xem tất cả ' . $outdoorNoun . ' ngoài trời',
    'rowBg' => '#F7F4EF',
])
