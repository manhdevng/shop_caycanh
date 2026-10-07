<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Migration DỮ LIỆU: thêm 8 cây ngoài trời + 13 cây mini để bàn.
     *
     * - Ảnh nằm trong git: storage/app/public/products/ngoai-troi|mini/
     *   (đã thu về tối đa 1200px) để không mất khi Render deploy lại.
     * - Cây ngoài trời gắn vào "Cây bóng mát & sân vườn" hoặc
     *   "Cây trồng viền & trang trí lối đi" (nhóm "Cây cảnh sân vườn & ngoài trời").
     * - Cây mini: danh mục "Cây mini" đã bị xoá ở migration 2026_09_18_073005,
     *   nên dùng danh mục mini đang có (nếu admin đã tạo lại), nếu không thì tạo
     *   "Cây mini để bàn" dưới nhóm "Cây cảnh trong nhà & để bàn".
     * - Thuộc tính lọc (không gian, ánh sáng, nước), hành phong thủy theo màu
     *   lá/hoa chủ đạo (kèm feng_shui_colors cho form admin) và mùa vụ.
     * - Idempotent: cây đã có (kể cả đã xoá mềm) theo tên thì bỏ qua.
     */
    private const MINI_NAMES = ['Cây mini để bàn', 'Cây mini', 'Cây cảnh mini'];

    private function plants(): array
    {
        $outdoor = 'Ngoài trời (Outdoor)';
        $indoor = 'Trong nhà (Indoor)';
        $shade = 'Cây bóng mát & sân vườn';
        $border = 'Cây trồng viền & trang trí lối đi';
        $mini = 'MINI';

        return [
            // ---------------- Cây ngoài trời ----------------
            [
                'name' => 'Cây Bonsai Sân Vườn', 'image' => 'ngoai-troi/bonsai-san-vuon.jpg',
                'price' => 3500000, 'weight' => 20000, 'stock' => 5, 'category' => $shade,
                'filters' => [$outdoor, 'Ưa nắng', 'Chịu hạn tốt'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Bonsai dáng thế được uốn tạo nhiều năm, thân xù xì vặn xoắn, tán xanh tầng lớp — tác phẩm nghệ thuật làm điểm nhấn cho sân vườn, tiểu cảnh hay lối vào nhà.\n\nĐặt nơi nhiều nắng, tưới khi mặt chậu khô, cắt tỉa định kỳ để giữ dáng. Tán xanh thuộc hành Mộc, tượng trưng cho sự bền bỉ, trường thọ.",
            ],
            [
                'name' => 'Cây Chà Là Cảnh', 'image' => 'ngoai-troi/cha-la-canh.jpg',
                'price' => 4500000, 'weight' => 20000, 'stock' => 5, 'category' => $shade,
                'filters' => [$outdoor, 'Ưa nắng', 'Chịu hạn tốt'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Chà Là cảnh có thân thẳng, tán lá lông chim xòe rộng, mang dáng vẻ nhiệt đới sang trọng như ở resort — hợp trồng cổng biệt thự, sân vườn, lối đi rộng.\n\nCây ưa nắng, chịu hạn và gió tốt, ít sâu bệnh; tưới 2–3 lần/tuần khi mới trồng. Lá xanh thuộc hành Mộc, mang ý nghĩa vững vàng, thịnh vượng.",
            ],
            [
                'name' => 'Cây Cọ Tây Bạc', 'image' => 'ngoai-troi/co-tay-bac.jpg',
                'price' => 5500000, 'weight' => 20000, 'stock' => 5, 'category' => $shade,
                'filters' => [$outdoor, 'Ưa nắng', 'Chịu hạn tốt'], 'seasons' => ['all_year'],
                'colors' => ['trang_bac', 'xanh_la'], 'elements' => ['kim', 'moc'],
                'description' => "Cọ Tây Bạc (Bismarckia nobilis) nổi bật với tán lá quạt khổng lồ màu xanh ánh bạc, dáng cây cân đối, bề thế — lựa chọn đẳng cấp cho sân vườn rộng, khuôn viên biệt thự.\n\nCây ưa nắng toàn phần, chịu hạn tốt, sinh trưởng khỏe. Sắc bạc thuộc hành Kim, sắc xanh thuộc hành Mộc.",
            ],
            [
                'name' => 'Cây Hoa Cẩm Tím', 'image' => 'ngoai-troi/hoa-cam-tim.jpg',
                'price' => 450000, 'weight' => 5000, 'stock' => 10, 'category' => $border,
                'filters' => [$outdoor, 'Ưa nắng', 'Trung bình'], 'seasons' => ['spring', 'summer'],
                'colors' => ['do_hong'], 'elements' => ['hoa'],
                'description' => "Bụi hoa tím nở thành từng chùm dày, phủ kín tán lá xanh, tạo mảng màu lãng mạn cho hàng rào, bồn hoa và lối đi trong vườn.\n\nTrồng nơi nhiều nắng, đất tơi thoát nước; tưới đều và tỉa cành sau mỗi đợt hoa để cây ra hoa nhiều hơn. Sắc tím thuộc hành Hỏa.",
            ],
            [
                'name' => 'Cây Mai Vàng', 'image' => 'ngoai-troi/mai-vang.jpg',
                'price' => 1200000, 'weight' => 10000, 'stock' => 10, 'category' => $border,
                'filters' => [$outdoor, 'Ưa nắng', 'Trung bình'], 'seasons' => ['spring'],
                'colors' => ['vang_dat_nau'], 'elements' => ['tho'],
                'description' => "Mai Vàng bung nở rực rỡ phủ kín cành vào mùa xuân, biểu tượng của phú quý, may mắn và khởi đầu suôn sẻ — không thể thiếu trong sân vườn dịp Tết.\n\nƯa nắng, đất thoát nước tốt; tưới đều, bón phân và lặt lá trước Tết để hoa nở đúng dịp. Sắc vàng sậm thuộc hành Thổ.",
            ],
            [
                'name' => 'Cây Hoa Anh Đào', 'image' => 'ngoai-troi/hoa-anh-dao.jpg',
                'price' => 2500000, 'weight' => 20000, 'stock' => 5, 'category' => $shade,
                'filters' => [$outdoor, 'Ưa nắng', 'Trung bình'], 'seasons' => ['spring'],
                'colors' => ['do_hong'], 'elements' => ['hoa'],
                'description' => "Anh Đào nở hoa hồng phủ kín cành vào mùa xuân, mang vẻ đẹp thơ mộng, tượng trưng cho tình yêu và sự tươi mới — điểm nhấn tuyệt đẹp cho sân vườn, lối đi.\n\nTrồng nơi thoáng, nhiều nắng; tưới đều trong mùa khô, bón phân trước mùa hoa. Sắc hồng thuộc hành Hỏa.",
            ],
            [
                'name' => 'Cây Tre Cảnh', 'image' => 'ngoai-troi/tre-canh.jpg',
                'price' => 650000, 'weight' => 10000, 'stock' => 10, 'category' => $border,
                'filters' => [$outdoor, 'Ưa nắng', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Tre cảnh mọc thành bụi, thân thanh mảnh, lá xanh non xào xạc — tạo hàng rào xanh che chắn tự nhiên, mang không gian thiền tĩnh lặng cho sân vườn.\n\nPhát triển nhanh, ưa nắng; tưới đều, tỉa bớt thân già để bụi luôn thoáng. Lá xanh thuộc hành Mộc, tượng trưng cho sự ngay thẳng, kiên cường.",
            ],
            [
                'name' => 'Cây Vạn Tuế', 'image' => 'ngoai-troi/van-tue.jpg',
                'price' => 1500000, 'weight' => 15000, 'stock' => 5, 'category' => $shade,
                'filters' => [$outdoor, 'Ưa nắng', 'Chịu hạn tốt'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Vạn Tuế có tán lá cứng xếp vòng đều quanh thân, dáng uy nghi, sống rất lâu năm — biểu tượng của trường thọ và bền vững, thường trồng ở sân, cổng, khuôn viên.\n\nRất dễ chăm: ưa nắng, chịu hạn, ít sâu bệnh, tưới 1–2 lần/tuần. Lá xanh thuộc hành Mộc.",
            ],

            // ---------------- Cây mini để bàn ----------------
            [
                'name' => 'Cây Cau Tiểu Trâm', 'image' => 'mini/cau-tieu-tram.jpg',
                'price' => 150000, 'weight' => 800, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Ưa râm', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc', 'thuy'],
                'description' => "Cau Tiểu Trâm (Chamaedorea elegans) nhỏ nhắn với tán lá lông chim xanh mướt, giúp lọc không khí — rất hợp đặt bàn làm việc, kệ sách, góc phòng.\n\nChịu bóng tốt, chịu được máy lạnh; tưới 2 lần/tuần, có thể trồng thủy canh. Hợp mệnh Mộc và Thủy.",
            ],
            [
                'name' => 'Cây Cỏ May Mắn Hình Trái Tim', 'image' => 'mini/co-may-man.jpg',
                'price' => 180000, 'weight' => 800, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Bán râm (Chịu bóng bán phần)', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Chậu cỏ may mắn tạo hình trái tim xanh mướt — món quà dễ thương cho người thương, đồng nghiệp hay trang trí bàn làm việc.\n\nĐặt nơi có ánh sáng tán xạ, giữ độ ẩm vừa phải. Sắc xanh thuộc hành Mộc, mang ý nghĩa may mắn, yêu thương.",
            ],
            [
                'name' => 'Cây Hồng Hạc Mini', 'image' => 'mini/hong-hac-mini.jpg',
                'price' => 180000, 'weight' => 800, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Bán râm (Chịu bóng bán phần)', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Hồng Hạc mini (Philodendron) có lá dài thuôn, xanh bóng, cuống ánh cam — nhỏ gọn nhưng rất có dáng, hợp bàn làm việc và kệ trang trí.\n\nĐặt nơi sáng gián tiếp, tưới khi đất se mặt. Lá xanh thuộc hành Mộc.",
            ],
            [
                'name' => 'Cây Hồng Môn', 'image' => 'mini/hong-mon.jpg',
                'price' => 160000, 'weight' => 800, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Bán râm (Chịu bóng bán phần)', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['do_hong', 'xanh_la'], 'elements' => ['hoa', 'moc'],
                'description' => "Hồng Môn (Anthurium) có hoa đỏ bóng hình trái tim nổi bật trên nền lá xanh, ra hoa quanh năm — tượng trưng cho tình yêu và sự nồng nhiệt.\n\nƯa ánh sáng tán xạ, độ ẩm cao; tưới 2 lần/tuần, tránh úng. Hoa đỏ thuộc hành Hỏa, lá xanh thuộc hành Mộc.",
            ],
            [
                'name' => 'Cây Kim Ngân Thắt Nơ', 'image' => 'mini/kim-ngan-that-no.jpg',
                'price' => 200000, 'weight' => 800, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Bán râm (Chịu bóng bán phần)', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['kim', 'moc'],
                'description' => "Kim Ngân thân thắt nơ độc đáo, lá xòe như bàn tay — biểu tượng giữ tiền, chiêu tài, rất hợp làm quà khai trương, tặng bàn làm việc.\n\nChịu bóng, chịu máy lạnh; tưới 1–2 lần/tuần, không để úng. Hợp mệnh Kim và Mộc.",
            ],
            [
                'name' => 'Cây Lá Hột Xoàn', 'image' => 'mini/la-hot-xoan.jpg',
                'price' => 120000, 'weight' => 600, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Ưa râm', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la', 'trang_bac'], 'elements' => ['moc', 'kim'],
                'description' => "Lá Hột Xoàn (Peperomia) có lá nhỏ tròn xanh đậm, vân bạc lấp lánh như hạt kim cương — chậu cây tí hon xinh xắn cho bàn học, bàn làm việc.\n\nChịu bóng, ít cần nước (1 lần/tuần). Lá xanh thuộc hành Mộc, vân bạc thuộc hành Kim.",
            ],
            [
                'name' => 'Cây Phát Lộc Để Bàn', 'image' => 'mini/phat-loc.jpg',
                'price' => 150000, 'weight' => 800, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Ưa râm', 'Ưa nước'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Phát Lộc với các đốt thân xếp tầng, lá xanh tươi, mang ý nghĩa tài lộc, thăng tiến — cây phong thủy để bàn được ưa chuộng nhất.\n\nCó thể trồng đất hoặc thủy canh, chịu bóng tốt; thay nước 1 lần/tuần nếu trồng nước. Lá xanh thuộc hành Mộc.",
            ],
            [
                'name' => 'Cây Trầu Bà Để Bàn', 'image' => 'mini/trau-ba.jpg',
                'price' => 130000, 'weight' => 800, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Ưa râm', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Trầu Bà lá xanh bóng hình tim, dây rủ mềm mại, lọc không khí tốt — cây dễ sống nhất cho người mới chơi cây.\n\nChịu bóng, có thể trồng thủy canh; tưới 2 lần/tuần. Lá xanh thuộc hành Mộc, mang ý nghĩa sinh sôi, may mắn.",
            ],
            [
                'name' => 'Cây Trúc Nhật Vàng', 'image' => 'mini/truc-nhat-vang.jpg',
                'price' => 160000, 'weight' => 800, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Bán râm (Chịu bóng bán phần)', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['vang_nhat', 'xanh_la'], 'elements' => ['kim', 'moc'],
                'description' => "Trúc Nhật Vàng có lá vàng chanh pha xanh tươi sáng, dáng thanh mảnh — mang lại cảm giác tươi mới cho góc làm việc, phòng khách.\n\nƯa sáng gián tiếp để lá giữ màu vàng đẹp, tưới khi đất se mặt. Sắc vàng nhạt thuộc hành Kim, xanh thuộc hành Mộc.",
            ],
            [
                'name' => 'Cây Hạnh Tiên Thảo Đỏ', 'image' => 'mini/hanh-tien-thao-do.jpg',
                'price' => 150000, 'weight' => 600, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Bán râm (Chịu bóng bán phần)', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['do_hong'], 'elements' => ['hoa'],
                'description' => "Hạnh Tiên Thảo đỏ (Peperomia caperata) có lá nhăn gợn sóng màu đỏ tía ánh bạc, rất lạ mắt — chậu nhỏ tạo điểm nhấn nổi bật cho bàn làm việc.\n\nChịu bóng, ít cần nước (1 lần/tuần), tránh úng. Sắc đỏ tía thuộc hành Hỏa.",
            ],
            [
                'name' => 'Chậu Sen Đá Mix', 'image' => 'mini/sen-da-mix.jpg',
                'price' => 220000, 'weight' => 1500, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Ưa nắng', 'Chịu hạn tốt'], 'seasons' => ['all_year'],
                'colors' => ['trang_bac', 'xanh_la'], 'elements' => ['kim', 'moc'],
                'description' => "Chậu sen đá phối nhiều loại với sắc xanh, xám bạc, tím hồng trong chậu đất nung — món quà nhỏ xinh, tượng trưng cho tình bạn bền lâu.\n\nĐặt nơi nhiều nắng (cửa sổ, ban công), tưới ít 1 lần/tuần. Sắc bạc thuộc hành Kim, xanh thuộc hành Mộc.",
            ],
            [
                'name' => 'Cây Tuyết Tùng Để Bàn', 'image' => 'mini/tuyet-tung.jpg',
                'price' => 180000, 'weight' => 800, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Bán râm (Chịu bóng bán phần)', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Tuyết Tùng để bàn dáng bonsai mini, lá kim xanh mịn — mang vẻ trang nhã, tĩnh tại cho bàn làm việc, kệ sách.\n\nĐặt nơi sáng, thỉnh thoảng mang ra nắng nhẹ; tưới khi mặt đất khô. Lá xanh thuộc hành Mộc, tượng trưng cho sự kiên định.",
            ],
            [
                'name' => 'Cây Vạn Niên Tùng', 'image' => 'mini/van-nien-tung.jpg',
                'price' => 150000, 'weight' => 600, 'stock' => 20, 'category' => $mini,
                'filters' => [$indoor, 'Bán râm (Chịu bóng bán phần)', 'Trung bình'], 'seasons' => ['all_year'],
                'colors' => ['xanh_la'], 'elements' => ['moc'],
                'description' => "Vạn Niên Tùng có lá kim nhỏ mọc dày như tán tùng thu nhỏ, xanh quanh năm — mang ý nghĩa trường thọ, bình an.\n\nDễ chăm, chịu bóng bán phần; tưới 2 lần/tuần. Lá xanh thuộc hành Mộc.",
            ],
        ];
    }

    private function categoryId(string $name): ?int
    {
        return DB::table('categories')->where('name', $name)->value('id');
    }

    /** Danh mục mini: dùng cái đã có, nếu không thì tạo dưới nhóm "Cây cảnh trong nhà & để bàn". */
    private function miniCategoryId(): ?int
    {
        foreach (self::MINI_NAMES as $name) {
            if ($id = $this->categoryId($name)) {
                return $id;
            }
        }

        $parentId = DB::table('categories')
            ->where('name', 'Cây cảnh trong nhà & để bàn')
            ->whereNull('parent_id')
            ->value('id');

        if (!$parentId) {
            Log::warning('[seed_outdoor_mini] Không tìm thấy nhóm "Cây cảnh trong nhà & để bàn" — bỏ qua cây mini.');
            return null;
        }

        $nextSort = (int) DB::table('categories')->where('parent_id', $parentId)->max('sort_order') + 1;

        return DB::table('categories')->insertGetId([
            'name' => 'Cây mini để bàn',
            'parent_id' => $parentId,
            'scope' => 'plant',
            'sort_order' => $nextSort,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function up(): void
    {
        $miniId = null;
        $now = now();

        foreach ($this->plants() as $plant) {
            if (DB::table('products')->where('name', $plant['name'])->exists()) {
                continue;
            }

            if ($plant['category'] === 'MINI') {
                $miniId ??= $this->miniCategoryId();
                $mainCategoryId = $miniId;
            } else {
                $mainCategoryId = $this->categoryId($plant['category']);
            }

            if (!$mainCategoryId) {
                Log::warning("[seed_outdoor_mini] Không tìm thấy danh mục cho \"{$plant['name']}\" — bỏ qua.");
                continue;
            }

            DB::transaction(function () use ($plant, $mainCategoryId, $now) {
                $productId = DB::table('products')->insertGetId([
                    'name' => $plant['name'],
                    'product_type' => 'plant',
                    'base_price' => $plant['price'],
                    'weight' => $plant['weight'],
                    'stock' => $plant['stock'],
                    'main_image' => 'products/' . $plant['image'],
                    'description' => $plant['description'],
                    'is_active' => true,
                    'badge' => null,
                    'feng_shui_colors' => json_encode($plant['colors']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $categoryIds = [$mainCategoryId];
                foreach ($plant['filters'] as $filterName) {
                    if ($id = $this->categoryId($filterName)) {
                        $categoryIds[] = $id;
                    }
                }

                foreach (array_unique($categoryIds) as $categoryId) {
                    DB::table('category_product')->insert([
                        'category_id' => $categoryId,
                        'product_id' => $productId,
                    ]);
                }

                foreach ($plant['elements'] as $element) {
                    DB::table('product_elements')->insert([
                        'product_id' => $productId, 'element' => $element,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }

                foreach ($plant['seasons'] as $season) {
                    DB::table('product_seasons')->insert([
                        'product_id' => $productId, 'season' => $season,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        $ids = DB::table('products')
            ->whereIn('name', array_column($this->plants(), 'name'))
            ->where(function ($q) {
                $q->where('main_image', 'like', 'products/ngoai-troi/%')
                    ->orWhere('main_image', 'like', 'products/mini/%');
            })
            ->pluck('id');

        DB::table('category_product')->whereIn('product_id', $ids)->delete();
        DB::table('products')->whereIn('id', $ids)->delete();
        // Danh mục "Cây mini để bàn" (nếu migration này tạo) được giữ lại; admin tự xoá nếu muốn.
    }
};
