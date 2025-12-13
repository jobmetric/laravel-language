<?php

namespace JobMetric\Language\Events;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

readonly class SetLocaleEvent implements DomainEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct()
    {
    }

    /**
     * Returns the stable technical key for the domain event.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'language.locale_set';
    }

    /**
     * Returns the full metadata definition for this domain event.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'language::base.entity_names.language', 'language::base.events.locale_set.title', 'language::base.events.locale_set.description', 'fas fa-globe', [
            'language',
            'locale',
            'localization',
        ]);
    }
}
