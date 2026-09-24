<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdminChatController extends Controller
{
    // Danh sách khách hàng đã từng nhắn tin qua lại với BẤT KỲ admin nào (hộp thư chung).
    // Trước đây lọc theo Auth::id() của admin đang đăng nhập nên khi có >1 tài khoản admin,
    // admin nào không phải người "sở hữu" hội thoại sẽ không thấy được khách đã nhắn tin.
    public function getUsers()
    {
        $adminIds = User::where('role', 'admin')->pluck('id')->all();

        if (empty($adminIds)) {
            return response()->json([]);
        }

        // Gom nhóm hoàn toàn bằng SQL thay vì tải toàn bộ tin nhắn vào PHP (endpoint được gọi lại
        // nhiều lần khi admin mở/làm mới khung chat, bảng messages càng lớn càng chậm và tốn RAM).
        // Placeholder "?" cho danh sách admin => id được bind an toàn, không nối chuỗi trực tiếp.
        $placeholders = implode(',', array_fill(0, count($adminIds), '?'));

        // Với mỗi dòng: nếu người gửi là admin thì khách là người nhận, ngược lại khách là người gửi.
        // is_unread = 1 khi khách nhắn cho admin (người gửi không phải admin) mà đội admin chưa ai đọc.
        $conversationRows = Message::query()
            ->toBase()
            ->selectRaw("CASE WHEN sender_id IN ({$placeholders}) THEN receiver_id ELSE sender_id END AS customer_id", $adminIds)
            ->addSelect('created_at')
            ->selectRaw("CASE WHEN sender_id IN ({$placeholders}) THEN 0 WHEN is_read = 0 THEN 1 ELSE 0 END AS is_unread", $adminIds)
            // Chỉ lấy tin nhắn mà ĐÚNG MỘT bên là admin (bỏ qua admin nhắn với admin).
            ->where(function ($query) use ($adminIds) {
                $query->where(function ($q) use ($adminIds) {
                    $q->whereIn('sender_id', $adminIds)->whereNotIn('receiver_id', $adminIds);
                })->orWhere(function ($q) use ($adminIds) {
                    $q->whereNotIn('sender_id', $adminIds)->whereIn('receiver_id', $adminIds);
                });
            });

        $rows = DB::query()
            ->fromSub($conversationRows, 'conversation_rows')
            ->join('users', 'users.id', '=', 'conversation_rows.customer_id')
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('MAX(conversation_rows.created_at) AS last_message_at')
            ->selectRaw('SUM(conversation_rows.is_unread) AS unread_count')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('last_message_at')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        // MAX() trả về chuỗi thô => parse lại để giữ đúng định dạng ISO8601 như trước.
        $users = $rows->map(function ($row) {
            return [
                'id' => (int) $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'last_message_at' => $row->last_message_at ? Carbon::parse($row->last_message_at)->toIso8601String() : null,
                'unread_count' => (int) $row->unread_count,
            ];
        })->values();

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
            'content' => ['required', 'string', 'max:2000', function ($attribute, $value, $fail) {
                if (trim($value) === '') {
                    $fail('Nội dung tin nhắn không được để trống.');
                }
            }],
        ], [
            'receiver_id.required' => 'Vui lòng chọn người nhận.',
            'receiver_id.exists' => 'Người nhận không tồn tại.',
            'content.required' => 'Vui lòng nhập nội dung tin nhắn.',
            'content.string' => 'Nội dung tin nhắn không hợp lệ.',
            'content.max' => 'Nội dung tin nhắn không được vượt quá 2000 ký tự.',
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

        // Chỉ đếm tin do khách gửi (loại tin admin nhắn cho admin), khớp với getUsers().
        $count = Message::whereIn('receiver_id', $adminIds)
            ->whereNotIn('sender_id', $adminIds)
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
