# Ảnh lá thật cho chuyển động trang chủ

Ảnh lá đã tách nền (WebP, nền trong suốt), dùng bởi `public/js/home-motion.js`
và các partial trong `resources/views/shop/partials/home/`.

Mọi ảnh đã được xoay cho **cuống chĩa thẳng xuống**, đầu cuống nằm sát đáy ảnh.
Lá xoay quanh điểm cuống, nên mỗi ảnh có một giá trị `ox` (vị trí cuống theo %
chiều ngang ảnh) dùng làm `transform-origin: ox 100%`.

| File | Nguồn gốc | ox | Kích thước gốc |
|---|---|---|---|
| `monstera-{160,600,1200}.webp` | Ảnh monstera, rawpixel | 65.4% | 1156×1805 |
| `la-gan-{160,600,1200}.webp` | Ảnh lá gân, pngtree | 48.9% | 1259×2251 |

Cỡ 160 dùng cho lá nhỏ (thân dây khối cam kết), 600/1200 dùng cho lá lớn qua
`srcset`.

## Cần thay trước khi đưa lên production

Hai ảnh gốc là **bản xem trước có watermark** (rawpixel, pngtree), nên watermark
mờ vẫn còn trên mặt lá. Hãy tải bản có giấy phép (rawpixel: tài khoản miễn phí
hoặc gói trả phí; pngtree: theo điều khoản của gói) rồi chạy lại quy trình bên
dưới với cùng tên file. Code không cần sửa gì.

## Quy trình tạo lại

1. Tách nền bằng `rembg` (model `isnet-general-use`, bật alpha matting).
2. Ghép thêm mặt nạ theo độ bão hòa màu để khoét sạch nền trong các khe lá
   (monstera) mà `rembg` bỏ sót.
3. Xoay cho cuống chĩa xuống, cắt sát viền, đo lại `ox`.
4. Xuất WebP bằng `sharp` ở ba cỡ 160 / 600 / 1200px chiều ngang.
