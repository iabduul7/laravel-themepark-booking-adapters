<?php

declare(strict_types=1);

namespace iabduul7\ThemeParkBooking\Utils;

/**
 * Sample configuration manager for testing PHPStan type checking.
 */
class ConfigManager
{
    /** @var array<string, mixed> */
    private array $config = [];

    public function __construct()
    {
        $this->config = [
            'api_url' => 'https://api.example.com',
            'timeout' => 30,
            'enabled' => true,
            'features' => ['booking', 'cancellation'],
        ];
    }

    /**
     * Get configuration value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value.
     */
    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Get API URL with proper string return type.
     */
    public function getApiUrl(): string
    {
        return (string) $this->get('api_url', '');
    }

    /**
     * Get timeout as integer.
     */
    public function getTimeout(): int
    {
        return (int) $this->get('timeout', 30);
    }

    /**
     * Check if feature is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) $this->get('enabled', false);
    }

    /**
     * Get available features.
     *
     * @return array<int, string>
     */
    public function getFeatures(): array
    {
        $features = $this->get('features', []);

        return is_array($features) ? $features : [];
    }

    /**
     * Validate configuration.
     *
     * @return array<string, string>
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->getApiUrl())) {
            $errors['api_url'] = 'API URL is required';
        }

        if ($this->getTimeout() <= 0) {
            $errors['timeout'] = 'Timeout must be positive';
        }

        return $errors;
    }
}
