<?php

namespace Iabduul7\ThemeParkAdapters\Contracts\Capabilities;

/**
 * Direct event/order lifecycle for SmartOrder providers (Universal). Unlike
 * Redeam there is no separate hold step — orders are placed directly after an
 * event capacity check. All payloads are provider-shaped arrays and responses
 * are nullable (the SmartOrder API may return an empty body).
 */
interface SupportsEvents
{
    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>|null
     */
    public function findEvents(array $parameters): ?array;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>|null
     */
    public function placeOrder(array $parameters): ?array;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>|null
     */
    public function getExistingOrder(array $parameters): ?array;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>|null
     */
    public function canCancelOrder(array $parameters): ?array;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>|null
     */
    public function cancelOrder(array $parameters): ?array;
}
