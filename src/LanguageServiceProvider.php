<?php

namespace JobMetric\Language;

use JobMetric\PackageCore\Enums\RegisterClassTypeEnum;
use JobMetric\PackageCore\Exceptions\AssetFolderNotFoundException;
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
     * @throws AssetFolderNotFoundException
     */
    public function configuration(PackageCore $package): void
    {
        $package->name('laravel-language')
            ->hasAsset()
            ->hasConfig()
            ->hasMigration()
            ->hasTranslation()
            ->registerClass('Language', Language::class, RegisterClassTypeEnum::SINGLETON());
    }
}
