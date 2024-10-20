<?php

use JobMetric\Language\Facades\Language;

if (!function_exists('storeLanguage')) {
    /**
     * store language
     *
     * @param array $data
     *
     * @return array
     */
    function storeLanguage(array $data): array
    {
        return Language::store($data);
    }
}

if (!function_exists('updateLanguage')) {
    /**
     * update language
     *
     * @param int $language_id
     * @param array $data
     *
     * @return array
     */
    function updateLanguage(int $language_id, array $data): array
    {
        return Language::update($language_id, $data);
    }
}

if (!function_exists('deleteLanguage')) {
    /**
     * delete language
     *
     * @param int $language_id
     *
     * @return array
     */
    function deleteLanguage(int $language_id): array
    {
        return Language::delete($language_id);
    }
}

if (!function_exists('addLanguageScript')) {
    /**
     * add language script
     *
     * @return void
     */
    function addLanguageScript(): void
    {
        DomiScript('assets/vendor/language/js/laravel-language.js');
    }
}
