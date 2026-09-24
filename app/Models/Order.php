<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone',
        'total_price',
        'status',
        'shipping_status',
        'voucher_id',
        'discount_amount',
        'points_awarded',
        // GHN:
        'ghn_order_code',
        'ghn_total_fee',
        'to_district_id',
        'to_ward_code',
        // Thanh toán:
        'payment_method',
        'transfer_ref',
        'transfer_confirmed_at',
        'transfer_confirmed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_awarded' => 'boolean',
            'transfer_confirmed_at' => 'datetime',
        ];
    }

    // Hình thức thanh toán (orders.payment_method).
    const PAYMENT_COD = 'cod';
    const PAYMENT_MOMO = 'momo';
    const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    /**
     * Nhãn tiếng Việt cho orders.payment_method.
     */
    const PAYMENT_LABELS = [
        'cod' => 'Thanh toán khi nhận hàng (COD)',
        'momo' => 'MoMo',
        'bank_transfer' => 'Chuyển khoản ngân hàng',
    ];

    /**
     * Nhãn tiếng Việt cho orders.status. Gồm 5 trạng thái hiện hành
     * (pending, awaiting_transfer, paid, cod_ordered, cancelled) và 2 trạng
     * thái cũ vẫn còn tồn tại trong dữ liệu (paid_momo, cod_paid) để đơn cũ
     * không hiển thị chuỗi tiếng Anh thô.
     */
    const STATUS_LABELS = [
        'pending' => 'Chờ thanh toán',
        'awaiting_transfer' => 'Chờ chuyển khoản',
        'paid' => 'Đã thanh toán',
        'paid_momo' => 'Đã thanh toán MoMo',
        'cod_ordered' => 'COD - Đã đặt',
        'cod_paid' => 'COD - Đã thu tiền',
        'cancelled' => 'Đã hủy',
    ];

    /**
     * Nhãn tiếng Việt cho orders.shipping_status — sao y nguyên từ
     * $shippingLabels trong AdminOrderController::index() để dùng chung.
     */
    const SHIPPING_LABELS = [
        'pending' => 'Chờ tạo vận đơn', 'not_shipped' => 'Chưa giao hàng', 'processing' => 'Đang tạo vận đơn',
        'ready_to_pick' => 'Chờ lấy hàng', 'picking' => 'Đang lấy hàng', 'picked' => 'Đã lấy hàng',
        'storing' => 'Đang lưu kho', 'transporting' => 'Đang trung chuyển', 'sorting' => 'Đang phân loại',
        'delivering' => 'Đang giao hàng', 'delivered' => 'Giao hàng thành công',
        'return' => 'Chờ hoàn hàng', 'returning' => 'Đang hoàn hàng', 'returned' => 'Đã hoàn hàng',
        'return_transporting' => 'Đang chuyển hoàn', 'return_sorting' => 'Đang phân loại hoàn', 'cancelled' => 'Đã hủy',
    ];

    /**
     * Nhóm mốc tiến trình giao hàng (dùng để chặn việc đặt shipping_status
     * lùi về giai đoạn trước đó) — nguồn dùng chung cho AdminOrderController
     * (thao tác tay) và GHNWebhookController (GHN báo tự động), tránh định
     * nghĩa luật chuyển trạng thái ở hai nơi khác nhau. Các trạng thái không
     * có mặt ở đây (cancelled, return, returning, returned,
     * return_transporting, return_sorting) là ngoại lệ, không bị ràng buộc
     * bởi thứ tự này vì có thể xảy ra bất kỳ lúc nào.
     */
    const SHIPPING_STAGE_GROUPS = [
        'pending' => 1, 'not_shipped' => 1, 'processing' => 1,
        'ready_to_pick' => 2, 'picking' => 2, 'picked' => 2,
        'storing' => 3, 'transporting' => 3, 'sorting' => 3, 'delivering' => 3,
        'delivered' => 4,
    ];

    /**
     * Nhãn tiếng Việt cho status; nếu giá trị thô không có trong
     * STATUS_LABELS thì trả về chính giá trị thô (không throw, không rỗng).
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? (string) $this->status;
    }

    /**
     * Nhãn tiếng Việt cho shipping_status; NULL trả về chuỗi mặc định,
     * giá trị lạ trả về chính giá trị thô.
     */
    public function getShippingLabelAttribute(): string
    {
        if ($this->shipping_status === null) {
            return 'Chưa có thông tin';
        }

        return self::SHIPPING_LABELS[$this->shipping_status] ?? (string) $this->shipping_status;
    }

    /**
     * Nhãn tiếng Việt cho payment_method; giá trị lạ trả về chính giá trị thô
     * (không throw, không rỗng) — cùng quy ước với getStatusLabelAttribute()
     * và getShippingLabelAttribute() ở trên.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_LABELS[$this->payment_method] ?? (string) $this->payment_method;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Admin đã xác nhận đã nhận được tiền chuyển khoản (orders.transfer_confirmed_by).
    public function transferConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transfer_confirmed_by');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

   
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Một đơn hàng có thể có nhiều lần thử thanh toán (1 - N)
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    // Giao dịch thanh toán mới nhất, tiện hiển thị trạng thái hiện tại
    public function latestPaymentTransaction()
    {
        return $this->hasOne(PaymentTransaction::class)->latestOfMany();
    }
}
