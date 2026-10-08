<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sổ địa chỉ nhận hàng (kiểu Shopee). Quản lý đầy đủ ở trang Hồ sơ (thêm/sửa/
 * xoá/đặt mặc định); trang thanh toán chọn sẵn địa chỉ mặc định và gọi
 * setDefault()/destroy() qua AJAX. Địa chỉ mới cũng được thêm khi đặt hàng với
 * ô "Lưu vào sổ địa chỉ" (xem OrderController::saveAddressFromOrder()).
 */
class UserAddressController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = Auth::user();

        $address = $user->addresses()->create($data);

        // Địa chỉ đầu tiên luôn là mặc định; các địa chỉ sau tuỳ khách chọn.
        if ($request->boolean('is_default') || $user->addresses()->count() === 1) {
            $address->makeDefault();
        }

        return redirect()->to(route('profile.show').'#so-dia-chi')->with('success', 'Đã thêm địa chỉ mới.');
    }

    public function update(Request $request, UserAddress $address)
    {
        $this->authorizeOwner($address);

        $address->update($this->validated($request));

        if ($request->boolean('is_default')) {
            $address->makeDefault();
        }

        return redirect()->to(route('profile.show').'#so-dia-chi')->with('success', 'Đã cập nhật địa chỉ.');
    }

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

    private function validated(Request $request): array
    {
        return $request->validateWithBag('address', [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^0[0-9]{9,10}$/'],
            'province_id' => ['required', 'integer'],
            'province_name' => ['required', 'string', 'max:255'],
            'district_id' => ['required', 'integer'],
            'district_name' => ['required', 'string', 'max:255'],
            'ward_code' => ['required', 'string', 'max:20'],
            'ward_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên người nhận.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không đúng định dạng (bắt đầu bằng 0, 10-11 chữ số).',
            'province_id.required' => 'Vui lòng chọn Tỉnh/Thành.',
            'province_name.required' => 'Vui lòng chọn Tỉnh/Thành.',
            'district_id.required' => 'Vui lòng chọn Quận/Huyện.',
            'district_name.required' => 'Vui lòng chọn Quận/Huyện.',
            'ward_code.required' => 'Vui lòng chọn Phường/Xã.',
            'ward_name.required' => 'Vui lòng chọn Phường/Xã.',
            'address.required' => 'Vui lòng nhập địa chỉ cụ thể (số nhà, tên đường).',
        ]);
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

        return redirect()->to(route('profile.show').'#so-dia-chi')->with('success', $message);
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
