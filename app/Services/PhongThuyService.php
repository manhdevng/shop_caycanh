<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Tính mệnh Nạp Âm theo năm âm lịch và gợi ý cây đang bán hợp mệnh.
 * Không lưu ngày sinh ở bất kỳ đâu: mọi hàm chỉ nhận tham số và trả mảng.
 *
 * Dữ liệu cố định ở config/phong_thuy.php; đổi lịch ở LunarCalendar.
 */
class PhongThuyService
{
    public function __construct(private LunarCalendar $calendar) {}

    /**
     * Tra cứu theo đủ ngày/tháng/năm dương lịch, hoặc chỉ năm khi $day và
     * $month cùng null. Không kiểm tra hợp lệ ngày — việc đó thuộc FormRequest.
     */
    public function lookup(?int $day, ?int $month, int $year): array
    {
        if ($day === null && $month === null) {
            return $this->fromYear($year);
        }

        if ($day === null || $month === null) {
            throw new InvalidArgumentException('Ngày và tháng phải cùng có hoặc cùng trống.');
        }

        return $this->fromDate($day, $month, $year);
    }

    /**
     * Ngày dương lịch đầy đủ: đổi sang năm âm lịch để xử lý người sinh trước Tết.
     */
    public function fromDate(int $day, int $month, int $year): array
    {
        $lunarYear = $this->calendar->lunarYear($day, $month, $year);
        $result = $this->fromLunarYear($lunarYear);

        $result['before_tet'] = $lunarYear < $year;
        $result['year_only'] = false;
        $result['note'] = $result['before_tet']
            ? 'Bạn sinh trước Tết '.$year.' nên tính theo năm '.$result['can_chi'].'.'
            : null;

        return $result;
    }

    /**
     * Chỉ có năm: dùng luôn năm đó và kèm lời nhắc nhập đủ ngày cho người sinh gần Tết.
     */
    public function fromYear(int $year): array
    {
        $result = $this->fromLunarYear($year);
        $result['year_only'] = true;
        $result['note'] = config('phong_thuy.year_only_hint');

        return $result;
    }

    /**
     * Kết quả mệnh cho một năm âm lịch.
     *
     * @return array{lunar_year:int, can:string, chi:string, can_chi:string, nap_am:string,
     *     gloss:string, advice:string, element:string, element_label:string, before_tet:bool,
     *     year_only:bool, note:?string, relations:array{ban_menh:string, tuong_sinh:string, ky:string},
     *     can_index:int, chi_index:int, cycle_index:int}
     */
    public function fromLunarYear(int $lunarYear): array
    {
        $cycleIndex = (($lunarYear - 4) % 60 + 60) % 60;
        $canIndex = $cycleIndex % 10;
        $chiIndex = $cycleIndex % 12;

        [$napName, $element, $gloss] = config('phong_thuy.nap_am')[intdiv($cycleIndex, 2)];
        $can = config('phong_thuy.can')[$canIndex];
        $chi = config('phong_thuy.chi')[$chiIndex];

        return [
            'lunar_year' => $lunarYear,
            'can' => $can,
            'chi' => $chi,
            'can_chi' => $can.' '.$chi,
            'nap_am' => $napName,
            'gloss' => $gloss,
            'advice' => config("phong_thuy.elements.$element.advice"),
            'element' => $element,
            'element_label' => config("phong_thuy.elements.$element.label"),
            'before_tet' => false,
            'year_only' => false,
            'note' => null,
            'relations' => $this->relations($element),
            'can_index' => $canIndex,
            'chi_index' => $chiIndex,
            'cycle_index' => $cycleIndex,
        ];
    }

    /**
     * @return array{ban_menh:string, tuong_sinh:string, ky:string}
     */
    public function relations(string $element): array
    {
        $info = $this->elementInfo($element);

        return [
            'ban_menh' => $element,
            'tuong_sinh' => $info['sinh_ra_boi'],
            'ky' => $info['khac_boi'],
        ];
    }

