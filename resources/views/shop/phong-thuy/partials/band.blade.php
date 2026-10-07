{{--
    Chân phần tra cứu: link xem cây theo mệnh + ghi nguồn thuật toán âm lịch.
    Tham số: $current (string|null) hành đang xem, để đánh dấu aria-current.
--}}
@php
    $lex = require resource_path('views/shop/phong-thuy/lexicon.php');
    $current = $current ?? null;
    $links = ['kim', 'moc', 'thuy', 'hoa', 'tho'];
@endphp
<section id="cpt-theo-menh" class="cpt-band" aria-label="Xem cây theo mệnh">
    <div class="cpt-band__inner">
        <nav aria-label="Cây theo mệnh">
            <span>Xem cây theo mệnh:</span>
            @foreach($links as $key)
                <span><a href="{{ url('/cay-phong-thuy/menh-' . $key) }}" @if($current === $key) aria-current="page" @endif>{{ $lex['elements'][$key]['name'] }}</a>@unless($loop->last)<span aria-hidden="true">&nbsp;&nbsp;·</span>@endunless</span>
            @endforeach
        </nav>
        <p class="cpt-band__note">Gợi ý theo quan niệm ngũ hành, mang tính tham khảo. Năm âm lịch tính theo thuật toán lịch Việt Nam của Hồ Ngọc Đức, múi giờ UTC+7.</p>
    </div>
</section>
