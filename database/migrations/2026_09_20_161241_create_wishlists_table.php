<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Xoá user thì xoá luôn wishlist của họ.
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Xoá sản phẩm thì xoá luôn khỏi wishlist.
            $table->timestamps();

            $table->unique(['user_id', 'product_id']); // Chống thêm trùng cùng 1 sản phẩm vào wishlist.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
