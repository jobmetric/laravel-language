<?php

namespace JobMetric\Language\Tests\Feature;

use PHPUnit\Framework\TestCase;
use JobMetric\Language\DateConverter;

class DateConverterTest extends TestCase
{
    private DateConverter $conv;

    protected function setUp(): void
    {
        $this->conv = new DateConverter();
    }

    /** ---------------- Jalali <-> Gregorian (exact pairs) ---------------- */

    public static function jalaliToGregorianProvider(): array
    {
        return [
            // [jy, jm, jd, gy, gm, gd]
            [1404, 5, 22, 2025, 8, 13], // reference date
            [1399, 12, 30, 2021, 3, 20], // end of leap year in Jalali
            [1400, 1, 1, 2021, 3, 21],   // Nowruz
            [1402, 12, 29, 2024, 3, 19], // end of year
        ];
    }

    /**
     * @dataProvider jalaliToGregorianProvider
     */
    public function testJalaliToGregorianExact(int $jy, int $jm, int $jd, int $gy, int $gm, int $gd): void
    {
        $this->assertSame([$gy, $gm, $gd], $this->conv->jalaliToGregorian($jy, $jm, $jd));
        $this->assertSame(sprintf('%04d-%02d-%02d', $gy, $gm, $gd), $this->conv->jalaliToGregorian($jy, $jm, $jd, '-'));
    }

    /**
     * @dataProvider jalaliToGregorianProvider
     */
    public function testGregorianToJalaliExact(int $jy, int $jm, int $jd, int $gy, int $gm, int $gd): void
    {
        $this->assertSame([$jy, $jm, $jd], $this->conv->gregorianToJalali($gy, $gm, $gd));
        $this->assertSame(sprintf('%04d/%02d/%02d', $jy, $jm, $jd), $this->conv->gregorianToJalali($gy, $gm, $gd, '/'));
    }

    /** Add Jalali round-trip stability across edge dates */
    public static function jalaliEdgeDatesProvider(): array
    {
        return [
            [1398, 12, 29],
            [1399, 12, 30],
            [1400, 1, 1],
            [1401, 12, 29],
            [1402, 1, 1],
            [1403, 12, 29],
            [1404, 5, 22],
        ];
    }

    /**
     * @dataProvider jalaliEdgeDatesProvider
     */
    public function testRoundTripJalali(int $jy, int $jm, int $jd): void
    {
        $g = $this->conv->jalaliToGregorian($jy, $jm, $jd);
        $j = $this->conv->gregorianToJalali(...$g);
        $this->assertSame([$jy, $jm, $jd], $j);
    }

    /** ---------------- ICU calendars: helpers ---------------- */

