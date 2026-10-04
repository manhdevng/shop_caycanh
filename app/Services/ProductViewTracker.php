<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Ghi lượt xem trang sản phẩm cho cả người đăng nhập và khách.
 *
 *  - Người đăng nhập: viewer_key = "u:<user_id>" (lịch sử xem/gợi ý vẫn đọc theo user_id).
 *  - Khách: viewer_key = "g:" + HMAC-SHA256(session id, APP_KEY); không lưu IP
 *    hay session id thô.
 *  - Cùng viewer_key + sản phẩm chỉ ghi một dòng trong DEDUP_MINUTES phút
 *    (refresh liên tục không phóng đại lượt xem); khoá cache chặn hai request
 *    song song cùng ghi.
 *  - Không ghi quản trị viên và bot/crawler có User-Agent rõ ràng.
 *  - Không bao giờ ném lỗi ra ngoài: lỗi ghi lượt xem không được làm vỡ trang.
 */
class ProductViewTracker
{
    public const DEDUP_MINUTES = 30;

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|lighthouse|curl|wget|python-requests|httpclient/i';

    public function record(Product $product, Request $request): void
    {
        try {
            $user = $request->user();
            if ($user?->isAdmin() || $this->isBot($request)) {
                return;
            }

            $viewerKey = $user ? 'u:'.$user->id : $this->guestKey($request);
            if ($viewerKey === null) {
                return;
            }

            $lock = Cache::lock('product-view:'.$product->id.':'.sha1($viewerKey), 10);
            if (! $lock->get()) {
                return; // Request song song của cùng người xem đang ghi.
            }

            try {
                $recent = ProductView::query()
                    ->where('viewer_key', $viewerKey)
                    ->where('product_id', $product->id)
                    ->where('viewed_at', '>=', now()->subMinutes(self::DEDUP_MINUTES))
                    ->exists();

                if (! $recent) {
                    ProductView::create([
                        'user_id' => $user?->id,
                        'viewer_key' => $viewerKey,
                        'product_id' => $product->id,
                        'viewed_at' => now(),
                        'tracking_version' => ProductView::TRACKING_DEDUPED,
                    ]);
                }
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            Log::warning('Không ghi được lượt xem sản phẩm', ['product_id' => $product->id, 'error' => $e->getMessage()]);
        }
    }

    private function guestKey(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }
        $session = $request->session();
        if (! $session->isStarted() || ! $session->getId()) {
            return null;
        }

        return 'g:'.hash_hmac('sha256', $session->getId(), (string) config('app.key'));
    }

    private function isBot(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent === '' || preg_match(self::BOT_PATTERN, $agent) === 1;
    }
}
