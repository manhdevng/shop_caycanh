<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhongThuyLookupRequest;
use App\Models\ElementWaitlist;
use App\Models\Product;
use App\Services\PhongThuyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Trang Cây hợp mệnh (/cay-phong-thuy): tra mệnh theo ngày sinh, gợi ý cây
 * đang bán hợp mệnh và nhận email khi mệnh còn ít cây.
 *
 * Ngày sinh chỉ đi qua POST và không được lưu (CSDL, session, URL); trang
 * chia sẻ menh-{element} chỉ mang hành.
 */
class PhongThuyController extends Controller
{
    public function __construct(private PhongThuyService $phongThuy) {}

    public function index()
    {
        return view('shop.phong-thuy.index', [
            'result' => null,
            'recommendations' => null,
            'showWaitlist' => false,
        ]);
    }

    /**
     * Form thường (tắt JS, hoặc gửi từ khối trang chủ): render kết quả ngay,
     * không redirect để ngày sinh không phải đi qua session hay URL.
     */
    public function lookup(PhongThuyLookupRequest $request)
    {
        $result = $this->phongThuy->lookup(...$request->birth());
        $recommendations = $this->phongThuy->recommend($result['element']);

        return view('shop.phong-thuy.index', [
            'result' => $result,
            'recommendations' => $recommendations,
            'showWaitlist' => $this->phongThuy->isFew($recommendations),
            'input' => $request->only(['ngay', 'thang', 'nam']),
        ]);
    }

    /** Tải lại trang kết quả (GET): không còn ngày sinh nên về trang nhập. */
    public function reload()
    {
        return redirect()->route('phong-thuy.index');
    }

    /**
     * API cho la bàn (phong-thuy-compass.js): kết quả mệnh + HTML lưới cây,
     * dựng bằng cùng partial với SSR. Lỗi validate trả 422 JSON.
     */
    public function api(PhongThuyLookupRequest $request)
    {
        $result = $this->phongThuy->lookup(...$request->birth());
        $recommendations = $this->phongThuy->recommend($result['element']);

        return response()->json([
            'result' => $result,
            'recommendations_html' => view('shop.phong-thuy.partials.product-grid', [
                'element' => $result['element'],
                'recommendations' => $recommendations,
                'showWaitlist' => $this->phongThuy->isFew($recommendations),
            ])->render(),
        ]);
    }

    /** Trang chia sẻ theo hành: thông tin chung của hành và cây hợp. */
    public function element(string $element)
    {
        $recommendations = $this->phongThuy->recommend($element);

        return view('shop.phong-thuy.element', [
            'element' => $element,
            'recommendations' => $recommendations,
            'showWaitlist' => $this->phongThuy->isFew($recommendations),
        ]);
    }

    /**
     * Nhận email báo khi có thêm cây hợp một hành. Email trùng (cùng hành)
     * vẫn báo thành công để không lộ email nào đã đăng ký.
     */
    public function waitlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'element' => ['required', Rule::in(array_keys(Product::ELEMENTS))],
        ], [
            'email.required' => 'Email chưa đúng. Kiểm tra lại giúp chúng tôi.',
            'email.email' => 'Email chưa đúng. Kiểm tra lại giúp chúng tôi.',
            'email.max' => 'Email chưa đúng. Kiểm tra lại giúp chúng tôi.',
            'element.*' => 'Mệnh không hợp lệ.',
        ]);

        // Form thường gửi từ trang kết quả POST: quay về trang của hành (GET
        // được) thay vì back() về URL tra cứu.
        $element = $request->input('element');
        if ($validator->fails() && ! $request->expectsJson() && is_string($element) && isset(Product::ELEMENTS[$element])) {
            return redirect()
                ->to(route('phong-thuy.element', $element).'#cpt-cay')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }
        $data = $validator->validate();

        ElementWaitlist::firstOrCreate([
            'email' => mb_strtolower(trim($data['email'])),
            'element' => $data['element'],
        ]);

        $message = 'Đã lưu. Chúng tôi sẽ báo khi có thêm cây mệnh '.Product::ELEMENTS[$data['element']].'.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()
            ->to(route('phong-thuy.element', $data['element']).'#cpt-cay')
            ->with('waitlist_message', $message);
    }
}
