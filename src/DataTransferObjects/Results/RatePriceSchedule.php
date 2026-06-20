<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Results;

class RatePriceSchedule extends Result
{
    /**
     * @return array<string, mixed>
     */
    public function getPriceData(): array
    {
        return $this->getData();
    }
}
