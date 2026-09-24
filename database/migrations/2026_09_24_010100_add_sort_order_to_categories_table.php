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
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('scope')->index();
        });

        // Giữ nguyên thứ tự cũ (đang là orderBy('id')) bằng cách gán sort_order = id cho dữ liệu hiện có.
        DB::statement('UPDATE categories SET sort_order = id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'sort_order')) {
                $table->dropIndex(['sort_order']);
                $table->dropColumn('sort_order');
            }
        });
    }
};
