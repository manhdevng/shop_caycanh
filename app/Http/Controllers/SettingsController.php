<?php

namespace App\Http\Controllers;

use App\Models\HomeFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function index()
    {
        // 3 hàng cố định (curated / service / materials) — bảng home_features
        // được seed sẵn 3 dòng này qua migration nên luôn có đủ dữ liệu.
        $homeFeatures = HomeFeature::orderBy('id')->get()->keyBy('slug');

        return view('settings.index', compact('homeFeatures'));
    }

    // Định dạng file hợp lệ theo từng loại media
    private const MEDIA_MIMES = [
        'image' => ['jpeg', 'png', 'jpg', 'gif', 'webp'],
        'video' => ['mp4', 'mov', 'webm'],
    ];

    public function updateHomeFeatures(Request $request)
    {
        $request->validate([
            'media_type' => 'required|array',
            'media_type.*' => 'required|in:image,video',
            'media' => 'nullable|array',
        ]);

        $features = HomeFeature::whereIn('slug', array_keys($request->input('media_type')))
            ->get()
            ->keyBy('slug');

        // Build rule cho từng slug theo media_type admin chọn: chọn ảnh thì chỉ nhận ảnh,
        // chọn video thì chỉ nhận video (tránh upload .mp4 nhưng lại để loại "Ảnh").
        $rules = [];
        $messages = [];
        foreach ($request->input('media_type') as $slug => $mediaType) {
            if (! $features->has($slug) || ! isset(self::MEDIA_MIMES[$mediaType])) {
                continue;
            }
            $field = "media.$slug";
            $rules[$field] = array_merge(
                ['nullable', 'file'],
                $mediaType === 'image' ? ['image'] : [],
                ['mimes:' . implode(',', self::MEDIA_MIMES[$mediaType]), 'max:51200']
            );
            $label = $features[$slug]->title ?: $slug;
            if ($mediaType === 'image') {
                $messages["$field.image"] = "[$label] Đã chọn loại Ảnh nên chỉ chấp nhận ảnh (jpg, png, gif, webp).";
                $messages["$field.mimes"] = "[$label] Đã chọn loại Ảnh nên chỉ chấp nhận ảnh (jpg, png, gif, webp).";
            } else {
                $messages["$field.mimes"] = "[$label] Đã chọn loại Video nên chỉ chấp nhận video (mp4, mov, webm).";
            }
            $messages["$field.max"] = "[$label] Dung lượng tối đa 50MB.";
        }

        $request->validate($rules, $messages);

        // Đổi loại media mà không upload file mới → media_path cũ sẽ sai loại (vd path ảnh nhưng render <video>).
        // Chỉ cho qua nếu đuôi file hiện tại đã khớp với loại mới.
        $typeErrors = [];
        foreach ($request->input('media_type') as $slug => $mediaType) {
            $feature = $features->get($slug);
            if (! $feature || $feature->media_type === $mediaType || $request->hasFile("media.$slug")) {
                continue;
            }
            $ext = strtolower(pathinfo((string) $feature->media_path, PATHINFO_EXTENSION));
            if ($feature->media_path && ! in_array($ext, self::MEDIA_MIMES[$mediaType], true)) {
                $label = $feature->title ?: $slug;
                $typeName = $mediaType === 'video' ? 'Video' : 'Ảnh';
                $typeErrors["media.$slug"] = "[$label] Đổi sang loại $typeName thì phải tải lên file $typeName mới.";
            }
        }
        if ($typeErrors) {
            throw ValidationException::withMessages($typeErrors);
        }

        foreach ($request->input('media_type') as $slug => $mediaType) {
            $feature = $features->get($slug);
            if (! $feature) {
                continue;
            }

            $data = ['media_type' => $mediaType];
            $oldPath = null;

            if ($request->hasFile("media.$slug")) {
                // Lưu file mới + cập nhật DB trước, xong mới xoá file cũ
                // → nếu store/update lỗi thì vẫn còn file cũ để hiển thị.
                $oldPath = $feature->media_path;
                $data['media_path'] = $request->file("media.$slug")->store('home-features', 'public');
            }

            $feature->update($data);

            if ($oldPath && $oldPath !== $feature->media_path) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return redirect()->route('settings.index')->with('success', 'Đã cập nhật ảnh/video cho khối giới thiệu trang chủ.');
    }
}
