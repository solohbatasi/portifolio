<?php

namespace App\Services;

use App\Enums\CoffeePaymentStatus;
use App\Models\CoffeePayment;
use App\Support\DarajaResultCodeMapper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class DarajaCallbackService
{
    /** @param array<string, mixed> $payload */
    public function handle(array $payload): void
    {
        $callback = data_get($payload, 'Body.stkCallback');
        if (! is_array($callback)) {
            Log::notice('Ignored malformed M-PESA callback.', ['reason' => 'missing_stk_callback']);

            return;
        }

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
        if (! is_string($checkoutRequestId) || $checkoutRequestId === '') {
            Log::notice('Ignored malformed M-PESA callback.', ['reason' => 'missing_checkout_request_id']);

            return;
        }

        DB::transaction(function () use ($callback, $checkoutRequestId): void {
            $payment = CoffeePayment::where('checkout_request_id', $checkoutRequestId)->lockForUpdate()->first();
            if (! $payment) {
                Log::notice('M-PESA callback did not match a payment.', ['checkout_id_hash' => hash('sha256', $checkoutRequestId)]);

                return;
            }
            if ($payment->status === CoffeePaymentStatus::Success) {
                return;
            }

            $resultCode = (string) ($callback['ResultCode'] ?? 'unknown');
            $status = DarajaResultCodeMapper::status($resultCode);
            $metadata = $this->metadata(data_get($callback, 'CallbackMetadata.Item', []));
            $updates = [
                'merchant_request_id' => $this->safe($callback['MerchantRequestID'] ?? $payment->merchant_request_id),
                'result_code' => $resultCode,
                'result_description' => $this->safe($callback['ResultDesc'] ?? null),
                'callback_received_at' => now(),
            ];

            if ($status === CoffeePaymentStatus::Success) {
                $callbackAmount = isset($metadata['Amount']) && is_numeric($metadata['Amount']) ? (int) $metadata['Amount'] : null;
                $updates['callback_amount'] = $callbackAmount;
                if ($callbackAmount === null || $callbackAmount !== $payment->amount) {
                    $updates['status'] = CoffeePaymentStatus::Processing;
                    $updates['reconciliation_warning'] = 'Callback amount did not match the requested amount.';
                    Log::warning('Coffee payment requires amount reconciliation.', [
                        'payment_id' => $payment->public_id,
                        'phone' => $payment->phone_masked,
                        'amount' => $payment->amount,
                        'result_code' => $resultCode,
                    ]);
                } else {
                    $updates['status'] = CoffeePaymentStatus::Success;
                    $updates['mpesa_receipt'] = $this->safe($metadata['MpesaReceiptNumber'] ?? null, 64);
                    $updates['transaction_date'] = $this->transactionDate($metadata['TransactionDate'] ?? null);
                    $updates['completed_at'] = now();
                    $updates['reconciliation_warning'] = null;
                }
            } else {
                $updates['status'] = $status;
                $updates['failed_at'] = now();
            }

            $payment->update($updates);
        });
    }

    /** @return array<string, mixed> */
    private function metadata(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }
        $metadata = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['Name']) && is_string($item['Name'])) {
                $metadata[$item['Name']] = $item['Value'] ?? null;
            }
        }

        return $metadata;
    }

    private function transactionDate(mixed $value): ?CarbonImmutable
    {
        if (! is_scalar($value) || preg_match('/^\d{14}$/', (string) $value) !== 1) {
            return null;
        }

        return CarbonImmutable::createFromFormat('YmdHis', (string) $value, config('daraja.timezone'));
    }

    private function safe(mixed $value, int $length = 255): ?string
    {
        return is_scalar($value) ? Str::limit(strip_tags((string) $value), $length, '') : null;
    }
}
