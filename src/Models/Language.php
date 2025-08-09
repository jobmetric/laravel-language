<?php

namespace JobMetric\Language\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JobMetric\Language\Enums\CalendarTypeEnum;
use JobMetric\PackageCore\Models\HasBooleanStatus;

/**
 * Class Language
 *
 * Represents a language entry used for i18n/L10n, including locale identity,
 * UI direction, calendar system preference, first day of week, and formatting masks.
 *
 * Conventions:
 * - Direction values: 'ltr' or 'rtl'.
 * - first_day_of_week: integer in [0..6], where 0=Saturday, 1=Sunday, ..., 6=Friday.
 *
 * Properties (database columns)
 * @property int $id
 * @property string $name
 * @property string|null $flag
 * @property string $locale
 * @property string $direction
 * @property string $calendar
 * @property int $first_day_of_week
 * @property bool $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * Query scopes
 * @method static Builder ofLocale(string $locale)
 * @method static Builder ofLtr()
 * @method static Builder ofRtl()
 * @method static Builder ofCalendar(string $calendar)
 */
class Language extends Model
{
    use HasFactory, HasBooleanStatus;

    /** Direction constants. */
    public const DIRECTION_LTR = 'ltr';
    public const DIRECTION_RTL = 'rtl';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'flag',
        'locale',
        'direction',
        'calendar',
        'first_day_of_week',
        'status',
    ];

    /**
     * Attribute casting rules.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'flag' => 'string',
        'locale' => 'string',
        'direction' => 'string',
        'calendar' => 'string',
        'first_day_of_week' => 'integer',
        'status' => 'boolean'
    ];

    /**
     * Resolve table name from configuration.
     *
     * @return string
     */
    public function getTable(): string
    {
        return config('language.tables.language', parent::getTable());
    }

    /**
     * Scope: filter by a given locale.
     *
     * @param Builder $query
     * @param string $locale
     * @return Builder
     */
    public function scopeOfLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope: filter entries with left-to-right direction.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfLtr(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_LTR);
    }

    /**
     * Scope: filter entries with right-to-left direction.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfRtl(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_RTL);
    }

    /**
     * Scope: filter by a specific calendar identifier (case-insensitive).
     *
     * @param Builder $query
     * @param string $calendar
     * @return Builder
     */
    public function scopeOfCalendar(Builder $query, string $calendar): Builder
    {
        return $query->where('calendar', strtolower(trim($calendar)));
    }

    /**
     * Mutator: normalize the calendar identifier to lowercase and
     * optionally infer a default first_day_of_week if not explicitly provided.
     *
     * This preserves developer intent: if 'first_day_of_week' is present in the
     * incoming attributes (even null), it will not be overwritten.
     *
     * @param string $value
     * @return void
     */
    public function setCalendarAttribute(string $value): void
    {
        $normalized = strtolower(trim($value));
        $this->attributes['calendar'] = $normalized;

        // Only set default if first_day_of_week is not being mass-assigned explicitly.
        if (!array_key_exists('first_day_of_week', $this->attributes)) {
            $this->attributes['first_day_of_week'] = self::defaultFirstDayForCalendar($normalized);
        }
    }

    /**
     * Helper: get the conventional default first day of week for a calendar.
     * Falls back to Saturday (0) if the calendar is unknown.
     *
     * @param string $calendar
     * @return int
     */
    public static function defaultFirstDayForCalendar(string $calendar): int
    {
        // Define first day of week for known calendars
        $firstDays = [
            CalendarTypeEnum::GREGORIAN() => 1, // Sunday
            CalendarTypeEnum::JALALI() => 0,    // Saturday
            CalendarTypeEnum::HIJRI() => 0,     // Saturday
            CalendarTypeEnum::HEBREW() => 0,    // Saturday
            CalendarTypeEnum::BUDDHIST() => 1,  // Sunday
            CalendarTypeEnum::COPTIC() => 0,    // Saturday
            CalendarTypeEnum::ETHIOPIAN() => 0, // Saturday
            CalendarTypeEnum::CHINESE() => 0,   // Saturday
        ];

        // Return the first day of week for the specified calendar, or default to 0 (Saturday)
        return $firstDays[$calendar] ?? 0;
    }

    /**
     * Convenience: true if the language direction is RTL.
     *
     * @return bool
     */
    public function isRtl(): bool
    {
        return $this->direction === self::DIRECTION_RTL;
    }

    /**
     * Convenience: true if the language direction is LTR.
     *
     * @return bool
     */
    public function isLtr(): bool
    {
        return $this->direction === self::DIRECTION_LTR;
    }
}
