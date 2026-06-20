<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Results;

/**
 * One Universal/SmartOrder product catalog entry (a single PLU) from
 * MyProductCatalog, enriched with its parent salesProgramId/salesProgramName.
 *
 * Every accessor reads a field the provider itself returns — the entry
 * self-describes its product type via the is* flags, numberOfDays, ageValue and
 * sales program. No application-specific PLU-prefix interpretation lives here;
 * that (grain modelling, "promo"/"HHN" labelling, the curated PLU set) is the
 * consuming app's job. {@see getData()} / toArray() return the untouched payload.
 */
class CatalogEntry extends Result
{
    public function getPlu(): string
    {
        return (string) $this->get('plu');
    }

    public function getProductName(): string
    {
        return (string) $this->get('productName');
    }

    public function getProductKind(): ?string
    {
        return $this->get('productKind');
    }

    public function getNumberOfDays(): int
    {
        return (int) $this->get('numberOfDays', 0);
    }

    /**
     * "A" (adult), "C" (child) or "NA" (not applicable, e.g. express add-ons).
     */
    public function getAgeValue(): ?string
    {
        return $this->get('ageValue');
    }

    public function getResidencyRequirement(): ?string
    {
        return $this->get('residencyRequirement');
    }

    public function isThemeParkAccess(): bool
    {
        return (bool) $this->get('isThemeParkAccess', false);
    }

    public function isParkToPark(): bool
    {
        return (bool) $this->get('isParkToPark', false);
    }

    public function isEpicAccess(): bool
    {
        return (bool) $this->get('isEpicAccess', false);
    }

    public function isLimitedExpress(): bool
    {
        return (bool) $this->get('isLimitedExpress', false);
    }

    public function isUnlimitedExpress(): bool
    {
        return (bool) $this->get('isUnlimitedExpress', false);
    }

    /**
     * Either flavour of Universal Express Pass.
     */
    public function isExpressPass(): bool
    {
        return $this->isLimitedExpress() || $this->isUnlimitedExpress();
    }

    /**
     * @return array<int, string>
     */
    public function getThemeParkAccessNames(): array
    {
        return (array) ($this->get('themeParkAccessNames') ?? []);
    }

    public function getBannerColor(): ?string
    {
        return $this->get('bannerColor');
    }

    public function getUiUsageWindow(): ?int
    {
        $window = $this->get('uiUsageWindow');

        return $window !== null ? (int) $window : null;
    }

    public function requiresFindEvents(): bool
    {
        return (bool) $this->get('requiresFindEvents', false);
    }

    /**
     * @return array<int, mixed>
     */
    public function getAllowedDeliveryMethods(): array
    {
        return (array) ($this->get('allowedDeliveryMethods') ?? []);
    }

    /**
     * Injected from the parent catalogBySalesProgram bucket during parsing.
     */
    public function getSalesProgramId(): ?int
    {
        $id = $this->get('salesProgramId');

        return $id !== null ? (int) $id : null;
    }

    public function getSalesProgramName(): ?string
    {
        return $this->get('salesProgramName');
    }

    /**
     * Price rows for a pricing bucket. $window is "future" or "current";
     * $type is "base" or "discounted". Empty buckets (e.g. when the request
     * narrowed them via retrieveOnly/pricing) return an empty array.
     *
     * @return array<int, PricePoint>
     */
    public function prices(string $window = 'future', string $type = 'base'): array
    {
        $rows = (array) $this->get("{$window}Pricing.{$type}PriceData.pricesByDay", []);

        return array_map(fn (array $row): PricePoint => new PricePoint($row), array_values($rows));
    }

    /**
     * @return array<int, PricePoint>
     */
    public function futureBasePrices(): array
    {
        return $this->prices('future', 'base');
    }

    /**
     * @return array<int, PricePoint>
     */
    public function futureDiscountedPrices(): array
    {
        return $this->prices('future', 'discounted');
    }

    /**
     * @return array<int, PricePoint>
     */
    public function currentBasePrices(): array
    {
        return $this->prices('current', 'base');
    }

    /**
     * @return array<int, PricePoint>
     */
    public function currentDiscountedPrices(): array
    {
        return $this->prices('current', 'discounted');
    }
}
