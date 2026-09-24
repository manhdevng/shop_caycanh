<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lớp kết nối trực tiếp đến API MoMo.
 *
 * MoMo sandbox KHÔNG gộp chung 1 trang cho mọi hình thức — mỗi requestType mở đúng
 * 1 màn hình riêng:
 *   - payWithATM   -> thẻ ATM nội địa (ngân hàng nội địa qua Napas)
 *   - payWithCC    -> thẻ quốc tế (Visa/Mastercard/JCB)
 *   - captureWallet -> quét mã QR bằng ví MoMo
 * Vì vậy phía ứng dụng phải cho người dùng CHỌN trước loại thẻ, rồi truyền đúng
 * requestType tương ứng khi gọi createPayment().
 *
 * Lưu ý chữ ký (rawHash): API tạo thanh toán AIO v2 dùng CHUNG 1 công thức rawHash cho
 * cả 3 requestType (chỉ khác giá trị của tham số requestType) — không có tham số riêng
 * nào chỉ áp dụng cho captureWallet. Vì vậy không cần rẽ nhánh rawHash theo requestType.
 */
class MomoService
{
    public const TYPE_ATM = 'payWithATM';
    public const TYPE_CC = 'payWithCC';
    public const TYPE_WALLET = 'captureWallet';

    // Tạo yêu cầu thanh toán MoMo cho 1 lần thử thanh toán (transaction) của 1 đơn hàng.
    public function createPayment(Order $order, PaymentTransaction $transaction, string $requestType = self::TYPE_ATM): array
    {
        $endpoint = config('services.momo.endpoint', 'https://test-payment.momo.vn/v2/gateway/api/create');
        $partnerCode = config('services.momo.partner_code', env('MOMO_PARTNER_CODE', ''));
        $accessKey = config('services.momo.access_key', env('MOMO_ACCESS_KEY', ''));
        $secretKey = config('services.momo.secret_key', env('MOMO_SECRET_KEY', ''));

        // Chặn sớm nếu thiếu cấu hình bắt buộc -> chắc chắn sẽ lỗi chữ ký nếu gọi API,
        // nên không gọi ra ngoài, trả thẳng lỗi cấu hình để redirectToMomo() xử lý như
        // một lần tạo payUrl thất bại bình thường.
        if (blank($partnerCode) || blank($accessKey) || blank($secretKey)) {
            return ['resultCode' => -1, 'message' => 'Thiếu cấu hình MoMo'];
        }

        $orderInfo = 'Thanh toan don hang #' . $order->id;
        $amount = (string) ((int) $order->total_price);
        // Mỗi lần thử thanh toán cần 1 orderId duy nhất bên phía MoMo -> ghép order_id + transaction_id + timestamp.
        $orderId = $order->id . '_' . $transaction->id . '_' . time();
        $redirectUrl = config('services.momo.redirect_url') ?: route('momo.callback');
        $ipnUrl = config('services.momo.ipn_url') ?: route('momo.ipn');

        // Cảnh báo sớm (chỉ log, không chặn luồng) khi redirect/ipn URL là địa chỉ
        // loopback/private -> MoMo (ở xa, trên Internet) không thể gọi ngược vào được.
        if (! app()->environment('production')
            && ($this->isPrivateOrLoopbackHost($redirectUrl) || $this->isPrivateOrLoopbackHost($ipnUrl))) {
            Log::warning('[MomoConfig] redirect/ipn URL is not publicly reachable', [
                'redirect_url' => $redirectUrl,
                'ipn_url' => $ipnUrl,
                'environment' => app()->environment(),
            ]);
        }
        // extraData mang theo order_id thật để đối chiếu ngược lại khi MoMo callback/IPN trả về.
        $extraData = (string) $order->id;
        $requestId = (string) time();

        $rawHash = 'accessKey=' . $accessKey .
            '&amount=' . $amount .
            '&extraData=' . $extraData .
            '&ipnUrl=' . $ipnUrl .
            '&orderId=' . $orderId .
            '&orderInfo=' . $orderInfo .
            '&partnerCode=' . $partnerCode .
            '&redirectUrl=' . $redirectUrl .
            '&requestId=' . $requestId .
            '&requestType=' . $requestType;

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'Cây Cảnh Shop',
            'storeId' => 'MomoStore',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => hash_hmac('sha256', $rawHash, $secretKey),
        ];

        $transaction->update([
            'gateway_order_id' => $orderId,
            'request_payload' => $data,
        ]);

