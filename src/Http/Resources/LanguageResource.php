<?php

namespace JobMetric\Language\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Language resource transformer.
 *
 * Exposes normalized language fields used for i18n/L10n:
 * - Basic identity (id, name, flag, locale)
 * - UI direction (ltr|rtl) + translated label and boolean helper
 * - Calendar system + translated label
 * - First day of week (0..6) + translated weekday name
 * - Status + timestamps
 *
 * Translations are expected under:
 * - language::base.calendar_type.{key}
 * - language::base.direction.{ltr|rtl}
 * - language::base.weekdays.{0..6}   (0=Saturday, 1=Sunday, ..., 6=Friday)
 *
 * @property int $id
 * @property string $name
 * @property string|null $flag
 * @property string $locale                IETF/BCP47-like code (e.g. fa, fa-IR, en, en-GB)
 * @property string $direction             'ltr' or 'rtl'
 * @property string $calendar              e.g. 'gregorian','jalali','hijri','hebrew','buddhist','coptic','ethiopian','chinese'
 * @property int $first_day_of_week        0..6 where 0=Saturday, 1=Sunday, ..., 6=Friday
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Safe helpers with graceful fallback if a key is missing in lang files.
        $calendarTrans = trans('language::base.calendar_type.' . $this->calendar);
        if ($calendarTrans === 'language::base.calendar_type.' . $this->calendar) {
            $calendarTrans = ucfirst($this->calendar);
        }

        $directionTrans = trans('language::base.direction.' . $this->direction);
        if ($directionTrans === 'language::base.direction.' . $this->direction) {
            $directionTrans = strtoupper($this->direction);
        }

        $weekdayKey = (string)$this->first_day_of_week;
        $firstDayTrans = trans('language::base.weekdays.' . $weekdayKey);
        if ($firstDayTrans === 'language::base.weekdays.' . $weekdayKey) {
            // Fallback map if translation key not provided
            $fallbackWeekdays = [
                0 => 'Saturday',
                1 => 'Sunday',
                2 => 'Monday',
                3 => 'Tuesday',
                4 => 'Wednesday',
                5 => 'Thursday',
                6 => 'Friday',
            ];
            $firstDayTrans = $fallbackWeekdays[$this->first_day_of_week] ?? 'Saturday';
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'flag' => $this->flag,
            'locale' => $this->locale,

            'direction' => $this->direction,
            'direction_trans' => $directionTrans,
            'is_rtl' => ($this->direction === 'rtl'),

            'calendar' => $this->calendar,
            'calendar_trans' => $calendarTrans,

            'first_day_of_week' => $this->first_day_of_week,
            'first_day_of_week_trans' => $firstDayTrans,

            'status' => $this->status,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
