<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

final class SourceAttributionPresenter
{
    /**
     * @param list<array<string, mixed>> $sources
     * @param array<int, list<string>> $trustedLocatorsByResourceId
     * @return list<array<string, mixed>>
     */
    public function present(
        array $sources,
        array $trustedLocatorsByResourceId = []
    ): array {
        $presented = [];

        foreach ($sources as $source) {
            $resourceId = (int) ($source['resource_id'] ?? 0);
            $title = $source['title'] ?? null;
            $fileType = $source['file_type'] ?? null;

            if (
                $resourceId <= 0
                || !is_string($title)
                || trim($title) === ''
                || !is_string($fileType)
                || $fileType === ''
            ) {
                return [];
            }

            $locators = [];
            $trustedLocators =
                $trustedLocatorsByResourceId[$resourceId] ?? [];

            if (is_array($trustedLocators)) {
                foreach ($trustedLocators as $locator) {
                    if (!is_string($locator)) {
                        continue;
                    }

                    $locator = trim($locator);

                    if (
                        $locator !== ''
                        && strlen($locator) <= 255
                        && !in_array($locator, $locators, true)
                    ) {
                        $locators[] = $locator;
                    }
                }
            }

            $presented[] = [
                'resource_id' => $resourceId,
                'title' => $title,
                'file_type' => strtoupper($fileType),
                'href' => '/resources/' . $resourceId,
                'locators' => $locators,
            ];
        }

        return $presented;
    }
}
