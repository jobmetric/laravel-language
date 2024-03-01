<?php

namespace JobMetric\Language;

use Illuminate\Support\Facades\View;
use JobMetric\Language\Facades\Language as LanguageFacade;
use JobMetric\PackageCore\Exceptions\MigrationFolderNotFoundException;
use JobMetric\PackageCore\Exceptions\RegisterClassTypeNotFoundException;
use JobMetric\PackageCore\PackageCore;
use JobMetric\PackageCore\PackageCoreServiceProvider;

class LanguageServiceProvider extends PackageCoreServiceProvider
{
    /**
     * @param PackageCore $package
     *
     * @return void
     * @throws MigrationFolderNotFoundException
     * @throws RegisterClassTypeNotFoundException
     */
    public function configuration(PackageCore $package): void
    {
        $package->name('laravel-language')
            ->hasConfig()
            ->hasMigration()
            ->hasTranslation()
            ->registerClass('Language', Language::class);
    }

    /**
     * after boot
     *
     * @return void
     */
    public function afterBootPackage(): void
    {
        if (checkDatabaseConnection()) {
            $languages = LanguageFacade::all(['status' => true]);
            View::composer('*', function ($view) use ($languages) {
                $view->with('languages', $languages);

                $defaultLanguage = $languages->where('locale', $this->app->getLocale())->first();
                $view->with('languageInfo', $defaultLanguage);
            });
        }
    }
}
