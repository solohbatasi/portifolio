<?php

namespace App\Services;

use App\Exceptions\DarajaException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class DarajaAuthService
{
    public function token(): string
    {
        $cached = Cache::get('daraja.oauth.token');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = null;
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = $this->request()->get('/oauth/v1/generate', ['grant_type' => 'client_credentials']);
            } catch (ConnectionException $exception) {
                if ($attempt === 2) {
                    throw new DarajaException('Daraja authentication could not be reached.', 'oauth_connection_failed');
                }
                usleep(250000);

                continue;
            }

            if (! $response->serverError() || $attempt === 2) {
                break;
            }
            usleep(250000);
        }

        if (! $response || ! $response->successful()) {
            throw new DarajaException('Daraja authentication failed.', 'oauth_failed', $response->status());
        }

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            throw new DarajaException('Daraja authentication returned an invalid response.', 'oauth_invalid_response', $response->status());
        }

        $expiresIn = max(60, (int) $response->json('expires_in', 3599) - 120);
        Cache::put('daraja.oauth.token', $token, now()->addSeconds($expiresIn));

        return $token;
    }

    private function request(): PendingRequest
    {
        $key = (string) config('daraja.consumer_key');
        $secret = (string) config('daraja.consumer_secret');
        if ($key === '' || $secret === '') {
            throw new DarajaException('Daraja authentication is not configured.', 'oauth_not_configured');
        }

        return Http::baseUrl(config('daraja.base_url'))
            ->withBasicAuth($key, $secret)
            ->acceptJson()
            ->connectTimeout(config('daraja.connect_timeout'))
            ->timeout(config('daraja.timeout'));
    }
}
