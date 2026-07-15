<?php

declare(strict_types=1);

namespace Portfolio\Support;

final class EnvironmentLoader
{
    /** @param list<string> $paths */
    public static function loadFirstExisting(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path) && is_readable($path)) {
                self::load($path);
                return;
            }
        }
    }

    private static function load(string $path): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            $separator = strpos($line, '=');
            if ($separator === false) {
                continue;
            }

            $name = trim(substr($line, 0, $separator));
            if (preg_match('/^[A-Z_][A-Z0-9_]*$/', $name) !== 1 || getenv($name) !== false) {
                continue;
            }

            $value = trim(substr($line, $separator + 1));
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            putenv("{$name}={$value}");
        }
    }
}
