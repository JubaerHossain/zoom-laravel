<?php

namespace Jubaer\Zoom\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jubaer\Zoom\Zoom
 */
class Zoom extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'zoom';
    }
}
