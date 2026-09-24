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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('pages')->insert([
            [
                'slug' => 've-chung-toi',
                'title' => 'Về chúng tôi',
                'content' => '<p>Cây Cảnh Shop được thành lập với mong muốn mang không gian xanh mát đến từng ngôi nhà Việt. '
                    .'Chúng tôi tuyển chọn cây cảnh, hoa tươi từ các nhà vườn uy tín, đảm bảo cây khoẻ, hoa đẹp trước khi giao đến tay khách hàng.</p>'
                    .'<p>Với đội ngũ am hiểu về cây trồng, chúng tôi luôn sẵn sàng tư vấn cách chăm sóc phù hợp với từng loại cây, giúp khách hàng '
                    .'yên tâm sở hữu một không gian sống trong lành và tràn đầy sức sống.</p>',
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'lien-he',
                'title' => 'Liên hệ',
                'content' => '<p>Địa chỉ: 123 Đường Hoa Lan, Phường Bình Thạnh, TP. Hồ Chí Minh</p>'
                    .'<p>Hotline: 0909 123 456 (8:00 - 20:00, tất cả các ngày trong tuần)</p>'
                    .'<p>Email: hotro@caycanhshop.vn</p>',
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'van-chuyen-doi-tra',
                'title' => 'Vận chuyển & đổi trả',
                'content' => '<p>Cây Cảnh Shop giao hàng toàn quốc thông qua đối tác vận chuyển, thời gian giao dự kiến từ 1-5 ngày tuỳ khu vực. '
                    .'Đơn hàng được đóng gói cẩn thận để đảm bảo cây, hoa không bị hư hại trong quá trình vận chuyển.</p>'
                    .'<p>Trong vòng 48 giờ kể từ khi nhận hàng, nếu sản phẩm bị dập, héo hoặc không đúng như mô tả, quý khách vui lòng liên hệ '
                    .'hotline hoặc email để được đổi trả hoặc hoàn tiền theo chính sách của cửa hàng.</p>',
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'chinh-sach-bao-hanh',
                'title' => 'Chính sách bảo hành',
                'content' => '<p>Đối với cây cảnh, Cây Cảnh Shop bảo hành 7 ngày kể từ ngày nhận hàng đối với các trường hợp cây chết do lỗi từ phía shop '
                    .'(không do sai cách chăm sóc của khách hàng).</p>'
                    .'<p>Khách hàng vui lòng gửi hình ảnh/video tình trạng cây kèm mã đơn hàng để được hỗ trợ đổi cây mới hoặc hoàn tiền tương ứng.</p>',
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
        Schema::dropIfExists('pages');
    }
};
