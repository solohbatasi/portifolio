<?php

namespace App\Services;

use App\Exceptions\DarajaException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class OrganizationDarajaClient
{
    public function __construct(private readonly DarajaAuthService $auth) {}

    public function b2c(string $phone, int $amount, string $remarks, ?string $occasion): array
    {
        $c = config('daraja');
        $this->requireConfigured([$c['organization']['initiator_name'], $c['organization']['security_credential'], $c['b2c']['shortcode'], $c['b2c']['result_url'], $c['b2c']['timeout_url']], 'b2c_not_configured');

        return $this->post($c['b2c']['path'], [
            'InitiatorName' => $c['organization']['initiator_name'], 'SecurityCredential' => $c['organization']['security_credential'],
            'CommandID' => $c['b2c']['command_id'], 'Amount' => $amount, 'PartyA' => $c['b2c']['shortcode'], 'PartyB' => $phone,
            'Remarks' => $remarks, 'QueueTimeOutURL' => $c['b2c']['timeout_url'], 'ResultURL' => $c['b2c']['result_url'],
            'Occasion' => $occasion ?: '',
        ], 'b2c');
    }

    public function balance(): array
    {
        $c = config('daraja');
        $this->requireConfigured([$c['organization']['initiator_name'], $c['organization']['security_credential'], $c['balance']['result_url'], $c['balance']['timeout_url']], 'balance_not_configured');

        return $this->post($c['balance']['path'], [
            'Initiator' => $c['organization']['initiator_name'], 'SecurityCredential' => $c['organization']['security_credential'],
            'CommandID' => 'AccountBalance', 'PartyA' => $c['b2c']['shortcode'] ?: $c['shortcode'],
            'IdentifierType' => $c['balance']['identifier_type'], 'Remarks' => 'Portfolio balance',
            'QueueTimeOutURL' => $c['balance']['timeout_url'], 'ResultURL' => $c['balance']['result_url'],
        ], 'balance');
    }

    private function post(string $path, array $payload, string $operation): array
    {
        try {
            $response = Http::baseUrl(config('daraja.base_url'))->withToken($this->auth->token())->acceptJson()->asJson()
                ->connectTimeout(config('daraja.connect_timeout'))->timeout(config('daraja.timeout'))->post($path, $payload);
        } catch (ConnectionException) {
            throw new DarajaException("{$operation} outcome is uncertain.", "{$operation}_connection_uncertain", null, true);
        }
        if (! $response->successful() || ! is_array($response->json())) {
            throw new DarajaException("{$operation} request failed.", "{$operation}_failed", $response->status());
        }

        return $response->json();
    }

    private function requireConfigured(array $values, string $code): void
    {
        foreach ($values as $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new DarajaException('The organization integration is not configured.', $code);
            }
        }
    }
}
