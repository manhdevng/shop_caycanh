<?php

/*
 * Chữ hiển thị cho trang Cây hợp mệnh (tên hành, chữ Hán, câu diễn giải).
 *
 * Chỉ là lớp trình bày: phép tính năm âm lịch, Nạp Âm, hành và nhóm cây do
 * PhongThuyService làm. File này nhận result array của service và đổi thành
 * chữ để Blade và JS cùng dùng một nguồn (JS đọc bản JSON in ra trang).
 *
 * Nguồn: design/cay-hop-menh/project/ngu-hanh-v2.js (NAP, GLOSS2, ADVICE2,
 * *_HAN) và phong-thuy-la-ban-style.md mục 9.
 */

$elements = [
    // Thứ tự tương sinh: Kim → Thủy → Mộc → Hỏa → Thổ → Kim.
    'kim' => ['name' => 'Kim', 'han' => '金', 'mother' => 'tho', 'avoid' => 'hoa',
        'advice' => 'Hợp cây lá viền trắng hoặc vàng nhạt, chậu sứ trắng.',
        'about' => 'Sáng, gọn, quyết đoán.'],
    'thuy' => ['name' => 'Thủy', 'han' => '水', 'mother' => 'kim', 'avoid' => 'tho',
        'advice' => 'Hợp cây thủy canh, lá sẫm, chậu thủy tinh hoặc men lam.',
        'about' => 'Linh hoạt, sâu lắng.'],
    'moc' => ['name' => 'Mộc', 'han' => '木', 'mother' => 'thuy', 'avoid' => 'kim',
        'advice' => 'Hợp cây lá xanh, thân đứng, chậu gỗ hoặc gốm men xanh.',
        'about' => 'Lớn dần, mềm mà bền.'],
    'hoa' => ['name' => 'Hỏa', 'han' => '火', 'mother' => 'moc', 'avoid' => 'thuy',
        'advice' => 'Hợp cây hoa đỏ, hồng, tím, chậu đất nung.',
        'about' => 'Ấm, rực, nhiều năng lượng.'],
    'tho' => ['name' => 'Thổ', 'han' => '土', 'mother' => 'hoa', 'avoid' => 'moc',
        'advice' => 'Hợp những cây lá to, sắc vàng đất và đỏ ấm.',
        'about' => 'Vững, che chở, bao dung.'],
];

// Vị trí nút hành trên la bàn: 火 ở 12 giờ, theo chiều kim đồng hồ 土 金 水 木.
$order = ['hoa', 'tho', 'kim', 'thuy', 'moc'];

$can = ['Giáp', 'Ất', 'Bính', 'Đinh', 'Mậu', 'Kỷ', 'Canh', 'Tân', 'Nhâm', 'Quý'];
$canHan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
$chi = ['Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ', 'Ngọ', 'Mùi', 'Thân', 'Dậu', 'Tuất', 'Hợi'];
$chiHan = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

// 30 Nạp Âm theo cặp năm (cycle_index / 2): [tên, hành, chữ Hán, diễn giải].
// Chữ Hán của Đại Trạch Thổ (sách Trung Hoa ghi 大驛土) chưa được kiểm chứng
// nên để null: trang chỉ hiện chữ Việt.
$nap = [
    ['Hải Trung Kim', 'kim', '海中金', 'Vàng trong lòng biển: quý giá, ẩn mình chờ thời.'],
    ['Lư Trung Hỏa', 'hoa', '爐中火', 'Lửa trong lò: bền bỉ, nung chín mọi việc.'],
    ['Đại Lâm Mộc', 'moc', '大林木', 'Cây trong rừng lớn: sum suê, che bóng cho người.'],
    ['Lộ Bàng Thổ', 'tho', '路旁土', 'Đất ven đường: rộng rãi, nâng bước người đi.'],
    ['Kiếm Phong Kim', 'kim', '劍鋒金', 'Vàng nơi mũi kiếm: sắc bén, quyết đoán.'],
    ['Sơn Đầu Hỏa', 'hoa', '山頭火', 'Lửa trên đỉnh núi: sáng rõ, soi đường xa.'],
    ['Giản Hạ Thủy', 'thuy', '澗下水', 'Nước dưới khe: trong mát, lặng lẽ mà bền.'],
    ['Thành Đầu Thổ', 'tho', '城頭土', 'Đất trên tường thành: vững, che chở.'],
    ['Bạch Lạp Kim', 'kim', '白蠟金', 'Vàng trong nến sáp: tinh khiết, càng rèn càng sáng.'],
    ['Dương Liễu Mộc', 'moc', '楊柳木', 'Cây dương liễu: mềm mại, uyển chuyển trước gió.'],
    ['Tuyền Trung Thủy', 'thuy', '泉中水', 'Nước giữa lòng suối: dồi dào, không cạn.'],
    ['Ốc Thượng Thổ', 'tho', '屋上土', 'Đất trên mái nhà: giữ ấm, bao bọc gia đình.'],
    ['Tích Lịch Hỏa', 'hoa', '霹靂火', 'Lửa sấm sét: mạnh mẽ, bừng lên tức thì.'],
    ['Tùng Bách Mộc', 'moc', '松柏木', 'Cây tùng cây bách: cứng cỏi, xanh quanh năm.'],
    ['Trường Lưu Thủy', 'thuy', '長流水', 'Dòng nước chảy dài: kiên trì, đi xa.'],
    ['Sa Trung Kim', 'kim', '沙中金', 'Vàng trong cát: giá trị ẩn sau vẻ giản dị.'],
    ['Sơn Hạ Hỏa', 'hoa', '山下火', 'Lửa dưới chân núi: ấm áp, gần gũi.'],
    ['Bình Địa Mộc', 'moc', '平地木', 'Cây trên đất bằng: hiền hòa, dễ bén rễ.'],
    ['Bích Thượng Thổ', 'tho', '壁上土', 'Đất trên vách: chắc chắn, giữ nếp nhà.'],
    ['Kim Bạch Kim', 'kim', '金箔金', 'Vàng pha bạch kim: sáng đẹp, tinh tế.'],
    ['Phú Đăng Hỏa', 'hoa', '覆燈火', 'Lửa ngọn đèn: nhỏ mà soi tỏ, cháy bền lâu.'],
    ['Thiên Hà Thủy', 'thuy', '天河水', 'Nước trên trời: mưa lành, nuôi dưỡng muôn loài.'],
    ['Đại Trạch Thổ', 'tho', null, 'Đất đầm lớn: bao dung, rộng mở.'],
    ['Thoa Xuyến Kim', 'kim', '釵釧金', 'Vàng trâm thoa: duyên dáng, quý phái.'],
    ['Tang Đố Mộc', 'moc', '桑柘木', 'Cây dâu tằm: chăm chỉ, sinh sôi.'],
    ['Đại Khê Thủy', 'thuy', '大溪水', 'Nước khe lớn: mạnh mẽ, tự mở lối.'],
    ['Sa Trung Thổ', 'tho', '沙中土', 'Đất pha cát: nhẹ nhàng, dễ thích nghi.'],
    ['Thiên Thượng Hỏa', 'hoa', '天上火', 'Lửa trên trời: rực rỡ như mặt trời.'],
    ['Thạch Lựu Mộc', 'moc', '石榴木', 'Cây thạch lựu: chắc gỗ, sai quả.'],
    ['Đại Hải Thủy', 'thuy', '大海水', 'Nước biển lớn: bao la, khoáng đạt.'],
];

