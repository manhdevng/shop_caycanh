{{--
    Hàng cây mua được đặt NGAY SAU cảnh "Khu vườn nở theo mùa" — cùng lý do
    với grid-indoor: cảnh sân vườn là ảnh cảm hứng, không phải ảnh của một SKU.

    Lưu ý dữ liệu: nhóm danh mục "Cây cảnh sân vườn & ngoài trời" hiện KHÔNG có
    sản phẩm nào, nên link nhóm đó sẽ ra trang trống. Hàng này (và nút "Xem tất
    cả" của cảnh sân vườn) dùng danh mục "Ngoài trời (Outdoor)" — nơi thực sự
    có hàng: hồng leo, hồng cổ Sapa, dâm bụt. Khi cửa hàng nhập cây sân vườn
    vào đúng nhóm kia thì đổi lại một dòng ở ShopController là xong.
--}}
@include('shop.partials.home.product-row', [
    'rowProducts' => $outdoorProducts,
    'rowKicker' => 'Cây ngoài trời',
    'rowTitle' => 'Cây cho sân vườn & ban công',
    'rowNote' => 'Ưa nắng, bền với nắng mưa, cho khoảng sân đổi màu theo mùa.',
    'rowHref' => $outdoorCategory ? route('shop.index', ['categories' => [$outdoorCategory->id]]) : route('shop.index', ['type' => 'plant']),
    'rowLinkText' => 'Xem tất cả cây ngoài trời',
    'rowBg' => '#F7F4EF',
])
