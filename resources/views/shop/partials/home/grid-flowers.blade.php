{{--
    Hàng hoa. Trước đây khối này luôn vẽ tiêu đề "Hoa mới nhập" kể cả khi
    $newestFlowers rỗng, để lại một tiêu đề treo lơ lửng không có hàng nào bên
    dưới; và khi kho hết hoa thì còn kèm dòng nhắc nhập hàng vốn chỉ dành cho
    admin. Nay dùng chung product-row: rỗng thì cả khối tự ẩn.

    $newestFlowers đã được ShopController loại những bông đã đứng ở hàng "cây
    ngoài trời" ngay phía trên, nên hai hàng không kể lại cùng một thứ.
--}}
@include('shop.partials.home.product-row', [
    'rowProducts' => $newestFlowers,
    'rowKicker' => 'Hoa',
    'rowTitle' => 'Hoa mới nhập',
    'rowHref' => route('shop.index', ['type' => 'flower', 'sort' => 'featured']),
    'rowLinkText' => 'Xem tất cả hoa',
    'rowBg' => '#FFFFFF',
])
