<?php

declare(strict_types=1);

namespace Portfolio\Support;

interface RateLimiter
{
    public function allow(string $key, int $maximumAttempts, int $windowSeconds): bool;
}
