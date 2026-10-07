{{--
    Hàng "Cây cảnh văn phòng" — đặt ngay sau hàng cây trong nhà.

    $officeProducts lấy từ danh mục "Cây cảnh văn phòng" (kể cả danh mục con),
    đã loại các cây đứng ở lưới đầu; hàng trong nhà phía trên lại loại các cây
    văn phòng này, nên hai hàng không lặp nhau. Hết hàng thì product-row tự ẩn.
--}}
@include('shop.partials.home.product-row', [
    'rowProducts' => $officeProducts ?? collect(),
    'rowKicker' => 'Cây văn phòng',
    'rowTitle' => 'Cây xanh cho bàn làm việc & văn phòng',
    'rowNote' => 'Chịu máy lạnh, ít cần chăm, giúp góc làm việc dễ chịu và hợp phong thủy.',
    'rowHref' => ($officeCategory ?? null)
        ? \App\Http\Controllers\ShopController::catalogUrl(['categories' => [$officeCategory->id]])
        : \App\Http\Controllers\ShopController::catalogUrl(['type' => 'plant']),
    'rowLinkText' => 'Xem tất cả cây văn phòng',
    'rowBg' => '#F7F4EF',
])
