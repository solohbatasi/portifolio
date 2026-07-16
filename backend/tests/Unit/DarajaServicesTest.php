<?php

namespace Tests\Unit;

use App\Exceptions\DarajaException;
use App\Services\DarajaAuthService;
use App\Services\DarajaClient;
use App\Services\DarajaPasswordFactory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DarajaServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set([
            'daraja.base_url' => 'https://daraja.test',
            'daraja.consumer_key' => 'test-consumer-key',
            'daraja.consumer_secret' => 'test-consumer-secret',
            'daraja.shortcode' => '123456',
            'daraja.party_b' => '123456',
            'daraja.passkey' => 'test-passkey',
            'daraja.callback_url' => 'https://portfolio.test/api/mpesa/stk/callback',
            'daraja.transaction_type' => 'CustomerPayBillOnline',
            'daraja.coffee.account_reference' => 'SOLOMON-PORTFOLIO',
            'daraja.coffee.description' => 'Support Solomon Batasi',
            'daraja.timezone' => 'Africa/Nairobi',
        ]);
        Cache::flush();
    }

    public function test_timestamp_and_password_generation(): void
    {
        $result = app(DarajaPasswordFactory::class)->make(CarbonImmutable::create(2026, 7, 15, 12, 34, 56, 'Africa/Nairobi'));
        $this->assertSame('20260715123456', $result['timestamp']);
        $this->assertSame(base64_encode('123456test-passkey20260715123456'), $result['password']);
    }

    public function test_oauth_token_is_requested_with_basic_auth_and_cached(): void
    {
        Http::fake(['https://daraja.test/oauth/*' => Http::response(['access_token' => 'test-token', 'expires_in' => 3599])]);
        $service = app(DarajaAuthService::class);
        $this->assertSame('test-token', $service->token());
        $this->assertSame('test-token', $service->token());
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Basic '.base64_encode('test-consumer-key:test-consumer-secret')));
    }

    public function test_oauth_authentication_failure_is_controlled(): void
    {
        Http::fake(['*' => Http::response(['error' => 'invalid'], 401)]);
        $this->expectException(DarajaException::class);
        app(DarajaAuthService::class)->token();
    }

    public function test_stk_payload_and_configured_transaction_type(): void
    {
        Http::fake([
            'https://daraja.test/oauth/*' => Http::response(['access_token' => 'test-token', 'expires_in' => 3599]),
            'https://daraja.test/mpesa/stkpush/*' => Http::response(['MerchantRequestID' => 'merchant-1', 'CheckoutRequestID' => 'checkout-1']),
        ]);
        app(DarajaClient::class)->initiate('254716933897', 250);
        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/mpesa/stkpush/v1/processrequest')) {
                return false;
            }

            return $request['BusinessShortCode'] === '123456'
                && $request['TransactionType'] === 'CustomerPayBillOnline'
                && $request['PartyB'] === '123456'
                && $request['Amount'] === 250
                && $request['PartyA'] === '254716933897'
                && $request['PhoneNumber'] === '254716933897'
                && $request['CallBackURL'] === 'https://portfolio.test/api/mpesa/stk/callback';
        });
    }

    public function test_till_transaction_type_is_not_guessed_from_shortcode(): void
    {
        config()->set('daraja.transaction_type', 'CustomerBuyGoodsOnline');
        config()->set('daraja.party_b', '654321');
        Http::fake([
            'https://daraja.test/oauth/*' => Http::response(['access_token' => 'test-token']),
            '*' => Http::response(['MerchantRequestID' => 'merchant-1', 'CheckoutRequestID' => 'checkout-1']),
        ]);
        app(DarajaClient::class)->initiate('254716933897', 250);
        Http::assertSent(fn ($request) => ! str_contains($request->url(), '/oauth/')
            && $request['TransactionType'] === 'CustomerBuyGoodsOnline'
            && $request['BusinessShortCode'] === '123456'
            && $request['PartyB'] === '654321');
    }

    public function test_stk_status_query_uses_stored_checkout_identifier_payload(): void
    {
        Http::fake([
            'https://daraja.test/oauth/*' => Http::response(['access_token' => 'test-token']),
            '*' => Http::response(['ResultCode' => '0']),
        ]);
        app(DarajaClient::class)->query('checkout-stored');
        Http::assertSent(fn ($request) => str_contains($request->url(), '/stkpushquery/') && $request['CheckoutRequestID'] === 'checkout-stored');
    }
}
