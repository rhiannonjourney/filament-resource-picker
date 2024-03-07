@once
    @php
        $resourceStore = collect(\UnexpectedJourney\FilamentResourcePicker\Facades\ResourcePickerManager::getResources())
            ->mapWithKeys(function(string $resource): array {
                /** @var \UnexpectedJourney\FilamentResourcePicker\Support\ResourcePickerConfiguration $configuration */
                $configuration = \UnexpectedJourney\FilamentResourcePicker\Facades\ResourcePickerManager::getConfiguration($resource);

                return [
                    $configuration->getAlpineStoreId() => [
                        'isMultiple' => false,
                        'isSortable' => false,
                        'selectedItemId' => null,
                        'selectedItemIds' => [],
                        'configuration' => $configuration->identifier,
                    ]
                ];
            });
    @endphp

    {{-- The AlpineJS Store --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('resourceBrowser', {
                resources: @json($resourceStore),

                initResourceFromEvent(resource, eventDetails) {
                    this.resources[resource].isMultiple = eventDetails.isMultiple
                    this.resources[resource].isSortable = eventDetails.isSortable
                    this.resources[resource].configuration = eventDetails.configuration
                    this.selectItem(eventDetails.currentSelectedItemIds, resource)
                },

                selectItem(itemId, resource) {
                    if (this.resources[resource].isMultiple) {
                        if (typeof itemId === 'object') {
                            itemId = Object.values(itemId)
                        }

                        this.resources[resource].selectedItemIds = itemId
                    } else {
                        this.resources[resource].selectedItemId = itemId
                    }
                },

                toggleItemSelection(itemId, resource) {
                    if (!this.resources[resource].isMultiple) {
                        this.resources[resource].selectedItemId = this.resources[resource].selectedItemId == itemId
                            ? null
                            : itemId

                        return
                    }

                    // Loose comparison, so using some instead of includes
                    if (this.resources[resource].selectedItemIds.some((selectedItemId) => selectedItemId == itemId)) {
                        this.resources[resource].selectedItemIds = this.resources[resource].selectedItemIds.filter((item) => item != itemId)
                    } else {
                        this.resources[resource].selectedItemIds.push(itemId)
                    }
                },

                isItemSelected(itemId, resource) {
                    if (!this.resources[resource].isMultiple) {
                        return this.resources[resource].selectedItemId == itemId // Loose comparison for string/integer.
                    }

                    for (let selectedItemId of this.resources[resource].selectedItemIds) {
                        if (selectedItemId == itemId) {
                            // Loose comparison for string/integer.
                            return true
                        }
                    }

                    return false
                },

                resetSelection(resource) {
                    this.resources[resource].selectedItemId = null
                    this.resources[resource].selectedItemIds = []
                },
            })
        })
    </script>

    {{-- The Modal --}}
    <div class="relative h-0">
        @foreach(\UnexpectedJourney\FilamentResourcePicker\Facades\ResourcePickerManager::getResources() as $resource)
            @php
                /** @var \UnexpectedJourney\FilamentResourcePicker\Support\ResourcePickerConfiguration $configuration */
                $configuration = \UnexpectedJourney\FilamentResourcePicker\Facades\ResourcePickerManager::getConfiguration($resource);
                $resourceLabel = $resource::getModelLabel();
            @endphp

            <div x-data="{ statePath: null }">
                <x-filament::modal
                    width="full"
                    :id="$configuration->getModalId()"
                    x-on:open-modal.window="
                        if ($event.detail.id !== '{{ $configuration->getModalId() }}') {
                            return
                        }

                        statePath = $event.detail.getStatePath
                        $store.resourceBrowser.initResourceFromEvent('{{ $configuration->getAlpineStoreId() }}', $event.detail)

                        open()
                    "
                >
                    <div>
                        <div class="relative h-full max-h-[80vh] overflow-y-scroll">
                            <livewire:resource-picker::resource-browser
                                :defer="true"
                                :resource="$configuration->resource"
                                :wire:key="$configuration->getResourceId().'-resource-picker'"
                            />
                        </div>
                    </div>

                    <x-slot name="footer">
                        <div
                            @class([
                                'flex space-x-2',
                                'justify-start' => config('filament.layout.forms.actions.alignment') === 'left',
                                'justify-center' => config('filament.layout.forms.actions.alignment') === 'center',
                                'justify-end' => config('filament.layout.forms.actions.alignment') === 'right',
                            ])
                        >
                            <x-filament::button
                                outlined
                                color="gray"
                                x-on:click="$dispatch('close-modal', {id: '{{ $configuration->getModalId() }}'})"
                            >
                                Cancel
                            </x-filament::button>

                            <x-filament::button
                                x-on:click="$dispatch('close-modal', {id: '{{ $configuration->getModalId() }}', statePath: statePath})"
                            >
                                Update and Close
                            </x-filament::button>
                        </div>
                    </x-slot>
                </x-filament::modal>
            </div>
        @endforeach
    </div>
@endonce
