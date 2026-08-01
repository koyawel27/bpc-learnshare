<?php

declare(strict_types=1);

use BpcLearnShare\Auth\AccountInput;
use BpcLearnShare\Auth\AccountRepository;
use BpcLearnShare\Auth\AuthService;
use BpcLearnShare\Core\Database;
use BpcLearnShare\Core\Environment;
use BpcLearnShare\Core\Session;
use BpcLearnShare\Security\Csrf;
use function BpcLearnShare\Support\redirect;
use function BpcLearnShare\Support\render;

require dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "style-src 'self'; img-src 'self'; "
    . "script-src 'self'; base-uri 'none'; frame-ancestors 'none'; "
    . "form-action 'self'"
);

Session::start();

$appName = Environment::get('APP_NAME', 'BPC LearnShare');
$accounts = new AccountRepository(Database::connection());
$auth = new AuthService($accounts);
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$requestPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);
$path = is_string($requestPath)
    ? '/' . trim($requestPath, '/')
    : '/';
$path = $path === '/' ? '/' : rtrim($path, '/');

$renderPage = static function (
    string $view,
    array $data = []
) use ($appName): void {
    render($view, array_merge(
        [
            'appName' => $appName,
            'notice' => Session::consumeFlash('notice'),
            'success' => Session::consumeFlash('success'),
        ],
        $data
    ));
};

$rejectInvalidCsrf = static function () use ($renderPage): never {
    http_response_code(403);
    $renderPage('error', [
        'title' => 'Request expired',
        'heading' => 'Please try that action again',
        'message' =>
            'The form security check failed. No account or session change was made.',
    ]);
    exit;
};

if ($path === '/' && $requestMethod === 'GET') {
    redirect(Session::accountId() === null ? '/login' : '/dashboard');
}

if ($path === '/health' && $requestMethod === 'GET') {
    $databaseReady = Database::ping();
    $renderPage('health', [
        'title' => 'Foundation Check',
        'environment' => Environment::get('APP_ENV', 'local'),
        'checks' => [
            'PHP application' => true,
            'Environment configuration' => true,
            'MariaDB connection' => $databaseReady,
            'Authentication foundation' => true,
        ],
    ]);
    exit;
}

if ($path === '/login' && $requestMethod === 'GET') {
    if (Session::accountId() !== null) {
        redirect('/dashboard');
    }

    $renderPage('auth/login', [
        'title' => 'Sign in',
        'errors' => [],
        'old' => ['username' => ''],
        'csrfToken' => Csrf::token(),
    ]);
    exit;
}

if ($path === '/login' && $requestMethod === 'POST') {
    if (!Csrf::validate($_POST['_token'] ?? null)) {
        $rejectInvalidCsrf();
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (
        $username !== ''
        && $password !== ''
        && $auth->attempt($username, $password)
    ) {
        redirect('/dashboard');
    }

    http_response_code(422);
    $renderPage('auth/login', [
        'title' => 'Sign in',
        'errors' => [
            'login' => 'Unable to sign in with those credentials.',
        ],
        'old' => ['username' => $username],
        'csrfToken' => Csrf::token(),
    ]);
    exit;
}

if ($path === '/register' && $requestMethod === 'GET') {
    if (Session::accountId() !== null) {
        redirect('/dashboard');
    }

    $renderPage('auth/register', [
        'title' => 'Create Student account',
        'errors' => [],
        'old' => [
            'username' => '',
            'display_name' => '',
        ],
        'csrfToken' => Csrf::token(),
    ]);
    exit;
}

if ($path === '/register' && $requestMethod === 'POST') {
    if (!Csrf::validate($_POST['_token'] ?? null)) {
        $rejectInvalidCsrf();
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation =
        (string) ($_POST['password_confirmation'] ?? '');
    $errors = AccountInput::validate(
        $username,
        $displayName,
        $password,
        $passwordConfirmation
    );

    if (array_key_exists('role', $_POST)) {
        $errors['role'] =
            'Public registration cannot select an account role.';
    }

    if ($errors === []) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if (!is_string($passwordHash)) {
            throw new RuntimeException('Password hashing failed.');
        }

        if (
            !$accounts->createStudent(
                $username,
                $passwordHash,
                $displayName
            )
        ) {
            $errors['username'] =
                'Registration could not be completed with those details.';
        }
    }

    if ($errors !== []) {
        http_response_code(422);
        $renderPage('auth/register', [
            'title' => 'Create Student account',
            'errors' => $errors,
            'old' => [
                'username' => $username,
                'display_name' => $displayName,
            ],
            'csrfToken' => Csrf::token(),
        ]);
        exit;
    }

    Session::flash(
        'success',
        'Student account created. You can now sign in.'
    );
    redirect('/login');
}

if ($path === '/dashboard' && $requestMethod === 'GET') {
    $account = $auth->currentAccount();

    if ($account === null) {
        redirect('/login');
    }

    $roleLabels = [
        'student' => 'Student',
        'teacher_instructor' => 'Teacher/Instructor',
        'moderator' => 'Moderator',
        'admin' => 'Admin',
    ];

    $renderPage('dashboard', [
        'title' => 'Dashboard',
        'account' => $account,
        'roleLabel' =>
            $roleLabels[(string) $account['role']] ?? 'Account',
        'csrfToken' => Csrf::token(),
    ]);
    exit;
}

if ($path === '/logout' && $requestMethod === 'POST') {
    if (!Csrf::validate($_POST['_token'] ?? null)) {
        $rejectInvalidCsrf();
    }

    Session::logout();
    Session::flash('notice', 'You have been signed out.');
    redirect('/login');
}

$allowedMethodsByPath = [
    '/login' => ['GET', 'POST'],
    '/register' => ['GET', 'POST'],
    '/logout' => ['POST'],
    '/dashboard' => ['GET'],
];

if (
    array_key_exists($path, $allowedMethodsByPath)
    && !in_array(
        $requestMethod,
        $allowedMethodsByPath[$path],
        true
    )
) {
    http_response_code(405);
    header(
        'Allow: ' . implode(', ', $allowedMethodsByPath[$path])
    );
    $renderPage('error', [
        'title' => 'Method not allowed',
        'heading' => 'That request method is not allowed',
        'message' => 'Please return to the application and try again.',
    ]);
    exit;
}

http_response_code(404);
$renderPage('error', [
    'title' => 'Page not found',
    'heading' => 'Page not found',
    'message' => 'The requested page does not exist.',
]);
