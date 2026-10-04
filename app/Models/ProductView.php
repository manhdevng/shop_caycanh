<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductView extends Model
{
    /** Dữ liệu cũ: chỉ người đăng nhập, mỗi lần tải trang một dòng. */
    public const TRACKING_LEGACY = 1;

    /** Ghi qua ProductViewTracker: có khách vãng lai, chống đếm lặp 30 phút. */
    public const TRACKING_DEDUPED = 2;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'viewer_key',
        'product_id',
        'viewed_at',
        'tracking_version',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'tracking_version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Dòng tạo trực tiếp (không qua tracker) vẫn có viewer_key để đếm người xem duy nhất.
        static::creating(function (ProductView $view) {
            if ($view->viewer_key === null && $view->user_id !== null) {
                $view->viewer_key = 'u:'.$view->user_id;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
