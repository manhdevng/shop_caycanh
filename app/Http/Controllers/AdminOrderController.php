<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\GHNOrderService;
use App\Services\LoyaltyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller
{
    private const TABS = [
        'all' => ['label' => 'Tất cả', 'color' => 'blue', 'statuses' => []],
        'pending' => ['label' => 'Chờ xử lý', 'color' => 'slate', 'statuses' => ['pending', 'not_shipped', 'processing']],
        'ready' => ['label' => 'Chờ lấy hàng', 'color' => 'cyan', 'statuses' => ['ready_to_pick']],
        'picking' => ['label' => 'Đang lấy hàng', 'color' => 'cyan', 'statuses' => ['picking']],
        'delivering' => ['label' => 'Đang giao', 'color' => 'amber', 'statuses' => ['delivering', 'picked', 'storing', 'transporting', 'sorting']],
        'delivered' => ['label' => 'Thành công', 'color' => 'green', 'statuses' => ['delivered']],
        'return' => ['label' => 'Hoàn hàng', 'color' => 'orange', 'statuses' => ['return', 'returning', 'returned', 'return_transporting', 'return_sorting']],
        'cancelled' => ['label' => 'Đã hủy', 'color' => 'red', 'statuses' => ['cancelled']],
    ];

  
    public function index(Request $request)
    {
        $paymentLabels = [
            'pending' => 'Chờ thanh toán', 'initiated' => 'Đang chờ MoMo', 'paid' => 'Đã thanh toán',
            'failed' => 'Thanh toán thất bại', 'cancelled' => 'Đã hủy',
            'refund_pending' => 'Chờ hoàn tiền', 'refunded' => 'Đã hoàn tiền',
        ];
        $shippingLabels = Order::SHIPPING_LABELS;

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'awaiting_transfer', 'paid', 'paid_momo', 'cod_ordered', 'cod_paid', 'cancelled'])],
            'payment_status' => ['nullable', Rule::in(array_keys($paymentLabels))],
            'shipping_status' => ['nullable', Rule::in(array_keys($shippingLabels))],
            'gateway' => ['nullable', Rule::in(['cod', 'momo', 'bank_transfer', 'unknown'])],
            'tab' => ['nullable', Rule::in(array_keys(self::TABS))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'amount_desc', 'amount_asc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'date_to.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
            '*.date_format' => 'Ngày lọc không hợp lệ.', '*.in' => 'Giá trị bộ lọc không hợp lệ.',
        ]);

        $paymentId = DB::table('payment_transactions')->select('id')->whereColumn('order_id', 'orders.id')
            ->orderByRaw("CASE WHEN status IN ('paid', 'refund_pending', 'refunded') THEN 0 ELSE 1 END")
            ->orderByDesc('id')->limit(1);

        $source = DB::table('orders')->leftJoin('payment_transactions as payment', function ($join) use ($paymentId) {
                $join->on('payment.order_id', '=', 'orders.id')->where('payment.id', '=', $paymentId);
            })->select('orders.*')
            ->selectRaw("COALESCE(payment.gateway, CASE WHEN orders.status IN ('cod_ordered', 'cod_paid') THEN 'cod' WHEN orders.status = 'awaiting_transfer' THEN 'bank_transfer' WHEN orders.status IN ('paid', 'paid_momo') THEN 'momo' ELSE 'unknown' END) as gateway")
            ->selectRaw("COALESCE(payment.status, CASE WHEN orders.status IN ('cod_ordered', 'awaiting_transfer') THEN 'pending' WHEN orders.status IN ('cod_paid', 'paid_momo') THEN 'paid' ELSE orders.status END) as payment_status");

        $query = Order::query()->fromSub($source, 'orders');

        foreach (['status', 'payment_status', 'gateway'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $filters[$field]);
            }
        }

        if ($request->filled('search')) {
            $search = trim($filters['search']);
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('ghn_order_code', 'like', '%'.$search.'%')
                    ->orWhereHas('items.product', fn ($products) => $products->where('name', 'like', '%'.$search.'%'));
                if (preg_match('/^(?:#|DH)?0*(\d+)$/i', $search, $matches)) {
                    $query->orWhere('orders.id', $matches[1]);
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay());
        }

        $shippingCounts = (clone $query)->select('shipping_status')->selectRaw('COUNT(*) as total')
            ->groupBy('shipping_status')->pluck('total', 'shipping_status');

        $tabs = collect(self::TABS)->map(function ($tab, $key) use ($shippingCounts) {
            $tab['count'] = $key === 'all' ? $shippingCounts->sum()
                : collect($tab['statuses'])->sum(fn ($status) => $shippingCounts->get($status, 0));
            return $tab;
        });

        $activeTab = $filters['tab'] ?? 'all';
        if ($activeTab !== 'all') {
            $query->whereIn('shipping_status', self::TABS[$activeTab]['statuses']);
        }

        if ($request->filled('shipping_status')) {
            $query->where('shipping_status', $filters['shipping_status']);
        }

        [$column, $direction] = match ($filters['sort'] ?? 'newest') {
            'oldest' => ['created_at', 'asc'], 'amount_desc' => ['total_price', 'desc'],
            'amount_asc' => ['total_price', 'asc'], default => ['created_at', 'desc'],
        };

        $orders = $query->with('items.product')->orderBy($column, $direction)->orderBy('id', $direction)
            ->paginate((int) ($filters['per_page'] ?? 25))->withQueryString();

        return view('orders.index', compact('orders', 'filters', 'tabs', 'activeTab', 'paymentLabels', 'shippingLabels'));
    }

    // Chi tiết một đơn hàng: sản phẩm trong đơn + lịch sử các giao dịch thanh toán liên quan.
    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'paymentTransactions']);

        return view('admin.orders.show', compact('order'));
    }

    // Cập nhật trạng thái vận chuyển của đơn hàng (dùng cho thao tác nhanh từ danh sách/chi tiết).
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'shipping_status' => ['required', Rule::in(array_keys(Order::SHIPPING_LABELS))],
        ], [
            'shipping_status.required' => 'Vui lòng chọn trạng thái vận chuyển.',
            'shipping_status.in' => 'Trạng thái vận chuyển không hợp lệ.',
        ]);

        $newStatus = $validated['shipping_status'];
        $currentStage = Order::SHIPPING_STAGE_GROUPS[$order->shipping_status] ?? null;
        $newStage = Order::SHIPPING_STAGE_GROUPS[$newStatus] ?? null;

        // Chỉ chặn lùi khi cả trạng thái hiện tại lẫn trạng thái mới đều nằm
        // trong 4 nhóm mốc tiến trình giao hàng thông thường. Các trạng thái
        // ngoại lệ (huỷ, hoàn hàng...) không nằm trong nhóm này nên luôn được phép.
        if ($currentStage !== null && $newStage !== null && $newStage < $currentStage) {
            return back()->with('error', 'Không thể chuyển trạng thái vận chuyển lùi về giai đoạn trước đó.');
        }

        $order->update(['shipping_status' => $newStatus]);

        // Cộng điểm thành viên (nếu đủ điều kiện) ngay khi đơn chuyển sang
        // "delivered" — service tự kiểm tra idempotent (points_awarded).
        LoyaltyService::awardIfDelivered($order);

        return back()->with('success', 'Đã cập nhật trạng thái vận chuyển.');
    }

    // Hủy đơn hàng nếu đơn chưa bước vào giai đoạn giao hàng/đã hoàn tất/đã hủy trước đó.
    // Đồng thời hoàn lại tồn kho và huỷ vận đơn GHN (nếu có) tương ứng.
    public function cancel(Order $order, GHNOrderService $ghnOrderService)
    {
        $blocked = ['delivering', 'picked', 'storing', 'transporting', 'sorting', 'delivered', 'cancelled'];

        if (in_array($order->shipping_status, $blocked, true)) {
            return back()->with('error', 'Không thể hủy đơn đang giao hoặc đã hoàn tất/đã hủy.');
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing('items');

            // Hoàn lại tồn kho cho từng sản phẩm trong đơn khi huỷ đơn.
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::whereKey($item->product_id)->increment('stock', (int) $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled', 'shipping_status' => 'cancelled']);
        });

        // Huỷ vận đơn GHN sau khi phần dữ liệu nội bộ đã huỷ thành công.
        // cancelForOrder() không bao giờ ném exception (đã try/catch bên trong).
        $ghnResult = $ghnOrderService->cancelForOrder($order);

        if (! $ghnResult['success'] && empty($ghnResult['skipped'])) {
            // success=false và skipped=false (khác skipped=true) nghĩa là GHN
            // thực sự gọi lỗi (không phải do đơn chưa có vận đơn) -> chỉ log
            // cảnh báo, không chặn việc huỷ đơn ở phía hệ thống.
            Log::warning('Huỷ đơn hàng #' . $order->id . ' thành công nhưng huỷ vận đơn GHN thất bại', [
                'order_id' => $order->id,
                'ghn_order_code' => $order->ghn_order_code,
                'message' => $ghnResult['message'] ?? null,
                'error' => $ghnResult['error'] ?? null,
            ]);
        }

        return back()->with('success', 'Đã hủy đơn hàng.');
    }

    // Admin xác nhận ĐÃ NHẬN được tiền chuyển khoản của đơn "bank_transfer":
    // chuyển đơn sang "paid" rồi tạo vận đơn GHN — mirror chính xác nhánh
    // MoMo đã thanh toán (MomoController::completePayment()): set trạng thái
    // trong transaction trước, rồi tạo vận đơn GHN SAU khi đã commit (GHN là
    // gọi mạng ngoài, không nên giữ khoá DB trong lúc chờ).
    public function confirmTransfer(Request $request, Order $order, GHNOrderService $ghnOrderService)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:255',
        ], [
            'admin_note.max' => 'Ghi chú không được vượt quá 255 ký tự.',
        ]);

        if ($order->status !== 'awaiting_transfer') {
            return back()->with('error', 'Đơn hàng không ở trạng thái chờ chuyển khoản nên không thể xác nhận.');
        }

        try {
            DB::transaction(function () use ($order) {
                // Khoá lại đơn để đọc trạng thái mới nhất, tránh 2 request xử
                // lý (ví dụ admin bấm 2 lần / 2 tab) cùng xác nhận một đơn.
                $locked = Order::whereKey($order->id)->lockForUpdate()->first();

                if (! $locked || $locked->status !== 'awaiting_transfer') {
                    throw new \RuntimeException('Đơn hàng không ở trạng thái chờ chuyển khoản nên không thể xác nhận.');
                }

                $locked->update([
                    'status' => 'paid',
                    'shipping_status' => 'processing',
                    'transfer_confirmed_at' => now(),
                    'transfer_confirmed_by' => auth()->id(),
                ]);

                // Đồng bộ luôn dòng payment_transactions của đơn (nếu có)
                // sang 'paid' + paid_at — nếu không làm, index() sẽ mãi hiển
                // thị payment_status='pending' dù đơn đã 'paid' (vì subquery
                // ở đó COALESCE ưu tiên đọc từ payment_transactions.status).
                // Dùng đúng cách chọn dòng mà subquery $paymentId trong
                // index() đang dùng để cập nhật ĐÚNG dòng admin sẽ nhìn thấy.
                $transaction = $this->latestPaymentTransactionFor($locked->id);

                if ($transaction) {
                    $transaction->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'message' => 'Admin xác nhận đã nhận được tiền chuyển khoản.',
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Tạo vận đơn GHN sau khi đã xác nhận thanh toán — tải lại đơn kèm
        // 'items.product', 'items.variant' để GHNOrderService lấy đúng
        // khối lượng/tên phân loại (giống MomoController::completePayment()).
        $order = Order::with(['items.product', 'items.variant'])->find($order->id);
        $ghnResponse = $ghnOrderService->create($order, isPaid: true);

        if (($ghnResponse['code'] ?? null) === 200 && ! empty($ghnResponse['data']['order_code'])) {
            $order->update([
                'ghn_order_code' => $ghnResponse['data']['order_code'],
                'ghn_total_fee' => $ghnResponse['data']['total_fee'] ?? $order->ghn_total_fee,
                'shipping_status' => 'ready_to_pick',
            ]);
        } else {
            Log::warning('Xác nhận chuyển khoản đơn #' . $order->id . ' thành công nhưng tạo vận đơn GHN thất bại', [
                'order_id' => $order->id,
                'response' => $ghnResponse,
            ]);
            $order->update(['shipping_status' => 'not_shipped']);
        }

        return back()->with('success', 'Đã xác nhận thanh toán chuyển khoản cho đơn hàng #' . $order->id . '.');
    }

    // Admin TỪ CHỐI đơn "bank_transfer" đang chờ chuyển khoản (ví dụ không
    // nhận được tiền sau thời hạn): huỷ đơn, hoàn kho, trả lại lượt dùng
    // voucher (nếu có) — mirror phần hoàn kho của cancel() ở trên, đồng thời
    // hoàn tác đúng những gì store() đã làm khi tạo đơn (tăng used_count +
    // đánh dấu voucher đã dùng trong ví khách).
    public function rejectTransfer(Request $request, Order $order, GHNOrderService $ghnOrderService)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:255',
        ], [
            'admin_note.max' => 'Ghi chú không được vượt quá 255 ký tự.',
        ]);

        if ($order->status !== 'awaiting_transfer') {
            return back()->with('error', 'Đơn hàng không ở trạng thái chờ chuyển khoản nên không thể từ chối.');
        }

        try {
            DB::transaction(function () use ($order) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->first();

                if (! $locked || $locked->status !== 'awaiting_transfer') {
                    throw new \RuntimeException('Đơn hàng không ở trạng thái chờ chuyển khoản nên không thể từ chối.');
                }

                $locked->loadMissing('items');

                // Hoàn lại tồn kho cho từng sản phẩm trong đơn — mirror
                // AdminOrderController::cancel().
                foreach ($locked->items as $item) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->increment('stock', (int) $item->quantity);
                    }
                }

                // Trả lại lượt dùng voucher (nếu đơn có áp mã) — hoàn tác
                // đúng thao tác store() đã làm lúc tạo đơn: giảm used_count
                // và gỡ dấu "đã dùng" khỏi ví khách để mã có thể dùng lại.
                if ($locked->voucher_id) {
                    Voucher::whereKey($locked->voucher_id)->decrement('used_count');

                    DB::table('user_voucher')
                        ->where('voucher_id', $locked->voucher_id)
                        ->where('order_id', $locked->id)
                        ->update(['used_at' => null, 'order_id' => null, 'updated_at' => now()]);
                }

                $locked->update(['status' => 'cancelled', 'shipping_status' => 'cancelled']);

                // Đồng bộ dòng payment_transactions (nếu có) sang 'cancelled'
                // cho nhất quán với orders.status — cùng lý do và cách chọn
                // dòng như trong confirmTransfer().
                $transaction = $this->latestPaymentTransactionFor($locked->id);

                if ($transaction) {
                    $transaction->update([
                        'status' => 'cancelled',
                        'message' => 'Admin từ chối - không nhận được chuyển khoản.',
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Huỷ vận đơn GHN (nếu có) sau khi phần dữ liệu nội bộ đã huỷ thành
        // công — mirror cancel(). Đơn bank_transfer ở awaiting_transfer luôn
        // chưa có ghn_order_code (chưa từng tạo vận đơn) nên thực chất luôn
        // "skipped", giữ lại lệnh gọi này để phòng trường hợp đặc biệt và
        // nhất quán hành vi với cancel().
        $ghnResult = $ghnOrderService->cancelForOrder($order);

        if (! $ghnResult['success'] && empty($ghnResult['skipped'])) {
            Log::warning('Từ chối chuyển khoản đơn hàng #' . $order->id . ' thành công nhưng huỷ vận đơn GHN thất bại', [
                'order_id' => $order->id,
                'ghn_order_code' => $order->ghn_order_code,
                'message' => $ghnResult['message'] ?? null,
                'error' => $ghnResult['error'] ?? null,
            ]);
        }

        return back()->with('success', 'Đã từ chối và hủy đơn hàng chuyển khoản #' . $order->id . '.');
    }

    /**
     * Lấy đúng dòng payment_transactions mà index() sẽ hiển thị cho đơn này
     * — dùng CÙNG thứ tự ưu tiên với subquery $paymentId trong index() (ưu
     * tiên dòng có status thuộc paid/refund_pending/refunded, nếu không có
     * thì lấy dòng mới nhất theo id) để không cập nhật nhầm dòng khác dòng
     * admin đang nhìn thấy. Luôn gọi bên trong DB::transaction() đang giữ
     * khoá đơn hàng nên khoá thêm dòng này (lockForUpdate) cho an toàn.
     * Trả về null nếu đơn không có dòng transaction nào (dữ liệu cũ/bất
     * thường) — caller PHẢI tự kiểm tra null, không được giả định luôn có.
     */
    private function latestPaymentTransactionFor(int $orderId): ?PaymentTransaction
    {
        return PaymentTransaction::where('order_id', $orderId)
            ->orderByRaw("CASE WHEN status IN ('paid', 'refund_pending', 'refunded') THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }
}
