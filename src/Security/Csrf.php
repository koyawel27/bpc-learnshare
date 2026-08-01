<?php

declare(strict_types=1);

namespace BpcLearnShare\Security;

final class Csrf
{
    public static function token(): string
    {
        $token = $_SESSION['csrf_token'] ?? null;

        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $token;
        }

        return $token;
    }

    public static function validate(mixed $submittedToken): bool
    {
        $storedToken = $_SESSION['csrf_token'] ?? null;

        return is_string($submittedToken)
            && is_string($storedToken)
            && hash_equals($storedToken, $submittedToken);
    }
}
