@extends('layouts.app')

@section('title', 'Phân tích hành vi người dùng · Cây Cảnh Shop')

@section('content')
@php
    $inputClass = 'w-full min-w-0 bg-white border border-green-border rounded-pill px-4 py-2 text-sm outline-none focus:border-green-primary transition-all';
    $labelClass = 'block text-xs font-semibold text-text-secondary uppercase mb-1';
    $range = $filters['range'];
    $nf = fn ($value) => number_format((float) $value, 0, ',', '.');
@endphp

<div class="mb-8">
    <h2 class="text-4xl sm:text-5xl font-medium gloock text-text-primary tracking-tight">Phân tích hành vi người dùng</h2>
</div>

<!-- Bộ lọc -->
<div class="bg-white rounded-[32px] p-6 border border-green-border shadow-sm mb-8">
    <form action="{{ route('admin.analytics.index') }}" method="GET" id="analyticsFilterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
        <div>
            <label for="an-range" class="{{ $labelClass }}">Kỳ</label>
            <select id="an-range" name="range" class="{{ $inputClass }}">
                @foreach ($ranges as $key => $name)
                    <option value="{{ $key }}" @selected($range === (string) $key)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div data-custom-range>
            <label for="an-from" class="{{ $labelClass }}">Từ ngày</label>
            <input id="an-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="{{ $inputClass }}">
        </div>
        <div data-custom-range>
            <label for="an-to" class="{{ $labelClass }}">Đến ngày</label>
            <input id="an-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label for="an-q" class="{{ $labelClass }}">Tên sản phẩm</label>
            <input id="an-q" type="text" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Tìm theo tên..." class="{{ $inputClass }}">
        </div>
        <div>
            <label for="an-type" class="{{ $labelClass }}">Loại</label>
            <select id="an-type" name="type" class="{{ $inputClass }}">
                <option value="">Tất cả</option>
                <option value="plant" @selected(($filters['type'] ?? '') === 'plant')>Cây cảnh</option>
                <option value="flower" @selected(($filters['type'] ?? '') === 'flower')>Hoa</option>
            </select>
        </div>
        <div>
            <label for="an-category" class="{{ $labelClass }}">Danh mục</label>
            <select id="an-category" name="category" class="{{ $inputClass }}">
                <option value="">Tất cả</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) ($filters['category'] ?? 0) === $category->id)>{{ $category->parent_id ? '— ' : '' }}{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="an-activity" class="{{ $labelClass }}">Hoạt động</label>
            <select id="an-activity" name="activity" class="{{ $inputClass }}">
                @foreach ($activities as $key => $name)
                    <option value="{{ $key }}" @selected(($filters['activity'] ?? '') === $key)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="an-sort" class="{{ $labelClass }}">Sắp xếp</label>
            <select id="an-sort" name="sort" class="{{ $inputClass }}">
                @foreach ($sorts as $key => $name)
                    <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap justify-end gap-3">
            <a href="{{ route('admin.analytics.index') }}" class="px-6 py-2 bg-white text-text-primary border border-green-border rounded-full hover:bg-green-background transition-all text-sm font-medium text-decoration-none">Mặc định</a>
            <button type="submit" class="px-6 py-2 bg-green-primary text-white rounded-full hover:bg-green-accent transition-all text-sm font-medium">Lọc dữ liệu</button>
        </div>
    </form>

    @if ($filterErrors->isNotEmpty())
        <div class="mt-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm" role="alert">
            <p class="font-medium mb-1">Bộ lọc không hợp lệ, đang hiển thị mặc định (30 ngày):</p>
            <ul class="list-disc pl-5">
                @foreach ($filterErrors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<!-- Tổng quan kỳ -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-[32px] p-6 border border-green-border shadow-sm">
        <div class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider mb-2">Lượt xem</div>
        <div class="text-3xl font-bold text-text-primary" data-testid="analytics-total-views">{{ $nf($summary->view_count ?? 0) }}</div>
    </div>
    <div class="bg-white rounded-[32px] p-6 border border-green-border shadow-sm">
        <div class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider mb-2">Người / phiên xem</div>
        <div class="text-3xl font-bold text-text-primary">{{ $nf($summary->viewer_count ?? 0) }}</div>
        <div class="text-xs text-text-secondary mt-1">trong đó {{ $nf($summary->guest_viewer_count ?? 0) }} phiên khách chưa đăng nhập</div>
    </div>
    <div class="bg-white rounded-[32px] p-6 border border-green-border shadow-sm">
        <div class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider mb-2">Kỳ đang xem</div>
        <div class="text-lg font-semibold text-text-primary" data-testid="analytics-period-label">{{ $period->label }}</div>
    </div>
</div>

<div class="mb-8 px-5 py-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl text-sm space-y-1">
    <p><strong>Cách đọc số liệu.</strong> Lượt xem và doanh số cùng lọc theo kỳ trên (doanh số theo ngày đặt đơn). Doanh số chỉ tính đơn đã thu tiền (online thành công hoặc COD đã thu), là giá bán × số lượng — có thể khác tổng tiền đơn vì phí giao hàng và voucher. Lượt xem và người mua không cùng tập đối tượng nên trang này không tính tỷ lệ chuyển đổi.</p>
    @if ($trackingSince)
        <p>Từ {{ $trackingSince->format('d/m/Y H:i') }}: ghi nhận cả khách chưa đăng nhập (theo phiên ẩn danh) và mỗi người/phiên chỉ tính một lượt cho cùng sản phẩm trong {{ \App\Services\ProductViewTracker::DEDUP_MINUTES }} phút.</p>
    @else
        <p>Chưa có lượt xem nào ghi theo cách đo mới (khách vãng lai + chống đếm lặp).</p>
    @endif
    @if (($summary->legacy_view_count ?? 0) > 0)
        <p data-testid="analytics-legacy-note">Kỳ này có {{ $nf($summary->legacy_view_count) }} lượt xem dữ liệu cũ: chỉ của tài khoản đã đăng nhập và mỗi lần tải lại trang tính một lượt.</p>
    @endif
</div>

<!-- Bảng hiệu quả sản phẩm -->
<div class="bg-white rounded-[32px] p-6 sm:p-8 border border-green-border shadow-sm mb-8">
    <div class="flex flex-wrap items-baseline justify-between gap-2 mb-6">
        <h3 class="text-2xl font-medium gloock text-text-primary">Hiệu quả sản phẩm</h3>
        <span class="text-sm text-text-secondary">{{ $nf($rows->total()) }} sản phẩm</span>
    </div>

    <div class="overflow-x-auto -mx-2 px-2">
        <table class="w-full min-w-[760px] text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Sản phẩm</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Lượt xem</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right" title="Tài khoản đăng nhập hoặc phiên khách khác nhau">Người/phiên xem</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Đơn đã thu</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">SL bán</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Doanh thu</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right" title="Tồn kho hiện tại, không phải tồn cuối kỳ">Tồn hiện tại</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-border/20">
                @forelse ($rows as $row)
                <tr data-product-row="{{ $row->id }}">
                    <td class="py-4 pr-4">
                        @if ($row->deleted_at)
                            <span class="font-medium text-text-secondary">{{ $row->name }}</span>
                        @else
                            <a href="{{ route('products.edit', $row->id) }}" class="font-medium text-text-primary hover:text-green-primary">{{ $row->name }}</a>
                        @endif
                        <div class="flex flex-wrap items-center gap-1 mt-1 text-xs text-text-secondary">
                            <span>{{ $row->product_type === 'flower' ? 'Hoa' : 'Cây cảnh' }}</span>
                            @if (! empty($categoryNames[$row->id]))
                                <span>· {{ implode(', ', $categoryNames[$row->id]) }}</span>
                            @endif
                            @if ($row->deleted_at)
                                <span class="px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200">Đã xoá</span>
                            @elseif (! $row->is_active)
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">Đang ẩn</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-4 pr-4 text-text-primary mono text-right">{{ $nf($row->view_count) }}</td>
                    <td class="py-4 pr-4 text-text-primary mono text-right">
                        {{ $nf($row->viewer_count) }}
                        @if ($row->guest_viewer_count > 0)
                            <div class="text-xs text-text-secondary">{{ $nf($row->guest_viewer_count) }} khách</div>
                        @endif
                    </td>
                    <td class="py-4 pr-4 text-text-primary mono text-right">{{ $nf($row->order_count) }}</td>
                    <td class="py-4 pr-4 text-text-primary mono text-right">{{ $nf($row->sold_qty) }}</td>
                    <td class="py-4 pr-4 text-text-primary font-medium text-right whitespace-nowrap">{{ $nf($row->revenue) }} đ</td>
                    <td class="py-4 mono text-right {{ $row->stock <= 5 ? 'text-red-700 font-semibold' : 'text-text-primary' }}">{{ $nf($row->stock) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-text-secondary italic">Không có sản phẩm phù hợp bộ lọc trong kỳ này.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rows->hasPages())
        <div class="mt-6">{{ $rows->links() }}</div>
    @endif
</div>

<!-- Khách hoạt động nhiều nhất -->
<div class="bg-white rounded-[32px] p-6 sm:p-8 border border-green-border shadow-sm">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-1">Khách hàng xem nhiều nhất</h3>
    <p class="text-sm text-text-secondary mb-6">Chỉ tài khoản đã đăng nhập, cùng kỳ {{ $period->label }}.</p>

    <div class="overflow-x-auto -mx-2 px-2">
        <table class="w-full min-w-[480px] text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Khách hàng</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Email</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Sản phẩm</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Lượt xem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-border/20">
                @forelse ($mostActiveUsers as $item)
                <tr>
                    <td class="py-4 pr-4 text-text-primary font-medium">{{ $item->user_name ?? 'Không xác định' }}</td>
                    <td class="py-4 pr-4 text-text-primary text-sm break-all">{{ $item->user_email ?? '—' }}</td>
                    <td class="py-4 pr-4 text-text-primary mono text-right">{{ $nf($item->product_count) }}</td>
                    <td class="py-4 text-text-primary mono text-right">{{ $nf($item->views_count) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-8 text-center text-text-secondary italic">Không có lượt xem của tài khoản đăng nhập trong kỳ này.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    (function () {
        var form = document.getElementById('analyticsFilterForm');
        if (!form) return;
        var range = form.querySelector('[name="range"]');
        function sync() {
            var custom = range.value === 'custom';
            form.querySelectorAll('[data-custom-range]').forEach(function (field) {
                field.classList.toggle('hidden', !custom);
                field.querySelectorAll('input').forEach(function (input) { input.disabled = !custom; });
            });
        }
        range.addEventListener('change', sync);
        sync();
    })();
</script>
@endsection
