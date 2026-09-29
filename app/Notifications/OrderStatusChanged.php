<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

/**
 * Thông báo cá nhân "đơn hàng của bạn vừa đổi trạng thái".
 *
 * Luôn lưu vào bảng `notifications` (chuông header + trang /thong-bao). Với 3
 * mốc quan trọng nhất (đang giao, đã giao, đã huỷ) thì gửi thêm email, vì đó
 * là lúc khách cần hành động hoặc cần biết ngay.
 *
 * KHÔNG dùng ShouldQueue: máy dev không chắc có `queue:work` chạy, thông báo
 * nằm mãi trong bảng jobs sẽ tệ hơn là gửi đồng bộ (đã được bọc trong
 * DB::afterCommit + try/catch ở OrderObserver).
 *
 * Cấu trúc `data` là hợp đồng với frontend — xem mục 4.3 của kế hoạch:
 * {kind, order_id, event, title, message, icon, url, occurred_at}.
 */
class OrderStatusChanged extends Notification
{
    use Queueable;

    /** Các mốc gửi kèm email ngoài thông báo trong ứng dụng. */
    private const MAIL_EVENTS = ['delivering', 'delivered', 'cancelled'];

    /**
     * Icon lucide cho từng mốc — frontend render thẳng tên này.
     */
    private const ICONS = [
        'placed' => 'package',
        'paid' => 'credit-card',
        'transfer_rejected' => 'x-circle',
        'ready_to_pick' => 'package',
        'picked' => 'package',
        'delivering' => 'truck',
        'delivery_fail' => 'circle-alert',
        'delivered' => 'circle-check',
        'completed' => 'circle-check',
        'cancelled' => 'x-circle',
        'return' => 'undo-2',
        'returned' => 'undo-2',
    ];

    public function __construct(
        public Order $order,
        public string $event,
        public ?string $note = null,
        public ?Carbon $occurredAt = null,
    ) {
        $this->occurredAt ??= now();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (in_array($this->event, self::MAIL_EVENTS, true) && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Hợp đồng dữ liệu mục 4.3 — đổi khoá ở đây là phải sửa cả view chuông
     * và trang /thong-bao.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'order_status',
            'order_id' => $this->order->id,
            'event' => $this->event,
            'title' => $this->title(),
            'message' => $this->message(),
            'icon' => self::ICONS[$this->event] ?? 'package',
            'url' => $this->url(),
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }

    /**
     * Trang đích khi khách bấm vào thông báo. Mốc "đã giao" dẫn thẳng tới
     * trang đánh giá đơn — đó là việc khách cần làm tiếp, thay vì bắt họ mở
     * chi tiết đơn rồi tự tìm nút "Đánh giá".
     */
    private function url(): string
    {
        return $this->event === 'delivered'
            ? '/orders/'.$this->order->id.'/danh-gia'
            : '/orders/'.$this->order->id;
    }

    public function toMail(object $notifiable): Mailable|MailMessage
    {
        $title = $this->title();
        $message = $this->message();
        $url = route('orders.show', $this->order);

        // View do blade-frontend làm; nếu chưa có thì vẫn gửi được email bằng
        // MailMessage mặc định thay vì ném lỗi giữa luồng đổi trạng thái.
        if (View::exists('emails.order-status')) {
            return (new MailMessage)
                ->subject($title)
                ->view('emails.order-status', [
                    'order' => $this->order,
                    'title' => $title,
                    'messageText' => $message,
                    'url' => $url,
                ]);
        }

        return (new MailMessage)
            ->subject($title)
            ->greeting('Chào '.($this->order->name ?: 'bạn').',')
            ->line($message)
            ->action('Xem đơn hàng', $url)
            ->line('Cảm ơn bạn đã mua hàng tại '.config('shop.name', 'Cây Cảnh Shop').'.');
    }

    /** Tiêu đề ngắn, luôn có mã đơn để khách nhận ra ngay. */
    private function title(): string
    {
        $id = $this->order->id;

        return match ($this->event) {
            'placed' => "Đã đặt đơn #{$id} thành công",
            'paid' => "Đơn #{$id} đã thanh toán thành công",
            'transfer_rejected' => "Đơn #{$id} chưa nhận được chuyển khoản",
            'ready_to_pick' => "Đơn #{$id} đã có mã vận đơn",
            'picked' => "Đơn #{$id} đã được giao cho đơn vị vận chuyển",
            'delivering' => "Đơn #{$id} đang được giao",
            'delivery_fail' => "Đơn #{$id} giao không thành công",
            'delivered' => "Đơn #{$id} đã giao thành công",
            'completed' => "Đơn #{$id} đã hoàn thành",
            'cancelled' => "Đơn #{$id} đã bị huỷ",
            'return' => "Đơn #{$id} đang chờ hoàn hàng",
            'returned' => "Đơn #{$id} đã hoàn hàng",
            default => "Đơn #{$id} vừa cập nhật trạng thái",
        };
    }

    /**
     * Nội dung thân thiện. Hai mốc "xấu" (huỷ, từ chối chuyển khoản) và mốc
     * giao hỏng luôn kèm lý do nếu có, vì đó là điều khách muốn biết nhất.
     */
    private function message(): string
    {
        $note = $this->note;
        $withNote = fn (string $base) => filled($note) ? $base.' Lý do: '.$note : $base;

        return match ($this->event) {
            'placed' => 'Shop đã nhận được đơn hàng của bạn. Theo dõi hành trình đơn ngay tại đây.',
            'paid' => 'Chúng tôi đã nhận được thanh toán của bạn và đang chuẩn bị hàng.',
            'transfer_rejected' => $withNote('Đơn đã bị huỷ vì chưa nhận được tiền chuyển khoản.'),
            'ready_to_pick' => 'Shop đã đóng gói xong, đang chờ GHN tới lấy hàng.',
            'picked' => 'GHN đã lấy hàng khỏi kho của shop, đơn đang trên đường tới bạn.',
            'delivering' => 'Shipper GHN đang giao hàng, bạn chú ý điện thoại nhé.',
            'delivery_fail' => $withNote('Shipper giao chưa thành công và sẽ thử lại.'),
            'delivered' => 'Hàng đã được giao tới bạn. Kiểm tra hàng rồi bấm "Đã nhận được hàng" giúp shop nhé.',
            'completed' => 'Cảm ơn bạn! Hãy dành ít phút đánh giá sản phẩm để shop phục vụ tốt hơn.',
            'cancelled' => $withNote('Đơn hàng của bạn đã được huỷ.'),
            'return' => 'Đơn hàng đang được chuyển hoàn về shop.',
            'returned' => 'Đơn hàng đã hoàn về shop thành công.',
            default => 'Đơn hàng của bạn vừa có cập nhật mới.',
        };
    }
}
