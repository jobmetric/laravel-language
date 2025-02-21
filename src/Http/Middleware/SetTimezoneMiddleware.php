<?php

namespace JobMetric\Language\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTimezoneMiddleware
{
    /**
     * Handle an incoming request for setting language.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasHeader('accept-timezone')) {
            $request->headers->set('accept-timezone', env('APP_TIMEZONE', config('app.timezone')));
        }

        return $next($request);
    }
}
