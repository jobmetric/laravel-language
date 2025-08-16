<?php

namespace JobMetric\Language\Tests\Stubs\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use JobMetric\Language\Casts\DateBasedOnCalendarCast;
use JobMetric\Metadata\Tests\Stubs\Factories\ArticleFactory;

/**
 * @property int $id
 * @property Carbon|null $date_at
 * @property Carbon|null $datetime_at
 *
 * @method static create(string[] $array)
 */
class CastedItem extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'date_at',
        'datetime_at'
    ];
    protected $casts = [
        'date_at' => DateBasedOnCalendarCast::class . ':date,-,en',
        'datetime_at' => DateBasedOnCalendarCast::class . ':datetime,-,en',
    ];

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }
}
