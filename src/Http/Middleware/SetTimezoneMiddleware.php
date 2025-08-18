<?php

namespace JobMetric\Language\Http\Middleware;

use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Resolves a per-request client timezone without changing Laravel/PHP default timezone.
 *
 * Priority:
 * 1) 'Accept-Timezone' header (if present & valid)
 * 2) config('app.timezone')            (fallback; if invalid => 'UTC')
 *
 * Effects:
 * - Sets request header 'Accept-Timezone' to the resolved value (normalized).
 * - Stores the effective value at config('app.client_timezone') for this request.
 * - DOES NOT touch app.timezone or PHP default timezone.
 */
class SetTimezoneMiddleware
{
    /**
     * Handle an incoming request and determine the effective timezone for this request.
     *
     * @param Request                      $request
     * @param Closure(Request): (Response) $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tz = null;

        // 1) Header: Accept-Timezone
        $headerTz = $request->header('Accept-Timezone');
        if ($this->isValidTimezone($headerTz)) {
            $tz = $headerTz;
        }

        // 2) Fallback: app.timezone (else UTC)
        if (!$tz) {
            $appTz = (string) config('app.timezone', 'UTC');
            $tz = $this->isValidTimezone($appTz) ? $appTz : 'UTC';
        }

        // Expose for the rest of the app (this request only)
        $request->headers->set('Accept-Timezone', $tz);
        config(['app.client_timezone' => $tz]);

        return $next($request);
    }

    /**
     * Validate a timezone identifier (IANA).
     *
     * @param mixed $tz
     *
     * @return bool
     */
    private function isValidTimezone(mixed $tz): bool
    {
        if (!is_string($tz) || trim($tz) === '') {
            return false;
        }

        try {
            new DateTimeZone($tz);
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
