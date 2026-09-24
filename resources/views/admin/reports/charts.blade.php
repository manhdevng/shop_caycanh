@extends('layouts.app')

@section('title', 'Biểu đồ doanh thu · Cây Cảnh Shop')

@section('content')
<div class="flex justify-between items-center mb-8">
    <h2 class="text-5xl font-medium gloock text-text-primary tracking-tight">Báo cáo Doanh thu</h2>
</div>

<!-- Tab Bảng số liệu / Biểu đồ -->
<div class="flex items-center gap-2 mb-8">
    <a href="{{ route('admin.reports.index') }}" class="px-5 py-2 rounded-pill text-sm font-medium text-decoration-none border transition-colors bg-white text-text-secondary border-green-border hover:bg-green-background">
        Bảng số liệu
    </a>
    <a href="{{ route('admin.reports.charts') }}" class="px-5 py-2 rounded-pill text-sm font-medium text-decoration-none border transition-colors bg-green-primary text-white border-green-border">
        Biểu đồ
    </a>
</div>

<!-- Thông báo dự phòng nếu Chart.js không tải được (CDN bị chặn...) -->
<div id="chartFallbackNotice" class="hidden mb-6 px-5 py-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl text-sm">
    Không thể tải thư viện biểu đồ (Chart.js) từ CDN. Vui lòng kiểm tra kết nối mạng hoặc xem báo cáo dạng
    <a href="{{ route('admin.reports.index') }}" class="underline font-medium">bảng số liệu</a> thay thế.
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu theo danh mục</h3>
        <div id="catChartData" class="hidden" data-labels="{{ json_encode($catLabels) }}" data-values="{{ json_encode($catRevenue) }}"></div>
        <div class="relative h-72"><canvas id="catChart"></canvas></div>
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu theo phương thức thanh toán</h3>
        <div id="methodChartData" class="hidden" data-labels="{{ json_encode($paymentMethodLabels) }}" data-values="{{ json_encode($paymentMethodRevenue) }}"></div>
        <div class="relative h-72"><canvas id="methodChart"></canvas></div>
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm lg:col-span-2">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu 30 ngày gần nhất</h3>
        <div id="dateChartData" class="hidden" data-labels="{{ json_encode($revDateLabels) }}" data-values="{{ json_encode($revDateData) }}"></div>
        <div class="relative h-72"><canvas id="dateChart"></canvas></div>
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu 12 tháng gần nhất</h3>
        <div id="monthChartData" class="hidden" data-labels="{{ json_encode($revMonthLabels) }}" data-values="{{ json_encode($revMonthData) }}"></div>
        <div class="relative h-72"><canvas id="monthChart"></canvas></div>
    </div>

    <div class="bg-white rounded-[32px] p-8 border border-green-border shadow-sm">
        <h3 class="text-xl font-medium gloock text-text-primary mb-6">Doanh thu theo năm</h3>
        <div id="yearChartData" class="hidden" data-labels="{{ json_encode($revYearLabels) }}" data-values="{{ json_encode($revYearData) }}"></div>
        <div class="relative h-72"><canvas id="yearChart"></canvas></div>
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

        var catData = readChartData('catChartData');
        new Chart(document.getElementById('catChart'), {
            type: 'bar',
            data: {
                labels: catData.labels,
                datasets: [{ label: 'Doanh thu (đ)', data: catData.values, backgroundColor: oxbloodSoft, borderColor: oxblood, borderWidth: 1 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        var dateData = readChartData('dateChartData');
        new Chart(document.getElementById('dateChart'), {
            type: 'line',
            data: {
                labels: dateData.labels,
                datasets: [{ label: 'Doanh thu (đ)', data: dateData.values, borderColor: oxblood, backgroundColor: oxbloodSoft, tension: 0.3, fill: true }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        var monthData = readChartData('monthChartData');
        new Chart(document.getElementById('monthChart'), {
            type: 'bar',
            data: {
                labels: monthData.labels,
                datasets: [{ label: 'Doanh thu (đ)', data: monthData.values, backgroundColor: oxbloodSoft, borderColor: oxblood, borderWidth: 1 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        var yearData = readChartData('yearChartData');
        new Chart(document.getElementById('yearChart'), {
            type: 'bar',
            data: {
                labels: yearData.labels,
                datasets: [{ label: 'Doanh thu (đ)', data: yearData.values, backgroundColor: oxbloodSoft, borderColor: oxblood, borderWidth: 1 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });

        var methodData = readChartData('methodChartData');
        new Chart(document.getElementById('methodChart'), {
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
