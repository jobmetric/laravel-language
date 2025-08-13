<?php

namespace JobMetric\Language;

use IntlCalendar;
use IntlTimeZone;
use InvalidArgumentException;

/**
 * Class DateConverter
 *
 * High-accuracy conversion utilities between Gregorian dates and several
 * internationally recognized calendar systems via PHP's Intl/ICU:
 *
 * - islamic (Hijri, lunar)
 * - hebrew (lunisolar)
 * - buddhist (Thai/B.E.)
 * - coptic (solar, 13 months)
 * - ethiopic/ethiopian (solar, 13 months)
 * - chinese (lunisolar; supports leap months)
 * - persian/jalali (solar) — routed through ICU here, though you may keep/compare with custom algorithms
 *
 * Implementation notes
 * --------------------
 * • ICU months are zero-based (0..11). Public API here uses 1..12 and converts internally.
 * • All conversions are performed at UTC midnight to avoid DST/zone drift.
 * • Some calendars (e.g., Chinese/Dangi) require using FIELD_EXTENDED_YEAR rather than FIELD_YEAR.
 * • Chinese calendar may contain leap months. This class caches leap-month information when converting
 *   Gregorian→Chinese and uses a dual-candidate strategy for Chinese→Gregorian to preserve round-trip accuracy
 *   without changing public method signatures.
 *
 * Return format
 * -------------
 * Each conversion method returns either:
 *   - array<int,int>: [Y, M, D]
 *   - string: formatted "YYYY{mod}MM{mod}DD" if $mod is a non-empty delimiter
 *
 * Exceptions
 * ----------
 * • InvalidArgumentException is thrown if the intl extension is missing or an unsupported calendar is requested.
 *
 * @package JobMetric\Language
 */
class DateConverter
{
    /** @var array<string,bool> Cache of Chinese leap-month flags keyed by "cy-cm-cd". */
    private array $chineseLeapCache = [];

