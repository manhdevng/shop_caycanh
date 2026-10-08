<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Tên Tỉnh/Quận/Phường của 1 cặp mã GHN (đơn cũ chỉ lưu mã) — dùng để hiển
     * thị lại địa chỉ các đơn đã đặt ở trang thanh toán. Danh mục GHN gần như
     * không đổi nên cache 1 ngày. Danh sách toàn bộ quận/huyện khá nặng (GHN
     * dev có lúc trả rất chậm) nên KHÔNG tải trong request của khách: chưa có
     * cache thì hẹn tải sau khi trả response và tạm trả null (view hiển thị
     * địa chỉ không kèm tên), lần sau mở trang sẽ có đủ tên.
     *
     * @return array{province_id:int,province_name:string,district_name:string,ward_name:string}|null
     */
    public function locationNames(int $districtId, string $wardCode): ?array
    {
        $districts = Cache::get('ghn:master:district-map');

        if (! is_array($districts)) {
            app()->terminating(fn () => $this->warmDistrictMap());

            return null;
        }

        $district = $districts[$districtId] ?? null;

        if (! $district) {
            return null;
        }

        $provinces = $this->cachedMasterData('ghn:master:provinces', '/master-data/province', [], 5);
        $wards = $this->cachedMasterData("ghn:master:wards:{$districtId}", '/master-data/ward', ['district_id' => $districtId], 5);
        $province = collect($provinces)->firstWhere('ProvinceID', $district['province_id']);
        $ward = collect($wards)->firstWhere('WardCode', $wardCode);

        return [
            'province_id' => (int) $district['province_id'],
            'province_name' => (string) ($province['ProvinceName'] ?? ''),
            'district_name' => (string) $district['name'],
            'ward_name' => (string) ($ward['WardName'] ?? ''),
        ];
    }

    // Tải toàn bộ quận/huyện 1 lần, chỉ giữ DistrictID => [tên, ProvinceID] cho gọn cache.
    public function warmDistrictMap(): void
    {
        if (Cache::has('ghn:master:district-map')) {
            return;
        }

        $map = collect($this->fetchMasterData('/master-data/district', [], 30) ?? [])
            ->mapWithKeys(fn ($d) => [(int) ($d['DistrictID'] ?? 0) => [
                'name' => (string) ($d['DistrictName'] ?? ''),
                'province_id' => (int) ($d['ProvinceID'] ?? 0),
            ]])
            ->all();

        if ($map !== []) {
            Cache::put('ghn:master:district-map', $map, now()->addDay());
        }
    }

    private function cachedMasterData(string $cacheKey, string $uri, array $query, int $timeout): array
    {
        if (is_array($cached = Cache::get($cacheKey))) {
            return $cached;
        }

        $data = $this->fetchMasterData($uri, $query, $timeout);

        if ($data === null) {
            return [];
        }

        Cache::put($cacheKey, $data, now()->addDay());

        return $data;
    }

    private function fetchMasterData(string $uri, array $query, int $timeout): ?array
    {
        try {
            $response = $this->client()->timeout($timeout)->get($uri, $query);
        } catch (ConnectionException $exception) {
            Log::warning('Unable to connect to GHN', ['uri' => $uri, 'error' => $exception->getMessage()]);

            return null;
        }

        $data = $response->successful() ? $response->json('data') : null;

        return is_array($data) && $data !== [] ? $data : null;
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

        return $this->post('/v2/shipping-order/fee', $this->withServiceId(array_merge([
            'shop_id' => $this->shopId,
        ], $params)));
    }

    // Tạo đơn giao hàng
    public function createOrder(array $orderData): array
    {
        return $this->post('/v2/shipping-order/create', $this->withServiceId(array_merge([
            'shop_id' => $this->shopId,
        ], $orderData)));
    }

    /**
     * GHN không phải lúc nào cũng tự suy ra được dịch vụ từ service_type_id
     * (tuỳ shop/tuyến) và khi đó báo "ServiceID failed on the 'required' tag".
     * Vì vậy hỏi /available-services cho đúng tuyến rồi gửi kèm service_id cụ
     * thể. Không tra được thì giữ nguyên payload (chỉ có service_type_id).
     */
    protected function withServiceId(array $payload): array
    {
        if (! empty($payload['service_id'])) {
            return $payload;
        }

        $fromDistrictId = (int) ($payload['from_district_id'] ?? config('services.ghn.from_district_id'));
        $toDistrictId = (int) ($payload['to_district_id'] ?? 0);

        $serviceId = $this->resolveServiceId($fromDistrictId, $toDistrictId, (int) ($payload['service_type_id'] ?? 2));

        if ($serviceId) {
            $payload['service_id'] = $serviceId;
        }

        return $payload;
    }

    // Dịch vụ GHN khả dụng cho 1 tuyến, ưu tiên đúng service_type_id; cache 1 ngày.
    protected function resolveServiceId(int $fromDistrictId, int $toDistrictId, int $serviceTypeId): ?int
    {
        if ($this->shopId <= 0 || $fromDistrictId <= 0 || $toDistrictId <= 0) {
            return null;
        }

        $cacheKey = "ghn:service:{$this->shopId}:{$fromDistrictId}:{$toDistrictId}:{$serviceTypeId}";

        if ($cached = Cache::get($cacheKey)) {
            return (int) $cached;
        }

        $response = $this->post('/v2/shipping-order/available-services', [
            'shop_id' => $this->shopId,
            'from_district' => $fromDistrictId,
            'to_district' => $toDistrictId,
        ]);

        $services = collect(is_array($response['data'] ?? null) ? $response['data'] : [])
            ->filter(fn ($service) => is_array($service) && (int) ($service['service_id'] ?? 0) > 0);

        $service = $services->firstWhere('service_type_id', $serviceTypeId) ?? $services->first();

        if (! $service) {
            Log::warning('GHN không trả dịch vụ khả dụng cho tuyến', [
                'from_district_id' => $fromDistrictId,
                'to_district_id' => $toDistrictId,
                'response' => $response,
            ]);

            return null;
        }

        Cache::put($cacheKey, (int) $service['service_id'], now()->addDay());

        return (int) $service['service_id'];
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
