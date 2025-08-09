<?php

namespace JobMetric\Language;

use Illuminate\Support\Facades\View;
use JobMetric\Language\Facades\Language;
use JobMetric\Language\Models\Language as LanguageModels;
use JobMetric\PackageCore\Enums\RegisterClassTypeEnum;
use JobMetric\PackageCore\Exceptions\AssetFolderNotFoundException;
use JobMetric\PackageCore\Exceptions\MigrationFolderNotFoundException;
use JobMetric\PackageCore\Exceptions\RegisterClassTypeNotFoundException;
use JobMetric\PackageCore\Exceptions\ViewFolderNotFoundException;
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
     * @throws AssetFolderNotFoundException
     * @throws ViewFolderNotFoundException
     */
    public function configuration(PackageCore $package): void
    {
        $package->name('laravel-language')
            ->hasConfig()
            ->hasAsset()
            ->hasRoute()
            ->hasView()
            ->hasMigration()
            ->hasTranslation()
            ->registerClass('Language', Language::class, RegisterClassTypeEnum::SINGLETON());
    }

    /**
     * after boot
     *
     * @return void
     */
    public function afterBootPackage(): void
    {
        if (checkDatabaseConnection() && !$this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            $languages = LanguageModels::active()->get();

            View::composer('*', function ($view) use ($languages) {
                $view->with('languages', $languages);

                $defaultLanguage = $languages->where('locale', $this->app->getLocale())->first();
                $view->with('languageInfo', $defaultLanguage);
            });

            DomiLocalize('languages', json_decode($languages));
        }
    }
}
