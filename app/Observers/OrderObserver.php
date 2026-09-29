<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Notifications\OrderStatusChanged;
use App\Support\OrderChangeContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Điểm DUY NHẤT ghi nhật ký trạng thái đơn + báo khách.
 *
 * Mọi luồng trong dự án (khách đặt hàng, MoMo IPN, admin đổi tay, webhook
 * GHN, lệnh tự huỷ) đều đổi trạng thái bằng Eloquent `->update()`, nên gom
 * việc ghi history vào Observer là cách duy nhất không phải rải code ghi log
 * ở 6 nơi khác nhau và không sợ quên một nơi. Observer không biết "ai đổi",
 * nên nó đọc ngữ cảnh từ App\Support\OrderChangeContext mà caller set trước.
 *
 * Hai điều bắt buộc phải nhớ khi sửa file này:
 * - Cập nhật delivered_at/completed_at bằng saveQuietly() để không gọi lại
 *   chính Observer này (vòng lặp vô hạn).
 * - Gửi notification trong DB::afterCommit(): webhook GHN và luồng huỷ đơn
 *   chạy trong transaction, nếu gửi ngay thì khách có thể nhận thông báo về
 *   một thay đổi sau đó bị rollback.
 */
class OrderObserver
{
    /**
     * Các mốc khách thật sự quan tâm — chỉ những sự kiện này mới sinh
     * notification. Các trạng thái trung gian của GHN (storing, transporting,
     * sorting) vẫn vào timeline nhưng KHÔNG báo, tránh spam chuông mỗi lần
     * hàng chuyển kho.
     */
    private const NOTIFIABLE_EVENTS = [
        'placed', 'paid', 'transfer_rejected', 'ready_to_pick', 'picked', 'delivering',
        'delivery_fail', 'delivered', 'completed', 'cancelled', 'return', 'returned',
    ];

    /**
     * Đơn vừa được tạo — ghi mốc "placed" để timeline luôn có điểm bắt đầu
     * (migration backfill cũng dùng đúng cặp field/to_value này).
     */
    public function created(Order $order): void
    {
        $this->writeHistory($order, OrderStatusHistory::FIELD_MILESTONE, null, 'placed');
        $this->notify($order, 'placed');
    }

    /**
     * Đơn vừa đổi trạng thái. Ghi history cho từng cột đổi, tự đặt mốc
     * delivered_at, rồi báo khách sau khi transaction đã commit.
     */
    public function updated(Order $order): void
    {
        $events = [];

        foreach ([OrderStatusHistory::FIELD_STATUS, OrderStatusHistory::FIELD_SHIPPING_STATUS] as $field) {
            if (! $order->wasChanged($field)) {
                continue;
            }

            $from = $order->getOriginal($field);
            $to = $order->{$field};

            if ($to === null) {
                continue;
            }

            $this->writeHistory($order, $field, $from === null ? null : (string) $from, (string) $to);
            $events[] = (string) $to;
        }

        // GHN báo giao thành công -> chốt mốc delivered_at để lệnh
        // orders:auto-complete biết đếm từ lúc nào. saveQuietly: không chạy
        // lại Observer.
        if ($order->wasChanged(OrderStatusHistory::FIELD_SHIPPING_STATUS)
            && $order->shipping_status === 'delivered'
            && $order->delivered_at === null) {
            $order->delivered_at = $this->occurredAt();
            $order->saveQuietly();
        }

        // Khách bấm "Đã nhận được hàng" (hoặc lệnh tự động) -> mốc riêng,
        // không phải một giá trị của status/shipping_status nên ghi là milestone.
        if ($order->wasChanged('completed_at') && $order->completed_at !== null) {
            $this->writeHistory($order, OrderStatusHistory::FIELD_MILESTONE, null, 'completed');
            $events[] = 'completed';
        }

        foreach ($events as $event) {
            $this->notify($order, $event);
        }
    }

    /**
     * Ghi 1 dòng history bằng insertOrIgnore: bảng có unique
     * osh_dedupe_unique(order_id, field, to_value, occurred_at) nên GHN gọi
     * lại webhook 10 lần cũng chỉ còn 1 dòng, và lệnh đồng bộ bù chạy lại
     * không nhân đôi timeline.
     */
    private function writeHistory(Order $order, string $field, ?string $from, string $to): void
    {
        $now = now();

        DB::table('order_status_histories')->insertOrIgnore([
            'order_id' => $order->id,
            'field' => $field,
            'from_value' => $from === null ? null : mb_substr($from, 0, 40),
            'to_value' => mb_substr($to, 0, 40),
            'source' => (string) (OrderChangeContext::get('source') ?? 'system'),
            'note' => $this->note(),
            'actor_id' => OrderChangeContext::get('actor_id'),
            'occurred_at' => $this->occurredAt(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Thời điểm THẬT của sự kiện: với webhook/đồng bộ GHN là trường `Time`
     * do GHN gửi (có thể trễ vài phút so với lúc ta nhận), còn lại là bây giờ.
     */
    private function occurredAt(): Carbon
    {
        $occurredAt = OrderChangeContext::get('occurred_at');

        if ($occurredAt instanceof \DateTimeInterface) {
            return Carbon::instance($occurredAt);
        }

        if (is_string($occurredAt) && $occurredAt !== '') {
            try {
                return Carbon::parse($occurredAt);
            } catch (\Throwable $e) {
                // Giá trị lạ từ GHN không được phép làm hỏng việc ghi log.
            }
        }

        return now();
    }

    private function note(): ?string
    {
        $note = OrderChangeContext::get('note');

        return is_string($note) && $note !== '' ? mb_substr($note, 0, 255) : null;
    }

    /**
     * Gửi thông báo cho chủ đơn — chỉ với các mốc trong NOTIFIABLE_EVENTS và
     * chỉ sau khi transaction bên ngoài đã commit.
     */
    private function notify(Order $order, string $event): void
    {
        if (! in_array($event, self::NOTIFIABLE_EVENTS, true)) {
            return;
        }

        $note = $this->note();
        $occurredAt = $this->occurredAt();

        // 'transfer_rejected' là mốc suy ra chứ không phải giá trị cột: đơn
        // chuyển khoản bị admin từ chối sẽ thành 'cancelled'; phân biệt bằng
        // nguồn + phương thức thanh toán để nội dung báo khách đúng ngữ cảnh.
        if ($event === 'cancelled'
            && $order->payment_method === Order::PAYMENT_BANK_TRANSFER
            && OrderChangeContext::get('source') === 'admin'
            && $order->getOriginal('status') === 'awaiting_transfer') {
            $event = 'transfer_rejected';
        }

        $orderId = $order->id;

        DB::afterCommit(function () use ($orderId, $event, $note, $occurredAt) {
            try {
                $fresh = Order::with('user')->find($orderId);

                if (! $fresh || ! $fresh->user) {
                    return;
                }

                $fresh->user->notify(new OrderStatusChanged($fresh, $event, $note, $occurredAt));
            } catch (\Throwable $e) {
                // Thông báo hỏng không được phép làm hỏng đơn hàng.
                Log::error('Gửi thông báo trạng thái đơn thất bại: '.$e->getMessage(), [
                    'order_id' => $orderId,
                    'event' => $event,
                ]);
            }
        });
    }
}
