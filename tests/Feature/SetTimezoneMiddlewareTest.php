<?php

namespace JobMetric\Language\Tests\Feature;

use Illuminate\Support\Facades\Route;
use JobMetric\Language\Http\Middleware\SetTimezoneMiddleware;
use JobMetric\Language\Tests\TestCase;

/**
 * @covers \JobMetric\Language\Http\Middleware\SetTimezoneMiddleware
 */
class SetTimezoneMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // app.timezone = UTC per TestCase already
        Route::middleware([SetTimezoneMiddleware::class])
            ->get('/tz', static function () {
                return response()->json([
                    'header' => request()->header('Accept-Timezone'),
                    'client_config' => config('app.client_timezone'),
                    'formatted' => tz_format('2025-08-16 12:00:00'), // from UTC to client tz
                ]);
            });
    }

    /**
     * It uses Accept-Timezone when provided and valid; does not touch app.timezone.
     *
     * @return void
     */
    public function test_accept_timezone_header_valid(): void
    {
        $res = $this->get('/tz', ['Accept-Timezone' => 'Asia/Tehran']);

        $res->assertOk();
        $json = $res->json();

        $this->assertSame('Asia/Tehran', $json['header']);
        $this->assertSame('Asia/Tehran', $json['client_config']);

        // 12:00 UTC => 15:30 Tehran
        $this->assertSame('2025-08-16 15:30:00', $json['formatted']);
    }

    /**
     * It falls back to app.timezone when header is missing or invalid.
     *
     * @return void
     */
    public function test_fallback_to_app_timezone_when_missing_or_invalid(): void
    {
        // Missing header
        $res = $this->get('/tz');
        $res->assertOk();
        $json = $res->json();
        $this->assertSame('UTC', $json['header']);
        $this->assertSame('UTC', $json['client_config']);
        $this->assertSame('2025-08-16 12:00:00', $json['formatted']);

        // Invalid header
        $res2 = $this->get('/tz', ['Accept-Timezone' => 'Not/AZone']);
        $res2->assertOk();
        $json2 = $res2->json();
        $this->assertSame('UTC', $json2['header']);
        $this->assertSame('UTC', $json2['client_config']);
        $this->assertSame('2025-08-16 12:00:00', $json2['formatted']);
    }
}
