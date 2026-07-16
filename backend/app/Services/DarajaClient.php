<?php

namespace App\Services;

use App\Exceptions\DarajaException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class DarajaClient
{
    public function __construct(
        private readonly DarajaAuthService $auth,
        private readonly DarajaPasswordFactory $passwords,
    ) {}

    /** @return array<string, mixed> */
    public function initiate(string $phone, int $amount): array
    {
        $credentials = $this->passwords->make();
        $payload = [
            'BusinessShortCode' => config('daraja.shortcode'),
            'Password' => $credentials['password'],
            'Timestamp' => $credentials['timestamp'],
            'TransactionType' => config('daraja.transaction_type'),
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => config('daraja.party_b'),
            'PhoneNumber' => $phone,
            'CallBackURL' => config('daraja.callback_url'),
            'AccountReference' => config('daraja.coffee.account_reference'),
            'TransactionDesc' => config('daraja.coffee.description'),
        ];

        try {
            $response = $this->request()->post('/mpesa/stkpush/v1/processrequest', $payload);
        } catch (ConnectionException $exception) {
            throw new DarajaException('The STK Push outcome is uncertain.', 'stk_connection_uncertain', null, true);
        }

        return $this->decode($response, 'stk_initiation_failed');
    }

    /** @return array<string, mixed> */
    public function query(string $checkoutRequestId): array
    {
        $credentials = $this->passwords->make();
        try {
            $response = $this->request()->post('/mpesa/stkpushquery/v1/query', [
                'BusinessShortCode' => config('daraja.shortcode'),
                'Password' => $credentials['password'],
                'Timestamp' => $credentials['timestamp'],
                'CheckoutRequestID' => $checkoutRequestId,
            ]);
        } catch (ConnectionException $exception) {
            throw new DarajaException('The transaction status could not be checked.', 'query_connection_failed');
        }

        return $this->decode($response, 'query_failed');
    }

    private function request(): PendingRequest
    {
        $transactionType = config('daraja.transaction_type');
        if (! in_array($transactionType, ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'], true)) {
            throw new DarajaException('Daraja transaction type is invalid.', 'invalid_transaction_type');
        }

        foreach (['shortcode', 'party_b', 'passkey', 'callback_url'] as $key) {
            if (! is_string(config("daraja.{$key}")) || config("daraja.{$key}") === '') {
                throw new DarajaException('Daraja is not configured.', 'daraja_not_configured');
            }
        }

        return Http::baseUrl(config('daraja.base_url'))
            ->withToken($this->auth->token())
            ->acceptJson()
            ->asJson()
            ->connectTimeout(config('daraja.connect_timeout'))
            ->timeout(config('daraja.timeout'));
    }

    /** @return array<string, mixed> */
    private function decode(Response $response, string $code): array
    {
        if (! $response->successful()) {
            throw new DarajaException('Daraja rejected the request.', $code, $response->status());
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new DarajaException('Daraja returned an invalid response.', $code.'_invalid_json', $response->status());
        }

        return $body;
    }
}
