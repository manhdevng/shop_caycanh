<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'product_type',
        'base_price',
        'weight',
        'stock',
        'main_image',
        'description',
        'is_active',
        'badge',
        'variant_label',
    ];

    /**
     * Loại sản phẩm. Xem D1 trong "Claude outputs/product-module-fix-plan.md".
     */
    const TYPES = [
        'plant' => 'Cây cảnh',
        'flower' => 'Hoa',
    ];

    /**
     * Nhãn hiển thị cho sản phẩm. `null` (không có trong mảng này) nghĩa là
     * chế độ Tự động — xem displayBadge(). Xem D4.
     */
    const BADGES = [
        'new' => 'Mới',
        'bestseller' => 'Bán chạy',
        'limited' => 'Số lượng có hạn',
        'gift' => 'Quà tặng',
        'none' => 'Không hiện nhãn',
    ];

    /**
     * Gợi ý tên nhóm lựa chọn (variant_label) cho admin chọn trong dropdown.
     * Xem D2.
     */
    const VARIANT_LABELS = [
        'Kích cỡ',
        'Loại chậu',
        'Số bông',
        'Kích thước bó',
        'Màu sắc',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class);
    }

    /**
     * Tất cả mã giảm giá đang còn hiệu lực (Voucher::available()) áp dụng
     * được cho sản phẩm này: mã phạm vi 'all', hoặc mã gắn trực tiếp sản
     * phẩm này, hoặc mã gắn vào ít nhất 1 danh mục mà sản phẩm này thuộc về.
     * Dùng 1 truy vấn duy nhất (whereHas -> subquery), không N+1.
     */
    public function availableVouchers(): \Illuminate\Support\Collection
    {
        $categoryIds = $this->relationLoaded('categories')
            ? $this->categories->pluck('id')
            : $this->categories()->pluck('categories.id');

        return Voucher::available()
            ->where(function ($query) use ($categoryIds) {
                $query->where('scope_type', 'all')
                    ->orWhere(function ($q) {
                        $q->where('scope_type', 'products')
                            ->whereHas('products', function ($q2) {
                                $q2->where('products.id', $this->id);
                            });
                    });

                if ($categoryIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($categoryIds) {
                        $q->where('scope_type', 'categories')
                            ->whereHas('categories', function ($q2) use ($categoryIds) {
                                $q2->whereIn('categories.id', $categoryIds);
                            });
                    });
                }
            })
            ->get();
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('sort_order')
            ->orderBy('price');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }

    public function viewLogs()
    {
        return $this->hasMany(ProductView::class);
    }

    public function scopePlants(Builder $query): Builder
    {
        return $query->where('product_type', 'plant');
    }

    public function scopeFlowers(Builder $query): Builder
    {
        return $query->where('product_type', 'flower');
    }

    /**
     * Tên nhóm lựa chọn hiển thị cho khách: dùng variant_label nếu admin đã
     * đặt, ngược lại mặc định theo loại sản phẩm (Cây -> Kích cỡ, Hoa -> Số
     * bông). Xem D2.
     */
    protected function effectiveVariantLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->variant_label
                ?: ($this->product_type === 'flower' ? 'Số bông' : 'Kích cỡ'),
        );
    }

    /**
     * True nếu sản phẩm còn hàng (stock > 0). Dùng ở Blade dưới dạng
     * $product->in_stock.
     */
    protected function inStock(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->stock > 0,
        );
    }

    /**
     * True nếu sản phẩm có từ 2 phân loại trở lên với giá khác nhau
     * (dùng để hiện "Từ X₫" ở thẻ sản phẩm). Xem D2.
     */
    public function hasPriceRange(): bool
    {
        return $this->variants->pluck('price')->unique()->count() >= 2;
    }

    /**
     * Nhãn hiển thị thực tế cho sản phẩm theo D4:
     *  - badge khác null: dùng nhãn tương ứng trong BADGES, trừ 'none' (ẩn
     *    nhãn) trả về null.
     *  - badge null (Tự động): "Mới" nếu tạo trong 14 ngày; nếu không thì
     *    "Bán chạy" nếu id nằm trong $bestSellerIds; ngược lại null.
     *
     * @param  array<int>  $bestSellerIds
     */
    public function displayBadge(array $bestSellerIds): ?string
    {
        if ($this->badge !== null) {
            if ($this->badge === 'none') {
                return null;
            }

            return self::BADGES[$this->badge] ?? null;
        }

        if ($this->created_at && $this->created_at->diffInDays(now()) <= 14) {
            return self::BADGES['new'];
        }

        if (in_array($this->id, $bestSellerIds, true)) {
            return self::BADGES['bestseller'];
        }

        return null;
    }
}
