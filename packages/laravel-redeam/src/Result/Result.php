<?php

namespace CodeCreatives\LaravelRedeam\Result;

use Illuminate\Support\Arr;

abstract class Result
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function get(string $key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }
}
