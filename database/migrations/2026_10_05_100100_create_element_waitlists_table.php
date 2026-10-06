<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Danh sách nhận tin khi một mệnh có ít cây hợp. Chỉ lưu email, hành và thời
 * gian — không có cột ngày sinh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_waitlists', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('element', 10);
            $table->timestamps();

            $table->unique(['email', 'element']);
            $table->index('element');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_waitlists');
    }
};
