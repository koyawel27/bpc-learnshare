<?php

declare(strict_types=1);

use BpcLearnShare\Ai\AiFeatureAvailability;
use BpcLearnShare\Ai\AiFeatureGate;
use BpcLearnShare\Ai\AiInquirySession;
use BpcLearnShare\Ai\AiSourceEligibility;
use BpcLearnShare\Ai\DatabaseAiSourceEligibility;
use BpcLearnShare\Ai\GroundedAnswerProvider;
use BpcLearnShare\Ai\GroundedInquiryCoordinator;
use BpcLearnShare\Ai\SourceAttributionPresenter;
use BpcLearnShare\Core\Database;
use BpcLearnShare\Core\Session;
use BpcLearnShare\Resource\ResourceDiscoveryRepository;
require dirname(__DIR__, 2) . '/src/bootstrap.php';

ob_start();
ini_set('session.use_cookies', '0');
Session::start();

final class StaticFeatureAvailability implements AiFeatureAvailability
{
    public function __construct(private readonly bool $enabled)
    {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}

final class SequenceSourceEligibility implements AiSourceEligibility
{
    public int $calls = 0;

    /** @var list<array<int, array<string, mixed>>> */
    public array $referenceCalls = [];

    /** @param list<list<array<string, mixed>>|null> $responses */
    public function __construct(private array $responses)
    {
    }

    public function revalidate(int $accountId, array $references): ?array
    {
        $this->referenceCalls[] = $references;
        $response = $this->responses[$this->calls] ?? null;
        $this->calls++;

        return $response;
    }
}

final class DeterministicFakeProvider implements GroundedAnswerProvider
{
    public int $calls = 0;

    /** @var list<array<string, mixed>> */
    public array $lastSources = [];

