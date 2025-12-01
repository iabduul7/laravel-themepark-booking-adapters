<?php

namespace iabduul7\ThemeParkBooking\Exceptions;

class BookingException extends AdapterException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Booking Error: {$message}", $code, $previous);
    }
}
