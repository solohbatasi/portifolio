<?php

namespace Tests\Feature;

use App\Enums\CoffeePaymentStatus;
use App\Models\CoffeePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DarajaCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_callback_parses_metadata_by_name_and_is_idempotent(): void
    {
        $payment = $this->payment();
        $payload = $this->callbackPayload(0, [
            ['Name' => 'PhoneNumber', 'Value' => 254716933897],
            ['Name' => 'TransactionDate', 'Value' => 20260715123000],
            ['Name' => 'Amount', 'Value' => 250],
            ['Name' => 'MpesaReceiptNumber', 'Value' => 'TESTRECEIPT'],
        ]);
        $this->postJson('/api/mpesa/stk/callback', $payload)->assertOk();
        $this->postJson('/api/mpesa/stk/callback', $payload)->assertOk();
        $payment->refresh();
        $this->assertSame(CoffeePaymentStatus::Success, $payment->status);
        $this->assertSame('TESTRECEIPT', $payment->mpesa_receipt);
        $this->assertNotNull($payment->completed_at);
    }

    public function test_amount_mismatch_is_not_marked_successful(): void
    {
        $payment = $this->payment();
        $this->postJson('/api/mpesa/stk/callback', $this->callbackPayload(0, [['Name' => 'Amount', 'Value' => 100]]))->assertOk();
        $payment->refresh();
        $this->assertSame(CoffeePaymentStatus::Processing, $payment->status);
        $this->assertNotNull($payment->reconciliation_warning);
    }

    public function test_missing_success_metadata_is_handled_safely(): void
    {
        $payment = $this->payment();
        $this->postJson('/api/mpesa/stk/callback', $this->callbackPayload(0, []))->assertOk();
        $this->assertSame(CoffeePaymentStatus::Processing, $payment->refresh()->status);
    }

    public function test_nonzero_callback_codes_map_to_terminal_statuses(): void
    {
        foreach ([1032 => CoffeePaymentStatus::Cancelled, 1037 => CoffeePaymentStatus::Timeout, 2001 => CoffeePaymentStatus::Failed, 9999 => CoffeePaymentStatus::Failed] as $code => $status) {
            $payment = $this->payment('checkout-'.$code);
            $payload = $this->callbackPayload($code, [], 'checkout-'.$code);
            $this->postJson('/api/mpesa/stk/callback', $payload)->assertOk();
            $this->assertSame($status, $payment->refresh()->status);
        }
    }

    public function test_unexpected_callback_structure_always_receives_safe_acknowledgement(): void
    {
        $this->postJson('/api/mpesa/stk/callback', ['unexpected' => true])
            ->assertOk()->assertExactJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    private function payment(string $checkout = 'checkout-test'): CoffeePayment
    {
        return CoffeePayment::create([
            'public_id' => (string) Str::uuid(), 'request_id' => (string) Str::uuid(),
            'reference' => 'COFFEE-'.Str::upper(Str::random(8)), 'amount' => 250,
            'phone_encrypted' => '254716933897', 'phone_hash' => hash('sha256', Str::random()),
            'phone_masked' => '2547****897', 'status' => CoffeePaymentStatus::Pending,
            'merchant_request_id' => 'merchant-test', 'checkout_request_id' => $checkout, 'initiated_at' => now(),
        ]);
    }

    private function callbackPayload(int $code, array $items, string $checkout = 'checkout-test'): array
    {
        return ['Body' => ['stkCallback' => [
            'MerchantRequestID' => 'merchant-test', 'CheckoutRequestID' => $checkout,
            'ResultCode' => $code, 'ResultDesc' => 'Safe test result',
            'CallbackMetadata' => ['Item' => $items],
        ]]];
    }
}
