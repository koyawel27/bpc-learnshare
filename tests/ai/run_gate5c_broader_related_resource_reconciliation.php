<?php

declare(strict_types=1);

use BpcLearnShare\Core\Database;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

$checks = 0;

/** @param mixed $actual */
function broaderAssertSame(mixed $expected, mixed $actual, string $label): void
{
    global $checks;

    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            '%s failed. Expected %s; received %s.',
            $label,
            var_export($expected, true),
            var_export($actual, true)
        ));
    }

    $checks++;
}

function broaderAssertTrue(bool $condition, string $label): void
{
    global $checks;

    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }

    $checks++;
}

/** @return list<array<string, string>> */
function broaderReadCsv(string $path): array
{
    $handle = fopen($path, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Unable to open CSV: ' . $path);
    }

    try {
        $headers = fgetcsv($handle, 0, ',', '"', '');

        if (!is_array($headers)) {
            throw new RuntimeException('CSV has no header: ' . $path);
        }

        $headers[0] = str_replace("\xEF\xBB\xBF", '', $headers[0]);
        $headers = array_map(
            static fn (string $header): string => trim(
                $header,
                "\" \t\n\r\0\x0B"
            ),
            $headers
        );
        $rows = [];

        while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($values === [null] || $values === []) {
                continue;
            }

            if (count($values) !== count($headers)) {
                throw new RuntimeException('Malformed CSV row: ' . $path);
            }

            $row = array_combine($headers, $values);

            if (!is_array($row)) {
                throw new RuntimeException('Unable to combine CSV row.');
            }

            $rows[] = $row;
        }

        return $rows;
    } finally {
        fclose($handle);
    }
}

/** @param list<array<string, string>> $rows */
function broaderFilterRows(
    array $rows,
    string $scenarioId,
    string $ruleId
): array {
    return array_values(array_filter(
        $rows,
        static fn (array $row): bool =>
            ($row['scenario_id'] ?? null) === $scenarioId
            && ($row['rule_id'] ?? null) === $ruleId
    ));
}

$repository = dirname(__DIR__, 2);
$boundaryRun = $repository
    . '/.local/ai-feasibility-spike/results/related-resources/'
    . 'REL-NO-USEFUL-BOUNDARY-DIAGNOSTIC-001/'
    . 'run-20260811-083522Z';
$simulationRun = $repository
    . '/.local/ai-feasibility-spike/results/live-relation-metadata/'
    . 'CONTENT-JUSTIFIED-TAG-SIMULATION-001/'
    . 'run-20260811-134032Z';

$artifactHashes = [
    $boundaryRun . '/accepted-fixture-clusters.csv' =>
        'FD95B9EDF32D9EAA16695128424EA6E1ADE5ED97F9480E4D205DFD97C2D87314',
    $simulationRun . '/fixture-scenario-tags.csv' =>
        'E299922847BAB90D06DF3128235119CA9C57CED517A741F4218F494927D09E00',
    $simulationRun . '/pair-results.csv' =>
        'A389E00D2E7A1C483F7966B364EF2A1139D598A9F4B2ECCAE52221B05A499B40',
    $simulationRun . '/per-resource-results.csv' =>
        'B83CBC9461DB9574D735967B8129BC69A83ACCB1FED5276FFE18693C39FAD9D1',
    $simulationRun . '/scenario-rule-summary.csv' =>
        '306C020CB1018D9FE7E055E71409D5377B504408646200AC38A33ED043D4ABDC',
    $simulationRun . '/simulation-summary.json' =>
        'D4913C692587C29C4672A85BD803C1AD4B00220D4987EB8061CF61DADEF2B3AA',
];

foreach ($artifactHashes as $path => $expectedHash) {
    broaderAssertTrue(is_file($path), 'Accepted evidence artifact exists');
    broaderAssertSame(
        $expectedHash,
        strtoupper((string) hash_file('sha256', $path)),
        'Accepted evidence artifact hash'
    );
}

$clusters = broaderReadCsv($boundaryRun . '/accepted-fixture-clusters.csv');
broaderAssertSame(25, count($clusters), 'Accepted fixture count');

$clusterCounts = [];
$fixtureIds = [];

foreach ($clusters as $row) {
    $cluster = (string) ($row['cluster'] ?? '');
    $fixtureId = (string) ($row['fixture_id'] ?? '');
    $clusterCounts[$cluster] = ($clusterCounts[$cluster] ?? 0) + 1;
    $fixtureIds[] = $fixtureId;
}

