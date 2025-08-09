<?php

namespace JobMetric\Language\Events\Language;

use JobMetric\Language\Models\Language;

readonly class LanguageDeletedEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public Language $language,
    )
    {
    }
}
