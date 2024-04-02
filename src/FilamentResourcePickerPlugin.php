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
    protected array $registerAdditionalResources = [];

    protected string $resourceBrowserComponent = ResourceBrowser::class;

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
        Livewire::component('resource-picker::resource-browser', $this->getResourceBrowserComponent());

        foreach (array_merge($panel->getResources(), $this->registerAdditionalResources) as $resource) {
            ResourcePickerManager::registerResource($resource);
        }
    }

    public function getResourceBrowserComponent(): string
    {
        return $this->resourceBrowserComponent;
    }

    public function registerAdditionalResource(string $resource): static
    {
        $this->registerAdditionalResources[] = $resource;

        return $this;
    }

    public function registerAdditionalResources(array $resources, bool $merge = true): static
    {
        $this->registerAdditionalResources = $merge
            ? array_merge($this->registerAdditionalResources, $resources)
            : $resources;

        return $this;
    }

    public function resourceBrowserComponent(string $resourceBrowserComponent): static
    {
        $this->resourceBrowserComponent = $resourceBrowserComponent;

        return $this;
    }
}
