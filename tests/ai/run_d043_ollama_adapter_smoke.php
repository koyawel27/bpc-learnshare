<?php

declare(strict_types=1);

use BpcLearnShare\Ai\OllamaAllMiniLmEmbeddingAdapter;
use BpcLearnShare\Core\Environment;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

$adapter = new OllamaAllMiniLmEmbeddingAdapter(
    Environment::get('OLLAMA_API_BASE', 'http://127.0.0.1:11434'),
    Environment::get('OLLAMA_EXPECTED_VERSION', '0.32.1'),
    Environment::get('OLLAMA_EMBEDDING_MODEL', 'all-minilm:latest'),
    Environment::get('OLLAMA_EMBEDDING_MODEL_DIGEST', '1b226e2802dbb772b5fc32a58f103ca1804ef7501331012de126ab22f67475ef'),
    Environment::getInt('OLLAMA_EMBEDDING_DIMENSION', 384)
);

fwrite(STDOUT, "=== D043 LOCAL OLLAMA ADAPTER SMOKE ===\n");
fwrite(STDOUT, "Input: one synthetic non-corpus sentence\n");
fwrite(STDOUT, "Persistence: prohibited\n\n");

$runtime = $adapter->preflight();
$result = $adapter->embed('Synthetic adapter smoke input about academic resource organization.');
$vector = $result['vector'];
$sumSquares = array_reduce($vector, static fn (float $sum, float $value): float => $sum + ($value * $value), 0.0);
$norm = sqrt($sumSquares);

if (
    $runtime['runtime_version'] !== '0.32.1'
    || $runtime['model_reference'] !== 'all-minilm:latest'
    || $runtime['model_digest'] !== '1b226e2802dbb772b5fc32a58f103ca1804ef7501331012de126ab22f67475ef'
    || count($vector) !== 384
    || $norm < 0.99
    || $norm > 1.01
) {
    throw new RuntimeException('Local Ollama adapter identity or vector guard failed.');
}

fwrite(STDOUT, 'Runtime: ' . $runtime['runtime_version'] . "\n");
fwrite(STDOUT, 'Model: ' . $runtime['model_reference'] . "\n");
fwrite(STDOUT, 'Dimension: ' . count($vector) . "\n");
fwrite(STDOUT, 'Norm: ' . number_format($norm, 12, '.', '') . "\n");
fwrite(STDOUT, "Vector persisted: No\n");
fwrite(STDOUT, "D043 LOCAL OLLAMA ADAPTER SMOKE PASSED.\n");