ksort($clusterCounts);
sort($fixtureIds);

broaderAssertSame(
    ['A' => 5, 'B' => 5, 'C' => 5, 'D' => 5, 'E' => 5],
    $clusterCounts,
    'Frozen five-group coverage'
);
broaderAssertSame(25, count(array_unique($fixtureIds)), 'Unique fixture IDs');

$scenarioTags = broaderReadCsv($simulationRun . '/fixture-scenario-tags.csv');
$currentTags = array_values(array_filter(
    $scenarioTags,
    static fn (array $row): bool =>
        ($row['scenario_id'] ?? null) === 'SCENARIO-CURRENT'
));

broaderAssertSame(25, count($currentTags), 'Current-scenario fixture rows');

$currentFixtureIds = array_map(
    static fn (array $row): string => (string) $row['fixture_id'],
    $currentTags
);
sort($currentFixtureIds);

broaderAssertSame(
    $fixtureIds,
    $currentFixtureIds,
    'Current-scenario fixture coverage'
);

$fixturesWithEffectiveTag = 0;

foreach ($currentTags as $row) {
    $baselineTag = trim((string) ($row['baseline_tag'] ?? ''));
    $addedTags = trim((string) ($row['content_justified_added_tags'] ?? ''));

    if ($baselineTag !== '' || $addedTags !== '') {
        $fixturesWithEffectiveTag++;
    }
}

broaderAssertSame(19, $fixturesWithEffectiveTag, 'Fixtures with effective tags');
broaderAssertSame(6, 25 - $fixturesWithEffectiveTag, 'Fixtures without effective tags');

$pairRows = broaderFilterRows(
    broaderReadCsv($simulationRun . '/pair-results.csv'),
    'SCENARIO-CURRENT',
    'RULE-SHARED-TAG'
);
broaderAssertSame(600, count($pairRows), 'Ordered non-self pair count');

$classificationCounts = [];
$displayedCount = 0;

foreach ($pairRows as $row) {
    $classification = (string) ($row['classification'] ?? '');
    $classificationCounts[$classification] =
        ($classificationCounts[$classification] ?? 0) + 1;

    if (($row['displayed_by_rule'] ?? null) === 'Yes') {
        $displayedCount++;
    }
}

ksort($classificationCounts);

broaderAssertSame(
    [
        'false_negative' => 28,
        'true_negative' => 500,
        'true_positive' => 72,
    ],
    $classificationCounts,
    'Recomputed broader pair classifications'
);
broaderAssertSame(72, $displayedCount, 'Displayed ordered pairs');

$truePositive = $classificationCounts['true_positive'];
$falsePositive = $classificationCounts['false_positive'] ?? 0;
$falseNegative = $classificationCounts['false_negative'];
$precision = round(
    100 * $truePositive / ($truePositive + $falsePositive),
    2
);
$recall = round(
    100 * $truePositive / ($truePositive + $falseNegative),
    2
);
$f1 = round(2 * $precision * $recall / ($precision + $recall), 2);

broaderAssertSame(100.0, $precision, 'Recomputed pair precision percent');
broaderAssertSame(72.0, $recall, 'Recomputed pair recall percent');
broaderAssertSame(83.72, $f1, 'Recomputed pair F1 percent');

$perResourceRows = broaderFilterRows(
    broaderReadCsv($simulationRun . '/per-resource-results.csv'),
    'SCENARIO-CURRENT',
    'RULE-SHARED-TAG'
);
broaderAssertSame(25, count($perResourceRows), 'Per-resource result count');

$resourcesWithUsefulPeer = count(array_filter(
    $perResourceRows,
    static fn (array $row): bool =>
        (int) ($row['displayed_useful_count'] ?? 0) > 0
));
$falseSafeNoResult = count(array_filter(
    $perResourceRows,
    static fn (array $row): bool =>
        ($row['false_safe_no_result'] ?? null) === 'Yes'
));

broaderAssertSame(19, $resourcesWithUsefulPeer, 'Resources with a useful peer');
broaderAssertSame(6, $falseSafeNoResult, 'False safe-no-result resources');

$summaryRows = broaderFilterRows(
    broaderReadCsv($simulationRun . '/scenario-rule-summary.csv'),
    'SCENARIO-CURRENT',
    'RULE-SHARED-TAG'
);
broaderAssertSame(1, count($summaryRows), 'Persisted summary row count');

