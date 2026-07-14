<?php

declare(strict_types=1);

namespace Portfolio\Support;

final class UpstreamValidationException extends UpstreamException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The payment provider rejected the request.');
    }
}
