{{-- Bộ lọc kỳ dùng chung cho tab Bảng số liệu và Biểu đồ. Biến: $period (App\Support\ReportPeriod), $action (URL submit). --}}
@php
    $presets = \App\Support\ReportPeriod::PRESETS;
    $currentYear = now()->year;
    $selectedYear = (int) $period->param('year', $currentYear);
    $selectedMonth = (int) $period->param('month', now()->month);
    $selectedQuarter = (int) $period->param('quarter', now()->quarter);
    $inputClass = 'w-full bg-white border border-green-border rounded-pill px-4 py-2 text-sm outline-none focus:border-green-primary transition-all';
@endphp
<div class="bg-white rounded-[32px] p-6 border border-green-border shadow-sm mb-8">
    <form action="{{ $action }}" method="GET" id="reportPeriodForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
        <div class="lg:col-span-2">
            <label for="report-period" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Kỳ báo cáo</label>
            <select id="report-period" name="period" class="{{ $inputClass }}">
                @foreach ($presets as $key => $name)
                    <option value="{{ $key }}" @selected($period->preset === $key)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div data-period-field="month quarter year">
            <label for="report-year" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Năm</label>
            <select id="report-year" name="year" class="{{ $inputClass }}">
                @for ($y = $currentYear + 1; $y >= max(\App\Support\ReportPeriod::MIN_YEAR, min($selectedYear, $currentYear - 10)); $y--)
                    <option value="{{ $y }}" @selected($selectedYear === $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div data-period-field="month">
            <label for="report-month" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Tháng</label>
            <select id="report-month" name="month" class="{{ $inputClass }}">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($selectedMonth === $m)>Tháng {{ $m }}</option>
                @endfor
            </select>
        </div>
        <div data-period-field="quarter">
            <label for="report-quarter" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Quý</label>
            <select id="report-quarter" name="quarter" class="{{ $inputClass }}">
                @for ($q = 1; $q <= 4; $q++)
                    <option value="{{ $q }}" @selected($selectedQuarter === $q)>Quý {{ $q }}</option>
                @endfor
            </select>
        </div>
        <div data-period-field="custom">
            <label for="report-date-from" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Từ ngày</label>
            <input id="report-date-from" type="date" name="date_from" value="{{ $period->param('date_from', request('date_from')) }}" class="{{ $inputClass }}">
        </div>
        <div data-period-field="custom">
            <label for="report-date-to" class="block text-xs font-semibold text-text-secondary uppercase mb-1">Đến ngày</label>
            <input id="report-date-to" type="date" name="date_to" value="{{ $period->param('date_to', request('date_to')) }}" class="{{ $inputClass }}">
        </div>
        <div class="flex gap-3 lg:col-span-2">
            <button type="submit" class="px-6 py-2 bg-green-primary text-white rounded-full hover:bg-green-accent transition-all text-sm font-medium">Xem báo cáo</button>
            <a href="{{ $action }}" class="px-6 py-2 bg-white text-text-primary border border-green-border rounded-full hover:bg-green-background transition-all text-sm font-medium text-decoration-none">Mặc định</a>
        </div>
    </form>

    @if ($period->errors->isNotEmpty())
        <div class="mt-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm" role="alert">
            <p class="font-medium mb-1">Bộ lọc không hợp lệ, đang hiển thị {{ \Illuminate\Support\Str::lower($presets[\App\Support\ReportPeriod::DEFAULT_PRESET]) }}:</p>
            <ul class="list-disc pl-5">
                @foreach ($period->errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="mt-4 text-sm text-text-secondary">
        Đang xem: <span class="font-semibold text-text-primary" data-testid="report-period-label">{{ $period->label }}</span>.
        Doanh thu chỉ tính đơn đã thu tiền (thanh toán online thành công hoặc COD đã thu); không tính đơn COD chưa thu, đơn hủy, hoàn hàng hay hoàn tiền.
    </p>
</div>

<script>
    (function () {
        var form = document.getElementById('reportPeriodForm');
        if (!form) return;
        var select = form.querySelector('[name="period"]');
        function sync() {
            form.querySelectorAll('[data-period-field]').forEach(function (field) {
                var visible = field.dataset.periodField.split(' ').indexOf(select.value) !== -1;
                field.classList.toggle('hidden', !visible);
                // Ô bị ẩn không gửi lên để query string gọn và không lệch kỳ.
                field.querySelectorAll('input, select').forEach(function (input) { input.disabled = !visible; });
            });
        }
        select.addEventListener('change', sync);
        sync();
    })();
</script>
