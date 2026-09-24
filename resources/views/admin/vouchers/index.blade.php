@extends('layouts.app')

@section('title', 'Mã giảm giá · Cây Cảnh Shop')

@section('content')
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Mã Giảm Giá</h2>
        <a class="px-6 py-3 bg-green-primary text-white rounded-pill font-medium hover:bg-green-accent transition-colors flex items-center gap-2 text-decoration-none border border-green-border" href="{{ route('admin.vouchers.create') }}">
            <i data-lucide="tag" class="w-5 h-5"></i>
            Tạo mã mới
        </a>
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
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Mã</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Loại &amp; Giá trị</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Phạm vi</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Điều kiện</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Hạn dùng</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Đã dùng</th>
                    <th class="py-4 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Trạng thái</th>
                    <th class="py-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vouchers as $voucher)
                <tr class="border-b border-green-border/20 hover:bg-green-background/20 transition-colors duration-200">
                    <td class="py-5 pr-4">
                        <div class="font-medium text-text-primary mono">{{ $voucher->code }}</div>
                    </td>
                    <td class="py-5 pr-4 text-text-primary text-sm">
                        @if ($voucher->discount_type === 'percent')
                            {{ number_format($voucher->discount_value, ($voucher->discount_value == intval($voucher->discount_value) ? 0 : 2), ',', '.') }}%
                            @if ($voucher->max_discount_amount)
                                <span class="text-text-secondary text-xs">(tối đa {{ number_format($voucher->max_discount_amount, 0, ',', '.') }}đ)</span>
                            @endif
                        @else
                            {{ number_format($voucher->discount_value, 0, ',', '.') }}đ
                        @endif
                    </td>
                    <td class="py-5 pr-4 text-text-secondary text-sm">
                        @if ($voucher->scope_type === 'products')
                            {{ $voucher->products->count() }} sản phẩm
                        @elseif ($voucher->scope_type === 'categories')
                            {{ $voucher->categories->count() }} danh mục
                        @else
                            Toàn shop
                        @endif
                    </td>
                    <td class="py-5 pr-4 text-text-secondary text-sm">
                        @if ($voucher->min_order_amount)
                            Đơn tối thiểu {{ number_format($voucher->min_order_amount, 0, ',', '.') }}đ
                        @else
                            Không yêu cầu
                        @endif
                    </td>
                    <td class="py-5 pr-4 text-text-secondary mono text-xs">
                        @if ($voucher->starts_at || $voucher->expires_at)
                            {{ $voucher->starts_at?->format('d/m/Y H:i') ?? '—' }} → {{ $voucher->expires_at?->format('d/m/Y H:i') ?? '—' }}
                        @else
                            Không giới hạn
                        @endif
                    </td>
                    <td class="py-5 pr-4 text-text-secondary mono text-sm">
                        {{ $voucher->used_count }}/{{ $voucher->usage_limit ?? '∞' }}
                    </td>
                    <td class="py-5 pr-4">
                        @if ($voucher->is_active)
                            <span class="px-3 py-1 bg-green-background border border-green-border/50 text-text-primary rounded-pill text-xs mono font-semibold">Đang hoạt động</span>
                        @else
                            <span class="px-3 py-1 bg-red-50 border border-red-200 text-red-700 rounded-pill text-xs mono font-semibold">Ngừng</span>
                        @endif
                    </td>
                    <td class="py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-green-background rounded-full transition-colors" title="Sửa">
                                <i data-lucide="edit-2" class="w-5 h-5"></i>
                            </a>
                            <form action="{{ route('admin.vouchers.destroy', $voucher) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-text-secondary hover:text-red-600 hover:bg-red-50 rounded-full transition-colors" title="Xóa">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-text-secondary">
                        Chưa có mã giảm giá nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($vouchers->hasPages())
        <div class="mt-6">{{ $vouchers->links() }}</div>
    @endif
</div>
@endsection
