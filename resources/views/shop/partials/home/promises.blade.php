<section data-sc-act="flow" style="background:#FFFFFF;padding:clamp(56px,8vw,120px) 24px">
    <div style="max-width:1200px;margin:0 auto">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:40px">
            @php
                $featureBullets = [
                    'curated' => ['Chọn từ vườn ươm uy tín', 'Kiểm tra kỹ trước khi giao', 'Cam kết đúng kích thước'],
                    'service' => ['Giao hàng toàn quốc', 'Đổi trả trong 3 ngày', 'Bảo hành cây dài hạn'],
                    'materials' => ['Chậu gốm cao cấp', 'Đất trồng hữu cơ', 'Đóng gói chống sốc'],
                ];
                $featurePlaceholderLabels = [
                    'curated' => 'ẢNH VƯỜN ƯƠM',
                    'service' => 'ẢNH GIAO HÀNG',
                    'materials' => 'ẢNH CHẬU &amp; ĐẤT TRỒNG',
                ];
                $featureTitles = [
                    'curated' => 'Cây được tuyển chọn',
                    'service' => 'Dịch vụ tận tâm',
                    'materials' => 'Chất liệu cao cấp',
                ];
            @endphp
            @foreach(['curated', 'service', 'materials'] as $i => $slug)
                @php $feature = $homeFeatures->get($slug); @endphp
                <div style="text-align:center">
                    <span class="sc-rule"></span>
                    <div data-sc-in data-sc-stagger="60">
                        <h3 style="font-family:'Anton',sans-serif;font-size:22px;letter-spacing:0.01em;text-transform:uppercase;color:#1C1C1A;margin:0 0 20px">{{ $feature->title ?? $featureTitles[$slug] }}</h3>
                        @if($feature && $feature->media_path)
                            <div style="aspect-ratio:1/1;border-radius:12px;overflow:hidden;margin-bottom:20px">
                                @if($feature->isVideo())
                                    <video src="{{ $feature->media_url }}" autoplay muted loop playsinline style="width:100%;height:100%;object-fit:cover;display:block"></video>
                                @else
                                    <img src="{{ $feature->media_url }}" alt="{{ $feature->title }}" style="width:100%;height:100%;object-fit:cover;display:block">
                                @endif
                            </div>
                        @else
                            <div class="placeholder-pattern" style="aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;margin-bottom:20px">
                                <span style="font-family:ui-monospace,Menlo,monospace;font-size:10px;color:#A8A196">{!! $featurePlaceholderLabels[$slug] !!}</span>
                            </div>
                        @endif
                        <ul style="list-style:none;margin:0;padding:0;text-align:left;color:#6B6B66;font-size:14px;line-height:1.9">
                            @foreach($featureBullets[$slug] as $bullet)
                                <li>&mdash; {{ $bullet }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<style>
/* Khối H — đường kẻ cấu trúc tĩnh (không reveal — H và G liền kề nhau nên
   không được trùng device family `reveal`; điều đáng nhớ ở H là nội dung cam
   kết nâng lên bằng `in`/`stagger`, đường kẻ chỉ là khung, không phải khoảnh
   khắc) thay cho border-top cũ, rồi nội dung cột nâng lên theo sau. */
.sc-home .sc-rule {
    display: block;
    height: 1px;
    background: var(--sc-hairline);
    width: 100%;
    margin-bottom: 24px;
}
</style>