    /**
     * Gợi ý hành cho cây theo tên, dựa trên config('phong_thuy.element_keywords').
     * Từ khóa phải khớp nguyên cụm (không dính chữ cái trước/sau), không phân
     * biệt hoa thường; chuẩn hóa Unicode NFC để chữ có dấu gõ kiểu tổ hợp vẫn khớp.
     *
     * Từ khóa dài/cụ thể thắng từ khóa ngắn nằm lồng trong nó: "cau vàng" (Thổ)
     * che "cau" (Mộc), "trầu bà đỏ" (Hỏa) che "trầu bà" (Mộc). Hai cụm KHÔNG
     * chồng lên nhau thì đều được tính, nên tên ghép vẫn có thể ra nhiều hành.
     *
     * @return array<string> mã hành theo thứ tự Product::ELEMENTS, rỗng nếu không khớp
     */
    public function suggestElements(string $name): array
    {
        $text = self::normalizeText($name);

        $matches = [];
        foreach (config('phong_thuy.element_keywords', []) as $element => $keywords) {
            foreach ($keywords as $keyword) {
                $pattern = '/(?<!\p{L})'.preg_quote(self::normalizeText($keyword), '/').'(?!\p{L})/u';
                if (preg_match_all($pattern, $text, $found, PREG_OFFSET_CAPTURE)) {
                    foreach ($found[0] as [$phrase, $offset]) {
                        $matches[] = ['element' => $element, 'start' => $offset, 'end' => $offset + strlen($phrase)];
                    }
                }
            }
        }

        // Dài trước; cụm nào đè lên cụm đã nhận thì bỏ.
        usort($matches, fn ($a, $b) => ($b['end'] - $b['start']) <=> ($a['end'] - $a['start']) ?: $a['start'] <=> $b['start']);
        $taken = [];
        $elements = [];
        foreach ($matches as $m) {
            foreach ($taken as [$start, $end]) {
                if ($m['start'] < $end && $start < $m['end']) {
                    continue 2;
                }
            }
            $taken[] = [$m['start'], $m['end']];
            $elements[] = $m['element'];
        }

        return array_values(array_intersect(array_keys(Product::ELEMENTS), $elements));
    }

    /**
     * Hành suy ra từ màu chủ đạo (config('phong_thuy.colors')). Mã màu lạ bị bỏ qua.
     *
     * @param  array<string>|null  $colors
     * @return array<string> mã hành theo thứ tự Product::ELEMENTS
     */
    public function elementsFromColors(?array $colors): array
    {
        $map = config('phong_thuy.colors', []);
        $elements = [];
        foreach ((array) $colors as $color) {
            if (isset($map[$color]['element'])) {
                $elements[] = $map[$color]['element'];
            }
        }

        return array_values(array_intersect(array_keys(Product::ELEMENTS), $elements));
    }

    /**
     * Gợi ý hành cho MỘT cây: có khai báo màu thì theo màu, chưa có thì đoán
     * theo tên. Dùng chung cho lệnh artisan, seeder, gán hàng loạt ở admin và
     * lưới an toàn của recommend().
     *
     * @return array<string>
     */
    public function suggestForProduct(Product $product): array
    {
        $byColor = $this->elementsFromColors($product->feng_shui_colors);

        return $byColor ?: $this->suggestElements($product->name);
    }

    /**
     * 'color' khi gợi ý đến từ màu, 'name' khi từ tên, null khi không có gợi ý.
     */
    public function suggestionSource(Product $product): ?string
    {
        if ($this->elementsFromColors($product->feng_shui_colors)) {
            return 'color';
        }

        return $this->suggestElements($product->name) ? 'name' : null;
    }

