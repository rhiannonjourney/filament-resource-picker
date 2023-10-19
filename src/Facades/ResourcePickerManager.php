<?php

namespace UnexpectedJourney\FilamentResourcePicker\Facades;

use Illuminate\Support\Facades\Facade;

class ResourcePickerManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \UnexpectedJourney\FilamentResourcePicker\Support\ResourcePickerManager::class;
    }
}
