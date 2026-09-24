<?php

namespace App\Services;

use App\Models\Order;
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
