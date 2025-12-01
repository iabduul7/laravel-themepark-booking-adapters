<?php

namespace CodeCreatives\LaravelRedeam\Result;

use CodeCreatives\LaravelRedeam\Facades\LaravelRedeam;

class Product extends Result
{
    public function getName(): string
    {
        return $this->get('name');
    }

    public function getProductType(): string
    {
        return $this->get('extensions.disney-productType');
    }

    public function getProductSupplierId(): string
    {
        return $this->get('supplierId');
    }

    public function getProductData()
    {
        return $this->getData();
    }

    public function getRates($parameters = [])
    {
        return LaravelRedeam::getProductRates($this->getId(), $parameters);
    }

    public function getId(): string
    {
        return $this->get('id');
    }
}
