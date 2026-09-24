<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 255);
            $table->text('answer');
            $table->string('placement', 20)->default('general');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['placement', 'is_published', 'sort_order'], 'faqs_placement_published_sort_index');
        });

        $now = now();

        DB::table('faqs')->insert([
            [
                'question' => 'Cây có dễ chăm sóc không?',
                'answer' => 'Đa số các loại cây tại cửa hàng đều dễ chăm sóc, chỉ cần tưới nước 2-3 lần/tuần và đặt ở nơi có ánh sáng gián tiếp.',
                'placement' => 'general',
                'sort_order' => 1,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => 'Nên đặt cây ở vị trí nào trong nhà?',
                'answer' => 'Nên đặt cây ở nơi có ánh sáng tự nhiên nhẹ, tránh ánh nắng gắt trực tiếp và nơi có gió lùa mạnh.',
                'placement' => 'general',
                'sort_order' => 2,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => 'Chính sách bảo hành cây như thế nào?',
                'answer' => 'Chúng tôi bảo hành cây trong 45 ngày, hỗ trợ đổi cây mới nếu cây gặp vấn đề do lỗi vận chuyển hoặc chất lượng.',
                'placement' => 'general',
                'sort_order' => 3,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => 'Cây có kèm chậu không?',
                'answer' => 'Có, mỗi cây đều được trồng sẵn trong chậu kèm đĩa lót, sẵn sàng trưng bày ngay khi nhận hàng.',
                'placement' => 'product',
                'sort_order' => 1,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => 'Có thể đổi mẫu chậu khác không?',
                'answer' => 'Có, bạn có thể liên hệ cửa hàng trước khi đặt hàng để được tư vấn đổi mẫu chậu phù hợp.',
                'placement' => 'product',
                'sort_order' => 2,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => 'Thời gian giao hàng bao lâu?',
                'answer' => 'Nội thành 1–2 ngày, các tỉnh thành khác 2–5 ngày làm việc tuỳ khu vực.',
                'placement' => 'product',
                'sort_order' => 3,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
