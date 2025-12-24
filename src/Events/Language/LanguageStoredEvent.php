<?php

namespace JobMetric\Language\Events\Language;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;
use JobMetric\Language\Models\Language;

readonly class LanguageStoredEvent implements DomainEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Language $language,
        public array $data
    ) {
    }

    /**
     * Returns the stable technical key for the domain event.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'language.stored';
    }

    /**
     * Returns the full metadata definition for this domain event.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'language::base.entity_names.language', 'language::base.events.language_stored.title', 'language::base.events.language_stored.description', 'fas fa-save', [
            'language',
            'storage',
            'management',
        ]);
    }
}
