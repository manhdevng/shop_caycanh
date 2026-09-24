<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Đây là migration DỮ LIỆU (không đổi schema).
     *
     * Bối cảnh: migration 2026_09_20_100001_add_stock_to_products_table đã
     * thêm cột products.stock (unsigned int, mặc định 0). Vì mặc định là 0
     * nên toàn bộ 12 sản phẩm đang bán hiện đang có stock = 0. Sắp tới code
     * sẽ bật chặn "hết hàng" (không cho thêm vào giỏ khi stock = 0), nếu
     * không nạp tồn kho ban đầu thì cả shop sẽ không bán được sản phẩm nào.
     * Vì vậy nạp tạm stock = 50 cho các sản phẩm đang bán để admin có thời
     * gian nhập số liệu tồn kho thật sau.
     *
     * Chỉ tác động sản phẩm ĐANG BÁN: is_active = 1 AND deleted_at IS NULL.
     * Không đụng tới các sản phẩm đã xoá mềm hoặc ngừng bán.
     *
     * Idempotent có chủ đích: điều kiện stock = 0 (ở up) và stock = 50
     * (ở down) đảm bảo nếu admin đã nhập tồn kho thật (khác 0 hoặc khác 50)
     * thì chạy lại migration này (vô tình hay cố ý) sẽ KHÔNG ghi đè lên số
     * liệu thật đó.
     *
     * Dùng Query Builder thuần (DB::table), không dùng Eloquent Model, để
     * tránh phụ thuộc vào $fillable/observer của Model Product.
     */
    public function up(): void
    {
        DB::table('products')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->where('stock', 0)
            ->update(['stock' => 50]);
    }

    /**
     * Reverse the migrations.
     *
     * Chỉ đưa về stock = 0 cho đúng tập sản phẩm đang bán mà migration này
     * đã nạp (stock = 50). Nếu admin đã sửa tay thành giá trị khác 50 thì
     * giữ nguyên, không rollback nhầm số liệu thật.
     */
    public function down(): void
    {
        DB::table('products')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->where('stock', 50)
            ->update(['stock' => 0]);
    }
};
