<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,customer',
        ], [
            'name.required'     => 'Vui lòng nhập tên.',
            'email.required'    => 'Vui lòng nhập email.',
            'email.email'       => 'Email không đúng định dạng.',
            'email.unique'      => 'Email đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min'      => 'Mật khẩu tối thiểu 6 ký tự.',
            'role.required'     => 'Vui lòng chọn vai trò.',
            'role.in'           => 'Vai trò không hợp lệ.',
        ]);

        // 'role' không nằm trong $fillable → gán tường minh để tránh bị bỏ qua khi mass-assign
        $user = new User([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->forceFill(['role' => $validated['role']])->save();

        return redirect()->route('admin.users.index')->with('success', 'Thêm người dùng thành công.');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required|in:admin,customer',
        ], [
            'name.required'  => 'Vui lòng nhập tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email'    => 'Email không đúng định dạng.',
            'email.unique'   => 'Email đã được sử dụng.',
            'role.required'  => 'Vui lòng chọn vai trò.',
            'role.in'        => 'Vai trò không hợp lệ.',
        ]);

        // Không cho admin tự hạ quyền của chính mình (tránh tự khoá khỏi trang quản trị)
        if ($user->is(auth()->user()) && $validated['role'] !== 'admin') {
            return back()->withInput()->with('error', 'Bạn không thể tự thay đổi vai trò admin của chính mình.');
        }

        $error = DB::transaction(function () use ($user, $validated) {
            if ($validated['role'] !== 'admin') {
                // Khoá các dòng admin để hai thao tác hạ quyền đồng thời không cùng vượt qua kiểm tra
                $adminIds = User::where('role', 'admin')->lockForUpdate()->pluck('id');

                if ($adminIds->contains($user->id) && $adminIds->count() <= 1) {
                    return 'Không thể hạ quyền admin cuối cùng của hệ thống.';
                }
            }

            $user->fill([
                'name'  => $validated['name'],
                'email' => $validated['email'],
            ]);
            // 'role' không nằm trong $fillable → gán tường minh
            $user->forceFill(['role' => $validated['role']])->save();

            return null;
        });

        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        return redirect()->route('admin.users.index')->with('success', 'Cập nhật người dùng thành công.');
    }

    public function destroy(User $user)
    {
        // Không cho admin tự xoá tài khoản của chính mình
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Bạn không thể tự xóa tài khoản của chính mình.');
        }

        $error = DB::transaction(function () use ($user) {
            // Khoá các dòng admin để hai thao tác xoá đồng thời không cùng vượt qua kiểm tra
            $adminIds = User::where('role', 'admin')->lockForUpdate()->pluck('id');

            if ($adminIds->contains($user->id) && $adminIds->count() <= 1) {
                return 'Không thể xóa admin cuối cùng của hệ thống.';
            }

            $user->delete();

            return null;
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return redirect()->route('admin.users.index')->with('success', 'Xóa người dùng thành công.');
    }
}