    /**
     * Generic calendar-to-calendar conversion using ICU/Intl.
     *
     * @param string $fromCal  Source calendar keyword. Supported aliases:
     *                         gregorian, jalali|persian, hijri|islamic, hebrew,
     *                         buddhist, coptic, ethiopian|ethiopic, chinese, dangi
     * @param int    $y        Year (1-based, public API)
     * @param int    $m        Month 1..12 (public API)
     * @param int    $d        Day 1..31
     * @param string $toCal    Target calendar keyword (same set as $fromCal)
     * @param string $mod      Optional delimiter to return a formatted string "YYYY{mod}MM{mod}DD"
     *
     * @return array{0:int,1:int,2:int}|string
     *
     * @throws InvalidArgumentException If intl extension is not loaded or calendars are unsupported.
     */
    private function convertCalendar(
        string $fromCal,
        int $y,
        int $m,
        int $d,
        string $toCal,
        string $mod = ''
    ): array|string {
        if (!extension_loaded('intl')) {
            throw new InvalidArgumentException('The "intl" PHP extension is required for multi-calendar conversions.');
        }

        $map = [
            'gregorian' => 'gregorian',
            'jalali'    => 'persian',
            'persian'   => 'persian',
            'hijri'     => 'islamic',
            'islamic'   => 'islamic',
            'hebrew'    => 'hebrew',
            'buddhist'  => 'buddhist',
            'coptic'    => 'coptic',
            'ethiopian' => 'ethiopic',
            'ethiopic'  => 'ethiopic',
            'chinese'   => 'chinese',
            'dangi'     => 'dangi',
        ];

        $from = $map[strtolower($fromCal)] ?? null;
        $to   = $map[strtolower($toCal)]   ?? null;
        if (!$from || !$to) {
            throw new InvalidArgumentException("Unsupported calendar. From='{$fromCal}' To='{$toCal}'");
        }

        $tz = IntlTimeZone::getGMT();

        // Chinese/Dangi use extended years in ICU.
        $useExtendedYearFrom = in_array($from, ['chinese', 'dangi'], true) && defined('\IntlCalendar::FIELD_EXTENDED_YEAR');
        $useExtendedYearTo   = in_array($to,   ['chinese', 'dangi'], true) && defined('\IntlCalendar::FIELD_EXTENDED_YEAR');

        $YEAR_FROM = $useExtendedYearFrom ? IntlCalendar::FIELD_EXTENDED_YEAR : IntlCalendar::FIELD_YEAR;
        $YEAR_TO   = $useExtendedYearTo   ? IntlCalendar::FIELD_EXTENDED_YEAR : IntlCalendar::FIELD_YEAR;

        // Build source calendar at UTC midnight
        $fromCalendar = IntlCalendar::createInstance($tz, "@calendar={$from}");
        if (!$fromCalendar) {
            throw new InvalidArgumentException("Cannot create source calendar '{$from}'.");
        }
        $fromCalendar->clear();
        $fromCalendar->set($YEAR_FROM, $y);
        $fromCalendar->set(IntlCalendar::FIELD_MONTH, $m - 1);
        $fromCalendar->set(IntlCalendar::FIELD_DAY_OF_MONTH, $d);
        $fromCalendar->set(IntlCalendar::FIELD_HOUR_OF_DAY, 0);
        $fromCalendar->set(IntlCalendar::FIELD_MINUTE, 0);
        $fromCalendar->set(IntlCalendar::FIELD_SECOND, 0);
        $fromCalendar->set(IntlCalendar::FIELD_MILLISECOND, 0);

        $millis = $fromCalendar->getTime();

        // Build target calendar
        $toCalendar = IntlCalendar::createInstance($tz, "@calendar={$to}");
        if (!$toCalendar) {
            throw new InvalidArgumentException("Cannot create target calendar '{$to}'.");
        }
        $toCalendar->setTime($millis);

        $ty = $toCalendar->get($YEAR_TO);
        $tm = $toCalendar->get(IntlCalendar::FIELD_MONTH) + 1;
        $td = $toCalendar->get(IntlCalendar::FIELD_DAY_OF_MONTH);

        return $mod === '' ? [$ty, $tm, $td] : sprintf('%04d%s%02d%s%02d', $ty, $mod, $tm, $mod, $td);
    }

