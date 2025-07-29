<?php

namespace JobMetric\Language\Enums;

use JobMetric\PackageCore\Enums\EnumMacros;

/**
 * @method static GREGORIAN()
 * @method static JALALI()
 */
enum CalendarTypeEnum: string
{
    use EnumMacros;

    case GREGORIAN = "gregorian";
    case JALALI = "jalali";
}
