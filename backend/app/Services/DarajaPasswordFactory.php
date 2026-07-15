<?php

namespace App\Services;

use Carbon\CarbonImmutable;

final class DarajaPasswordFactory
{
    /** @return array{timestamp: string, password: string} */
    public function make(?CarbonImmutable $now = null): array
    {
        $timestamp = ($now ?? CarbonImmutable::now(config('daraja.timezone')))
            ->setTimezone(config('daraja.timezone'))
            ->format('YmdHis');

        return [
            'timestamp' => $timestamp,
            'password' => base64_encode(config('daraja.shortcode').config('daraja.passkey').$timestamp),
        ];
    }
}
