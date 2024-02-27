<?php

namespace UnexpectedJourney\FilamentResourcePicker\Support;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Arr;

class ResourcePickerManager
{
    use EvaluatesClosures;

    /** @var ResourcePickerConfiguration[] */
    protected array $configurations = [];

    protected array $resources = [];

    public function registerResource(string $resource): void
    {
        $this->resources[] = $resource;

        $this->registerConfiguration(ResourcePickerConfiguration::make($resource));
    }

    public function registerConfiguration(array | ResourcePickerConfiguration $configuration): void
    {
        foreach (Arr::wrap($configuration) as $config) {
            $this->configurations[$config->identifier] = $config;
        }
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    public function configure(array | string $configurations, ?Closure $callback = null): static
    {
        if (! is_array($configurations)) {
            throw_if(
                $callback === null,
                new \Exception('Required parameter $callback is missing')
            );

            $configurations = [$configurations => $callback];
        }

        foreach ($configurations as $configuration => $callback) {
            $this->evaluate($callback, namedInjections: ['configuration' => $this->getConfiguration($configuration)]);
        }

        return $this;
    }

    public function getConfiguration(string $configurationIdentifier)
    {
        throw_unless(
            isset($this->configurations[$configurationIdentifier]),
            new \Exception('Resource identified by [' . $configurationIdentifier . '] is not registered.')
        );

        return $this->configurations[$configurationIdentifier];
    }

    public function all(): array
    {
        return $this->configurations;
    }
}
