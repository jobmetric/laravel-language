<?php

namespace JobMetric\Language\Events\Language;

use JobMetric\Language\Models\Language;

readonly class LanguageUpdatedEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Language $language,
        public array    $data
    )
    {
    }
}
