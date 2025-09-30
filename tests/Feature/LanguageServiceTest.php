<?php

namespace JobMetric\Language\Tests\Feature;

use FilesystemIterator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use JobMetric\Language\Events\Language\LanguageDeletedEvent;
use JobMetric\Language\Events\Language\LanguageDeletingEvent;
use JobMetric\Language\Events\Language\LanguageStoredEvent;
use JobMetric\Language\Events\Language\LanguageUpdatedEvent;
use JobMetric\Language\Language as LanguageService;
use JobMetric\Language\Models\Language as LanguageModel;
use JobMetric\Language\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

/**
 * Feature tests for the Language service.
 */
class LanguageServiceTest extends TestCase
{
    /**
     * Service instance under test.
     *
     * @var LanguageService
     */
    protected LanguageService $service;

    /**
     * Get languages table name from config with a safe fallback.
     */
    protected function table_name(): string
    {
        return config('language.tables.language', 'languages');
    }

    /**
     * Absolute path to the public flags directory used by getFlags().
     */
    protected function flags_path(): string
    {
        return public_path('assets/vendor/language/flags');
    }

    /**
     * Recursively remove flags directory if it exists to avoid test interference.
     */
    protected function remove_flags_directory_if_exists(): void
    {
        $path = $this->flags_path();

        if (!is_dir($path)) {
            return;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $fileInfo) {
            $p = $fileInfo->getRealPath();
            if ($fileInfo->isDir()) {
                @rmdir($p);
            } else {
                @unlink($p);
            }
        }

        @rmdir($path);
    }

    /**
     * Create a minimal SVG file in the flags directory for getFlags() tests.
     */
    protected function create_test_svg(string $filename): void
    {
        $path = $this->flags_path();
        @mkdir($path, 0777, true);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>';
        file_put_contents($path . DIRECTORY_SEPARATOR . $filename, $svg);
    }

