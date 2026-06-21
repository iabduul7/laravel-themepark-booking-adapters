<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Results;

class Supplier extends Result
{
    /**
     * @return array<string, mixed>
     */
    public function getSupplierData(): array
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

    public function getOctoId(): string
    {
        return (string) $this->get('octoID');
    }
}
