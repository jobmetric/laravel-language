<?php

namespace JobMetric\Language\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JobMetric\Language\Language
 *
 * @method static array store(array $data)
 * @method static array update(int $language_id, array $data)
 * @method static array delete(int $language_id)
 */
class Language extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'Language';
    }
}
