<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway'); // phân biệt các cổng thanh toán khác nhau (ví dụ: PayPal, Stripe, VNPay, Momo, v.v.)
            $table->string('gateway_order_id')->nullable()->index(); // lưu trữ ID đơn hàng từ cổng thanh toán (ví dụ: ID giao dịch từ PayPal, Stripe, VNPay, Momo, v.v.)
            $table->string('transaction_id')->nullable()->index(); // lưu trữ ID giao dịch từ cổng thanh toán (ví dụ: ID giao dịch từ PayPal, Stripe, VNPay, Momo, v.v.)
            $table->decimal('amount', 15, 2); // lưu trữ số tiền thanh toán (ví dụ: số tiền thanh toán từ PayPal, Stripe, VNPay, Momo, v.v.)
            $table->string('status')->default('pending'); // trạng thái giao dịch thanh toán (ví dụ: pending, completed, failed, canceled)
            $table->integer('result_code')->nullable(); // lưu trữ mã kết quả từ cổng thanh toán (ví dụ: mã lỗi từ PayPal, Stripe, VNPay, Momo, v.v.)
            $table->string('message')->nullable(); // lưu trữ thông điệp từ cổng thanh toán (ví dụ: thông báo lỗi từ PayPal, Stripe, VNPay, Momo, v.v.)
            $table->json('request_payload')->nullable(); // lưu trữ dữ liệu yêu cầu gửi đến cổng thanh toán
            $table->json('response_payload')->nullable(); // lưu trữ dữ liệu phản hồi từ cổng thanh toán
            $table->timestamp('paid_at')->nullable(); // lưu trữ thời gian thanh toán thành công (nếu có)
            $table->timestamps();

            $table->unique(['gateway', 'gateway_order_id']); // đảm bảo rằng mỗi cổng thanh toán chỉ có một giao dịch duy nhất cho mỗi ID đơn hàng từ cổng thanh toán
            $table->index(['order_id', 'status']); // tạo chỉ mục để tối ưu hóa truy vấn theo order_id và status, giúp tìm kiếm các giao dịch thanh toán theo đơn hàng và trạng thái nhanh hơn
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
