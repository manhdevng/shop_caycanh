<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Báo khách "shop đã phản hồi đánh giá của bạn".
 *
 * Chỉ kênh `database` (chuông header + trang /thong-bao) — phản hồi đánh giá
 * không khẩn cấp như mốc giao hàng nên không gửi email.
 *
 * `data` giữ đúng khuôn chung với OrderStatusChanged để view chuông render
 * được cả hai loại bằng một đoạn mã: {kind, title, message, icon, url}.
 */
class ReviewReplied extends Notification
{
    use Queueable;

    public function __construct(public Review $review) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $productName = $this->review->product?->name ?? 'sản phẩm';
        $reply = trim((string) $this->review->shop_reply);

        return [
            'kind' => 'review_reply',
            'review_id' => $this->review->id,
            'product_id' => $this->review->product_id,
            'title' => 'Shop đã phản hồi đánh giá của bạn',
            // Cắt ngắn để dòng thông báo trong chuông không tràn; nội dung
            // đầy đủ nằm ở trang sản phẩm.
            'message' => 'Về "'.$productName.'": '.mb_strimwidth($reply, 0, 120, '…'),
            'icon' => 'message-square',
            'url' => '/san-pham/'.$this->review->product_id,
            'occurred_at' => ($this->review->shop_replied_at ?? now())->toIso8601String(),
        ];
    }
}
