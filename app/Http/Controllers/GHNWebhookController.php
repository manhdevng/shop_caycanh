<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\GHNShipmentSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Nhận webhook GHN báo cập nhật trạng thái vận đơn (server-to-server, không
 * có session người dùng — KHÔNG dùng auth()). Đây là kênh cập nhật tự động
 * cho luồng theo dõi đơn (xem GHNShipmentSyncService — nơi thật sự chứa
 * logic áp trạng thái, dùng chung với lệnh `ghn:sync-orders`, `ghn:simulate`
 * và các nút "Cập nhật"/"Đồng bộ GHN" của khách/admin).
 *
 * Xác thực nguồn gốc request: GHN không ký HMAC như MoMo. Cơ chế xác thực ở
 * đây dựa trên một token bí mật do TA tự đặt khi khai báo Webhook URL trên
 * trang quản trị GHN (dạng .../ghn/webhook?token=xxxxx), so khớp với
 * config('services.ghn.webhook_token'). Nếu GHN thực tế cung cấp cơ chế khác
 * (ví dụ header ký riêng), chỉ cần sửa verifyRequest() — phần còn lại không
 * đổi.
 */
class GHNWebhookController extends Controller
{
    /**
     * `Type` payload GHN cần xử lý trạng thái vận chuyển. Các loại khác
     * (update_weight, update_cod, update_fee...) chỉ cần trả 200 để GHN
     * không retry, không có gì để cập nhật ở đây.
     */
    private const HANDLED_TYPES = ['create', 'switch_status'];

    public function handle(Request $request, GHNShipmentSyncService $sync)
    {
        $payload = $request->all();

        $ghnOrderCode = $this->extractOrderCode($payload);
        $ghnStatus = $this->extractStatus($payload);
        $type = $this->extractType($payload);

        // Không log giá trị token để tránh lộ bí mật dùng để xác thực webhook.
        Log::info('GHN webhook received', [
            'order_code' => $ghnOrderCode,
            'status' => $ghnStatus,
            'type' => $type,
        ]);

        if (! $this->verifyRequest($request)) {
            Log::warning('GHN webhook rejected: token không hợp lệ hoặc chưa cấu hình', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($type !== null && ! in_array($type, self::HANDLED_TYPES, true)) {
            Log::info('GHN webhook: loại sự kiện không cần cập nhật trạng thái vận chuyển, bỏ qua', [
                'order_code' => $ghnOrderCode,
                'type' => $type,
            ]);

            return response()->json(['message' => 'Received']);
        }

        if (! $ghnOrderCode || ! $ghnStatus) {
            Log::warning('GHN webhook thiếu OrderCode hoặc Status, bỏ qua', ['payload' => $payload]);

            return response()->json(['message' => 'Received']);
        }

        // Không tìm thấy đơn -> vẫn trả 200 (tránh GHN gọi lại vô ích 10 lần
        // cho một đơn không tồn tại phía shop, ví dụ dữ liệu test/demo).
        $order = Order::where('ghn_order_code', $ghnOrderCode)->first();

        if (! $order) {
            Log::warning('GHN webhook: không tìm thấy đơn hàng khớp ghn_order_code', [
                'ghn_order_code' => $ghnOrderCode,
                'ghn_status' => $ghnStatus,
            ]);

            return response()->json(['message' => 'Received']);
        }

        $sync->apply(
            $order,
            $ghnStatus,
            $this->extractTime($payload),
            'ghn_webhook',
            $this->extractReason($payload)
        );

        return response()->json(['message' => 'Received']);
    }

    // Xác thực request thực sự đến từ GHN qua token bí mật tự cấu hình
    // (query string ?token=... hoặc header X-GHN-Webhook-Token — GHN webhook
    // không hỗ trợ header tuỳ biến trên URL cấu hình sẵn nên query string là
    // lựa chọn thực tế nhất). Không hardcode secret — luôn đọc qua config().
    private function verifyRequest(Request $request): bool
    {
        $expected = config('services.ghn.webhook_token');

        if (empty($expected)) {
            // Chưa cấu hình secret -> không thể xác thực nguồn gốc request,
            // từ chối theo nguyên tắc "không xác thực được thì không tin".
            return false;
        }

        $provided = $request->query('token') ?? $request->header('X-GHN-Webhook-Token');

        return is_string($provided) && hash_equals((string) $expected, $provided);
    }

    // GHN thực tế dùng field "OrderCode"; giữ vài biến thể tên khoá phòng khi
    // payload thật khác tài liệu tham khảo (đây là điểm duy nhất cần sửa).
    private function extractOrderCode(array $payload): ?string
    {
        $value = $payload['OrderCode'] ?? $payload['order_code'] ?? $payload['OrderId'] ?? null;

        return $value !== null ? (string) $value : null;
    }

    private function extractStatus(array $payload): ?string
    {
        $value = $payload['Status'] ?? $payload['status'] ?? null;

        return $value !== null ? (string) $value : null;
    }

    // 'create'|'switch_status'|'update_weight'|'update_cod'|'update_fee'...
    // (theo tài liệu GHN Webhook API).
    private function extractType(array $payload): ?string
    {
        $value = $payload['Type'] ?? $payload['type'] ?? null;

        return $value !== null ? (string) $value : null;
    }

    private function extractReason(array $payload): ?string
    {
        $value = $payload['Reason'] ?? $payload['reason'] ?? null;

        return $value !== null ? (string) $value : null;
    }

    // GHN gửi thời điểm THẬT của sự kiện trong trường "Time" — dùng làm
    // occurred_at của lịch sử thay vì thời điểm server nhận được webhook
    // (có thể trễ vài giây/phút). Quy đổi về giờ Việt Nam để nhất quán với
    // phần còn lại của hệ thống. Định dạng lạ/parse lỗi -> trả null, caller
    // (GHNShipmentSyncService::apply) tự dùng now() làm mặc định.
    private function extractTime(array $payload): ?Carbon
    {
        $value = $payload['Time'] ?? $payload['time'] ?? null;

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh');
        } catch (\Throwable $e) {
            Log::warning('GHN webhook: không parse được trường Time, dùng thời điểm hiện tại', [
                'raw_time' => $value,
            ]);

            return null;
        }
    }
}
