<?php

namespace App\Support;

use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;

/**
 * "Giá sau voucher" cho trang sản phẩm — chọn mã giảm nhiều nhất mà khách
 * thật sự dùng được cho RIÊNG sản phẩm đang xem.
 *
 * Nguyên tắc: lớp này chỉ TÍNH ĐỂ HIỂN THỊ. Số tiền thật khi đặt hàng vẫn do
 * Voucher::validationErrorForCart() + calculateDiscountAmount() quyết định ở
 * checkout, nên hai nơi không bao giờ lệch nhau: cả hai gọi chung
 * calculateDiscountAmount().
 *
 * KHÔNG cache: giá sau voucher phụ thuộc user (mã đã dùng), số lượng, phân
 * loại và cả `used_count` của mã — cache lại sẽ hiện giá sai cho người khác.
 * Các truy vấn ở đây đều nhỏ (availableVouchers() đã lọc sẵn theo phạm vi).
 */
class VoucherPricing
{
    /**
     * Mã tốt nhất cho $qty sản phẩm ở đơn giá $unitPrice.
     *
     * @return array{voucher: Voucher, discount: float, final_total: float, final_unit: float}|null
     */
    public static function bestFor(Product $product, float $unitPrice, int $qty, ?User $user): ?array
    {
        $total = $unitPrice * max(1, $qty);
        $best = null;

        foreach (self::candidatesFor($product, $user) as $voucher) {
            // Chưa đạt đơn tối thiểu -> mã này không áp được cho riêng dòng
            // hàng đang xem (gợi ý "mua thêm" do nextTierHint() lo).
            if ($voucher->min_order_amount !== null && $total < (float) $voucher->min_order_amount) {
                continue;
            }

            $discount = $voucher->calculateDiscountAmount($total);

            if ($discount <= 0) {
                continue;
            }

            if ($best === null || self::beats($voucher, $discount, $best['voucher'], $best['discount'])) {
                $best = ['voucher' => $voucher, 'discount' => $discount];
            }
        }

        if ($best === null) {
            return null;
        }

        $finalTotal = max(0, $total - $best['discount']);

        return [
            'voucher' => $best['voucher'],
            'discount' => $best['discount'],
            'final_total' => $finalTotal,
            'final_unit' => $finalTotal / max(1, $qty),
        ];
    }

    /**
     * Gợi ý kích cầu: mã giảm NHIỀU HƠN mã đang áp được, nhưng khách chưa đạt
     * đơn tối thiểu — "Mua thêm 150.000đ để được giảm 50.000đ với mã XYZ".
     *
     * Trả null khi không có mã nào đáng gợi ý (không có mã chặn bởi
     * min_order_amount, hoặc mã đó cũng không giảm hơn mã hiện tại).
     *
     * @return array{voucher: Voucher, need_more: float, discount: float}|null
     */
    public static function nextTierHint(Product $product, float $unitPrice, int $qty, ?User $user): ?array
    {
        $total = $unitPrice * max(1, $qty);
        $current = self::bestFor($product, $unitPrice, $qty, $user);
        $currentDiscount = $current['discount'] ?? 0.0;

        $hint = null;

        foreach (self::candidatesFor($product, $user) as $voucher) {
            $min = $voucher->min_order_amount === null ? 0.0 : (float) $voucher->min_order_amount;

            // Chỉ quan tâm mã đang BỊ CHẶN vì chưa đủ đơn tối thiểu.
            if ($min <= $total) {
                continue;
            }

            // Số tiền giảm khi khách mua đủ mức tối thiểu — đó là con số đáng
            // để khách cân nhắc mua thêm.
            $discount = $voucher->calculateDiscountAmount($min);

            if ($discount <= $currentDiscount) {
                continue;
            }

            $needMore = $min - $total;

            // Ưu tiên mã giảm nhiều hơn; giảm bằng nhau thì mã cần mua thêm ít hơn.
            if ($hint === null
                || $discount > $hint['discount']
                || ($discount === $hint['discount'] && $needMore < $hint['need_more'])) {
                $hint = ['voucher' => $voucher, 'need_more' => $needMore, 'discount' => $discount];
            }
        }

        return $hint;
    }

    /**
     * Bảng giá sau voucher cho TỪNG phân loại ở số lượng 1, để JS đổi giá
     * ngay khi khách chọn phân loại khác mà không phải gọi lại server.
     *
     * Khoá: `'base'` cho sản phẩm không phân loại, còn lại là variant_id.
     *
     * @return array<int|string, array{price: float, voucher_code: ?string, discount: float, final_unit: float, min_order: ?float}>
     */
    public static function variantMatrix(Product $product, ?User $user): array
    {
        $product->loadMissing('variants');

        $rows = [];

        // Danh sách [khoá => đơn giá]: sản phẩm có phân loại thì mỗi phân
        // loại một dòng, không có thì đúng 1 dòng 'base' theo base_price.
        $entries = $product->variants->isNotEmpty()
            ? $product->variants->mapWithKeys(fn ($v) => [$v->id => (float) $v->price])->all()
            : ['base' => (float) $product->base_price];

        foreach ($entries as $key => $price) {
            $best = self::bestFor($product, $price, 1, $user);

            $rows[$key] = [
                'price' => $price,
                'voucher_code' => $best['voucher']->code ?? null,
                'discount' => $best['discount'] ?? 0.0,
                'final_unit' => $best['final_unit'] ?? $price,
                'min_order' => isset($best['voucher']) && $best['voucher']->min_order_amount !== null
                    ? (float) $best['voucher']->min_order_amount
                    : null,
            ];
        }

        return $rows;
    }

    /**
     * Các mã KHÁCH NÀY còn dùng được cho sản phẩm này: đã lọc hạn/lượt/phạm
     * vi bởi Product::availableVouchers(), ở đây chỉ loại thêm mã khách đã
     * dùng rồi. Khách chưa đăng nhập: không loại gì (isUsedBy(null) = false)
     * — vẫn cho xem giá sau voucher để kích cầu, lúc bấm mua mới bắt đăng nhập.
     *
     * @return \Illuminate\Support\Collection<int, Voucher>
     */
    public static function candidatesFor(Product $product, ?User $user): \Illuminate\Support\Collection
    {
        return $product->availableVouchers()
            ->reject(fn (Voucher $voucher) => $voucher->isUsedBy($user))
            ->values();
    }

    /**
     * Luật so sánh 2 mã: giảm nhiều hơn thì thắng; giảm BẰNG NHAU thì mã hết
     * hạn sớm hơn thắng (dùng trước kẻo phí), mã không có hạn xếp sau cùng.
     */
    private static function beats(Voucher $candidate, float $candidateDiscount, Voucher $current, float $currentDiscount): bool
    {
        if ($candidateDiscount !== $currentDiscount) {
            return $candidateDiscount > $currentDiscount;
        }

        if ($candidate->expires_at === null) {
            return false;
        }

        if ($current->expires_at === null) {
            return true;
        }

        return $candidate->expires_at->lt($current->expires_at);
    }
}
