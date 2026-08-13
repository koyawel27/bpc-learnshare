<?php

declare(strict_types=1);

use BpcLearnShare\Ai\AiFeatureGate;
use BpcLearnShare\Ai\DatabaseAiSourceEligibility;
use BpcLearnShare\Ai\GroundedAnswerProvider;
use BpcLearnShare\Ai\GroundedInquiryCoordinator;
use BpcLearnShare\Ai\SourceAttributionPresenter;
use BpcLearnShare\Core\Database;
use BpcLearnShare\Resource\ResourceDiscoveryRepository;
require dirname(__DIR__, 2) . '/src/bootstrap.php';

final class Gate5bFakeProvider implements GroundedAnswerProvider
{
    public int $calls = 0;

    /** @param null|callable(): void $onGenerate */
    public function __construct(
        private readonly bool $ready,
        private readonly int $sourceId,
        private readonly mixed $onGenerate = null
    ) {
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function generate(string $question, array $eligibleSources): array
    {
        $this->calls++;

        if (is_callable($this->onGenerate)) {
            ($this->onGenerate)();
        }

        return [
            'answer' => 'Synthetic answer used only to test final revalidation.',
            'source_ids' => [$this->sourceId],
        ];
    }
}

/** @param mixed $actual */
function gate5bAssertSame(mixed $expected, mixed $actual, string $label): void
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

function gate5bAssertTrue(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }
}

/** @param list<array<string, mixed>> $rows */
function gate5bContainsResource(array $rows, int $resourceId): bool
{
    foreach ($rows as $row) {
        if ((int) ($row['id'] ?? 0) === $resourceId) {
            return true;
        }
    }

    return false;
}

