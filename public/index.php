<?php

declare(strict_types=1);

use BpcLearnShare\Ai\AiFeatureGate;
use BpcLearnShare\Ai\GuardedSemanticRetrieval;
use BpcLearnShare\Ai\LocalProcessingException;
use BpcLearnShare\Ai\OllamaAllMiniLmEmbeddingAdapter;
use BpcLearnShare\Ai\SemanticRetrievalRepository;
use BpcLearnShare\Auth\AccountInput;
use BpcLearnShare\Auth\AccountRepository;
use BpcLearnShare\Auth\AuthService;
use BpcLearnShare\Core\Database;
use BpcLearnShare\Core\Environment;
use BpcLearnShare\Core\Session;
use BpcLearnShare\Moderation\ModerationDecisionException;
use BpcLearnShare\Moderation\ModerationInput;
use BpcLearnShare\Moderation\ModerationRepository;
use BpcLearnShare\Resource\ResourceDiscoveryInput;
use BpcLearnShare\Resource\ResourceDiscoveryRepository;
use BpcLearnShare\Resource\ResourceInput;
use BpcLearnShare\Resource\ResourceRepository;
use BpcLearnShare\Resource\ResourceUploadService;
use BpcLearnShare\Resource\TaxonomyRepository;
use BpcLearnShare\Resource\UploadValidationException;
use BpcLearnShare\Resource\UploadValidator;
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
$database = Database::connection();
$accounts = new AccountRepository($database);
$auth = new AuthService($accounts);
$taxonomy = new TaxonomyRepository($database);
$uploadValidator = new UploadValidator();
$resourceUploads = new ResourceUploadService(
    new ResourceRepository($database),
    dirname(__DIR__) . '/storage/uploads/resources'
);
$moderation = new ModerationRepository($database);
$resourceDiscovery = new ResourceDiscoveryRepository($database);
$resourceStorageDirectory =
    dirname(__DIR__) . '/storage/uploads/resources';
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

$rejectMethod = static function (
    array $allowedMethods
) use ($renderPage): never {
    http_response_code(405);
    header('Allow: ' . implode(', ', $allowedMethods));
    $renderPage('error', [
        'title' => 'Method not allowed',
        'heading' => 'That request method is not allowed',
        'message' => 'Please return to the application and try again.',
    ]);
    exit;
};

$requireAccount = static function () use ($auth): array {
    $account = $auth->currentAccount();

    if ($account === null) {
        redirect('/login');
    }

    return $account;
};

