<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ChatController extends Controller
{
    // Khách hàng gửi tin nhắn tới "shop" (hộp thư chung của mọi admin).
    public function send(Request $request)
    {
        $request->validate([
            'content' => ['required', 'string', function ($attribute, $value, $fail) {
                if (trim($value) === '') {
                    $fail('Nội dung tin nhắn không được để trống.');
                }
            }],
        ], [
            'content.required' => 'Vui lòng nhập nội dung tin nhắn.',
            'content.string' => 'Nội dung tin nhắn không hợp lệ.',
        ]);

        try {
            $userId = Auth::id();

            // receiver_id chỉ cần là MỘT admin có thật để thoả ràng buộc NOT NULL của schema.
            // Việc admin nào thực sự nhìn thấy/đọc tin nhắn không phụ thuộc vào id này,
            // vì getMessages() của cả khách hàng và admin đều lọc theo role='admin' (hộp thư chung),
            // không lọc theo một id admin cụ thể nữa.
            $adminIds = User::where('role', 'admin')->pluck('id');

            if ($adminIds->isEmpty()) {
                return response()->json([
                    'error' => 'Hiện chưa có admin nào để nhận tin nhắn. Vui lòng thử lại sau.',
                ], 500);
            }

            // Ưu tiên admin đã từng nhắn tin gần nhất với khách hàng này (giữ mạch hội thoại quen thuộc).
            $lastMessage = Message::where(function ($query) use ($userId, $adminIds) {
                    $query->where('sender_id', $userId)->whereIn('receiver_id', $adminIds);
                })
                ->orWhere(function ($query) use ($userId, $adminIds) {
                    $query->whereIn('sender_id', $adminIds)->where('receiver_id', $userId);
                })
                ->orderByDesc('created_at')
                ->first();

            if ($lastMessage) {
                $receiverId = $adminIds->contains($lastMessage->sender_id)
                    ? $lastMessage->sender_id
                    : $lastMessage->receiver_id;
            } else {
                // Khách hàng chưa từng chat với ai: fallback admin đầu tiên theo id.
                $receiverId = User::where('role', 'admin')->orderBy('id')->first()->id;
            }

            $message = Message::create([
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
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

    // Toàn bộ tin nhắn giữa khách hàng hiện tại và ĐỘI NGŨ admin (hộp thư chung),
    // sắp theo thời gian tăng dần. Trước đây chỉ lấy 1 admin cố định (admin đầu tiên) nên
    // khi có nhiều tài khoản admin, tin nhắn/trả lời của admin khác sẽ bị "mất tích".
    public function getMessages()
    {
        $adminIds = User::where('role', 'admin')->pluck('id');

        if ($adminIds->isEmpty()) {
            return response()->json([]);
        }

        $userId = Auth::id();

        $messages = Message::where(function ($query) use ($userId, $adminIds) {
                $query->where('sender_id', $userId)->whereIn('receiver_id', $adminIds);
            })
            ->orWhere(function ($query) use ($userId, $adminIds) {
                $query->whereIn('sender_id', $adminIds)->where('receiver_id', $userId);
            })
            ->orderBy('created_at')
            ->get();

        // Khách hàng vừa mở hộp thoại xem tin => đánh dấu đã đọc mọi tin admin gửi cho họ.
        Message::whereIn('sender_id', $adminIds)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    // Số tin nhắn (từ đội ngũ admin) mà khách hàng hiện tại chưa đọc, dùng cho chấm đỏ thông báo.
    public function unreadCount()
    {
        $count = Message::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
