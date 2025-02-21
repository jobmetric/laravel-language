<?php

namespace JobMetric\Language\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use JobMetric\Language\Enums\CalendarTypeEnum;
use JobMetric\Language\Facades\DateConverter;
use JobMetric\Language\Models\Language;

class DateBasedOnCalendar implements CastsAttributes
{
    protected string $type;
    protected ?Language $language = null;

    public function __construct(string $type = 'date')
    {
        $this->type = $type;
    }

    /**
     * Cast the given value.
     *
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        $timezone = request()->header('accept-timezone', config('app.timezone'));

        $date = Carbon::parse($value, config('app.timezone'))->setTimezone($timezone);

        /**
         * @var Language $language
         */
        $language = Language::query()->where('locale', app()->getLocale())->first();
        if ($language->calendar === CalendarTypeEnum::JALALI()) {
            $value = DateConverter::gregorianToJalali($date->format('Y'), $date->format('m'), $date->format('d'), '-');

            if ($this->type === 'datetime') {
                $value .= ' ' . $date->format('H:i:s');
            }
        }

        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        $timezone = request()->header('accept-timezone', config('app.timezone'));

        /**
         * @var Language $language
         */
        $language = Language::query()->where('locale', app()->getLocale())->first();
        if ($language->calendar === CalendarTypeEnum::JALALI()) {
            $dateAndTime = explode(' ', $value);
            $haveTime = count($dateAndTime) > 1;

            if (!isset($dateAndTime[0]) || $dateAndTime[0] === '0000-00-00') {
                return null;
            }

            $date = explode('-', $dateAndTime[0]);

            if (count($date) !== 3) {
                return null;
            }

            $value = DateConverter::jalaliToGregorian($date[0], $date[1], $date[2], '-');

            if ($haveTime && $this->type === 'datetime') {
                $value .= ' ' . $dateAndTime[1];
            }
        }

        return Carbon::parse($value, $timezone)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    /**
     * Get the type of cast.
     *
     * @param array $arguments
     *
     * @return static
     */
    public static function castUsing(array $arguments): static
    {
        return new self($arguments[0] ?? 'date');
    }
}
