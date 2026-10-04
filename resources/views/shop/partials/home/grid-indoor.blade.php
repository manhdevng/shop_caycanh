{{--
    Hàng cây mua được đặt NGAY SAU cảnh "Một góc xanh cho mỗi căn phòng".

    Lý do tồn tại: cảnh phòng khách là ảnh cảm hứng, không chụp một SKU nào
    đang bán — nên tự nó chỉ tạo mong muốn rồi bỏ khách ở đó. Hàng này biến
    mong muốn đó thành thứ bấm mua được, không bắt khách quay lại tìm từ đầu.

    $indoorProducts lấy từ danh mục "Trong nhà (Indoor)" thật và đã loại các
    cây đã khoe ở hero/lưới đầu (xem ShopController), nên không lặp lại.
--}}
@include('shop.partials.home.product-row', [
    'rowProducts' => $indoorProducts,
    'rowKicker' => 'Cây trong nhà',
    'rowTitle' => 'Cây hợp với trong nhà',
    'rowNote' => 'Ưa bóng râm, ít cần chăm, sống tốt trong phòng máy lạnh.',
    'rowHref' => \App\Http\Controllers\ShopController::catalogUrl($indoorCategory ? ['categories' => [$indoorCategory->id]] : ['type' => 'plant']),
    'rowLinkText' => 'Xem tất cả cây trong nhà',
    'rowBg' => '#FFFFFF',
])
