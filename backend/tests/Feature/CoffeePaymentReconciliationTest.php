<?php

namespace Tests\Feature;

use App\Enums\CoffeePaymentStatus;
use App\Models\CoffeePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoffeePaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set([
            'cache.default' => 'array',
            'daraja.base_url' => 'https://daraja.test',
            'daraja.consumer_key' => 'test-key', 'daraja.consumer_secret' => 'test-secret',
            'daraja.shortcode' => '123456', 'daraja.passkey' => 'test-passkey',
            'daraja.callback_url' => 'https://portfolio.test/api/mpesa/stk/callback',
            'daraja.transaction_type' => 'CustomerPayBillOnline',
            'daraja.coffee.query_after_seconds' => 30, 'daraja.coffee.query_interval_seconds' => 30,
        ]);
    }

    public function test_status_endpoint_queries_eligible_pending_payment_once(): void
    {
        $payment = $this->payment();
        $this->fakeSuccessfulQuery();
        $this->getJson('/api/coffee-payments/'.$payment->public_id)->assertOk()->assertJsonPath('status', 'success');
        $this->getJson('/api/coffee-payments/'.$payment->public_id)->assertOk()->assertJsonPath('status', 'success');
        Http::assertSentCount(2);
    }

    public function test_recent_pending_payment_does_not_query_daraja(): void
    {
        $payment = $this->payment(['initiated_at' => now()]);
        Http::fake();
        $this->getJson('/api/coffee-payments/'.$payment->public_id)->assertOk()->assertJsonPath('status', 'pending');
        Http::assertNothingSent();
    }

    public function test_reconciliation_command_updates_eligible_payment_and_skips_completed(): void
    {
        $pending = $this->payment();
        $this->payment(['status' => CoffeePaymentStatus::Success, 'checkout_request_id' => 'checkout-complete', 'completed_at' => now()]);
        $this->fakeSuccessfulQuery();
        $this->artisan('coffee-payments:reconcile')->assertSuccessful();
        $this->assertSame(CoffeePaymentStatus::Success, $pending->refresh()->status);
        Http::assertSentCount(2);
    }

    public function test_query_failure_never_creates_another_stk_push(): void
    {
        $payment = $this->payment();
        Http::fake([
            'https://daraja.test/oauth/*' => Http::response(['access_token' => 'token']),
            '*' => Http::response(['error' => 'temporary'], 503),
        ]);
        $this->getJson('/api/coffee-payments/'.$payment->public_id)->assertOk()->assertJsonPath('status', 'processing');
        Http::assertSent(fn ($request) => ! str_contains($request->url(), '/processrequest'));
    }

    private function fakeSuccessfulQuery(): void
    {
        Http::fake([
            'https://daraja.test/oauth/*' => Http::response(['access_token' => 'token']),
            'https://daraja.test/mpesa/stkpushquery/*' => Http::response(['ResultCode' => '0', 'ResultDesc' => 'Success']),
        ]);
    }

    private function payment(array $overrides = []): CoffeePayment
    {
        return CoffeePayment::create(array_merge([
            'public_id' => (string) Str::uuid(), 'request_id' => (string) Str::uuid(),
            'reference' => 'COFFEE-'.Str::upper(Str::random(8)), 'amount' => 250,
            'phone_encrypted' => '254716933897', 'phone_hash' => hash('sha256', Str::random()),
            'phone_masked' => '2547****897', 'status' => CoffeePaymentStatus::Pending,
            'merchant_request_id' => 'merchant-test', 'checkout_request_id' => 'checkout-'.Str::random(8),
            'initiated_at' => now()->subMinutes(2),
        ], $overrides));
    }
}
