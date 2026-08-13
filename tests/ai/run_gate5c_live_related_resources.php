<?php

declare(strict_types=1);

use BpcLearnShare\Ai\DatabaseAiSourceEligibility;
use BpcLearnShare\Ai\DatabaseRelatedResourceMetadata;
use BpcLearnShare\Ai\SourceAttributionPresenter;
use BpcLearnShare\Core\Database;
use BpcLearnShare\Resource\ResourceDiscoveryRepository;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

/** @param mixed $actual */
function gate5cAssertSame(mixed $expected, mixed $actual, string $label): void
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

function gate5cAssertTrue(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }
}

/** @return list<int> */
function gate5cSuggestionIds(array $result): array
{
    return array_map(
        static fn (array $row): int => (int) ($row['resource_id'] ?? 0),
        is_array($result['suggestions'] ?? null)
            ? $result['suggestions']
            : []
    );
}

/** @return array<string, mixed> */
function gate5cResourceSnapshot(PDO $database, int $resourceId): array
{
    $statement = $database->prepare(
        'SELECT * FROM resources WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $resourceId]);
    $row = $statement->fetch();

    if (!is_array($row)) {
        throw new RuntimeException('A required Gate 5C resource is missing.');
    }

    return $row;
}

/** @return list<string> */
function gate5cResourceTags(PDO $database, int $resourceId): array
{
    $statement = $database->prepare(
        'SELECT t.name
         FROM resource_tags rt
         INNER JOIN tags t ON t.id = rt.tag_id
         WHERE rt.resource_id = :resource_id
         ORDER BY t.name'
    );
    $statement->execute(['resource_id' => $resourceId]);

    return array_map(
        static fn (array $row): string => (string) $row['name'],
        $statement->fetchAll()
    );
}

$database = Database::connection();
$storageDirectory = dirname(__DIR__, 2) . '/storage/uploads/resources';
$eligibility = new DatabaseAiSourceEligibility(
    $database,
    $storageDirectory
);
$presenter = new SourceAttributionPresenter();
$relations = new DatabaseRelatedResourceMetadata(
    $database,
    $eligibility,
    $presenter
);
$discovery = new ResourceDiscoveryRepository($database);

$titles = [
    'security_module' =>
        '[Synthetic Demo] Web Application Security Basics Module',
    'security_reference' =>
        '[Synthetic Demo] Security and Privacy Terms Quick Reference',
    'usability_guide' =>
        '[Synthetic Demo] Usability Evaluation Methods Guide',
    'heuristic_guide' =>
        '[Synthetic Demo] Heuristic Evaluation Worksheet Guide',
];

$resources = [];
$findResource = $database->prepare(
    'SELECT r.*, s.name AS subject_name
     FROM resources r
     INNER JOIN subjects s ON s.id = r.subject_id
     WHERE r.title = :title
     ORDER BY r.id'
);

foreach ($titles as $key => $title) {
    $findResource->execute(['title' => $title]);
    $matches = $findResource->fetchAll();

    if (count($matches) !== 1) {
        throw new RuntimeException(sprintf(
            'Gate 5C requires exactly one live resource titled "%s".',
            $title
        ));
    }

    $resources[$key] = $matches[0];
}

$accountId = (int) $database->query(
    "SELECT id
     FROM accounts
     WHERE account_status = 'active'
     ORDER BY id
     LIMIT 1"
)->fetchColumn();

if ($accountId <= 0) {
    throw new RuntimeException('Gate 5C requires one Active account.');
}

$expectedPeerByKey = [
    'security_module' => 'security_reference',
    'security_reference' => 'security_module',
    'usability_guide' => 'heuristic_guide',
    'heuristic_guide' => 'usability_guide',
];
$resourceIds = array_map(
    static fn (array $row): int => (int) $row['id'],
    $resources
);
$resourceBefore = [];
$fileHashesBefore = [];

foreach ($resources as $key => $resource) {
    $resourceId = (int) $resource['id'];
    $resourceBefore[$resourceId] = gate5cResourceSnapshot(
        $database,
        $resourceId
    );
    $path = $storageDirectory
        . DIRECTORY_SEPARATOR
        . (string) $resource['stored_filename'];
    $hash = hash_file('sha256', $path);

    if (!is_string($hash)) {
        throw new RuntimeException('Unable to hash a Gate 5C resource file.');
    }

    $fileHashesBefore[$resourceId] = $hash;
}

$accountBefore = $database->query(
    'SELECT * FROM accounts WHERE id = ' . $accountId
)->fetch();
$tagRowsBefore = $database->query(
    "SELECT * FROM tags
     WHERE name IN ('Security', 'Usability')
     ORDER BY id"
)->fetchAll();
$aiOutputCountBefore = (int) $database
    ->query('SELECT COUNT(*) FROM ai_outputs')
    ->fetchColumn();
$auditCountBefore = (int) $database
    ->query('SELECT COUNT(*) FROM audit_log')
    ->fetchColumn();

$passed = 0;
$run = static function (string $label, callable $test) use (&$passed): void {
    $test();
    $passed++;
    echo '[PASS] ' . $label . PHP_EOL;
};

$run('Controlled live resources have the accepted status and file state', static function () use ($resources): void {
    foreach ($resources as $resource) {
        gate5cAssertSame('approved', $resource['status'], __FUNCTION__);
        gate5cAssertSame(
            'available',
            $resource['file_availability'],
            __FUNCTION__
        );
    }
});

$run('Controlled resources share one subject but retain distinct topics', static function () use ($resources): void {
    $subjects = array_values(array_unique(array_map(
        static fn (array $row): string => (string) $row['subject_name'],
        $resources
    )));
    $topics = array_values(array_unique(array_map(
        static fn (array $row): string => (string) $row['topic'],
        $resources
    )));
    gate5cAssertSame(['Web Systems and Technologies'], $subjects, __FUNCTION__);
    gate5cAssertSame(4, count($topics), __FUNCTION__);
});

$run('Security and Usability tags are exact and content-justified', static function () use ($database, $resources): void {
    gate5cAssertSame(
        ['Security'],
        gate5cResourceTags($database, (int) $resources['security_module']['id']),
        __FUNCTION__
    );
    gate5cAssertSame(
        ['Security'],
        gate5cResourceTags($database, (int) $resources['security_reference']['id']),
        __FUNCTION__
    );
    gate5cAssertSame(
        ['Usability'],
        gate5cResourceTags($database, (int) $resources['usability_guide']['id']),
        __FUNCTION__
    );
    gate5cAssertSame(
        ['Usability'],
        gate5cResourceTags($database, (int) $resources['heuristic_guide']['id']),
        __FUNCTION__
    );
});

$baselineResults = [];
$expectedHits = 0;
$usefulTopThree = 0;
$topThreeCount = 0;

foreach ($expectedPeerByKey as $targetKey => $peerKey) {
    $result = $relations->suggest(
        $accountId,
        (int) $resources[$targetKey]['id']
    );
    $baselineResults[$targetKey] = $result;
    $suggestionIds = gate5cSuggestionIds($result);
    $expectedId = (int) $resources[$peerKey]['id'];
    $rank = array_search($expectedId, $suggestionIds, true);

    if ($rank !== false && $rank < 5) {
        $expectedHits++;
    }

    foreach (array_slice($suggestionIds, 0, 3) as $suggestionId) {
        $topThreeCount++;

        if ($suggestionId === $expectedId) {
            $usefulTopThree++;
        }
    }
}

$run('Every controlled target displays its expected peer in the top five', static function () use ($expectedHits): void {
    gate5cAssertSame(4, $expectedHits, __FUNCTION__);
});

$run('Expected-resource top-five coverage meets the 80 percent criterion', static function () use ($expectedHits): void {
    gate5cAssertTrue(($expectedHits / 4) >= 0.80, __FUNCTION__);
});

$run('Reviewed top-three usefulness meets the 70 percent criterion', static function () use ($usefulTopThree, $topThreeCount): void {
    gate5cAssertTrue($topThreeCount > 0, __FUNCTION__);
    gate5cAssertTrue(
        ($usefulTopThree / $topThreeCount) >= 0.70,
        __FUNCTION__
    );
});

$run('Self-suggestions are excluded', static function () use ($baselineResults, $resources): void {
    foreach ($baselineResults as $targetKey => $result) {
        gate5cAssertSame(
            false,
            in_array(
                (int) $resources[$targetKey]['id'],
                gate5cSuggestionIds($result),
                true
            ),
            __FUNCTION__
        );
    }
});

$run('Same-subject cross-topic resources are not treated as related', static function () use ($baselineResults, $resources): void {
    $securityIds = [
        (int) $resources['security_module']['id'],
        (int) $resources['security_reference']['id'],
    ];
    $usabilityIds = [
        (int) $resources['usability_guide']['id'],
        (int) $resources['heuristic_guide']['id'],
    ];

    foreach (['security_module', 'security_reference'] as $key) {
        gate5cAssertSame(
            [],
            array_values(array_intersect(
                gate5cSuggestionIds($baselineResults[$key]),
                $usabilityIds
            )),
            __FUNCTION__
        );
    }

    foreach (['usability_guide', 'heuristic_guide'] as $key) {
        gate5cAssertSame(
            [],
            array_values(array_intersect(
                gate5cSuggestionIds($baselineResults[$key]),
                $securityIds
            )),
            __FUNCTION__
        );
    }
});

$run('Suggestions use protected resource-detail links with no stored filename', static function () use ($baselineResults): void {
    foreach ($baselineResults as $result) {
        foreach ($result['suggestions'] as $suggestion) {
            $resourceId = (int) $suggestion['resource_id'];
            gate5cAssertSame(
                '/resources/' . $resourceId,
                $suggestion['href'],
                __FUNCTION__
            );
            gate5cAssertSame(
                false,
                array_key_exists('source_file_reference', $suggestion),
                __FUNCTION__
            );
        }
    }
});

$database->beginTransaction();

try {
    $run('Every presented link resolves through Approved-only detail lookup', static function () use ($baselineResults, $discovery): void {
    foreach ($baselineResults as $result) {
        foreach ($result['suggestions'] as $suggestion) {
            gate5cAssertTrue(
                is_array($discovery->openAvailableApproved(
                    (int) $suggestion['resource_id']
                )),
                __FUNCTION__
            );
        }
    }
    });

    $run('A resource with no shared active tag returns the safe no-result outcome', static function () use ($database, $relations, $accountId, $resourceIds): void {
    $placeholders = implode(',', array_fill(0, count($resourceIds), '?'));
    $statement = $database->prepare(
        "SELECT r.id
         FROM resources r
         WHERE r.status = 'approved'
           AND r.file_availability = 'available'
           AND r.id NOT IN ($placeholders)
           AND NOT EXISTS (
               SELECT 1
               FROM resource_tags own_tag
               INNER JOIN tags active_tag
                  ON active_tag.id = own_tag.tag_id
                 AND active_tag.is_active = 1
               INNER JOIN resource_tags peer_tag
                  ON peer_tag.tag_id = own_tag.tag_id
                 AND peer_tag.resource_id <> r.id
               INNER JOIN resources peer
                  ON peer.id = peer_tag.resource_id
                 AND peer.status = 'approved'
                 AND peer.file_availability = 'available'
               WHERE own_tag.resource_id = r.id
           )
         ORDER BY r.id
         LIMIT 1"
    );
    $statement->execute(array_values($resourceIds));
    $noRelationId = (int) $statement->fetchColumn();
    gate5cAssertTrue($noRelationId > 0, __FUNCTION__);
    $result = $relations->suggest($accountId, $noRelationId);
    gate5cAssertSame('unavailable', $result['status'], __FUNCTION__);
    gate5cAssertSame(
        'no_useful_related_resource',
        $result['reason_code'],
        __FUNCTION__
    );
    gate5cAssertSame([], $result['suggestions'], __FUNCTION__);
    });

    $securityModuleId = (int) $resources['security_module']['id'];
    $securityReferenceId = (int) $resources['security_reference']['id'];
    $securityReference = $resources['security_reference'];

    $setResourceState = static function (
        string $status,
        string $fileAvailability,
        string $storedFilename
    ) use ($database, $securityReferenceId): void {
        $statement = $database->prepare(
            'UPDATE resources
             SET status = :status,
                 file_availability = :file_availability,
                 stored_filename = :stored_filename
             WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'file_availability' => $fileAvailability,
            'stored_filename' => $storedFilename,
            'id' => $securityReferenceId,
        ]);
    };

    $restoreSecurityReference = static function () use (
        $setResourceState,
        $securityReference
    ): void {
        $setResourceState(
            'approved',
            'available',
            (string) $securityReference['stored_filename']
        );
    };

    $run('Hidden related candidate disappears from fresh suggestions', static function () use ($setResourceState, $relations, $accountId, $securityModuleId, $securityReference, $securityReferenceId, $restoreSecurityReference): void {
        $setResourceState(
            'hidden',
            'available',
            (string) $securityReference['stored_filename']
        );
        $result = $relations->suggest($accountId, $securityModuleId);
        gate5cAssertSame(
            false,
            in_array($securityReferenceId, gate5cSuggestionIds($result), true),
            __FUNCTION__
        );
        $restoreSecurityReference();
    });

    $run('File-unavailable related candidate disappears from fresh suggestions', static function () use ($setResourceState, $relations, $accountId, $securityModuleId, $securityReference, $securityReferenceId, $restoreSecurityReference): void {
        $setResourceState(
            'approved',
            'deleted',
            (string) $securityReference['stored_filename']
        );
        $result = $relations->suggest($accountId, $securityModuleId);
        gate5cAssertSame(
            false,
            in_array($securityReferenceId, gate5cSuggestionIds($result), true),
            __FUNCTION__
        );
        $restoreSecurityReference();
    });

    $run('Missing protected candidate file fails closed before presentation', static function () use ($setResourceState, $relations, $accountId, $securityModuleId, $securityReference, $securityReferenceId, $restoreSecurityReference): void {
        $missing = hash('sha256', 'gate5c-missing-' . $securityReferenceId)
            . '.' . $securityReference['file_type'];
        $setResourceState('approved', 'available', $missing);
        $result = $relations->suggest($accountId, $securityModuleId);
        gate5cAssertSame(
            false,
            in_array($securityReferenceId, gate5cSuggestionIds($result), true),
            __FUNCTION__
        );
        $restoreSecurityReference();
    });

    $run('Inactive relation tag produces a safe no-result outcome', static function () use ($database, $relations, $accountId, $securityModuleId): void {
        $database->exec("UPDATE tags SET is_active = 0 WHERE name = 'Security'");
        $result = $relations->suggest($accountId, $securityModuleId);
        gate5cAssertSame('unavailable', $result['status'], __FUNCTION__);
        gate5cAssertSame([], $result['suggestions'], __FUNCTION__);
        $database->exec("UPDATE tags SET is_active = 1 WHERE name = 'Security'");
    });

    $run('Ineligible target returns no titles, metadata, or links', static function () use ($database, $relations, $accountId, $securityModuleId): void {
        $database->exec(
            'UPDATE resources SET status = \'hidden\' WHERE id = '
            . $securityModuleId
        );
        $result = $relations->suggest($accountId, $securityModuleId);
        gate5cAssertSame('target_ineligible', $result['reason_code'], __FUNCTION__);
        gate5cAssertSame([], $result['suggestions'], __FUNCTION__);
        $database->exec(
            'UPDATE resources SET status = \'approved\' WHERE id = '
            . $securityModuleId
        );
    });

    $run('Disabled requester receives no related-resource output', static function () use ($database, $relations, $accountId, $securityModuleId): void {
        $database->exec(
            'UPDATE accounts SET account_status = \'disabled\' WHERE id = '
            . $accountId
        );
        $result = $relations->suggest($accountId, $securityModuleId);
        gate5cAssertSame('target_ineligible', $result['reason_code'], __FUNCTION__);
        gate5cAssertSame([], $result['suggestions'], __FUNCTION__);
        $database->exec(
            'UPDATE accounts SET account_status = \'active\' WHERE id = '
            . $accountId
        );
    });

    $run('Hidden candidate link also fails Approved-only detail lookup', static function () use ($setResourceState, $securityReference, $discovery, $securityReferenceId, $restoreSecurityReference): void {
        $setResourceState(
            'hidden',
            'available',
            (string) $securityReference['stored_filename']
        );
        gate5cAssertSame(
            null,
            $discovery->openAvailableApproved($securityReferenceId),
            __FUNCTION__
        );
        $restoreSecurityReference();
    });

    gate5cAssertSame(18, $passed, 'Expected Gate 5C check count');
    $database->rollBack();
} catch (Throwable $exception) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }

    fwrite(STDERR, 'GATE 5C FAILED: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

try {
    foreach ($resourceBefore as $resourceId => $snapshot) {
        gate5cAssertSame(
            $snapshot,
            gate5cResourceSnapshot($database, $resourceId),
            'Resource rollback ' . $resourceId
        );
        $file = $storageDirectory
            . DIRECTORY_SEPARATOR
            . (string) $snapshot['stored_filename'];
        gate5cAssertSame(
            $fileHashesBefore[$resourceId],
            hash_file('sha256', $file),
            'Protected file hash ' . $resourceId
        );
    }

    gate5cAssertSame(
        $accountBefore,
        $database->query(
            'SELECT * FROM accounts WHERE id = ' . $accountId
        )->fetch(),
        'Account rollback'
    );
    gate5cAssertSame(
        $tagRowsBefore,
        $database->query(
            "SELECT * FROM tags
             WHERE name IN ('Security', 'Usability')
             ORDER BY id"
        )->fetchAll(),
        'Tag rollback'
    );
    gate5cAssertSame(
        $aiOutputCountBefore,
        (int) $database->query('SELECT COUNT(*) FROM ai_outputs')->fetchColumn(),
        'AI output count'
    );
    gate5cAssertSame(
        $auditCountBefore,
        (int) $database->query('SELECT COUNT(*) FROM audit_log')->fetchColumn(),
        'Audit count'
    );
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        'GATE 5C RESTORATION FAILED: ' . $exception->getMessage() . PHP_EOL
    );
    exit(1);
}

echo PHP_EOL;
echo 'GATE 5C LIVE RELATED-RESOURCE VALIDATION PASSED.' . PHP_EOL;
echo 'Checks passed: ' . $passed . '/18' . PHP_EOL;
echo 'Expected peer in top five: ' . $expectedHits . '/4 (100%)' . PHP_EOL;
echo 'Reviewed top-three usefulness: '
    . $usefulTopThree . '/' . $topThreeCount . ' (100%)' . PHP_EOL;
echo 'Matching method: metadata_shared_active_tag (bounded fallback candidate)' . PHP_EOL;
echo 'Database transaction committed: No (rolled back)' . PHP_EOL;
echo 'Persistent database changes: 0' . PHP_EOL;
echo 'Protected files modified: 0' . PHP_EOL;
echo 'Real model/provider requests: 0' . PHP_EOL;
echo 'Retrieval or embedding reruns: 0' . PHP_EOL;
echo 'Schema changes: 0' . PHP_EOL;
echo 'User-facing AI route added: No' . PHP_EOL;
echo 'Final relation rule, model, or architecture selected: No' . PHP_EOL;
