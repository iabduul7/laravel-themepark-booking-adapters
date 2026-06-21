<?php

namespace Iabduul7\ThemeParkAdapters\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Iabduul7\ThemeParkAdapters\Contracts\ThemeParkAdapterInterface provider(?string $name = null)
 * @method static \Iabduul7\ThemeParkAdapters\Contracts\ThemeParkAdapterInterface driver(?string $driver = null)
 * @method static \Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter createDisneyDriver()
 * @method static \Iabduul7\ThemeParkAdapters\Providers\SeaWorld\SeaWorldRedeamAdapter createSeaworldDriver()
 * @method static \Iabduul7\ThemeParkAdapters\Providers\Universal\UniversalSmartOrder2Adapter createUniversalDriver()
 * @method static string getDefaultDriver()
 *
 * Note: each resolved provider exposes its own (drop-in compatible) method
 * surface — see the concrete adapter classes and the SupportsHolds / SupportsEvents
 * capability interfaces.
 *
 * @see \Iabduul7\ThemeParkAdapters\ThemeParkManager
 */
class ThemePark extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'themepark';
    }
}
