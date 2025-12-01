<?php

namespace iabduul7\ThemeParkBooking\Exceptions;

class AdapterException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}