    private static function normalizeText(string $text): string
    {
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        }

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text)));
    }

    /**
     * Cây đang bán hợp mệnh $element, chia ba nhóm không trùng nhau:
     *  - ban_menh: có hành trùng mệnh;
     *  - tuong_sinh: có hành sinh ra mệnh (và không thuộc bản mệnh);
     *  - trung_tinh: đã gán hành, không hợp và không có hành khắc. Chỉ bù khi
     *    hai nhóm hợp chưa đủ few_threshold cây.
     * Cây có cả hành hợp lẫn hành khắc vẫn vào nhóm hợp. Tổng ba nhóm không vượt $limit.
     *
     * Cây chưa gán hành chỉ dùng làm lưới an toàn: bù trung tính xong mà vẫn
     * dưới few_threshold thì lấy thêm cây chưa gán có suggestForProduct() ra hành
     * hợp (tính tại chỗ, không ghi CSDL) xếp vào ban_menh/tuong_sinh, để trang
     * không trống khi admin chưa kịp gán hành.
     *
     * @return array{ban_menh:Collection<int,Product>, tuong_sinh:Collection<int,Product>, trung_tinh:Collection<int,Product>}
     */
    public function recommend(string $element, int $limit = 12): array
    {
        $relations = $this->relations($element);
        $threshold = (int) config('phong_thuy.few_threshold', 4);

        $groups = ['ban_menh' => collect(), 'tuong_sinh' => collect(), 'trung_tinh' => collect()];
        $unassigned = collect();

        foreach ($this->candidates()->get() as $product) {
            $codes = $product->elementCodes();

            if ($codes === []) {
                $unassigned->push($product);
            } elseif (in_array($relations['ban_menh'], $codes, true)) {
                $groups['ban_menh']->push($product);
            } elseif (in_array($relations['tuong_sinh'], $codes, true)) {
                $groups['tuong_sinh']->push($product);
            } elseif (! in_array($relations['ky'], $codes, true)) {
                $groups['trung_tinh']->push($product);
            }
        }

        $limit = max(0, $limit);
        $banMenh = $groups['ban_menh']->take($limit)->values();
        $tuongSinh = $groups['tuong_sinh']->take($limit - $banMenh->count())->values();

        $harmonious = $banMenh->count() + $tuongSinh->count();
        $fill = min(max(0, $threshold - $harmonious), $limit - $harmonious);
        $trungTinh = $groups['trung_tinh']->take(max(0, $fill))->values();

        $missing = min($threshold, $limit) - ($harmonious + $trungTinh->count());
        foreach ($unassigned as $product) {
            if ($missing <= 0) {
                break;
            }
            $codes = $this->suggestForProduct($product);
            if (in_array($relations['ban_menh'], $codes, true)) {
                $banMenh->push($product);
            } elseif (in_array($relations['tuong_sinh'], $codes, true)) {
                $tuongSinh->push($product);
            } else {
                continue;
            }
            $missing--;
        }

        return ['ban_menh' => $banMenh, 'tuong_sinh' => $tuongSinh, 'trung_tinh' => $trungTinh];
    }

    /**
     * True khi hai nhóm hợp (bản mệnh + tương sinh) có dưới few_threshold cây,
     * kể cả khi đã bù trung tính — dùng để hiện form nhận tin.
     */
    public function isFew(array $recommendations): bool
    {
        $harmonious = $recommendations['ban_menh']->count() + $recommendations['tuong_sinh']->count();

        return $harmonious < (int) config('phong_thuy.few_threshold', 4);
    }

    /**
     * Cây đang bán, còn hàng, mua được (giá gốc > 0 hoặc có phân loại giá > 0
     * — base_price của sản phẩm có phân loại là MIN giá phân loại). Gồm cả cây
     * chưa gán hành — recommend() tự tách nhóm đó ra.
     *
     * "Còn hàng" = products.stock > 0, hoặc một phân loại còn tồn nếu bảng
     * product_variants có cột stock (cột này đã bị bỏ ở migration
     * 2026_08_20_014303; tồn kho hiện chỉ nằm ở cấp sản phẩm).
     */
    private function candidates(): Builder
    {
        $variantStock = self::variantsHaveStock();

        return Product::query()
            ->plants()
            ->where('is_active', true)
            ->where(function (Builder $q) use ($variantStock) {
                $q->where('stock', '>', 0)
                    ->when($variantStock, fn (Builder $q) => $q->orWhereHas('variants', fn (Builder $v) => $v->where('stock', '>', 0)));
            })
            ->where(function (Builder $q) {
                $q->where('base_price', '>', 0)
                    ->orWhereHas('variants', fn (Builder $v) => $v->where('price', '>', 0));
            })
            ->with(['elements', 'variants'])
            ->latest('id');
    }

    private static function variantsHaveStock(): bool
    {
        static $has = null;

        return $has ??= Schema::hasColumn('product_variants', 'stock');
    }

    private function elementInfo(string $element): array
    {
        $info = config("phong_thuy.elements.$element");
        if (! is_array($info)) {
            throw new InvalidArgumentException("Hành không hợp lệ: $element");
        }

        return $info;
    }
}
