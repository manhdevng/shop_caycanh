<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'scope_type',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'starts_at',
        'expires_at',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Sản phẩm được gán trực tiếp cho mã (khi scope_type = 'products').
     */
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Danh mục được gán cho mã (khi scope_type = 'categories').
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Những user đã lưu mã này vào ví (bảng user_voucher). Pivot mang theo
     * saved_at, used_at (null = chưa dùng), order_id (đơn hàng đã dùng mã).
     */
    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'user_voucher')
            ->withPivot(['saved_at', 'used_at', 'order_id'])
            ->withTimestamps();
    }

    /**
     * Chỉ lấy các voucher đang thực sự dùng được NGAY BÂY GIỜ: đang bật
     * (is_active), đã bắt đầu và chưa hết hạn (starts_at/expires_at), và
     * chưa hết lượt sử dụng (usage_limit). Sắp xếp theo min_order_amount
     * tăng dần để mã dễ đủ điều kiện nhất (không yêu cầu/đơn tối thiểu thấp)
     * hiện lên đầu — NULL (không yêu cầu tối thiểu) được coi như nhỏ nhất
     * nên lên đầu tiên. Dùng ở CartController::index() và
     * User/OrderController::index() để hiển thị danh sách mã khả dụng cho
     * khách (biến $availableVouchers).
     */
    public function scopeAvailable($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            // NULL trước (coi như 0), rồi tăng dần theo giá trị thật.
            ->orderByRaw('min_order_amount IS NULL DESC, min_order_amount ASC');
    }

    /**
     * Mô tả ngắn gọn mức giảm giá, dùng để hiển thị trong danh sách voucher
     * cho khách (trang giỏ hàng/thanh toán). Logic định dạng số GIỐNG HỆT
     * resources/views/admin/vouchers/index.blade.php để nhất quán giữa
     * trang admin và trang khách.
     */
    public function getSummaryAttribute(): string
    {
        if ($this->discount_type === 'percent') {
            $decimals = $this->discount_value == intval($this->discount_value) ? 0 : 2;
            $summary = 'Giảm ' . number_format((float) $this->discount_value, $decimals, ',', '.') . '%';

            if ($this->max_discount_amount !== null) {
                $summary .= ', tối đa ' . number_format((float) $this->max_discount_amount, 0, ',', '.') . 'đ';
            }
        } else {
            $summary = 'Giảm ' . number_format((float) $this->discount_value, 0, ',', '.') . 'đ';
        }

        return $summary . $this->scopeSuffix();
    }

    /**
     * Hậu tố mô tả phạm vi áp dụng, nối vào getSummaryAttribute() khi mã
     * không áp cho toàn shop. Nếu quan hệ 'categories' đã được eager-load
     * thì liệt kê tên (ngắn gọn); nếu chưa load (tránh N+1) thì dùng câu
     * chung chung.
     */
    private function scopeSuffix(): string
    {
        if ($this->scope_type === 'products') {
            return ' · Chỉ áp dụng cho một số sản phẩm';
        }

        if ($this->scope_type === 'categories') {
            if ($this->relationLoaded('categories') && $this->categories->isNotEmpty()) {
                $names = $this->categories->pluck('name')->take(2)->implode(', ');
                $suffix = $this->categories->count() > 2 ? $names . '…' : $names;

                return ' · Chỉ áp dụng cho: ' . $suffix;
            }

            return ' · Chỉ áp dụng cho một số danh mục';
        }

        return '';
    }

    /**
     * Mã này có áp dụng được cho 1 sản phẩm cụ thể không, dựa trên
     * scope_type. Với 'categories', CHỈ khớp đúng danh mục được gán trực
     * tiếp cho sản phẩm — không tự suy ra cây danh mục cha/con (quyết định
     * đã chốt, xem mục 3.5 kế hoạch).
     */
    public function appliesToProduct(Product $product): bool
    {
        if ($this->scope_type === 'products') {
            $ids = $this->relationLoaded('products')
                ? $this->products->pluck('id')
                : $this->products()->pluck('products.id');

            return $ids->contains($product->id);
        }

        if ($this->scope_type === 'categories') {
            $voucherCategoryIds = $this->relationLoaded('categories')
                ? $this->categories->pluck('id')
                : $this->categories()->pluck('categories.id');

            $productCategoryIds = $product->relationLoaded('categories')
                ? $product->categories->pluck('id')
                : $product->categories()->pluck('categories.id');

            return $voucherCategoryIds->intersect($productCategoryIds)->isNotEmpty();
        }

        // scope_type === 'all' (mặc định)
        return true;
    }

    /**
     * Tổng tiền hàng PHÙ HỢP trong 1 giỏ hàng (mảng session('cart') đã lọc
     * theo checkbox nếu có — hàm này KHÔNG tự đọc session, luôn nhận đúng
     * tập mà nơi gọi truyền vào). Với scope 'all' trả về tổng toàn bộ giỏ
     * (giữ nguyên hành vi cũ). Dòng thiếu product_id hoặc sản phẩm không
     * tồn tại bị bỏ qua, không tính vào tổng.
     */
    public function eligibleSubtotal(array $cart): float
    {
        if ($this->scope_type === 'all') {
            return (float) collect($cart)->sum(fn ($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0));
        }

        $productIds = collect($cart)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return 0.0;
        }

        // Nạp 1 lần toàn bộ sản phẩm liên quan kèm danh mục — tránh N+1 khi
        // duyệt từng dòng giỏ hàng bên dưới.
        $products = Product::with('categories')->whereIn('id', $productIds)->get()->keyBy('id');

        // Nạp 1 lần quan hệ products/categories của CHÍNH voucher này (dùng
        // trong appliesToProduct() bên dưới), tránh query lại mỗi vòng lặp.
        if ($this->scope_type === 'products') {
            $this->loadMissing('products');
        } elseif ($this->scope_type === 'categories') {
            $this->loadMissing('categories');
        }

        $total = 0.0;

        foreach ($cart as $item) {
            $productId = $item['product_id'] ?? null;
            $product = $productId !== null ? $products->get($productId) : null;

            if (!$product) {
                continue;
            }

            if ($this->appliesToProduct($product)) {
                $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Kiểm tra đầy đủ điều kiện hợp lệ của voucher đối với 1 giỏ hàng cụ thể
     * (đã lọc theo checkbox nếu có — hàm này KHÔNG tự đọc session('cart'),
     * luôn nhận đúng tập mà nơi gọi truyền vào) và user đang thao tác. Trả
     * về null nếu hợp lệ, ngược lại trả về thông điệp lỗi tiếng Việt tương
     * ứng. Dùng chung cho VoucherController::apply() (kiểm tra sơ bộ) và
     * User/OrderController::store() (kiểm tra lại trong transaction có
     * lockForUpdate, không tin kết quả đã kiểm tra trước đó).
     *
     * THAY THẾ HẲN validationErrorFor(float $total) cũ — bản cũ đã bị xoá vì
     * bỏ qua toàn bộ luật "phải lưu vào ví trước" và "mỗi khách dùng 1 lần".
     */
    public function validationErrorForCart(array $cart, ?User $user): ?string
    {
        if (!$this->is_active) {
            return 'Mã giảm giá đã ngừng áp dụng.';
        }

        $now = now();

        if (($this->starts_at && $now->lt($this->starts_at))
            || ($this->expires_at && $now->gt($this->expires_at))) {
            return 'Mã giảm giá đã hết hạn hoặc chưa bắt đầu.';
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'Mã giảm giá đã hết lượt sử dụng.';
        }

        if ($user === null) {
            return 'Vui lòng đăng nhập để sử dụng mã giảm giá.';
        }

        // 1 truy vấn duy nhất lấy bản ghi ví (nếu có) — dùng cho cả 2 kiểm
        // tra "chưa lưu" và "đã dùng" bên dưới, tránh 2 lượt query riêng.
        $walletEntry = DB::table('user_voucher')
            ->where('user_id', $user->id)
            ->where('voucher_id', $this->id)
            ->first();

        if (!$walletEntry) {
            return 'Bạn cần lưu mã này trước khi sử dụng.';
        }

        if ($walletEntry->used_at !== null) {
            return 'Bạn đã sử dụng mã này rồi.';
        }

        $eligibleSubtotal = $this->eligibleSubtotal($cart);

        if ($eligibleSubtotal <= 0) {
            return 'Giỏ hàng chưa có sản phẩm phù hợp với mã này.';
        }

        if ($this->min_order_amount !== null && $eligibleSubtotal < (float) $this->min_order_amount) {
            $min = number_format((float) $this->min_order_amount, 0, ',', '.');

            return "Cần mua thêm sản phẩm áp dụng mã để đạt tối thiểu {$min}đ.";
        }

        return null;
    }

    // Tính số tiền giảm thực tế cho 1 tổng tiền cho trước: không vượt quá
    // tổng tiền, không âm, và bị chặn trần bởi max_discount_amount nếu là %.
    public function calculateDiscountAmount(float $total): float
    {
        if ($this->discount_type === 'percent') {
            $discount = $total * (float) $this->discount_value / 100;

            if ($this->max_discount_amount !== null) {
                $discount = min($discount, (float) $this->max_discount_amount);
            }
        } else {
            $discount = (float) $this->discount_value;
        }

        return max(0, min($discount, $total));
    }

    /**
     * True nếu $user đã lưu mã này vào ví (có bản ghi user_voucher), bất kể
     * đã dùng hay chưa. Dùng ở view để quyết định hiện nút "Lưu" hay "Dùng".
     */
    public function isSavedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->relationLoaded('savedByUsers')) {
            return $this->savedByUsers->contains('id', $user->id);
        }

        return DB::table('user_voucher')
            ->where('user_id', $user->id)
            ->where('voucher_id', $this->id)
            ->exists();
    }

    /**
     * True nếu $user đã dùng mã này rồi (used_at khác null trong ví).
     */
    public function isUsedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->relationLoaded('savedByUsers')) {
            $entry = $this->savedByUsers->firstWhere('id', $user->id);

            return $entry !== null && $entry->pivot->used_at !== null;
        }

        return DB::table('user_voucher')
            ->where('user_id', $user->id)
            ->where('voucher_id', $this->id)
            ->whereNotNull('used_at')
            ->exists();
    }
}
