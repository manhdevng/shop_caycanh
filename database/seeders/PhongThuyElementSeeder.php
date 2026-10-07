<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\PhongThuyService;
use Illuminate\Database\Seeder;

/**
 * Gán hành phong thủy cho các cây CHƯA có hành — cùng quy tắc với lệnh
 * `php artisan phong-thuy:goi-y-hanh --apply` (PhongThuyService::
 * suggestForProduct: theo màu chủ đạo nếu có, không thì theo tên).
 *
 * Idempotent: không bao giờ ghi đè cây đã có hành (kể cả hành admin gán tay),
 * chạy lại nhiều lần không đổi gì. Được gọi từ DatabaseSeeder để môi trường
 * Render/Aiven (chạy db:seed khi deploy) cũng có dữ liệu cho trang Cây hợp mệnh.
 */
class PhongThuyElementSeeder extends Seeder
{
    public function run(PhongThuyService $phongThuy): void
    {
        $assigned = 0;
        $unmatched = [];

        Product::plants()
            ->doesntHave('elements')
            ->orderBy('id')
            ->get(['id', 'name', 'feng_shui_colors'])
            ->each(function (Product $product) use ($phongThuy, &$assigned, &$unmatched) {
                $codes = $phongThuy->suggestForProduct($product);
                if ($codes === []) {
                    $unmatched[] = "#{$product->id} {$product->name}";

                    return;
                }
                $product->syncElements($codes);
                $assigned++;
            });

        $this->command?->info("PhongThuyElementSeeder: đã gán hành cho {$assigned} cây.");
        if ($unmatched) {
            $this->command?->warn('Cần gán tay trong admin: '.implode(', ', $unmatched));
        }
    }
}
