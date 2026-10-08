<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sổ địa chỉ nhận hàng (kiểu Shopee) — dùng từ hộp "Thay đổi địa chỉ" ở trang
 * thanh toán qua AJAX. Địa chỉ mới được thêm khi đặt hàng với ô "Lưu vào sổ
 * địa chỉ" (xem OrderController::saveAddressFromOrder()).
 */
class UserAddressController extends Controller
{
    public function setDefault(Request $request, UserAddress $address)
    {
        $this->authorizeOwner($address);

        $address->makeDefault();

        return $this->respond($request, 'Đã đặt làm địa chỉ mặc định.');
    }

    public function destroy(Request $request, UserAddress $address)
    {
        $this->authorizeOwner($address);

        $wasDefault = $address->is_default;
        $address->delete();

        // Xoá địa chỉ mặc định thì địa chỉ mới nhất còn lại lên làm mặc định.
        if ($wasDefault) {
            Auth::user()->addresses()->first()?->makeDefault();
        }

        return $this->respond($request, 'Đã xoá địa chỉ.');
    }

    private function authorizeOwner(UserAddress $address): void
    {
        abort_unless($address->user_id === Auth::id(), 403);
    }

    private function respond(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'addresses' => Auth::user()->addresses()->get()->map(fn (UserAddress $a) => self::toArray($a)),
            ]);
        }

        return back()->with('success', $message);
    }

    // Dữ liệu 1 địa chỉ cho JS trang thanh toán.
    public static function toArray(UserAddress $address): array
    {
        return [
            'id' => $address->id,
            'name' => $address->name,
            'phone' => $address->phone,
            'province_id' => $address->province_id,
            'province_name' => $address->province_name,
            'district_id' => $address->district_id,
            'district_name' => $address->district_name,
            'ward_code' => $address->ward_code,
            'ward_name' => $address->ward_name,
            'address' => $address->address,
            'full_address' => $address->fullAddress(),
            'is_default' => $address->is_default,
        ];
    }
}
