<?php

namespace JobMetric\Language\Tests\Stubs\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JobMetric\Language\Tests\Stubs\Models\CastedItem;

/**
 * @extends Factory<CastedItem>
 */
class CastedItemFactory extends Factory
{
    protected $model = CastedItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date_at' => null,
            'datetime_at' => null
        ];
    }

    /**
     * set date_at
     *
     * @param string $date
     *
     * @return static
     */
    public function setDateAt(string $date): static
    {
        return $this->state(fn(array $attributes) => [
            'date_at' => $date,
        ]);
    }

    /**
     * set datetime_at
     *
     * @param string $datetime
     *
     * @return static
     */
    public function setDatetimeAt(string $datetime): static
    {
        return $this->state(fn(array $attributes) => [
            'datetime_at' => $datetime,
        ]);
    }
}
