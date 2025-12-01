<?php

namespace CodeCreatives\LaravelRedeam\Result;

class PriceSchedule extends Result
{
    public function getPriceData()
    {
        return $this->getData();
    }

    public function getRatePricingSchedule(string $rateId): ?RatePriceSchedule
    {
        return new RatePriceSchedule($this->get($rateId));
    }

    public function getErrorCode()
    {
        return $this->get('error.code');
    }

    public function getErrorMessage()
    {
        return $this->get('error.message');
    }

    public function hasErrorCode()
    {
        return $this->getErrorCode() !== null && $this->getErrorCode() !== '';
    }

    public function isEmpty()
    {
        return empty($this->getData());
    }
}