        try {
            $response = Http::withOptions([
                    'verify' => filter_var(config('services.momo.verify_ssl', true), FILTER_VALIDATE_BOOLEAN),
                ])
                ->acceptJson()
                ->timeout(15)
                ->post($endpoint, $data);

            $result = $response->json() ?? [];
        } catch (\Throwable $e) {
            // Timeout/DNS lỗi/connection refused... -> không để exception văng ra thẳng
            // màn hình 500 cho khách. Log rõ nguyên nhân (không log signature/secret key)
            // rồi trả mảng rỗng để redirectToMomo() tự xử lý nhánh "Không thể kết nối tới MoMo".
            Log::error('[MomoConfig] Failed to call MoMo API', [
                'endpoint' => $endpoint,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
            ]);

            $result = [];
        }

        $transaction->update([
            'response_payload' => $result,
            'result_code' => isset($result['resultCode']) ? (int) $result['resultCode'] : null,
            'message' => $result['message'] ?? null,
            'status' => isset($result['payUrl']) ? 'initiated' : 'failed',
        ]);

        return $result;
    }

    /**
     * Hỏi thẳng MoMo xem 1 giao dịch (theo orderId phía MoMo, tức gateway_order_id lưu
     * trong payment_transactions) đã thanh toán thành công thật hay chưa — dùng API
     * "Kiểm tra kết quả giao dịch" (v2/gateway/api/query) của MoMo.
     *
     * Vì sao cần: IPN (server-to-server) chỉ hoạt động nếu ipnUrl là địa chỉ MoMo (ở xa,
     * trên Internet) gọi tới được. Khi chạy dev trên localhost/127.0.0.1 (không tunnel công
     * khai), MoMo KHÔNG BAO GIỜ gọi được IPN vào máy dev. Nếu vì bất kỳ lý do gì trình
     * duyệt khách không hoàn tất được redirect về callback (đóng tab, mất mạng giữa chừng,
     * sandbox MoMo xử lý chậm...), đơn hàng sẽ kẹt vĩnh viễn ở "pending" dù MoMo đã xử lý
     * xong giao dịch thật. queryTransaction() cho phép chủ động hỏi lại MoMo bất cứ lúc nào
     * để tự khắc phục, thay vì chỉ thụ động chờ MoMo gọi ngược.
     *
     * Trả về mảng y hệt cấu trúc payload mà callback()/ipn() nhận được (resultCode, orderId,
     * amount, transId, message...) để có thể tái sử dụng thẳng completePayment()/markFailed().
     */
    public function queryTransaction(string $orderId): array
    {
        $endpoint = config('services.momo.query_endpoint')
            ?: str_replace('/create', '/query', config('services.momo.endpoint', 'https://test-payment.momo.vn/v2/gateway/api/create'));
        $partnerCode = config('services.momo.partner_code', '');
        $accessKey = config('services.momo.access_key', '');
        $secretKey = config('services.momo.secret_key', '');

        if (blank($partnerCode) || blank($accessKey) || blank($secretKey)) {
            return ['resultCode' => -1, 'message' => 'Thiếu cấu hình MoMo'];
        }

        $requestId = (string) time();

        // Công thức rawHash riêng của API query — KHÁC với rawHash của createPayment(),
        // theo đúng tài liệu MoMo (chỉ gồm accessKey, orderId, partnerCode, requestId).
        $rawHash = 'accessKey=' . $accessKey .
            '&orderId=' . $orderId .
            '&partnerCode=' . $partnerCode .
            '&requestId=' . $requestId;

        $data = [
            'partnerCode' => $partnerCode,
            'requestId' => $requestId,
            'orderId' => $orderId,
            'lang' => 'vi',
            'signature' => hash_hmac('sha256', $rawHash, $secretKey),
        ];

        try {
            $response = Http::withOptions([
                    'verify' => filter_var(config('services.momo.verify_ssl', true), FILTER_VALIDATE_BOOLEAN),
                ])
                ->acceptJson()
                ->timeout(15)
                ->post($endpoint, $data);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('[MomoConfig] Failed to call MoMo query API', [
                'endpoint' => $endpoint,
                'order_id' => $orderId,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
            ]);

            return ['resultCode' => -1, 'message' => 'Không thể kết nối tới MoMo để kiểm tra giao dịch.'];
        }
    }

    // Kiểm tra host của 1 URL có phải địa chỉ loopback/private (chỉ máy trong mạng nội bộ
    // mới gọi được) hay không -> dùng để cảnh báo sớm khi redirect/ipn URL chắc chắn không
    // thể truy cập được từ máy chủ MoMo ở xa trên Internet.
    private function isPrivateOrLoopbackHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            // Không parse được host (URL rỗng/sai định dạng) -> coi như đáng ngờ, cần cảnh báo.
            return true;
        }

        $host = strtolower($host);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // FILTER_FLAG_NO_PRIV_RANGE/NO_RES_RANGE loại trừ IP hợp lệ nếu nó thuộc dải
            // private (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, ...) hoặc dải dành riêng.
            return ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        // Domain thật (không phải IP) -> không gắn cờ, kể cả khi bị tường lửa nội bộ chặn.
        return false;
    }

    // MoMo báo thanh toán thành công (resultCode = 0) hay không.
    public function isSuccessful(array $payload): bool
    {
        return (string) ($payload['resultCode'] ?? '') === '0';
    }

    // Đánh dấu giao dịch đã thanh toán thành công.
    public function markPaid(PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'transaction_id' => $payload['transId'] ?? null,
            'result_code' => (int) ($payload['resultCode'] ?? 0),
            'message' => $payload['message'] ?? null,
            'response_payload' => $payload,
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);
    }

    // Đánh dấu giao dịch thất bại hoặc bị huỷ.
    public function markFailed(PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'transaction_id' => $payload['transId'] ?? null,
            'result_code' => isset($payload['resultCode']) ? (int) $payload['resultCode'] : null,
            'message' => $payload['message'] ?? null,
            'response_payload' => $payload,
            'status' => 'failed',
        ]);
    }

    // Callback hợp lệ (đúng chữ ký) VÀ báo thành công.
    public function isValidSuccessfulResponse(array $payload): bool
    {
        return $this->isValidResponse($payload) && $this->isSuccessful($payload);
    }

    // Kiểm tra chữ ký MoMo gửi kèm callback/IPN, chống giả mạo request.
    public function isValidResponse(array $payload): bool
    {
        if (! isset($payload['signature'])) {
            return false;
        }

        $secretKey = config('services.momo.secret_key', '');

        return hash_equals(
            hash_hmac('sha256', $this->ipnRawHash($payload), $secretKey),
            (string) $payload['signature']
        );
    }

    // Chuỗi rawHash MoMo dùng để ký/kiểm tra chữ ký của 1 payload callback/IPN (đúng thứ tự
    // field chuẩn MoMo v2 cho phản hồi thanh toán). Tách riêng để isValidResponse() (kiểm tra
    // chữ ký thật từ MoMo) và signIpnPayload() (tự ký payload giả lập, dùng bởi lệnh artisan
    // momo:simulate-ipn) dùng chung đúng 1 công thức, tránh lệch nhau.
    private function ipnRawHash(array $payload): string
    {
        $accessKey = config('services.momo.access_key', '');

        return 'accessKey=' . $accessKey .
            '&amount=' . ($payload['amount'] ?? '') .
            '&extraData=' . ($payload['extraData'] ?? '') .
            '&message=' . ($payload['message'] ?? '') .
            '&orderId=' . ($payload['orderId'] ?? '') .
            '&orderInfo=' . ($payload['orderInfo'] ?? '') .
            '&orderType=' . ($payload['orderType'] ?? '') .
            '&partnerCode=' . ($payload['partnerCode'] ?? '') .
            '&payType=' . ($payload['payType'] ?? '') .
            '&requestId=' . ($payload['requestId'] ?? '') .
            '&responseTime=' . ($payload['responseTime'] ?? '') .
            '&resultCode=' . ($payload['resultCode'] ?? '') .
            '&transId=' . ($payload['transId'] ?? '');
    }

    // Tự ký 1 payload IPN giả lập giống hệt công thức MoMo thật dùng -> chỉ phục vụ lệnh
    // artisan momo:simulate-ipn (dev/testing nội bộ), KHÔNG dùng trong luồng xử lý thật
    // (luồng thật luôn kiểm tra chữ ký MoMo tự gửi qua isValidResponse(), không tự tạo).
    public function signIpnPayload(array $payload): string
    {
        $secretKey = config('services.momo.secret_key', '');

        return hash_hmac('sha256', $this->ipnRawHash($payload), $secretKey);
    }

    // Lấy lại ID đơn hàng nội bộ từ trường extraData mà MoMo gửi kèm callback/IPN.
    public function orderId(array $payload): ?int
    {
        $orderId = $payload['extraData'] ?? null;

        return is_numeric($orderId) ? (int) $orderId : null;
    }
}
