<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\GHNOrderService;
use App\Services\MomoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Luồng: start/payAgain -> tạo transaction -> MomoService gọi API MoMo
 * -> chuyển khách đến payUrl -> callback (trình duyệt) / ipn (MoMo gọi thẳng server)
 * -> kiểm tra chữ ký + đối chiếu số tiền -> đánh dấu đã thanh toán -> tạo vận đơn GHN.
 */
class MomoController extends Controller
{
    // Bắt đầu thanh toán cho 1 đơn hàng vừa đặt.
    // ?type=atm (mặc định, thẻ nội địa) hoặc ?type=cc (thẻ quốc tế) hoặc ?type=wallet (quét QR ví MoMo).
    public function start(Request $request, Order $order, MomoService $momo)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        return $this->redirectToMomo($order, $this->newTransaction($order), $momo, $this->resolveCardType($request));
    }

    // Thanh toán lại cho đơn hàng cũ (giao dịch trước thất bại) — không tạo đơn hàng mới.
    // Nếu không truyền ?type=, giữ nguyên loại hình khách đã chọn ở lần thử trước đó.
    public function payAgain(Request $request, Order $order, MomoService $momo)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        return $this->redirectToMomo($order, $this->newTransaction($order), $momo, $this->resolveCardType($request, $order));
    }

    // Đọc lựa chọn loại thẻ từ query string (?type=atm|cc|wallet).
    // - Có ?type= hợp lệ -> dùng đúng loại đó.
    // - Không có ?type= và có $order (trường hợp payAgain) -> lấy lại loại hình của lần thử
    //   thanh toán MoMo gần nhất cho đơn hàng này (lưu trong request_payload.requestType).
    // - Giá trị lạ hoặc không xác định được -> fallback an toàn về ATM (đồng nhất với hành vi
    //   cũ, không chặn luồng bằng lỗi validate).
    private function resolveCardType(Request $request, ?Order $order = null): string
    {
        $map = [
            'atm' => MomoService::TYPE_ATM,
            'cc' => MomoService::TYPE_CC,
            'wallet' => MomoService::TYPE_WALLET,
        ];

        $type = $request->query('type');

        if ($type !== null) {
            return $map[$type] ?? MomoService::TYPE_ATM;
        }

        if ($order) {
            $previousType = $this->previousRequestType($order);

            if ($previousType) {
                return $previousType;
            }
        }

        return MomoService::TYPE_ATM;
    }

    // Lấy lại requestType (payWithATM/payWithCC/captureWallet) của lần thử thanh toán MoMo
    // gần nhất cho đơn hàng này, để payAgain() không âm thầm đổi loại hình thanh toán khách
    // đã chọn ban đầu.
    private function previousRequestType(Order $order): ?string
    {
        $lastTransaction = PaymentTransaction::where('order_id', $order->id)
            ->where('gateway', 'momo')
            ->whereNotNull('request_payload')
            ->latest('id')
            ->first();

        $requestType = $lastTransaction->request_payload['requestType'] ?? null;

        $validTypes = [MomoService::TYPE_ATM, MomoService::TYPE_CC, MomoService::TYPE_WALLET];

        return in_array($requestType, $validTypes, true) ? $requestType : null;
    }

    // Trình duyệt của khách được MoMo chuyển hướng về sau khi thanh toán.
    public function callback(Request $request, GHNOrderService $ghnOrders, MomoService $momo)
    {
        Log::info('MoMo callback received', [
            'payload' => $request->except('signature'),
            'has_signature' => $request->has('signature'),
        ]);

        if (! $momo->isValidSuccessfulResponse($request->all())) {
            Log::warning('MoMo callback rejected', [
                'result_code' => $request->input('resultCode'),
                'order_id' => $request->input('orderId'),
                'signature_valid' => $momo->isValidResponse($request->all()),
            ]);

            if ($momo->isValidResponse($request->all())) {
                $this->markFailed($request->all(), $momo);
            }

            return redirect()->route('orders.history')->with('error', 'Giao dịch MoMo thất bại hoặc đã bị huỷ.');
        }

        $result = $this->completePayment($request->all(), $ghnOrders, $momo);

        if ($result === 'invalid') {
            return redirect()->route('orders.history')->with('error', 'Dữ liệu thanh toán MoMo không hợp lệ hoặc số tiền không khớp.');
        }

        $message = in_array($result, ['created', 'already_created'], true)
            ? 'Thanh toán MoMo thành công! Vận đơn GHN đã được khởi tạo.'
            : 'Thanh toán thành công! Đơn hàng đang chờ tạo vận đơn GHN.';

        return redirect()->route('orders.history')->with('success', $message);
    }

    // Khách chủ động bấm "Kiểm tra lại trạng thái thanh toán" trên trang chi tiết đơn hàng.
    // Cần thiết khi: IPN không gọi tới được server (ví dụ đang chạy dev trên localhost —
    // MoMo ở xa không thể gọi ngược vào 127.0.0.1) VÀ trình duyệt cũng không hoàn tất được
    // redirect về callback() (đóng tab, mất mạng, sandbox MoMo xử lý chậm...). Khi đó đơn
    // hàng kẹt ở "pending" dù MoMo đã xử lý xong giao dịch thật — nút này chủ động hỏi lại
    // MoMo thay vì chỉ thụ động chờ MoMo gọi ngược.
    public function checkStatus(Order $order, MomoService $momo, GHNOrderService $ghnOrders)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->ghn_order_code || in_array($order->status, ['paid', 'paid_momo'], true)) {
            return back()->with('success', 'Đơn hàng đã được xác nhận thanh toán trước đó.');
        }

        $transaction = PaymentTransaction::where('order_id', $order->id)
            ->where('gateway', 'momo')
            ->whereNotNull('gateway_order_id')
            ->latest('id')
            ->first();

        if (! $transaction) {
            return back()->with('error', 'Đơn hàng này chưa có lần thử thanh toán MoMo nào để kiểm tra.');
        }

        if ($transaction->status === 'paid') {
            return back()->with('success', 'Giao dịch đã được ghi nhận thanh toán thành công trước đó.');
        }

        $result = $momo->queryTransaction($transaction->gateway_order_id);
        // Một vài nhánh lỗi của API query không trả kèm orderId -> tự điền lại để
        // completePayment()/markFailed() tra đúng dòng payment_transactions cần cập nhật.
        $result['orderId'] = $result['orderId'] ?? $transaction->gateway_order_id;

        if ($momo->isSuccessful($result)) {
            $outcome = $this->completePayment($result, $ghnOrders, $momo);

            if ($outcome === 'invalid') {
                return back()->with('error', 'Dữ liệu thanh toán MoMo không hợp lệ hoặc số tiền không khớp.');
            }

            $message = in_array($outcome, ['created', 'already_created'], true)
                ? 'MoMo xác nhận đã thanh toán thành công! Vận đơn GHN đã được khởi tạo.'
                : 'MoMo xác nhận đã thanh toán thành công! Đơn hàng đang chờ tạo vận đơn GHN.';

            return back()->with('success', $message);
        }

        // Các resultCode này (theo tài liệu MoMo) nghĩa là giao dịch CHƯA hoàn tất —
        // đang chờ khách xác nhận hoặc đang được xử lý — không phải đã thất bại hẳn,
        // nên không đánh dấu failed vội, chỉ báo khách thử kiểm tra lại sau.
        $stillProcessingCodes = [1000, 7000, 7002, 9000];
        $resultCode = (int) ($result['resultCode'] ?? -1);

        if (in_array($resultCode, $stillProcessingCodes, true)) {
            return back()->with('error', 'Giao dịch đang được MoMo xử lý, vui lòng kiểm tra lại sau ít phút.');
        }

        $this->markFailed($result, $momo);

        return back()->with('error', 'MoMo báo giao dịch thất bại hoặc đã bị huỷ (' . ($result['message'] ?? 'không rõ lý do') . '). Vui lòng thử thanh toán lại.');
    }

    // MoMo gọi thẳng máy chủ (server-to-server) để báo kết quả thanh toán thật sự.
    public function ipn(Request $request, GHNOrderService $ghnOrders, MomoService $momo)
    {
        Log::info('MoMo IPN received', [
            'payload' => $request->except('signature'),
            'has_signature' => $request->has('signature'),
        ]);

        if ($momo->isValidSuccessfulResponse($request->all())) {
            $this->completePayment($request->all(), $ghnOrders, $momo);
        } elseif ($momo->isValidResponse($request->all())) {
            $this->markFailed($request->all(), $momo);
        }

        return response()->json(['message' => 'Received']);
    }

    // Lưu một lần thử thanh toán mới cho đơn hàng.
    private function newTransaction(Order $order): PaymentTransaction
    {
        return PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'momo',
            'amount' => $order->total_price,
            'status' => 'pending',
        ]);
    }

    // Gọi MomoService để lấy đường dẫn thanh toán và chuyển hướng khách sang đó.
    private function redirectToMomo(Order $order, PaymentTransaction $transaction, MomoService $momo, string $requestType)
    {
        $result = $momo->createPayment($order, $transaction, $requestType);

        return isset($result['payUrl'])
            ? redirect()->away($result['payUrl'])
            : redirect()->route('orders.history')->with('error', 'Không thể kết nối tới MoMo. Vui lòng thử lại.');
    }

    // Xác nhận thanh toán (đối chiếu giao dịch + số tiền) và tạo vận đơn GHN.
    private function completePayment(array $payload, GHNOrderService $ghnOrders, MomoService $momo): string
    {
        $result = DB::transaction(function () use ($payload, $momo) {
            $transaction = PaymentTransaction::where('gateway', 'momo')
                ->where('gateway_order_id', $payload['orderId'] ?? '')
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return 'invalid';
            }

            $order = Order::lockForUpdate()->find($transaction->order_id);

            if (! $order) {
                return 'invalid';
            }

            if ($order->ghn_order_code) {
                return 'already_created';
            }

            if ($order->shipping_status === 'processing') {
                return 'processing';
            }

            if ((int) $transaction->amount !== (int) ($payload['amount'] ?? 0)) {
                $momo->markFailed($transaction, $payload);
                return 'invalid';
            }

            $order->update(['status' => 'paid', 'shipping_status' => 'processing']);
            $momo->markPaid($transaction, $payload);

            return ['create', $order->id];
        });

        if (! is_array($result)) {
            return (string) $result;
        }

        $order = Order::with('items.product', 'items.variant')->find($result[1]);
        $response = $ghnOrders->create($order, isPaid: true);

        if (($response['code'] ?? null) === 200 && ! empty($response['data']['order_code'])) {
            $order->update([
                'ghn_order_code' => $response['data']['order_code'],
                'ghn_total_fee' => $response['data']['total_fee'] ?? $order->ghn_total_fee,
                'shipping_status' => 'ready_to_pick',
            ]);
            return 'created';
        }

        Log::error('GHN order failed after MoMo payment', [
            'order_id' => $order->id,
            'response' => $response,
        ]);
        $order->update(['shipping_status' => 'not_shipped']);
        return 'failed';
    }

    // Ghi nhận giao dịch thất bại/bị huỷ.
    private function markFailed(array $payload, MomoService $momo): void
    {
        $transaction = PaymentTransaction::where('gateway', 'momo')
            ->where('gateway_order_id', $payload['orderId'] ?? '')
            ->first();

        if ($transaction && $transaction->status !== 'paid') {
            $momo->markFailed($transaction, $payload);
        }
    }
}
