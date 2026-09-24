<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTicketController extends Controller
{
    // Danh sách tất cả ticket hỗ trợ (mọi khách hàng), có thể lọc theo trạng thái.
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['open', 'answered', 'closed'])],
        ], [
            'status.in' => 'Trạng thái lọc không hợp lệ.',
        ]);

        $query = Ticket::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $filters['status']);
        }

        $tickets = $query->paginate(15)->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    // Chi tiết ticket - admin xem được mọi ticket (đã qua middleware admin).
    public function show(Ticket $ticket)
    {
        $ticket->load(['replies.user', 'user']);

        return view('admin.tickets.show', compact('ticket'));
    }

    // Admin trả lời ticket, đồng thời đánh dấu đã trả lời.
    public function reply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ], [
            'message.required' => 'Vui lòng nhập nội dung trả lời.',
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
        ]);

        $ticket->update(['status' => 'answered']);

        return back()->with('success', 'Đã trả lời ticket.');
    }

    // Cập nhật trạng thái ticket thủ công (open/answered/closed).
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'answered', 'closed'])],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ]);

        $ticket->update(['status' => $validated['status']]);

        return back()->with('success', 'Đã cập nhật trạng thái ticket.');
    }
}
