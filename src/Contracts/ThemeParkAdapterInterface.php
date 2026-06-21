<?php

namespace Iabduul7\ThemeParkAdapters\Contracts;

/**
 * Minimal contract every theme-park provider adapter satisfies.
 *
 * Provider-specific operations (product catalog, rates, availability, pricing
 * schedules and holds/bookings for Redeam; events/orders for SmartOrder)
 * intentionally live on the concrete adapters and the capability interfaces
 * (SupportsHolds, SupportsEvents). Their signatures differ per provider — Disney
 * keys by a configured supplier, SeaWorld/United Parks takes supplier_id per call,
 * SmartOrder has no holds at all — so forcing them into one interface would be
 * lossy and break drop-in compatibility with the upstream clients.
 *
 * A cleaner, fully-normalised booking interface is proposed for a future major
 * version in guides/CLEANER_API_REFERENCE.md.
 */
interface ThemeParkAdapterInterface
{
    /**
     * Short provider/park identifier (e.g. "disney", "seaworld", "universal").
     */
    public function getProviderName(): string;

    /**
     * Verify the configured credentials can reach the provider.
     */
    public function validateCredentials(): bool;
}
