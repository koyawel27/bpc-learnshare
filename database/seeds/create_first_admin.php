<?php

declare(strict_types=1);

use BpcLearnShare\Auth\AccountInput;
use BpcLearnShare\Auth\AccountRepository;
use BpcLearnShare\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__, 2) . '/src/bootstrap.php';

$options = getopt(
    '',
    ['username:', 'display-name:', 'password-stdin']
);

if (
    !is_array($options)
    || !isset($options['username'], $options['display-name'])
    || !array_key_exists('password-stdin', $options)
) {
    fwrite(
        STDERR,
        "Usage: php database/seeds/create_first_admin.php "
        . "--username=<username> --display-name=<name> --password-stdin\n"
    );
    exit(2);
}

$username = trim((string) $options['username']);
$displayName = trim((string) $options['display-name']);
$passwordInput = stream_get_contents(STDIN);

if (!is_string($passwordInput)) {
    fwrite(STDERR, "Unable to read the password from standard input.\n");
    exit(2);
}

$password = rtrim($passwordInput, "\r\n");
$errors = AccountInput::validate(
    $username,
    $displayName,
    $password
);

if ($errors !== []) {
    foreach ($errors as $message) {
        fwrite(STDERR, "- {$message}\n");
    }

    exit(2);
}

$accounts = new AccountRepository(Database::connection());

if ($accounts->adminCount() !== 0) {
    fwrite(
        STDERR,
        "First-Admin setup is disabled because an Admin already exists.\n"
    );
    exit(3);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

if (!is_string($passwordHash)) {
    fwrite(STDERR, "Password hashing failed.\n");
    exit(1);
}

if (
    !$accounts->createFirstAdmin(
        $username,
        $passwordHash,
        $displayName
    )
) {
    fwrite(
        STDERR,
        "The first Admin could not be created. No setup account was added.\n"
    );
    exit(1);
}

fwrite(
    STDOUT,
    "First Admin created successfully. The password was not logged.\n"
);
