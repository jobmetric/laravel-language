<?php

namespace JobMetric\Language\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JobMetric\Language\DateConverter
 *
 * @method static array|string gregorianToJalali(int $gy, int $gm, int $gd, string $mod = '')
 * @method static array|string jalaliToGregorian(int $jy, int $jm, int $jd, string $mod = '')
 * @method static string trNum(string $str, string $mod = 'en', string $mf = '٫')
 */
class DateConverter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \JobMetric\Language\DateConverter::class;
    }
}
