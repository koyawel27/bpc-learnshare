<?php

declare(strict_types=1);

use BpcLearnShare\Ai\AiFeatureGate;
use BpcLearnShare\Ai\AiPersistenceException;
use BpcLearnShare\Ai\AiPersistenceRepository;
use BpcLearnShare\Ai\BlockAwareContextFitSegmenter;
use BpcLearnShare\Ai\GuardedAiPersistenceProcessor;
use BpcLearnShare\Ai\GuardedLocalResourceProcessor;
use BpcLearnShare\Ai\LocalProcessingException;
use BpcLearnShare\Ai\LocalReadableTextExtractor;
use BpcLearnShare\Ai\OllamaAllMiniLmEmbeddingAdapter;
use BpcLearnShare\Core\Database;
use BpcLearnShare\Core\Environment;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__, 2) . '/src/bootstrap.php';

/** @return never */
function localProcessingUsage(string $message = ''): void
{
    if ($message !== '') {
        fwrite(STDERR, $message . PHP_EOL . PHP_EOL);
    }

    fwrite(STDERR, implode(PHP_EOL, [
        'Usage:',
        '  php scripts/ai/process_resource.php --mode=validate --resource-id=ID --actor-id=ID',
        '  php scripts/ai/process_resource.php --mode=apply --resource-id=ID --actor-id=ID --confirm=TOKEN',
        '',
        'Apply confirmation token:',
        '  PROCESS-RESOURCE-{resource-id}-AS-{actor-id}',
        '',
    ]));
    exit(2);
}

$options = getopt('', [
    'mode:',
    'resource-id:',
    'actor-id:',
    'confirm::',
]);

if (!is_array($options)) {
    localProcessingUsage('Unable to parse CLI options.');
}

$mode = strtolower(trim((string) ($options['mode'] ?? '')));
$resourceId = filter_var(
    $options['resource-id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$actorId = filter_var(
    $options['actor-id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (
    !in_array($mode, ['validate', 'apply'], true)
    || !is_int($resourceId)
    || !is_int($actorId)
) {
    localProcessingUsage('Mode, resource ID, or actor ID is invalid.');
}

$expectedConfirmation = sprintf(
    'PROCESS-RESOURCE-%d-AS-%d',
    $resourceId,
    $actorId
);

if (
    $mode === 'apply'
    && !hash_equals(
        $expectedConfirmation,
        (string) ($options['confirm'] ?? '')
    )
) {
    localProcessingUsage(
        'Apply mode requires the exact resource-and-actor confirmation token.'
    );
}

try {
    $database = Database::connection();
    $featureGate = new AiFeatureGate($database);
    $repository = new AiPersistenceRepository($database);
    $persistence = new GuardedAiPersistenceProcessor(
        $database,
        $repository,
        $featureGate,
        dirname(__DIR__, 2) . '/storage/uploads/resources'
    );
    $embedding = new OllamaAllMiniLmEmbeddingAdapter(
        Environment::get('OLLAMA_API_BASE', 'http://127.0.0.1:11434'),
        Environment::get('OLLAMA_EXPECTED_VERSION', '0.32.1'),
        Environment::get('OLLAMA_EMBEDDING_MODEL', 'all-minilm:latest'),
        Environment::get(
            'OLLAMA_EMBEDDING_MODEL_DIGEST',
            '1b226e2802dbb772b5fc32a58f103ca1804ef7501331012de126ab22f67475ef'
        ),
        Environment::getInt('OLLAMA_EMBEDDING_DIMENSION', 384)
    );
    $processor = new GuardedLocalResourceProcessor(
        $repository,
        $persistence,
        $featureGate,
        new LocalReadableTextExtractor(),
        new BlockAwareContextFitSegmenter(),
        $embedding,
        dirname(__DIR__, 2) . '/storage/uploads/resources',
        Environment::getBool('AI_LOCAL_PROCESSING_ENABLED', false)
    );

    fwrite(STDOUT, "=== GUARDED LOCAL RESOURCE PROCESSOR ===\n");
    fwrite(STDOUT, "Mode: {$mode}\n");
    fwrite(STDOUT, "Resource ID: {$resourceId}\n");
    fwrite(STDOUT, "Actor ID: {$actorId}\n");
    fwrite(STDOUT, "Public AI route: none\n");
    fwrite(STDOUT, "Generation/inquiry: prohibited\n\n");

    $validated = $processor->validate($resourceId, $actorId);
    fwrite(STDOUT, "Prerequisites: passed\n");
    fwrite(STDOUT, 'File type: ' . $validated['file_type'] . PHP_EOL);
    fwrite(STDOUT, 'Extraction: ' . $validated['extraction_configuration_id'] . PHP_EOL);
    fwrite(STDOUT, 'Segmentation: ' . $validated['segmentation_configuration_id'] . PHP_EOL);
    fwrite(STDOUT, 'Embedding: ' . $validated['embedding_configuration_id'] . PHP_EOL);
    fwrite(STDOUT, 'Ollama: ' . $validated['runtime_version'] . PHP_EOL);
    fwrite(STDOUT, 'Model: ' . $validated['model_reference'] . PHP_EOL);
    fwrite(STDOUT, 'Digest: ' . $validated['model_digest'] . PHP_EOL);

    if ($mode === 'validate') {
        fwrite(STDOUT, "\nLOCAL PROCESSING VALIDATION PASSED.\n");
        fwrite(STDOUT, "No source text was extracted, embedded, or persisted.\n");
        fwrite(STDOUT, "Next permitted action: separately run apply with the exact confirmation token.\n");
        exit(0);
    }

    $result = $processor->process($resourceId, $actorId);
    fwrite(STDOUT, "\nLOCAL RESOURCE PROCESSING COMPLETED.\n");
    fwrite(STDOUT, 'Source version ID: ' . $result['source_version_id'] . PHP_EOL);
    fwrite(STDOUT, 'Chunks: ' . $result['chunk_count'] . PHP_EOL);
    fwrite(STDOUT, 'Embeddings: ' . $result['embedding_count'] . PHP_EOL);
    fwrite(STDOUT, "No query vector, transcript, generated answer, or public AI route was created.\n");
} catch (LocalProcessingException|AiPersistenceException $exception) {
    fwrite(STDERR, sprintf(
        "LOCAL PROCESSING STOPPED SAFELY. Reason: %s\n",
        $exception->reason
    ));
    exit(2);
} catch (Throwable $exception) {
    error_log(sprintf(
        '[BPC LearnShare] Local processor failed: %s',
        $exception::class
    ));
    fwrite(STDERR, "LOCAL PROCESSING STOPPED SAFELY. Reason: internal_failure\n");
    exit(2);
}
