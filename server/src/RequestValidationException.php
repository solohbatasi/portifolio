<?php

declare(strict_types=1);

namespace Portfolio\Support;

use RuntimeException;

final class RequestValidationException extends RuntimeException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The request contains invalid fields.');
    }
}
