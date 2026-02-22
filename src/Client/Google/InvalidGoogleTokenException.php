<?php

namespace App\Client\Google;

class InvalidGoogleTokenException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'The required Google token was not found.',
            0,
            $previous,
        );
    }
}
