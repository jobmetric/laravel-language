<?php

namespace JobMetric\Language;

use Illuminate\Contracts\Container\BindingResolutionException;
use JobMetric\EventSystem\Support\EventRegistry;
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

    /**
     * after boot package
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function afterBootPackage(): void
    {
        // Register events if EventRegistry is available
        // This ensures EventRegistry is available if EventSystemServiceProvider is loaded
        if ($this->app->bound('EventRegistry')) {
            /** @var EventRegistry $registry */
            $registry = $this->app->make('EventRegistry');

            // Language Events
            $registry->register(\JobMetric\Language\Events\Language\LanguageStoredEvent::class);
            $registry->register(\JobMetric\Language\Events\Language\LanguageUpdatedEvent::class);
            $registry->register(\JobMetric\Language\Events\Language\LanguageDeletedEvent::class);
            $registry->register(\JobMetric\Language\Events\Language\LanguageDeletingEvent::class);
            $registry->register(\JobMetric\Language\Events\SetLocaleEvent::class);
        }
    }
}
