<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    // Danh sách yêu cầu hỗ trợ của khách hàng hiện tại (chỉ của chính họ).
    public function index()
    {
        $tickets = Ticket::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    // Form tạo yêu cầu hỗ trợ mới.
    public function create()
    {
        return view('tickets.create');
    }

    // Tạo ticket mới kèm tin nhắn đầu tiên (lưu dưới dạng ticket_replies).
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ], [
            'subject.required' => 'Vui lòng nhập tiêu đề yêu cầu hỗ trợ.',
            'subject.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'message.required' => 'Vui lòng nhập nội dung câu hỏi.',
        ]);

        $ticket = DB::transaction(function () use ($validated) {
            $ticket = Ticket::create([
                'user_id' => auth()->id(),
                'subject' => $validated['subject'],
                'status' => 'open',
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $validated['message'],
            ]);

            return $ticket;
        });

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Đã gửi yêu cầu hỗ trợ.');
    }

    // Chi tiết ticket - chỉ chủ sở hữu mới được xem, không tin id trên URL.
    public function show(Ticket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);

        $ticket->load(['replies.user']);

        return view('tickets.show', compact('ticket'));
    }

    // Khách hàng trả lời thêm vào ticket của chính mình.
    public function reply(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'message' => 'required|string',
        ], [
            'message.required' => 'Vui lòng nhập nội dung trả lời.',
        ]);

        if ($ticket->status === 'closed') {
            return back()->with('error', 'Ticket đã đóng, không thể trả lời thêm.');
        }

        DB::transaction(function () use ($ticket, $validated) {
            TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $validated['message'],
            ]);

            // Khách trả lời lại thì mở lại chờ admin xử lý tiếp (nếu đang
            // "answered"), giữ nguyên nếu đang "open".
            if ($ticket->status === 'answered') {
                $ticket->update(['status' => 'open']);
            }
        });

        return back()->with('success', 'Đã gửi trả lời.');
    }
}
