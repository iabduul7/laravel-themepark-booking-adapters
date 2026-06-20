<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Collections;

use Iabduul7\ThemeParkAdapters\DataTransferObjects\Results\CatalogEntry;
use Illuminate\Support\Collection;

/**
 * A filterable collection of {@see CatalogEntry} objects.
 *
 * Every predicate keys on a field the provider itself returns, so the filters are
 * generic SmartOrder conveniences — not application-specific taxonomy. They return
 * a new collection, so they chain. Inherited Laravel Collection methods (map,
 * filter, first, where, …) all preserve this type.
 *
 * The notable absence is anything the provider does NOT self-describe — there is
 * deliberately no promo()/hhn() predicate, because "promo"/"HHN" is a consuming-app
 * label (no catalog field expresses it; discount data is present on every entry).
 *
 * @extends Collection<int, CatalogEntry>
 */
class CatalogEntryCollection extends Collection
{
    public function salesProgram(int $salesProgramId): static
    {
        return $this->filter(
            fn (CatalogEntry $entry): bool => $entry->getSalesProgramId() === $salesProgramId
        )->values();
    }

    public function expressPasses(): static
    {
        return $this->filter(fn (CatalogEntry $entry): bool => $entry->isExpressPass())->values();
    }

    public function limitedExpress(): static
    {
        return $this->filter(fn (CatalogEntry $entry): bool => $entry->isLimitedExpress())->values();
    }

    public function unlimitedExpress(): static
    {
        return $this->filter(fn (CatalogEntry $entry): bool => $entry->isUnlimitedExpress())->values();
    }

    public function themeParkAccess(): static
    {
        return $this->filter(fn (CatalogEntry $entry): bool => $entry->isThemeParkAccess())->values();
    }

    public function parkToPark(): static
    {
        return $this->filter(fn (CatalogEntry $entry): bool => $entry->isParkToPark())->values();
    }

    public function epicAccess(): static
    {
        return $this->filter(fn (CatalogEntry $entry): bool => $entry->isEpicAccess())->values();
    }

    public function days(int $numberOfDays): static
    {
        return $this->filter(
            fn (CatalogEntry $entry): bool => $entry->getNumberOfDays() === $numberOfDays
        )->values();
    }

    public function multiDay(): static
    {
        return $this->filter(fn (CatalogEntry $entry): bool => $entry->getNumberOfDays() > 1)->values();
    }

    public function singleDay(): static
    {
        return $this->filter(fn (CatalogEntry $entry): bool => $entry->getNumberOfDays() === 1)->values();
    }

    public function forAge(string $ageValue): static
    {
        return $this->filter(
            fn (CatalogEntry $entry): bool => $entry->getAgeValue() === $ageValue
        )->values();
    }

    public function requiresFindEvents(): static
    {
        return $this->filter(fn (CatalogEntry $entry): bool => $entry->requiresFindEvents())->values();
    }

    /**
     * Escape hatch — the underlying provider payloads, one array per entry. Never
     * hides fields: returns each entry exactly as received (plus the injected
     * salesProgramId/salesProgramName). For the full untouched nested response,
     * call the adapter's getAllProducts() instead.
     *
     * @return array<int, array<string, mixed>>
     */
    public function raw(): array
    {
        return array_values(array_map(
            static fn (CatalogEntry $entry): array => $entry->getData(),
            $this->all(),
        ));
    }
}
