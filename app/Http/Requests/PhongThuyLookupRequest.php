<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\ViewErrorBag;

/**
 * Ngày sinh cho trang Cây hợp mệnh: năm bắt buộc (1920 → năm nay), ngày và
 * tháng cùng có hoặc cùng trống, ngày phải có thật và không ở tương lai.
 * Câu báo lỗi trùng với validate() trong public/js/phong-thuy-compass.js.
 *
 * Ngày sinh không được lưu: khi lỗi ở form thường, không redirect kèm
 * old() (sẽ ghi ngày sinh vào session) mà render lại trang ngay trong response.
 */
class PhongThuyLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nam' => ['required', 'regex:/^\d{4}$/', 'integer', 'between:1920,'.now()->year],
            'ngay' => ['nullable', 'regex:/^\d{1,2}$/'],
            'thang' => ['nullable', 'regex:/^\d{1,2}$/'],
        ];
    }

    public function messages(): array
    {
        $year = 'Năm sinh cần đủ 4 chữ số, từ 1920 đến '.now()->year.'.';
        $date = 'Ngày và tháng chỉ gồm chữ số. Kiểm tra lại ngày và tháng.';

        return [
            'nam.required' => $year,
            'nam.regex' => $year,
            'nam.integer' => $year,
            'nam.between' => $year,
            'ngay.regex' => $date,
            'thang.regex' => $date,
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $errors = $validator->errors();
            if ($errors->hasAny(['nam', 'ngay', 'thang'])) {
                return;
            }

            $d = $this->input('ngay');
            $m = $this->input('thang');
            if ($d === null && $m === null) {
                return;
            }
            if ($d === null || $m === null) {
                $errors->add($d === null ? 'ngay' : 'thang', 'Nhập cả ngày và tháng, hoặc để trống cả hai.');

                return;
            }

            $D = (int) $d;
            $M = (int) $m;
            $Y = (int) $this->input('nam');
            if ($M < 1 || $M > 12) {
                $errors->add('thang', 'Không có tháng '.$m.'. Kiểm tra lại ngày và tháng.');

                return;
            }
            if (! checkdate($M, $D, $Y)) {
                $errors->add('ngay', 'Ngày '.$D.' tháng '.$M.' không có thật. Kiểm tra lại ngày và tháng.');

                return;
            }
            if (sprintf('%04d-%02d-%02d', $Y, $M, $D) > now()->toDateString()) {
                $errors->add('ngay', 'Ngày sinh không thể ở tương lai. Kiểm tra lại ngày, tháng và năm.');
            }
        }];
    }

    /** @return array{0:?int,1:?int,2:int} [ngày, tháng, năm] đã kiểm tra */
    public function birth(): array
    {
        $d = $this->input('ngay');
        $m = $this->input('thang');

        return [$d === null ? null : (int) $d, $m === null ? null : (int) $m, (int) $this->input('nam')];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson() || $this->is('api/*')) {
            parent::failedValidation($validator);
        }

        $errors = (new ViewErrorBag)->put('default', $validator->errors());

        throw new HttpResponseException(response()->view('shop.phong-thuy.index', [
            'result' => null,
            'recommendations' => null,
            'showWaitlist' => false,
            'input' => $this->only(['ngay', 'thang', 'nam']),
            'errors' => $errors,
        ], 422));
    }
}
