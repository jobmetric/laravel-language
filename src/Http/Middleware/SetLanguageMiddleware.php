<?php

namespace JobMetric\Language\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

class SetLanguageMiddleware
{
    /**
     * Handle an incoming request for setting language.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     *
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (request()->route()->getName() !== 'language.set') {
            if (request()->header('Language')) {
                app()->setLocale(request()->header('Language'));
            } else if (session()->has('language')) {
                app()->setLocale(session()->get('language'));
            } else {
                app()->setLocale(config('app.locale'));
            }
        }

        return $next($request);
    }
}
