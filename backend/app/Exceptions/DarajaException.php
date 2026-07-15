<?php

namespace App\Exceptions;

use RuntimeException;

class DarajaException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $safeCode = 'daraja_error',
        public readonly ?int $upstreamStatus = null,
        public readonly bool $uncertain = false,
    ) {
        parent::__construct($message);
    }
}
