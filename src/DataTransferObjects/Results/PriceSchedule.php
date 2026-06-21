<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Results;

class PriceSchedule extends Result
{
    /**
     * @return array<string, mixed>
     */
    public function getPriceData(): array
    {
        return $this->getData();
    }

    public function getRatePricingSchedule(string $rateId): RatePriceSchedule
    {
        return new RatePriceSchedule($this->get($rateId, []));
    }

    public function getErrorCode(): mixed
    {
        return $this->get('error.code');
    }

    public function getErrorMessage(): mixed
    {
        return $this->get('error.message');
    }

    public function hasErrorCode(): bool
    {
        return $this->getErrorCode() !== null && $this->getErrorCode() !== '';
    }

    public function isEmpty(): bool
    {
        return empty($this->getData());
    }
}
