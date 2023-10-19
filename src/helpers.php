<?php

namespace UnexpectedJourney\FilamentResourcePicker\Support;

if (! function_exists('UnexpectedJourney\FilamentResourcePicker\Support\get_resource_identifier')) {
    function get_resource_identifier(string $resource): string
    {
        return (string) str($resource)
            ->remove('\\')
            ->camel();
    }
}
