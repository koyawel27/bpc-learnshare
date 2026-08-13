<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

final class AiFallbackResponse
{
    /** @return array<string, mixed> */
    public static function disabled(): array
    {
        return self::response(
            'ai_disabled',
            'AI inquiry is currently disabled.'
        );
    }

    /** @return array<string, mixed> */
    public static function unavailable(): array
    {
        return self::response(
            'ai_unavailable',
            'AI inquiry is temporarily unavailable.'
        );
    }

    /** @return array<string, mixed> */
    public static function evidenceUnavailable(): array
    {
        return self::response(
            'evidence_unavailable',
            'The supporting repository evidence could not be verified.'
        );
    }

    /** @return array<string, mixed> */
    public static function invalidRequest(): array
    {
        return self::response(
            'invalid_request',
            'Enter a clear repository question before trying again.'
        );
    }

    /** @return array<string, mixed> */
    private static function response(
        string $reasonCode,
        string $message
    ): array {
        return [
            'status' => 'unavailable',
            'reason_code' => $reasonCode,
            'message' => $message
                . ' You can still search and open Approved resources.',
            'fallback' => [
                'label' => 'Search approved resources',
                'href' => '/resources',
            ],
            'answer' => null,
            'sources' => [],
        ];
    }
}
