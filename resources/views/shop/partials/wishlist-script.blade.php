{{-- Hàm toggleWishlist(productId, btn) mà shop.partials.wishlist-button gọi.
     Include MỘT lần ở trang có lưới thẻ sản phẩm. --}}
<script>
    // ==== Bật/tắt yêu thích trực tiếp trên thẻ sản phẩm (không reload) ====
    function toggleWishlist(productId, btn) {
        btn.disabled = true;

        const urlTemplate = "{{ route('wishlist.toggle', ['product' => '__ID__']) }}";

        fetch(urlTemplate.replace('__ID__', productId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
            .then(res => {
                if (!res.ok) throw new Error('request-failed');
                return res.json();
            })
            .then(data => {
                btn.disabled = false;
                const icon = btn.querySelector('.wishlist-heart-icon');
                if (icon) icon.setAttribute('fill', data.liked ? '#5C2323' : 'none');
                btn.setAttribute('aria-label', (data.liked ? 'Bỏ yêu thích' : 'Yêu thích'));
                if (typeof showToast === 'function') {
                    showToast(data.message);
                }
            })
            .catch(() => {
                // Chưa đăng nhập (401) hoặc lỗi khác -> chuyển hướng đăng nhập thay vì im lặng thất bại.
                window.location.href = "{{ route('login') }}";
            });
    }
</script>
