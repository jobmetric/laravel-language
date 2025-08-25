<?php

namespace JobMetric\Language\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \JobMetric\Language\Language
 *
 * @method static \Spatie\QueryBuilder\QueryBuilder query(array $filter = [])
 * @method static \Illuminate\Pagination\LengthAwarePaginator paginate(array $filter = [], int $page_limit = 15)
 * @method static \Illuminate\Database\Eloquent\Collection all(array $filter = [])
 * @method static \JobMetric\PackageCore\Output\Response store(array $input)
 * @method static \JobMetric\PackageCore\Output\Response update(int $language_id, array $input)
 * @method static \JobMetric\PackageCore\Output\Response delete(int $language_id)
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
