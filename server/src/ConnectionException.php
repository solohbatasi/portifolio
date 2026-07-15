<?php

declare(strict_types=1);

namespace Portfolio\Support;

use RuntimeException;

final class ConnectionException extends RuntimeException
{
    public function __construct(string $message, public readonly string $reasonCode = 'connection_error')
    {
        parent::__construct($message);
    }
}
