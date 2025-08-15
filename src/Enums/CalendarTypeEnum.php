<?php

namespace JobMetric\Language\Enums;

use JobMetric\PackageCore\Enums\EnumMacros;

/**
 * @method static GREGORIAN()
 * @method static JALALI()
 * @method static HIJRI()
 * @method static HEBREW()
 * @method static BUDDHIST()
 * @method static COPTIC()
 * @method static ETHIOPIAN()
 * @method static CHINESE()
 */
enum CalendarTypeEnum: string
{
    use EnumMacros;

    case GREGORIAN = 'gregorian';
    case JALALI = 'jalali';
    case HIJRI = 'hijri';
    case HEBREW = 'hebrew';
    case BUDDHIST = 'buddhist';
    case COPTIC = 'coptic';
    case ETHIOPIAN = 'ethiopian';
    case CHINESE = 'chinese';
}
