<?php

namespace App\Services;

use App\Enums\CoffeePaymentStatus;
use App\Exceptions\DarajaException;
use App\Models\CoffeePayment;
use App\Support\KenyanPhoneNumber;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CoffeePaymentService
{
    public function __construct(private readonly DarajaClient $daraja) {}

    public function create(string $requestId, string $phone, int $amount): CoffeePayment
    {
        if ($existing = CoffeePayment::where('request_id', $requestId)->first()) {
            return $existing;
        }

        $normalized = KenyanPhoneNumber::normalize($phone);
        try {
            $payment = CoffeePayment::create([
                'public_id' => (string) Str::uuid(),
                'request_id' => $requestId,
                'reference' => 'COFFEE-'.strtoupper(substr(hash('sha256', $requestId), 0, 8)),
                'amount' => $amount,
                'phone_encrypted' => $normalized,
                'phone_hash' => KenyanPhoneNumber::hash($normalized),
                'phone_masked' => KenyanPhoneNumber::mask($normalized),
                'status' => CoffeePaymentStatus::Created,
            ]);
        } catch (QueryException $exception) {
            if ($existing = CoffeePayment::where('request_id', $requestId)->first()) {
                return $existing;
            }
            throw $exception;
        }

        $payment->update(['status' => CoffeePaymentStatus::Initiating]);

        try {
            $response = $this->daraja->initiate($normalized, $amount);
            $merchantRequestId = $response['MerchantRequestID'] ?? null;
            $checkoutRequestId = $response['CheckoutRequestID'] ?? null;
            if (! is_string($merchantRequestId) || ! is_string($checkoutRequestId) || $checkoutRequestId === '') {
                throw new DarajaException('Daraja returned an incomplete STK response.', 'stk_incomplete_response', null, true);
            }

            $payment->update([
                'status' => CoffeePaymentStatus::Pending,
                'merchant_request_id' => $merchantRequestId,
                'checkout_request_id' => $checkoutRequestId,
                'response_code' => $this->safeString($response['ResponseCode'] ?? null, 20),
                'response_description' => $this->safeString($response['ResponseDescription'] ?? null),
                'customer_message' => $this->safeString($response['CustomerMessage'] ?? null),
                'initiated_at' => now(),
            ]);
        } catch (DarajaException $exception) {
            $payment->update([
                'status' => $exception->uncertain ? CoffeePaymentStatus::Processing : CoffeePaymentStatus::Failed,
                'failed_at' => $exception->uncertain ? null : now(),
                'reconciliation_warning' => $exception->uncertain ? 'STK initiation outcome requires reconciliation.' : null,
            ]);
            Log::warning('Coffee payment initiation failed.', [
                'payment_id' => $payment->public_id,
                'phone' => $payment->phone_masked,
                'amount' => $payment->amount,
                'status' => $payment->status->value,
                'reason' => $exception->safeCode,
                'upstream_status' => $exception->upstreamStatus,
            ]);
        }

        return $payment->refresh();
    }

    private function safeString(mixed $value, int $length = 255): ?string
    {
        return is_scalar($value) ? Str::limit(strip_tags((string) $value), $length, '') : null;
    }
}
