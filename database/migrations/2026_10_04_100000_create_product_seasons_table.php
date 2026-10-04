<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T8: một sản phẩm có thể thuộc nhiều mùa (spring/summer/autumn/winter) hoặc
 * được gắn rõ all_year. Bảng chỉ được ghi khi admin chủ động gán mùa (form
 * sản phẩm — Agent 4); migration KHÔNG tự suy đoán mùa cho sản phẩm hiện có.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('season', 20);
            $table->timestamps();

            $table->unique(['product_id', 'season']);
            $table->index('season');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_seasons');
    }
};
