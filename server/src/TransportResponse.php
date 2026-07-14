<?php

declare(strict_types=1);

namespace Portfolio\Support;

final readonly class TransportResponse
{
    /** @param array<string, mixed> $body */
    public function __construct(public int $statusCode, public array $body)
    {
    }
}
