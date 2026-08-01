<?php

declare(strict_types=1);

namespace BpcLearnShare\Moderation;

final class ModerationInput
{
    private const ALLOWED_ACTIONS = [
        'approve',
        'reject',
        'request_correction',
    ];

    /**
     * @param array<string, mixed> $input
     * @return array{action: string, note: string}
     */
    public static function normalize(array $input): array
    {
        return [
            'action' => trim((string) ($input['action'] ?? '')),
            'note' => trim((string) ($input['note'] ?? '')),
        ];
    }

    /**
     * @param array{action: string, note: string} $input
     * @return array<string, string>
     */
    public static function validate(array $input): array
    {
        $errors = [];

        if (!in_array($input['action'], self::ALLOWED_ACTIONS, true)) {
            $errors['action'] =
                'Choose Approve, Reject, or Request Correction.';
        }

        if (
            in_array(
                $input['action'],
                ['reject', 'request_correction'],
                true
            )
            && $input['note'] === ''
        ) {
            $errors['note'] =
                'Explain what the uploader needs to know for this decision.';
        }

        if (strlen($input['note']) > 65535) {
            $errors['note'] = 'The moderator note is too long.';
        }

        return $errors;
    }
}
