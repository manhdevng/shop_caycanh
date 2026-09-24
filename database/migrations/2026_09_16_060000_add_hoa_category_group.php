<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Thêm nhóm danh mục "Kiểu loại hoa" (song song với nhóm "Kiểu loại cây"
     * đã có sẵn từ PlantAttributeSeeder) để có thể tách riêng 1 hàng sản phẩm
     * chỉ gồm cây cảnh và 1 hàng sản phẩm chỉ gồm hoa trên trang chủ.
     *
     * Sau khi chạy migration này, vào /admin/categories sẽ thấy danh mục con
     * "Hoa" (nằm dưới "Kiểu loại hoa") — gắn sản phẩm hoa vào danh mục này để
     * chúng xuất hiện ở khối "Hoa mới nhập" trên trang chủ. Có thể thêm các
     * danh mục con khác (VD: Hoa hồng, Hoa lan...) ngay trong trang quản trị,
     * chỉ cần chọn "Kiểu loại hoa" làm danh mục cha.
     */
    public function up(): void
    {
        $exists = DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại hoa')
            ->exists();

        if ($exists) {
            return;
        }

        $now = Carbon::now();

        $parentId = DB::table('categories')->insertGetId([
            'name' => 'Kiểu loại hoa',
            'parent_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('categories')->insert([
            'name' => 'Hoa',
            'parent_id' => $parentId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $parent = DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại hoa')
            ->first();

        if (! $parent) {
            return;
        }

        DB::table('category_product')
            ->whereIn('category_id', DB::table('categories')->where('parent_id', $parent->id)->pluck('id'))
            ->delete();

        DB::table('categories')->where('parent_id', $parent->id)->delete();
        DB::table('categories')->where('id', $parent->id)->delete();
    }
};
