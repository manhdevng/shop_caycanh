<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Tái cấu trúc 2 nhóm danh mục "Kiểu loại cây" và "Kiểu loại hoa" theo
     * logic công dụng/đặc điểm chi tiết (đã chốt trong
     * "Claude outputs/category-plant-flower-restructure-plan.md").
     *
     * Migration này CHỈ thao tác dữ liệu trên bảng `categories` (và gián tiếp
     * `category_product` qua cascadeOnDelete khi xoá category cha), KHÔNG đổi
     * schema, KHÔNG đụng `products`/`orders`.
     *
     * Nhóm "Cây cảnh" (parent = "Kiểu loại cây"):
     * - Đổi tên "Cây phong thủy" -> "Cây phong thủy hút tài lộc" (giữ nguyên id).
     * - Thêm 5 category con mới theo công dụng/đặc điểm.
     * - Xoá 3 category con cũ: "Cây cảnh", "Cây để bàn", "Cây mini".
     *
     * Nhóm "Hoa" (parent = "Kiểu loại hoa"):
     * - Xoá category con "Hoa".
     * - Thêm 4 category con mới theo dịp sử dụng.
     *
     * Nhóm "Không gian sống": chỉ xoá nếu KHÔNG có sản phẩm nào đang gắn vào
     * bất kỳ category con nào của nhóm này (kiểm tra động lúc chạy, không
     * hardcode). Nếu có sản phẩm gắn -> SKIP toàn bộ bước này.
     */
    public function up(): void
    {
        $plantParentId = DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại cây')
            ->value('id');

        $flowerParentId = DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại hoa')
            ->value('id');

        if (! $plantParentId || ! $flowerParentId) {
            // Môi trường không có đủ 2 parent này -> an toàn, không làm gì.
            return;
        }

        $now = Carbon::now();

        // 1) Đổi tên "Cây phong thủy" -> "Cây phong thủy hút tài lộc" (giữ id).
        DB::table('categories')
            ->where('parent_id', $plantParentId)
            ->where('name', 'Cây phong thủy')
            ->update([
                'name' => 'Cây phong thủy hút tài lộc',
                'updated_at' => $now,
            ]);

        // 2) Thêm 5 category con mới cho nhóm Cây cảnh.
        DB::table('categories')->insert([
            [
                'name' => 'Cây dễ chăm sóc',
                'parent_id' => $plantParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cây lọc không khí',
                'parent_id' => $plantParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cây kích thước trung bình/lớn',
                'parent_id' => $plantParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cây bóng mát & sân vườn',
                'parent_id' => $plantParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cây trồng viền & trang trí lối đi',
                'parent_id' => $plantParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 3) Xoá 3 category con cũ của nhóm Cây cảnh (cascade tự xoá
        // category_product liên quan, KHÔNG xoá products).
        DB::table('categories')
            ->where('parent_id', $plantParentId)
            ->whereIn('name', ['Cây cảnh', 'Cây để bàn', 'Cây mini'])
            ->delete();

        // 4) Thêm 4 category con mới cho nhóm Hoa.
        DB::table('categories')->insert([
            [
                'name' => 'Hoa tặng sinh nhật',
                'parent_id' => $flowerParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Hoa chúc mừng & khai trương',
                'parent_id' => $flowerParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Hoa cưới & hoa cầm tay',
                'parent_id' => $flowerParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Hoa chia buồn & viếng tang',
                'parent_id' => $flowerParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 5) Xoá category con "Hoa" cũ (cascade tự xoá category_product liên quan).
        DB::table('categories')
            ->where('parent_id', $flowerParentId)
            ->where('name', 'Hoa')
            ->delete();

        // 6) Xử lý "Không gian sống" — kiểm tra động, chỉ xoá nếu KHÔNG có
        // sản phẩm nào đang gắn vào category con nào của nhóm này.
        $livingSpaceParentId = DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Không gian sống')
            ->value('id');

        if ($livingSpaceParentId) {
            $childIds = DB::table('categories')
                ->where('parent_id', $livingSpaceParentId)
                ->pluck('id');

            $attachedCount = DB::table('category_product')
                ->whereIn('category_id', $childIds)
                ->count();

            if ($attachedCount === 0) {
                DB::table('categories')->where('parent_id', $livingSpaceParentId)->delete();
                DB::table('categories')->where('id', $livingSpaceParentId)->delete();
            }
            // else: có sản phẩm gắn ($attachedCount > 0) -> SKIP, giữ nguyên
            // "Không gian sống" và các category con của nó.
        }
    }

    /**
     * Reverse the migrations.
     *
     * Chỉ để rollback ngay sau khi migration này chạy (nếu có lỗi), KHÔNG
     * nhằm khôi phục lại gắn kết sản phẩm cũ vào các category đã xoá ở up().
     * KHÔNG đụng "Không gian sống" vì up() có thể đã SKIP nhóm này.
     */
    public function down(): void
    {
        $plantParentId = DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại cây')
            ->value('id');

        $flowerParentId = DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại hoa')
            ->value('id');

        if (! $plantParentId || ! $flowerParentId) {
            return;
        }

        $now = Carbon::now();

        // 1) Đổi tên "Cây phong thủy hút tài lộc" -> "Cây phong thủy".
        DB::table('categories')
            ->where('parent_id', $plantParentId)
            ->where('name', 'Cây phong thủy hút tài lộc')
            ->update([
                'name' => 'Cây phong thủy',
                'updated_at' => $now,
            ]);

        // 2) Xoá 5 category con mới đã tạo cho nhóm Cây cảnh.
        DB::table('categories')
            ->where('parent_id', $plantParentId)
            ->whereIn('name', [
                'Cây dễ chăm sóc',
                'Cây lọc không khí',
                'Cây kích thước trung bình/lớn',
                'Cây bóng mát & sân vườn',
                'Cây trồng viền & trang trí lối đi',
            ])
            ->delete();

        // 3) Xoá 4 category con mới đã tạo cho nhóm Hoa.
        DB::table('categories')
            ->where('parent_id', $flowerParentId)
            ->whereIn('name', [
                'Hoa tặng sinh nhật',
                'Hoa chúc mừng & khai trương',
                'Hoa cưới & hoa cầm tay',
                'Hoa chia buồn & viếng tang',
            ])
            ->delete();

        // 4) Tạo lại 3 category con cũ của nhóm Cây cảnh (không khôi phục
        // gắn kết sản phẩm cũ).
        DB::table('categories')->insert([
            [
                'name' => 'Cây cảnh',
                'parent_id' => $plantParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cây để bàn',
                'parent_id' => $plantParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cây mini',
                'parent_id' => $plantParentId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 5) Tạo lại category con "Hoa" cũ của nhóm Hoa.
        DB::table('categories')->insert([
            'name' => 'Hoa',
            'parent_id' => $flowerParentId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