    /**
     * Prepare a fresh service instance and clean caches/directories per test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LanguageService();

        Cache::forget('language.flags.list.v1');
        $this->remove_flags_directory_if_exists();
    }

    /**
     * Store should persist the record, return created response, and dispatch event.
     *
     * @throws Throwable
     */
    public function test_store(): void
    {
        Event::fake();

        $resp = $this->service->store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'calendar' => 'gregorian',
            'first_day_of_week' => 1,
            'status' => true,
        ]);

        $this->assertTrue($resp->ok);
        $this->assertEquals(201, $resp->status);
        $this->assertEquals(trans('language::base.messages.created'), $resp->message);
        $this->assertSame('English', $resp->data->name);
        $this->assertSame('us', $resp->data->flag);
        $this->assertSame('en', $resp->data->locale);
        $this->assertSame('ltr', $resp->data->direction);
        $this->assertTrue($resp->data->status);

        $this->assertDatabaseHas($this->table_name(), ['locale' => 'en', 'name' => 'English']);

        Event::assertDispatched(LanguageStoredEvent::class, function ($event) {
            return $event->language instanceof LanguageModel && $event->language->locale === 'en';
        });

        // Validation failure (status must be boolean)
        try {
            $this->service->store([
                'name' => 'Duplicate English',
                'flag' => 'us',
                'locale' => 'en',
                'direction' => 'ltr',
                'calendar' => 'gregorian',
                'first_day_of_week' => 1,
                'status' => 'true',
            ]);
        } catch (Throwable $e) {
            $this->assertInstanceOf(ValidationException::class, $e);
        }
    }

    /**
     * Update should persist changes and dispatch LanguageUpdatedEvent.
     *
     * @throws Throwable
     */
    public function test_update(): void
    {
        Event::fake();

        $created = $this->service->store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'calendar' => 'gregorian',
            'first_day_of_week' => 1,
            'status' => true,
        ]);

        $resp = $this->service->update($created->data->id, [
            'name' => 'English Updated',
            'status' => false,
        ]);

        $this->assertTrue($resp->ok);
        $this->assertEquals(200, $resp->status);
        $this->assertSame('English Updated', $resp->data->name);
        $this->assertFalse($resp->data->status);

        $this->assertDatabaseHas($this->table_name(), [
            'id' => $created->data->id,
            'name' => 'English Updated',
            'status' => false,
        ]);

        Event::assertDispatched(LanguageUpdatedEvent::class, function ($event) use ($created) {
            return $event->language instanceof LanguageModel && $event->language->id === $created->data->id;
        });
    }

    /**
     * Delete should remove the record and dispatch deleting/deleted events.
     *
     * @throws Throwable
     */
    public function test_delete(): void
    {
        Event::fake();

        $created = $this->service->store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'calendar' => 'gregorian',
            'first_day_of_week' => 1,
            'status' => true,
        ]);

        $resp = $this->service->delete($created->data->id);

        $this->assertTrue($resp->ok);
        $this->assertEquals(200, $resp->status);

        $this->assertDatabaseMissing($this->table_name(), ['id' => $created->data->id]);

        Event::assertDispatched(LanguageDeletingEvent::class);
        Event::assertDispatched(LanguageDeletedEvent::class);
    }

    /**
     * all() should return all records ordered by -id and match attributes.
     *
     * @throws Throwable
     */
    public function test_all(): void
    {
        $this->service->store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'calendar' => 'gregorian',
            'first_day_of_week' => 1,
            'status' => true,
        ]);

        $this->service->store([
            'name' => 'Turkish',
            'flag' => 'tr',
            'locale' => 'tr',
            'direction' => 'ltr',
            'calendar' => 'gregorian',
            'first_day_of_week' => 1,
            'status' => true,
        ]);

        $languages = $this->service->all();

        $this->assertCount(2, $languages);

        // default sort -id => latest first
        $this->assertSame('Turkish', $languages[0]->name);
        $this->assertSame('tr', $languages[0]->flag);
        $this->assertSame('tr', $languages[0]->locale);
        $this->assertSame('ltr', $languages[0]->direction);
        $this->assertTrue($languages[0]->status);

        $this->assertSame('English', $languages[1]->name);
        $this->assertSame('us', $languages[1]->flag);
        $this->assertSame('en', $languages[1]->locale);
        $this->assertSame('ltr', $languages[1]->direction);
        $this->assertTrue($languages[1]->status);
    }

    /**
     * all() should respect whitelisted filters and ignore unknown keys.
     *
     * @throws Throwable
     */
    public function test_all_with_whitelisted_filters(): void
    {
        $this->service->store([
            'name' => 'Persian',
            'flag' => 'ir',
            'locale' => 'fa',
            'direction' => 'rtl',
            'calendar' => 'jalali',
            'first_day_of_week' => 6,
            'status' => true,
        ]);
        $this->service->store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'calendar' => 'gregorian',
            'first_day_of_week' => 1,
            'status' => true,
        ]);
        $this->service->store([
            'name' => 'Arabic',
            'flag' => 'sa',
            'locale' => 'ar',
            'direction' => 'rtl',
            'calendar' => 'hijri',
            'first_day_of_week' => 6,
            'status' => false,
        ]);

        $list = $this->service->all([
            'direction' => 'rtl',
            'status' => true,
            'unknown' => 'ignored',
        ]);

        $this->assertCount(1, $list);
        $this->assertSame('fa', $list[0]->locale);
    }

    /**
     * paginate() should return a paginator with page size and default order (-id).
     *
     * @throws Throwable
     */
    public function test_paginate(): void
    {
        $this->service->store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'calendar' => 'gregorian',
            'first_day_of_week' => 1,
            'status' => true,
        ]);

        $this->service->store([
            'name' => 'Turkish',
            'flag' => 'tr',
            'locale' => 'tr',
            'direction' => 'ltr',
            'calendar' => 'gregorian',
            'first_day_of_week' => 1,
            'status' => true,
        ]);

        $paginator = $this->service->paginate([], 1);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertCount(1, $paginator->items());

        $first = $paginator->items()[0];
        $this->assertSame('Turkish', $first->name);
        $this->assertSame('tr', $first->locale);
    }

    /**
     * addLanguageData should be idempotent; calling twice must not duplicate.
     *
     * @throws Throwable
     */
    public function test_add_language_data_is_idempotent(): void
    {
        $this->service->addLanguageData('fa');
        $this->service->addLanguageData('fa');

        $this->assertSame(1, LanguageModel::query()->where('locale', 'fa')->count());
    }

    /**
     * getFlags should throw when the directory is missing.
     */
    public function test_get_flags_throws_when_directory_missing(): void
    {
        Cache::forget('language.flags.list.v1');
        $this->remove_flags_directory_if_exists();

        $this->expectException(RuntimeException::class);
        $this->service->getFlags();
    }

    /**
     * getFlags should return a sorted list with value/name/url for each SVG file.
     */
    public function test_get_flags_returns_sorted_list_with_urls(): void
    {
        Cache::forget('language.flags.list.v1');

        $this->create_test_svg('gb.svg');
        $this->create_test_svg('ir.svg');
        $this->create_test_svg('us.svg');

        $flags = $this->service->getFlags();

        $this->assertIsArray($flags);
        $this->assertNotEmpty($flags);

        $first = $flags[0];
        $this->assertArrayHasKey('value', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('url', $first);

        $names = array_column($flags, 'name');
        $sorted = $names;
        natcasesort($sorted);
        $this->assertSame(array_values($sorted), array_values($names));

        foreach ($flags as $item) {
            $this->assertStringContainsString('/assets/vendor/language/flags/', $item['url']);
            $this->assertStringEndsWith('.svg', $item['value']);
        }
    }
}
