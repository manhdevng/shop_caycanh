@extends('layouts.app')

@section('title', 'Biểu đồ doanh thu · Cây Cảnh Shop')

@section('content')
<div class="flex justify-between items-center mb-8">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Báo cáo Doanh thu</h2>
</div>

@include('admin.reports._tabs', ['active' => 'charts'])

@include('admin.reports._filter', ['action' => route('admin.reports.charts')])

<!-- Thông báo dự phòng nếu Chart.js không tải được (CDN bị chặn...) -->
<div id="chartFallbackNotice" class="hidden mb-6 px-5 py-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl text-sm">
    Không thể tải thư viện biểu đồ (Chart.js) từ CDN. Vui lòng kiểm tra kết nối mạng hoặc xem báo cáo dạng
    <a href="{{ route('admin.reports.index', $period->query()) }}" class="underline font-medium">bảng số liệu</a> thay thế.
</div>

<p class="text-sm text-text-secondary mb-4">Tất cả biểu đồ dưới đây cùng kỳ <span class="font-semibold text-text-primary">{{ $period->label }}</span> — tổng doanh thu đã thu: <span class="font-semibold text-text-primary" data-testid="report-total-revenue">{{ number_format($totalRevenue, 0, ',', '.') }} đ</span>.</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu theo danh mục</h3>
        <div id="catChartData" class="hidden" data-labels="{{ json_encode($catLabels) }}" data-values="{{ json_encode($catRevenue) }}"></div>
        @if (array_sum($catRevenue) > 0)
        <div class="relative h-72"><canvas id="catChart"></canvas></div>
        @else
        <div class="h-72 flex items-center justify-center text-center text-text-secondary italic rounded-2xl bg-green-background/40" data-chart-empty>Không có doanh thu trong kỳ này.</div>
        @endif
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu theo phương thức thanh toán</h3>
        <div id="methodChartData" class="hidden" data-labels="{{ json_encode($paymentMethodLabels) }}" data-values="{{ json_encode($paymentMethodRevenue) }}"></div>
        @if (array_sum($paymentMethodRevenue) > 0)
        <div class="relative h-72"><canvas id="methodChart"></canvas></div>
        @else
        <div class="h-72 flex items-center justify-center text-center text-text-secondary italic rounded-2xl bg-green-background/40" data-chart-empty>Không có doanh thu trong kỳ này.</div>
        @endif
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm lg:col-span-2">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu theo ngày</h3>
        <div id="dateChartData" class="hidden" data-labels="{{ json_encode($revDateLabels) }}" data-values="{{ json_encode($revDateData) }}"></div>
        @if ($dailyTooLong)
        <div class="h-72 flex items-center justify-center text-center text-text-secondary italic rounded-2xl bg-green-background/40" data-chart-empty>Kỳ dài hơn 366 ngày — xem biểu đồ theo tháng/năm bên dưới hoặc thu hẹp khoảng ngày.</div>
        @else
        @if ($totalRevenue > 0)
        <div class="relative h-72"><canvas id="dateChart"></canvas></div>
        @else
        <div class="h-72 flex items-center justify-center text-center text-text-secondary italic rounded-2xl bg-green-background/40" data-chart-empty>Không có doanh thu trong kỳ này.</div>
        @endif
        @endif
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu theo tháng</h3>
        <div id="monthChartData" class="hidden" data-labels="{{ json_encode($revMonthLabels) }}" data-values="{{ json_encode($revMonthData) }}"></div>
        @if ($totalRevenue > 0)
        <div class="relative h-72"><canvas id="monthChart"></canvas></div>
        @else
        <div class="h-72 flex items-center justify-center text-center text-text-secondary italic rounded-2xl bg-green-background/40" data-chart-empty>Không có doanh thu trong kỳ này.</div>
        @endif
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu theo năm</h3>
        <div id="yearChartData" class="hidden" data-labels="{{ json_encode($revYearLabels) }}" data-values="{{ json_encode($revYearData) }}"></div>
        @if ($totalRevenue > 0)
        <div class="relative h-72"><canvas id="yearChart"></canvas></div>
        @else
        <div class="h-72 flex items-center justify-center text-center text-text-secondary italic rounded-2xl bg-green-background/40" data-chart-empty>Không có doanh thu trong kỳ này.</div>
        @endif
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
        var palette = ['#5C2323', '#7A3030', '#A1B887', '#B6CC9D', '#CED1C3', '#9CA3AF'];

        // Kỳ không có dữ liệu thì Blade không render canvas — bỏ qua biểu đồ đó.
        function draw(canvasId, config) {
            var canvas = document.getElementById(canvasId);
            if (canvas) new Chart(canvas, config);
        }

        var catData = readChartData('catChartData');
        draw('catChart', {
            type: 'bar',
            data: {
                labels: catData.labels,
                datasets: [{ label: 'Doanh thu (đ)', data: catData.values, backgroundColor: oxbloodSoft, borderColor: oxblood, borderWidth: 1 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        var dateData = readChartData('dateChartData');
        draw('dateChart', {
            type: 'line',
            data: {
                labels: dateData.labels,
                datasets: [{ label: 'Doanh thu (đ)', data: dateData.values, borderColor: oxblood, backgroundColor: oxbloodSoft, tension: 0.3, fill: true }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        var monthData = readChartData('monthChartData');
        draw('monthChart', {
            type: 'bar',
            data: {
                labels: monthData.labels,
                datasets: [{ label: 'Doanh thu (đ)', data: monthData.values, backgroundColor: oxbloodSoft, borderColor: oxblood, borderWidth: 1 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        var yearData = readChartData('yearChartData');
        draw('yearChart', {
            type: 'bar',
            data: {
                labels: yearData.labels,
                datasets: [{ label: 'Doanh thu (đ)', data: yearData.values, backgroundColor: oxbloodSoft, borderColor: oxblood, borderWidth: 1 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        var methodData = readChartData('methodChartData');
        draw('methodChart', {
            type: 'pie',
            data: {
                labels: methodData.labels,
                datasets: [{ data: methodData.values, backgroundColor: palette }],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });
    });

    window.addEventListener('load', function () {
        if (typeof Chart === 'undefined' || window.__chartJsFailed) {
            var notice = document.getElementById('chartFallbackNotice');
            if (notice) notice.classList.remove('hidden');
        }
    });
</script>
@endsection