    /**
     * Convert Gregorian → Islamic (Hijri).
     *
     * @param int    $gy
     * @param int    $gm
     * @param int    $gd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function gregorianToHijri(int $gy, int $gm, int $gd, string $mod = ''): array|string
    {
        return $this->convertCalendar('gregorian', $gy, $gm, $gd, 'islamic', $mod);
    }

    /**
     * Convert Islamic (Hijri) → Gregorian.
     *
     * @param int    $hy
     * @param int    $hm
     * @param int    $hd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function hijriToGregorian(int $hy, int $hm, int $hd, string $mod = ''): array|string
    {
        return $this->convertCalendar('islamic', $hy, $hm, $hd, 'gregorian', $mod);
    }

    /**
     * Convert Gregorian → Hebrew (lunisolar).
     *
     * @param int    $gy
     * @param int    $gm
     * @param int    $gd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function gregorianToHebrew(int $gy, int $gm, int $gd, string $mod = ''): array|string
    {
        return $this->convertCalendar('gregorian', $gy, $gm, $gd, 'hebrew', $mod);
    }

    /**
     * Convert Hebrew (lunisolar) → Gregorian.
     *
     * @param int    $hy
     * @param int    $hm
     * @param int    $hd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function hebrewToGregorian(int $hy, int $hm, int $hd, string $mod = ''): array|string
    {
        return $this->convertCalendar('hebrew', $hy, $hm, $hd, 'gregorian', $mod);
    }

    /**
     * Convert Gregorian → Buddhist (B.E.).
     * Uses ICU to avoid edge-case errors around leap years compared to naive +543 logic.
     *
     * @param int    $gy
     * @param int    $gm
     * @param int    $gd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function gregorianToBuddhist(int $gy, int $gm, int $gd, string $mod = ''): array|string
    {
        return $this->convertCalendar('gregorian', $gy, $gm, $gd, 'buddhist', $mod);
    }

    /**
     * Convert Buddhist (B.E.) → Gregorian.
     *
     * @param int    $by
     * @param int    $bm
     * @param int    $bd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function buddhistToGregorian(int $by, int $bm, int $bd, string $mod = ''): array|string
    {
        return $this->convertCalendar('buddhist', $by, $bm, $bd, 'gregorian', $mod);
    }

    /**
     * Convert Gregorian → Coptic (solar, 13 months).
     *
     * @param int    $gy
     * @param int    $gm
     * @param int    $gd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function gregorianToCoptic(int $gy, int $gm, int $gd, string $mod = ''): array|string
    {
        return $this->convertCalendar('gregorian', $gy, $gm, $gd, 'coptic', $mod);
    }

    /**
     * Convert Coptic (solar, 13 months) → Gregorian.
     *
     * @param int    $cy
     * @param int    $cm
     * @param int    $cd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function copticToGregorian(int $cy, int $cm, int $cd, string $mod = ''): array|string
    {
        return $this->convertCalendar('coptic', $cy, $cm, $cd, 'gregorian', $mod);
    }

    /**
     * Convert Gregorian → Ethiopian/Ethiopic (solar, 13 months).
     *
     * @param int    $gy
     * @param int    $gm
     * @param int    $gd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function gregorianToEthiopian(int $gy, int $gm, int $gd, string $mod = ''): array|string
    {
        return $this->convertCalendar('gregorian', $gy, $gm, $gd, 'ethiopic', $mod);
    }

    /**
     * Convert Ethiopian/Ethiopic (solar, 13 months) → Gregorian.
     *
     * @param int    $ey
     * @param int    $em
     * @param int    $ed
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function ethiopianToGregorian(int $ey, int $em, int $ed, string $mod = ''): array|string
    {
        return $this->convertCalendar('ethiopic', $ey, $em, $ed, 'gregorian', $mod);
    }

    /**
     * Convert Gregorian → Chinese (lunisolar).
     * Stores leap-month info internally to preserve round-trip behavior in subsequent Chinese→Gregorian calls.
     *
     * @param int    $gy
     * @param int    $gm
     * @param int    $gd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     *
     * @throws InvalidArgumentException If intl extension is not available.
     */
    public function gregorianToChinese(int $gy, int $gm, int $gd, string $mod = ''): array|string
    {
        if (!extension_loaded('intl')) {
            throw new InvalidArgumentException('The "intl" extension is required.');
        }

        $tz = IntlTimeZone::getGMT();

        // Build Gregorian at UTC midnight
        $gcal = IntlCalendar::createInstance($tz, '@calendar=gregorian');
        $gcal->clear();
        $gcal->set(IntlCalendar::FIELD_YEAR, $gy);
        $gcal->set(IntlCalendar::FIELD_MONTH, $gm - 1);
        $gcal->set(IntlCalendar::FIELD_DAY_OF_MONTH, $gd);
        $gcal->set(IntlCalendar::FIELD_HOUR_OF_DAY, 0);
        $gcal->set(IntlCalendar::FIELD_MINUTE, 0);
        $gcal->set(IntlCalendar::FIELD_SECOND, 0);
        $gcal->set(IntlCalendar::FIELD_MILLISECOND, 0);

        $millis = $gcal->getTime();

        // Convert to Chinese calendar
        $ccal = IntlCalendar::createInstance($tz, '@calendar=chinese');
        $ccal->setTime($millis);

        $YEAR = \defined('\IntlCalendar::FIELD_EXTENDED_YEAR') ? IntlCalendar::FIELD_EXTENDED_YEAR : IntlCalendar::FIELD_YEAR;
        $cy = $ccal->get($YEAR);
        $cm = $ccal->get(IntlCalendar::FIELD_MONTH) + 1;
        $cd = $ccal->get(IntlCalendar::FIELD_DAY_OF_MONTH);

        $isLeap = \defined('\IntlCalendar::FIELD_IS_LEAP_MONTH')
            ? (bool) $ccal->get(IntlCalendar::FIELD_IS_LEAP_MONTH)
            : false;

        // Cache leap flag for this Chinese date
        $this->chineseLeapCache["{$cy}-{$cm}-{$cd}"] = $isLeap;

        return $mod === '' ? [$cy, $cm, $cd] : sprintf('%04d%s%02d%s%02d', $cy, $mod, $cm, $mod, $cd);
    }

