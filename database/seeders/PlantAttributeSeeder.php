<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class PlantAttributeSeeder extends Seeder
{
    /**
     * ⚠️ CẢNH BÁO: seeder này TRUNCATE bảng `categories` + `category_product`
     * — KHÔNG được chạy lại trên database thật (ecommere2024), sẽ xoá toàn
     * bộ gắn danh mục của mọi sản phẩm hiện có (cả nhóm Cây lẫn nhóm Hoa).
     * Chỉ an toàn để chạy trên DB test/mới tinh chưa có dữ liệu thật.
     */

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ⚠️ CẢNH BÁO: run() bên dưới TRUNCATE categories + category_product.
        // KHÔNG gọi seeder này (trực tiếp hoặc qua DatabaseSeeder/db:seed)
        // nhắm vào database ecommere2024 (DB thật) — sẽ mất toàn bộ gắn
        // danh mục sản phẩm hiện có.
        // Xóa hết categories cũ trước khi thêm (Optional)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('category_product')->truncate();
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $attributes = [
            'Kiểu loại cây' => [
                'Cây cảnh',
                'Cây phong thủy',
                'Cây để bàn',
                'Cây ăn quả',
            ],
            'Đặc tính ánh sáng' => [
                'Ưa nắng',
                'Ưa râm',
                'Bán râm (Chịu bóng bán phần)',
            ],
            'Nhu cầu nước' => [
                'Ưa nước',
                'Chịu hạn tốt',
                'Trung bình',
            ],
            'Không gian sống' => [
                'Trong nhà (Indoor)',
                'Ngoài trời (Outdoor)',
                'Ban công / Cửa sổ',
            ]
        ];

        foreach ($attributes as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'parent_id' => null,
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'name' => $childName,
                    'parent_id' => $parent->id,
                ]);
            }
        }
    }
}
