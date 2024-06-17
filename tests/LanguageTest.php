<?php

namespace JobMetric\Language\Tests;

use JobMetric\Language\Facades\Language;
use Tests\BaseDatabaseTestCase as BaseTestCase;
use Throwable;

class LanguageTest extends BaseTestCase
{
    /**
     * @throws Throwable
     */
    public function testStore(): void
    {
        // store language
        $language = Language::store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => true
        ]);

        $this->assertTrue($language['ok']);
        $this->assertEquals('English', $language['data']->name);
        $this->assertEquals('us', $language['data']->flag);
        $this->assertEquals('en', $language['data']->locale);
        $this->assertEquals('ltr', $language['data']->direction);
        $this->assertTrue($language['data']->status);
        $this->assertEquals(201, $language['status']);
        $this->assertEquals(trans('language::base.messages.created'), $language['message']);
        $this->assertDatabaseHas('languages', [
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => true
        ]);

        // store language with validation error
        $language = Language::store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => 'true'
        ]);

        $this->assertFalse($language['ok']);
        $this->assertEquals(422, $language['status']);
    }

    /**
     * @throws Throwable
     */
    public function testUpdate(): void
    {
        // store language
        $language = Language::store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => true
        ]);

        // update language
        $language = Language::update($language['data']->id, [
            'name' => 'English Updated',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => false
        ]);

        $this->assertTrue($language['ok']);
        $this->assertEquals('English Updated', $language['data']->name);
        $this->assertEquals('us', $language['data']->flag);
        $this->assertEquals('en', $language['data']->locale);
        $this->assertEquals('ltr', $language['data']->direction);
        $this->assertFalse($language['data']->status);
        $this->assertEquals(200, $language['status']);
        $this->assertDatabaseHas('languages', [
            'name' => 'English Updated',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => false
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testDelete(): void
    {
        // store language
        $language = Language::store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => true
        ]);

        // delete language
        $language = Language::delete($language['data']->id);

        $this->assertTrue($language['ok']);
        $this->assertEquals(200, $language['status']);
        $this->assertDatabaseMissing('languages', [
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => true
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testAll(): void
    {
        // store language
        $language_one = Language::store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => true
        ]);

        // store language
        $language_two = Language::store([
            'name' => 'Turkish',
            'flag' => 'tr',
            'locale' => 'tr',
            'direction' => 'ltr',
            'status' => true
        ]);

        // get all languages
        $languages = Language::all();


        $this->assertCount(2, $languages);

        $this->assertEquals('Turkish', $languages[0]->name);
        $this->assertEquals('tr', $languages[0]->flag);
        $this->assertEquals('tr', $languages[0]->locale);
        $this->assertEquals('ltr', $languages[0]->direction);
        $this->assertTrue($languages[0]->status);

        $this->assertEquals('English', $languages[1]->name);
        $this->assertEquals('us', $languages[1]->flag);
        $this->assertEquals('en', $languages[1]->locale);
        $this->assertEquals('ltr', $languages[1]->direction);
        $this->assertTrue($languages[1]->status);
    }

    /**
     * @throws Throwable
     */
    public function testPaginate(): void
    {
        // store language
        $language_one = Language::store([
            'name' => 'English',
            'flag' => 'us',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => true
        ]);

        // store language
        $language_two = Language::store([
            'name' => 'Turkish',
            'flag' => 'tr',
            'locale' => 'tr',
            'direction' => 'ltr',
            'status' => true
        ]);

        // paginate languages
        $paginateLanguages = Language::paginate([], 1);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $paginateLanguages);
        $this->assertIsInt($paginateLanguages->total());
        $this->assertIsInt($paginateLanguages->perPage());
        $this->assertIsInt($paginateLanguages->currentPage());
        $this->assertIsInt($paginateLanguages->lastPage());
        $this->assertIsArray($paginateLanguages->items());

        $this->assertCount(1, $paginateLanguages->items());

        $this->assertEquals('Turkish', $paginateLanguages->items()[0]->name);
        $this->assertEquals('tr', $paginateLanguages->items()[0]->flag);
        $this->assertEquals('tr', $paginateLanguages->items()[0]->locale);
        $this->assertEquals('ltr', $paginateLanguages->items()[0]->direction);
        $this->assertTrue($paginateLanguages->items()[0]->status);
    }
}
