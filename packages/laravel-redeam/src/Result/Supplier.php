<?php

namespace CodeCreatives\LaravelRedeam\Result;

class Supplier extends Result
{
    public function getSupplierData()
    {
        return $this->getData();
    }

    public function getName(): string
    {
        return $this->get('name');
    }

    public function getCode(): string
    {
        return $this->get('code');
    }

    public function getOctoId(): string
    {
        return $this->get('octoID');
    }

    public function getId(): string
    {
        return $this->get('id');
    }
}
