<?php

declare(strict_types=1);

use BpcLearnShare\Ai\GuardedSemanticRetrieval;
use BpcLearnShare\Resource\ResourceDiscoveryInput;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

$checks = 0;

/** @param mixed $actual */
function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    global $checks;

    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }

    $checks++;
}

function assertContainsText(string $needle, string $haystack, string $message): void
{
    global $checks;

    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException($message);
    }

    $checks++;
}

function assertNotContainsText(string $needle, string $haystack, string $message): void
{
    global $checks;

    if (str_contains($haystack, $needle)) {
        throw new RuntimeException($message);
    }

    $checks++;
}

$root = dirname(__DIR__, 2);
$route = file_get_contents($root . '/public/index.php');
$view = file_get_contents($root . '/src/Views/resource/index.php');
$environment = file_get_contents($root . '/.env.example');

if ($route === false || $view === false || $environment === false) {
    throw new RuntimeException('Required semantic-search surface source is unreadable.');
}

$route = str_replace("\r\n", "\n", $route);
$view = str_replace("\r\n", "\n", $view);
$environment = str_replace("\r\n", "\n", $environment);
assertSameValue(
    'metadata',
    ResourceDiscoveryInput::searchMode([]),
    'Standard metadata search must remain the default.'
);
assertSameValue(
    'metadata',
    ResourceDiscoveryInput::searchMode(['search_mode' => 'unexpected']),
    'Unexpected search modes must normalize to the safe metadata default.'
);
assertSameValue(
    'semantic',
    ResourceDiscoveryInput::searchMode(['search_mode' => 'semantic']),
    'The explicit semantic choice was not preserved.'
);
assertSameValue(
    [],
    ResourceDiscoveryInput::validate(['search_mode' => 'metadata']),
    'The metadata search choice should validate.'
);
assertSameValue(
    [],
    ResourceDiscoveryInput::validate(['search_mode' => 'semantic']),
    'The semantic search choice should validate.'
);
assertSameValue(
    ['search_mode' => 'Choose a valid search method.'],
    ResourceDiscoveryInput::validate(['search_mode' => 'unsafe']),
    'An unsupported search mode must be rejected.'
);

assertContainsText(
    "Environment::getBool(\n                        'AI_SEMANTIC_RETRIEVAL_ENABLED',\n                        false",
    $route,
    'The user-facing route must keep semantic retrieval default-off.'
);
assertContainsText(
    'new GuardedSemanticRetrieval(',
    $route,
    'The route does not use the accepted guarded retrieval service.'
);
assertContainsText(
    "unset(\$resource['internal_similarity_score']);",
    $route,
    'Internal similarity scores are not removed at the route boundary.'
);
assertContainsText(
    "\$exception->reason === 'semantic_requester_not_authorized'",
    $route,
    'Late requester deauthorization is not handled explicitly.'
);
assertContainsText(
    'Standard metadata results are shown instead.',
    $route,
    'Safe user-facing metadata fallback copy is missing.'
);
assertNotContainsText(
    'Semantic search fell back safely: %s',
    $route,
    'Optional semantic dependency details must not be written to application logs.'
);

assertContainsText(
    'value="metadata"',
    $view,
    'The standard-search choice is missing from the interface.'
);
assertContainsText(
    'value="semantic"',
    $view,
    'The AI-assisted search choice is missing from the interface.'
);
assertContainsText(
    "e((string) \$resource['matched_locator'])",
    $view,
    'The matched locator is not escaped before display.'
);
assertContainsText(
    "e((string) (\$resource['matched_excerpt'] ?? ''))",
    $view,
    'The matched excerpt is not escaped before display.'
);
assertNotContainsText(
    'internal_similarity_score',
    $view,
    'The interface must not expose raw similarity scores.'
);
assertNotContainsText(
    'definite answer',
    strtolower($view),
    'The interface must not claim that similarity proves an answer.'
);

assertContainsText(
    'AI_SEMANTIC_RETRIEVAL_ENABLED=false',
    $environment,
    'The example environment must keep semantic retrieval off.'
);
assertContainsText(
    'OLLAMA_EMBEDDING_TIMEOUT_SECONDS=5',
    $environment,
    'The bounded user-facing embedding timeout is undocumented.'
);

$retrievalReflection = new ReflectionClass(GuardedSemanticRetrieval::class);
$retrieval = $retrievalReflection->newInstanceWithoutConstructor();
$present = $retrievalReflection->getMethod('present');
$longText = str_repeat('alpha ', 53) . 'unfinishedword remainder';
$presented = $present->invoke($retrieval, [
    'resource_id' => 1,
    'title' => 'Boundary test',
    'description' => 'Boundary test',
    'topic' => 'Boundary test',
    'file_type' => 'txt',
    'file_size' => 1,
    'view_count' => 0,
    'download_count' => 0,
    'created_at' => '2026-08-24 00:00:00',
    'uploader_name' => 'Test',
    'course_name' => 'Test',
    'subject_name' => 'Test',
    'year_level_name' => 'Test',
    'resource_type_name' => 'Test',
    'tag_names' => '',
    'locator_label' => 'Lines 1-2',
    'chunk_text' => $longText,
    'internal_similarity_score' => 0.5,
]);
assertSameValue(
    rtrim(str_repeat('alpha ', 53)) . '…',
    $presented['matched_excerpt'],
    'Long excerpts must stop at a complete word and end with an ellipsis.'
);
assertSameValue(
    true,
    mb_strlen((string) $presented['matched_excerpt']) <= 320,
    'The word-safe excerpt must remain bounded.'
);
assertSameValue(
    false,
    str_contains((string) $presented['matched_excerpt'], 'unfinished'),
    'The word-safe excerpt must not expose a cut word fragment.'
);

fwrite(STDOUT, sprintf(
    "D043 SEMANTIC SEARCH SURFACE CHECKS PASSED: %d/%d\n",
    $checks,
    23
));
fwrite(STDOUT, "No real model request, live database write, semantic feature activation, commit, or push occurred.\n");
