<?php

namespace App\Support;

use App\Enums\CoffeePaymentStatus;

final class DarajaResultCodeMapper
{
    public static function status(string|int $code): CoffeePaymentStatus
    {
        return match ((string) $code) {
            '0' => CoffeePaymentStatus::Success,
            '1032' => CoffeePaymentStatus::Cancelled,
            '1037', '1031' => CoffeePaymentStatus::Timeout,
            '2001', '1', '17', '26' => CoffeePaymentStatus::Failed,
            default => CoffeePaymentStatus::Failed,
        };
    }
}
