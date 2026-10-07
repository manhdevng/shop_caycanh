<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Migration DỮ LIỆU: thêm 10 cây vào danh mục "Cây cảnh văn phòng".
     *
     * Ảnh nằm sẵn trong git ở storage/app/public/products/cay-van-phong/
     * (giống các ảnh sản phẩm khác) để không bị mất khi Render deploy lại.
     *
     * - Gán danh mục "Cây cảnh văn phòng" + thuộc tính lọc (ánh sáng, nước,
     *   không gian) nếu tìm thấy theo tên; không thấy thì bỏ qua, không lỗi.
     * - Gán hành phong thủy (product_elements) theo màu chủ đạo của lá, lưu
     *   kèm feng_shui_colors để form admin hiện đúng các ô màu đã chọn.
     * - Mùa vụ: Quanh năm.
     * - Idempotent: cây nào đã có (kể cả đã xoá mềm) theo tên thì bỏ qua.
     *
     * Dùng Query Builder thuần, không phụ thuộc Model.
     */
    private function plants(): array
    {
        return [
            [
                'name' => 'Cây Cau Nga Mi',
                'image' => 'cau-nga-mi.jpg',
                'price' => 450000, 'weight' => 5000,
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'light' => 'Bán râm (Chịu bóng bán phần)', 'water' => 'Trung bình',
                'description' => "Cau Nga Mi có thân mảnh, tán lá xanh mướt rủ mềm, mang lại cảm giác thoáng đãng như một góc nhiệt đới cho văn phòng, sảnh hay phòng khách.\n\nĐặt nơi có ánh sáng tán xạ, tưới khi mặt đất se khô (2–3 lần/tuần), lau lá định kỳ để cây luôn tươi. Theo phong thủy, sắc xanh lá thuộc hành Mộc, tượng trưng cho sự sinh sôi và phát triển.",
            ],
            [
                'name' => 'Cây Cọ Nhật',
                'image' => 'co-nhat.jpg',
                'price' => 350000, 'weight' => 2500,
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'light' => 'Bán râm (Chịu bóng bán phần)', 'water' => 'Trung bình',
                'description' => "Cọ Nhật có lá xòe tròn như chiếc quạt, xếp nếp đều đặn, xanh bóng rất bắt mắt — điểm nhấn sang trọng cho bàn tiếp khách, góc làm việc hay kệ trang trí.\n\nCây chịu bóng tốt, hợp ánh sáng gián tiếp; tưới 2 lần/tuần, tránh để úng gốc. Lá xanh thuộc hành Mộc, mang ý nghĩa che chở, may mắn và bình an.",
            ],
            [
                'name' => 'Cây Đuôi Công Sanderiana',
                'image' => 'duoi-cong-sanderiana.jpg',
                'price' => 280000, 'weight' => 1500,
                'colors' => ['xanh_la', 'do_hong'], 'elements' => ['moc', 'hoa'],
                'light' => 'Ưa râm', 'water' => 'Ưa nước',
                'description' => "Đuôi Công Sanderiana (Calathea ornata) nổi bật với lá xanh sẫm điểm những đường sọc hồng nhạt mảnh như được vẽ tay, mặt dưới lá ánh tím. Lá cây khép lại vào buổi tối và mở ra khi trời sáng.\n\nƯa bóng râm, độ ẩm cao; giữ đất luôn ẩm nhẹ và phun sương cho lá. Hợp mệnh Mộc và Hỏa nhờ sắc xanh và hồng trên lá.",
            ],
            [
                'name' => 'Cây Đuôi Công Tím',
                'image' => 'duoi-cong-tim.jpg',
                'price' => 280000, 'weight' => 1500,
                'colors' => ['xanh_duong_den', 'do_hong'], 'elements' => ['thuy', 'hoa'],
                'light' => 'Ưa râm', 'water' => 'Ưa nước',
                'description' => "Đuôi Công Tím (Calathea roseopicta) có lá tròn màu xanh đen gần như tím than, viền và gân lá ánh hồng tím rất độc đáo — một chậu nhỏ đủ làm sáng cả góc bàn làm việc.\n\nĐặt nơi râm mát, tránh nắng gắt; giữ đất ẩm, phun sương thường xuyên. Lá sẫm thuộc hành Thủy, sắc hồng tím thuộc hành Hỏa.",
            ],
            [
                'name' => 'Cây Vạn Lộc Son',
                'image' => 'van-loc-son.jpg',
                'price' => 250000, 'weight' => 1500,
                'colors' => ['do_hong'], 'elements' => ['hoa'],
                'light' => 'Bán râm (Chịu bóng bán phần)', 'water' => 'Trung bình',
                'description' => "Vạn Lộc Son có lá đỏ hồng rực rỡ, gân viền xanh, tượng trưng cho tài lộc dồi dào và may mắn — món quà khai trương, tân gia được ưa chuộng.\n\nCây dễ chăm, chịu được máy lạnh; đặt nơi có ánh sáng tán xạ để giữ màu lá đẹp, tưới 2 lần/tuần. Sắc đỏ hồng thuộc hành Hỏa.",
            ],
            [
                'name' => 'Cây Công Chúa Pink Princess',
                'image' => 'cong-chua-pink-princess.jpg',
                'price' => 480000, 'weight' => 1500,
                'colors' => ['do_hong', 'xanh_duong_den'], 'elements' => ['hoa', 'thuy'],
                'light' => 'Bán râm (Chịu bóng bán phần)', 'water' => 'Trung bình',
                'description' => "Công Chúa Pink Princess (Philodendron erubescens) có lá xanh sẫm loang những mảng hồng phấn, mỗi chiếc lá một vẻ riêng — dòng cây sưu tầm được nhiều người yêu cây săn đón.\n\nCần ánh sáng gián tiếp đủ sáng để giữ mảng hồng; tưới khi mặt đất khô 2–3 cm. Hồng thuộc hành Hỏa, lá sẫm thuộc hành Thủy.",
            ],
            [
                'name' => 'Cây Hắc Cầm',
                'image' => 'hac-cam.jpg',
                'price' => 520000, 'weight' => 3000,
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'light' => 'Bán râm (Chịu bóng bán phần)', 'water' => 'Trung bình',
                'description' => "Hắc Cầm (Philodendron billietiae) có lá dài, thuôn như lưỡi kiếm, xanh bóng trên cuống màu cam nổi bật — dáng cây phóng khoáng, hiện đại, hợp sảnh văn phòng và phòng khách.\n\nĐặt nơi có ánh sáng tán xạ, tưới khi đất se mặt, lau lá định kỳ. Lá xanh thuộc hành Mộc, tượng trưng cho sự vươn lên.",
            ],
            [
                'name' => 'Cây Ngà Voi',
                'image' => 'nga-voi.jpg',
                'price' => 220000, 'weight' => 1500,
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'light' => 'Bán râm (Chịu bóng bán phần)', 'water' => 'Chịu hạn tốt',
                'description' => "Ngà Voi (Sansevieria cylindrica) có lá tròn, cứng, vươn thẳng như ngà voi, vân xanh đậm nhạt xen kẽ. Cây nổi tiếng lọc không khí tốt và nhả oxy cả ban đêm.\n\nRất dễ chăm: chịu hạn, chịu bóng, chỉ cần tưới 1 lần/tuần — lựa chọn lý tưởng cho người bận rộn. Lá xanh thuộc hành Mộc, mang ý nghĩa trường thọ, vững vàng.",
            ],
            [
                'name' => 'Cây Ngũ Gia Bì',
                'image' => 'ngu-gia-bi.jpg',
                'price' => 320000, 'weight' => 2500,
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'light' => 'Bán râm (Chịu bóng bán phần)', 'water' => 'Trung bình',
                'description' => "Ngũ Gia Bì có tán lá xanh dày, mỗi cuống xòe 5–7 lá chét như bàn tay. Cây giúp lọc không khí và có mùi nhẹ giúp xua muỗi, rất hợp đặt văn phòng, phòng làm việc.\n\nChịu bóng tốt, tưới 2–3 lần/tuần, tỉa tán để cây giữ dáng gọn. Lá xanh thuộc hành Mộc, tượng trưng cho sự sung túc, hòa hợp.",
            ],
            [
                'name' => 'Cây Tùng Thơm',
                'image' => 'tung-thom.jpg',
                'price' => 260000, 'weight' => 2000,
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'light' => 'Ưa nắng', 'water' => 'Trung bình',
                'description' => "Tùng Thơm (Tùng chanh) có tán hình tháp xanh non, lá kim mềm tỏa hương chanh dịu nhẹ khi chạm vào — vừa trang trí bàn làm việc, vừa là cây thông Noel mini dễ thương.\n\nƯa sáng: đặt gần cửa sổ hoặc mang ra nắng nhẹ vài giờ mỗi tuần; tưới đều, không để khô kiệt. Lá xanh thuộc hành Mộc, mang ý nghĩa trường tồn, kiên định.",
            ],
        ];
    }

    public function up(): void
    {
        $officeCategoryId = DB::table('categories')
            ->where('name', 'Cây cảnh văn phòng')
            ->value('id');

        if (!$officeCategoryId) {
            Log::warning('[seed_cay_van_phong] Không tìm thấy danh mục "Cây cảnh văn phòng" — bỏ qua.');
            return;
        }

        $indoorId = DB::table('categories')->where('name', 'Trong nhà (Indoor)')->value('id');
        $now = now();

        foreach ($this->plants() as $plant) {
            $exists = DB::table('products')->where('name', $plant['name'])->exists();
            if ($exists) {
                continue;
            }

            DB::transaction(function () use ($plant, $officeCategoryId, $indoorId, $now) {
                $productId = DB::table('products')->insertGetId([
                    'name' => $plant['name'],
                    'product_type' => 'plant',
                    'base_price' => $plant['price'],
                    'weight' => $plant['weight'],
                    'stock' => 20,
                    'main_image' => 'products/cay-van-phong/' . $plant['image'],
                    'description' => $plant['description'],
                    'is_active' => true,
                    'badge' => null,
                    'feng_shui_colors' => json_encode($plant['colors']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $categoryIds = array_filter([
                    $officeCategoryId,
                    $indoorId,
                    DB::table('categories')->where('name', $plant['light'])->value('id'),
                    DB::table('categories')->where('name', $plant['water'])->value('id'),
                ]);

                foreach (array_unique($categoryIds) as $categoryId) {
                    DB::table('category_product')->insert([
                        'category_id' => $categoryId,
                        'product_id' => $productId,
                    ]);
                }

                foreach ($plant['elements'] as $element) {
                    DB::table('product_elements')->insert([
                        'product_id' => $productId,
                        'element' => $element,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('product_seasons')->insert([
                    'product_id' => $productId,
                    'season' => 'all_year',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
        }
    }

    public function down(): void
    {
        // Xoá hẳn 10 cây do migration này tạo (bảng con tự xoá theo cascade).
        // Cây đã phát sinh đơn hàng thì nên xoá mềm trong admin thay vì rollback.
        $names = array_column($this->plants(), 'name');
        $ids = DB::table('products')->whereIn('name', $names)
            ->where('main_image', 'like', 'products/cay-van-phong/%')
            ->pluck('id');

        DB::table('category_product')->whereIn('product_id', $ids)->delete();
        DB::table('products')->whereIn('id', $ids)->delete();
    }
};