/** @return array<string, mixed> */
function gate5bResourceSnapshot(PDO $database, int $resourceId): array
{
    $statement = $database->prepare(
        'SELECT * FROM resources WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $resourceId]);
    $row = $statement->fetch();

    if (!is_array($row)) {
        throw new RuntimeException('The selected lifecycle resource disappeared.');
    }

    return $row;
}

/** @return array<string, mixed> */
function gate5bAccountSnapshot(PDO $database, int $accountId): array
{
    $statement = $database->prepare(
        'SELECT * FROM accounts WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $accountId]);
    $row = $statement->fetch();

    if (!is_array($row)) {
        throw new RuntimeException('The selected lifecycle account disappeared.');
    }

    return $row;
}

/** @return list<array<string, mixed>> */
function gate5bAiSettingSnapshot(PDO $database): array
{
    return $database->query(
        "SELECT *
         FROM system_settings
         WHERE setting_name = 'ai_enabled'
         ORDER BY id"
    )->fetchAll();
}

$database = Database::connection();
$storageDirectory = dirname(__DIR__, 2) . '/storage/uploads/resources';
$eligibility = new DatabaseAiSourceEligibility($database, $storageDirectory);
$discovery = new ResourceDiscoveryRepository($database);

$accountIds = $database->query(
    "SELECT id
     FROM accounts
     WHERE account_status = 'active'
     ORDER BY id"
)->fetchAll(PDO::FETCH_COLUMN);

$resourceRows = $database->query(
    "SELECT id, title, stored_filename, file_type, file_size
     FROM resources
     WHERE status = 'approved'
       AND file_availability = 'available'
     ORDER BY id"
)->fetchAll();

$selected = null;

foreach ($accountIds as $accountIdValue) {
    foreach ($resourceRows as $resourceRow) {
        $candidateReference = [[
            'resource_id' => (int) $resourceRow['id'],
            'source_file_reference' =>
                (string) $resourceRow['stored_filename'],
        ]];
        $candidate = $eligibility->revalidate(
            (int) $accountIdValue,
            $candidateReference
        );

        if (is_array($candidate) && count($candidate) === 1) {
            $selected = [
                'account_id' => (int) $accountIdValue,
                'resource' => $resourceRow,
                'reference' => $candidateReference,
            ];
            break 2;
        }
    }
}

if (!is_array($selected)) {
    throw new RuntimeException(
        'Gate 5B requires one Active account and one Approved, available, '
        . 'physically valid resource.'
    );
}

$accountId = $selected['account_id'];
$resource = $selected['resource'];
$resourceId = (int) $resource['id'];
$reference = $selected['reference'];
$storedFilename = (string) $resource['stored_filename'];
$fileType = (string) $resource['file_type'];
$fileSize = (int) $resource['file_size'];
$resourceFile = $storageDirectory . DIRECTORY_SEPARATOR . $storedFilename;
$fileHashBefore = hash_file('sha256', $resourceFile);

if (!is_string($fileHashBefore)) {
    throw new RuntimeException('Unable to fingerprint the selected file.');
}

$resourceBefore = gate5bResourceSnapshot($database, $resourceId);
$accountBefore = gate5bAccountSnapshot($database, $accountId);
$settingBefore = gate5bAiSettingSnapshot($database);
$aiOutputCountBefore = (int) $database
    ->query('SELECT COUNT(*) FROM ai_outputs')
    ->fetchColumn();
$auditCountBefore = (int) $database
    ->query('SELECT COUNT(*) FROM audit_log')
    ->fetchColumn();

$missingFilename = hash(
    'sha256',
    'gate5b-missing-' . $resourceId . '-' . $storedFilename
) . '.' . $fileType;

if (is_file($storageDirectory . DIRECTORY_SEPARATOR . $missingFilename)) {
    throw new RuntimeException(
        'The synthetic missing-file reference unexpectedly exists.'
    );
}

$passed = 0;
$run = static function (string $label, callable $test) use (&$passed): void {
    $test();
    $passed++;
    echo '[PASS] ' . $label . PHP_EOL;
};

$restoreResource = static function () use (
    $database,
    $resourceId,
    $storedFilename,
    $fileSize
): void {
    $statement = $database->prepare(
        "UPDATE resources
         SET status = 'approved',
             file_availability = 'available',
             stored_filename = :stored_filename,
             file_size = :file_size
         WHERE id = :id"
    );
    $statement->execute([
        'stored_filename' => $storedFilename,
        'file_size' => $fileSize,
        'id' => $resourceId,
    ]);
};

$setStatus = static function (string $status) use (
    $database,
    $resourceId,
    $restoreResource
): void {
    $restoreResource();
    $statement = $database->prepare(
        'UPDATE resources SET status = :status WHERE id = :id'
    );
    $statement->execute(['status' => $status, 'id' => $resourceId]);
};

$setAvailability = static function (string $availability) use (
    $database,
    $resourceId,
    $restoreResource
): void {
    $restoreResource();
    $statement = $database->prepare(
        'UPDATE resources
         SET file_availability = :availability
         WHERE id = :id'
    );
    $statement->execute([
        'availability' => $availability,
        'id' => $resourceId,
    ]);
};

$setAiSetting = static function (?string $value) use ($database): void {
    $database->exec(
        "DELETE FROM system_settings WHERE setting_name = 'ai_enabled'"
    );

    if ($value === null) {
        return;
    }

    $statement = $database->prepare(
        "INSERT INTO system_settings
            (setting_name, setting_value, updated_by_account_id)
         VALUES ('ai_enabled', :setting_value, NULL)"
    );
    $statement->execute(['setting_value' => $value]);
};

$database->beginTransaction();

try {
    $run('Baseline live source is eligible', static function () use (
        $eligibility,
        $accountId,
        $reference
    ): void {
        $result = $eligibility->revalidate($accountId, $reference);
        gate5bAssertTrue(is_array($result), __FUNCTION__);
        gate5bAssertSame(1, count($result), __FUNCTION__);
    });

    foreach (['hidden', 'restricted', 'removed', 'replaced'] as $status) {
        $run(
            ucfirst($status) . ' source is excluded from new AI evidence',
            static function () use (
                $setStatus,
                $status,
                $eligibility,
                $accountId,
                $reference
            ): void {
                $setStatus($status);
                gate5bAssertSame(
                    null,
                    $eligibility->revalidate($accountId, $reference),
                    __FUNCTION__ . '-' . $status
                );
            }
        );
    }

    foreach (['deleted', 'invalidated'] as $availability) {
        $run(
            ucfirst($availability) . ' file state is excluded from AI evidence',
            static function () use (
                $setAvailability,
                $availability,
                $eligibility,
                $accountId,
                $reference
            ): void {
                $setAvailability($availability);
                gate5bAssertSame(
                    null,
                    $eligibility->revalidate($accountId, $reference),
                    __FUNCTION__ . '-' . $availability
                );
            }
        );
    }

    $run('Changed source fingerprint makes old evidence stale', static function () use (
        $database,
        $resourceId,
        $restoreResource,
        $missingFilename,
        $eligibility,
        $accountId,
        $reference
    ): void {
        $restoreResource();
        $statement = $database->prepare(
            'UPDATE resources SET stored_filename = :filename WHERE id = :id'
        );
        $statement->execute([
            'filename' => $missingFilename,
            'id' => $resourceId,
        ]);
        gate5bAssertSame(
            null,
            $eligibility->revalidate($accountId, $reference),
            __FUNCTION__
        );
    });

    $run('Unavailable protected file fails closed', static function () use (
        $database,
        $resourceId,
        $restoreResource,
        $missingFilename,
        $eligibility,
        $accountId
    ): void {
        $restoreResource();
        $statement = $database->prepare(
            'UPDATE resources SET stored_filename = :filename WHERE id = :id'
        );
        $statement->execute([
            'filename' => $missingFilename,
            'id' => $resourceId,
        ]);
        $missingReference = [[
            'resource_id' => $resourceId,
            'source_file_reference' => $missingFilename,
        ]];
        gate5bAssertSame(
            null,
            $eligibility->revalidate($accountId, $missingReference),
            __FUNCTION__
        );
    });

    $run('File-size drift makes current evidence ineligible', static function () use (
        $database,
        $resourceId,
        $restoreResource,
        $fileSize,
        $eligibility,
        $accountId,
        $reference
    ): void {
        $restoreResource();
        $statement = $database->prepare(
            'UPDATE resources SET file_size = :file_size WHERE id = :id'
        );
        $statement->execute([
            'file_size' => $fileSize + 1,
            'id' => $resourceId,
        ]);
        gate5bAssertSame(
            null,
            $eligibility->revalidate($accountId, $reference),
            __FUNCTION__
        );
    });

    $run('Disabled account cannot use otherwise eligible evidence', static function () use (
        $database,
        $accountId,
        $restoreResource,
        $eligibility,
        $reference
    ): void {
        $restoreResource();
        $statement = $database->prepare(
            "UPDATE accounts
             SET account_status = 'disabled'
             WHERE id = :id"
        );
        $statement->execute(['id' => $accountId]);
        gate5bAssertSame(
            null,
            $eligibility->revalidate($accountId, $reference),
            __FUNCTION__
        );
        $database->prepare(
            "UPDATE accounts SET account_status = 'active' WHERE id = :id"
        )->execute(['id' => $accountId]);
    });

    $run('Missing AI setting fails closed', static function () use (
        $setAiSetting,
        $database
    ): void {
        $setAiSetting(null);
        gate5bAssertSame(
            false,
            (new AiFeatureGate($database))->isEnabled(),
            __FUNCTION__
        );
    });

    $run('Enabled AI setting is read exactly', static function () use (
        $setAiSetting,
        $database
    ): void {
        $setAiSetting('enabled');
        gate5bAssertSame(
            true,
            (new AiFeatureGate($database))->isEnabled(),
            __FUNCTION__
        );
    });

    $run('Disabled AI setting fails closed', static function () use (
        $setAiSetting,
        $database
    ): void {
        $setAiSetting('disabled');
        gate5bAssertSame(
            false,
            (new AiFeatureGate($database))->isEnabled(),
            __FUNCTION__
        );
    });

    $run('Disabled AI returns safe fallback without provider use', static function () use (
        $setAiSetting,
        $database,
        $eligibility,
        $resourceId,
        $accountId,
        $reference
    ): void {
        $setAiSetting('disabled');
        $provider = new Gate5bFakeProvider(true, $resourceId);
        $coordinator = new GroundedInquiryCoordinator(
            new AiFeatureGate($database),
            $eligibility,
            $provider,
            new SourceAttributionPresenter()
        );
        $result = $coordinator->respond(
            $accountId,
            'What does this approved resource explain?',
            [[
                'resource_id' => $resourceId,
                'source_file_reference' =>
                    $reference[0]['source_file_reference'],
                'evidence_text' => 'Synthetic bounded evidence.',
            ]]
        );
        gate5bAssertSame('ai_disabled', $result['reason_code'], __FUNCTION__);
        gate5bAssertSame(null, $result['answer'], __FUNCTION__);
        gate5bAssertSame([], $result['sources'], __FUNCTION__);
        gate5bAssertSame(0, $provider->calls, __FUNCTION__);
    });

    $run('Unavailable provider preserves safe fallback', static function () use (
        $setAiSetting,
        $database,
        $eligibility,
        $resourceId,
        $accountId,
        $reference
    ): void {
        $setAiSetting('enabled');
        $provider = new Gate5bFakeProvider(false, $resourceId);
        $coordinator = new GroundedInquiryCoordinator(
            new AiFeatureGate($database),
            $eligibility,
            $provider,
            new SourceAttributionPresenter()
        );
        $result = $coordinator->respond(
            $accountId,
            'What does this approved resource explain?',
            [[
                'resource_id' => $resourceId,
                'source_file_reference' =>
                    $reference[0]['source_file_reference'],
                'evidence_text' => 'Synthetic bounded evidence.',
            ]]
        );
        gate5bAssertSame('ai_unavailable', $result['reason_code'], __FUNCTION__);
        gate5bAssertSame(null, $result['answer'], __FUNCTION__);
        gate5bAssertSame([], $result['sources'], __FUNCTION__);
        gate5bAssertSame(0, $provider->calls, __FUNCTION__);
    });

    $run('Final live revalidation blocks a newly Hidden source', static function () use (
        $setAiSetting,
        $database,
        $eligibility,
        $resourceId,
        $accountId,
        $reference,
        $restoreResource
    ): void {
        $restoreResource();
        $setAiSetting('enabled');
        $provider = new Gate5bFakeProvider(
            true,
            $resourceId,
            static function () use ($database, $resourceId): void {
                $database->prepare(
                    "UPDATE resources SET status = 'hidden' WHERE id = :id"
                )->execute(['id' => $resourceId]);
            }
        );
        $coordinator = new GroundedInquiryCoordinator(
            new AiFeatureGate($database),
            $eligibility,
            $provider,
            new SourceAttributionPresenter()
        );
        $result = $coordinator->respond(
            $accountId,
            'What does this approved resource explain?',
            [[
                'resource_id' => $resourceId,
                'source_file_reference' =>
                    $reference[0]['source_file_reference'],
                'evidence_text' => 'Synthetic bounded evidence.',
            ]]
        );
        gate5bAssertSame(
            'evidence_unavailable',
            $result['reason_code'],
            __FUNCTION__
        );
        gate5bAssertSame(null, $result['answer'], __FUNCTION__);
        gate5bAssertSame([], $result['sources'], __FUNCTION__);
        gate5bAssertSame(1, $provider->calls, __FUNCTION__);
    });

    $run('Hidden source is absent from metadata search and download lookup', static function () use (
        $setStatus,
        $discovery,
        $resourceId,
        $resource
    ): void {
        $setStatus('hidden');
        $results = $discovery->search([
            'q' => (string) $resource['title'],
            'course_id' => 0,
            'subject_id' => 0,
            'year_level_id' => 0,
            'resource_type_id' => 0,
            'tag_id' => 0,
        ]);
        gate5bAssertSame(
            false,
            gate5bContainsResource($results, $resourceId),
            __FUNCTION__
        );
        gate5bAssertSame(
            null,
            $discovery->availableDownload($resourceId),
            __FUNCTION__
        );
    });

    $run('Metadata search and download lookup work while AI is disabled', static function () use (
        $restoreResource,
        $setAiSetting,
        $discovery,
        $resourceId,
        $resource
    ): void {
        $restoreResource();
        $setAiSetting('disabled');
        $results = $discovery->search([
            'q' => (string) $resource['title'],
            'course_id' => 0,
            'subject_id' => 0,
            'year_level_id' => 0,
            'resource_type_id' => 0,
            'tag_id' => 0,
        ]);
        gate5bAssertSame(
            true,
            gate5bContainsResource($results, $resourceId),
            __FUNCTION__
        );
        gate5bAssertTrue(
            is_array($discovery->availableDownload($resourceId)),
            __FUNCTION__
        );
    });

    gate5bAssertSame(19, $passed, 'Expected Gate 5B check count');
    $database->rollBack();
} catch (Throwable $exception) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }

    fwrite(STDERR, 'GATE 5B FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

$resourceAfter = gate5bResourceSnapshot($database, $resourceId);
$accountAfter = gate5bAccountSnapshot($database, $accountId);
$settingAfter = gate5bAiSettingSnapshot($database);
$aiOutputCountAfter = (int) $database
    ->query('SELECT COUNT(*) FROM ai_outputs')
    ->fetchColumn();
$auditCountAfter = (int) $database
    ->query('SELECT COUNT(*) FROM audit_log')
    ->fetchColumn();
$fileHashAfter = hash_file('sha256', $resourceFile);

try {
    gate5bAssertSame($resourceBefore, $resourceAfter, 'Resource rollback');
    gate5bAssertSame($accountBefore, $accountAfter, 'Account rollback');
    gate5bAssertSame($settingBefore, $settingAfter, 'AI setting rollback');
    gate5bAssertSame(
        $aiOutputCountBefore,
        $aiOutputCountAfter,
        'AI output count'
    );
    gate5bAssertSame($auditCountBefore, $auditCountAfter, 'Audit count');
    gate5bAssertSame($fileHashBefore, $fileHashAfter, 'Protected file hash');
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'GATE 5B RESTORATION FAILED: ' . $exception->getMessage() . PHP_EOL
    );
    exit(1);
}

echo PHP_EOL;
echo 'GATE 5B LIVE LIFECYCLE AND FALLBACK VALIDATION PASSED.' . PHP_EOL;
echo 'Checks passed: ' . $passed . '/19' . PHP_EOL;
echo 'Database transaction committed: No (rolled back)' . PHP_EOL;
echo 'Persistent database changes: 0' . PHP_EOL;
echo 'Protected files modified: 0' . PHP_EOL;
echo 'Real model/provider requests: 0' . PHP_EOL;
echo 'Retrieval or embedding reruns: 0' . PHP_EOL;
echo 'Schema changes: 0' . PHP_EOL;
echo 'User-facing AI route added: No' . PHP_EOL;
echo 'Final model or architecture selected: No' . PHP_EOL;
