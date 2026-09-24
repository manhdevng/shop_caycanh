<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

class AdminFaqController extends Controller
{
    public function index()
    {
        // Sắp theo placement rồi sort_order rồi id để admin dễ theo dõi
        // theo từng nhóm hiển thị (general/product).
        $faqs = Faq::orderBy('placement')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return view('admin.faqs.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        Faq::create($validated);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Đã tạo câu hỏi thường gặp thành công.');
    }

    public function show(Faq $faq)
    {
        return redirect()->route('admin.faqs.edit', $faq);
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $validated = $this->validateData($request, $faq);

        $faq->update($validated);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Đã cập nhật câu hỏi thường gặp.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Đã xoá câu hỏi thường gặp.');
    }

    /**
     * Rule validate dùng chung cho store/update. is_published là checkbox —
     * khi không tick, trình duyệt không gửi field này lên nên phải tự suy
     * ra false thay vì để validate 'required'.
     */
    private function validateData(Request $request, ?Faq $faq = null): array
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'placement' => 'required|in:general,product',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ], [
            'question.required' => 'Vui lòng nhập câu hỏi.',
            'question.max' => 'Câu hỏi không được vượt quá 255 ký tự.',
            'answer.required' => 'Vui lòng nhập câu trả lời.',
            'placement.required' => 'Vui lòng chọn vị trí hiển thị.',
            'placement.in' => 'Vị trí hiển thị không hợp lệ.',
            'sort_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'sort_order.min' => 'Thứ tự hiển thị không được nhỏ hơn 0.',
        ]);

        $validated['is_published'] = $request->boolean('is_published');

        // Cột faqs.sort_order là INT NOT NULL DEFAULT 0 — default chỉ áp
        // dụng khi INSERT không nhắc tới cột, không cứu được UPDATE gán
        // null. Rule 'nullable' cho phép để trống ô này trên form, nên phải
        // tự ép về 0 ở đây để tránh SQLSTATE 1048 (Column cannot be null).
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }
}
