<?php

namespace JobMetric\Language;

class DateConverter
{
    public function gregorianToJalali(int $gy, int $gm, int $gd, string $mod = ''): array|string
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $jm = $days < 186 ? 1 + intdiv($days, 31) : 7 + intdiv($days - 186, 30);
        $jd = 1 + ($days < 186 ? $days % 31 : ($days - 186) % 30);

        return $mod === '' ? [$jy, $jm, $jd] : sprintf('%04d%s%02d%s%02d', $jy, $mod, $jm, $mod, $jd);
    }

    public function jalaliToGregorian(int $jy, int $jm, int $jd, string $mod = ''): array|string
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd;
        $days += ($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186;

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) $days++;
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $months = [31, ($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        for ($gm = 1; $gm <= 12; $gm++) {
            if ($gd <= $months[$gm - 1]) break;
            $gd -= $months[$gm - 1];
        }

        return $mod === '' ? [$gy, $gm, $gd] : sprintf('%04d%s%02d%s%02d', $gy, $mod, $gm, $mod, $gd);
    }

    public function trNum(string $str, string $mod = 'en', string $mf = '٫'): string
    {
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', $mf];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'];

        return $mod === 'fa' ? str_replace($englishNumbers, $persianNumbers, $str) : str_replace($persianNumbers, $englishNumbers, $str);
    }
}
