<?php

declare(strict_types=1);

namespace BpcLearnShare\Resource;

final class ResourceDiscoveryInput
{
    private const FILTER_FIELDS = [
        'course_id',
        'subject_id',
        'year_level_id',
        'resource_type_id',
        'tag_id',
    ];

    /**
     * @param array<string, mixed> $input
     * @return array{
     *     q: string,
     *     course_id: int,
     *     subject_id: int,
     *     year_level_id: int,
     *     resource_type_id: int,
     *     tag_id: int
     * }
     */
    public static function normalize(array $input): array
    {
        $query = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) ($input['q'] ?? ''))
        );

        $filters = [
            'q' => is_string($query) ? $query : '',
        ];

        foreach (self::FILTER_FIELDS as $field) {
            $filters[$field] = (int) ($input[$field] ?? 0);
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public static function validate(array $input): array
    {
        $errors = [];
        $query = trim((string) ($input['q'] ?? ''));

        if (mb_strlen($query) > 100) {
            $errors['q'] = 'Search text must not exceed 100 characters.';
        }

        foreach (self::FILTER_FIELDS as $field) {
            $value = trim((string) ($input[$field] ?? ''));

            if ($value !== '' && preg_match('/\A[1-9][0-9]*\z/', $value) !== 1) {
                $errors[$field] = 'Choose a valid filter option.';
            }
        }

        return $errors;
    }

    /**
     * @param array<string, int|string> $filters
     * @param array<string, list<array{id: int, name: string}>> $options
     * @return array<string, string>
     */
    public static function activeFilterErrors(
        array $filters,
        array $options
    ): array {
        $errors = [];
        $optionKeys = [
            'course_id' => 'courses',
            'subject_id' => 'subjects',
            'year_level_id' => 'year_levels',
            'resource_type_id' => 'resource_types',
            'tag_id' => 'tags',
        ];

        foreach ($optionKeys as $field => $optionKey) {
            $selectedId = (int) $filters[$field];

            if ($selectedId === 0) {
                continue;
            }

            $activeIds = array_column($options[$optionKey] ?? [], 'id');

            if (!in_array($selectedId, $activeIds, true)) {
                $errors[$field] = 'The selected filter is unavailable.';
            }
        }

        return $errors;
    }
}
