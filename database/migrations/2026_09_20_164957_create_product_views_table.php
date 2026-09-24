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
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Xoá user thì xoá luôn lịch sử xem của họ.
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Xoá sản phẩm thì xoá luôn khỏi lịch sử xem.
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['user_id', 'viewed_at']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};
