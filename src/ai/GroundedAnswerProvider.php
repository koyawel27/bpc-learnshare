<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

interface GroundedAnswerProvider
{
    public function isReady(): bool;

    /**
     * Each source contains request-scoped evidence_text after live eligibility
     * revalidation. Implementations must not substitute unsupported knowledge.
     *
     * @param list<array<string, mixed>> $eligibleSources
     * @return array{answer: string, source_ids: list<int>}
     */
    public function generate(string $question, array $eligibleSources): array;
}
