<?php

declare(strict_types=1);

use BpcLearnShare\Auth\AccountInput;

/**
 * Read-only validation for the D044 registration-removal and mandatory-
 * password-change application foundation. This script performs no HTTP,
 * database, session, filesystem-write, migration, or account operation.
 */

$options = getopt('', ['mode:']);
$mode = (string) ($options['mode'] ?? 'validate');

if ($mode !== 'validate') {
    fwrite(STDERR, "Only --mode=validate is supported.\n");
    exit(2);
}

$root = dirname(__DIR__, 2);
require $root . '/src/auth/AccountInput.php';

/** @var list<array{label: string, passed: bool}> $checks */
$checks = [];

function d044Check(string $label, bool $passed): void
{
    global $checks;
    $checks[] = ['label' => $label, 'passed' => $passed];
    fwrite(STDOUT, sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $label));
}

function d044Read(string $root, string $relativePath): string
{
    $path = $root . '/' . $relativePath;
    $content = file_get_contents($path);

    if (!is_string($content)) {
        throw new RuntimeException('Could not read ' . $relativePath);
    }

    return $content;
}

$index = d044Read($root, 'public/index.php');
$repository = d044Read($root, 'src/auth/AccountRepository.php');
$authService = d044Read($root, 'src/auth/AuthService.php');
$session = d044Read($root, 'src/Core/Session.php');
$loginView = d044Read($root, 'src/Views/auth/login.php');
$passwordView = d044Read($root, 'src/Views/auth/change_password.php');
$schema = d044Read($root, 'database/schema.sql');

fwrite(STDOUT, "=== D044 AUTH FOUNDATION READ-ONLY VALIDATION ===\n");

d044Check(
    'Public registration view is absent',
    !is_file($root . '/src/Views/auth/register.php')
);
d044Check(
    'Public Student account-creation repository method is absent',
    !str_contains($repository, 'createStudent')
);
d044Check(
    'GET /register uses the neutral institution-issued account notice',
    str_contains($index, "if (\$path === '/register' && \$requestMethod === 'GET')")
        && str_contains($index, 'LearnShare accounts are issued by the institution.')
        && str_contains($index, "redirect('/login');")
);
d044Check(
    'POST /register fails closed with 405 and permits no account creation',
    str_contains($index, "if (\$path === '/register' && \$requestMethod === 'POST')")
        && str_contains($index, "header('Allow: GET');")
        && str_contains($index, 'No account or session was created.')
        && !str_contains($index, 'createStudent')
);
d044Check(
    'POST /register is rejected before session and database startup',
    strpos($index, "if (\$path === '/register' && \$requestMethod === 'POST')")
        < strpos($index, 'Session::start();')
        && strpos($index, "if (\$path === '/register' && \$requestMethod === 'POST')")
            < strpos($index, 'Database::connection();')
);
d044Check(
    'Login interface has no public registration link',
    !str_contains($loginView, 'href="/register"')
        && str_contains($loginView, 'issued by the institution')
);
d044Check(
    'User-facing authentication label is Account Identifier',
    str_contains($loginView, '>Account Identifier<')
);
d044Check(
    'Ordinary account reloads include the mandatory-change flag',
    substr_count($repository, 'must_change_password') >= 6
        && str_contains($repository, 'public function findById')
);
d044Check(
    'Password hash is isolated in a credential-state lookup',
    str_contains($repository, 'findCredentialStateById')
        && str_contains($repository, 'SELECT password_hash, account_status, must_change_password')
);
d044Check(
    'Mandatory password replacement clears the flag',
    str_contains($repository, 'must_change_password = 0')
);
d044Check(
    'Mandatory password replacement requires the live flag',
    str_contains($repository, 'AND must_change_password = 1')
);
d044Check(
    'Mandatory password replacement uses optimistic hash matching',
    str_contains(
        $repository,
        'AND BINARY password_hash = BINARY :expected_password_hash'
    )
);
d044Check(
    'New password cannot reuse the temporary password',
    str_contains($authService, 'password_verify($newPassword, $currentPasswordHash)')
        && str_contains($authService, 'different from the temporary password')
);
d044Check(
    'Password is hashed before the guarded repository update',
    str_contains($authService, 'password_hash($newPassword, PASSWORD_DEFAULT)')
        && str_contains($authService, 'replaceMandatoryPassword')
);
d044Check(
    'Successful password change regenerates authenticated session state',
    str_contains($authService, 'Session::authenticate($accountId);')
        && str_contains($session, 'session_regenerate_id(true);')
);
d044Check(
    'Authentication regeneration rotates the CSRF token',
    str_contains($session, "\$_SESSION['csrf_token']")
);
d044Check(
    'Global live mandatory-change guard is present',
    str_contains($index, "(int) \$currentAccount['must_change_password'] === 1")
        && str_contains($index, "redirect('/account/change-password');")
);
d044Check(
    'Only password change and logout bypass the global flag redirect',
    str_contains($index, "'/account/change-password',")
        && str_contains($index, "'/logout',")
        && str_contains($index, '!in_array($path, $mandatoryPasswordPaths, true)')
);
d044Check(
    'Login routes flagged accounts directly to password change',
    str_contains($index, "(int) \$signedInAccount['must_change_password'] === 1")
);
d044Check(
    'Password-change POST requires CSRF validation',
    str_contains($index, "if (!Csrf::validate(\$_POST['_token'] ?? null))")
        && str_contains($passwordView, 'name="_token"')
);
d044Check(
    'Password-change view contains only new-password inputs and logout',
    substr_count($passwordView, 'autocomplete="new-password"') === 2
        && !str_contains($passwordView, 'autocomplete="current-password"')
        && str_contains($passwordView, 'action="/logout"')
);
d044Check(
    'Canonical schema contains the additive D044 flag',
    preg_match(
        '/must_change_password\s+TINYINT\(1\)\s+NOT\s+NULL\s+DEFAULT\s+0/i',
        $schema
    ) === 1
);

$valid = AccountInput::validatePasswordChange(
    'private-pass-123',
    'private-pass-123'
);
$short = AccountInput::validatePasswordChange('short', 'short');
$mismatch = AccountInput::validatePasswordChange(
    'private-pass-123',
    'private-pass-456'
);
$tooLong = AccountInput::validatePasswordChange(
    str_repeat('a', 256),
    str_repeat('a', 256)
);

d044Check('Valid password-change input passes', $valid === []);
d044Check(
    'Short password-change input is rejected',
    isset($short['password'])
);
d044Check(
    'Mismatched confirmation is rejected',
    isset($mismatch['password_confirmation'])
);
d044Check(
    'Overlong password-change input is rejected',
    isset($tooLong['password'])
);

$passed = count(array_filter(
    $checks,
    static fn (array $check): bool => $check['passed']
));
$total = count($checks);

fwrite(STDOUT, sprintf("\nResult: %d/%d checks passed.\n", $passed, $total));
fwrite(
    STDOUT,
    "No HTTP request, database operation, session mutation, migration, account change, commit, or push occurred.\n"
);

if ($passed !== $total) {
    exit(1);
}

fwrite(STDOUT, "D044 AUTH FOUNDATION READ-ONLY VALIDATION PASSED.\n");
