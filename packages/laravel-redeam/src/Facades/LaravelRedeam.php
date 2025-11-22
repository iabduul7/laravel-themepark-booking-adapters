<?php

namespace CodeCreatives\LaravelRedeam\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CodeCreatives\LaravelRedeam\LaravelRedeamForWaltDisney
 */
class LaravelRedeam extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \CodeCreatives\LaravelRedeam\LaravelRedeamForWaltDisney::class;
    }
}
