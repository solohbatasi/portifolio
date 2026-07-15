<?php

namespace App\Console\Commands;

use App\Enums\CoffeePaymentStatus;
use App\Models\CoffeePayment;
use App\Services\CoffeePaymentReconciler;
use Illuminate\Console\Command;

class ReconcileCoffeePayments extends Command
{
    protected $signature = 'coffee-payments:reconcile {--limit=50}';

    protected $description = 'Reconcile eligible pending M-PESA coffee payments';

    public function handle(CoffeePaymentReconciler $reconciler): int
    {
        $count = 0;
        CoffeePayment::query()
            ->whereIn('status', [CoffeePaymentStatus::Pending->value, CoffeePaymentStatus::Processing->value])
            ->oldest('initiated_at')
            ->limit(max(1, min(200, (int) $this->option('limit'))))
            ->get()
            ->each(function (CoffeePayment $payment) use ($reconciler, &$count): void {
                if ($reconciler->eligible($payment)) {
                    $updated = $reconciler->refresh($payment);
                    $this->line("{$updated->public_id} {$updated->phone_masked} {$updated->status->value}");
                    $count++;
                }
            });

        $this->info("Reconciled {$count} eligible payment(s).");

        return self::SUCCESS;
    }
}
