<section id="heroSection" style="width:100%;min-height:100vh;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center">
    <video src="{{ asset('videos/hero.mp4') }}" autoplay muted loop playsinline preload="auto" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0"></video>
    @include('shop.partials.hero-pixel')
    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.3);z-index:1"></div>
    <div class="hero-veil" aria-hidden="true" style="position:absolute;inset:0;background:#0A100B;opacity:0;z-index:1;pointer-events:none"></div>
    <div aria-hidden="true" style="position:absolute;left:0;right:0;bottom:0;height:140px;background:linear-gradient(180deg,transparent 0%,#0A100B 100%);z-index:1;pointer-events:none"></div>
    <div class="hero-copy" style="position:relative;z-index:2;max-width:900px;margin:0 auto;padding:24px;text-align:center">
        <p style="font-family:'Space Mono',monospace;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.85);margin:0 0 20px">Bộ sưu tập mùa mới</p>
        <h1 style="font-family:'Anton',sans-serif;font-size:clamp(36px,6vw,64px);line-height:1.25;letter-spacing:0.01em;text-transform:uppercase;color:#FFFFFF;margin:0 0 20px">Mang thiên nhiên vào không gian sống của bạn</h1>
        <p style="font-size:16px;line-height:1.6;color:rgba(255,255,255,0.85);max-width:520px;margin:0 auto 32px">Cây cảnh được tuyển chọn kỹ, đóng gói cẩn thận, giao đến tận cửa nhà bạn trên toàn quốc.</p>
        <a href="{{ route('shop.index') }}#luoi-san-pham" style="display:inline-block;font-family:'Space Mono',monospace;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;background:#5C2323;color:#FFFFFF;padding:16px 36px;border-radius:999px">Khám phá ngay</a>
    </div>
</section>
