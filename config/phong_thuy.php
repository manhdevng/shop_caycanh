<?php

/*
 * Dữ liệu cố định cho tính năng "Cây hợp mệnh". Nguồn: ke-hoach-cay-phong-thuy.md
 * mục 1.1–1.3 và design/cay-hop-menh/project/ngu-hanh-v2.js (NAP, ADVICE2).
 *
 * Tương sinh: Kim → Thủy → Mộc → Hỏa → Thổ → Kim.
 * Tương khắc: Kim khắc Mộc, Mộc khắc Thổ, Thổ khắc Thủy, Thủy khắc Hỏa, Hỏa khắc Kim.
 */
return [

    'elements' => [
        'kim' => [
            'label' => 'Kim',
            'colors' => ['trắng', 'vàng nhạt', 'ánh kim'],
            'sinh_ra_boi' => 'tho',
            'khac_boi' => 'hoa',
            'advice' => 'Hợp cây lá viền trắng hoặc vàng nhạt, chậu sứ trắng.',
        ],
        'thuy' => [
            'label' => 'Thủy',
            'colors' => ['đen', 'xanh dương'],
            'sinh_ra_boi' => 'kim',
            'khac_boi' => 'tho',
            'advice' => 'Hợp cây thủy canh, lá sẫm, chậu thủy tinh hoặc men lam.',
        ],
        'moc' => [
            'label' => 'Mộc',
            'colors' => ['xanh lá'],
            'sinh_ra_boi' => 'thuy',
            'khac_boi' => 'kim',
            'advice' => 'Hợp cây lá xanh, thân đứng, chậu gỗ hoặc gốm men xanh.',
        ],
        'hoa' => [
            'label' => 'Hỏa',
            'colors' => ['đỏ', 'hồng', 'tím', 'cam'],
            'sinh_ra_boi' => 'moc',
            'khac_boi' => 'thuy',
            'advice' => 'Hợp cây hoa đỏ, hồng, tím, chậu đất nung.',
        ],
        'tho' => [
            'label' => 'Thổ',
            'colors' => ['vàng đất', 'nâu'],
            'sinh_ra_boi' => 'hoa',
            'khac_boi' => 'moc',
            'advice' => 'Hợp những cây lá to, sắc vàng đất và đỏ ấm.',
        ],
    ],

    /*
     * Gợi ý hành theo tên cây (PhongThuyService::suggestElements, form admin,
     * lệnh phong-thuy:goi-y-hanh). Mỗi từ khóa khớp nguyên cụm trong tên, không
     * phân biệt hoa thường; một cây có thể khớp nhiều hành. Chỉ là gợi ý để
     * admin duyệt — cây không khớp từ khóa nào vẫn để "Chưa gán hành".
     * Có loại cây mới thì thêm từ khóa vào đây, không cần sửa code.
     */
    'element_keywords' => [
        'kim' => [
            'kim ngân', 'lan ý', 'bạch mã', 'ngọc ngân', 'cung điện vàng', 'bạch lan',
            'lá trắng', 'viền trắng', 'viền bạc', 'cẩm nhung trắng', 'sứ trắng',
        ],
        'thuy' => [
            'thủy canh', 'thủy sinh', 'thủy trúc', 'trúc thủy', 'phát tài núi', 'ngọc bích',
            'cỏ lan chi', 'lan chi', 'cau tiểu trâm', 'trầu bà thủy',
        ],
        'moc' => [
            'trầu bà', 'trầu nam mỹ', 'kim tiền', 'vạn niên thanh', 'phát tài', 'phát lộc',
            'thường xuân', 'bàng singapore', 'bàng đài loan', 'hạnh phúc', 'tùng', 'bonsai',
            'đa búp đỏ', 'monstera', 'dương xỉ', 'cau',
        ],
        'hoa' => [
            'vạn lộc', 'hồng môn', 'trạng nguyên', 'phú quý', 'đuôi công tím', 'tróc bạc đỏ',
            'hoa giấy', 'lá đỏ', 'hoa đỏ', 'hoa hồng', 'hoa tím', 'trầu bà đỏ',
        ],
        'tho' => [
            'lưỡi hổ', 'sen đá', 'xương rồng', 'cẩm nhung', 'ngọc ngân', 'cau vàng',
            'phát tài búp sen', 'kim phát tài', 'vàng đất',
        ],
    ],

    'can' => ['Giáp', 'Ất', 'Bính', 'Đinh', 'Mậu', 'Kỷ', 'Canh', 'Tân', 'Nhâm', 'Quý'],

    'chi' => ['Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ', 'Ngọ', 'Mùi', 'Thân', 'Dậu', 'Tuất', 'Hợi'],

    /*
     * 30 cặp Nạp Âm theo thứ tự trong chu kỳ 60 năm (pair = intdiv(cycle_index, 2)).
     * [tên, hành, diễn giải]
     */
    'nap_am' => [
        ['Hải Trung Kim', 'kim', 'Vàng trong lòng biển: quý giá, ẩn mình chờ thời.'],
        ['Lư Trung Hỏa', 'hoa', 'Lửa trong lò: bền bỉ, nung chín mọi việc.'],
        ['Đại Lâm Mộc', 'moc', 'Cây trong rừng lớn: sum suê, che bóng cho người.'],
        ['Lộ Bàng Thổ', 'tho', 'Đất ven đường: rộng rãi, nâng bước người đi.'],
        ['Kiếm Phong Kim', 'kim', 'Vàng nơi mũi kiếm: sắc bén, quyết đoán.'],
        ['Sơn Đầu Hỏa', 'hoa', 'Lửa trên đỉnh núi: sáng rõ, soi đường xa.'],
        ['Giản Hạ Thủy', 'thuy', 'Nước dưới khe: trong mát, lặng lẽ mà bền.'],
        ['Thành Đầu Thổ', 'tho', 'Đất trên tường thành: vững, che chở.'],
        ['Bạch Lạp Kim', 'kim', 'Vàng trong nến sáp: tinh khiết, càng rèn càng sáng.'],
        ['Dương Liễu Mộc', 'moc', 'Cây dương liễu: mềm mại, uyển chuyển trước gió.'],
        ['Tuyền Trung Thủy', 'thuy', 'Nước giữa lòng suối: dồi dào, không cạn.'],
        ['Ốc Thượng Thổ', 'tho', 'Đất trên mái nhà: giữ ấm, bao bọc gia đình.'],
        ['Tích Lịch Hỏa', 'hoa', 'Lửa sấm sét: mạnh mẽ, bừng lên tức thì.'],
        ['Tùng Bách Mộc', 'moc', 'Cây tùng cây bách: cứng cỏi, xanh quanh năm.'],
        ['Trường Lưu Thủy', 'thuy', 'Dòng nước chảy dài: kiên trì, đi xa.'],
        ['Sa Trung Kim', 'kim', 'Vàng trong cát: giá trị ẩn sau vẻ giản dị.'],
        ['Sơn Hạ Hỏa', 'hoa', 'Lửa dưới chân núi: ấm áp, gần gũi.'],
        ['Bình Địa Mộc', 'moc', 'Cây trên đất bằng: hiền hòa, dễ bén rễ.'],
        ['Bích Thượng Thổ', 'tho', 'Đất trên vách: chắc chắn, giữ nếp nhà.'],
        ['Kim Bạch Kim', 'kim', 'Vàng pha bạch kim: sáng đẹp, tinh tế.'],
        ['Phú Đăng Hỏa', 'hoa', 'Lửa ngọn đèn: nhỏ mà soi tỏ, cháy bền lâu.'],
        ['Thiên Hà Thủy', 'thuy', 'Nước trên trời: mưa lành, nuôi dưỡng muôn loài.'],
        ['Đại Trạch Thổ', 'tho', 'Đất đầm lớn: bao dung, rộng mở.'],
        ['Thoa Xuyến Kim', 'kim', 'Vàng trâm thoa: duyên dáng, quý phái.'],
        ['Tang Đố Mộc', 'moc', 'Cây dâu tằm: chăm chỉ, sinh sôi.'],
        ['Đại Khê Thủy', 'thuy', 'Nước khe lớn: mạnh mẽ, tự mở lối.'],
        ['Sa Trung Thổ', 'tho', 'Đất pha cát: nhẹ nhàng, dễ thích nghi.'],
        ['Thiên Thượng Hỏa', 'hoa', 'Lửa trên trời: rực rỡ như mặt trời.'],
        ['Thạch Lựu Mộc', 'moc', 'Cây thạch lựu: chắc gỗ, sai quả.'],
        ['Đại Hải Thủy', 'thuy', 'Nước biển lớn: bao la, khoáng đạt.'],
    ],

    // Lời nhắc khi khách chỉ nhập năm (câu chữ của prototype Cay Phong Thuy).
    'year_only_hint' => 'Nếu bạn sinh tháng 1 hoặc đầu tháng 2, nhập đủ ngày để kết quả chính xác.',

    // Năm sớm nhất được tra cứu.
    'min_year' => 1920,

    // Tổng số cây hợp (bản mệnh + tương sinh) dưới ngưỡng này thì bù trung tính và hiện form nhận tin.
    'few_threshold' => 4,
];
