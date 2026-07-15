<?php

declare(strict_types=1);

namespace Portfolio\Support;

use RuntimeException;

class UpstreamException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly string $reasonCode = 'upstream_error',
    ) {
        parent::__construct($message);
    }
}
