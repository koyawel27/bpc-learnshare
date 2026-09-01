<?php

declare(strict_types=1);

namespace BpcLearnShare\Auth;

use PDO;
use PDOException;
use Throwable;

final class AccountRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, username, password_hash, display_name, role,
                    account_status, must_change_password
             FROM accounts
             WHERE username = :username
             LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $account = $statement->fetch();

        return is_array($account) ? $account : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $accountId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, username, display_name, role, account_status,
                    must_change_password
             FROM accounts
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $accountId]);
        $account = $statement->fetch();

        return is_array($account) ? $account : null;
    }

    public function createFirstAdmin(
        string $username,
        string $passwordHash,
        string $displayName
    ): bool {
        $this->database->beginTransaction();

        try {
            $statement = $this->database->query(
                "SELECT id
                 FROM accounts
                 WHERE role = 'admin'
                 LIMIT 1
                 FOR UPDATE"
            );

            if ($statement->fetchColumn() !== false) {
                $this->database->rollBack();

                return false;
            }

            $created = $this->createAccount(
                $username,
                $passwordHash,
                $displayName,
                'admin'
            );

            if (!$created) {
                $this->database->rollBack();

                return false;
            }

            $this->database->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function adminCount(): int
    {
        $statement = $this->database->query(
            "SELECT COUNT(*) FROM accounts WHERE role = 'admin'"
        );

        return (int) $statement->fetchColumn();
    }

    /**
     * Keeps the password hash out of ordinary account/view data.
     *
     * @return array<string, mixed>|null
     */
    public function findCredentialStateById(int $accountId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT password_hash, account_status, must_change_password
             FROM accounts
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $accountId]);
        $state = $statement->fetch();

        return is_array($state) ? $state : null;
    }

    public function replaceMandatoryPassword(
        int $accountId,
        string $expectedPasswordHash,
        string $newPasswordHash
    ): bool {
        $statement = $this->database->prepare(
            "UPDATE accounts
             SET password_hash = :new_password_hash,
                 must_change_password = 0
             WHERE id = :id
               AND account_status = 'active'
               AND must_change_password = 1
               AND BINARY password_hash = BINARY :expected_password_hash"
        );
        $statement->execute([
            'id' => $accountId,
            'expected_password_hash' => $expectedPasswordHash,
            'new_password_hash' => $newPasswordHash,
        ]);

        return $statement->rowCount() === 1;
    }

    private function createAccount(
        string $username,
        string $passwordHash,
        string $displayName,
        string $role
    ): bool {
        $statement = $this->database->prepare(
            'INSERT INTO accounts (
                username,
                password_hash,
                display_name,
                role,
                account_status,
                must_change_password
            ) VALUES (
                :username,
                :password_hash,
                :display_name,
                :role,
                :account_status,
                0
            )'
        );

        try {
            return $statement->execute([
                'username' => $username,
                'password_hash' => $passwordHash,
                'display_name' => $displayName,
                'role' => $role,
                'account_status' => 'active',
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return false;
            }

            throw $exception;
        }
    }
}
