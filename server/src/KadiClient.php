<?php

declare(strict_types=1);

namespace Portfolio\Support;

final class KadiClient
{
    public function __construct(
        private readonly Config $config,
        private readonly Transport $transport,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function initiate(array $payload, string $requestId): array
    {
        $response = $this->transport->request(
            'POST',
            $this->config->baseUrl . '/api/v1/transactions/push-stk',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'x-api-key' => $this->config->secretKey,
                'Idempotency-Key' => 'coffee-' . strtolower($requestId),
            ],
            $payload,
        );

        return $this->handleResponse($response);
    }

    public function find(string $transactionId): array
    {
        $response = $this->transport->request(
            'GET',
            $this->config->baseUrl . '/api/v1/transactions/' . rawurlencode($transactionId),
            [
                'Accept' => 'application/json',
                'x-api-key' => $this->config->secretKey,
            ],
        );

        return $this->handleResponse($response);
    }

    /** @return array<string, mixed> */
    private function handleResponse(TransportResponse $response): array
    {
        if ($response->statusCode === 422) {
            throw new UpstreamValidationException($this->sanitiseErrors($response->body['errors'] ?? []));
        }

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new UpstreamException('The payment provider could not process the request.');
        }

        return $response->body;
    }

    /** @return array<string, list<string>> */
    private function sanitiseErrors(mixed $errors): array
    {
        if (!is_array($errors)) {
            return [];
        }

        $safeErrors = [];
        foreach ($errors as $field => $messages) {
            if (!is_string($field) || preg_match('/^[a-z_]{1,40}$/', $field) !== 1) {
                continue;
            }

            $messageList = is_array($messages) ? $messages : [$messages];
            foreach ($messageList as $message) {
                if (is_string($message) && $message !== '') {
                    $safeErrors[$field][] = substr(strip_tags($message), 0, 180);
                }
            }
        }

        return $safeErrors;
    }
}
