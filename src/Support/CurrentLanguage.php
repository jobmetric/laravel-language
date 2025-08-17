<?php

namespace JobMetric\Language\Support;

use Illuminate\Support\Facades\Cache;
use JobMetric\Language\Models\Language;

/**
 * Resolves the current Language model by the active app locale with optional caching.
 *
 * Behavior:
 * - Reads cache policy from config('language.cache_time'):
 *   - 0     => no cache (query each time)
 *   - null  => cache forever
 *   - N>0   => cache for N minutes
 * - Uses the default cache store; no extra config keys required.
 */
final class CurrentLanguage
{
    /**
     * Return the Language model for the current app locale with config-driven caching.
     *
     * Flow:
     * 1) Read active locale via app()->getLocale().
     * 2) If cache_time=0, bypass cache and query directly.
     * 3) If cache_time=null, cache forever.
     * 4) Otherwise, cache for cache_time minutes.
     *
     * @return Language|null
     */
    public static function get(): ?Language
    {
        $locale = app()->getLocale();
        $minutes = config('language.cache_time', 0);
        $key = 'language.current.' . $locale;

        if ($minutes === 0) {
            return self::resolveByLocale($locale);
        }

        if ($minutes === null) {
            return Cache::rememberForever($key, static fn(): ?Language => self::resolveByLocale($locale));
        }

        // Minutes => use a DateTimeInterface TTL for clarity across Laravel versions
        $ttl = now()->addMinutes((int)$minutes);

        return Cache::remember($key, $ttl, static fn(): ?Language => self::resolveByLocale($locale));
    }

    /**
     * Query a Language model by its locale in a single call.
     *
     * @param string $locale
     *
     * @return Language|null
     */
    private static function resolveByLocale(string $locale): ?Language
    {
        return Language::query()
            ->where('locale', $locale)
            ->first();
    }
}
