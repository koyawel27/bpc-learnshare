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

    /**
     * @return array<string, string>
     */
    public function changeMandatoryPassword(
        int $accountId,
        string $newPassword,
        string $passwordConfirmation
    ): array {
        $errors = AccountInput::validatePasswordChange(
            $newPassword,
            $passwordConfirmation
        );

        if ($errors !== []) {
            return $errors;
        }

        $credentialState = $this->accounts->findCredentialStateById(
            $accountId
        );

        if (
            !is_array($credentialState)
            || $credentialState['account_status'] !== 'active'
            || (int) $credentialState['must_change_password'] !== 1
        ) {
            return [
                'password_change' =>
                    'The required password change is no longer available. Please sign in again.',
            ];
        }

        $currentPasswordHash = (string) $credentialState['password_hash'];

        if (password_verify($newPassword, $currentPasswordHash)) {
            return [
                'password' =>
                    'Choose a new private password that is different from the temporary password.',
            ];
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        if (!is_string($newPasswordHash)) {
            return [
                'password_change' =>
                    'The password could not be updated. Please try again.',
            ];
        }

        if (!$this->accounts->replaceMandatoryPassword(
            $accountId,
            $currentPasswordHash,
            $newPasswordHash
        )) {
            return [
                'password_change' =>
                    'The account changed before the password could be updated. Please try again.',
            ];
        }

        Session::authenticate($accountId);

        return [];
    }
}
