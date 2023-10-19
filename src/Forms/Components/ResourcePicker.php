<?php

namespace UnexpectedJourney\FilamentResourcePicker\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use UnexpectedJourney\FilamentResourcePicker\Facades\ResourcePickerManager;
use UnexpectedJourney\FilamentResourcePicker\Support\ResourcePickerConfiguration;

class ResourcePicker extends Field
{
    protected string $view = 'resource-picker::forms.components.resource-picker.index';

    protected bool $isMultiple = false;

    protected bool $isSortable = false;

    protected string | Closure | null $resource = null;

    protected string | Closure | null $relationship = null;

    protected string | Closure | null $orderColumn = null;

    public function isSortable(): bool
    {
        return $this->evaluate($this->isSortable);
    }

    public function multiple(bool $multiple = true): static
    {
        $this->isMultiple = $multiple;

        return $this;
    }

    public function resource(string | Closure $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function getItems(): null | Collection | Model
    {
        $state = $this->getState();

        if ($state === null) {
            return collect();
        }

        $resource = $this->resource;
        $model = $resource::getModel();

        $state = Collection::wrap($state);

        return $model::find($state)
            // Sort the items as they were present in the original state.
            ->sortBy(fn (Model $model) => array_search($model->getKey(), $state->all()));
    }

    public function getConfiguration(): ResourcePickerConfiguration
    {
        return ResourcePickerManager::getConfiguration($this->getResource());
    }

    public function getResource(): string
    {
        return $this->evaluate($this->resource);
    }

    public function orderColumn(string | Closure | null $column = 'sort'): static
    {
        $this->orderColumn = $column;
        $this->sortable();

        return $this;
    }

    public function sortable(bool | Closure $sortable = true): static
    {
        $this->isSortable = $sortable;

        return $this;
    }

    public function relationship(string | Closure $name = null, Closure $modifyQueryUsing = null): static
    {
        $this->relationship = $name ?? $this->getName();

        $this->loadStateFromRelationshipsUsing(static function (ResourcePicker $component, $state): void {
            if (filled($state)) {
                return;
            }

            $relationship = $component->getRelationship();

            if ($relationship instanceof BelongsToMany) {
                /** @var \Illuminate\Database\Eloquent\Collection $relatedModels */
                $relatedModels = $relationship->getResults();

                $component->state(
                    $relatedModels
                        ->pluck($relationship->getRelatedKeyName())
                        ->toArray(),
                );

                return;
            }

            if ($relationship instanceof \Znck\Eloquent\Relations\BelongsToThrough) {
                $relatedModel = $relationship->getResults();

                $component->state(
                    $relatedModel->getAttribute(
                        $relationship->getRelated()->getKeyName(),
                    ),
                );

                return;
            }

            /** @var BelongsTo $relationship */
            $relatedModel = $relationship->getResults();

            if (! $relatedModel) {
                return;
            }

            $component->state(
                $relatedModel->getAttribute(
                    $relationship->getOwnerKeyName(),
                ),
            );
        });

        $this->rule(
            static function (ResourcePicker $component): Exists {
                if ($component->getRelationship() instanceof \Znck\Eloquent\Relations\BelongsToThrough) {
                    $column = $component->getRelationship()->getRelated()->getKeyName();
                } else {
                    $column = $component->getRelationship()->getOwnerKeyName();
                }

                return Rule::exists(
                    $component->getRelationship()->getModel()::class,
                    $column,
                );
            },
            static function (ResourcePicker $component): bool {
                $relationship = $component->getRelationship();

                if (! (
                    $relationship instanceof BelongsTo ||
                    $relationship instanceof \Znck\Eloquent\Relations\BelongsToThrough
                )) {
                    return false;
                }

                return ! $component->isMultiple();
            },
        );

        $this->saveRelationshipsUsing(static function (ResourcePicker $component, Model $record, $state) {
            $relationship = $component->getRelationship();

            if (! $relationship instanceof BelongsToMany) {
                $relationship->associate($state);

                return;
            }

            if ($state === null) {
                $relationship->sync([]);

                return;
            }

            if ($orderColumn = $component->getOrderColumn()) {
                $state = collect($state)->mapWithKeys(fn ($item, $key) => [$item => [$orderColumn => $key + 1]]);
            }

            $relationship->sync($state);
        });

        $this->dehydrated(fn (ResourcePicker $component): bool => ! $component->isMultiple());

        return $this;
    }

    public function getRelationship(): BelongsTo | BelongsToMany | \Znck\Eloquent\Relations\BelongsToThrough | null
    {
        $name = $this->getRelationshipName();

        if (blank($name)) {
            return null;
        }

        return $this->getModelInstance()->{$name}();
    }

    public function getRelationshipName(): ?string
    {
        return $this->evaluate($this->relationship);
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    public function getOrderColumn(): ?string
    {
        return $this->evaluate($this->orderColumn);
    }
}
