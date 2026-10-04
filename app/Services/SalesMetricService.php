<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Nguồn sự thật duy nhất cho "đơn đã bán" (doanh số đã thu tiền).
 *
 * Đơn đủ điều kiện khi:
 *  - giao dịch thanh toán đại diện của đơn có status = 'paid'. Giao dịch đại diện
 *    ưu tiên giao dịch đã thu/hoàn tiền (paid, refund_pending, refunded) trước
 *    các lần thử thanh toán mới, nên một lần thử lại thất bại không che mất
 *    tiền đã thu; còn đơn đang chờ hoàn/đã hoàn tiền thì bị loại;
 *  - hoặc đơn cũ chưa có payment_transactions nhưng orders.status thuộc
 *    LEGACY_PAID_STATUSES (paid, cod_paid, paid_momo);
 *  - và đơn không bị huỷ, không huỷ vận chuyển, không thuộc luồng hoàn hàng GHN,
 *    không có created_at ở tương lai.
 * Đơn COD mới đặt (cod_ordered, giao dịch pending) chưa thu tiền nên KHÔNG tính.
 *
 * Ví dụ dùng (đừng tự viết lại điều kiện ở controller khác):
 *
 *   $sales = app(SalesMetricService::class);
 *   // Đơn đã bán trong 30 ngày gần nhất (tính cả hôm nay)
 *   $orders = $sales->qualifiedOrders(now()->startOfDay()->subDays(29), now()->startOfDay()->addDay());
 *   // Lọc order_items theo đơn đã bán
 *   DB::table('order_items')->whereIn('order_id', $sales->qualifiedOrderIds($from, $until));
 *   // Sản phẩm bán chạy: product_id, total_qty, total_revenue
 *   $sales->productSales($from, $until)->orderByDesc('total_qty')->limit(8)->pluck('product_id');
 *   // Theo kỳ báo cáo
 *   $period = ReportPeriod::fromRequest($request);
 *   $sales->qualifiedOrders($period->from, $period->untilExclusive);
 */
class SalesMetricService
{
    /** Trạng thái đơn cũ (chưa có payment_transactions) được coi là đã thu tiền. */
    public const LEGACY_PAID_STATUSES = ['paid', 'cod_paid', 'paid_momo'];

    /** Giao dịch đã thu/hoàn tiền được ưu tiên làm giao dịch đại diện của đơn. */
    public const SETTLED_PAYMENT_STATUSES = ['paid', 'refund_pending', 'refunded'];

    /**
     * Đơn đã bán, lọc orders.created_at trong [from, untilExclusive).
     * Cận null nghĩa là không giới hạn phía đó.
     *
     * @return Builder<Order>
     */
    public function qualifiedOrders(?CarbonInterface $from = null, ?CarbonInterface $untilExclusive = null): Builder
    {
        $paymentStatus = DB::table('payment_transactions')->select('status')
            ->whereColumn('order_id', 'orders.id')
            ->orderByRaw($this->settledFirstSql())
            ->orderByDesc('id')->limit(1);

        return Order::query()
            ->where('orders.created_at', '<=', now())
            ->when($from, fn (Builder $query) => $query->where('orders.created_at', '>=', $from))
            ->when($untilExclusive, fn (Builder $query) => $query->where('orders.created_at', '<', $untilExclusive))
            ->where('orders.status', '!=', 'cancelled')
            // Loại đơn huỷ vận chuyển và MỌI trạng thái thuộc luồng hoàn hàng GHN.
            ->where(function (Builder $query) {
                $query->whereNull('orders.shipping_status')
                    ->orWhereNotIn('orders.shipping_status', array_merge(['cancelled'], Order::SHIPPING_RETURN_STATUSES));
            })
            ->where(function (Builder $query) use ($paymentStatus) {
                $query->where($paymentStatus, 'paid')
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereDoesntHave('paymentTransactions')
                            ->whereIn('orders.status', self::LEGACY_PAID_STATUSES);
                    });
            });
    }

    /** Subquery chỉ chọn orders.id của đơn đã bán, dùng cho whereIn('order_id', ...). */
    public function qualifiedOrderIds(?CarbonInterface $from = null, ?CarbonInterface $untilExclusive = null): Builder
    {
        return $this->qualifiedOrders($from, $untilExclusive)->select('orders.id');
    }

    /**
     * Số lượng và doanh thu (price × quantity của order_items) theo sản phẩm
     * trong các đơn đã bán. Cột: product_id, total_qty, total_revenue.
     * Không join products để không mất dòng của sản phẩm đã ẩn/xoá; nơi gọi
     * tự join/lọc is_active nếu cần.
     */
    public function productSales(?CarbonInterface $from = null, ?CarbonInterface $untilExclusive = null): QueryBuilder
    {
        return DB::table('order_items')
            ->whereIn('order_items.order_id', $this->qualifiedOrderIds($from, $untilExclusive))
            ->select('order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as total_qty, SUM(order_items.price * order_items.quantity) as total_revenue')
            ->groupBy('order_items.product_id');
    }

    /** Tổng doanh thu (orders.total_price) của đơn đã bán trong kỳ. */
    public function revenue(?CarbonInterface $from = null, ?CarbonInterface $untilExclusive = null): float
    {
        return (float) $this->qualifiedOrders($from, $untilExclusive)->sum('orders.total_price');
    }

    private function settledFirstSql(): string
    {
        $statuses = "'".implode("', '", self::SETTLED_PAYMENT_STATUSES)."'";

        return "CASE WHEN status IN ($statuses) THEN 0 ELSE 1 END";
    }
}
