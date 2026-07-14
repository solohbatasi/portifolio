<?php

declare(strict_types=1);

namespace Portfolio\Support;

final class SupportService
{
    private const STATUS_MESSAGES = [
        'pending' => 'Waiting for you to complete the M-Pesa prompt.',
        'processing' => 'Your payment is being confirmed.',
        'success' => 'Thank you for supporting my work.',
        'failed' => 'The payment could not be completed.',
        'cancelled' => 'The M-Pesa request was cancelled.',
        'timeout' => 'The payment request timed out. You can try again.',
        'reversed' => 'The payment was reversed.',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly KadiClient $client,
        private readonly RateLimiter $rateLimiter,
    ) {
    }

    /** @param array<string, mixed> $input */
    public function initiate(array $input, string $ipAddress): array
    {
        if (!$this->rateLimiter->allow('init-ip:' . $ipAddress, 5, 600)) {
            throw new RateLimitException('Too many payment requests. Please wait before trying again.');
        }

        $validated = $this->validateInitiation($input);
        $phoneKey = hash('sha256', $validated['phone']);
        if (!$this->rateLimiter->allow('init-phone:' . $phoneKey, 3, 600)) {
            throw new RateLimitException('Too many requests for this phone number. Please wait before trying again.');
        }

        $reference = 'COFFEE-' . strtoupper(substr(hash('sha256', $validated['request_id']), 0, 12));
        $response = $this->client->initiate([
            'phone' => $validated['phone'],
            'amount' => $validated['amount'],
            'reference' => $reference,
            'description' => "Support for Solomon Batasi's work",
            'metadata' => [
                'source' => 'solomon-portfolio',
                'purpose' => 'buy-me-a-coffee',
                'request_id' => $validated['request_id'],
            ],
        ], $validated['request_id']);

        $transactionId = $response['transaction_id'] ?? null;
        if (!is_string($transactionId) || !$this->validTransactionId($transactionId)) {
            throw new UpstreamException('The payment provider returned an unexpected response.');
        }

        $status = $this->normaliseStatus($response['status'] ?? 'pending');

        return [
            'transaction_id' => $transactionId,
            'status' => $status,
            'message' => 'Payment prompt sent. Check your phone to complete the payment.',
        ];
    }

    public function status(string $transactionId, string $ipAddress): array
    {
        if (!$this->validTransactionId($transactionId)) {
            throw new RequestValidationException(['transaction_id' => ['The transaction identifier is invalid.']]);
        }

        if (!$this->rateLimiter->allow('status-ip:' . $ipAddress, 30, 60)) {
            throw new RateLimitException('Too many status checks. Please wait a moment and try again.');
        }

        $response = $this->client->find($transactionId);
        $status = $this->normaliseStatus($response['status'] ?? 'processing');
        return [
            'transaction_id' => $transactionId,
            'status' => $status,
            'message' => self::STATUS_MESSAGES[$status],
        ];
    }

    /** @param array<string, mixed> $input */
    private function validateInitiation(array $input): array
    {
        $errors = [];
        $phone = $this->normalisePhone($input['phone'] ?? null);
        if ($phone === null) {
            $errors['phone'][] = 'Enter a valid Kenyan mobile number.';
        }

        $amount = $input['amount'] ?? null;
        if (!is_int($amount)) {
            $errors['amount'][] = 'The amount must be a whole-number KES value.';
        } elseif ($amount < $this->config->minimumAmount) {
            $errors['amount'][] = "The minimum support amount is KES {$this->config->minimumAmount}.";
        } elseif ($amount > $this->config->maximumAmount) {
            $errors['amount'][] = "The maximum support amount is KES {$this->config->maximumAmount}.";
        }

        $requestId = $input['request_id'] ?? null;
        if (!is_string($requestId) || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $requestId) !== 1) {
            $errors['request_id'][] = 'The request identifier is invalid.';
        }

        if ($errors !== []) {
            throw new RequestValidationException($errors);
        }

        return ['phone' => $phone, 'amount' => $amount, 'request_id' => strtolower($requestId)];
    }

    private function normalisePhone(mixed $phone): ?string
    {
        if (!is_string($phone)) {
            return null;
        }

        $cleaned = preg_replace('/[\s()-]+/', '', trim($phone));
        if (!is_string($cleaned)) {
            return null;
        }

        if (preg_match('/^0([17]\d{8})$/', $cleaned, $matches) === 1) {
            return '254' . $matches[1];
        }

        if (preg_match('/^(?:\+?254)([17]\d{8})$/', $cleaned, $matches) === 1) {
            return '254' . $matches[1];
        }

        return null;
    }

    private function validTransactionId(string $transactionId): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{4,128}$/', $transactionId) === 1;
    }

    private function normaliseStatus(mixed $status): string
    {
        $normalised = is_string($status) ? strtolower($status) : 'processing';

        return array_key_exists($normalised, self::STATUS_MESSAGES) ? $normalised : 'processing';
    }
}
