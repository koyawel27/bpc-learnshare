<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

/**
 * Deterministic block-aware segmentation derived from the accepted
 * SEG-BLOCK-AWARE-CONTEXT-FIT-002 feasibility configuration.
 */
final class BlockAwareContextFitSegmenter
{
    private const TARGET_CHARACTERS = 900;
    private const MAX_CHARACTERS = 1200;
    private const LONG_BLOCK_OVERLAP_CHARACTERS = 120;

    public function configurationId(): string
    {
        return 'SEG-BLOCK-AWARE-CONTEXT-FIT-002';
    }

    /**
     * @param list<array{type: string, locator: string, text: string}> $blocks
     * @return list<array{
     *     text: string,
     *     locator_kind: string,
     *     start_locator: string,
     *     end_locator: string,
     *     locator_label: string
     * }>
     */
    public function segment(array $blocks, string $fileType): array
    {
        $fileType = strtolower(trim($fileType));

        if (
            $blocks === []
            || !array_is_list($blocks)
            || !in_array($fileType, ['pdf', 'docx', 'pptx', 'txt'], true)
        ) {
            throw $this->failure(
                'Extraction blocks or file type are invalid for segmentation.',
                'invalid_segmentation_input'
            );
        }

        $normalized = [];

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                throw $this->failure(
                    'Extraction contains a malformed block.',
                    'invalid_segmentation_input'
                );
            }

            $type = trim((string) ($block['type'] ?? ''));
            $locator = trim((string) ($block['locator'] ?? ''));
            $text = trim((string) ($block['text'] ?? ''));

            if (
                $type === ''
                || $locator === ''
                || mb_strlen($locator) > 255
                || $text === ''
            ) {
                throw $this->failure(
                    'Extraction block text or locator is invalid.',
                    'invalid_segmentation_input'
                );
            }

