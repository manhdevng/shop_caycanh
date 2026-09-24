<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    /**
     * Lưu mã giảm giá vào ví của user hiện tại (bảng user_voucher). Chỉ
     * kiểm tra mã còn hiệu lực CƠ BẢN (is_active, thời hạn, usage_limit) —
     * KHÔNG kiểm tra giỏ hàng/đơn tối thiểu ở bước này, vì lưu để dành là
     * hợp lệ dù giỏ hàng đang trống hoặc chưa đủ điều kiện.
     */
    public function save(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
        ]);

        $code = strtoupper(trim((string) $request->input('code')));

        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Mã giảm giá không tồn tại.'], 422);
        }

        $basicError = $this->basicValidationError($voucher);

        if ($basicError !== null) {
            return response()->json(['success' => false, 'message' => $basicError], 422);
        }

        $user = $request->user();

        $existing = DB::table('user_voucher')
            ->where('user_id', $user->id)
            ->where('voucher_id', $voucher->id)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => true,
                'already_saved' => true,
                'message' => 'Mã này đã có trong ví của bạn.',
            ]);
        }

        DB::table('user_voucher')->insert([
            'user_id' => $user->id,
            'voucher_id' => $voucher->id,
            'saved_at' => now(),
            'used_at' => null,
            'order_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'already_saved' => false,
            'message' => 'Đã lưu mã "' . $voucher->code . '" vào ví của bạn.',
        ]);
    }

    /**
     * Áp dụng mã giảm giá cho giỏ hàng hiện tại (session).
     * Chỉ lưu tạm trong session (key 'voucher') vì đơn hàng chưa được tạo —
     * giá trị này sẽ được TÍNH LẠI TỪ ĐẦU trong OrderController::store()
     * ngay trong transaction, không tin dữ liệu đã tính sẵn ở đây.
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
        ]);

        $cart = session('cart', []);

        if (empty($cart)) {
            $message = 'Giỏ hàng đang trống, không thể áp dụng mã giảm giá.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $code = strtoupper(trim((string) $request->input('code')));

        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            $message = 'Mã giảm giá không tồn tại.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $user = $request->user();

        // Luồng "gõ tay mã lạ hợp lệ": nếu mã còn hiệu lực cơ bản (is_active,
        // thời hạn, usage_limit) nhưng user chưa lưu vào ví, hệ thống tự lưu
        // giúp rồi áp luôn — khách không phải thao tác "Lưu" rồi "Dùng" 2
        // lần. Chỉ tự lưu khi các điều kiện khác đều hợp lệ, để không lưu
        // rác vào ví 1 mã đã hết hạn/hết lượt.
        if ($user !== null && $this->basicValidationError($voucher) === null) {
            DB::table('user_voucher')->insertOrIgnore([
                'user_id' => $user->id,
                'voucher_id' => $voucher->id,
                'saved_at' => now(),
                'used_at' => null,
                'order_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $error = $voucher->validationErrorForCart($cart, $user);

        if ($error !== null) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $error], 422);
            }

            return back()->with('error', $error);
        }

        $discountAmount = $voucher->calculateDiscountAmount((float) $voucher->eligibleSubtotal($cart));

        session(['voucher' => [
            'id' => $voucher->id,
            'code' => $voucher->code,
            'discount_amount' => $discountAmount,
        ]]);

        $message = 'Áp dụng mã giảm giá "' . $voucher->code . '" thành công. Bạn được giảm ' . number_format($discountAmount, 0, ',', '.') . 'đ.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'voucher' => session('voucher'),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Trang public "Săn mã giảm giá" — liệt kê các voucher đang khả dụng.
     * Không yêu cầu đăng nhập, khách vãng lai cũng xem được; chỉ khi bấm
     * "Dùng mã"/"Lưu" mới cần đăng nhập (route voucher.apply/voucher.save
     * đã yêu cầu auth).
     */
    public function browse()
    {
        $userId = auth()->id();

        // Eager-load chỉ bản ghi ví của user hiện tại (nếu đã đăng nhập) để
        // isSavedBy()/isUsedBy() ở view không phát sinh N+1 truy vấn.
        $vouchers = Voucher::available()
            ->when($userId, function ($query) use ($userId) {
                $query->with(['savedByUsers' => function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                }]);
            })
            ->get();

        $cart = session('cart', []);
        $subtotal = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);

        return view('vouchers.browse', compact('vouchers', 'subtotal'));
    }

    /**
     * Trang "Ví voucher của tôi": liệt kê các mã user đã lưu, phân thành 3
     * nhóm: còn dùng được, đã dùng, hết hạn/hết lượt.
     */
    public function wallet(Request $request)
    {
        $user = $request->user();
        $cart = session('cart', []);
        $subtotal = collect($cart)->sum(fn ($item) => $item['price'] * $item['quantity']);

        $savedVouchers = $user->savedVouchers()->with(['products', 'categories'])->get();

        $now = now();

        $used = $savedVouchers->filter(fn ($voucher) => $voucher->pivot->used_at !== null)->values();

        $notUsed = $savedVouchers->filter(fn ($voucher) => $voucher->pivot->used_at === null)->values();

        $expired = $notUsed->filter(function ($voucher) use ($now) {
            $expiredByDate = $voucher->expires_at !== null && $now->gt($voucher->expires_at);
            $expiredByLimit = $voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit;

            return !$voucher->is_active || $expiredByDate || $expiredByLimit;
        })->values();

        $usable = $notUsed->reject(function ($voucher) use ($expired) {
            return $expired->contains('id', $voucher->id);
        })->values();

        return view('vouchers.wallet', compact('usable', 'used', 'expired', 'subtotal'));
    }

    // Xoá mã giảm giá đang áp dụng khỏi session
    public function remove(Request $request)
    {
        session()->forget('voucher');

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã gỡ mã giảm giá.');
    }

    /**
     * Kiểm tra điều kiện hợp lệ CƠ BẢN của voucher — không liên quan tới
     * giỏ hàng hay user (is_active, thời hạn, usage_limit). Dùng để quyết
     * định có cho phép LƯU mã vào ví hay không (save(), và tự-lưu trong
     * apply()) — bước lưu không cần biết giỏ hàng đã đủ điều kiện chưa.
     */
    private function basicValidationError(Voucher $voucher): ?string
    {
        if (!$voucher->is_active) {
            return 'Mã giảm giá đã ngừng áp dụng.';
        }

        $now = now();

        if (($voucher->starts_at && $now->lt($voucher->starts_at))
            || ($voucher->expires_at && $now->gt($voucher->expires_at))) {
            return 'Mã giảm giá đã hết hạn hoặc chưa bắt đầu.';
        }

        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            return 'Mã giảm giá đã hết lượt sử dụng.';
        }

        return null;
    }
}
