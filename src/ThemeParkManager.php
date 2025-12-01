<?php

namespace Iabduul7\ThemeParkAdapters;

use Iabduul7\ThemeParkAdapters\Contracts\ThemeParkAdapterInterface;
use Iabduul7\ThemeParkAdapters\Providers\Disney\DisneyRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Providers\SeaWorld\SeaWorldRedeamAdapter;
use Iabduul7\ThemeParkAdapters\Providers\Universal\UniversalSmartOrder2Adapter;
use Illuminate\Support\Manager;

class ThemeParkManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('themepark-adapters.default', 'disney');
    }

    /**
     * Create an instance of the Disney driver.
     *
     * @return ThemeParkAdapterInterface
     */
    public function createDisneyDriver(): ThemeParkAdapterInterface
    {
        $config = $this->config->get('themepark-adapters.providers.disney', []);

        return new DisneyRedeamAdapter($config);
    }

    /**
     * Create an instance of the SeaWorld driver.
     *
     * @return ThemeParkAdapterInterface
     */
    public function createSeaworldDriver(): ThemeParkAdapterInterface
    {
        $config = $this->config->get('themepark-adapters.providers.seaworld', []);

        return new SeaWorldRedeamAdapter($config);
    }

    /**
     * Create an instance of the Universal driver.
     *
     * @return ThemeParkAdapterInterface
     */
    public function createUniversalDriver(): ThemeParkAdapterInterface
    {
        $config = $this->config->get('themepark-adapters.providers.universal', []);

        return new UniversalSmartOrder2Adapter($config);
    }

    /**
     * Get a provider instance by name.
     *
     * @param string|null $name
     * @return ThemeParkAdapterInterface
     */
    public function provider(?string $name = null): ThemeParkAdapterInterface
    {
        return $this->driver($name);
    }
}
