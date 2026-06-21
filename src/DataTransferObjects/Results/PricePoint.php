<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Results;

/**
 * A single SmartOrder price row from a catalog entry's pricesByDay array.
 *
 * Normalises both shapes the MyProductCatalog endpoint returns:
 *  - per-day (default): one row per date, {@see getDate()} set, {@see getEndDate()} null.
 *  - collapsed (collapseDates=true): contiguous equal-price days fold into one row
 *    spanning getDate() .. getEndDate(). {@see isRange()} distinguishes the two.
 *
 * Tax is provider-shaped (tax1 + tax2); discount metadata is only present on rows
 * drawn from the discountedPriceData bucket.
 */
class PricePoint extends Result
{
    public function getDate(): ?string
    {
        return $this->get('pricingDateTime');
    }

    /**
     * The inclusive end of a collapsed price range; null for per-day rows.
     */
    public function getEndDate(): ?string
    {
        return $this->get('pricingRangeEndDateTime');
    }

    /**
     * True when this row was returned with collapseDates and spans a date range
     * rather than a single day.
     */
    public function isRange(): bool
    {
        return $this->getEndDate() !== null;
    }

    public function getPrice(): float
    {
        return (float) $this->get('priceWithTax.price', 0);
    }

    public function getTax1(): float
    {
        return (float) $this->get('priceWithTax.tax1', 0);
    }

    public function getTax2(): float
    {
        return (float) $this->get('priceWithTax.tax2', 0);
    }

    /**
     * Combined tax (tax1 + tax2). The provider exposes the two components
     * separately; most consumers want the sum.
     */
    public function getTax(): float
    {
        return $this->getTax1() + $this->getTax2();
    }

    public function getTotal(): float
    {
        return (float) $this->get('priceWithTax.totalPriceWithTax', 0);
    }

    public function getDiscountType(): ?int
    {
        $type = $this->get('priceWithTax.discountType');

        return $type !== null ? (int) $type : null;
    }

    public function getDiscountAmount(): ?float
    {
        $amount = $this->get('priceWithTax.discountAmount');

        return $amount !== null ? (float) $amount : null;
    }

    public function isDiscounted(): bool
    {
        return $this->getDiscountType() !== null;
    }
}
