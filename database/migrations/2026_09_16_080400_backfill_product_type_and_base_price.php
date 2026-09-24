<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration này CHỈ SỬA DỮ LIỆU (không đổi schema):
     *  1. Gán product_type = 'flower' cho sản phẩm đang gắn (qua bảng nối
     *     category_product) ít nhất 1 danh mục con thuộc nhóm gốc có
     *     scope = 'flower'. Các sản phẩm còn lại giữ mặc định 'plant'
     *     (không cần update vì default cột đã là 'plant').
     *  2. Gán base_price = MIN(price) từ product_variants cho các sản
     *     phẩm đang có ít nhất 1 phân loại.
     *
     * Xem Phase 1 mục 5 trong "Claude outputs/product-module-fix-plan.md".
     */
    public function up(): void
    {
        // 1) product_type = 'flower' cho sản phẩm gắn danh mục con của
        // nhóm gốc có scope = 'flower'.
        $flowerCategoryIds = DB::table('categories as child')
            ->join('categories as root', 'child.parent_id', '=', 'root.id')
            ->where('root.scope', 'flower')
            ->pluck('child.id');

        if ($flowerCategoryIds->isNotEmpty()) {
            $flowerProductIds = DB::table('category_product')
                ->whereIn('category_id', $flowerCategoryIds)
                ->pluck('product_id')
                ->unique()
                ->values();

            if ($flowerProductIds->isNotEmpty()) {
                DB::table('products')
                    ->whereIn('id', $flowerProductIds)
                    ->update(['product_type' => 'flower']);
            }
        }

        // 2) base_price = MIN(price) cho sản phẩm đang có phân loại.
        $minPrices = DB::table('product_variants')
            ->select('product_id', DB::raw('MIN(price) as min_price'))
            ->groupBy('product_id')
            ->get();

        foreach ($minPrices as $row) {
            DB::table('products')
                ->where('id', $row->product_id)
                ->update(['base_price' => $row->min_price]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Để trống — chỉ sửa dữ liệu, không revert.
     */
    public function down(): void
    {
        //
    }
};
