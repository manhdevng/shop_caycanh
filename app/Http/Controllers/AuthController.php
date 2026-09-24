<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Hiển thị form đăng ký
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    // Xử lý đăng ký người dùng
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 'role' không nằm trong $fillable của User (chống mass-assignment
            // nâng quyền) nên phải gán trực tiếp rồi save().
            $user->role = 'customer'; // Mặc định role là customer
            $user->save();

            // Gửi email xác thực
            $user->sendEmailVerificationNotification();

            Auth::login($user);

            return redirect()->route('verification.notice')
                ->with('success', 'Đăng ký thành công! Vui lòng kiểm tra email để xác thực tài khoản.');
        } catch (\Exception $e) {
            Log::error('Registration failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Đăng ký thất bại. Vui lòng thử lại.');
        }
    }

    // Hiển thị form đăng nhập
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Xử lý đăng nhập
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();

            // Phân quyền điều hướng sau khi đăng nhập.
            // Lưu ý: KHÔNG dùng redirect()->intended() cho admin, vì trang chủ
            // shop ('/') cũng nằm sau middleware 'auth' — nếu admin từng ghé
            // trang đó lúc chưa đăng nhập, session sẽ lưu intended = '/' và
            // đưa nhầm admin sang giao diện mua hàng thay vì trang quản trị.
            if (Auth::user()->isAdmin()) {
                $request->session()->forget('url.intended');
                return redirect()->route('admin.dashboard');
            }

            return redirect()->intended(route('shop.index'));
        }

        return redirect()->back()->with('error', 'Email hoặc mật khẩu không đúng.');
    }

    // Xử lý đăng xuất
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
