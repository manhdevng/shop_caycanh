{{--
    Khối C — Lưới mua sắm ĐẦU TIÊN, ngay sau dải chọn nhanh.

    Đây là điểm mà khách phải thấy hàng thật + giá trong vòng ~1,5 màn hình
    cuộn. $homeFeatured do ShopController dựng: có số liệu bán chạy thì dùng
    bán chạy, chưa có thì rơi về cây mới về — nhưng LUÔN là một lưới hàng mua
    được, không bao giờ rơi về banner như bản cũ (banner không mua được gì).

    id="luoi-san-pham" giữ nguyên tên neo cũ để các link #luoi-san-pham sẵn có
    trong trang vẫn nhảy đúng chỗ, chỉ khác là giờ nó trỏ tới lưới đầu tiên.
--}}
@include('shop.partials.home.product-row', [
    'rowId' => 'luoi-san-pham',
    'rowProducts' => $homeFeatured,
    'rowKicker' => $homeFeaturedIsBestSeller ? 'Được yêu thích nhất' : 'Mới về vườn',
    'rowTitle' => $homeFeaturedIsBestSeller ? 'Cây bán chạy' : 'Cây nổi bật',
    'rowNote' => $homeFeaturedIsBestSeller ? 'Mua nhiều nhất trong 30 ngày qua' : null,
    'rowHref' => $homeFeaturedIsBestSeller ? route('shop.bestSellers') : route('shop.index', ['type' => 'plant']),
    'rowLinkText' => $homeFeaturedIsBestSeller ? 'Xem tất cả bán chạy' : 'Xem tất cả cây cảnh',
    'rowBg' => '#F7F4EF',
    'rowRanked' => $homeFeaturedIsBestSeller,
    'rowSoldCounts' => $homeSoldCounts,
])
