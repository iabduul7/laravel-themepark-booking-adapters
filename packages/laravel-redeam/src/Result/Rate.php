<?php

namespace CodeCreatives\LaravelRedeam\Result;

class Rate extends Result
{
    public function getRateData()
    {
        return $this->getData();
    }

    public function getId(): string
    {
        return $this->get('id');
    }

    public function getName(): string
    {
        return $this->get('name');
    }

    public function getCode(): string
    {
        return $this->get('code');
    }

    public function getOptionId(): string
    {
        return $this->get('optionId');
    }

    public function getProductDuration(): int
    {
        return $this->get('ext.disney-productDuration');
    }

    public function getValidFrom(): string
    {
        return $this->get('valid.from');
    }

    public function getValidUntil(): string
    {
        return $this->get('valid.until');
    }
}