    /** @param array{answer: string, source_ids: list<int>} $result */
    public function __construct(
        private readonly bool $ready,
        private readonly array $result,
        private readonly bool $throwOnGenerate = false
    ) {
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function generate(string $question, array $eligibleSources): array
    {
        $this->calls++;
        $this->lastSources = $eligibleSources;

        if ($this->throwOnGenerate) {
            throw new RuntimeException('Synthetic provider failure.');
        }

        return $this->result;
    }
}

/** @param mixed $actual */
function assertSameValue(mixed $expected, mixed $actual, string $label): void
{
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            '%s failed. Expected %s; received %s.',
            $label,
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

function assertTrueValue(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }
}

/**
 * @param list<list<array<string, mixed>>|null> $eligibilityResponses
 * @param array{answer: string, source_ids: list<int>} $providerResult
 * @return array{
 *     coordinator: GroundedInquiryCoordinator,
 *     eligibility: SequenceSourceEligibility,
 *     provider: DeterministicFakeProvider
 * }
 */
function controlFixture(
    bool $enabled,
    bool $providerReady,
    array $eligibilityResponses,
    array $providerResult,
    bool $throwOnGenerate = false
): array {
    $eligibility = new SequenceSourceEligibility($eligibilityResponses);
    $provider = new DeterministicFakeProvider(
        $providerReady,
        $providerResult,
        $throwOnGenerate
    );

    return [
        'coordinator' => new GroundedInquiryCoordinator(
            new StaticFeatureAvailability($enabled),
            $eligibility,
            $provider,
            new SourceAttributionPresenter()
        ),
        'eligibility' => $eligibility,
        'provider' => $provider,
    ];
}

$reference = [
    'resource_id' => 23,
    'source_file_reference' => str_repeat('a', 64) . '.pdf',
    'evidence_text' =>
        'Synthetic repository evidence used only by the fake provider.',
];
$source = [
    'resource_id' => 23,
    'title' => 'Synthetic Approved Resource',
    'file_type' => 'pdf',
    'source_file_reference' => $reference['source_file_reference'],
];
$providerResult = [
    'answer' => 'The supplied repository evidence supports this test answer.',
    'source_ids' => [23],
];
$passed = 0;

$run = static function (string $label, callable $test) use (&$passed): void {
    $test();
    $passed++;
    echo '[PASS] ' . $label . PHP_EOL;
};

$run('Invalid requests fail before any provider call', static function () use (
    $reference,
    $source,
    $providerResult
): void {
    $fixture = controlFixture(
        true,
        true,
        [[$source], [$source]],
        $providerResult
    );
    $result = $fixture['coordinator']->respond(1, '   ', [$reference]);
    assertSameValue('invalid_request', $result['reason_code'], __FUNCTION__);
    assertSameValue(0, $fixture['provider']->calls, __FUNCTION__);
    assertSameValue(0, $fixture['eligibility']->calls, __FUNCTION__);
});

$run('Disabled AI preserves metadata-search fallback', static function () use (
    $reference,
    $providerResult
): void {
    $fixture = controlFixture(false, true, [], $providerResult);
    $result = $fixture['coordinator']->respond(
        1,
        'What does the repository say?',
        [$reference]
    );
    assertSameValue('ai_disabled', $result['reason_code'], __FUNCTION__);
    assertSameValue('/resources', $result['fallback']['href'], __FUNCTION__);
    assertSameValue(null, $result['answer'], __FUNCTION__);
    assertSameValue(0, $fixture['provider']->calls, __FUNCTION__);
});

$run('Unavailable provider fails only the AI capability', static function () use (
    $reference,
    $providerResult
): void {
    $fixture = controlFixture(true, false, [], $providerResult);
    $result = $fixture['coordinator']->respond(
        1,
        'What does the repository say?',
        [$reference]
    );
    assertSameValue('ai_unavailable', $result['reason_code'], __FUNCTION__);
    assertSameValue('/resources', $result['fallback']['href'], __FUNCTION__);
    assertSameValue(0, $fixture['provider']->calls, __FUNCTION__);
});

$run('Missing or ineligible evidence never reaches the provider', static function () use (
    $reference,
    $providerResult
): void {
    $missingEvidenceReference = $reference;
    $missingEvidenceReference['evidence_text'] = '   ';
    $missingFixture = controlFixture(true, true, [], $providerResult);
    $missingResult = $missingFixture['coordinator']->respond(
        1,
        'What does the repository say?',
        [$missingEvidenceReference]
    );
    assertSameValue(
        'evidence_unavailable',
        $missingResult['reason_code'],
        __FUNCTION__
    );
    assertSameValue(0, $missingFixture['provider']->calls, __FUNCTION__);
    assertSameValue(0, $missingFixture['eligibility']->calls, __FUNCTION__);

    $fixture = controlFixture(true, true, [null], $providerResult);
    $result = $fixture['coordinator']->respond(
        1,
        'What does the repository say?',
        [$reference]
    );
    assertSameValue(
        'evidence_unavailable',
        $result['reason_code'],
        __FUNCTION__
    );
    assertSameValue(0, $fixture['provider']->calls, __FUNCTION__);
});

$run('Provider failure returns a safe fallback', static function () use (
    $reference,
    $source,
    $providerResult
): void {
    $fixture = controlFixture(
        true,
        true,
        [[$source]],
        $providerResult,
        true
    );
    $result = $fixture['coordinator']->respond(
        1,
        'Private test question',
        [$reference]
    );
    assertSameValue('ai_unavailable', $result['reason_code'], __FUNCTION__);
    assertSameValue([], $result['sources'], __FUNCTION__);
});

$run('Unknown provider source labels disclose no answer', static function () use (
    $reference,
    $source
): void {
    $fixture = controlFixture(
        true,
        true,
        [[$source]],
        ['answer' => 'Unsafe answer', 'source_ids' => [999]]
    );
    $result = $fixture['coordinator']->respond(
        1,
        'What does the repository say?',
        [$reference]
    );
    assertSameValue(
        'evidence_unavailable',
        $result['reason_code'],
        __FUNCTION__
    );
    assertSameValue(null, $result['answer'], __FUNCTION__);
});

$run('Second-point revalidation blocks changed evidence', static function () use (
    $reference,
    $source,
    $providerResult
): void {
    $secondReference = [
        'resource_id' => 24,
        'source_file_reference' => str_repeat('b', 64) . '.pdf',
        'evidence_text' =>
            'Second synthetic repository evidence for revalidation.',
    ];
    $secondSource = [
        'resource_id' => 24,
        'title' => 'Second Synthetic Approved Resource',
        'file_type' => 'pdf',
        'source_file_reference' =>
            $secondReference['source_file_reference'],
    ];
    $fixture = controlFixture(
        true,
        true,
        [[$source, $secondSource], null],
        $providerResult
    );
    $result = $fixture['coordinator']->respond(
        1,
        'What does the repository say?',
        [$reference, $secondReference]
    );
    assertSameValue(
        'evidence_unavailable',
        $result['reason_code'],
        __FUNCTION__
    );
    assertSameValue(null, $result['answer'], __FUNCTION__);
    assertSameValue([], $result['sources'], __FUNCTION__);
    assertSameValue(2, $fixture['eligibility']->calls, __FUNCTION__);
    assertSameValue(
        2,
        count($fixture['eligibility']->referenceCalls[1]),
        __FUNCTION__
    );
});

$run('Successful answer uses protected resource links', static function () use (
    $reference,
    $source,
    $providerResult
): void {
    $fixture = controlFixture(
        true,
        true,
        [[$source], [$source]],
        $providerResult
    );
    $result = $fixture['coordinator']->respond(
        1,
        'What does the repository say?',
        [$reference],
        [23 => ['Page 1', 'Page 1', '  ']]
    );
    assertSameValue('answered', $result['status'], __FUNCTION__);
    assertSameValue('/resources/23', $result['sources'][0]['href'], __FUNCTION__);
    assertSameValue(['Page 1'], $result['sources'][0]['locators'], __FUNCTION__);
    assertSameValue(
        $reference['evidence_text'],
        $fixture['provider']->lastSources[0]['evidence_text'],
        __FUNCTION__
    );
    assertTrueValue(
        !array_key_exists(
            'source_file_reference',
            $result['sources'][0]
        ),
        __FUNCTION__
    );
});

$run('Unavailable reliable locator is omitted', static function () use (
    $reference,
    $source,
    $providerResult
): void {
    $fixture = controlFixture(
        true,
        true,
        [[$source], [$source]],
        $providerResult
    );
    $result = $fixture['coordinator']->respond(
        1,
        'What does the repository say?',
        [$reference]
    );
    assertSameValue([], $result['sources'][0]['locators'], __FUNCTION__);
});

$run('Session context stores identifiers but no question text', static function () use (
    $reference
): void {
    AiInquirySession::clear();
    $inquiryId = AiInquirySession::begin([$reference]);
    $context = AiInquirySession::current();
    assertTrueValue(is_array($context), __FUNCTION__);
    assertSameValue($inquiryId, $context['inquiry_id'], __FUNCTION__);
    assertTrueValue(!array_key_exists('question', $context), __FUNCTION__);
    AiInquirySession::clear();
    assertSameValue(null, AiInquirySession::current(), __FUNCTION__);
});

$run('Malformed session context is cleared', static function (): void {
    $_SESSION[AiInquirySession::SESSION_KEY] = [
        'inquiry_id' => 'not-valid',
    ];
    assertSameValue(null, AiInquirySession::current(), __FUNCTION__);
    assertTrueValue(
        !isset($_SESSION[AiInquirySession::SESSION_KEY]),
        __FUNCTION__
    );
});

$run('Authentication clears prior inquiry context', static function () use (
    $reference
): void {
    AiInquirySession::begin([$reference]);
    Session::authenticate(1);
    assertSameValue(null, AiInquirySession::current(), __FUNCTION__);
});

$run('Logout clears inquiry context', static function () use (
    $reference
): void {
    AiInquirySession::begin([$reference]);
    Session::logout();
    assertSameValue(null, AiInquirySession::current(), __FUNCTION__);
});

$run('Idle expiration clears inquiry context', static function () use (
    $reference
): void {
    AiInquirySession::begin([$reference]);
    $_SESSION['account_id'] = 1;
    $_SESSION['last_activity'] =
        time() - Session::IDLE_TIMEOUT_SECONDS;
    Session::start();
    assertSameValue(null, AiInquirySession::current(), __FUNCTION__);
});

$database = Database::connection();
$aiOutputCountBefore = (int) $database
    ->query('SELECT COUNT(*) FROM ai_outputs')
    ->fetchColumn();
$settingSnapshotBefore = $database->query(
    "SELECT setting_name, setting_value
     FROM system_settings
     WHERE setting_name = 'ai_enabled'"
)->fetchAll();

$run('Missing live AI setting fails closed', static function () use (
    $database
): void {
    assertSameValue(false, (new AiFeatureGate($database))->isEnabled(), __FUNCTION__);
});

$run('Live source eligibility checks account, status, file and fingerprint', static function () use (
    $database
): void {
    $row = $database->query(
        "SELECT
            r.id AS resource_id,
            r.stored_filename AS source_file_reference,
            (
                SELECT a.id
                FROM accounts a
                WHERE a.account_status = 'active'
                ORDER BY a.id
                LIMIT 1
            ) AS account_id
         FROM resources r
         WHERE r.status = 'approved'
           AND r.file_availability = 'available'
         ORDER BY r.id
         LIMIT 1"
    )->fetch();
    assertTrueValue(is_array($row), __FUNCTION__);

    $eligibility = new DatabaseAiSourceEligibility(
        $database,
        dirname(__DIR__, 2) . '/storage/uploads/resources'
    );
    $reference = [[
        'resource_id' => (int) $row['resource_id'],
        'source_file_reference' => (string) $row['source_file_reference'],
    ]];
    $eligible = $eligibility->revalidate(
        (int) $row['account_id'],
        $reference
    );
    assertTrueValue(is_array($eligible), __FUNCTION__);
    assertSameValue(1, count($eligible), __FUNCTION__);

    $reference[0]['source_file_reference'] =
        str_repeat('0', 64) . '.pdf';
    assertSameValue(
        null,
        $eligibility->revalidate((int) $row['account_id'], $reference),
        __FUNCTION__
    );
});

$run('Metadata discovery remains available with AI off', static function () use (
    $database
): void {
    $resources = (new ResourceDiscoveryRepository($database))->search([
        'q' => '',
        'course_id' => 0,
        'subject_id' => 0,
        'year_level_id' => 0,
        'resource_type_id' => 0,
        'tag_id' => 0,
    ]);
    assertTrueValue(count($resources) >= 1, __FUNCTION__);
});

$aiOutputCountAfter = (int) $database
    ->query('SELECT COUNT(*) FROM ai_outputs')
    ->fetchColumn();
$settingSnapshotAfter = $database->query(
    "SELECT setting_name, setting_value
     FROM system_settings
     WHERE setting_name = 'ai_enabled'"
)->fetchAll();

$run('Verification leaves AI database state unchanged', static function () use (
    $aiOutputCountBefore,
    $aiOutputCountAfter,
    $settingSnapshotBefore,
    $settingSnapshotAfter
): void {
    assertSameValue($aiOutputCountBefore, $aiOutputCountAfter, __FUNCTION__);
    assertSameValue($settingSnapshotBefore, $settingSnapshotAfter, __FUNCTION__);
});

assertSameValue(18, $passed, 'Expected Gate 5A check count');

echo PHP_EOL;
echo 'GATE 5A MODEL-INDEPENDENT SAFETY FOUNDATION PASSED.' . PHP_EOL;
echo 'Checks passed: ' . $passed . '/18' . PHP_EOL;
echo 'Real model/provider requests: 0' . PHP_EOL;
echo 'Database writes: 0' . PHP_EOL;
echo 'Schema changes: 0' . PHP_EOL;
echo 'User-facing AI route added: No' . PHP_EOL;
echo 'Final model or architecture selected: No' . PHP_EOL;

$_SESSION = [];
session_destroy();
ob_end_flush();
