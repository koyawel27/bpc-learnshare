<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

interface LocalEmbeddingAdapter
{
    public function configurationId(): string;

    public function dependencyFingerprint(): string;

    /** @return array<string, int|string|null> */
    public function preflight(): array;

    /**
     * @return array{
     *     model_reference: string,
     *     model_digest: string|null,
     *     vector: list<float>
     * }
     */
    public function embed(string $text): array;
}
