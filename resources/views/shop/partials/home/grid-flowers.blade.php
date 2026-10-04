{{--
    Hàng hoa. Trước đây khối này luôn vẽ tiêu đề "Hoa mới nhập" kể cả khi
    $newestFlowers rỗng, để lại một tiêu đề treo lơ lửng không có hàng nào bên
    dưới; và khi kho hết hoa thì còn kèm dòng nhắc nhập hàng vốn chỉ dành cho
    admin. Nay dùng chung product-row: rỗng thì cả khối tự ẩn.

    $newestFlowers: ShopController ưu tiên hoa chưa đứng ở hàng ngoài trời
    phía trên, rồi mới bù bằng hoa ngoài trời nếu kho ít — tối đa 4, không lặp
    trong hàng.
--}}
@include('shop.partials.home.product-row', [
    'rowProducts' => $newestFlowers,
    'rowKicker' => 'Hoa',
    'rowTitle' => 'Hoa mới nhập',
    'rowHref' => \App\Http\Controllers\ShopController::catalogUrl(['type' => 'flower']),
    'rowLinkText' => 'Xem tất cả hoa',
    'rowBg' => '#FFFFFF',
])
