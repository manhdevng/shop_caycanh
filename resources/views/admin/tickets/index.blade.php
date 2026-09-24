@extends('layouts.app')

@section('title', 'Ticket hỗ trợ · Cây Cảnh Shop')

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Ticket hỗ trợ</h2>
    </div>

    @if (session('success'))
        <div class="mb-6 px-5 py-4 bg-green-background border border-green-border/50 text-text-primary rounded-2xl text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 px-5 py-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Khách hàng</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Chủ đề</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Trạng thái</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Ngày tạo</th>
                    <th class="py-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    @php
                        $statusClasses = [
                            'open' => 'bg-red-50 border-red-200 text-red-700',
                            'answered' => 'bg-green-background border-green-border/50 text-text-primary',
                            'closed' => 'bg-gray-100 border-gray-200 text-gray-500',
                        ];
                        $statusLabels = [
                            'open' => 'Đang mở',
                            'answered' => 'Đã trả lời',
                            'closed' => 'Đã đóng',
                        ];
                        $statusClass = $statusClasses[$ticket->status] ?? $statusClasses['open'];
                        $statusLabel = $statusLabels[$ticket->status] ?? $ticket->status;
                    @endphp
                    <tr class="border-b border-green-border/20 hover:bg-green-background/20 transition-colors duration-200">
                        <td class="py-5 pr-4">
                            <div class="font-medium text-text-primary">{{ $ticket->user->name ?? 'Người dùng đã xoá' }}</div>
                            <div class="text-text-secondary text-xs mt-0.5">{{ $ticket->user->email ?? '—' }}</div>
                        </td>
                        <td class="py-5 pr-4 text-text-primary">{{ $ticket->subject }}</td>
                        <td class="py-5 pr-4">
                            <span class="px-3 py-1 border rounded-pill text-xs mono font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="py-5 pr-4 text-text-secondary mono text-sm">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-5 text-right">
                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors inline-flex" title="Xem chi tiết">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-text-secondary">
                            Chưa có ticket hỗ trợ nào.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($tickets->hasPages())
        <div class="mt-6">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
