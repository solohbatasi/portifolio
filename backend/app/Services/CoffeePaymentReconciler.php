<?php

namespace App\Services;

use App\Enums\CoffeePaymentStatus;
use App\Exceptions\DarajaException;
use App\Models\CoffeePayment;
use App\Support\DarajaResultCodeMapper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class CoffeePaymentReconciler
{
    public function __construct(private readonly DarajaClient $daraja) {}

    public function eligible(CoffeePayment $payment): bool
    {
        if ($payment->status->isTerminal() || ! $payment->checkout_request_id || ! $payment->initiated_at) {
            return false;
        }
        $queryAfter = (int) config('daraja.coffee.query_after_seconds');
        $queryInterval = (int) config('daraja.coffee.query_interval_seconds');

        return $payment->initiated_at->lte(now()->subSeconds($queryAfter))
            && (! $payment->last_status_query_at || $payment->last_status_query_at->lte(now()->subSeconds($queryInterval)));
    }

    public function refresh(CoffeePayment $payment): CoffeePayment
    {
        if (! $this->eligible($payment)) {
            return $payment;
        }

        $lock = Cache::lock('coffee-query:'.$payment->public_id, 20);
        if (! $lock->get()) {
            return $payment->refresh();
        }

        try {
            $payment->update(['last_status_query_at' => now(), 'status' => CoffeePaymentStatus::Processing]);
            $result = $this->daraja->query($payment->checkout_request_id);
            if (array_key_exists('ResultCode', $result)) {
                $status = DarajaResultCodeMapper::status((string) $result['ResultCode']);
                $payment->update([
                    'status' => $status,
                    'result_code' => (string) $result['ResultCode'],
                    'result_description' => is_scalar($result['ResultDesc'] ?? null) ? substr(strip_tags((string) $result['ResultDesc']), 0, 255) : null,
                    'completed_at' => $status === CoffeePaymentStatus::Success ? now() : null,
                    'failed_at' => $status !== CoffeePaymentStatus::Success && $status->isTerminal() ? now() : null,
                ]);
            }
        } catch (DarajaException $exception) {
            Log::notice('Coffee payment status query deferred.', [
                'payment_id' => $payment->public_id,
                'phone' => $payment->phone_masked,
                'status' => $payment->status->value,
                'reason' => $exception->safeCode,
            ]);
        } finally {
            $lock->release();
        }

        return $payment->refresh();
    }
}