            $normalized[] = [
                'type' => $type,
                'locator' => $locator,
                'text' => $text,
            ];
        }

        $chunks = in_array($fileType, ['pdf', 'pptx'], true)
            ? $this->segmentBoundaryBlocks($normalized, $fileType)
            : $this->segmentGroupedBlocks($normalized, $fileType);

        if ($chunks === []) {
            throw $this->failure(
                'Segmentation produced no chunks.',
                'empty_segmentation_result'
            );
        }

        foreach ($chunks as $chunk) {
            if (mb_strlen($chunk['text']) > self::MAX_CHARACTERS) {
                throw $this->failure(
                    'Segmentation produced an oversized chunk.',
                    'oversized_segmentation_result'
                );
            }
        }

        return $chunks;
    }

    /**
     * @param list<array{type: string, locator: string, text: string}> $blocks
     * @return list<array{text: string, locator_kind: string, start_locator: string, end_locator: string, locator_label: string}>
     */
    private function segmentBoundaryBlocks(array $blocks, string $fileType): array
    {
        $chunks = [];

        foreach ($blocks as $block) {
            $parts = $this->splitLongText($block['text']);
            $partCount = count($parts);

            foreach ($parts as $offset => $part) {
                $label = $block['locator'];

                if ($partCount > 1) {
                    $label .= ' part ' . ($offset + 1);
                }

                $chunks[] = [
                    'text' => $part,
                    'locator_kind' => $fileType === 'pdf' ? 'page' : 'slide',
                    'start_locator' => $block['locator'],
                    'end_locator' => $block['locator'],
                    'locator_label' => $label,
                ];
            }
        }

        return $chunks;
    }

    /**
     * @param list<array{type: string, locator: string, text: string}> $blocks
     * @return list<array{text: string, locator_kind: string, start_locator: string, end_locator: string, locator_label: string}>
     */
    private function segmentGroupedBlocks(array $blocks, string $fileType): array
    {
        $chunks = [];
        $group = [];

        $flush = function () use (&$chunks, &$group, $fileType): void {
            if ($group === []) {
                return;
            }

            $chunks[] = $this->groupChunk($group, $fileType);
            $group = [];
        };

        foreach ($blocks as $block) {
            if (mb_strlen($block['text']) > self::MAX_CHARACTERS) {
                $flush();
                $parts = $this->splitLongText($block['text']);

                foreach ($parts as $offset => $part) {
                    $chunks[] = [
                        'text' => $part,
                        'locator_kind' => $this->singleLocatorKind(
                            $block['type'],
                            $fileType
                        ),
                        'start_locator' => $block['locator'],
                        'end_locator' => $block['locator'],
                        'locator_label' => $block['locator']
                            . ' part ' . ($offset + 1),
                    ];
                }

                continue;
            }

            if ($block['type'] === 'heading' && $group !== []) {
                $flush();
            }

            $candidate = $group === []
                ? $block['text']
                : implode(
                    "\n\n",
                    array_merge(
                        array_column($group, 'text'),
                        [$block['text']]
                    )
                );

            if (
                $group !== []
                && mb_strlen($candidate) > self::MAX_CHARACTERS
            ) {
                $flush();
            }

            $group[] = $block;
            $currentText = implode("\n\n", array_column($group, 'text'));
            $last = $group[array_key_last($group)];

            if (
                mb_strlen($currentText) >= self::TARGET_CHARACTERS
                && $last['type'] !== 'heading'
            ) {
                $flush();
            }
        }

        $flush();

        return $chunks;
    }

    /**
     * @param list<array{type: string, locator: string, text: string}> $group
     * @return array{text: string, locator_kind: string, start_locator: string, end_locator: string, locator_label: string}
     */
    private function groupChunk(array $group, string $fileType): array
    {
        $first = $group[0];
        $last = $group[array_key_last($group)];
        $types = array_values(array_unique(array_column($group, 'type')));
        $kind = count($types) === 1
            ? $this->singleLocatorKind($types[0], $fileType)
            : 'mixed';
        $label = $first['locator'] === $last['locator']
            ? $first['locator']
            : $first['locator'] . ' to ' . $last['locator'];

        return [
            'text' => implode("\n\n", array_column($group, 'text')),
            'locator_kind' => $kind,
            'start_locator' => $first['locator'],
            'end_locator' => $last['locator'],
            'locator_label' => $label,
        ];
    }

    /** @return list<string> */
    private function splitLongText(string $text): array
    {
        if (mb_strlen($text) <= self::MAX_CHARACTERS) {
            return [trim($text)];
        }

        $parts = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $remaining = $length - $start;

            if ($remaining <= self::MAX_CHARACTERS) {
                $part = trim(mb_substr($text, $start));

                if ($part !== '') {
                    $parts[] = $part;
                }

                break;
            }

            $window = mb_substr($text, $start, self::MAX_CHARACTERS);
            $minimum = (int) floor(self::TARGET_CHARACTERS * 0.60);
            $split = $this->bestBoundary($window, $minimum);

            if ($split <= 0) {
                $split = self::TARGET_CHARACTERS;
            }

            $part = trim(mb_substr($text, $start, $split));

            if ($part === '') {
                throw $this->failure(
                    'Long-block segmentation could not make progress.',
                    'invalid_segmentation_boundary'
                );
            }

            $parts[] = $part;
            $next = $start + $split - self::LONG_BLOCK_OVERLAP_CHARACTERS;

            if ($next <= $start) {
                $next = $start + $split;
            }

            $start = $next;
        }

        return $parts;
    }

    private function bestBoundary(string $window, int $minimum): int
    {
        foreach (["\n\n", "\n", '. ', '; ', ', ', ' '] as $separator) {
            $position = mb_strrpos($window, $separator);

            if (is_int($position) && $position >= $minimum) {
                return $position + mb_strlen($separator);
            }
        }

        return self::TARGET_CHARACTERS;
    }

    private function singleLocatorKind(string $blockType, string $fileType): string
    {
        if ($fileType === 'txt') {
            return 'section';
        }

        return match ($blockType) {
            'heading' => 'heading',
            'paragraph', 'table_row' => 'paragraph',
            default => 'section',
        };
    }

    private function failure(string $message, string $reason): LocalProcessingException
    {
        return new LocalProcessingException($message, $reason);
    }
}
