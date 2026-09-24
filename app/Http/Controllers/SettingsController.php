<?php

namespace App\Http\Controllers;

use App\Models\HomeFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        // 3 hàng cố định (curated / service / materials) — bảng home_features
        // được seed sẵn 3 dòng này qua migration nên luôn có đủ dữ liệu.
        $homeFeatures = HomeFeature::orderBy('id')->get()->keyBy('slug');

        return view('settings.index', compact('homeFeatures'));
    }

    public function updateHomeFeatures(Request $request)
    {
        $validated = $request->validate([
            'media_type' => 'required|array',
            'media_type.*' => 'required|in:image,video',
            'media' => 'nullable|array',
            'media.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,webm|max:51200',
        ], [
            'media.*.mimes' => 'Chỉ chấp nhận ảnh (jpg, png, gif, webp) hoặc video (mp4, mov, webm).',
            'media.*.max' => 'Dung lượng tối đa 50MB.',
        ]);

        foreach ($validated['media_type'] as $slug => $mediaType) {
            $feature = HomeFeature::where('slug', $slug)->first();
            if (! $feature) {
                continue;
            }

            $data = ['media_type' => $mediaType];

            if ($request->hasFile("media.$slug")) {
                if ($feature->media_path) {
                    Storage::disk('public')->delete($feature->media_path);
                }
                $data['media_path'] = $request->file("media.$slug")->store('home-features', 'public');
            }

            $feature->update($data);
        }

        return redirect()->route('settings.index')->with('success', 'Đã cập nhật ảnh/video cho khối giới thiệu trang chủ.');
    }
}
