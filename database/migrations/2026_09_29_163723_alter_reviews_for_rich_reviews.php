<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tên chỉ mục/khoá THẬT đã kiểm chứng bằng `php artisan db:table reviews`
     * và `SHOW INDEX FROM reviews` trên DB ecommere2024 trước khi viết migration này:
     *   - UNIQUE KEY `reviews_product_id_user_id_unique` (product_id, user_id)
     *   - FK `reviews_product_id_foreign` (product_id -> products.id, ON DELETE CASCADE)
     *   - FK `reviews_order_id_foreign` (order_id -> orders.id, ON DELETE SET NULL)
     * order_items có sẵn cột variant_name (varchar 255, nullable) dùng để backfill.
     */
    private const UNIQUE_PRODUCT_USER = 'reviews_product_id_user_id_unique';

    private const INDEX_PRODUCT_RATING = 'reviews_product_id_rating_index';

    private const UNIQUE_ORDER_ITEM = 'reviews_order_item_id_unique';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1) Thêm 9 cột mới cho đánh giá "kiểu Shopee".
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_item_id')
                ->nullable()
                ->after('order_id')
                ->constrained('order_items')
                ->nullOnDelete();
            $table->string('variant_name', 120)->nullable()->after('order_item_id');
            $table->boolean('is_anonymous')->default(false)->after('rating');
            $table->unsignedInteger('helpful_count')->default(0)->after('comment');
            $table->boolean('is_hidden')->default(false)->after('helpful_count');
            $table->text('shop_reply')->nullable()->after('is_hidden');
            $table->timestamp('shop_replied_at')->nullable()->after('shop_reply');
            $table->timestamp('edited_at')->nullable()->after('shop_replied_at');
            $table->boolean('points_awarded')->default(false)->after('edited_at');
        });

        // 2) (a) Thêm index(product_id, rating) TRƯỚC — để cột product_id luôn có
        //    một chỉ mục hỗ trợ FK `reviews_product_id_foreign`, tránh lỗi MySQL
        //    "needed in a foreign key constraint" khi drop unique ở bước (b).
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['product_id', 'rating'], self::INDEX_PRODUCT_RATING);
        });

        // 3) (b) Drop unique(product_id, user_id) cũ — dùng đúng tên thật đã kiểm chứng.
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(self::UNIQUE_PRODUCT_USER);
        });

        // 4) Backfill order_item_id + variant_name bằng query builder (không dùng Model/Observer),
        //    idempotent (chỉ xử lý review có order_id nhưng order_item_id còn NULL), theo lô 500 dòng.
        //    Vì unique(order_item_id) sẽ được thêm ở bước (c) ngay sau, ở đây phải tự kiểm tra
        //    tránh gán trùng 1 order_item cho nhiều review — nếu order_item đã bị review khác
        //    dùng thì bỏ qua dòng đó và đếm lại để báo cáo.
        $backfilled = 0;
        $skipped = 0;

        DB::table('reviews')
            ->select('id', 'order_id', 'product_id')
            ->whereNotNull('order_id')
            ->whereNull('order_item_id')
            ->orderBy('id')
            ->chunkById(500, function ($reviews) use (&$backfilled, &$skipped) {
                foreach ($reviews as $review) {
                    $orderItem = DB::table('order_items')
                        ->select('id', 'variant_name')
                        ->where('order_id', $review->order_id)
                        ->where('product_id', $review->product_id)
                        ->orderBy('id')
                        ->first();

                    if (! $orderItem) {
                        continue;
                    }

                    $alreadyUsed = DB::table('reviews')
                        ->where('order_item_id', $orderItem->id)
                        ->where('id', '!=', $review->id)
                        ->exists();

                    if ($alreadyUsed) {
                        $skipped++;

                        continue;
                    }

                    DB::table('reviews')->where('id', $review->id)->update([
                        'order_item_id' => $orderItem->id,
                        'variant_name' => $orderItem->variant_name,
                    ]);
                    $backfilled++;
                }
            });

        if ($backfilled > 0 || $skipped > 0) {
            Log::info("Backfill reviews.order_item_id: đã gán {$backfilled} dòng, bỏ qua {$skipped} dòng do order_item đã được review khác dùng.");
        }
        fwrite(STDOUT, "  Backfill reviews.order_item_id: đã gán {$backfilled} dòng, bỏ qua {$skipped} dòng (đụng độ order_item).\n");

        // 5) (c) Thêm unique(order_item_id) SAU CÙNG, sau khi đã backfill an toàn.
        Schema::table('reviews', function (Blueprint $table) {
            $table->unique('order_item_id', self::UNIQUE_ORDER_ITEM);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1) Drop FK order_item_id TRƯỚC. Quan trọng: sau khi up() chạy, MySQL chỉ còn
        //    ĐÚNG MỘT chỉ mục trên order_item_id — là `reviews_order_item_id_unique`
        //    (chỉ mục tự động lúc tạo FK không tồn tại song song với unique cùng cột) —
        //    nên nếu drop unique trước khi drop FK sẽ gặp đúng lỗi 1553 "needed in a
        //    foreign key constraint" (đã kiểm chứng thực tế khi test rollback). Vì vậy
        //    phải dropForeign trước, rồi drop cột (dropColumn sẽ tự dọn theo chỉ mục
        //    unique/index chỉ còn nằm trên (các) cột bị xoá, không cần dropUnique riêng).
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
        });

        // 2) Drop 9 cột vừa thêm (kéo theo tự động drop reviews_order_item_id_unique
        //    vì chỉ mục đó nằm trên cột order_item_id đang bị xoá).
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn([
                'order_item_id',
                'variant_name',
                'is_anonymous',
                'helpful_count',
                'is_hidden',
                'shop_reply',
                'shop_replied_at',
                'edited_at',
                'points_awarded',
            ]);
        });

        // 3) Chỉ khôi phục lại unique(product_id, user_id) nếu dữ liệu hiện tại
        //    KHÔNG có cặp (product_id, user_id) trùng nhau (có thể phát sinh nếu ứng dụng
        //    từng cho phép nhiều review/1 order_item trong lúc unique(product_id,user_id)
        //    đã bị gỡ). Nếu có trùng: bỏ qua việc khôi phục unique VÀ giữ nguyên
        //    index(product_id, rating) (không drop) để cột product_id vẫn luôn có chỉ mục
        //    hỗ trợ FK `reviews_product_id_foreign` — tránh lặp lại đúng rủi ro đã nêu ở mục 6
        //    của kế hoạch. Chỉ khi khôi phục unique thành công mới drop index(product_id,rating).
        $duplicates = DB::table('reviews')
            ->select('product_id', 'user_id')
            ->groupBy('product_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->unique(['product_id', 'user_id'], self::UNIQUE_PRODUCT_USER);
            });

            Schema::table('reviews', function (Blueprint $table) {
                $table->dropIndex(self::INDEX_PRODUCT_RATING);
            });
        } else {
            Log::warning(
                'Rollback alter_reviews_for_rich_reviews: bỏ qua khôi phục unique(product_id,user_id) '
                .'vì phát hiện dữ liệu trùng cặp (product_id,user_id); giữ nguyên index(product_id,rating) '
                .'để không vi phạm khoá ngoại reviews_product_id_foreign.',
                ['duplicate_pairs' => $duplicates->toArray()]
            );
            fwrite(STDOUT, '  CẢNH BÁO: phát hiện dữ liệu trùng (product_id,user_id) — bỏ qua khôi phục unique cũ, xem log.'."\n");
        }
    }
};
