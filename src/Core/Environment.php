<?php

declare(strict_types=1);

namespace BpcLearnShare\Core;

use RuntimeException;

final class Environment
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(
                'Local environment configuration is missing.'
            );
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            throw new RuntimeException(
                'Local environment configuration could not be read.'
            );
        }

        foreach ($lines as $lineNumber => $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $separator = strpos($trimmed, '=');

            if ($separator === false) {
                throw new RuntimeException(sprintf(
                    'Invalid environment entry on line %d.',
                    $lineNumber + 1
                ));
            }

            $key = trim(substr($trimmed, 0, $separator));
            $value = trim(substr($trimmed, $separator + 1));

            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                throw new RuntimeException(sprintf(
                    'Invalid environment key on line %d.',
                    $lineNumber + 1
                ));
            }

            if (
                strlen($value) >= 2
                && (
                    ($value[0] === '"' && $value[-1] === '"')
                    || ($value[0] === "'" && $value[-1] === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            self::$values[$key] = $value;
        }
    }

    public static function get(
        string $key,
        ?string $default = null
    ): string {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        if ($default !== null) {
            return $default;
        }

        throw new RuntimeException(sprintf(
            'Required environment value %s is missing.',
            $key
        ));
    }

    public static function getInt(string $key, int $default): int
    {
        $value = self::get($key, (string) $default);

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException(sprintf(
                'Environment value %s must be an integer.',
                $key
            ));
        }

        return (int) $value;
    }

    public static function getBool(string $key, bool $default): bool
    {
        $value = strtolower(self::get(
            $key,
            $default ? 'true' : 'false'
        ));

        return match ($value) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => throw new RuntimeException(sprintf(
                'Environment value %s must be boolean.',
                $key
            )),
        };
    }
}
