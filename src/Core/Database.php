<?php

declare(strict_types=1);

namespace BpcLearnShare\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = Environment::get('DB_HOST', '127.0.0.1');
        $port = Environment::getInt('DB_PORT', 3306);
        $database = Environment::get('DB_NAME');
        $username = Environment::get('DB_USER');
        $password = Environment::get('DB_PASS', '');

        if ($database === '' || $username === '') {
            throw new RuntimeException(
                'Database configuration is incomplete.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        self::$connection = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND =>
                    "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,"
                    . "ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_IN_DATE,"
                    . "NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'",
            ]
        );

        return self::$connection;
    }

    public static function ping(): bool
    {
        $result = self::connection()
            ->query('SELECT 1')
            ->fetchColumn();

        return (int) $result === 1;
    }
}
