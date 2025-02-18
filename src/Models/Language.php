<?php

namespace JobMetric\Language\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JobMetric\PackageCore\Models\HasBooleanStatus;

/**
 * JobMetric\Language\Models\Language
 *
 * @property int id
 * @property string name
 * @property string flag
 * @property string locale
 * @property string direction
 * @property string calendar
 * @property string status
 */
class Language extends Model
{
    use HasFactory, HasBooleanStatus;

    protected $fillable = [
        'name',
        'flag',
        'locale',
        'direction',
        'calendar',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'flag' => 'string',
        'locale' => 'string',
        'direction' => 'string',
        'calendar' => 'string',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getTable()
    {
        return config('language.tables.language', parent::getTable());
    }

    /**
     * Scope a query to only include languages of a given locale.
     *
     * @param Builder $query
     * @param string $locale
     *
     * @return Builder
     */
    public function scopeOfLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }
}
