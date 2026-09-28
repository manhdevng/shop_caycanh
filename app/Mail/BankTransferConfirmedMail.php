<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Email báo khách đã được admin xác nhận nhận tiền chuyển khoản — gửi ngay
// sau khi AdminOrderController::confirmTransfer() chuyển đơn sang "paid".
class BankTransferConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Đã nhận được chuyển khoản cho đơn hàng #'.$this->order->id.' — Cây Cảnh Shop',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bank-transfer-confirmed',
            with: [
                'order' => $this->order,
            ],
        );
    }
}
