<?php

namespace JobMetric\Language\Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use JobMetric\Language\Facades\Language as LanguageFacade;
use JobMetric\Language\Language;
use JobMetric\Language\Tests\TestCase;
use JobMetric\PackageCore\Output\Response;
use Mockery;
use Mockery\MockInterface;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Tests for Language facade by mocking the underlying container binding.
 *
 * We bind a mocked service instance into the container using the same accessor key ("Language").
 * The facade then forwards calls to this mock.
 */
class LanguageFacadeTest extends TestCase
{
    /**
     * Create and bind a mock of the underlying Language service.
     *
     * @return MockInterface
     */
    protected function bindServiceMock(): MockInterface
    {
        $mock = Mockery::mock(Language::class);
        $this->app->instance('Language', $mock);

        return $mock;
    }

    /**
     * Ensure the binding key used by the facade can be overridden for mocking.
     *
     * @return void
     */
    public function test_facade_binding_can_be_mocked(): void
    {
        $mock = $this->bindServiceMock();

        $this->assertTrue(app()->bound('Language'));
        $this->assertSame($mock, app('Language'));
    }

    /**
     * Ensure query(...) forwards to the underlying service.
     *
     * @return void
     */
    public function test_query_forwards_call(): void
    {
        $mock = $this->bindServiceMock();

        $mock->shouldReceive('query')
            ->once()
            ->with([])
            ->andReturn(Mockery::mock(QueryBuilder::class));

        $qb = LanguageFacade::query([]);

        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    /**
     * Ensure paginate(...) forwards to the underlying service.
     *
     * @return void
     */
    public function test_paginate_forwards_call(): void
    {
        $mock = $this->bindServiceMock();

        $paginator = new LengthAwarePaginator([], 0, 15);

        $mock->shouldReceive('paginate')
            ->once()
            ->with([], 15)
            ->andReturn($paginator);

        $result = LanguageFacade::paginate([], 15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(15, $result->perPage());
    }

    /**
     * Ensure all(...) forwards to the underlying service.
     *
     * @return void
     */
    public function test_all_forwards_call(): void
    {
        $mock = $this->bindServiceMock();

        $mock->shouldReceive('all')
            ->once()
            ->with([])
            ->andReturn(new Collection());

        $result = LanguageFacade::all([]);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Ensure store(...) forwards to the underlying service.
     *
     * @return void
     */
    public function test_store_forwards_call(): void
    {
        $mock = $this->bindServiceMock();

        $payload = [
            'name' => 'Persian',
            'locale' => 'fa',
            'direction' => 'rtl',
        ];

        // Simulate a successful Response (HTTP 200)
        $response = new Response(true, 'ok');

        $mock->shouldReceive('store')
            ->once()
            ->with($payload)
            ->andReturn($response);

        $result = LanguageFacade::store($payload);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(200, $result->status);
    }

    /**
     * Ensure update(...) forwards to the underlying service.
     *
     * @return void
     */
    public function test_update_forwards_call(): void
    {
        $mock = $this->bindServiceMock();

        $response = new Response(true, 'updated');

        $mock->shouldReceive('update')
            ->once()
            ->with(1, ['name' => 'Farsi'])
            ->andReturn($response);

        $result = LanguageFacade::update(1, ['name' => 'Farsi']);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(200, $result->status);
    }

    /**
     * Ensure delete(...) forwards to the underlying service.
     *
     * @return void
     */
    public function test_delete_forwards_call(): void
    {
        $mock = $this->bindServiceMock();

        $response = new Response(true, 'deleted');

        $mock->shouldReceive('delete')
            ->once()
            ->with(2)
            ->andReturn($response);

        $result = LanguageFacade::delete(2);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(200, $result->status);
    }

    /**
     * Ensure addLanguageData(...) forwards (void method).
     *
     * @return void
     */
    public function test_add_language_data_forwards_call(): void
    {
        $mock = $this->bindServiceMock();

        $mock->shouldReceive('addLanguageData')
            ->once()
            ->with('fa')
            ->andReturnNull();

        LanguageFacade::addLanguageData('fa');

        $this->assertTrue(true);
    }

    /**
     * Ensure getFlags() forwards and returns array.
     *
     * @return void
     */
    public function test_get_flags_forwards_call(): void
    {
        $mock = $this->bindServiceMock();

        $flags = ['ir' => '🇮🇷', 'us' => '🇺🇸'];

        $mock->shouldReceive('getFlags')
            ->once()
            ->withNoArgs()
            ->andReturn($flags);

        $result = LanguageFacade::getFlags();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ir', $result);
    }

    /**
     * Close Mockery after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
