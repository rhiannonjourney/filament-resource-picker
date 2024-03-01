<div
    x-data="{
            reorderItems(order) {
                $store.resourceBrowser.resources['{{ $configuration->getAlpineStoreId() }}'].selectedItemIds = order.map(function (item) {
                    return item.split('item-')[1]
                })
            }
        }"
    x-init="
        $watch('$store.resourceBrowser.resources.{{ $configuration->getAlpineStoreId() }}.selectedItemId', (value) => {
            $wire.set('selectedItemId', value )
        })

        $watch('$store.resourceBrowser.resources.{{ $configuration->getAlpineStoreId() }}.selectedItemIds', (value) => {
            $wire.set('selectedItemIds', value)
        })

        $watch('$store.resourceBrowser.resources.{{ $configuration->getAlpineStoreId() }}.isSortable', (value) => {
            $wire.set('isSortable', value)
        })

        $watch('$store.resourceBrowser.resources.{{ $configuration->getAlpineStoreId() }}.configuration', (value) => {
            $wire.set('configurationIdentifier', value)
        })
    "
    x-on:open-modal.window="
            if ($event.detail.id !== '{{ $configuration->getModalId() }}') {
                return
            }

            $wire.resetTable();
        "
    class="relative flex flex-row space-x-4 px-0.5 pb-0.5"
>
    <div class="h-full grow space-y-4">
        <h2 class="text-xl font-bold tracking-tight sticky top-0 bg-white z-10 dark:bg-gray-900">
            {{ str($configuration->getPluralResourceLabel())->title() }}
        </h2>

        <div class="space-y-6">
            {{ \Filament\Support\Facades\FilamentView::renderHook('resource-picker::resource-browser.table.before', scopes: $this->getRenderHookScopes()) }}

            {{ $this->table }}
        </div>
    </div>
    <div class="aside w-[300px] space-y-4">
        <h2 class="text-xl font-bold tracking-tight sticky top-0 bg-white z-10 dark:bg-gray-900">
            Selected
            @if($totalSelectedCount > 0)
                ({{ $totalSelectedCount }})
            @endif
        </h2>

        <div class="mt-4 space-y-2">
            <div>
                {{ $this->form }}
            </div>

            <div class="space-y-2"
                 {{ $isSortable ? 'x-sortable' : '' }}
                 x-on:end="reorderItems($el.sortable.toArray())"
            >
                @forelse($selectedItems as $item)
                    <div
                        class="relative group"
                        x-sortable-handle
                        x-sortable-item="{{ 'item-' . $item->getKey() }}"
                    >
                        @if(filled($previewComponent = $configuration->getPreviewComponent()))
                            <x-dynamic-component :component="$previewComponent" :record="$item"/>
                        @else
                            <x-filament::section :compact="true">
                                {{ $configuration->getRecordTitle($item) }}
                            </x-filament::section>
                        @endif

                        <button
                            type="button"
                            class="absolute right-1 top-1 hidden rounded-full bg-white p-1.5 shadow-sm hover:bg-gray-100 group-hover:block"
                            x-on:click="$store.resourceBrowser.toggleItemSelection('{{ $item->getKey() }}', '{{ $configuration->getAlpineStoreId() }}')"
                        >
                            @svg('heroicon-o-trash', 'h-5 w-5 text-danger-500')
                        </button>
                    </div>
                @empty
                    <p>Nothing selected</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
