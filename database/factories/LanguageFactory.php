<?php

namespace JobMetric\Language\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JobMetric\Language\Models\Language;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => null,
            'flag' => null,
            'locale' => null,
            'direction' => null,
            'language' => null,
            'status' => true
        ];
    }

    /**
     * set name
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => $name
        ]);
    }

    /**
     * set flag
     *
     * @param string $flag
     *
     * @return static
     */
    public function setFlag(string $flag): static
    {
        return $this->state(fn(array $attributes) => [
            'flag' => $flag
        ]);
    }

    /**
     * set locale
     *
     * @param string $locale
     *
     * @return static
     */
    public function setLocale(string $locale): static
    {
        return $this->state(fn(array $attributes) => [
            'locale' => $locale
        ]);
    }

    /**
     * set direction
     *
     * @param string $direction
     *
     * @return static
     */
    public function setDirection(string $direction): static
    {
        return $this->state(fn(array $attributes) => [
            'direction' => $direction
        ]);
    }

    /**
     * set calendar
     *
     * @param string $calendar
     *
     * @return static
     */
    public function setCalendar(string $calendar): static
    {
        return $this->state(fn(array $attributes) => [
            'calendar' => $calendar
        ]);
    }

    /**
     * set status
     *
     * @param bool $status
     *
     * @return static
     */
    public function setStatus(bool $status): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => $status
        ]);
    }
}
