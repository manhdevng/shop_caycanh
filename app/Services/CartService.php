<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use DomainException;
use Illuminate\Validation\ValidationException;

/**
 * Logic đặt một dòng hàng vào giỏ (session `cart`), tách ra khỏi
 * CartController@add để nút "Mua ngay" / "Mua với voucher" dùng chung đúng
 * một bộ kiểm tra: sản phẩm còn bán, phân loại hợp lệ, giá > 0, đủ tồn kho.
 *
 * Trước đây logic này nằm trong controller nên "Mua ngay" phải gọi lại
 * `add()` và luôn CỘNG DỒN số lượng (lỗi V2: giỏ có 2 cây, bấm mua ngay 1 cây
 * thì thanh toán 3 cây). Chế độ `set` ở đây đặt ĐÚNG số lượng khách vừa chọn
 * — giống Shopee.
 *
 * Lỗi được ném ra bằng exception có message tiếng Việt sẵn sàng hiển thị:
 * - ValidationException: khách nhập/chọn thiếu (chưa chọn phân loại) — hợp
 *   với luồng form, tự đẩy lỗi vào field tương ứng.
 * - DomainException: trạng thái sản phẩm/kho không cho mua (hết hàng, liên
 *   hệ giá) — không gắn với field nào.
 */
class CartService
{
    /** Cộng dồn vào số lượng đang có trong giỏ (hành vi cũ của nút "Thêm vào giỏ"). */
    public const MODE_ADD = 'add';

    /** Đặt đúng số lượng, ghi đè dòng đang có (nút "Mua ngay"/"Mua với voucher"). */
    public const MODE_SET = 'set';

    /**
     * Đưa 1 sản phẩm (kèm phân loại) vào giỏ trong session.
     *
     * @param  string  $mode  self::MODE_ADD | self::MODE_SET
     * @return array{key: string, item: array, cart_count: int}
     *
     * @throws ValidationException  khách chưa chọn phân loại bắt buộc
     * @throws DomainException      sản phẩm ngừng bán, chưa có giá, hoặc không đủ tồn
     */
    public function putItem(Product $product, ?int $variantId, int $qty, string $mode = self::MODE_ADD): array
    {
        if (! $product->is_active) {
            throw new DomainException('Sản phẩm này hiện không còn được bán.');
        }

        $qty = max(1, $qty);
        $variant = $this->resolveVariant($product, $variantId);
        $price = (float) ($variant->price ?? $product->base_price);

        if ($price <= 0) {
            throw new DomainException('Sản phẩm liên hệ giá');
        }

        // Khoá giỏ hàng theo sản phẩm + phân loại (mỗi phân loại là 1 dòng riêng).
        $key = $variant ? $product->id.'-'.$variant->id : (string) $product->id;

        $cart = session('cart', []);
        $currentQuantity = (int) ($cart[$key]['quantity'] ?? 0);

        // Số lượng cuối cùng của dòng này sau thao tác — chế độ 'set' bỏ qua
        // số đang có, chế độ 'add' cộng dồn.
        $newQuantity = $mode === self::MODE_SET ? $qty : $currentQuantity + $qty;

        // Luôn đối chiếu với tồn kho THẬT trong DB, không tin số client gửi.
        if ($newQuantity > $product->stock) {
            throw new DomainException('Chỉ còn '.$product->stock.' sản phẩm trong kho.');
        }

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = $newQuantity;
            // Giá/ảnh có thể đã đổi từ lần thêm trước -> làm mới theo DB.
            $cart[$key]['price'] = $price;
        } else {
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'name' => $product->name.($variant ? ' - '.$variant->variant_name : ''),
                'quantity' => $newQuantity,
                'price' => $price,
                // Dùng để tính phí vận chuyển GHN.
                'weight' => (int) ($variant->weight ?? $product->weight ?? 200),
                'image' => $variant->image ?? $product->main_image,
                'category' => optional($product->categories->first())->name,
            ];
        }

        session(['cart' => $cart]);

        return [
            'key' => $key,
            'item' => $cart[$key],
            'cart_count' => count($cart),
        ];
    }

    /**
     * Phân loại hợp lệ của sản phẩm, hoặc null khi sản phẩm không có phân loại.
     * Không tin `variant_id` gửi lên: phải thuộc đúng sản phẩm này.
     *
     * @throws ValidationException
     */
    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        if (! $product->variants()->exists()) {
            return null;
        }

        $variant = $variantId !== null ? $product->variants()->find($variantId) : null;

        if (! $variant) {
            throw ValidationException::withMessages([
                'variant_id' => 'Vui lòng chọn '.mb_strtolower($product->effective_variant_label),
            ]);
        }

        return $variant;
    }
}
