<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Một tấm ảnh kèm theo đánh giá (bảng review_images).
 *
 * `path` là đường dẫn TƯƠNG ĐỐI trên disk 'public' (vd
 * `reviews/12/a1b2c3.jpg`), không bao giờ là URL đầy đủ hay tên file gốc do
 * khách tải lên — xem ReviewController::storeForOrder().
 */
class ReviewImage extends Model
{
    protected $fillable = [
        'review_id',
        'path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /** URL công khai để nhúng vào thẻ <img>. */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
