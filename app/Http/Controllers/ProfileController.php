<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Hồ sơ cá nhân của khách hàng (mã yêu cầu C2.3).
 *
 * Cho phép người dùng đang đăng nhập xem/sửa thông tin cá nhân (tên, số điện
 * thoại, địa chỉ, ảnh đại diện) và đổi mật khẩu. Toàn bộ thao tác chỉ áp dụng
 * cho auth()->user() — không cho phép sửa hồ sơ của người dùng khác thông qua
 * bất kỳ tham số nào trên request/URL.
 */
class ProfileController extends Controller
{
    /** Hiển thị trang hồ sơ cá nhân. */
    public function show(Request $request)
    {
        return view('profile.show', [
            'user' => $request->user(),
            'orderStageCounts' => $this->orderStageCounts($request->user()->id),
        ]);
    }

    /**
     * Dải "Đơn mua" trên trang hồ sơ: 4 con số dẫn sang các tab của
     * orders.history. 3 khoá đầu trùng key tab của Order::TABS nên view link
     * thẳng được; 'cho-danh-gia' là con số riêng (không có tab tương ứng,
     * view trỏ về tab 'da-giao').
     *
     * @return array<string, int>
     */
    private function orderStageCounts(int $userId): array
    {
        $base = fn () => Order::where('user_id', $userId);

        return [
            'cho-thanh-toan' => $base()->forTab('cho-thanh-toan')->count(),
            'cho-lay-hang' => $base()->forTab('cho-lay-hang')->count(),
            'dang-giao' => $base()->forTab('dang-giao')->count(),
            // Chờ đánh giá: đơn đã nhận hàng mà khách chưa viết đánh giá nào
            // cho đơn đó (reviews.order_id do ReviewController ghi lại).
            'cho-danh-gia' => $base()
                ->whereIn('status', Order::PAID_OR_COD_STATUSES)
                ->where('shipping_status', 'delivered')
                ->whereNotExists(function ($query) use ($userId) {
                    $query->selectRaw(1)
                        ->from('reviews')
                        ->whereColumn('reviews.order_id', 'orders.id')
                        ->where('reviews.user_id', $userId);
                })
                ->count(),
        ];
    }

    /** Cập nhật thông tin cá nhân (tên, điện thoại, địa chỉ, ảnh đại diện). */
    public function update(Request $request)
    {
        // Luôn thao tác trên user đang đăng nhập, không tin bất kỳ id nào từ client.
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^0[0-9]{9,10}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.string' => 'Họ tên không hợp lệ.',
            'name.max' => 'Họ tên không được vượt quá 255 ký tự.',
            'phone.string' => 'Số điện thoại không hợp lệ.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'phone.regex' => 'Số điện thoại không đúng định dạng (phải bắt đầu bằng số 0 và có 10-11 chữ số).',
            'address.string' => 'Địa chỉ không hợp lệ.',
            'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',
            'avatar.image' => 'Ảnh đại diện phải là tệp hình ảnh.',
            'avatar.max' => 'Ảnh đại diện không được vượt quá 2MB.',
        ]);

        $user->name = $validated['name'];
        $user->phone = $validated['phone'] ?? null;
        $user->address = $validated['address'] ?? null;

        if ($request->hasFile('avatar')) {
            $duongDanCu = $user->avatar;

            $duongDanMoi = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $duongDanMoi;

            // Dọn ảnh cũ nếu có, tránh rác trong storage.
            if ($duongDanCu) {
                Storage::disk('public')->delete($duongDanCu);
            }
        }

        $user->save();

        return redirect()->route('profile.show')->with('success', 'Cập nhật hồ sơ cá nhân thành công.');
    }

    /** Đổi mật khẩu (yêu cầu nhập đúng mật khẩu hiện tại). */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Mật khẩu hiện tại không đúng.',
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('profile.show')->with('success', 'Đổi mật khẩu thành công.');
    }
}
