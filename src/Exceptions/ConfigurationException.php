<?php

namespace iabduul7\ThemeParkBooking\Exceptions;

class ConfigurationException extends AdapterException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Configuration Error: {$message}", $code, $previous);
    }
}
