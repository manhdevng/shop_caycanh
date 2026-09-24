<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AdminChatController extends Controller
{
    // Danh sách khách hàng đã từng nhắn tin qua lại với BẤT KỲ admin nào (hộp thư chung).
    // Trước đây lọc theo Auth::id() của admin đang đăng nhập nên khi có >1 tài khoản admin,
    // admin nào không phải người "sở hữu" hội thoại sẽ không thấy được khách đã nhắn tin.
    public function getUsers()
    {
        $adminIds = User::where('role', 'admin')->pluck('id');

        if ($adminIds->isEmpty()) {
            return response()->json([]);
        }

        // Các dòng tin nhắn mà một bên là admin, bên kia không phải admin (tức là khách hàng).
        // Với mỗi dòng, xác định cột nào chứa id khách hàng bằng CASE, rồi gom nhóm theo khách đó.
        $rows = Message::query()
            ->whereIn('sender_id', $adminIds)
            ->orWhereIn('receiver_id', $adminIds)
            ->get(['sender_id', 'receiver_id', 'is_read', 'created_at']);

        $stats = []; // [customer_id => ['last_message_at' => ..., 'unread_count' => int]]

        foreach ($rows as $row) {
            $senderIsAdmin = $adminIds->contains($row->sender_id);
            $receiverIsAdmin = $adminIds->contains($row->receiver_id);

            // Bỏ qua tin nhắn giữa admin với admin (không phải hội thoại với khách hàng).
            if ($senderIsAdmin && $receiverIsAdmin) {
                continue;
            }

            $customerId = $senderIsAdmin ? $row->receiver_id : $row->sender_id;

            if (! isset($stats[$customerId])) {
                $stats[$customerId] = [
                    'last_message_at' => $row->created_at,
                    'unread_count' => 0,
                ];
            }

            if ($row->created_at && (! $stats[$customerId]['last_message_at'] || $row->created_at->gt($stats[$customerId]['last_message_at']))) {
                $stats[$customerId]['last_message_at'] = $row->created_at;
            }

            // Khách nhắn cho admin (sender = khách, receiver = admin) mà đội admin chưa ai đọc.
            if (! $senderIsAdmin && $receiverIsAdmin && ! $row->is_read) {
                $stats[$customerId]['unread_count']++;
            }
        }

        if (empty($stats)) {
            return response()->json([]);
        }

        $users = User::whereIn('id', array_keys($stats))
            ->select('id', 'name', 'email')
            ->get()
            ->map(function ($user) use ($stats) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'last_message_at' => optional($stats[$user->id]['last_message_at'])->toIso8601String(),
                    'unread_count' => $stats[$user->id]['unread_count'],
                ];
            })
            ->sortByDesc('last_message_at')
            ->values();

        return response()->json($users);
    }

    // Toàn bộ tin nhắn giữa một khách hàng cụ thể và ĐỘI NGŨ admin (hộp thư chung).
    // Không lọc theo Auth::id() nữa để mọi admin đăng nhập đều thấy đúng một lịch sử hội thoại.
    public function getMessages($userId)
    {
        $adminIds = User::where('role', 'admin')->pluck('id');

        $messages = Message::where(function ($query) use ($adminIds, $userId) {
                $query->whereIn('sender_id', $adminIds)->where('receiver_id', $userId);
            })
            ->orWhere(function ($query) use ($adminIds, $userId) {
                $query->where('sender_id', $userId)->whereIn('receiver_id', $adminIds);
            })
            ->orderBy('created_at')
            ->get();

        // Một admin bất kỳ mở hội thoại này => coi như cả đội admin đã đọc (đúng tinh thần hộp thư chung).
        Message::where('sender_id', $userId)
            ->whereIn('receiver_id', $adminIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    // Admin gửi tin nhắn tới một khách hàng.
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => ['required', 'string', function ($attribute, $value, $fail) {
                if (trim($value) === '') {
                    $fail('Nội dung tin nhắn không được để trống.');
                }
            }],
        ], [
            'receiver_id.required' => 'Vui lòng chọn người nhận.',
            'receiver_id.exists' => 'Người nhận không tồn tại.',
            'content.required' => 'Vui lòng nhập nội dung tin nhắn.',
            'content.string' => 'Nội dung tin nhắn không hợp lệ.',
        ]);

        // Chặn admin lỡ gửi tin cho một admin khác qua API này (chỉ dùng để nhắn cho khách hàng).
        $receiver = User::find($request->receiver_id);
        if ($receiver && $receiver->role === 'admin') {
            return response()->json([
                'error' => 'Người nhận phải là khách hàng.',
            ], 422);
        }

        try {
            $message = Message::create([
                'sender_id' => Auth::id(),
                'receiver_id' => $request->receiver_id,
                'content' => $request->content,
                'is_read' => false,
            ]);

            return response()->json($message);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Gửi tin nhắn thất bại. Vui lòng thử lại.',
            ], 500);
        }
    }

    // Tổng số tin nhắn khách hàng gửi tới đội ngũ admin mà chưa AI trong đội đọc (chấm đỏ thông báo).
    public function unreadCount()
    {
        $adminIds = User::where('role', 'admin')->pluck('id');

        $count = Message::whereIn('receiver_id', $adminIds)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    // Tìm khách hàng để admin chủ động bắt đầu hội thoại mới (kể cả khách chưa từng chat).
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
        ], [
            'q.string' => 'Từ khoá tìm kiếm không hợp lệ.',
            'q.max' => 'Từ khoá tìm kiếm quá dài.',
        ]);

        $query = User::where('role', 'customer');

        $keyword = trim((string) $request->query('q'));

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        } else {
            $query->orderByDesc('created_at');
        }

        $users = $query->select('id', 'name', 'email')
            ->limit(20)
            ->get();

        return response()->json($users);
    }
}
