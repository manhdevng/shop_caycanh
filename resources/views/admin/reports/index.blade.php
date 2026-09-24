@extends('layouts.app')

@section('title', 'Báo cáo doanh thu · Cây Cảnh Shop')

@section('content')
<div class="flex justify-between items-center mb-8">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Báo cáo Doanh thu</h2>
</div>

<!-- Tab Bảng số liệu / Biểu đồ -->
<div class="flex items-center gap-2 mb-8">
    <a href="{{ route('admin.reports.index') }}" class="px-5 py-2 rounded-pill text-sm font-medium text-decoration-none border transition-colors bg-green-primary text-white border-green-border">
        Bảng số liệu
    </a>
    <a href="{{ route('admin.reports.charts') }}" class="px-5 py-2 rounded-pill text-sm font-medium text-decoration-none border transition-colors bg-white text-text-secondary border-green-border hover:bg-green-background">
        Biểu đồ
    </a>
</div>

<!-- Thẻ số liệu tổng quan -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-green-background flex items-center justify-center text-green-primary">
                <i data-lucide="package-open" class="w-5 h-5"></i>
            </div>
            <span class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider">Tổng đơn hàng</span>
        </div>
        <div class="text-4xl font-bold text-text-primary">{{ number_format($totalOrders, 0, ',', '.') }}</div>
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-green-background flex items-center justify-center text-green-primary">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
            <span class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider">Tổng khách hàng</span>
        </div>
        <div class="text-4xl font-bold text-text-primary">{{ number_format($totalCustomers, 0, ',', '.') }}</div>
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-green-background flex items-center justify-center text-green-primary">
                <i data-lucide="banknote" class="w-5 h-5"></i>
            </div>
            <span class="text-sm font-semibold text-text-secondary mono uppercase tracking-wider">Tổng doanh thu</span>
        </div>
        <div class="text-4xl font-bold text-text-primary">{{ number_format($totalRevenue, 0, ',', '.') }} đ</div>
    </div>
</div>

<!-- Doanh thu theo danh mục -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-8">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-2">Doanh thu theo danh mục</h3>
    <p class="text-sm text-text-secondary mb-6">Mỗi sản phẩm chỉ tính vào một nhóm danh mục gốc (Cây / Hoa); sản phẩm chưa thuộc nhóm nào nằm ở "Chưa phân loại".</p>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Danh mục</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Số lượng bán</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Doanh thu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-border/20">
                @forelse ($categoryRevenue as $row)
                <tr>
                    <td class="py-4 pr-4 text-text-primary font-medium">{{ $row->category_id === null ? 'Chưa phân loại' : ($row->category_name ?? 'Danh mục #'.$row->category_id) }}</td>
                    <td class="py-4 pr-4 text-text-primary mono">{{ number_format($row->total_qty, 0, ',', '.') }}</td>
                    <td class="py-4 text-text-primary font-medium text-right">{{ number_format($row->total_revenue, 0, ',', '.') }} đ</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-8 text-center text-text-secondary italic">Chưa có dữ liệu doanh thu theo danh mục.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Top 10 sản phẩm bán chạy -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-8">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Top 10 sản phẩm bán chạy</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tên sản phẩm</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Số lượng đã bán</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Doanh thu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-border/20">
                @forelse ($topProducts as $item)
                <tr>
                    <td class="py-4 pr-4 text-text-primary font-medium">{{ $item->product_name ?? 'Không xác định' }}</td>
                    <td class="py-4 pr-4 text-text-primary mono">{{ number_format($item->total_qty, 0, ',', '.') }}</td>
                    <td class="py-4 text-text-primary font-medium text-right">{{ number_format($item->total_revenue, 0, ',', '.') }} đ</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-8 text-center text-text-secondary italic">Chưa có dữ liệu bán hàng.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Doanh thu theo ngày -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Theo ngày</h3>
        <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-green-border/50">
                        <th class="py-2 pr-2 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Ngày</th>
                        <th class="py-2 pr-2 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Đơn</th>
                        <th class="py-2 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Doanh thu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-border/20">
                    @forelse ($revenueByDate as $row)
                    <tr>
                        <td class="py-3 pr-2 text-text-primary text-sm">{{ \Illuminate\Support\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                        <td class="py-3 pr-2 text-text-primary mono text-sm">{{ $row->order_count }}</td>
                        <td class="py-3 text-text-primary font-medium text-right text-sm">{{ number_format($row->total_revenue, 0, ',', '.') }} đ</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="py-8 text-center text-text-secondary italic">Chưa có dữ liệu.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Doanh thu theo tháng -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Theo tháng</h3>
        <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-green-border/50">
                        <th class="py-2 pr-2 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tháng</th>
                        <th class="py-2 pr-2 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Đơn</th>
                        <th class="py-2 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Doanh thu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-border/20">
                    @forelse ($revenueByMonth as $row)
                    <tr>
                        <td class="py-3 pr-2 text-text-primary text-sm">{{ $row->period }}</td>
                        <td class="py-3 pr-2 text-text-primary mono text-sm">{{ $row->order_count }}</td>
                        <td class="py-3 text-text-primary font-medium text-right text-sm">{{ number_format($row->total_revenue, 0, ',', '.') }} đ</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="py-8 text-center text-text-secondary italic">Chưa có dữ liệu.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Doanh thu theo năm -->
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Theo năm</h3>
        <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-green-border/50">
                        <th class="py-2 pr-2 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Năm</th>
                        <th class="py-2 pr-2 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Đơn</th>
                        <th class="py-2 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Doanh thu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-green-border/20">
                    @forelse ($revenueByYear as $row)
                    <tr>
                        <td class="py-3 pr-2 text-text-primary text-sm">{{ $row->period }}</td>
                        <td class="py-3 pr-2 text-text-primary mono text-sm">{{ $row->order_count }}</td>
                        <td class="py-3 text-text-primary font-medium text-right text-sm">{{ number_format($row->total_revenue, 0, ',', '.') }} đ</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="py-8 text-center text-text-secondary italic">Chưa có dữ liệu.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
