@extends('layouts.app')

@section('title', 'Phân tích hành vi người dùng · Cây Cảnh Shop')

@section('content')
<div class="flex justify-between items-center mb-8">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Phân tích hành vi người dùng</h2>
</div>

<!-- Thông báo dự phòng nếu Chart.js không tải được (CDN bị chặn...) -->
<div id="chartFallbackNotice" class="hidden mb-6 px-5 py-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl text-sm">
    Không thể tải thư viện biểu đồ (Chart.js) từ CDN. Vui lòng kiểm tra kết nối mạng để xem biểu đồ lượt xem theo ngày.
</div>

<!-- Biểu đồ lượt xem theo ngày (30 ngày) -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-8">
    <h3 class="text-xl font-medium gloock text-text-primary mb-6">Lượt xem sản phẩm theo ngày (30 ngày gần nhất)</h3>
    @if (empty($dailyViews['labels'] ?? []))
        <p class="py-8 text-center text-text-secondary italic">Chưa có dữ liệu lượt xem trong 30 ngày qua.</p>
    @else
        <div id="dailyViewsChartData" class="hidden" data-labels="{{ json_encode($dailyViews['labels']) }}" data-values="{{ json_encode($dailyViews['data']) }}"></div>
        <div class="relative h-72"><canvas id="dailyViewsChart"></canvas></div>
    @endif
</div>

<!-- Top 10 sản phẩm xem nhiều nhất -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-8">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Top 10 sản phẩm xem nhiều nhất</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tên sản phẩm</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Lượt xem (30 ngày)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-border/20">
                @forelse ($topViewedProducts as $item)
                <tr>
                    <td class="py-4 pr-4 text-text-primary font-medium">{{ $item->product->name ?? 'Sản phẩm đã bị xoá' }}</td>
                    <td class="py-4 text-text-primary mono text-right">{{ number_format($item->views_count, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" class="py-8 text-center text-text-secondary italic">Chưa có dữ liệu lượt xem trong 30 ngày qua.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Top 10 tỷ lệ chuyển đổi xem → mua -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm mb-8">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Top 10 tỷ lệ chuyển đổi xem → mua</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Tên sản phẩm</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Lượt xem</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Lượt mua</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Tỷ lệ chuyển đổi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-border/20">
                @forelse ($topConversionProducts as $item)
                <tr>
                    <td class="py-4 pr-4 text-text-primary font-medium">{{ $item->product->name ?? 'Sản phẩm đã bị xoá' }}</td>
                    <td class="py-4 pr-4 text-text-primary mono">{{ number_format($item->view_count, 0, ',', '.') }}</td>
                    <td class="py-4 pr-4 text-text-primary mono">{{ number_format($item->purchase_count, 0, ',', '.') }}</td>
                    <td class="py-4 text-text-primary font-medium text-right">{{ number_format($item->conversion_rate * 100, 1, ',', '.') }}%</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-8 text-center text-text-secondary italic">Chưa có đủ dữ liệu để tính tỷ lệ chuyển đổi (cần tối thiểu 3 lượt xem/sản phẩm).</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Top 10 user hoạt động nhiều nhất -->
<div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
    <h3 class="text-2xl font-medium gloock text-text-primary mb-6">Top 10 khách hàng hoạt động nhiều nhất</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-green-border/50">
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Khách hàng</th>
                    <th class="py-3 pr-4 font-semibold text-text-secondary mono text-xs uppercase tracking-wider">Email</th>
                    <th class="py-3 font-semibold text-text-secondary mono text-xs uppercase tracking-wider text-right">Lượt xem (30 ngày)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-green-border/20">
                @forelse ($mostActiveUsers as $item)
                <tr>
                    <td class="py-4 pr-4 text-text-primary font-medium">{{ $item->user_name ?? 'Không xác định' }}</td>
                    <td class="py-4 pr-4 text-text-primary text-sm">{{ $item->user_email ?? '—' }}</td>
                    <td class="py-4 text-text-primary mono text-right">{{ number_format($item->views_count, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-8 text-center text-text-secondary italic">Chưa có dữ liệu hoạt động của khách hàng trong 30 ngày qua.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js" onerror="window.__chartJsFailed = true;"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') {
            document.getElementById('chartFallbackNotice').classList.remove('hidden');
            return;
        }

        // Đọc dữ liệu qua thuộc tính data-* thay vì nhúng biến PHP trực tiếp trong <script>.
        function readChartData(containerId) {
            var el = document.getElementById(containerId);
            if (!el) return { labels: [], values: [] };
            return {
                labels: JSON.parse(el.dataset.labels || '[]'),
                values: JSON.parse(el.dataset.values || '[]'),
            };
        }

        var oxblood = '#5C2323';
        var oxbloodSoft = 'rgba(92, 35, 35, 0.55)';

        var dailyViewsData = readChartData('dailyViewsChartData');
        var dailyViewsCanvas = document.getElementById('dailyViewsChart');
        if (dailyViewsCanvas) {
            new Chart(dailyViewsCanvas, {
                type: 'line',
                data: {
                    labels: dailyViewsData.labels,
                    datasets: [{ label: 'Lượt xem', data: dailyViewsData.values, borderColor: oxblood, backgroundColor: oxbloodSoft, tension: 0.3, fill: true }],
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
            });
        }
    });

    window.addEventListener('load', function () {
        if (typeof Chart === 'undefined' || window.__chartJsFailed) {
            var notice = document.getElementById('chartFallbackNotice');
            if (notice) notice.classList.remove('hidden');
        }
    });
</script>
@endsection
