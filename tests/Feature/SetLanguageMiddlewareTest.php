<?php

namespace JobMetric\Language\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use JobMetric\Language\Events\SetLocaleEvent;
use JobMetric\Language\Http\Middleware\SetLanguageMiddleware;
use JobMetric\Language\Tests\TestCase;

/**
 * @covers \JobMetric\Language\Http\Middleware\SetLanguageMiddleware
 */
class SetLanguageMiddlewareTest extends TestCase
{
    /**
     * Define minimal routes used for middleware testing.
     *
     * - /ping: applies 'web' + SetLanguageMiddleware (session available)
     * - /ping-nosession: applies only SetLanguageMiddleware (no session middleware)
     * - /lang-set: named 'language.set' and must be skipped by the middleware logic
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Route with session (web group)
        Route::middleware(['web', SetLanguageMiddleware::class])
            ->get('/ping', static fn () => response(app()->getLocale()));

        // Route without session middleware
        Route::middleware([SetLanguageMiddleware::class])
            ->get('/ping-nosession', static fn () => response(app()->getLocale()));

        // Route that must be skipped by the middleware language mutation
        Route::middleware([SetLanguageMiddleware::class])
            ->get('/lang-set', static fn () => response(app()->getLocale()))
            ->name('language.set');
    }

    /**
     * It skips locale mutation when current route is named 'language.set'.
     *
     * @return void
     */
    public function test_skips_on_language_set_route(): void
    {
        config()->set('app.locale', 'en');

        $res = $this->get('/lang-set', ['Accept-Language' => 'fa-IR,fa;q=0.9']);

        $res->assertOk();
        $this->assertSame('en', $res->getContent());
    }

    /**
     * It prefers Accept-Language over session and config, and normalizes to base locale.
     *
     * @return void
     */
    public function test_accept_language_has_priority_and_normalizes_to_base(): void
    {
        config()->set('app.locale', 'en');

        $res = $this->withSession(['language' => 'ar'])
            ->get('/ping', ['Accept-Language' => 'fa-IR,fa;q=0.9']);

        $res->assertOk();
        $this->assertSame('fa', $res->getContent());
    }

    /**
     * It falls back to session('language') when Accept-Language header is missing.
     *
     * @return void
     */
    public function test_session_language_used_when_accept_language_missing(): void
    {
        config()->set('app.locale', 'en');

        $res = $this
            ->withSession(['language' => 'ar'])
            ->get('/ping', ['Accept-Language' => '*']);

        $res->assertOk();
        $this->assertSame('ar', $res->getContent());
    }

    /**
     * It uses Accept-Language when neither session nor config should take precedence (q-weighted, cleaned).
     *
     * @return void
     */
    public function test_accept_language_q_weighted_and_cleaned(): void
    {
        config()->set('app.locale', 'fa');

        // Messy header: mix of underscores, spaces, and q
        $header = ' de-DE , fa-IR;q=0.8 , en_US ; q = 0.6 , *;q=0.1 ';

        $res = $this->get('/ping', ['Accept-Language' => $header]);

        $res->assertOk();
        // Highest q first is 'de-DE' → base 'de'
        $this->assertSame('de', $res->getContent());
    }

    /**
     * It cleans underscores and spaces: 'en_US, fa;q=0.8' => base 'en'.
     *
     * @return void
     */
    public function test_accept_language_underscore_and_spaces_are_cleaned(): void
    {
        config()->set('app.locale', 'fa');

        $res = $this->get('/ping', ['Accept-Language' => ' en_US , fa ; q = 0.8 ']);

        $res->assertOk();
        $this->assertSame('en', $res->getContent());
    }

    /**
     * It falls back to config('app.locale') when no usable source is provided.
     *
     * @return void
     */
    public function test_fallback_to_config_locale_when_no_sources(): void
    {
        config()->set('app.locale', 'en');

        $res = $this->get('/ping');

        $res->assertOk();
        $this->assertSame('en', $res->getContent());
    }

    /**
     * It is safe when session is not started: no error is thrown and Accept-Language is honored.
     *
     * @return void
     */
    public function test_safe_when_session_not_started_accept_language_honored(): void
    {
        config()->set('app.locale', 'en');

        $res = $this->get('/ping-nosession', ['Accept-Language' => 'fa-IR,fa;q=0.9']);

        $res->assertOk();
        $this->assertSame('fa', $res->getContent());
    }

    /**
     * It dispatches SetLocaleEvent after resolving the locale.
     *
     * @return void
     */
    public function test_event_is_dispatched(): void
    {
        Event::fake();

        $res = $this->get('/ping', ['Accept-Language' => 'en-US,en;q=0.9']);

        $res->assertOk();
        Event::assertDispatched(SetLocaleEvent::class);
    }

    /**
     * It ignores wildcard '*' in Accept-Language and falls back to session, then config.
     *
     * @return void
     */
    public function test_accept_language_wildcard_is_ignored_then_session_then_config(): void
    {
        config()->set('app.locale', 'en');

        $res = $this->withSession(['language' => 'fa'])
            ->get('/ping', ['Accept-Language' => '*']);

        $res->assertOk();
        $this->assertSame('fa', $res->getContent());
    }

    /**
     * It ignores candidates with q=0 (unacceptable) and uses the next valid one.
     *
     * @return void
     */
    public function test_accept_language_ignores_q_zero_candidates(): void
    {
        config()->set('app.locale', 'en');

        $res = $this->get('/ping', ['Accept-Language' => 'fa-IR;q=0, ar;q=0, de;q=0, en;q=1']);

        $res->assertOk();
        $this->assertSame('en', $res->getContent());
    }

    /**
     * When q values are equal, preserves original order (first wins).
     *
     * @return void
     */
    public function test_accept_language_equal_q_preserves_order(): void
    {
        config()->set('app.locale', 'en');

        $res = $this->get('/ping', ['Accept-Language' => 'fa-IR;q=0.8, en-US;q=0.8']);

        $res->assertOk();
        $this->assertSame('fa', $res->getContent());
    }

    /**
     * It normalizes multi-part tags like 'zh-Hant-TW' to base 'zh'.
     *
     * @return void
     */
    public function test_accept_language_multi_part_tag_normalizes_to_base(): void
    {
        config()->set('app.locale', 'en');

        $res = $this->get('/ping', ['Accept-Language' => 'zh-Hant-TW, en;q=0.8']);

        $res->assertOk();
        $this->assertSame('zh', $res->getContent());
    }
}
