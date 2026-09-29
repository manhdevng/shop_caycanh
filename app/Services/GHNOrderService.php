<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lớp nghiệp vụ tạo vận đơn GHN từ Order của hệ thống.
 *
 * Nhiệm vụ:
 * - Nhận model Order.
 * - Đọc các sản phẩm trong đơn.
 * - Tính tổng trọng lượng.
 * - Chuyển dữ liệu sản phẩm sang định dạng GHN.
 * - Xác định cod_amount.
 * - Tạo payload vận đơn.
 * - Gọi lại GHNService::createOrder().
 */
class GHNOrderService
{
    public function __construct(private GHNService $ghn)
    {
    }

    public function create(Order $order, bool $isPaid = false): array
    {
        $items = [];
        $weight = 0;

        foreach ($order->items as $item) {
            $itemWeight = (int) ($item->variant?->weight ?? $item->product->weight ?? 200);
            $weight += $itemWeight * (int) $item->quantity;

            $itemName = $item->product->name ?? 'Sản phẩm';
            if (! empty($item->variant_name)) {
                $itemName .= ' - ' . $item->variant_name;
            }

            $items[] = [
                'name' => $itemName,
                'quantity' => (int) $item->quantity,
                'price' => (int) $item->price,
                'weight' => $itemWeight,
            ];
        }

        return $this->ghn->createOrder([
            'payment_type_id' => 2,
            'note' => 'Đơn hàng #' . $order->id,
            'required_note' => 'KHONGCHOXEMHANG',
            'to_name' => $order->name,
            'to_phone' => $order->phone,
            'to_address' => $order->address,
            'to_ward_code' => (string) $order->to_ward_code,
            'to_district_id' => (int) $order->to_district_id,
            'cod_amount' => $isPaid ? 0 : (int) $order->total_price,
            'weight' => $weight > 0 ? $weight : 300,
            'length' => 15,
            'width' => 15,
            'height' => 10,
            'service_type_id' => 2,
            'items' => $items,
        ]);
    }

    /**
     * Áp kết quả gọi GHNService::createOrder() (hoặc phản hồi tương đương) vào
     * Order: mã vận đơn, phí ship, dự kiến giao hàng, và mở màn hành trình
     * (`shipping_status = ready_to_pick`).
     *
     * NƠI DUY NHẤT nên dùng để "chốt" kết quả tạo vận đơn — thay cho việc mỗi
     * controller tự viết lại đoạn `$order->update([...])` (từng lặp lại ở
     * User\MomoController, User\OrderController, AdminOrderController).
     *
     * $fallbackFee: phí ship đã ước tính từ trước (ví dụ lúc checkout) — dùng
     * khi phản hồi GHN không kèm `total_fee` (một số response tạo đơn không
     * trả trường này).
     *
     * Trả false (và Log::warning, KHÔNG throw) nếu response không hợp lệ
     * (code khác 200 hoặc thiếu order_code) — caller tự quyết định nhánh lỗi
     * (thường là đặt shipping_status = 'not_shipped' để hiện nút "Tạo lại
     * vận đơn").
     */
    public function applyCreateResponse(Order $order, array $resp, int $fallbackFee): bool
    {
        $code = $resp['code'] ?? null;
        $orderCode = $resp['data']['order_code'] ?? null;

        if ($code !== 200 || empty($orderCode)) {
            Log::warning('Không thể áp dụng kết quả tạo vận đơn GHN: response không hợp lệ hoặc thiếu order_code', [
                'order_id' => $order->id,
                'response' => $resp,
            ]);

            return false;
        }

        $data = $resp['data'];

        $expectedDeliveryAt = null;
        if (! empty($data['expected_delivery_time'])) {
            try {
                $expectedDeliveryAt = Carbon::parse($data['expected_delivery_time']);
            } catch (Throwable $e) {
                // Định dạng thời gian lạ -> bỏ qua, không chặn luồng đặt hàng.
                $expectedDeliveryAt = null;
            }
        }

        $order->update([
            'ghn_order_code' => $orderCode,
            'ghn_total_fee' => $data['total_fee'] ?? $fallbackFee,
            'ghn_expected_delivery_at' => $expectedDeliveryAt,
            'shipping_status' => 'ready_to_pick',
        ]);

        return true;
    }

    /**
     * Huỷ vận đơn GHN gắn với một Order của hệ thống (dùng khi admin huỷ đơn hàng).
     *
     * Quy tắc:
     * - Nếu đơn chưa có mã vận đơn GHN (chưa từng tạo vận đơn) thì bỏ qua,
     *   không gọi API, trả về kết quả có 'skipped' => true.
     * - Nếu có mã vận đơn thì gọi GHNService::cancelOrder() trong try/catch,
     *   tuyệt đối không để ngoại lệ (lỗi mạng, timeout, token sai...) thoát ra ngoài,
     *   để luồng huỷ đơn ở controller không bị vỡ (không trả lỗi 500).
     *
     * Cấu trúc mảng trả về (luôn có 'success' bool và 'message' string):
     * - Bỏ qua (không có mã vận đơn):
     *   ['success' => false, 'skipped' => true, 'message' => '...']
     * - Gọi GHN thất bại (exception hoặc GHN báo lỗi):
     *   ['success' => false, 'skipped' => false, 'message' => '...', 'error' => '...']
     * - Gọi GHN thành công:
     *   ['success' => true, 'skipped' => false, 'message' => '...', 'data' => array GHN trả về]
     *
     * @param Order $order Đơn hàng cần huỷ vận đơn GHN.
     * @return array Kết quả huỷ vận đơn (không throw exception).
     */
    public function cancelForOrder(Order $order): array
    {
        $ghnOrderCode = trim((string) ($order->ghn_order_code ?? ''));

        // Đơn chưa từng tạo vận đơn GHN (hoặc mã rỗng) thì không có gì để huỷ.
        if ($ghnOrderCode === '') {
            return [
                'success' => false,
                'skipped' => true,
                'message' => 'Đơn hàng chưa có mã vận đơn GHN nên không cần huỷ.',
            ];
        }

        try {
            $response = $this->ghn->cancelOrder([$ghnOrderCode]);

            // GHNService luôn trả về array; kiểm tra code trả về từ GHN để biết thành công hay không.
            $code = $response['code'] ?? null;

            if ($code !== 200) {
                Log::error('Huỷ vận đơn GHN thất bại', [
                    'order_id' => $order->id,
                    'ghn_order_code' => $ghnOrderCode,
                    'response' => $response,
                ]);

                return [
                    'success' => false,
                    'skipped' => false,
                    'message' => $response['message'] ?? 'Huỷ vận đơn GHN thất bại.',
                    'error' => $response['message'] ?? 'unknown_error',
                    'data' => $response,
                ];
            }

            Log::info('Huỷ vận đơn GHN thành công', [
                'order_id' => $order->id,
                'ghn_order_code' => $ghnOrderCode,
            ]);

            return [
                'success' => true,
                'skipped' => false,
                'message' => 'Huỷ vận đơn GHN thành công.',
                'data' => $response,
            ];
        } catch (Throwable $exception) {
            Log::error('Ngoại lệ khi huỷ vận đơn GHN', [
                'order_id' => $order->id,
                'ghn_order_code' => $ghnOrderCode,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'skipped' => false,
                'message' => 'Không thể kết nối tới GHN để huỷ vận đơn.',
                'error' => $exception->getMessage(),
            ];
        }
    }
}
