<?php

declare(strict_types=1);

namespace Portfolio\Support;

use RuntimeException;

final class FileRateLimiter implements RateLimiter
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new RuntimeException('The rate limiter is unavailable.');
        }
    }

    public function allow(string $key, int $maximumAttempts, int $windowSeconds): bool
    {
        $file = $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            throw new RuntimeException('The rate limiter is unavailable.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('The rate limiter is unavailable.');
            }

            $contents = stream_get_contents($handle);
            $attempts = is_string($contents) && $contents !== '' ? json_decode($contents, true) : [];
            if (!is_array($attempts)) {
                $attempts = [];
            }

            $now = time();
            $cutoff = $now - $windowSeconds;
            $attempts = array_values(array_filter($attempts, static fn (mixed $attempt): bool => is_int($attempt) && $attempt >= $cutoff));

            if (count($attempts) >= $maximumAttempts) {
                return false;
            }

            $attempts[] = $now;
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($attempts, JSON_THROW_ON_ERROR));
            fflush($handle);

            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
