<?php

declare(strict_types=1);

namespace BpcLearnShare\Resource;

final class ResourceInput
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public static function validate(array $input): array
    {
        $errors = [];
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $topic = trim((string) ($input['topic'] ?? ''));

        if ($title === '' || mb_strlen($title) > 200) {
            $errors['title'] = 'Title is required and must not exceed 200 characters.';
        }

        if ($description === '' || strlen($description) > 65535) {
            $errors['description'] =
                'Description is required and must fit within 65,535 bytes.';
        }

        if ($topic === '' || mb_strlen($topic) > 150) {
            $errors['topic'] = 'Topic is required and must not exceed 150 characters.';
        }

        foreach (
            [
                'course_id' => 'course/program',
                'subject_id' => 'subject',
                'year_level_id' => 'year level',
                'resource_type_id' => 'resource type',
            ] as $field => $label
        ) {
            if (filter_var($input[$field] ?? null, FILTER_VALIDATE_INT) === false
                || (int) $input[$field] < 1
            ) {
                $errors[$field] = 'Select a valid ' . $label . '.';
            }
        }

        $tagIds = $input['tag_ids'] ?? [];

        if (!is_array($tagIds)) {
            $errors['tag_ids'] = 'Tag selection is invalid.';
        } else {
            foreach ($tagIds as $tagId) {
                if (filter_var($tagId, FILTER_VALIDATE_INT) === false
                    || (int) $tagId < 1
                ) {
                    $errors['tag_ids'] = 'Tag selection is invalid.';
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $tagIds = is_array($input['tag_ids'] ?? null)
            ? array_values(array_unique(array_map(
                static fn (mixed $value): int => (int) $value,
                $input['tag_ids']
            )))
            : [];
        sort($tagIds);

        return [
            'title' => trim((string) ($input['title'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'topic' => trim((string) ($input['topic'] ?? '')),
            'course_id' => (int) ($input['course_id'] ?? 0),
            'subject_id' => (int) ($input['subject_id'] ?? 0),
            'year_level_id' => (int) ($input['year_level_id'] ?? 0),
            'resource_type_id' => (int) ($input['resource_type_id'] ?? 0),
            'tag_ids' => $tagIds,
        ];
    }
}