<?php

namespace UnexpectedJourney\FilamentResourcePicker;

use Filament\Support\Facades\FilamentAsset;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use UnexpectedJourney\FilamentResourcePicker\Support\ResourcePickerManager;
use UnexpectedJourney\FilamentResourcePicker\Testing\TestsFilamentResourcePicker;

class FilamentResourcePickerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name('filament-resource-picker')
            ->hasViews('resource-picker')
            ->hasTranslations();

        $this->app->singleton(ResourcePickerManager::class);
    }

    public function packageRegistered(): void
    {
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Testing
        Testable::mixin(new TestsFilamentResourcePicker());
    }

    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('filament-resource-picker', __DIR__ . '/../resources/dist/components/filament-resource-picker.js'),
            //            Css::make('filament-resource-picker-styles', __DIR__.'/../resources/dist/filament-resource-picker.css'),
            //            Js::make('filament-resource-picker-scripts', __DIR__.'/../resources/dist/filament-resource-picker.js'),
        ];
    }

    protected function getAssetPackageName(): ?string
    {
        return 'unexpectedjourney/filament-resource-picker';
    }

    protected function getScriptData(): array
    {
        return [];
    }
}
