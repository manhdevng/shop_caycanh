<?php

namespace App\Services;

/**
 * Đổi ngày dương lịch sang âm lịch Việt Nam theo thuật toán của Hồ Ngọc Đức
 * (port từ solar2lunar trong design/cay-hop-menh/project/ngu-hanh-v2.js).
 * Múi giờ mặc định +7. Mọi phép làm tròn dùng floor() để khớp Math.floor
 * của bản JS, kể cả với số âm.
 */
class LunarCalendar
{
    private const TZ = 7.0;

    /**
     * @return array{day:int, month:int, year:int, leap:bool}
     */
    public function solarToLunar(int $dd, int $mm, int $yy, float $tz = self::TZ): array
    {
        $dayNumber = $this->jdFromDate($dd, $mm, $yy);
        $k = $this->int(($dayNumber - 2415021.076998695) / 29.530588853);

        $monthStart = $this->newMoon($k + 1, $tz);
        if ($monthStart > $dayNumber) {
            $monthStart = $this->newMoon($k, $tz);
        }

        $a11 = $this->lunarMonth11($yy, $tz);
        $b11 = $a11;
        if ($a11 >= $monthStart) {
            $lunarYear = $yy;
            $a11 = $this->lunarMonth11($yy - 1, $tz);
        } else {
            $lunarYear = $yy + 1;
            $b11 = $this->lunarMonth11($yy + 1, $tz);
        }

        $lunarDay = $dayNumber - $monthStart + 1;
        $diff = $this->int(($monthStart - $a11) / 29);
        $leap = false;
        $lunarMonth = $diff + 11;

        if ($b11 - $a11 > 365) {
            $leapMonthDiff = $this->leapMonthOffset($a11, $tz);
            if ($diff >= $leapMonthDiff) {
                $lunarMonth = $diff + 10;
                if ($diff === $leapMonthDiff) {
                    $leap = true;
                }
            }
        }

        if ($lunarMonth > 12) {
            $lunarMonth -= 12;
        }
        if ($lunarMonth >= 11 && $diff < 4) {
            $lunarYear -= 1;
        }

        return ['day' => $lunarDay, 'month' => $lunarMonth, 'year' => $lunarYear, 'leap' => $leap];
    }

    /**
     * Năm âm lịch chứa ngày dương $dd/$mm/$yy.
     */
    public function lunarYear(int $dd, int $mm, int $yy): int
    {
        return $this->solarToLunar($dd, $mm, $yy)['year'];
    }

    private function int(float $d): int
    {
        return (int) floor($d);
    }

    public function jdFromDate(int $dd, int $mm, int $yy): int
    {
        $a = $this->int((14 - $mm) / 12);
        $y = $yy + 4800 - $a;
        $m = $mm + 12 * $a - 3;
        $jd = $dd + $this->int((153 * $m + 2) / 5) + 365 * $y + $this->int($y / 4)
            - $this->int($y / 100) + $this->int($y / 400) - 32045;
        if ($jd < 2299161) {
            $jd = $dd + $this->int((153 * $m + 2) / 5) + 365 * $y + $this->int($y / 4) - 32083;
        }

        return $jd;
    }

    private function newMoon(int $k, float $tz): int
    {
        $T = $k / 1236.85;
        $T2 = $T * $T;
        $T3 = $T2 * $T;
        $dr = M_PI / 180;

        $jd1 = 2415020.75933 + 29.53058868 * $k + 0.0001178 * $T2 - 0.000000155 * $T3;
        $jd1 += 0.00033 * sin((166.56 + 132.87 * $T - 0.009173 * $T2) * $dr);
        $M = 359.2242 + 29.10535608 * $k - 0.0000333 * $T2 - 0.00000347 * $T3;
        $Mpr = 306.0253 + 385.81691806 * $k + 0.0107306 * $T2 + 0.00001236 * $T3;
        $F = 21.2964 + 390.67050646 * $k - 0.0016528 * $T2 - 0.00000239 * $T3;

        $C1 = (0.1734 - 0.000393 * $T) * sin($M * $dr) + 0.0021 * sin(2 * $dr * $M);
        $C1 = $C1 - 0.4068 * sin($Mpr * $dr) + 0.0161 * sin($dr * 2 * $Mpr);
        $C1 = $C1 - 0.0004 * sin($dr * 3 * $Mpr);
        $C1 = $C1 + 0.0104 * sin($dr * 2 * $F) - 0.0051 * sin($dr * ($M + $Mpr));
        $C1 = $C1 - 0.0074 * sin($dr * ($M - $Mpr)) + 0.0004 * sin($dr * (2 * $F + $M));
        $C1 = $C1 - 0.0004 * sin($dr * (2 * $F - $M)) - 0.0006 * sin($dr * (2 * $F + $Mpr));
        $C1 = $C1 + 0.0010 * sin($dr * (2 * $F - $Mpr)) + 0.0005 * sin($dr * (2 * $Mpr + $M));

        $deltaT = $T < -11
            ? 0.001 + 0.000839 * $T + 0.0002261 * $T2 - 0.00000845 * $T3 - 0.000000081 * $T * $T3
            : -0.000278 + 0.000265 * $T + 0.000262 * $T2;

        return $this->int($jd1 + $C1 - $deltaT + 0.5 + $tz / 24);
    }

    private function sunLongitude(int $jdn, float $tz): int
    {
        $T = ($jdn - 2451545.5 - $tz / 24) / 36525;
        $T2 = $T * $T;
        $dr = M_PI / 180;

        $M = 357.52910 + 35999.05030 * $T - 0.0001559 * $T2 - 0.00000048 * $T * $T2;
        $L0 = 280.46645 + 36000.76983 * $T + 0.0003032 * $T2;
        $DL = (1.914600 - 0.004817 * $T - 0.000014 * $T2) * sin($dr * $M);
        $DL += (0.019993 - 0.000101 * $T) * sin($dr * 2 * $M) + 0.000290 * sin($dr * 3 * $M);

        $L = ($L0 + $DL) * $dr;
        $L = $L - M_PI * 2 * $this->int($L / (M_PI * 2));

        return $this->int($L / M_PI * 6);
    }

    private function lunarMonth11(int $yy, float $tz): int
    {
        $off = $this->jdFromDate(31, 12, $yy) - 2415021;
        $k = $this->int($off / 29.530588853);
        $nm = $this->newMoon($k, $tz);
        if ($this->sunLongitude($nm, $tz) >= 9) {
            $nm = $this->newMoon($k - 1, $tz);
        }

        return $nm;
    }

    private function leapMonthOffset(int $a11, float $tz): int
    {
        $k = $this->int(($a11 - 2415021.076998695) / 29.530588853 + 0.5);
        $i = 1;
        $arc = $this->sunLongitude($this->newMoon($k + $i, $tz), $tz);
        do {
            $last = $arc;
            $i++;
            $arc = $this->sunLongitude($this->newMoon($k + $i, $tz), $tz);
        } while ($arc !== $last && $i < 14);

        return $i - 1;
    }
}
