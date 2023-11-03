<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        /** @var \UnexpectedJourney\FilamentResourcePicker\Support\ResourcePickerConfiguration $configuration */
        $configuration = $getConfiguration();
        $resource = $configuration->resource;
        $previewComponent = $configuration->getPreviewComponent();
        $modalId = $configuration->getModalId();

        $items = $getItems();
        $state = $getState();
        $isMultiple = $isMultiple();
        $isSortable = $isSortable();
        $statePath = $getStatePath();
    @endphp
    <div
        x-data='{
            state: $wire.entangle("{{ $getStatePath() }}").live,
            configuration: @json($configuration->identifier),
            reorderItems(order) {
                this.state = order.map(function (item) {
                    return item.split("item-")[1]
                })
            },
            openModal() {
                $dispatch("open-modal", {
                    id: "{{ $configuration->getModalId() }}",
                    isMultiple: {{ $isMultiple ? "true" : "false" }},
                    isSortable: {{ $isSortable ? "true" : "false" }},
                    currentSelectedItemIds: this.state ?? [],
                    getStatePath: "{{ $getStatePath() }}",
                    configuration: this.configuration,
                })
            }
        }'
        x-on:close-modal.window="if($event.detail.id === '{{ $configuration->getModalId() }}' && $event.detail.statePath === '{{ $getStatePath() }}' ) {
            @if ($isMultiple)
                state = $store.resourceBrowser.resources['{{ $configuration->getAlpineStoreId() }}'].selectedItemIds
            @else
                state = $store.resourceBrowser.resources['{{ $configuration->getAlpineStoreId() }}'].selectedItemId
            @endif
        }"
        class="pb-4"
    >
        <div x-show="state != null">
            <x-filament::grid
                :default="$getColumns('default')"
                :sm="$getColumns('sm')"
                :md="$getColumns('md')"
                :lg="$getColumns('lg')"
                :xl="$getColumns('xl')"
                :two-xl="$getColumns('2xl')"
                class="gap-2"
                :x-sortable="$isSortable && $isMultiple"
                x-on:end="reorderItems($el.sortable.toArray())"
            >
                @foreach($items->filter() as $item)
                    <x-filament::grid.column
                        :default="1"
                        class="group relative cursor-pointer"
                        x-sortable-handle
                        x-sortable-item="{{ 'item-' . $item->getKey() }}"
                        x-on:click="openModal"
                    >
                        @if ($previewComponent = $configuration->getPreviewComponent())
                            <x-dynamic-component :component="$previewComponent" :record="$item"/>
                        @else
                            <x-filament::section :compact="true">
                                {{ $configuration->getRecordTitle($item) }}
                            </x-filament::section>
                        @endif

                        <button
                            type="button"
                            class="absolute right-1 top-1 hidden rounded-full bg-white p-1.5 shadow-sm hover:bg-gray-100 group-hover:block"
                            x-on:click="
                                @unless($isMultiple)
                                    state = null
                                @else
                                    state = state.filter((item) => {
                                        let itemId = item

                                        if (Number.isInteger(itemId)) {
                                            itemId = itemId.toString()
                                        }

                                        return itemId !== '{{ $item->getKey() }}'
                                    })
                                @endif
                            "
                        >
                            @svg('heroicon-o-trash', 'h-5 w-5 text-danger-500')
                        </button>
                    </x-filament::grid.column>
                @endforeach
            </x-filament::grid>
        </div>

        @unless ($isDisabled() || (!$isMultiple && filled($state)))
            <div class="mt-4 flex flex-row items-center space-x-4">
                <x-filament::button
                    x-on:click="openModal"
                >
                    Choose
                    {{ $isMultiple ? $configuration->getPluralResourceLabel() : $configuration->getResourceLabel() }}
                </x-filament::button>

                <button
                    type="button"
                    x-on:click.prevent="state = {{ $isMultiple ? '[]' : 'null' }}"
                    x-show="state !== null && (! Array.isArray(state) || state.length > 0)"
                    class="text-base text-gray-400"
                    x-cloak
                >
                    Clear selected
                </button>
            </div>
        @endunless
    </div>
</x-dynamic-component>
