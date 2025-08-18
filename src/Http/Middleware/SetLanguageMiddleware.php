<?php

namespace JobMetric\Language\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JobMetric\Language\Events\SetLocaleEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the application locale for the current request before controllers run.
 *
 * Priority:
 * 1) 'Accept-Language' header (q-weighted, cleaned)
 * 2) session('language') (if present)
 * 3) config('app.locale') (fallback)
 *
 * Notes:
 * - Skips locale mutation on route named 'language.set'.
 * - Normalizes resolved tag to base form (e.g., 'fa-IR' → 'fa', 'en_US' → 'en').
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
            $locale = null;

            // 1) Accept-Language (q-weighted, cleaned)
            $accept = $request->header('Accept-Language');
            if ($accept) {
                $locale = $this->negotiateFromAcceptLanguage($accept);
            }

            // 2) Session fallback
            if (!$locale && $request->hasSession() && $request->session()->has('language')) {
                $locale = (string)$request->session()->get('language');
            }

            // 3) Config fallback
            if (!$locale) {
                $locale = (string)config('app.locale');
            }

            $locale = $this->baseLocale((string)$locale);

            app()->setLocale($locale);
        }

        event(new SetLocaleEvent());

        return $next($request);
    }

    /**
     * Extract a best-effort locale from the Accept-Language header.
     *
     * Behavior:
     * - Parses comma-separated tags with optional q-values.
     * - Ignores wildcard '*'.
     * - Sorts by q (descending) and returns the first base-locale match.
     * - Cleans underscores and extra spaces; supports forms like "fa-IR, en_US;q=0.9".
     *
     * @param string $header
     *
     * @return string|null
     */
    private function negotiateFromAcceptLanguage(string $header): ?string
    {
        $candidates = $this->parseAcceptLanguage($header);

        foreach ($candidates as $tag) {
            $base = $this->baseLocale($tag);
            if ($base !== '') {
                return $base;
            }
        }

        return null;
    }

    /**
     * Parse an Accept-Language header into an ordered list of locale tags by descending q.
     *
     * Rules:
     * - Splits by comma, trims spaces.
     * - Accepts tags with optional q-values; ignores wildcard '*'.
     * - Normalizes underscores to dashes (e.g., 'en_US' → 'en-US').
     * - Drops candidates with q <= 0.0 (unacceptable).
     * - Stable sort: higher q first; for equal q, preserve original order.
     *
     * @param string $header
     *
     * @return array<int,string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $items = array_map('trim', explode(',', $header));
        $parsed = [];
        $idx = 0;

        foreach ($items as $item) {
            // Pattern: lang[-REGION][_REGION][;q=0.8]
            if (!preg_match('/^\s*([a-zA-Z0-9_-]+)\s*(?:;\s*q\s*=\s*(\d(?:\.\d+)?))?\s*$/', $item, $m)) {
                continue;
            }

            $tag = $m[1];
            if ($tag === '*') {
                continue;
            }

            // Normalize underscores early to keep consistency
            $tag = str_replace('_', '-', $tag);

            $q = isset($m[2]) ? (float)$m[2] : 1.0;
            if ($q <= 0.0) {
                continue;
            }

            // Keep original position for stable sorting on equal q
            $parsed[] = ['tag' => $tag, 'q' => $q, 'i' => $idx++];
        }

        usort($parsed, static function ($a, $b) {
            if ($a['q'] === $b['q']) {
                if ($a['i'] === $b['i']) {
                    return 0;
                }

                return ($a['i'] < $b['i']) ? -1 : 1;
            }

            return ($a['q'] > $b['q']) ? -1 : 1;
        });

        return array_column($parsed, 'tag');
    }

    /**
     * Reduce a locale tag to its base language to align with translation directories.
     * Examples: 'fa-IR' → 'fa', 'en_US' → 'en', 'ar' → 'ar'.
     *
     * @param string $tag
     *
     * @return string
     */
    private function baseLocale(string $tag): string
    {
        $tag = trim($tag);
        if ($tag === '') {
            return '';
        }

        $norm = str_replace('_', '-', $tag);
        $parts = explode('-', $norm, 2);

        return strtolower($parts[0] ?? $norm);
    }
}
