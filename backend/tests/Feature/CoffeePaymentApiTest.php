<?php

namespace Tests\Feature;

use App\Enums\CoffeePaymentStatus;
use App\Models\CoffeePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoffeePaymentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set([
            'cache.default' => 'array',
            'daraja.base_url' => 'https://daraja.test',
            'daraja.consumer_key' => 'test-key',
            'daraja.consumer_secret' => 'test-secret',
            'daraja.shortcode' => '123456',
            'daraja.party_b' => '123456',
            'daraja.passkey' => 'test-passkey',
            'daraja.callback_url' => 'https://portfolio.test/api/mpesa/stk/callback',
            'daraja.transaction_type' => 'CustomerPayBillOnline',
            'daraja.coffee.minimum' => 50,
            'daraja.coffee.maximum' => 10000,
        ]);
    }

    public function test_amount_and_uuid_validation(): void
    {
        $this->postJson('/api/coffee-payments', ['phone' => '0716933897', 'amount' => 49, 'request_id' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['amount', 'request_id']);
        $this->postJson('/api/coffee-payments', ['phone' => '0716933897', 'amount' => 10001, 'request_id' => (string) Str::uuid()])
            ->assertUnprocessable()->assertJsonValidationErrors(['amount']);
    }

    public function test_successful_stk_initiation_is_pending_and_sanitised(): void
    {
        $this->fakeSuccessfulInitiation();
        $response = $this->postJson('/api/coffee-payments', $this->payload())->assertAccepted();
        $response->assertJsonStructure(['payment_id', 'status', 'amount', 'phone', 'message'])
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('phone', '2547****897');
        $this->assertCount(5, $response->json());
        $payment = CoffeePayment::firstOrFail();
        $this->assertNotSame('254716933897', $payment->getRawOriginal('phone_encrypted'));
        $this->assertSame('254716933897', $payment->phone_encrypted);
    }

    public function test_duplicate_request_id_returns_existing_payment_without_second_stk_push(): void
    {
        $this->fakeSuccessfulInitiation();
        $payload = $this->payload();
        $first = $this->postJson('/api/coffee-payments', $payload)->assertAccepted()->json('payment_id');
        $second = $this->postJson('/api/coffee-payments', $payload)->assertOk()->json('payment_id');
        $this->assertSame($first, $second);
        $this->assertDatabaseCount('coffee_payments', 1);
        Http::assertSentCount(2);
    }

    public function test_daraja_validation_failure_is_safe(): void
    {
        Http::fake([
            'https://daraja.test/oauth/*' => Http::response(['access_token' => 'token']),
            '*' => Http::response(['errorMessage' => 'Internal Daraja detail'], 400),
        ]);
        $response = $this->postJson('/api/coffee-payments', $this->payload())->assertAccepted();
        $response->assertJsonPath('status', 'failed');
        $this->assertStringNotContainsString('Internal', $response->getContent());
    }

    public function test_uncertain_connection_does_not_retry_or_create_another_record(): void
    {
        Cache::put('daraja.oauth.token', 'test-token', 300);
        Http::fake(['*' => Http::failedConnection('timeout')]);
        $payload = $this->payload();
        $this->postJson('/api/coffee-payments', $payload)->assertAccepted()->assertJsonPath('status', 'processing');
        $this->postJson('/api/coffee-payments', $payload)->assertOk()->assertJsonPath('status', 'processing');
        $this->assertDatabaseCount('coffee_payments', 1);
        Http::assertSentCount(1);
    }

    public function test_status_endpoint_exposes_no_internal_daraja_identifiers_or_secret_values(): void
    {
        $payment = $this->payment(['status' => CoffeePaymentStatus::Pending]);
        $response = $this->getJson('/api/coffee-payments/'.$payment->public_id)->assertOk();
        $this->assertSame(['payment_id', 'status', 'amount', 'phone', 'message'], array_keys($response->json()));
        $this->assertStringNotContainsString('checkout-test', $response->getContent());
        $this->assertStringNotContainsString('test-secret', $response->getContent());
    }

    public function test_initiation_rate_limit_is_active(): void
    {
        $this->fakeSuccessfulInitiation();
        foreach (range(1, 5) as $index) {
            $this->postJson('/api/coffee-payments', $this->payload((string) Str::uuid(), sprintf('071600%04d', $index)))->assertAccepted();
        }
        $this->postJson('/api/coffee-payments', $this->payload((string) Str::uuid(), '0716000099'))->assertTooManyRequests();
    }

    public function test_phone_hash_rate_limit_is_active(): void
    {
        $this->fakeSuccessfulInitiation();
        foreach (range(1, 3) as $index) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$index])
                ->postJson('/api/coffee-payments', $this->payload())->assertAccepted();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.99'])
            ->postJson('/api/coffee-payments', $this->payload())->assertTooManyRequests();
    }

    private function fakeSuccessfulInitiation(): void
    {
        $sequence = 0;
        Http::fake(function ($request) use (&$sequence) {
            if (str_contains($request->url(), '/oauth/')) {
                return Http::response(['access_token' => 'token', 'expires_in' => 3599]);
            }
            $sequence++;

            return Http::response([
                'MerchantRequestID' => 'merchant-test-'.$sequence, 'CheckoutRequestID' => 'checkout-test-'.$sequence,
                'ResponseCode' => '0', 'ResponseDescription' => 'Success', 'CustomerMessage' => 'Prompt sent',
            ]);
        });
    }

    private function payload(?string $requestId = null, string $phone = '0716933897'): array
    {
        return ['phone' => $phone, 'amount' => 250, 'request_id' => $requestId ?? (string) Str::uuid()];
    }

    private function payment(array $overrides = []): CoffeePayment
    {
        return CoffeePayment::create(array_merge([
            'public_id' => (string) Str::uuid(), 'request_id' => (string) Str::uuid(),
            'reference' => 'COFFEE-'.Str::upper(Str::random(8)), 'amount' => 250,
            'phone_encrypted' => '254716933897', 'phone_hash' => hash('sha256', Str::random()),
            'phone_masked' => '2547****897', 'status' => CoffeePaymentStatus::Pending,
            'merchant_request_id' => 'merchant-test', 'checkout_request_id' => 'checkout-test',
            'initiated_at' => now(),
        ], $overrides));
    }
}