    /**
     * Convert Chinese (lunisolar) → Gregorian.
     * Tries both normal and leap candidates (when supported) and selects the one consistent with ICU
     * and cached leap information, preserving round-trip behavior.
     *
     * @param int    $cy  Chinese year (ICU extended-year semantics are handled internally)
     * @param int    $cm  Chinese month 1..12 (leap handled internally if supported)
     * @param int    $cd  Chinese day 1..30
     * @param string $mod Optional delimiter for formatted result
     * @return array{0:int,1:int,2:int}|string
     *
     * @throws InvalidArgumentException If intl extension is not available.
     */
    public function chineseToGregorian(int $cy, int $cm, int $cd, string $mod = ''): array|string
    {
        if (!extension_loaded('intl')) {
            throw new InvalidArgumentException('The "intl" extension is required.');
        }

        $tz   = IntlTimeZone::getGMT();
        $YEAR = \defined('\IntlCalendar::FIELD_EXTENDED_YEAR') ? IntlCalendar::FIELD_EXTENDED_YEAR : IntlCalendar::FIELD_YEAR;

        $toGregorian = function (bool $isLeap) use ($tz, $YEAR, $cy, $cm, $cd): array {
            $from = IntlCalendar::createInstance($tz, '@calendar=chinese');
            if (!$from) {
                throw new InvalidArgumentException('Cannot create Chinese calendar.');
            }
            $from->clear();
            $from->set($YEAR, $cy);
            $from->set(IntlCalendar::FIELD_MONTH, $cm - 1);
            $from->set(IntlCalendar::FIELD_DAY_OF_MONTH, $cd);
            $from->set(IntlCalendar::FIELD_HOUR_OF_DAY, 0);
            $from->set(IntlCalendar::FIELD_MINUTE, 0);
            $from->set(IntlCalendar::FIELD_SECOND, 0);
            $from->set(IntlCalendar::FIELD_MILLISECOND, 0);

            if (\defined('\IntlCalendar::FIELD_IS_LEAP_MONTH')) {
                $from->set(IntlCalendar::FIELD_IS_LEAP_MONTH, $isLeap ? 1 : 0);
            }

            $millis = $from->getTime();

            $to = IntlCalendar::createInstance($tz, '@calendar=gregorian');
            $to->setTime($millis);

            $gy = $to->get(IntlCalendar::FIELD_YEAR);
            $gm = $to->get(IntlCalendar::FIELD_MONTH) + 1;
            $gd = $to->get(IntlCalendar::FIELD_DAY_OF_MONTH);

            return [$gy, $gm, $gd];
        };

        // Build both candidates (normal/leap) and decide which to use.
        [$gy1, $gm1, $gd1] = $toGregorian(false);
        [$gy2, $gm2, $gd2] = $toGregorian(true);

        $key = "{$cy}-{$cm}-{$cd}";
        if (isset($this->chineseLeapCache[$key])) {
            $preferLeap = $this->chineseLeapCache[$key] === true;
            [$gyp, $gmp, $gdp] = $preferLeap ? [$gy2, $gm2, $gd2] : [$gy1, $gm1, $gd1];
            return $mod === '' ? [$gyp, $gmp, $gdp] : sprintf('%04d%s%02d%s%02d', $gyp, $mod, $gmp, $mod, $gdp);
        }

        // No cache available: probe leapness via round-trip from the Gregorian candidates.
        $leapOf = function (int $gy, int $gm, int $gd) use ($tz): bool {
            $g = IntlCalendar::createInstance($tz, '@calendar=gregorian');
            $g->clear();
            $g->set(IntlCalendar::FIELD_YEAR, $gy);
            $g->set(IntlCalendar::FIELD_MONTH, $gm - 1);
            $g->set(IntlCalendar::FIELD_DAY_OF_MONTH, $gd);
            $g->set(IntlCalendar::FIELD_HOUR_OF_DAY, 0);
            $g->set(IntlCalendar::FIELD_MINUTE, 0);
            $g->set(IntlCalendar::FIELD_SECOND, 0);
            $g->set(IntlCalendar::FIELD_MILLISECOND, 0);

            $millis = $g->getTime();

            $c = IntlCalendar::createInstance($tz, '@calendar=chinese');
            $c->setTime($millis);

            return \defined('\IntlCalendar::FIELD_IS_LEAP_MONTH')
                ? (bool) $c->get(IntlCalendar::FIELD_IS_LEAP_MONTH)
                : false;
        };

        $leap1 = $leapOf($gy1, $gm1, $gd1);
        $leap2 = $leapOf($gy2, $gm2, $gd2);

        // If only one candidate is leap, prefer it; otherwise choose the later Gregorian date (ambiguous months).
        $choice = null;
        if ($leap1 !== $leap2) {
            $choice = $leap1 ? [$gy1, $gm1, $gd1] : [$gy2, $gm2, $gd2];
        } else {
            $dt1 = $gy1 * 10000 + $gm1 * 100 + $gd1;
            $dt2 = $gy2 * 10000 + $gm2 * 100 + $gd2;
            $choice = ($dt2 > $dt1) ? [$gy2, $gm2, $gd2] : [$gy1, $gm1, $gd1];
        }

        return $mod === '' ? $choice : sprintf('%04d%s%02d%s%02d', $choice[0], $mod, $choice[1], $mod, $choice[2]);
    }

