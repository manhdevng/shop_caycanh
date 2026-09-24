<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Thêm phạm vi (scope) cho nhóm danh mục gốc: 'plant' | 'flower' | 'both'.
     * Chỉ có ý nghĩa ở nhóm gốc (parent_id NULL); danh mục con lấy theo
     * nhóm cha. Xem D1 trong "Claude outputs/product-module-fix-plan.md".
     *
     * Gán dữ liệu cũ theo TÊN nhóm gốc thật đã xác nhận trong DB
     * `ecommere2024` trước khi viết migration này:
     *   - "Kiểu loại cây"      -> plant
     *   - "Kiểu loại hoa"      -> flower
     *   - "Đặc tính ánh sáng"  -> both (default, không cần update)
     *   - "Nhu cầu nước"       -> both (default, không cần update)
     *   - "Không gian sống"    -> both (default, không cần update)
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('scope', 20)->default('both')->after('name');
        });

        // Chỉ update nhóm gốc, theo đúng tên cột `name` thật.
        DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại cây')
            ->update(['scope' => 'plant']);

        DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại hoa')
            ->update(['scope' => 'flower']);

        // Các nhóm còn lại (Đặc tính ánh sáng, Nhu cầu nước, Không gian sống)
        // giữ mặc định 'both' — không cần update.
    }

    /**
     * Reverse the migrations.
     *
     * Chỉ cần xoá cột scope (không cần revert dữ liệu vì cột bị xoá luôn).
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};
