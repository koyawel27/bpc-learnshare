<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

interface AiSourceEligibility
{
    /**
     * @param list<array{resource_id: int, source_file_reference: string}> $references
     * @return list<array<string, mixed>>|null
     */
    public function revalidate(int $accountId, array $references): ?array;
}
