<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use finfo;
use Smalot\PdfParser\Parser;
use Throwable;
use ZipArchive;

/**
 * Local readable-text extraction for the accepted PDF/DOCX/PPTX/TXT scope.
 *
 * It performs no database work and sends no content outside the machine.
 */
final class LocalReadableTextExtractor
{
    private const MAX_FILE_SIZE_BYTES = 20 * 1024 * 1024;
    private const MAX_OFFICE_XML_BYTES = 16 * 1024 * 1024;
    private const MAX_PPTX_SLIDES = 500;

    /** @var array<string, list<string>> */
    private const MIME_TYPES = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
            'application/octet-stream',
        ],
        'pptx' => [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
            'application/octet-stream',
        ],
        'txt' => ['text/plain', 'application/octet-stream'],
    ];

    public function configurationId(): string
    {
        return 'EX-LOCAL-PHP-001';
    }

    public function supports(string $fileType): bool
    {
        return array_key_exists(strtolower($fileType), self::MIME_TYPES);
    }

    public function detectMimeType(string $path, string $fileType): string
    {
        $fileType = strtolower(trim($fileType));
        $this->assertReadableFile($path, $fileType);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if (
            !is_string($mime)
            || !in_array($mime, self::MIME_TYPES[$fileType], true)
        ) {
            throw $this->failure(
                'Protected file content does not match its registered type.',
                'extraction_mime_mismatch'
            );
        }

        return $mime;
    }

    /**
     * @return array{
     *     detected_mime_type: string,
     *     full_text: string,
     *     blocks: list<array{type: string, locator: string, text: string}>
     * }
     */
    public function extract(string $path, string $fileType): array
    {
        $fileType = strtolower(trim($fileType));
        $mime = $this->detectMimeType($path, $fileType);

        try {
            $blocks = match ($fileType) {
                'pdf' => $this->extractPdf($path),
                'docx' => $this->extractDocx($path),
                'pptx' => $this->extractPptx($path),
                'txt' => $this->extractTxt($path),
                default => throw $this->failure(
                    'File type is outside the readable local extraction scope.',
                    'unsupported_extraction_type'
                ),
            };
        } catch (LocalProcessingException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw $this->failure(
                'The protected file could not be extracted safely.',
                'local_extraction_failed'
            );
        }

        $blocks = array_values(array_filter(
            $blocks,
            static fn (array $block): bool => trim($block['text']) !== ''
        ));

        if ($blocks === []) {
            throw $this->failure(
                'No meaningful readable text was found in the protected file.',
                'no_meaningful_text'
            );
        }

        $fullText = implode(
            "\n\n",
            array_map(
                static fn (array $block): string => $block['text'],
                $blocks
            )
        );

        if (trim($fullText) === '') {
            throw $this->failure(
                'No meaningful readable text was found in the protected file.',
                'no_meaningful_text'
            );
        }

        return [
            'detected_mime_type' => $mime,
            'full_text' => $fullText,
            'blocks' => $blocks,
        ];
    }

    /** @return list<array{type: string, locator: string, text: string}> */
    private function extractPdf(string $path): array
    {
        if (!class_exists(Parser::class)) {
            throw $this->failure(
                'The reviewed local PDF extraction dependency is unavailable.',
                'pdf_dependency_unavailable'
            );
        }

        $document = (new Parser())->parseFile($path);
        $pages = $document->getPages();
        $blocks = [];

        foreach ($pages as $offset => $page) {
            $text = $this->normalizeText($page->getText());

            if ($text === '') {
                continue;
            }

            $blocks[] = [
                'type' => 'page',
                'locator' => 'Page ' . ($offset + 1),
                'text' => $text,
            ];
        }

        return $blocks;
    }

    /** @return list<array{type: string, locator: string, text: string}> */
    private function extractDocx(string $path): array
    {
        $zip = $this->openZip($path, 'word/document.xml');

        try {
            $xml = $zip->getFromName(
                'word/document.xml',
                self::MAX_OFFICE_XML_BYTES + 1
            );
        } finally {
            $zip->close();
        }

        if (
            !is_string($xml)
            || $xml === ''
            || strlen($xml) > self::MAX_OFFICE_XML_BYTES
        ) {
            throw $this->failure(
                'DOCX document XML is unavailable.',
                'local_extraction_failed'
            );
        }

        $document = $this->loadXml($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace(
            'w',
            'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
        );
        $body = $xpath->query('//w:body')->item(0);

        if (!$body instanceof DOMElement) {
            throw $this->failure(
                'DOCX body is unavailable.',
                'local_extraction_failed'
            );
        }

        $blocks = [];
        $paragraphNumber = 0;
        $tableNumber = 0;

        foreach ($body->childNodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            if ($node->localName === 'p') {
                $paragraphNumber++;
                $text = $this->wordNodeText($xpath, $node);

                if ($text === '') {
                    continue;
                }

                $style = trim((string) $xpath->evaluate(
                    'string(w:pPr/w:pStyle/@w:val)',
                    $node
                ));
                $heading = preg_match('/\AHeading[1-9]\z/i', $style) === 1;
                $blocks[] = [
                    'type' => $heading ? 'heading' : 'paragraph',
                    'locator' => $heading
                        ? $style . ': ' . $this->locatorText($text)
                        : 'Paragraph ' . $paragraphNumber,
                    'text' => $text,
                ];

                continue;
            }

            if ($node->localName !== 'tbl') {
                continue;
            }

            $tableNumber++;
            $rows = $xpath->query('./w:tr', $node);

            foreach ($rows as $rowOffset => $row) {
                if (!$row instanceof DOMElement) {
                    continue;
                }

                $cells = [];

                foreach ($xpath->query('./w:tc', $row) as $cell) {
                    if ($cell instanceof DOMElement) {
                        $cellText = $this->wordNodeText($xpath, $cell);

                        if ($cellText !== '') {
                            $cells[] = $cellText;
                        }
                    }
                }

                $text = $this->normalizeText(implode(' | ', $cells));

                if ($text !== '') {
                    $blocks[] = [
                        'type' => 'table_row',
                        'locator' => sprintf(
                            'Table %d, Row %d',
                            $tableNumber,
                            $rowOffset + 1
                        ),
                        'text' => $text,
                    ];
                }
            }
        }

        return $blocks;
    }

    /** @return list<array{type: string, locator: string, text: string}> */
    private function extractPptx(string $path): array
    {
        $zip = $this->openZip($path, '[Content_Types].xml');
        $slides = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (
                is_string($name)
                && preg_match('/\Appt\/slides\/slide([1-9][0-9]*)\.xml\z/', $name, $match) === 1
            ) {
                $slides[(int) $match[1]] = $name;
            }
        }

        ksort($slides, SORT_NUMERIC);

        if (count($slides) > self::MAX_PPTX_SLIDES) {
            $zip->close();
            throw $this->failure(
                'PPTX slide count is outside the bounded extraction scope.',
                'local_extraction_failed'
            );
        }

        $blocks = [];

        try {
            foreach ($slides as $slideNumber => $name) {
                $xml = $zip->getFromName(
                    $name,
                    self::MAX_OFFICE_XML_BYTES + 1
                );

                if (
                    !is_string($xml)
                    || $xml === ''
                    || strlen($xml) > self::MAX_OFFICE_XML_BYTES
                ) {
                    throw $this->failure(
                        'PPTX slide XML is unavailable.',
                        'local_extraction_failed'
                    );
                }

                $document = $this->loadXml($xml);
                $xpath = new DOMXPath($document);
                $xpath->registerNamespace(
                    'a',
                    'http://schemas.openxmlformats.org/drawingml/2006/main'
                );
                $parts = [];

                foreach ($xpath->query('//a:t') as $textNode) {
                    $part = $this->normalizeInlineText($textNode->textContent);

                    if ($part !== '') {
                        $parts[] = $part;
                    }
                }

                $text = $this->normalizeText(implode("\n", $parts));

                if ($text !== '') {
                    $blocks[] = [
                        'type' => 'slide',
                        'locator' => 'Slide ' . $slideNumber,
                        'text' => $text,
                    ];
                }
            }
        } finally {
            $zip->close();
        }

        return $blocks;
    }

    /** @return list<array{type: string, locator: string, text: string}> */
    private function extractTxt(string $path): array
    {
        $content = file_get_contents($path);

        if (
            !is_string($content)
            || str_contains($content, "\0")
            || !mb_check_encoding($content, 'UTF-8')
        ) {
            throw $this->failure(
                'Text file is not valid readable UTF-8.',
                'local_extraction_failed'
            );
        }

        $content = preg_replace('/\A\xEF\xBB\xBF/', '', $content) ?? $content;
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);
        $blocks = [];
        $start = null;
        $buffer = [];

        $flush = function (int $endingLine) use (&$blocks, &$buffer, &$start): void {
            if ($start === null || $buffer === []) {
                $buffer = [];
                $start = null;

                return;
            }

            $text = $this->normalizeText(implode("\n", $buffer));

            if ($text !== '') {
                $blocks[] = [
                    'type' => 'text_block',
                    'locator' => $start === $endingLine
                        ? 'Line ' . $start
                        : sprintf('Lines %d-%d', $start, $endingLine),
                    'text' => $text,
                ];
            }

            $buffer = [];
            $start = null;
        };

        foreach ($lines as $offset => $line) {
            $lineNumber = $offset + 1;

            if (trim($line) === '') {
                $flush($lineNumber - 1);

                continue;
            }

            if ($start === null) {
                $start = $lineNumber;
            }

            $buffer[] = rtrim($line);
        }

        $flush(count($lines));

        return $blocks;
    }

    private function assertReadableFile(string $path, string $fileType): void
    {
        if (!array_key_exists($fileType, self::MIME_TYPES)) {
            throw $this->failure(
                'File type is outside the readable local extraction scope.',
                'unsupported_extraction_type'
            );
        }

        $size = is_file($path) ? filesize($path) : false;

        if (
            !is_string(realpath($path))
            || !is_readable($path)
            || !is_int($size)
            || $size < 1
            || $size > self::MAX_FILE_SIZE_BYTES
        ) {
            throw $this->failure(
                'Protected file is missing, empty, oversized, or unreadable.',
                'extraction_file_unavailable'
            );
        }
    }

    private function openZip(string $path, string $requiredEntry): ZipArchive
    {
        if (!class_exists(ZipArchive::class)) {
            throw $this->failure(
                'ZIP support is unavailable.',
                'zip_dependency_unavailable'
            );
        }

        $zip = new ZipArchive();

        $opened = $zip->open($path) === true;

        if (!$opened || $zip->locateName($requiredEntry) === false) {
            if ($opened) {
                $zip->close();
            }
            throw $this->failure(
                'Office document structure is invalid or unreadable.',
                'local_extraction_failed'
            );
        }

        return $zip;
    }

    private function loadXml(string $xml): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $loaded = $document->loadXML(
            $xml,
            LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOBLANKS
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            throw $this->failure(
                'Office document XML is invalid.',
                'local_extraction_failed'
            );
        }

        return $document;
    }

    private function wordNodeText(DOMXPath $xpath, DOMNode $node): string
    {
        $parts = [];

        foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $node) as $item) {
            if (!$item instanceof DOMElement) {
                continue;
            }

            if ($item->localName === 'tab') {
                $parts[] = "\t";
            } elseif ($item->localName === 'br') {
                $parts[] = "\n";
            } else {
                $parts[] = $item->textContent;
            }
        }

        return $this->normalizeText(implode('', $parts));
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\t ]+\n/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function normalizeInlineText(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function locatorText(string $text): string
    {
        $text = $this->normalizeInlineText($text);

        return mb_strlen($text) <= 180
            ? $text
            : mb_substr($text, 0, 177) . '...';
    }

    private function failure(string $message, string $reason): LocalProcessingException
    {
        return new LocalProcessingException($message, $reason);
    }
}