$summary = $summaryRows[0];
broaderAssertSame('100', $summary['precision_percent'], 'Persisted precision');
broaderAssertSame('72', $summary['recall_percent'], 'Persisted recall');
broaderAssertSame('83.72', $summary['f1_percent'], 'Persisted F1');
broaderAssertSame('19', $summary['resources_with_useful_peer'], 'Persisted peer coverage');
broaderAssertSame('6', $summary['false_safe_no_result_resources'], 'Persisted false no-result count');

$implementationPath = $repository
    . '/src/ai/DatabaseRelatedResourceMetadata.php';
$implementation = file_get_contents($implementationPath);

broaderAssertTrue(
    is_string($implementation),
    'Current relation implementation is readable'
);
broaderAssertTrue(
    str_contains($implementation, 'private const MAX_SUGGESTIONS = 5;'),
    'Current implementation keeps the five-result bound'
);
broaderAssertTrue(
    str_contains($implementation, 'active_tag.is_active = 1'),
    'Current implementation requires active tags'
);
broaderAssertTrue(
    str_contains($implementation, 'candidate.id <> :excluded_resource_id'),
    'Current implementation excludes self-results'
);
broaderAssertTrue(
    str_contains($implementation, "candidate.status = 'approved'"),
    'Current implementation prefilters Approved resources'
);
broaderAssertTrue(
    str_contains($implementation, "candidate.file_availability = 'available'"),
    'Current implementation prefilters available files'
);
broaderAssertTrue(
    str_contains($implementation, '$this->eligibility->revalidate('),
    'Current implementation performs final eligibility revalidation'
);

$database = Database::connection();
$database->exec('START TRANSACTION READ ONLY');

try {
    $liveRows = $database->query(
        "SELECT
            r.id,
            r.title,
            COUNT(DISTINCT CASE WHEN t.is_active = 1 THEN t.id END)
                AS active_tag_count
         FROM resources r
         LEFT JOIN resource_tags rt ON rt.resource_id = r.id
         LEFT JOIN tags t ON t.id = rt.tag_id
         WHERE r.status = 'approved'
           AND r.file_availability = 'available'
         GROUP BY r.id, r.title
         ORDER BY r.id"
    )->fetchAll(PDO::FETCH_ASSOC);

    $controlledRows = $database->query(
        "SELECT r.id, t.name AS tag_name
         FROM resources r
         INNER JOIN resource_tags rt ON rt.resource_id = r.id
         INNER JOIN tags t ON t.id = rt.tag_id AND t.is_active = 1
         WHERE r.title IN (
            '[Synthetic Demo] Web Application Security Basics Module',
            '[Synthetic Demo] Security and Privacy Terms Quick Reference',
            '[Synthetic Demo] Usability Evaluation Methods Guide',
            '[Synthetic Demo] Heuristic Evaluation Worksheet Guide'
         )
           AND r.status = 'approved'
           AND r.file_availability = 'available'
         ORDER BY r.id, t.name"
    )->fetchAll(PDO::FETCH_ASSOC);

    $database->rollBack();
} catch (Throwable $error) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }

    throw $error;
}

broaderAssertSame(5, count($liveRows), 'Current live Approved-resource count');
broaderAssertSame(4, count($controlledRows), 'Controlled live relation rows');

$controlledTags = array_map(
    static fn (array $row): string => (string) $row['tag_name'],
    $controlledRows
);
sort($controlledTags);

broaderAssertSame(
    ['Security', 'Security', 'Usability', 'Usability'],
    $controlledTags,
    'Controlled live active-tag assignments'
);

echo "GATE 5C BROADER RELATED-RESOURCE RECONCILIATION PASSED: {$checks}/{$checks}\n";
echo "Accepted offline corpus: 25 resources across 5 reviewed relation groups\n";
echo "Current shared-active-tag scenario precision: 100.00%\n";
echo "Current shared-active-tag scenario recall: 72.00%\n";
echo "Resources with at least one useful displayed peer: 19/25\n";
echo "False safe-no-result resources: 6/25\n";
echo "Current live Approved and available resources: 5\n";
echo "Decision status: bounded fallback remains safe but incomplete; no final relation rule selected\n";
echo "No model/provider request, embedding rerun, database write, evidence rewrite, threshold change, registration, commit, or push occurred.\n";
