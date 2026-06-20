<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Results;

class Rate extends Result
{
    /**
     * @return array<string, mixed>
     */
    public function getRateData(): array
    {
        return $this->getData();
    }

    public function getId(): string
    {
        return (string) $this->get('id');
    }

    public function getName(): string
    {
        return (string) $this->get('name');
    }

    public function getCode(): string
    {
        return (string) $this->get('code');
    }

    public function getOptionId(): string
    {
        return (string) $this->get('optionId');
    }

    public function getProductDuration(): int
    {
        return (int) $this->get('ext.disney-productDuration');
    }

    public function getValidFrom(): ?string
    {
        return $this->get('valid.from');
    }

    public function getValidUntil(): ?string
    {
        return $this->get('valid.until');
    }
}
