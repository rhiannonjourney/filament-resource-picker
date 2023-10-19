<?php

namespace UnexpectedJourney\FilamentResourcePicker;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Livewire\Livewire;
use UnexpectedJourney\FilamentResourcePicker\Facades\ResourcePickerManager;
use UnexpectedJourney\FilamentResourcePicker\Livewire\ResourceBrowser;

class FilamentResourcePickerPlugin implements Plugin
{
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-resource-picker';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            'panels::page.start',
            fn (): string => view('resource-picker::forms.components.resource-picker.modal')->render()
        );
    }

    public function register(Panel $panel): void
    {
        Livewire::component('resource-picker::resource-browser', ResourceBrowser::class);

        foreach ($panel->getResources() as $resource) {
            ResourcePickerManager::registerResource($resource);
        }
    }
}