    private function requireIntlOrSkip(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension is not available; skipping ICU calendar tests.');
        }
    }

    /** A broad set of Gregorian edge dates for stress testing. */
    public static function gregorianEdgeDatesProvider(): array
    {
        return [
            [1600, 3, 1],   // leap century in proleptic Gregorian
            [1700, 3, 1],   // non-leap century
            [1800, 3, 1],   // non-leap century
            [1899, 12, 31], // year end
            [1900, 2, 28],  // non-leap century edge
            [1900, 3, 1],
            [1969, 12, 31],
            [1970, 1, 1],   // epoch
            [1999, 12, 31],
            [2000, 2, 29],  // leap year (century divisible by 400)
            [2004, 2, 29],  // leap year
            [2016, 2, 29],  // leap year
            [2019, 12, 31],
            [2020, 2, 29],  // leap year
            [2024, 2, 29],  // leap year
            [2025, 8, 13],  // reference
            [2032, 2, 29],  // future leap
        ];
    }

    /** ---------------- ICU calendars Round-trip (per calendar) ---------------- */

    /**
     * @dataProvider gregorianEdgeDatesProvider
     */
    public function testRoundTripHijri(int $y, int $m, int $d): void
    {
        $this->requireIntlOrSkip();
        $to = $this->conv->gregorianToHijri($y, $m, $d);
        $back = $this->conv->hijriToGregorian(...$to);
        $this->assertSame([$y, $m, $d], $back);
    }

    /**
     * @dataProvider gregorianEdgeDatesProvider
     */
    public function testRoundTripHebrew(int $y, int $m, int $d): void
    {
        $this->requireIntlOrSkip();
        $to = $this->conv->gregorianToHebrew($y, $m, $d);
        $back = $this->conv->hebrewToGregorian(...$to);
        $this->assertSame([$y, $m, $d], $back);
    }

    /**
     * @dataProvider gregorianEdgeDatesProvider
     */
    public function testRoundTripBuddhist(int $y, int $m, int $d): void
    {
        $this->requireIntlOrSkip();
        $to = $this->conv->gregorianToBuddhist($y, $m, $d);
        $back = $this->conv->buddhistToGregorian(...$to);
        $this->assertSame([$y, $m, $d], $back);
    }

    /**
     * @dataProvider gregorianEdgeDatesProvider
     */
    public function testRoundTripCoptic(int $y, int $m, int $d): void
    {
        $this->requireIntlOrSkip();
        $to = $this->conv->gregorianToCoptic($y, $m, $d);
        $back = $this->conv->copticToGregorian(...$to);
        $this->assertSame([$y, $m, $d], $back);
    }

    /**
     * @dataProvider gregorianEdgeDatesProvider
     */
    public function testRoundTripEthiopian(int $y, int $m, int $d): void
    {
        $this->requireIntlOrSkip();
        $to = $this->conv->gregorianToEthiopian($y, $m, $d);
        $back = $this->conv->ethiopianToGregorian(...$to);
        $this->assertSame([$y, $m, $d], $back);
    }

    /**
     * @dataProvider gregorianEdgeDatesProvider
     */
    public function testRoundTripChinese(int $y, int $m, int $d): void
    {
        $this->requireIntlOrSkip();
        $to = $this->conv->gregorianToChinese($y, $m, $d);
        $back = $this->conv->chineseToGregorian(...$to);
        $this->assertSame([$y, $m, $d], $back);
    }

    /** ---------------- ICU calendars Round-trip (bulk sweep) ---------------- */

    public function testRoundTripAllCalendarsBulkSweep(): void
    {
        $this->requireIntlOrSkip();

        $samples = [
            [1990, 1, 1], [1990, 6, 15], [1990, 12, 31],
            [1996, 2, 29], [1997, 3, 1],
            [2001, 1, 31], [2001, 3, 31], [2001, 4, 30], [2001, 5, 31],
            [2010, 8, 31], [2011, 11, 30],
            [2015, 1, 1], [2015, 6, 30], [2015, 12, 31],
            [2016, 2, 29], [2016, 3, 1],
            [2020, 2, 29], [2020, 3, 1],
            [2024, 2, 29], [2024, 3, 1],
            [2025, 8, 13],
        ];

        foreach ($samples as [$y, $m, $d]) {
            // Hijri
            $to = $this->conv->gregorianToHijri($y, $m, $d);
            $this->assertSame([$y, $m, $d], $this->conv->hijriToGregorian(...$to));

            // Hebrew
            $to = $this->conv->gregorianToHebrew($y, $m, $d);
            $this->assertSame([$y, $m, $d], $this->conv->hebrewToGregorian(...$to));

            // Buddhist
            $to = $this->conv->gregorianToBuddhist($y, $m, $d);
            $this->assertSame([$y, $m, $d], $this->conv->buddhistToGregorian(...$to));

            // Coptic
            $to = $this->conv->gregorianToCoptic($y, $m, $d);
            $this->assertSame([$y, $m, $d], $this->conv->copticToGregorian(...$to));

            // Ethiopian
            $to = $this->conv->gregorianToEthiopian($y, $m, $d);
            $this->assertSame([$y, $m, $d], $this->conv->ethiopianToGregorian(...$to));

            // Chinese
            $to = $this->conv->gregorianToChinese($y, $m, $d);
            $this->assertSame([$y, $m, $d], $this->conv->chineseToGregorian(...$to));
        }
    }

    /** ---------------- Formatting with $mod ---------------- */

    public function testFormattingModVariants(): void
    {
        $this->requireIntlOrSkip();

        $y = 2025; $m = 8; $d = 13;

        // 1) Base array output (Hebrew Y/M/D)
        [$hy, $hm, $hd] = $this->conv->gregorianToHebrew($y, $m, $d);
        $this->assertIsArray([$hy, $hm, $hd]);

        // 2) Formatted string matches the same Hebrew Y/M/D with the chosen delimiter
        $this->assertSame(sprintf('%04d/%02d/%02d', $hy, $hm, $hd), $this->conv->gregorianToHebrew($y, $m, $d, '/'));
        $this->assertSame(sprintf('%04d-%02d-%02d', $hy, $hm, $hd), $this->conv->gregorianToHebrew($y, $m, $d, '-'));
        $this->assertSame(sprintf('%04d.%02d.%02d', $hy, $hm, $hd), $this->conv->gregorianToHebrew($y, $m, $d, '.'));

        // 3) Empty mod on reverse returns array and round-trip equals original Gregorian
        $back = $this->conv->hebrewToGregorian($hy, $hm, $hd, '');
        $this->assertSame([$y, $m, $d], $back);
    }

    /** ---------------- trNum: digits and punctuation ---------------- */

    public function testTrNumAsciiToPersian(): void
    {
        $this->assertSame('۱۲۳٬۴۵۶٫۷۸', $this->conv->trNum('123,456.78', 'fa'));
        $this->assertSame('۰', $this->conv->trNum('0', 'fa'));
        $this->assertSame('۲۰۲۵/۰۸/۱۳', $this->conv->trNum('2025/08/13', 'fa'));
    }

    public function testTrNumPersianToAscii(): void
    {
        $this->assertSame('123,456.78', $this->conv->trNum('۱۲۳٬۴۵۶٫۷۸', 'en'));
        $this->assertSame('0', $this->conv->trNum('۰', 'en'));
        $this->assertSame('2025/08/13', $this->conv->trNum('۲۰۲۵/۰۸/۱۳', 'en'));
    }

    public function testTrNumArabicIndicToAscii(): void
    {
        $this->assertSame('1234567890', $this->conv->trNum('١٢٣٤٥٦٧٨٩٠', 'en'));
        $this->assertSame('0.5', $this->conv->trNum('٠٫٥', 'en'));
        $this->assertSame('1,234', $this->conv->trNum('١٬٢٣٤', 'en'));
    }

    public function testTrNumIdempotency(): void
    {
        $s1 = $this->conv->trNum('123,456.78', 'fa');
        $s2 = $this->conv->trNum($s1, 'fa'); // applying again should be stable
        $this->assertSame($s1, $s2);

        $e1 = $this->conv->trNum('۱۲۳٬۴۵۶٫۷۸', 'en');
        $e2 = $this->conv->trNum($e1, 'en'); // applying again should be stable
        $this->assertSame($e1, $e2);
    }

    public function testTrNumMixedContent(): void
    {
        $in  = 'Ref# A-۱۲۳۴ on ۲۰۲۵/۰۸/۱۳ amount ١٬٢٣٤٫٥٦';
        $out = 'Ref# A-1234 on 2025/08/13 amount 1,234.56';
        $this->assertSame($out, $this->conv->trNum($in, 'en'));

        $in2  = 'Ref# A-1234 on 2025/08/13 amount 1,234.56';
        $out2 = 'Ref# A-۱۲۳۴ on ۲۰۲۵/۰۸/۱۳ amount ۱٬۲۳۴٫۵۶';
        $this->assertSame($out2, $this->conv->trNum($in2, 'fa'));
    }
}
