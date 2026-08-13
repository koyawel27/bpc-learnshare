<?php

declare(strict_types=1);

namespace BpcLearnShare\Core;

final class Session
{
    public const IDLE_TIMEOUT_SECONDS = 1800;

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $accountId = self::accountId();
        $lastActivity = $_SESSION['last_activity'] ?? null;

        if (
            $accountId !== null
            && is_int($lastActivity)
            && time() - $lastActivity >= self::IDLE_TIMEOUT_SECONDS
        ) {
            self::destroy();
            self::startFresh();
            self::flash(
                'notice',
                'Your session expired after 30 minutes of inactivity. Please sign in again.'
            );

            return;
        }

        if ($accountId !== null) {
            $_SESSION['last_activity'] = time();
        }
    }

    public static function accountId(): ?int
    {
        $value = $_SESSION['account_id'] ?? null;

        return is_int($value) && $value > 0 ? $value : null;
    }

    public static function authenticate(int $accountId): void
    {
        session_regenerate_id(true);
        unset($_SESSION['ai_inquiry_context']);
        $_SESSION['account_id'] = $accountId;
        $_SESSION['authenticated_at'] = time();
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        self::destroy();
        self::startFresh();
    }

    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function consumeFlash(string $key): ?string
    {
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return is_string($message) ? $message : null;
    }

    private static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $parameters['path'],
                    'domain' => $parameters['domain'],
                    'secure' => $parameters['secure'],
                    'httponly' => $parameters['httponly'],
                    'samesite' => $parameters['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }

    private static function startFresh(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);
    }
}
