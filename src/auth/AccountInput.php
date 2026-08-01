<?php

declare(strict_types=1);

namespace BpcLearnShare\Auth;

final class AccountInput
{
    /**
     * @return array<string, string>
     */
    public static function validate(
        string $username,
        string $displayName,
        string $password,
        ?string $passwordConfirmation = null
    ): array {
        $errors = [];

        if (
            !preg_match(
                '/\A[a-zA-Z0-9._-]{3,50}\z/',
                $username
            )
        ) {
            $errors['username'] =
                'Use 3–50 letters, numbers, dots, underscores, or hyphens.';
        }

        $displayNameLength = mb_strlen($displayName);

        if ($displayNameLength < 2 || $displayNameLength > 100) {
            $errors['display_name'] =
                'Display name must be between 2 and 100 characters.';
        }

        $passwordLength = mb_strlen($password);

        if ($passwordLength < 8 || $passwordLength > 255) {
            $errors['password'] =
                'Password must be between 8 and 255 characters.';
        }

        if (
            $passwordConfirmation !== null
            && !hash_equals($password, $passwordConfirmation)
        ) {
            $errors['password_confirmation'] =
                'Password confirmation does not match.';
        }

        return $errors;
    }
}
