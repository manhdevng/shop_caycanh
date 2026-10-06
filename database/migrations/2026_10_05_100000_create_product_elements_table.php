<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cây hợp mệnh: một sản phẩm có thể thuộc nhiều hành (kim/moc/thuy/hoa/tho).
 * Bảng chỉ được ghi khi admin chủ động gán hành; migration KHÔNG tự suy đoán
 * hành cho sản phẩm hiện có.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('element', 10);
            $table->timestamps();

            $table->unique(['product_id', 'element']);
            $table->index('element');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_elements');
    }
};
