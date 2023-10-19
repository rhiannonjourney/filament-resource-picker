<?php

namespace UnexpectedJourney\FilamentResourcePicker\Livewire;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Columns\Column;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use UnexpectedJourney\FilamentResourcePicker\Facades\ResourcePickerManager;
use UnexpectedJourney\FilamentResourcePicker\Support\ResourcePickerConfiguration;

/**
 * @property Collection $selected;
 * @property Collection $selectedItems;
 */
class ResourceBrowser extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $resource;

    public int | string | null $selectedItemId = null;

    public array $selectedItemIds = [];

    public array $selectedSearchData = [];

    public bool $isSortable = false;

    public bool $isMultiple = false;

    public string $configurationIdentifier;

    public function mount(string $resource): void
    {
        $this->resource = $resource;
        $this->configurationIdentifier = ResourcePickerManager::getConfiguration($resource)->identifier;

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('selectedSearchData')
            ->schema([
                TextInput::make('term')
                    ->placeholder('Search selected')
                    ->hiddenLabel()
                    ->live(),
            ]);
    }

    #[Computed]
    public function selected(): Collection
    {
        return collect($this->selectedItemIds)
            ->push($this->selectedItemId)
            ->filter();
    }

    public function resetTable()
    {
        $this->resetTableSearch();
        $this->resetTableFiltersForm();
    }

    public function table(Table $table): Table
    {
        /*
         Get a fresh instance of the resource table so that
         the columns and filters can be re-used. We can't
         just pass the original $table instance into the
         table() method because bulk actions persist even
         if `bulkActions([])` is called to clear them out
         on the configured instance.

         We need our table to not have bulk actions otherwise
         the table builder renders a selection checkbox on
         every table row.
        */
        $resource = $this->resource;
        $resourceId = \UnexpectedJourney\FilamentResourcePicker\Support\get_resource_identifier($resource);
        $resourceTable = $resource::table(new Table($this));
        $queryStringIdentifier = str(class_basename($resource)) . 'Picker';

        // Make each cell clickable to toggle its selection
        $columns = collect($resourceTable->getColumns())
            ->map(fn (Column $column): Column => $column
                ->extraCellAttributes(fn (Model $record): array => [
                    'class' => 'cursor-pointer',
                    'x-on:click.prevent' => '$store.resourceBrowser.toggleItemSelection("' . $record->getKey() . '", "' . $resourceId . '")',
                ]))
            ->all();

        return $table
            ->query($resource::getEloquentQuery())
            ->columns($columns)
            ->recordClasses(fn (Model $record): ?string => match (true) {
                collect($this->selected)->contains($record->getKey()) => 'bg-primary-100 dark:bg-primary-800/50',
                default => null
            })
            ->queryStringIdentifier(str($resource::getModelLabel())->camel()->append('Picker'))
            ->filters($resourceTable->getFilters());
    }

    public function render(): View
    {
        return view('resource-picker::livewire.resource-browser', [
            'totalSelectedCount' => count($this->selected),
            'selectedItems' => $this->getSelectedItems(),
            'configuration' => $this->getConfigurationIdentifier(),
        ]);
    }

    public function getSelectedItems(): ?Collection
    {
        if (blank($this->resource)) {
            return collect();
        }

        if ($this->selected->isEmpty()) {
            return collect();
        }

        $resource = $this->resource;
        $configuration = $this->getConfigurationIdentifier();
        $resourceModel = $resource::getModel();
        $keyName = $resourceModel::make()->getKeyName();
        $selectedSearchModifiers = $this->form->getState();

        $query = $resourceModel::query()
            ->whereIn($keyName, $this->selected);

        // Apply search constraints for the results
        if (filled($selectedSearchModifiers['term'])) {
            ray()->showQueries();
            $isFirst = true;
            $query->where(fn (Builder $query): Builder => $this->applyResultsSearchAttributeConstraint(
                $query,
                $selectedSearchModifiers['term'],
                $configuration->getSearchColumns(),
                $isFirst
            ));
        }

        $ret = $query
            ->get()
            ->sortBy(fn (Model $model) => array_search($model->getKey(), $this->selected->all()));

        ray()->stopShowingQueries();

        return $ret;
    }

    public function getConfigurationIdentifier(): ResourcePickerConfiguration
    {
        return ResourcePickerManager::getConfiguration($this->configurationIdentifier);
    }

    protected function applyResultsSearchAttributeConstraint(
        Builder $query,
        string $search,
        array $searchAttributes,
        bool &$isFirst
    ): Builder {
        /** @var Connection $databaseConnection */
        $databaseConnection = $query->getConnection();

        $model = $query->getModel();

        $isForcedCaseInsensitive = $this->isResultsSearchForcedCaseInsensitive($query);

        foreach ($searchAttributes as $searchAttribute) {
            $whereClause = $isFirst ? 'where' : 'orWhere';

            $query->when(
                method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($searchAttribute),
                function (Builder $query) use (
                    $databaseConnection,
                    $isForcedCaseInsensitive,
                    $searchAttribute,
                    $search,
                    $whereClause
                ): Builder {
                    $searchColumn = match ($databaseConnection->getDriverName()) {
                        'pgsql' => "{$searchAttribute}::text",
                        default => $searchAttribute,
                    };

                    $caseAwareSearchColumn = $isForcedCaseInsensitive ?
                        new Expression("lower({$searchColumn})") :
                        $searchColumn;

                    return $query->$whereClause(
                        $caseAwareSearchColumn,
                        'like',
                        "%{$search}%",
                    );
                },
                fn (Builder $query): Builder => $query->when(
                    str($searchAttribute)->contains('.'),
                    function (Builder $query) use (
                        $isForcedCaseInsensitive,
                        $searchAttribute,
                        $search,
                        $whereClause
                    ): Builder {
                        $searchColumn = (string) str($searchAttribute)->afterLast('.');

                        $caseAwareSearchColumn = $isForcedCaseInsensitive ?
                            new Expression("lower({$searchColumn})") :
                            $searchColumn;

                        return $query->{"{$whereClause}Relation"}(
                            (string) str($searchAttribute)->beforeLast('.'),
                            $caseAwareSearchColumn,
                            'like',
                            "%{$search}%",
                        );
                    },
                    function ($query) use ($isForcedCaseInsensitive, $whereClause, $searchAttribute, $search) {
                        $caseAwareSearchColumn = $isForcedCaseInsensitive ?
                            new Expression("lower({$searchAttribute})") :
                            $searchAttribute;

                        return $query->{$whereClause}(
                            $caseAwareSearchColumn,
                            'like',
                            "%{$search}%",
                        );
                    },
                ),
            );

            $isFirst = false;
        }

        return $query;
    }

    protected function isResultsSearchForcedCaseInsensitive(Builder $query): bool
    {
        /** @var Connection $databaseConnection */
        $databaseConnection = $query->getConnection();

        return match ($databaseConnection->getDriverName()) {
            'pgsql' => true,
            default => false,
        };
    }
}
