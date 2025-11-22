<?php

namespace CodeCreatives\LaravelSmartOrder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CodeCreatives\LaravelSmartOrder\LaravelSmartOrder
 */
class LaravelSmartOrder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \CodeCreatives\LaravelSmartOrder\LaravelSmartOrder::class;
    }
}
