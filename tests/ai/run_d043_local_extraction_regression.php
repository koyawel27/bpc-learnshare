<?php

declare(strict_types=1);

use BpcLearnShare\Ai\BlockAwareContextFitSegmenter;
use BpcLearnShare\Ai\LocalReadableTextExtractor;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

$root = dirname(__DIR__, 2);
$corpus = $root . '/.local/ai-feasibility-spike/authorized-corpus/primary-readable';
$fixtures = [
    'pdf' => $corpus . '/pdf/FX-PDF-001_Database_Normalization_Study_Guide.pdf',
    'docx' => $corpus . '/docx/FX-DOCX-001_ERD_and_Cardinality_Notes.docx',
    'pptx' => $corpus . '/pptx/FX-PPTX-001_Database_Keys_and_Relationships.pptx',
    'txt' => $corpus . '/txt/FX-TXT-001_SQL_Terminology_Quick_Reference.txt',
];
$extractor = new LocalReadableTextExtractor();
$segmenter = new BlockAwareContextFitSegmenter();
$checks = 0;

fwrite(STDOUT, "=== D043 LOCAL EXTRACTION REGRESSION ===\n");
fwrite(STDOUT, "Fixture scope: one accepted primary-readable file per supported type\n");
fwrite(STDOUT, "Database/provider/model requests: prohibited\n\n");

foreach ($fixtures as $type => $path) {
    if (!is_file($path)) {
        throw new RuntimeException('Accepted local fixture is missing: ' . basename($path));
    }

    $result = $extractor->extract($path, $type);
    $chunks = $segmenter->segment($result['blocks'], $type);

    if (trim($result['full_text']) === '' || $result['blocks'] === [] || $chunks === []) {
        throw new RuntimeException(strtoupper($type) . ' extraction or segmentation is empty.');
    }

    foreach ($chunks as $chunk) {
        if (
            mb_strlen($chunk['text']) > 1200
            || trim($chunk['locator_label']) === ''
            || trim($chunk['start_locator']) === ''
            || trim($chunk['end_locator']) === ''
        ) {
            throw new RuntimeException(strtoupper($type) . ' produced an invalid bounded chunk.');
        }
    }

    $checks++;
    fwrite(STDOUT, sprintf(
        "[PASS] %s: %d blocks, %d chunks, locators preserved, max 1200 characters\n",
        strtoupper($type),
        count($result['blocks']),
        count($chunks)
    ));
}

fwrite(STDOUT, "\nD043 LOCAL EXTRACTION REGRESSION PASSED.\n");
fwrite(STDOUT, "Supported file types passed: {$checks}/4\n");
fwrite(STDOUT, "Database/provider/model requests performed: 0\n");
