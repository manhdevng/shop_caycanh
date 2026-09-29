<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Đánh giá sản phẩm, gắn với MỘT DÒNG HÀNG cụ thể trong đơn
 * (`order_item_id`, unique) chứ không phải "mỗi khách 1 đánh giá / sản phẩm"
 * như trước — nhờ vậy khách mua lại lần 2 vẫn đánh giá được, và mỗi đánh giá
 * biết rõ đã mua phân loại nào (`variant_name` chụp lại tên phân loại tại
 * thời điểm mua, để phân loại bị xoá sau đó vẫn hiển thị đúng).
 */
class Review extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'comment',
        'order_id',
        'order_item_id',
        'variant_name',
        'is_anonymous',
        'helpful_count',
        'is_hidden',
        'shop_reply',
        'shop_replied_at',
        'edited_at',
        'points_awarded',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'helpful_count' => 'integer',
            'is_anonymous' => 'boolean',
            'is_hidden' => 'boolean',
            'points_awarded' => 'boolean',
            'shop_replied_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Dòng hàng đã mua sinh ra đánh giá này (null với dữ liệu cũ chưa backfill được). */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(ReviewHelpfulVote::class);
    }

    /**
     * Chỉ các đánh giá khách được nhìn thấy (admin chưa ẩn). Mọi truy vấn
     * hiển thị ra ngoài PHẢI đi qua scope này.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Tên hiển thị của người đánh giá. Khi khách chọn ẩn danh thì che giữa
     * theo kiểu Shopee: giữ ký tự đầu + ký tự cuối, ở giữa là ***.
     * Tên 1 ký tự trả về "***" (không lộ gì), tên 2 ký tự giữ đầu và cuối.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) ($this->user?->name ?? ''));

        if ($name === '') {
            $name = 'Khách hàng';
        }

        if (! $this->is_anonymous) {
            return $name;
        }

        $length = mb_strlen($name);

        if ($length <= 1) {
            return '***';
        }

        return mb_substr($name, 0, 1).'***'.mb_substr($name, -1);
    }

    /**
     * Khách được SỬA đánh giá này không: phải là chủ đánh giá, chưa từng sửa
     * (`edited_at` null — mỗi đánh giá chỉ sửa 1 lần) và vẫn còn trong thời
     * hạn đánh giá tính từ lúc đơn được giao.
     */
    public function canBeEditedBy(?User $user): bool
    {
        if ($user === null || $this->user_id !== $user->id) {
            return false;
        }

        if ($this->edited_at !== null) {
            return false;
        }

        $order = $this->relationLoaded('order') ? $this->order : $this->order()->first();

        return $order !== null && $order->withinReviewWindow();
    }

    /** Đánh giá có ít nhất 1 ảnh — dùng cho bộ lọc "Có hình ảnh". */
    public function hasImages(): bool
    {
        if ($this->relationLoaded('images')) {
            return $this->images->isNotEmpty();
        }

        return $this->images()->exists();
    }

    /**
     * Đủ điều kiện thưởng điểm: có ảnh VÀ nhận xét đủ dài. Kiểm tra bằng
     * mb_strlen vì nhận xét tiếng Việt có dấu.
     */
    public function qualifiesForReward(): bool
    {
        return $this->hasImages() && mb_strlen(trim((string) $this->comment)) >= 50;
    }
}
