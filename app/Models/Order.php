<?php

namespace App\Models;

use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[ObservedBy([OrderObserver::class])]
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
        // Mốc theo dõi đơn (kế hoạch "luồng sau thanh toán"):
        'delivered_at',
        'completed_at',
        'ghn_expected_delivery_at',
        'ghn_last_synced_at',
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
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'ghn_expected_delivery_at' => 'datetime',
            'ghn_last_synced_at' => 'datetime',
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
        'delivering' => 'Đang giao hàng', 'delivery_fail' => 'Giao không thành công', 'delivered' => 'Giao hàng thành công',
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
        'storing' => 3, 'transporting' => 3, 'sorting' => 3, 'delivering' => 3, 'delivery_fail' => 3,
        'delivered' => 4,
    ];

    /**
     * Các orders.status được coi là "đã thanh toán hoặc COD" — chỉ những đơn
     * này mới được admin đánh dấu shipping_status = delivered (tránh cộng
     * điểm thành viên cho đơn chưa trả tiền). Gồm cả 2 trạng thái cũ
     * paid_momo, cod_paid vẫn còn trong dữ liệu.
     */
    const PAID_OR_COD_STATUSES = ['paid', 'paid_momo', 'cod_ordered', 'cod_paid'];

    /**
     * Các shipping_status thuộc luồng hoàn hàng của GHN.
     */
    const SHIPPING_RETURN_STATUSES = ['return', 'returning', 'returned', 'return_transporting', 'return_sorting'];

    /**
     * Các tab của trang "Đơn mua" (orders.history) — key dùng trong ?tab=,
     * giá trị là nhãn hiển thị. Thứ tự ở đây chính là thứ tự hiển thị.
     * Xem scopeForTab() cho điều kiện lọc tương ứng.
     */
    const TABS = [
        'tat-ca' => 'Tất cả',
        'cho-thanh-toan' => 'Chờ thanh toán',
        'cho-lay-hang' => 'Chờ lấy hàng',
        'dang-giao' => 'Đang giao',
        'da-giao' => 'Đã giao',
        'da-huy' => 'Đã huỷ',
        'tra-hang' => 'Trả hàng',
    ];

    /**
     * shipping_status thuộc giai đoạn "chờ lấy hàng" dưới mắt khách hàng
     * (đã trả tiền/COD nhưng GHN chưa lấy hàng đi).
     */
    const STAGE_TO_SHIP_SHIPPING_STATUSES = ['pending', 'not_shipped', 'processing', 'ready_to_pick', 'picking'];

    /**
     * shipping_status thuộc giai đoạn "đang giao" dưới mắt khách hàng
     * (hàng đã rời kho shop, đang trên đường tới khách).
     */
    const STAGE_SHIPPING_SHIPPING_STATUSES = ['picked', 'storing', 'transporting', 'sorting', 'delivering', 'delivery_fail'];

    /**
     * orders.status khách đang chờ thanh toán (chưa trả tiền, chưa COD).
     */
    const STAGE_AWAITING_PAYMENT_STATUSES = ['pending', 'awaiting_transfer'];

    /**
     * Nhãn tiếng Việt cho từng giai đoạn của customerStage().
     */
    const CUSTOMER_STAGE_LABELS = [
        'awaiting_payment' => 'Chờ thanh toán',
        'to_ship' => 'Chờ lấy hàng',
        'shipping' => 'Đang giao',
        'delivered' => 'Đã giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã huỷ',
        'returning' => 'Trả hàng',
    ];

    /**
     * Luật chuyển shipping_status khi ADMIN đổi tay (AdminOrderController::updateStatus()).
     * Trả về thông báo lỗi tiếng Việt nếu không được phép, NULL nếu được phép.
     * Giữ nguyên trạng thái hiện tại (không đổi) luôn được phép.
     *
     * Luật:
     * - Không cho chọn "cancelled" ở đây: huỷ đơn phải qua nút "Hủy đơn"
     *   (OrderCancellationService lo hoàn kho, voucher, thanh toán, GHN).
     * - Đơn đã hủy (status hoặc shipping_status = cancelled): không đổi gì nữa.
     * - Đơn đã "delivered": trạng thái cuối, không đổi gì nữa (hoàn hàng của
     *   GHN xảy ra trước khi giao thành công nên không cần mở ngoại lệ).
     * - Chỉ đơn đã thanh toán / COD (PAID_OR_COD_STATUSES) mới được đặt "delivered".
     * - Không lùi giai đoạn trong SHIPPING_STAGE_GROUPS; đang ở luồng hoàn
     *   hàng thì không quay lại các giai đoạn giao đi (chỉ đổi qua lại giữa
     *   các trạng thái hoàn hàng).
     *
     * Webhook GHN không dùng hàm này (GHN là nguồn chuẩn), chỉ dùng
     * SHIPPING_STAGE_GROUPS để chặn lùi.
     */
    public function shippingTransitionError(string $newStatus): ?string
    {
        $current = $this->shipping_status;

        if ($newStatus === 'cancelled') {
            return 'Không thể hủy đơn bằng cách đổi trạng thái vận chuyển. Vui lòng dùng nút "Hủy đơn".';
        }

        if ($this->status === 'cancelled' || $current === 'cancelled') {
            return 'Đơn hàng đã hủy, không thể thay đổi trạng thái vận chuyển.';
        }

        if ($newStatus === $current) {
            return null;
        }

        if ($current === 'delivered') {
            return 'Đơn hàng đã giao thành công, không thể thay đổi trạng thái vận chuyển.';
        }

        if ($newStatus === 'delivered' && ! in_array($this->status, self::PAID_OR_COD_STATUSES, true)) {
            return 'Đơn hàng chưa được thanh toán, không thể đánh dấu giao hàng thành công.';
        }

        $currentStage = self::SHIPPING_STAGE_GROUPS[$current] ?? null;
        $newStage = self::SHIPPING_STAGE_GROUPS[$newStatus] ?? null;

        if ($currentStage !== null && $newStage !== null && $newStage < $currentStage) {
            return 'Không thể chuyển trạng thái vận chuyển lùi về giai đoạn trước đó.';
        }

        if (in_array($current, self::SHIPPING_RETURN_STATUSES, true) && $newStage !== null) {
            return 'Đơn đang trong luồng hoàn hàng, không thể chuyển lại trạng thái giao hàng.';
        }

        return null;
    }

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

    /**
     * orders.status cho phép KHÁCH tự huỷ (chưa vào giai đoạn giao hàng, xem
     * canCustomerCancel()). Dùng cùng với CUSTOMER_CANCELLABLE_SHIPPING_STATUSES.
     */
    public const CUSTOMER_CANCELLABLE_STATUSES = ['pending', 'awaiting_transfer', 'cod_ordered'];

    /**
     * shipping_status cho phép KHÁCH tự huỷ — chưa rời kho (chưa "processing"
     * trở lên trong SHIPPING_STAGE_GROUPS, ngoại trừ 'processing' vì đó là lúc
     * hệ thống đang tạo vận đơn, không nên huỷ giữa chừng).
     */
    public const CUSTOMER_CANCELLABLE_SHIPPING_STATUSES = ['pending', 'not_shipped', 'ready_to_pick'];

    /**
     * shipping_status cho phép admin/khách TẠO LẠI vận đơn GHN (đã thanh toán
     * hoặc COD nhưng chưa có ghn_order_code — xem canRetryGhn()).
     */
    public const RETRY_GHN_SHIPPING_STATUSES = ['not_shipped', 'pending'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Đơn có ít nhất 1 giao dịch đã thu tiền thành công (status = 'paid') hay
    // chưa — dùng relation đã eager-load nếu có (tránh N+1 ở orders.show/history).
    private function hasPaidPaymentTransaction(): bool
    {
        if ($this->relationLoaded('paymentTransactions')) {
            return $this->paymentTransactions->contains(fn (PaymentTransaction $t) => $t->status === 'paid');
        }

        return $this->paymentTransactions()->where('status', 'paid')->exists();
    }

    // Giao dịch MoMo mới nhất của đơn (theo id), dùng relation đã eager-load nếu có.
    private function latestMomoTransaction(): ?PaymentTransaction
    {
        if ($this->relationLoaded('paymentTransactions')) {
            return $this->paymentTransactions
                ->where('gateway', 'momo')
                ->sortByDesc('id')
                ->first();
        }

        return $this->paymentTransactions()->where('gateway', 'momo')->latest('id')->first();
    }

    // C1: khách được bấm "Thanh toán lại" MoMo — đơn đang chờ thanh toán MoMo
    // (chưa huỷ, chưa hoàn tất, chưa có giao dịch nào đã thu tiền).
    public function canRetryMomo(): bool
    {
        return $this->payment_method === self::PAYMENT_MOMO
            && $this->status === 'pending'
            && ! $this->hasPaidPaymentTransaction();
    }

    // C2: lần thử thanh toán MoMo gần nhất của đơn đã thất bại — dùng để báo
    // khách thay cho orders.status = 'payment_failed' (giá trị không tồn tại).
    public function lastPaymentFailed(): bool
    {
        if ($this->payment_method !== self::PAYMENT_MOMO) {
            return false;
        }

        return optional($this->latestMomoTransaction())->status === 'failed';
    }

    // C3: khách tự huỷ được đơn — chưa vào giai đoạn giao hàng và chưa thu tiền.
    public function canCustomerCancel(): bool
    {
        return in_array($this->status, self::CUSTOMER_CANCELLABLE_STATUSES, true)
            && in_array($this->shipping_status, self::CUSTOMER_CANCELLABLE_SHIPPING_STATUSES, true)
            && ! $this->hasPaidPaymentTransaction();
    }

    // C4: nội dung chuyển khoản chuẩn để đối soát (admin tìm đơn theo tiền
    // tố "DH" — xem AdminOrderController::index()). Bản demo: khách tự nhập
    // tài khoản ngân hàng theo config('services.bank') và ghi đúng nội dung
    // này, không dùng QR.
    public function transferContent(): string
    {
        return 'DH'.$this->id;
    }

    // C5: được phép tạo lại vận đơn GHN — đã thanh toán/COD, chưa có mã vận
    // đơn, và chưa rời khỏi giai đoạn "chờ tạo vận đơn".
    public function canRetryGhn(): bool
    {
        return in_array($this->status, self::PAID_OR_COD_STATUSES, true)
            && blank($this->ghn_order_code)
            && in_array($this->shipping_status, self::RETRY_GHN_SHIPPING_STATUSES, true);
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

    /**
     * Nhật ký đổi trạng thái của đơn, cũ → mới theo thời điểm THẬT của sự
     * kiện (occurred_at), phá hoà bằng id để thứ tự luôn ổn định.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)
            ->orderBy('occurred_at')
            ->orderBy('id');
    }

    /**
     * Giai đoạn đơn hàng dưới mắt KHÁCH (khác với status/shipping_status thô
     * trong DB): awaiting_payment|to_ship|shipping|delivered|completed|
     * cancelled|returning. Xem bảng mục 1 của kế hoạch.
     *
     * Thứ tự kiểm tra quan trọng: huỷ và hoàn hàng là "thoát luồng" nên xét
     * trước; hoàn thành (khách đã xác nhận nhận hàng) đè lên "đã giao".
     */
    public function customerStage(): string
    {
        if ($this->status === 'cancelled' || $this->shipping_status === 'cancelled') {
            return 'cancelled';
        }

        if (in_array($this->shipping_status, self::SHIPPING_RETURN_STATUSES, true)) {
            return 'returning';
        }

        if ($this->completed_at !== null) {
            return 'completed';
        }

        if ($this->shipping_status === 'delivered') {
            return 'delivered';
        }

        if (in_array($this->status, self::STAGE_AWAITING_PAYMENT_STATUSES, true)) {
            return 'awaiting_payment';
        }

        if (in_array($this->shipping_status, self::STAGE_SHIPPING_SHIPPING_STATUSES, true)) {
            return 'shipping';
        }

        return 'to_ship';
    }

    /**
     * Nhãn tiếng Việt của customerStage() — dùng thẳng trong view
     * ($order->customer_stage_label).
     */
    public function getCustomerStageLabelAttribute(): string
    {
        $stage = $this->customerStage();

        return self::CUSTOMER_STAGE_LABELS[$stage] ?? $stage;
    }

    /**
     * Lọc đơn theo tab của trang "Đơn mua". Tab lạ (hoặc 'tat-ca') không lọc
     * gì — validate ở controller, ở đây chỉ im lặng bỏ qua để không vỡ trang.
     *
     * Điều kiện phải khớp với customerStage() ở trên, nhưng viết bằng SQL để
     * còn phân trang và đếm được.
     */
    public function scopeForTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'cho-thanh-toan' => $query->whereIn('status', self::STAGE_AWAITING_PAYMENT_STATUSES),

            'cho-lay-hang' => $query
                ->whereIn('status', self::PAID_OR_COD_STATUSES)
                ->whereIn('shipping_status', self::STAGE_TO_SHIP_SHIPPING_STATUSES)
                ->whereNull('completed_at'),

            'dang-giao' => $query
                ->where('status', '!=', 'cancelled')
                ->whereIn('shipping_status', self::STAGE_SHIPPING_SHIPPING_STATUSES)
                ->whereNull('completed_at'),

            // "Đã giao" gộp luôn đơn đã hoàn thành: khách vẫn coi đó là đơn
            // đã nhận được hàng, không cần tách thêm một tab nữa.
            'da-giao' => $query
                ->where('status', '!=', 'cancelled')
                ->where('shipping_status', 'delivered'),

            'da-huy' => $query->where(function (Builder $q) {
                $q->where('status', 'cancelled')->orWhere('shipping_status', 'cancelled');
            }),

            'tra-hang' => $query->whereIn('shipping_status', self::SHIPPING_RETURN_STATUSES),

            default => $query,
        };
    }

    /**
     * Khách được bấm "Đã nhận được hàng" — GHN đã báo giao thành công nhưng
     * khách chưa xác nhận.
     */
    public function canConfirmReceived(): bool
    {
        return $this->shipping_status === 'delivered' && $this->completed_at === null;
    }

    /**
     * Khách được đánh giá các sản phẩm trong đơn — đã trả tiền (hoặc COD đã
     * thu), hàng đã giao tới nơi, VÀ còn trong thời hạn đánh giá.
     *
     * Sửa lỗi L12: trước đây chỉ chấp nhận paid/cod_ordered nên đơn COD (đã
     * đổi sang cod_paid sau khi giao) bị chặn đánh giá, còn đơn chưa giao lại
     * đánh giá được. Bổ sung thời hạn theo luật B2 của kế hoạch "mua với
     * voucher + đánh giá": quá hạn thì đơn không còn mời đánh giá nữa.
     */
    public function canReview(): bool
    {
        return in_array($this->status, self::PAID_OR_COD_STATUSES, true)
            && $this->shipping_status === 'delivered'
            && $this->withinReviewWindow();
    }

    /**
     * Còn trong cửa sổ đánh giá (mặc định 30 ngày kể từ lúc GHN báo giao
     * thành công). Đơn đã giao nhưng thiếu `delivered_at` (dữ liệu cũ trước
     * khi có cột này) được coi là CÒN hạn — thà cho đánh giá muộn còn hơn
     * chặn oan khách vì dữ liệu lịch sử thiếu mốc thời gian.
     */
    public function withinReviewWindow(): bool
    {
        if ($this->delivered_at === null) {
            return true;
        }

        $days = max(1, (int) config('shop.review_window_days', 30));

        return $this->delivered_at->copy()->addDays($days)->isFuture();
    }

    /**
     * Các dòng hàng trong đơn kèm trạng thái đánh giá, dùng cho trang
     * /orders/{order}/danh-gia và cho ShopController::show().
     *
     * Trả Collection các mảng {item, product, review|null, can_review,
     * can_edit} theo hợp đồng mục 4.4. Eager load sẵn items.product và
     * reviews của chính $user để không sinh N+1 khi view duyệt từng dòng.
     *
     * @return \Illuminate\Support\Collection<int, array{item: OrderItem, product: ?Product, review: ?Review, can_review: bool, can_edit: bool}>
     */
    public function reviewableLines(?User $user): Collection
    {
        if ($user === null || $this->user_id !== $user->id) {
            return collect();
        }

        $this->loadMissing('items.product');

        // 1 truy vấn lấy hết đánh giá của khách trong đơn này, khoá theo
        // order_item_id để tra cứu O(1) trong vòng lặp bên dưới.
        $reviews = Review::where('order_id', $this->id)
            ->where('user_id', $user->id)
            ->whereNotNull('order_item_id')
            ->with('images')
            ->get()
            ->keyBy('order_item_id');

        $orderIsReviewable = $this->canReview();

        return $this->items->map(function (OrderItem $item) use ($reviews, $orderIsReviewable, $user) {
            $review = $reviews->get($item->id);

            return [
                'item' => $item,
                'product' => $item->product,
                'review' => $review,
                // Chưa đánh giá dòng này và đơn còn đủ điều kiện -> viết mới.
                'can_review' => $orderIsReviewable && $review === null,
                // Đã đánh giá -> chỉ còn quyền sửa (1 lần, trong hạn).
                'can_edit' => $review !== null && $review->canBeEditedBy($user),
            ];
        })->values();
    }

    /** Đơn còn ít nhất 1 dòng hàng chưa đánh giá (và vẫn đủ điều kiện). */
    public function hasPendingReviews(?User $user = null): bool
    {
        $user ??= $this->relationLoaded('user') ? $this->user : $this->user()->first();

        return $this->reviewableLines($user)->contains(fn (array $line) => $line['can_review']);
    }

    /**
     * Tiền hàng trước phí ship và giảm giá (tổng price * quantity của items).
     * Dùng quan hệ đã eager-load nếu có để không sinh thêm truy vấn.
     */
    public function subtotal(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum(fn (OrderItem $item) => $item->price * $item->quantity);
        }

        return (int) $this->items()->selectRaw('COALESCE(SUM(price * quantity), 0) AS s')->value('s');
    }

    /**
     * Link tra cứu vận đơn trên trang của GHN; null khi đơn chưa có mã.
     */
    public function ghnTrackingUrl(): ?string
    {
        if (blank($this->ghn_order_code)) {
            return null;
        }

        return 'https://donhang.ghn.vn/?order_code='.urlencode($this->ghn_order_code);
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
