<?php

namespace App\Services;

use App\Models\CoffeePayment;
use App\Models\Payout;

final class RecordedBalanceService
{
    public function summary(): array
    {
        $inflows = (int) CoffeePayment::where('status', 'success')->sum('amount');
        $paid = (int) Payout::where('status', 'success')->sum('amount');
        $reserved = (int) Payout::whereIn('status', ['initiating', 'pending', 'processing', 'timeout'])->sum('amount');
        $recorded = $inflows - $paid;

        return ['inflows' => $inflows, 'successful_payouts' => $paid, 'recorded' => $recorded, 'reserved' => $reserved, 'available' => max(0, $recorded - $reserved)];
    }
}
