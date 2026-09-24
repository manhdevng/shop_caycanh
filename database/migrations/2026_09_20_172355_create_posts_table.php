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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->foreignId('author_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('posts')->insert([
            [
                'title' => 'Hướng dẫn chăm sóc cây',
                'slug' => 'huong-dan-cham-soc-cay',
                'excerpt' => 'Những nguyên tắc cơ bản giúp cây cảnh luôn xanh tốt: tưới nước, ánh sáng, đất trồng và phòng sâu bệnh.',
                'content' => '<p>Tưới nước đúng cách là yếu tố quan trọng hàng đầu khi chăm sóc cây cảnh. Không nên tưới quá nhiều khiến rễ bị úng, '
                    .'cũng không nên để đất khô hạn kéo dài. Với hầu hết cây trong nhà, chỉ cần tưới khi lớp đất mặt đã khô, trung bình '
                    .'2-3 lần mỗi tuần tuỳ điều kiện thời tiết.</p>'
                    .'<p>Ánh sáng ảnh hưởng trực tiếp đến khả năng quang hợp và sinh trưởng của cây. Cây ưa sáng nên đặt gần cửa sổ hoặc '
                    .'ban công có ánh nắng nhẹ vào buổi sáng, trong khi cây ưa bóng có thể đặt trong phòng khách, phòng làm việc. Nên xoay '
                    .'chậu cây định kỳ để cây phát triển đều các hướng.</p>'
                    .'<p>Đất trồng cần tơi xốp, thoát nước tốt và giàu dinh dưỡng. Có thể trộn đất thịt với xơ dừa, trấu hun, phân hữu cơ '
                    .'theo tỷ lệ phù hợp với từng loại cây. Nên thay đất hoặc bổ sung dinh dưỡng định kỳ 3-6 tháng một lần.</p>'
                    .'<p>Để phòng sâu bệnh cơ bản, hãy thường xuyên kiểm tra mặt dưới lá, cắt tỉa lá vàng úa, đảm bảo cây thông thoáng và '
                    .'tránh tưới nước lên lá vào buổi tối để hạn chế nấm mốc.</p>',
                'author_id' => null,
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Tư vấn chọn cây',
                'slug' => 'tu-van-chon-cay',
                'excerpt' => 'Gợi ý cách chọn cây cảnh phù hợp với không gian sống, mệnh phong thuỷ và mục đích sử dụng.',
                'content' => '<p>Trước khi chọn cây, hãy xác định vị trí đặt cây: trong nhà, ban công hay ngoài sân vườn. Không gian trong nhà thiếu '
                    .'sáng nên ưu tiên các loại cây ưa bóng như trầu bà, lưỡi hổ, kim tiền; ban công hoặc sân vườn nhiều nắng phù hợp với hoa '
                    .'hồng, sen đá, cây bụi cảnh.</p>'
                    .'<p>Về mặt phong thuỷ, mỗi mệnh sẽ hợp với một số loại cây và màu sắc riêng. Ví dụ mệnh Mộc hợp cây xanh lá to như trầu '
                    .'bà, mệnh Kim hợp cây có màu trắng hoặc bạc như ngọc ngân, mệnh Hoả hợp cây có hoa đỏ, cam như hồng môn. Tuy nhiên, yếu '
                    .'tố quan trọng nhất vẫn là cây phù hợp với điều kiện chăm sóc thực tế.</p>'
                    .'<p>Nếu chọn cây theo mục đích, cây để bàn làm việc nên nhỏ gọn, ít cần chăm sóc như sen đá, xương rồng mini; cây trang '
                    .'trí phòng khách nên có dáng đẹp, xanh tốt quanh năm như kim ngân, phát tài; cây làm quà tặng thường mang ý nghĩa may '
                    .'mắn, tài lộc như kim tiền, phát lộc.</p>'
                    .'<p>Cuối cùng, người mới bắt đầu nên ưu tiên các loại cây dễ sống, ít sâu bệnh, chịu được sai sót trong chăm sóc trước '
                    .'khi thử sức với các loại cây đòi hỏi kỹ thuật cao hơn.</p>',
                'author_id' => null,
                'is_published' => true,
                'published_at' => $now,
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
        Schema::dropIfExists('posts');
    }
};
