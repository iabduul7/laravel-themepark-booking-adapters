<?php

namespace Iabduul7\ThemeParkAdapters\Exceptions;

use Exception;

class ThemeParkApiException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    public static function invalidCredentials(): self
    {
        return new self('Invalid API credentials provided.');
    }

    public static function productNotFound(string $productId): self
    {
        return new self("Product with ID '{$productId}' not found.", 404);
    }

    public static function orderNotFound(string $orderId): self
    {
        return new self("Order with ID '{$orderId}' not found.", 404);
    }

    /**
     * @param  array<string, mixed>  $responseData
     */
    public static function requestFailed(int $status, array $responseData = []): self
    {
        return new self("ThemePark API request failed with HTTP {$status}.", $status, null, $responseData);
    }

    public static function apiError(string $message, array $responseData = []): self
    {
        return new self($message, 0, null, $responseData);
    }
}
