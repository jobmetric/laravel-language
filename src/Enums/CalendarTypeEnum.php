<?php

namespace JobMetric\Language\Enums;

use JobMetric\PackageCore\Enums\EnumToArray;

/**
 * @method static GREGORIAN()
 * @method static JALALI()
 */
enum CalendarTypeEnum: string
{
    use EnumToArray;

    case GREGORIAN = "gregorian";
    case JALALI = "jalali";
}