    /**
     * Convert Gregorian → Persian (Jalali) using ICU.
     * Note: You may keep/customize a handcrafted algorithm for Jalali if required.
     *
     * @param int    $gy
     * @param int    $gm
     * @param int    $gd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function gregorianToJalali(int $gy, int $gm, int $gd, string $mod = ''): array|string
    {
        return $this->convertCalendar('gregorian', $gy, $gm, $gd, 'persian', $mod);
    }

    /**
     * Convert Persian (Jalali) → Gregorian using ICU.
     *
     * @param int    $jy
     * @param int    $jm
     * @param int    $jd
     * @param string $mod
     * @return array{0:int,1:int,2:int}|string
     */
    public function jalaliToGregorian(int $jy, int $jm, int $jd, string $mod = ''): array|string
    {
        return $this->convertCalendar('persian', $jy, $jm, $jd, 'gregorian', $mod);
    }

    /**
     * Translate digits and punctuation between English and Persian/Arabic forms.
     *
     * Behavior:
     *  - $mod === 'fa': ASCII digits and punctuation → Persian digits and Persian/Arabic punctuation.
     *  - otherwise: Persian and Arabic-Indic digits/punctuation → ASCII digits and punctuation.
     *
     * @param string $str Input string
     * @param string $mod 'fa' to localize to Persian digits; anything else to normalize to ASCII
     * @param string $mf  Decimal separator to use when converting to Persian (default '٫')
     * @return string
     */
    public function trNum(string $str, string $mod = 'en', string $mf = '٫'): string
    {
        if ($mod === 'fa') {
            return strtr($str, [
                '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
                '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
                '.' => $mf, ',' => '٬',
            ]);
        }

        return strtr($str, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٫' => '.', '٬' => ',', '،' => ',',
        ]);
    }
}
