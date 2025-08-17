<?php

namespace JobMetric\Language\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JobMetric\Language\Events\SetLocaleEvent;
use JobMetric\Language\Models\Language;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale for the current request before controllers run.
 *
 * Behavior:
 * - Skips locale mutation on the 'language.set' route.
 * - Resolves locale by priority:
 *     1) 'Language' header (normalized/validated against active languages)
 *     2) session('language') if session exists and value is valid
 *     3) 'Accept-Language' header (q-weighted, RFC-style negotiation)
 *     4) config('app.locale') as fallback (normalized to an active locale when possible)
 * - Dispatches SetLocaleEvent after the locale is determined.
 */
class SetLanguageMiddleware
{
    /**
     * Handle the incoming request and configure the runtime locale accordingly.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (!($route && $route->getName() === 'language.set')) {
            $activeLocales = $this->activeLocales();
            $locale = null;

            // 1) Header: Language
            $headerLocale = $request->header('Language');
            if ($headerLocale) {
                $locale = $this->chooseBestLocale($headerLocale, $activeLocales);
            }

            // 2) Session: language
            if (!$locale && $request->hasSession() && $request->session()->has('language')) {
                $sessionLocale = (string)$request->session()->get('language');
                $locale = $this->chooseBestLocale($sessionLocale, $activeLocales) ?? $sessionLocale;
            }

            // 3) Header: Accept-Language (q-weighted)
            if (!$locale) {
                $accept = $request->header('Accept-Language');
                if ($accept) {
                    $locale = $this->negotiateFromAcceptLanguage($accept, $activeLocales);
                }
            }

            // 4) Fallback: config('app.locale') normalized if possible
            if (!$locale) {
                $fallback = (string)config('app.locale');
                $locale = $this->chooseBestLocale($fallback, $activeLocales) ?? $fallback;
            }

            app()->setLocale((string)$locale);
        }

        event(new SetLocaleEvent());

        return $next($request);
    }

    /**
     * Choose the best matching active locale for a given input.
     *
     * Matching strategy:
     * - Exact case-insensitive match (e.g., 'fa-IR' → 'fa-IR' if present).
     * - Base language fallback (e.g., 'fa-IR' → 'fa' if only base exists).
     * - Regional fallback when only regioned variants exist (e.g., 'pt' → first 'pt-*').
     *
     * @param string $input
     * @param array<int,string> $activeLocales
     *
     * @return string|null
     */
    private function chooseBestLocale(string $input, array $activeLocales): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        $normalized = str_replace('_', '-', strtolower($input));

        // Build a lowercase → original map for O(1) lookup
        $map = [];
        foreach ($activeLocales as $loc) {
            $map[strtolower($loc)] = $loc;
        }

        // 1) Exact case-insensitive match
        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        // 2) Base language fallback (fa-IR => fa)
        $base = explode('-', $normalized, 2)[0] ?? $normalized;
        if (isset($map[$base])) {
            return $map[$base];
        }

        // 3) Regional fallback when only regioned variants exist (input 'fa' and only 'fa-IR' exists)
        foreach ($map as $low => $orig) {
            if (str_starts_with($low, $base . '-')) {
                return $orig;
            }
        }

        return null;
    }

    /**
     * Negotiate locale from an Accept-Language header using q-weights against active locales.
     *
     * Parsing rules:
     * - Splits by comma, supports q-values (e.g., "fa-IR,fa;q=0.9,en-US;q=0.7,en;q=0.5").
     * - Ignores wildcard '*'.
     * - Tries each candidate (best q first) via chooseBestLocale.
     *
     * @param string $header
     * @param array<int,string> $activeLocales
     *
     * @return string|null
     */
    private function negotiateFromAcceptLanguage(string $header, array $activeLocales): ?string
    {
        $candidates = $this->parseAcceptLanguage($header);

        foreach ($candidates as $locale) {
            $match = $this->chooseBestLocale($locale, $activeLocales);
            if ($match) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Parse an Accept-Language header into an ordered list of locale tags by descending q.
     *
     * @param string $header
     *
     * @return array<int,string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $items = array_map('trim', explode(',', $header));
        $parsed = [];

        foreach ($items as $item) {
            // Pattern: lang[-REGION][;q=0.8]
            if (!preg_match('/^\s*([a-zA-Z0-9_-]+)\s*(?:;\s*q\s*=\s*(\d(?:\.\d+)?))?\s*$/', $item, $m)) {
                continue;
            }

            $tag = $m[1];
            if ($tag === '*') {
                continue;
            }

            $q = isset($m[2]) ? (float)$m[2] : 1.0;
            $parsed[] = ['tag' => $tag, 'q' => $q];
        }

        usort($parsed, static function ($a, $b) {
            if ($a['q'] === $b['q']) {
                return 0;
            }

            return ($a['q'] > $b['q']) ? -1 : 1;
        });

        return array_column($parsed, 'tag');
    }

    /**
     * Load active locales from the languages table (status=true). Optionally cache per config.
     *
     * Caching:
     * - language.cache_time (minutes): 0 => no cache, null => forever, N>0 => cache N minutes.
     *
     * @return array<int,string>
     */
    private function activeLocales(): array
    {
        $minutes = config('language.cache_time', 0);
        $key = 'language.active_locales';

        // No cache
        if ($minutes === 0) {
            return $this->queryActiveLocales();
        }

        // Forever
        if ($minutes === null) {
            return cache()->rememberForever($key, fn() => $this->queryActiveLocales());
        }

        // Minutes
        $ttl = now()->addMinutes((int)$minutes);

        return cache()->remember($key, $ttl, fn() => $this->queryActiveLocales());
    }

    /**
     * Query the database for active locales (status=true).
     *
     * @return array<int,string>
     */
    private function queryActiveLocales(): array
    {
        return Language::active()
            ->pluck('locale')
            ->filter()
            ->values()
            ->all();
    }
}
