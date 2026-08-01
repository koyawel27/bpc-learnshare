<?php

declare(strict_types=1);

namespace BpcLearnShare\Auth;

use BpcLearnShare\Core\Session;

final class AuthService
{
    private const DUMMY_PASSWORD_HASH =
        '$2y$10$WJdTn0WDxoXEIEc7GQ7Q8e3xAA8iGPrzY8k8zLhLO2RxPqSJOPGdu';

    public function __construct(
        private readonly AccountRepository $accounts
    ) {
    }

    public function attempt(string $username, string $password): bool
    {
        $account = $this->accounts->findByUsername($username);
        $hash = is_array($account)
            ? (string) $account['password_hash']
            : self::DUMMY_PASSWORD_HASH;
        $passwordMatches = password_verify($password, $hash);

        if (
            !is_array($account)
            || !$passwordMatches
            || $account['account_status'] !== 'active'
        ) {
            return false;
        }

        Session::authenticate((int) $account['id']);

        return true;
    }

    /**
     * Reloads the authoritative role and status on every protected request.
     *
     * @return array<string, mixed>|null
     */
    public function currentAccount(): ?array
    {
        $accountId = Session::accountId();

        if ($accountId === null) {
            return null;
        }

        $account = $this->accounts->findById($accountId);

        if (
            !is_array($account)
            || $account['account_status'] !== 'active'
        ) {
            Session::logout();
            Session::flash(
                'notice',
                'Please sign in to continue.'
            );

            return null;
        }

        return $account;
    }
}
