<?php

declare(strict_types=1);

namespace iabduul7\ThemeParkBooking\Utils;

/**
 * Simple test helper class to validate PHPStan configuration.
 */
class TestHelper
{
    public function __construct(
        private readonly string $name,
        private readonly int $value
    ) {}

    /**
     * Get the name property.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the value property.
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Calculate sum with proper type checking.
     */
    public function calculateSum(int $a, int $b): int
    {
        return $a + $b;
    }

    /**
     * Calculate sum with proper type checking.
     */
    public function calculateSubtraction(int $a, int $b): int
    {
        return $a - $b;
    }

    /**
     * Process array data with proper typing.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function processData(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = (string) $value;
        }

        return $result;
    }

    /**
     * Get configuration with null handling.
     */
    public function getConfig(?string $key = null): ?string
    {
        if ($key === null) {
            return null;
        }

        return "config_$key";
    }
}
