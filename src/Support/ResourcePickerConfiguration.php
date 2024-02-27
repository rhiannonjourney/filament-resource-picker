<?php

namespace UnexpectedJourney\FilamentResourcePicker\Support;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Database\Eloquent\Model;

class ResourcePickerConfiguration
{
    use EvaluatesClosures;

    protected string | Closure | null $previewComponent = null;

    protected ?array $searchColumns = null;

    protected function __construct(public readonly string $resource, public readonly string $identifier)
    {
    }

    public static function make(string $resource, ?string $identifier = null): static
    {
        return new static($resource, $identifier ?? $resource);
    }

    public function previewComponent(string | Closure $componentName): static
    {
        $this->previewComponent = $componentName;

        return $this;
    }

    public function getPreviewComponent(): ?string
    {
        return $this->evaluate($this->previewComponent);
    }

    public function searchColumns(?array $columns): static
    {
        $this->searchColumns = $columns;

        return $this;
    }

    public function getSearchColumns(): array
    {
        $resource = $this->resource;
        $columns = $this->searchColumns ?? [$resource::getRecordTitleAttribute()];

        return collect($columns)
            ->filter()
            ->all();
    }

    public function getModalId(): string
    {
        return str($this->resource)->remove('\\')->snake();
    }

    public function getResourceId(): string
    {
        return str($this->resource)->remove('\\')->snake();
    }

    public function getAlpineStoreId(): string
    {
        return str($this->resource)->remove('\\')->camel();
    }

    public function getResourceLabel(): string
    {
        $resource = $this->resource;

        return $resource::getModelLabel();
    }

    public function getPluralResourceLabel(): string
    {
        $resource = $this->resource;

        return $resource::getPluralModelLabel();
    }

    public function getRecordTitle(Model $record): string
    {
        $resource = $this->resource;

        return $resource::getRecordTitle($record);
    }
}