/*
 * Đổi result array của PhongThuyService thành các chuỗi hiển thị. Chịu được
 * thiếu trường: chỉ cycle_index (0–59) và element là bắt buộc để vẽ.
 */
$present = function (?array $result) use ($elements, $can, $chi, $nap) {
    if (! $result || ! isset($result['cycle_index'])) {
        return null;
    }
    $idx = ((int) $result['cycle_index'] % 60 + 60) % 60;
    $napRow = $nap[intdiv($idx, 2)];
    $el = $result['element'] ?? $napRow[1];
    if (! isset($elements[$el])) {
        $el = $napRow[1];
    }
    $mother = $result['relations']['tuong_sinh'] ?? $result['relations']['mother'] ?? $elements[$el]['mother'];
    $avoid = $result['relations']['ky'] ?? $result['relations']['avoid'] ?? $elements[$el]['avoid'];
    $mother = is_string($mother) && isset($elements[$mother]) ? $mother : $elements[$el]['mother'];
    $avoid = is_string($avoid) && isset($elements[$avoid]) ? $avoid : $elements[$el]['avoid'];
    $canIdx = (int) ($result['can_index'] ?? $idx % 10);
    $chiIdx = (int) ($result['chi_index'] ?? $idx % 12);
    $canChi = $result['can_chi'] ?? ($can[$canIdx] . ' ' . $chi[$chiIdx]);
    $lunarYear = (int) ($result['lunar_year'] ?? 0);

    return [
        'idx' => $idx,
        'can' => $canIdx,
        'chi' => $chiIdx,
        'canChi' => $canChi,
        'lunarYear' => $lunarYear,
        'name' => $result['nap_am'] ?? $napRow[0],
        'han' => $napRow[2],
        'gloss' => $result['gloss'] ?? $napRow[3],
        'el' => $el,
        'elName' => $result['element_label'] ?? $elements[$el]['name'],
        'elHan' => $elements[$el]['han'],
        'advice' => $result['advice'] ?? $elements[$el]['advice'],
        'mother' => $mother,
        'motherName' => $elements[$mother]['name'],
        'motherHan' => $elements[$mother]['han'],
        'avoid' => $avoid,
        'avoidName' => $elements[$avoid]['name'],
        'avoidHan' => $elements[$avoid]['han'],
        // Service đã soạn note (sinh trước Tết, hoặc lời nhắc khi chỉ nhập năm).
        'note' => array_key_exists('note', $result) ? $result['note'] : (! empty($result['before_tet']) && $lunarYear
            ? 'Bạn sinh trước Tết ' . ($lunarYear + 1) . ' nên tính theo năm ' . $canChi . '.'
            : null),
    ];
};

// Mọi chữ Hán xuất hiện trên trang, để nạp Noto Serif TC theo tập con (&text=).
$hanText = implode('', array_merge($canHan, $chiHan, array_column($elements, 'han'), array_filter(array_column($nap, 2))));
$hanText = implode('', array_unique(mb_str_split($hanText)));

return [
    'elements' => $elements,
    'order' => $order,
    'can' => $can,
    'canHan' => $canHan,
    'chi' => $chi,
    'chiHan' => $chiHan,
    'nap' => $nap,
    'present' => $present,
    'hanText' => $hanText,
    'fontsHref' => 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500&display=swap',
    'hanFontsHref' => 'https://fonts.googleapis.com/css2?family=Noto+Serif+TC:wght@500;700&display=swap&text=' . rawurlencode($hanText),
];
