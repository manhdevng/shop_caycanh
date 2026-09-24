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
        Schema::rename('product_colors', 'product_variants');
        Schema::table('product_variants', function (Blueprint $table) {
            $table->renameColumn('color_name', 'variant_name');
            $table->dropColumn('color_hex');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('color_hex')->nullable();
            $table->renameColumn('variant_name', 'color_name');
        });
        Schema::rename('product_variants', 'product_colors');
    }
};
