<?php

namespace JobMetric\Language\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JobMetric\Language\Language
 *
 * @method static \Spatie\QueryBuilder\QueryBuilder query(array $filter = [])
 * @method static \Illuminate\Pagination\LengthAwarePaginator paginate(array $filter = [], int $page_limit = 15)
 * @method static \Illuminate\Database\Eloquent\Collection all(array $filter = [])
 * @method static array store(array $data)
 * @method static array update(int $language_id, array $data)
 * @method static array delete(int $language_id)
 * @method static void addLanguageData(string $locale)
 * @method static array getFlags()
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
