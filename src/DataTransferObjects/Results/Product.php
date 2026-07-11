<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Results;

class Product extends Result
{
    public function getId(): string
    {
        return (string) $this->get('id');
    }

    public function getName(): string
    {
        return (string) $this->get('name');
    }

    /**
     * Disney exposes the product type under extensions.disney-productType.
     * United Parks/SeaWorld categorise under extensions.beta-categories — read
     * that directly via get() when needed.
     */
    public function getProductType(): ?string
    {
        return $this->get('extensions.disney-productType');
    }

    public function getProductSupplierId(): ?string
    {
        return $this->get('supplierId');
    }

    /**
     * @return array<string, mixed>
     */
    public function getProductData(): array
    {
        return $this->getData();
    }

    /**
     * Fetch this product's rates by delegating back to the originating adapter.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<int, Rate>
     */
    public function getRates(array $parameters = []): array
    {
        if ($this->adapter !== null && method_exists($this->adapter, 'ratesFor')) {
            return $this->adapter->ratesFor($this, $parameters);
        }

        if ($this->adapter !== null && method_exists($this->adapter, 'getProductRates')) {
            return $this->adapter->getProductRates($this->getId(), $parameters);
        }

        return [];
    }
}
