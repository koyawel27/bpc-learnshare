<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use Throwable;

final class GroundedInquiryCoordinator
{
    private const MAX_QUESTION_BYTES = 4000;
    private const MAX_ANSWER_BYTES = 16000;
    private const MAX_EVIDENCE_ITEM_BYTES = 8000;
    private const MAX_TOTAL_EVIDENCE_BYTES = 32000;

    public function __construct(
        private readonly AiFeatureAvailability $featureAvailability,
        private readonly AiSourceEligibility $sourceEligibility,
        private readonly GroundedAnswerProvider $provider,
        private readonly SourceAttributionPresenter $attributionPresenter
    ) {
    }

    /**
     * @param list<array{
     *     resource_id: int,
     *     source_file_reference: string,
     *     evidence_text: string
     * }> $evidenceItems
     * @param array<int, list<string>> $trustedLocatorsByResourceId
     * @return array<string, mixed>
     */
    public function respond(
        int $accountId,
        string $question,
        array $evidenceItems,
        array $trustedLocatorsByResourceId = []
    ): array {
        $question = trim($question);

        if (
            $accountId <= 0
            || $question === ''
            || strlen($question) > self::MAX_QUESTION_BYTES
            || preg_match('//u', $question) !== 1
        ) {
            return AiFallbackResponse::invalidRequest();
        }

        try {
            if (!$this->featureAvailability->isEnabled()) {
                return AiFallbackResponse::disabled();
            }

            if (!$this->provider->isReady()) {
                return AiFallbackResponse::unavailable();
            }

            $evidenceItems = $this->normalizeEvidenceItems($evidenceItems);

            if ($evidenceItems === null) {
                return AiFallbackResponse::evidenceUnavailable();
            }

            $sourceReferences = array_map(
                static fn (array $item): array => [
                    'resource_id' => $item['resource_id'],
                    'source_file_reference' =>
                        $item['source_file_reference'],
                ],
                $evidenceItems
            );

            $initialSources = $this->sourceEligibility->revalidate(
                $accountId,
                $sourceReferences
            );

            if ($initialSources === null) {
                return AiFallbackResponse::evidenceUnavailable();
            }

            $evidenceTextById = [];

            foreach ($evidenceItems as $evidenceItem) {
                $evidenceTextById[$evidenceItem['resource_id']] =
                    $evidenceItem['evidence_text'];
            }

            foreach ($initialSources as &$initialSource) {
                $sourceId = (int) ($initialSource['resource_id'] ?? 0);
                $evidenceText = $evidenceTextById[$sourceId] ?? null;

                if (!is_string($evidenceText)) {
                    return AiFallbackResponse::evidenceUnavailable();
                }

                $initialSource['evidence_text'] = $evidenceText;
            }
            unset($initialSource);

            $providerResult = $this->provider->generate(
                $question,
                $initialSources
            );
            $answer = $providerResult['answer'] ?? null;
            $sourceIds = $providerResult['source_ids'] ?? null;

            if (
                !is_string($answer)
                || trim($answer) === ''
                || strlen($answer) > self::MAX_ANSWER_BYTES
                || preg_match('//u', $answer) !== 1
                || !is_array($sourceIds)
                || $sourceIds === []
                || !array_is_list($sourceIds)
            ) {
                return AiFallbackResponse::unavailable();
            }

            $referencesById = [];

            foreach ($sourceReferences as $reference) {
                if (
                    !is_array($reference)
                    || !isset(
                        $reference['resource_id'],
                        $reference['source_file_reference']
                    )
                    || !is_int($reference['resource_id'])
                    || !is_string($reference['source_file_reference'])
                ) {
                    return AiFallbackResponse::evidenceUnavailable();
                }

                $referencesById[$reference['resource_id']] = $reference;
            }

            $citedReferences = [];
            $seenSourceIds = [];

            foreach ($sourceIds as $sourceId) {
                if (
                    !is_int($sourceId)
                    || $sourceId <= 0
                    || isset($seenSourceIds[$sourceId])
                    || !isset($referencesById[$sourceId])
                ) {
                    return AiFallbackResponse::evidenceUnavailable();
                }

                $seenSourceIds[$sourceId] = true;
                $citedReferences[] = $referencesById[$sourceId];
            }

            $finalSources = $this->sourceEligibility->revalidate(
                $accountId,
                $sourceReferences
            );

            if ($finalSources === null) {
                return AiFallbackResponse::evidenceUnavailable();
            }

            $finalSourcesById = [];

            foreach ($finalSources as $source) {
                $sourceId = (int) ($source['resource_id'] ?? 0);

                if ($sourceId <= 0) {
                    return AiFallbackResponse::evidenceUnavailable();
                }

                $finalSourcesById[$sourceId] = $source;
            }

            $citedSources = [];

            foreach ($citedReferences as $citedReference) {
                $source = $finalSourcesById[
                    $citedReference['resource_id']
                ] ?? null;

                if (!is_array($source)) {
                    return AiFallbackResponse::evidenceUnavailable();
                }

                $citedSources[] = $source;
            }

            $presentedSources = $this->attributionPresenter->present(
                $citedSources,
                $trustedLocatorsByResourceId
            );

            if (count($presentedSources) !== count($citedReferences)) {
                return AiFallbackResponse::evidenceUnavailable();
            }

            return [
                'status' => 'answered',
                'reason_code' => 'validated_answer',
                'message' => '',
                'fallback' => null,
                'answer' => trim($answer),
                'sources' => $presentedSources,
            ];
        } catch (Throwable $exception) {
            error_log(sprintf(
                '[BPC LearnShare] AI inquiry unavailable type=%s',
                $exception::class
            ));

            return AiFallbackResponse::unavailable();
        }
    }

    /**
     * @param list<array{
     *     resource_id: int,
     *     source_file_reference: string,
     *     evidence_text: string
     * }> $evidenceItems
     * @return list<array{
     *     resource_id: int,
     *     source_file_reference: string,
     *     evidence_text: string
     * }>|null
     */
    private function normalizeEvidenceItems(array $evidenceItems): ?array
    {
        if (
            $evidenceItems === []
            || count($evidenceItems) > 10
            || !array_is_list($evidenceItems)
        ) {
            return null;
        }

        $normalized = [];
        $seenResourceIds = [];
        $totalBytes = 0;

        foreach ($evidenceItems as $item) {
            $resourceId = is_array($item)
                ? ($item['resource_id'] ?? null)
                : null;
            $sourceFileReference = is_array($item)
                ? ($item['source_file_reference'] ?? null)
                : null;
            $evidenceText = is_array($item)
                ? ($item['evidence_text'] ?? null)
                : null;

            if (
                !is_int($resourceId)
                || $resourceId <= 0
                || isset($seenResourceIds[$resourceId])
                || !is_string($sourceFileReference)
                || $sourceFileReference === ''
                || !is_string($evidenceText)
            ) {
                return null;
            }

            $evidenceText = trim($evidenceText);
            $evidenceBytes = strlen($evidenceText);
            $totalBytes += $evidenceBytes;

            if (
                $evidenceText === ''
                || $evidenceBytes > self::MAX_EVIDENCE_ITEM_BYTES
                || $totalBytes > self::MAX_TOTAL_EVIDENCE_BYTES
                || preg_match('//u', $evidenceText) !== 1
            ) {
                return null;
            }

            $seenResourceIds[$resourceId] = true;
            $normalized[] = [
                'resource_id' => $resourceId,
                'source_file_reference' => $sourceFileReference,
                'evidence_text' => $evidenceText,
            ];
        }

        return $normalized;
    }
}
