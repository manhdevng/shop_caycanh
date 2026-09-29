<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nhật ký một lần đổi trạng thái đơn hàng (bảng order_status_histories).
 *
 * Mỗi dòng ghi lại: đổi cột nào (`field`), từ giá trị gì sang giá trị gì,
 * do nguồn nào (`source`), vì lý do gì (`note`), ai thao tác (`actor_id`) và
 * thời điểm THẬT của sự kiện (`occurred_at` — với GHN là trường `Time` trong
 * webhook chứ không phải lúc hệ thống nhận được).
 *
 * Bảng có unique `osh_dedupe_unique(order_id, field, to_value, occurred_at)`
 * để GHN gọi lại webhook 10 lần cũng chỉ sinh 1 dòng — vì vậy luôn ghi bằng
 * insertOrIgnore, không dùng create().
 */
class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'field',
        'from_value',
        'to_value',
        'source',
        'note',
        'actor_id',
        'occurred_at',
    ];

    /** Các giá trị hợp lệ của cột `field`. */
    const FIELD_STATUS = 'status';

    const FIELD_SHIPPING_STATUS = 'shipping_status';

    const FIELD_MILESTONE = 'milestone';

    /**
     * Nhãn tiếng Việt cho `source` — dùng ở timeline admin.
     */
    const SOURCE_LABELS = [
        'customer' => 'Khách hàng',
        'admin' => 'Quản trị viên',
        'system' => 'Hệ thống',
        'momo' => 'MoMo',
        'ghn_webhook' => 'GHN (webhook)',
        'ghn_sync' => 'GHN (đồng bộ)',
        'scheduler' => 'Tự động theo lịch',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCE_LABELS[$this->source] ?? (string) $this->source;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Người thao tác (admin hoặc khách); null khi hệ thống/GHN tự đổi. */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
