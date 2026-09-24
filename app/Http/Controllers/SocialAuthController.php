<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // Chuyển hướng người dùng sang Google để đăng nhập
    public function redirectToGoogle()
    {
        // Nếu chưa cấu hình client_id/client_secret (ví dụ môi trường dev/test
        // chưa có khóa Google thật) thì không được gọi Socialite — tránh lỗi 500.
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')
                ->with('error', 'Đăng nhập bằng Google hiện chưa khả dụng. Vui lòng đăng nhập bằng email.');
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (\Throwable $e) {
            Log::error('Lỗi khởi tạo đăng nhập Google: ' . $e->getMessage());
            return redirect()->route('login')
                ->with('error', 'Không thể kết nối tới Google lúc này. Vui lòng thử lại sau.');
        }
    }

    // Xử lý callback Google trả về sau khi người dùng cấp quyền
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            Log::error('Lỗi callback đăng nhập Google: ' . $e->getMessage());
            return redirect()->route('login')
                ->with('error', 'Đăng nhập Google thất bại hoặc đã bị huỷ. Vui lòng thử lại.');
        }

        try {
            // Tìm user theo google_id trước, nếu chưa có thì thử liên kết theo
            // email (tài khoản đã đăng ký bằng email trùng với email Google),
            // cuối cùng mới tạo mới. Bọc trong DB::transaction() để tránh
            // race condition tạo trùng khi có nhiều request đăng nhập cùng lúc.
            $user = DB::transaction(function () use ($googleUser) {
                $user = User::where('google_id', $googleUser->getId())->first();
                if ($user) {
                    return $user;
                }

                $user = User::where('email', $googleUser->getEmail())->first();
                if ($user) {
                    // Liên kết tài khoản email đã có sẵn với Google, đồng thời
                    // coi như email đã xác thực (Google đã xác thực hộ).
                    $user->google_id = $googleUser->getId();
                    $user->email_verified_at = $user->email_verified_at ?? now();
                    $user->save();
                    return $user;
                }

                $newUser = User::create([
                    'name' => $googleUser->getName() ?: $googleUser->getEmail(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    // Người dùng đăng nhập qua Google không cần mật khẩu, nhưng
                    // cột password NOT NULL nên tạo một mật khẩu ngẫu nhiên,
                    // không sử dụng để đăng nhập thường.
                    'password' => bcrypt(Str::random(32)),
                    'role' => 'customer', // Mặc định role là customer, giống đăng ký thường
                ]);

                // 'email_verified_at' không nằm trong $fillable của User (có
                // chủ đích, để tránh mass-assignment xác thực email từ input
                // thường) nên phải gán trực tiếp rồi save() thay vì đưa vào
                // mảng create() ở trên — nếu không, Eloquent sẽ âm thầm bỏ
                // qua field này và user mới sẽ bị middleware 'verified' chặn
                // ngay sau khi đăng nhập Google, dù Google đã xác thực hộ.
                $newUser->email_verified_at = now();
                $newUser->save();

                return $newUser;
            });
        } catch (\Throwable $e) {
            Log::error('Lỗi xử lý tài khoản đăng nhập Google: ' . $e->getMessage());
            return redirect()->route('login')
                ->with('error', 'Đăng nhập Google thất bại. Vui lòng thử lại hoặc đăng nhập bằng email.');
        }

        Auth::login($user, true);

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')->with('success', 'Đăng nhập bằng Google thành công!');
        }

        return redirect()->route('shop.index')->with('success', 'Đăng nhập bằng Google thành công!');
    }
}
