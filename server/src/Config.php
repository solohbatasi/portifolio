<?php

declare(strict_types=1);

namespace Portfolio\Support;

use RuntimeException;

final readonly class Config
{
    public function __construct(
        public string $baseUrl,
        public string $secretKey,
        public int $minimumAmount,
        public int $maximumAmount,
        public ?string $frontendUrl,
        public string $rateLimitDirectory,
    ) {
        if ($this->secretKey === '') {
            throw new RuntimeException('Kadi server configuration is incomplete.');
        }

        if (filter_var($this->baseUrl, FILTER_VALIDATE_URL) === false || parse_url($this->baseUrl, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('Kadi base URL is invalid.');
        }

        if ($this->frontendUrl !== null && (filter_var($this->frontendUrl, FILTER_VALIDATE_URL) === false || !in_array(parse_url($this->frontendUrl, PHP_URL_SCHEME), ['http', 'https'], true))) {
            throw new RuntimeException('Coffee frontend URL is invalid.');
        }

        if ($this->minimumAmount < 1 || $this->maximumAmount < $this->minimumAmount) {
            throw new RuntimeException('Coffee amount limits are invalid.');
        }
    }

    public static function fromEnvironment(): self
    {
        $baseUrl = rtrim(trim((string) (getenv('KADI_BASE_URL') ?: 'https://kadi.pulsetikafrica.com')), '/');
        $frontendUrl = trim((string) (getenv('COFFEE_FRONTEND_URL') ?: ''));

        return new self(
            baseUrl: $baseUrl,
            secretKey: trim((string) (getenv('KADI_SECRET_KEY') ?: '')),
            minimumAmount: self::integerEnvironment('COFFEE_MIN_AMOUNT', 50),
            maximumAmount: self::integerEnvironment('COFFEE_MAX_AMOUNT', 10000),
            frontendUrl: $frontendUrl !== '' ? rtrim($frontendUrl, '/') : null,
            rateLimitDirectory: trim((string) (getenv('COFFEE_RATE_LIMIT_DIR') ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'solomon-portfolio-coffee-rates')),
        );
    }

    private static function integerEnvironment(string $name, int $default): int
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException("{$name} must be an integer.");
        }

        return (int) $value;
    }
}
