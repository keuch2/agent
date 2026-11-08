<?php

namespace App\Domain\Channels\Exceptions;

use RuntimeException;

final class MetaApiException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $retryable = false)
    {
        parent::__construct($message);
    }
}
