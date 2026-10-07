<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lớp kết nối trực tiếp đến API GHN (Giao Hàng Nhanh).
 *
 * Nhiệm vụ:
 * - Gửi Token, ShopId.
 * - Gọi API lấy tỉnh, quận, phường.
 * - Tính phí vận chuyển.
 * - Tạo hoặc huỷ vận đơn.
 * - Xử lý HTTP request và lỗi kết nối.
 */
class GHNService
{
    protected string $baseUrl;
    protected string $token;
    protected int $shopId;

    public function __construct()
    {
        $this->baseUrl = config('services.ghn.base_url');
        $this->token = config('services.ghn.token') ?? '';
        $this->shopId = (int) config('services.ghn.shop_id', 0);
    }

    protected function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withOptions([
                'verify' => filter_var(config('services.ghn.verify_ssl', true), FILTER_VALIDATE_BOOLEAN),
            ])
            ->acceptJson()
            ->timeout(15)
            ->withHeaders([
                'Token' => $this->token,
                'ShopId' => (string) $this->shopId,
                'Content-Type' => 'application/json',
            ]);
    }

    // Lấy Tỉnh/Thành
    public function getProvinces(): array
    {
        return $this->get('/master-data/province');
    }

    // Lấy Quận/Huyện
    public function getDistricts(int $provinceId): array
    {
        return $this->get('/master-data/district', [
            'province_id' => $provinceId,
        ]);
    }

    // Lấy Phường/Xã
    public function getWards(int $districtId): array
    {
        return $this->get('/master-data/ward', [
            'district_id' => $districtId,
        ]);
    }

    // Tính phí giao hàng
    public function calculateFee(array $params): array
    {
        // API tính phí bắt buộc ShopId + from_district_id hợp lệ (master-data chỉ
        // cần Token). Thiếu cấu hình thì báo lỗi rõ ràng, không gọi GHN để rồi
        // nhận về lỗi khó hiểu (hoặc phí 0).
        if ($this->shopId <= 0 || (int) ($params['from_district_id'] ?? 0) <= 0) {
            Log::error('Thiếu cấu hình GHN để tính phí', [
                'shop_id' => $this->shopId,
                'from_district_id' => $params['from_district_id'] ?? null,
            ]);

            return ['code' => 500, 'message' => 'Thiếu cấu hình GHN (GHN_SHOP_ID / GHN_FROM_DISTRICT_ID).'];
        }

        return $this->post('/v2/shipping-order/fee', array_merge([
            'shop_id' => $this->shopId,
        ], $params));
    }

    // Tạo đơn giao hàng
    public function createOrder(array $orderData): array
    {
        return $this->post('/v2/shipping-order/create', array_merge([
            'shop_id' => $this->shopId,
        ], $orderData));
    }

    // Huỷ đơn hàng
    public function cancelOrder(array $orderCodes): array
    {
        return $this->post('/v2/switch-status/cancel', [
            'order_codes' => $orderCodes,
            'shop_id' => $this->shopId,
        ]);
    }

    /**
     * Tra cứu chi tiết 1 vận đơn: trạng thái hiện tại (`data.status`) và mảng
     * hành trình `data.log[] {status, updated_date}`. Dùng để vẽ timeline
     * kiểu Shopee và để "đồng bộ bù" khi webhook bị lỡ (xem
     * GHNShipmentSyncService::syncFromDetail()).
     */
    public function orderDetail(string $orderCode): array
    {
        return $this->post('/v2/shipping-order/detail', [
            'order_code' => $orderCode,
        ]);
    }

    protected function get(string $uri, array $query = []): array
    {
        try {
            $response = $this->client()->get($uri, $query);

            if (! $response->successful()) {
                Log::warning('GHN GET request failed', [
                    'uri' => $uri,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return $this->errorFromResponse($response);
            }

            return $response->json() ?? ['code' => -1, 'message' => 'GHN returned an empty response.'];
        } catch (ConnectionException $exception) {
            Log::error('Unable to connect to GHN', ['uri' => $uri, 'error' => $exception->getMessage()]);
            return ['code' => -1, 'message' => 'Unable to connect to GHN.'];
        }
    }

    protected function post(string $uri, array $payload): array
    {
        try {
            $response = $this->client()->post($uri, $payload);

            if (! $response->successful()) {
                Log::warning('GHN POST request failed', [
                    'uri' => $uri,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return $this->errorFromResponse($response);
            }

            return $response->json() ?? ['code' => -1, 'message' => 'GHN returned an empty response.'];
        } catch (ConnectionException $exception) {
            Log::error('Unable to connect to GHN', ['uri' => $uri, 'error' => $exception->getMessage()]);
            return ['code' => -1, 'message' => 'Unable to connect to GHN.'];
        }
    }

    /**
     * Lỗi HTTP không 2xx: giữ lại code/message/code_message_value thật trong
     * body GHN (vd. "ShopId không hợp lệ") thay vì một câu chung chung, để
     * màn hình checkout và log cho biết đúng nguyên nhân.
     */
    protected function errorFromResponse(Response $response): array
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];

        return [
            'code' => $body['code'] ?? $response->status(),
            'message' => $body['message'] ?? 'GHN API request failed.',
            'code_message_value' => $body['code_message_value'] ?? null,
        ];
    }
}
