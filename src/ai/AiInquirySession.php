<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use InvalidArgumentException;
use RuntimeException;

final class AiInquirySession
{
    public const SESSION_KEY = 'ai_inquiry_context';

    /**
     * @param list<array{resource_id: int, source_file_reference: string}> $sourceReferences
     */
    public static function begin(array $sourceReferences): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException(
                'An active PHP session is required for inquiry context.'
            );
        }

        $sourceReferences = self::normalizeReferences($sourceReferences);
        $inquiryId = bin2hex(random_bytes(16));

        $_SESSION[self::SESSION_KEY] = [
            'inquiry_id' => $inquiryId,
            'source_references' => $sourceReferences,
            'created_at' => time(),
            'updated_at' => time(),
        ];

        return $inquiryId;
    }

    /**
     * @return array{
     *     inquiry_id: string,
     *     source_references: list<array{resource_id: int, source_file_reference: string}>,
     *     created_at: int,
     *     updated_at: int
     * }|null
     */
    public static function current(): ?array
    {
        $context = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_array($context)) {
            return null;
        }

        $inquiryId = $context['inquiry_id'] ?? null;
        $sourceReferences = $context['source_references'] ?? null;
        $createdAt = $context['created_at'] ?? null;
        $updatedAt = $context['updated_at'] ?? null;

        if (
            !is_string($inquiryId)
            || preg_match('/\A[a-f0-9]{32}\z/', $inquiryId) !== 1
            || !is_array($sourceReferences)
            || !is_int($createdAt)
            || !is_int($updatedAt)
        ) {
            self::clear();

            return null;
        }

        try {
            $sourceReferences = self::normalizeReferences($sourceReferences);
        } catch (InvalidArgumentException) {
            self::clear();

            return null;
        }

        return [
            'inquiry_id' => $inquiryId,
            'source_references' => $sourceReferences,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    public static function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * @param list<array{resource_id: int, source_file_reference: string}> $sourceReferences
     * @return list<array{resource_id: int, source_file_reference: string}>
     */
    private static function normalizeReferences(array $sourceReferences): array
    {
        if (
            $sourceReferences === []
            || count($sourceReferences) > 10
            || !array_is_list($sourceReferences)
        ) {
            throw new InvalidArgumentException(
                'AI inquiry context requires one to ten source references.'
            );
        }

        $normalized = [];
        $seen = [];

        foreach ($sourceReferences as $reference) {
            $resourceId = is_array($reference)
                ? ($reference['resource_id'] ?? null)
                : null;
            $sourceFileReference = is_array($reference)
                ? ($reference['source_file_reference'] ?? null)
                : null;

            if (
                !is_int($resourceId)
                || $resourceId <= 0
                || isset($seen[$resourceId])
                || !is_string($sourceFileReference)
                || preg_match(
                    '/\A[a-f0-9]{64}\.(pdf|docx|pptx|txt|jpg|png)\z/',
                    $sourceFileReference
                ) !== 1
            ) {
                throw new InvalidArgumentException(
                    'AI inquiry context contains an invalid source reference.'
                );
            }

            $seen[$resourceId] = true;
            $normalized[] = [
                'resource_id' => $resourceId,
                'source_file_reference' => $sourceFileReference,
            ];
        }

        return $normalized;
    }
}
