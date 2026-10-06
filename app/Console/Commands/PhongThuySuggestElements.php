<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\PhongThuyService;
use Illuminate\Console\Command;

/**
 * Gợi ý hành phong thủy cho các cây chưa gán hành, theo tên cây
 * (config/phong_thuy.php → element_keywords).
 *
 * Mặc định chỉ in bảng để duyệt; thêm --apply mới ghi vào product_elements.
 * Không bao giờ ghi đè cây đã có hành.
 */
class PhongThuySuggestElements extends Command
{
    protected $signature = 'phong-thuy:goi-y-hanh {--apply : Ghi các gợi ý vào CSDL sau khi in bảng}';

    protected $description = 'Gợi ý hành phong thủy theo tên cho các cây cảnh chưa gán hành';

    public function handle(PhongThuyService $phongThuy): int
    {
        $products = Product::where('product_type', 'plant')
            ->doesntHave('elements')
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($products->isEmpty()) {
            $this->info('Không còn cây nào chưa gán hành.');

            return self::SUCCESS;
        }

        $rows = [];
        $toApply = [];
        foreach ($products as $product) {
            $codes = $phongThuy->suggestElements($product->name);
            $rows[] = [
                $product->id,
                $product->name,
                $codes ? implode(', ', array_map(fn ($c) => Product::ELEMENTS[$c], $codes)) : '— (không khớp, gán tay)',
            ];
            if ($codes) {
                $toApply[] = [$product, $codes];
            }
        }

        $this->table(['ID', 'Tên cây', 'Hành gợi ý'], $rows);
        $this->line(count($toApply).'/'.$products->count().' cây có gợi ý.');

        if (! $this->option('apply')) {
            $this->comment('Chưa ghi gì. Chạy lại với --apply để lưu các gợi ý trên.');

            return self::SUCCESS;
        }

        foreach ($toApply as [$product, $codes]) {
            $product->syncElements($codes);
        }
        $this->info('Đã gán hành cho '.count($toApply).' cây.');

        return self::SUCCESS;
    }
}
