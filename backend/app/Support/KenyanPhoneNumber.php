<?php

namespace App\Support;

use InvalidArgumentException;

final class KenyanPhoneNumber
{
    public static function normalize(string $phone): string
    {
        $clean = preg_replace('/[\s()\-]+/', '', trim($phone));
        if (! is_string($clean)) {
            throw new InvalidArgumentException('Invalid phone number.');
        }

        if (preg_match('/^0([17]\d{8})$/', $clean, $matches) === 1) {
            return '254'.$matches[1];
        }

        if (preg_match('/^\+?254([17]\d{8})$/', $clean, $matches) === 1) {
            return '254'.$matches[1];
        }

        throw new InvalidArgumentException('Invalid Kenyan mobile number.');
    }

    public static function mask(string $normalized): string
    {
        return substr($normalized, 0, 4).'****'.substr($normalized, -3);
    }

    public static function hash(string $normalized): string
    {
        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }
}
