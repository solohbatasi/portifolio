<?php

declare(strict_types=1);

namespace Portfolio\Support;

use Throwable;

final class SupportController
{
    public function __construct(private readonly SupportService $service)
    {
    }

    /** @param array<string, mixed> $input */
    public function initiate(array $input, string $ipAddress): array
    {
        try {
            return [202, $this->service->initiate($input, $ipAddress)];
        } catch (RequestValidationException $exception) {
            return [422, ['message' => 'Please check the highlighted fields.', 'errors' => $exception->errors]];
        } catch (UpstreamValidationException $exception) {
            $errors = [];
            if (isset($exception->errors['phone'])) {
                $errors['phone'] = ['The payment platform could not accept this phone number.'];
            }
            if (isset($exception->errors['amount'])) {
                $errors['amount'] = ['The payment platform could not accept this amount.'];
            }
            return [422, ['message' => 'The payment details could not be accepted.', 'errors' => $errors]];
        } catch (RateLimitException $exception) {
            return [429, ['message' => $exception->getMessage()]];
        } catch (ConnectionException|UpstreamException $exception) {
            $this->recordSafeFailure('initiate', $exception);
            return [502, ['message' => 'We could not initiate the payment right now. Please try again.']];
        } catch (Throwable $exception) {
            $this->recordSafeFailure('initiate', $exception);
            return [500, ['message' => 'The payment service is temporarily unavailable.']];
        }
    }

    public function status(string $transactionId, string $ipAddress): array
    {
        try {
            return [200, $this->service->status($transactionId, $ipAddress)];
        } catch (RequestValidationException $exception) {
            return [422, ['message' => 'The transaction identifier is invalid.', 'errors' => $exception->errors]];
        } catch (RateLimitException $exception) {
            return [429, ['message' => $exception->getMessage()]];
        } catch (ConnectionException|UpstreamException $exception) {
            $this->recordSafeFailure('status', $exception);
            return [502, ['message' => 'We could not check the payment right now. Please try again.']];
        } catch (Throwable $exception) {
            $this->recordSafeFailure('status', $exception);
            return [500, ['message' => 'The payment service is temporarily unavailable.']];
        }
    }

    private function recordSafeFailure(string $operation, Throwable $exception): void
    {
        $statusCode = $exception instanceof UpstreamException ? $exception->statusCode : null;
        $reasonCode = $exception instanceof ConnectionException || $exception instanceof UpstreamException
            ? $exception->reasonCode
            : 'internal_error';

        error_log(sprintf(
            '[portfolio-coffee] operation=%s failure=%s upstream_status=%s reason=%s',
            $operation,
            $exception::class,
            $statusCode ?? 'none',
            $reasonCode,
        ));
    }
}
