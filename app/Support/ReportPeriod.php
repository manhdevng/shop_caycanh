<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

/**
 * Kỳ lọc báo cáo dùng chung cho bảng số liệu, biểu đồ và các trang thống kê.
 *
 * Mỗi kỳ được quy về nửa khoảng [from, untilExclusive) theo timezone của ứng
 * dụng: ngày kết thúc người dùng chọn được tính tới hết ngày bằng cận trên là
 * 00:00 của ngày kế tiếp. Preset "all" không có cận (from/untilExclusive = null).
 *
 * Tham số query: period=last30|month|quarter|year|custom|all,
 * year (month/quarter/year), month (1-12), quarter (1-4), date_from/date_to (Y-m-d, custom).
 */
final class ReportPeriod
{
    public const PRESETS = [
        'last30' => '30 ngày gần nhất',
        'month' => 'Theo tháng',
        'quarter' => 'Theo quý',
        'year' => 'Theo năm',
        'custom' => 'Khoảng ngày',
        'all' => 'Toàn thời gian',
    ];

    public const DEFAULT_PRESET = 'last30';

    public const MIN_YEAR = 2000;

    /** Khoảng ngày tự chọn dài nhất (ngày, tính cả hai đầu). */
    public const MAX_CUSTOM_DAYS = 731;

    private function __construct(
        public readonly string $preset,
        public readonly ?CarbonImmutable $from,
        public readonly ?CarbonImmutable $untilExclusive,
        public readonly string $label,
        private readonly array $params,
        public readonly MessageBag $errors,
    ) {
    }

    /**
     * Đọc kỳ lọc từ query string. Tham số sai không ném lỗi (trang GET không
     * nên redirect vòng): trả về kỳ mặc định kèm $errors để view hiển thị.
     */
    public static function fromRequest(Request $request): self
    {
        $input = $request->only(['period', 'year', 'month', 'quarter', 'date_from', 'date_to']);
        $validator = Validator::make($input, [
            'period' => ['nullable', Rule::in(array_keys(self::PRESETS))],
            'year' => ['nullable', 'required_if:period,month,quarter,year', 'integer', 'between:'.self::MIN_YEAR.','.(now()->year + 1)],
            'month' => ['nullable', 'required_if:period,month', 'integer', 'between:1,12'],
            'quarter' => ['nullable', 'required_if:period,quarter', 'integer', 'between:1,4'],
            'date_from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ], [
            'period.in' => 'Kỳ báo cáo không hợp lệ.',
            'year.required_if' => 'Vui lòng chọn năm.',
            'year.*' => 'Năm phải trong khoảng '.self::MIN_YEAR.'–'.(now()->year + 1).'.',
            'month.required_if' => 'Vui lòng chọn tháng.',
            'month.*' => 'Tháng phải từ 1 đến 12.',
            'quarter.required_if' => 'Vui lòng chọn quý.',
            'quarter.*' => 'Quý phải từ 1 đến 4.',
            'date_from.required_if' => 'Vui lòng chọn ngày bắt đầu.',
            'date_to.required_if' => 'Vui lòng chọn ngày kết thúc.',
            'date_to.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
            '*.date_format' => 'Ngày không hợp lệ (định dạng năm-tháng-ngày).',
        ]);

        $validator->after(function ($validator) use ($input) {
            if (($input['period'] ?? null) !== 'custom' || $validator->errors()->isNotEmpty()) {
                return;
            }
            $days = (int) CarbonImmutable::parse($input['date_from'])->diffInDays(CarbonImmutable::parse($input['date_to'])) + 1;
            if ($days > self::MAX_CUSTOM_DAYS) {
                $validator->errors()->add('date_to', 'Khoảng ngày tối đa '.self::MAX_CUSTOM_DAYS.' ngày; hãy dùng bộ lọc theo năm hoặc toàn thời gian.');
            }
        });

        if ($validator->fails()) {
            return self::make(self::DEFAULT_PRESET, [], $validator->errors());
        }

        return self::make($input['period'] ?? self::DEFAULT_PRESET, $input);
    }

    /**
     * Tạo kỳ từ preset + tham số đã hợp lệ (dùng cho lối tắt kỳ nhanh, test...).
     *
     * @param  array{year?: int|string, month?: int|string, quarter?: int|string, date_from?: string, date_to?: string}  $params
     */
    public static function make(string $preset, array $params = [], ?MessageBag $errors = null): self
    {
        $today = CarbonImmutable::now()->startOfDay();
        $year = (int) ($params['year'] ?? $today->year);
        $errors ??= new MessageBag();

        switch ($preset) {
            case 'month':
                $month = (int) ($params['month'] ?? $today->month);
                $from = CarbonImmutable::create($year, $month, 1)->startOfDay();

                return new self($preset, $from, $from->addMonthNoOverflow(), sprintf('Tháng %02d/%d', $month, $year),
                    ['year' => $year, 'month' => $month], $errors);
            case 'quarter':
                $quarter = (int) ($params['quarter'] ?? $today->quarter);
                $from = CarbonImmutable::create($year, ($quarter - 1) * 3 + 1, 1)->startOfDay();

                return new self($preset, $from, $from->addMonthsNoOverflow(3), 'Quý '.$quarter.'/'.$year,
                    ['year' => $year, 'quarter' => $quarter], $errors);
            case 'year':
                $from = CarbonImmutable::create($year, 1, 1)->startOfDay();

                return new self($preset, $from, $from->addYear(), 'Năm '.$year, ['year' => $year], $errors);
            case 'custom':
                $from = CarbonImmutable::createFromFormat('Y-m-d', $params['date_from'])->startOfDay();
                $to = CarbonImmutable::createFromFormat('Y-m-d', $params['date_to'])->startOfDay();

                return new self($preset, $from, $to->addDay(), 'Từ '.$from->format('d/m/Y').' đến '.$to->format('d/m/Y'),
                    ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()], $errors);
            case 'all':
                return new self($preset, null, null, 'Toàn thời gian', [], $errors);
            default:
                // last30: hôm nay và 29 ngày trước đó.
                $from = $today->subDays(29);

                return new self('last30', $from, $today->addDay(),
                    '30 ngày gần nhất ('.$from->format('d/m/Y').' – '.$today->format('d/m/Y').')', [], $errors);
        }
    }

    /** Tham số query để giữ bộ lọc khi chuyển tab/sắp xếp/phân trang. */
    public function query(): array
    {
        return ['period' => $this->preset] + $this->params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /** Ngày cuối cùng (tính cả) của kỳ, null nếu không giới hạn. */
    public function lastDay(): ?CarbonImmutable
    {
        return $this->untilExclusive?->subDay();
    }
}