$requireStaff = static function () use (
    $auth,
    $renderPage
): array {
    $account = $auth->currentAccount();

    if ($account === null) {
        redirect('/login');
    }

    if (!in_array(
        (string) $account['role'],
        ['moderator', 'admin'],
        true
    )) {
        http_response_code(403);
        $renderPage('error', [
            'title' => 'Moderation not permitted',
            'heading' => 'This area is for moderation staff',
            'message' =>
                'Only active Moderator and Admin accounts may review Pending resources.',
        ]);
        exit;
    }

    return $account;
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

if ($path === '/resources/upload'
    && in_array($requestMethod, ['GET', 'POST'], true)
) {
    $account = $auth->currentAccount();

    if ($account === null) {
        redirect('/login');
    }

    if (!in_array(
        (string) $account['role'],
        ['student', 'teacher_instructor'],
        true
    )) {
        http_response_code(403);
        $renderPage('error', [
            'title' => 'Upload not permitted',
            'heading' => 'This account cannot submit ordinary uploads',
            'message' =>
                'Only Student and Teacher/Instructor accounts may upload resources.',
        ]);
        exit;
    }

    $taxonomyOptions = $taxonomy->activeOptions();
    $taxonomyReady = $taxonomyOptions['courses'] !== []
        && $taxonomyOptions['subjects'] !== []
        && $taxonomyOptions['year_levels'] !== []
        && $taxonomyOptions['resource_types'] !== [];
    $old = [
        'title' => '',
        'description' => '',
        'topic' => '',
        'course_id' => 0,
        'subject_id' => 0,
        'year_level_id' => 0,
        'resource_type_id' => 0,
        'tag_ids' => [],
    ];
    $errors = [];

    if ($requestMethod === 'POST') {
        if (!Csrf::validate($_POST['_token'] ?? null)) {
            $rejectInvalidCsrf();
        }

        $old = ResourceInput::normalize($_POST);
        $errors = ResourceInput::validate($_POST);

        if (!$taxonomyReady) {
            $errors['upload'] =
                'Upload choices are not configured. Contact an Admin.';
        }

        if ($errors === []) {
            $errors = $taxonomy->selectionErrors($old);
        }

        $validatedFile = null;

        if ($errors === []) {
            try {
                $submittedFile = $_FILES['resource_file'] ?? null;
                $validatedFile = $uploadValidator->validate(
                    is_array($submittedFile) ? $submittedFile : null
                );
            } catch (UploadValidationException $exception) {
                $errors['resource_file'] = $exception->getMessage();
                error_log(sprintf(
                    '[BPC LearnShare] Rejected upload actor=%d category=%s extension=%s',
                    (int) $account['id'],
                    $exception->category(),
                    $exception->attemptedExtension() === ''
                        ? '[none]'
                        : $exception->attemptedExtension()
                ));
            }
        }

        if ($errors === [] && is_array($validatedFile)) {
            try {
                $resourceId = $resourceUploads->createPending(
                    $account,
                    $old,
                    $validatedFile
                );
                Session::flash(
                    'success',
                    'Resource #' . $resourceId
                    . ' was stored securely and submitted for moderation.'
                );
                redirect('/resources/upload');
            } catch (Throwable $exception) {
                error_log(sprintf(
                    '[BPC LearnShare] Resource upload failed after validation: %s',
                    $exception->getMessage()
                ));
                $errors['upload'] =
                    'The upload could not be completed. No resource was accepted.';
            }
        }
    }

    $renderPage('resource/upload', [
        'title' => 'Upload resource',
        'errors' => $errors,
        'old' => $old,
        'taxonomy' => $taxonomyOptions,
        'taxonomyReady' => $taxonomyReady,
        'csrfToken' => Csrf::token(),
    ]);
    exit;
}

if ($path === '/resources') {
    if ($requestMethod !== 'GET') {
        $rejectMethod(['GET']);
    }

    $account = $requireAccount();
    $taxonomyOptions = $taxonomy->activeOptions();
    $filters = ResourceDiscoveryInput::normalize($_GET);
    $searchMode = ResourceDiscoveryInput::searchMode($_GET);
    $errors = ResourceDiscoveryInput::validate($_GET);

    if ($errors === []) {
        $errors = ResourceDiscoveryInput::activeFilterErrors(
            $filters,
            $taxonomyOptions
        );
    }

    $resources = [];
    $resultMode = 'metadata';
    $semanticStatusMessage = null;

    if ($errors === []) {
        if ($searchMode === 'metadata') {
            $resources = $resourceDiscovery->search($filters);
        } else {
            try {
                $semanticRetrieval = new GuardedSemanticRetrieval(
                    new SemanticRetrievalRepository($database),
                    $resourceDiscovery,
                    new AiFeatureGate($database),
                    new OllamaAllMiniLmEmbeddingAdapter(
                        Environment::get(
                            'OLLAMA_API_BASE',
                            'http://127.0.0.1:11434'
                        ),
                        Environment::get('OLLAMA_EXPECTED_VERSION', '0.32.1'),
                        Environment::get(
                            'OLLAMA_EMBEDDING_MODEL',
                            'all-minilm:latest'
                        ),
                        Environment::get(
                            'OLLAMA_EMBEDDING_MODEL_DIGEST',
                            '1b226e2802dbb772b5fc32a58f103ca1804ef7501331012de126ab22f67475ef'
                        ),
                        Environment::getInt('OLLAMA_EMBEDDING_DIMENSION', 384),
                        Environment::getInt(
                            'OLLAMA_EMBEDDING_TIMEOUT_SECONDS',
                            5
                        )
                    ),
                    $resourceStorageDirectory,
                    Environment::getBool(
                        'AI_SEMANTIC_RETRIEVAL_ENABLED',
                        false
                    )
                );
                $outcome = $semanticRetrieval->search(
                    $filters,
                    (int) $account['id'],
                    5
                );
                $resultMode = (string) $outcome['mode'];
                $resources = $outcome['results'];

                foreach ($resources as &$resource) {
                    unset($resource['internal_similarity_score']);
                }
                unset($resource);

                if ($resultMode === 'semantic') {
                    $semanticStatusMessage =
                        'AI-assisted meaning search ranked processed Approved resources. '
                        . 'Review the matched passages before using them.';
                } elseif ($outcome['fallback_reason'] === 'empty_query') {
                    $semanticStatusMessage =
                        'Enter search words to use AI-assisted meaning search. '
                        . 'Standard metadata results are shown.';
                } else {
                    $semanticStatusMessage =
                        'AI-assisted meaning search is unavailable right now. '
                        . 'Standard metadata results are shown instead.';
                }
            } catch (LocalProcessingException $exception) {
                if ($exception->reason === 'semantic_requester_not_authorized') {
                    Session::logout();
                    Session::flash('notice', 'Please sign in to continue.');
                    redirect('/login');
                }

                $resources = $resourceDiscovery->search($filters);
                $resultMode = 'metadata_fallback';
                $semanticStatusMessage =
                    'AI-assisted meaning search is unavailable right now. '
                    . 'Standard metadata results are shown instead.';
            } catch (Throwable) {
                error_log(
                    '[BPC LearnShare] Semantic search fell back safely.'
                );
                $resources = $resourceDiscovery->search($filters);
                $resultMode = 'metadata_fallback';
                $semanticStatusMessage =
                    'AI-assisted meaning search is unavailable right now. '
                    . 'Standard metadata results are shown instead.';
            }
        }
    }

    if ($errors !== []) {
        http_response_code(422);
    }

    $renderPage('resource/index', [
        'title' => 'Approved resources',
        'account' => $account,
        'resources' => $resources,
        'filters' => $filters,
        'errors' => $errors,
        'taxonomy' => $taxonomyOptions,
        'searchMode' => $searchMode,
        'resultMode' => $resultMode,
        'semanticStatusMessage' => $semanticStatusMessage,
    ]);
    exit;
}

$resourceDetailMatch = [];
$isResourceDetail = preg_match(
    '#\A/resources/([1-9][0-9]*)\z#',
    $path,
    $resourceDetailMatch
) === 1;

if ($isResourceDetail) {
    if ($requestMethod !== 'GET') {
        $rejectMethod(['GET']);
    }

    $requireAccount();
    $resourceId = (int) $resourceDetailMatch[1];
    $resource = $resourceDiscovery->openAvailableApproved($resourceId);

    if ($resource === null) {
        http_response_code(404);
        $renderPage('error', [
            'title' => 'Resource unavailable',
            'heading' => 'This resource is unavailable',
            'message' =>
                'It may not be Approved, or its protected file may no longer be available.',
        ]);
        exit;
    }

    $renderPage('resource/show', [
        'title' => (string) $resource['title'],
        'resource' => $resource,
    ]);
    exit;
}

$resourceDownloadMatch = [];
$isResourceDownload = preg_match(
    '#\A/resources/([1-9][0-9]*)/download\z#',
    $path,
    $resourceDownloadMatch
) === 1;

if ($isResourceDownload) {
    if ($requestMethod !== 'GET') {
        $rejectMethod(['GET']);
    }

    $account = $requireAccount();
    $resourceId = (int) $resourceDownloadMatch[1];
    $file = $resourceDiscovery->availableDownload($resourceId);

    if ($file === null) {
        http_response_code(404);
        $renderPage('error', [
            'title' => 'File unavailable',
            'heading' => 'The protected file is unavailable',
            'message' =>
                'The resource may no longer be Approved or its file is unavailable.',
        ]);
        exit;
    }

    $storedFilename = (string) $file['stored_filename'];
    $expectedExtension = (string) $file['file_type'];

    if (
        preg_match(
            '/\A[a-f0-9]{64}\.(pdf|docx|pptx|txt|jpg|png)\z/',
            $storedFilename
        ) !== 1
        || !str_ends_with($storedFilename, '.' . $expectedExtension)
    ) {
        error_log(sprintf(
            '[BPC LearnShare] Invalid approved filename resource=%d actor=%d',
            $resourceId,
            (int) $account['id']
        ));
        http_response_code(500);
        $renderPage('error', [
            'title' => 'File unavailable',
            'heading' => 'The protected file could not be verified',
            'message' => 'No file was served. Ask an Admin to inspect storage.',
        ]);
        exit;
    }

    $storageRoot = realpath($resourceStorageDirectory);
    $filePath = realpath(
        $resourceStorageDirectory
        . DIRECTORY_SEPARATOR
        . $storedFilename
    );

    if (
        $storageRoot === false
        || $filePath === false
        || dirname($filePath) !== $storageRoot
        || !is_file($filePath)
        || filesize($filePath) !== (int) $file['file_size']
    ) {
        error_log(sprintf(
            '[BPC LearnShare] Approved file mismatch resource=%d actor=%d',
            $resourceId,
            (int) $account['id']
        ));
        http_response_code(404);
        $renderPage('error', [
            'title' => 'File unavailable',
            'heading' => 'The protected file is unavailable',
            'message' => 'No file was served because its stored evidence did not match.',
        ]);
        exit;
    }

    if (!$resourceDiscovery->recordDownload($resourceId)) {
        http_response_code(404);
        $renderPage('error', [
            'title' => 'File unavailable',
            'heading' => 'The resource is no longer available',
            'message' => 'Its eligibility changed before the file could be served.',
        ]);
        exit;
    }

    $mimeTypes = [
        'pdf' => 'application/pdf',
        'docx' =>
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pptx' =>
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain; charset=UTF-8',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
    ];
    $originalFilename = str_replace(
        ["\r", "\n"],
        '',
        (string) $file['original_filename']
    );
    $fallbackFilename = preg_replace(
        '/[^A-Za-z0-9._-]+/',
        '_',
        $originalFilename
    );

    if (!is_string($fallbackFilename) || $fallbackFilename === '') {
        $fallbackFilename =
            'resource-' . $resourceId . '.' . $expectedExtension;
    }

    header_remove('Content-Type');
    header(
        'Content-Type: '
        . ($mimeTypes[$expectedExtension] ?? 'application/octet-stream')
    );
    header(
        'Content-Disposition: attachment; filename="'
        . addcslashes($fallbackFilename, "\\\"")
        . '"; filename*=UTF-8\'\''
        . rawurlencode($originalFilename)
    );
    header('Content-Length: ' . (string) filesize($filePath));
    header('Cache-Control: private, no-store');

    if (readfile($filePath) === false) {
        error_log(sprintf(
            '[BPC LearnShare] Approved read failed resource=%d actor=%d',
            $resourceId,
            (int) $account['id']
        ));
    }
    exit;
}

if ($path === '/moderation') {
    if ($requestMethod !== 'GET') {
        $rejectMethod(['GET']);
    }

    $account = $requireStaff();
    $renderPage('moderation/queue', [
        'title' => 'Pending resource queue',
        'account' => $account,
        'pendingResources' => $moderation->pendingQueue(),
    ]);
    exit;
}

$moderationReviewMatch = [];
$isModerationReview = preg_match(
    '#\A/moderation/resources/([1-9][0-9]*)\z#',
    $path,
    $moderationReviewMatch
) === 1;

if ($isModerationReview) {
    if ($requestMethod !== 'GET') {
        $rejectMethod(['GET']);
    }

    $requireStaff();
    $resourceId = (int) $moderationReviewMatch[1];
    $resource = $moderation->pendingResource($resourceId);

    if ($resource === null) {
        http_response_code(404);
        $renderPage('error', [
            'title' => 'Pending resource unavailable',
            'heading' => 'This resource is not in the Pending queue',
            'message' =>
                'It may have already been reviewed or may no longer exist.',
        ]);
        exit;
    }

    $renderPage('moderation/review', [
        'title' => 'Review resource',
        'resource' => $resource,
        'errors' => [],
        'old' => ['action' => '', 'note' => ''],
        'csrfToken' => Csrf::token(),
    ]);
    exit;
}

$moderationFileMatch = [];
$isModerationFile = preg_match(
    '#\A/moderation/resources/([1-9][0-9]*)/file\z#',
    $path,
    $moderationFileMatch
) === 1;

if ($isModerationFile) {
    if ($requestMethod !== 'GET') {
        $rejectMethod(['GET']);
    }

    $account = $requireStaff();
    $resourceId = (int) $moderationFileMatch[1];
    $file = $moderation->pendingFile($resourceId);

    if (
        $file === null
        || (string) $file['file_availability'] !== 'available'
    ) {
        http_response_code(404);
        $renderPage('error', [
            'title' => 'File unavailable',
            'heading' => 'The protected file is unavailable',
            'message' =>
                'The resource may have left Pending or its file is no longer available.',
        ]);
        exit;
    }

    $storedFilename = (string) $file['stored_filename'];
    $expectedExtension = (string) $file['file_type'];

    if (
        preg_match(
            '/\A[a-f0-9]{64}\.(pdf|docx|pptx|txt|jpg|png)\z/',
            $storedFilename
        ) !== 1
        || !str_ends_with($storedFilename, '.' . $expectedExtension)
    ) {
        error_log(sprintf(
            '[BPC LearnShare] Invalid protected filename resource=%d actor=%d',
            $resourceId,
            (int) $account['id']
        ));
        http_response_code(500);
        $renderPage('error', [
            'title' => 'File unavailable',
            'heading' => 'The protected file could not be verified',
            'message' => 'No file was served. Ask an Admin to inspect storage.',
        ]);
        exit;
    }

    $storageRoot = realpath($resourceStorageDirectory);
    $filePath = realpath(
        $resourceStorageDirectory
        . DIRECTORY_SEPARATOR
        . $storedFilename
    );

    if (
        $storageRoot === false
        || $filePath === false
        || dirname($filePath) !== $storageRoot
        || !is_file($filePath)
        || filesize($filePath) !== (int) $file['file_size']
    ) {
        error_log(sprintf(
            '[BPC LearnShare] Protected file mismatch resource=%d actor=%d',
            $resourceId,
            (int) $account['id']
        ));
        http_response_code(404);
        $renderPage('error', [
            'title' => 'File unavailable',
            'heading' => 'The protected file is unavailable',
            'message' => 'No file was served because its stored evidence did not match.',
        ]);
        exit;
    }

    $mimeTypes = [
        'pdf' => 'application/pdf',
        'docx' =>
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pptx' =>
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain; charset=UTF-8',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
    ];
    $originalFilename = str_replace(
        ["\r", "\n"],
        '',
        (string) $file['original_filename']
    );
    $fallbackFilename = preg_replace(
        '/[^A-Za-z0-9._-]+/',
        '_',
        $originalFilename
    );

    if (!is_string($fallbackFilename) || $fallbackFilename === '') {
        $fallbackFilename =
            'resource-' . $resourceId . '.' . $expectedExtension;
    }

    header_remove('Content-Type');
    header(
        'Content-Type: '
        . ($mimeTypes[$expectedExtension] ?? 'application/octet-stream')
    );
    header(
        'Content-Disposition: attachment; filename="'
        . addcslashes($fallbackFilename, "\\\"")
        . '"; filename*=UTF-8\'\''
        . rawurlencode($originalFilename)
    );
    header('Content-Length: ' . (string) filesize($filePath));
    header('Cache-Control: private, no-store');

    if (readfile($filePath) === false) {
        error_log(sprintf(
            '[BPC LearnShare] Protected read failed resource=%d actor=%d',
            $resourceId,
            (int) $account['id']
        ));
    }

    exit;
}

$moderationDecisionMatch = [];
$isModerationDecision = preg_match(
    '#\A/moderation/resources/([1-9][0-9]*)/decision\z#',
    $path,
    $moderationDecisionMatch
) === 1;

if ($isModerationDecision) {
    if ($requestMethod !== 'POST') {
        $rejectMethod(['POST']);
    }

    $account = $requireStaff();

    if (!Csrf::validate($_POST['_token'] ?? null)) {
        $rejectInvalidCsrf();
    }

    $resourceId = (int) $moderationDecisionMatch[1];
    $resource = $moderation->pendingResource($resourceId);

    if ($resource === null) {
        http_response_code(409);
        $renderPage('error', [
            'title' => 'Decision not applied',
            'heading' => 'This resource is no longer Pending',
            'message' => 'No moderation decision was recorded.',
        ]);
        exit;
    }

    $old = ModerationInput::normalize($_POST);
    $errors = ModerationInput::validate($old);

    if ($errors === []) {
        try {
            $statusAfter = $moderation->applyDecision(
                (int) $account['id'],
                $resourceId,
                $old['action'],
                $old['note']
            );
            $statusLabels = [
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'needs_correction' => 'Needs Correction',
            ];
            Session::flash(
                'success',
                'Resource #' . $resourceId . ' is now '
                . ($statusLabels[$statusAfter] ?? $statusAfter)
                . '. Its moderation history was recorded.'
            );
            redirect('/moderation');
        } catch (ModerationDecisionException $exception) {
            http_response_code($exception->httpStatus());
            $errors['decision'] = $exception->getMessage();
            error_log(sprintf(
                '[BPC LearnShare] Moderation denied actor=%d resource=%d category=%s',
                (int) $account['id'],
                $resourceId,
                $exception->category()
            ));
        } catch (Throwable $exception) {
            http_response_code(500);
            $errors['decision'] =
                'The decision could not be recorded. The resource was not changed.';
            error_log(sprintf(
                '[BPC LearnShare] Moderation failed actor=%d resource=%d: %s',
                (int) $account['id'],
                $resourceId,
                $exception->getMessage()
            ));
        }
    } else {
        http_response_code(422);
    }

    $resource = $moderation->pendingResource($resourceId);

    if ($resource === null) {
        $renderPage('error', [
            'title' => 'Decision state changed',
            'heading' => 'The Pending resource changed',
            'message' =>
                'Return to the moderation queue before trying another action.',
        ]);
        exit;
    }

    $renderPage('moderation/review', [
        'title' => 'Review resource',
        'resource' => $resource,
        'errors' => $errors,
        'old' => $old,
        'csrfToken' => Csrf::token(),
    ]);
    exit;
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
        'canUpload' => in_array(
            (string) $account['role'],
            ['student', 'teacher_instructor'],
            true
        ),
        'canModerate' => in_array(
            (string) $account['role'],
            ['moderator', 'admin'],
            true
        ),
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
    '/resources' => ['GET'],
    '/resources/upload' => ['GET', 'POST'],
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
