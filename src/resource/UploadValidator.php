<?php

declare(strict_types=1);

namespace BpcLearnShare\Resource;

use finfo;
use ZipArchive;

final class UploadValidator
{
    public const MAX_FILE_SIZE_BYTES = 20 * 1024 * 1024;

    private const ALLOWED = [
        'pdf' => [
            'file_type' => 'pdf',
            'mimes' => ['application/pdf', 'application/x-pdf'],
        ],
        'docx' => [
            'file_type' => 'docx',
            'mimes' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/x-zip',
                'application/x-zip-compressed',
                'application/octet-stream',
            ],
        ],
        'pptx' => [
            'file_type' => 'pptx',
            'mimes' => [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
                'application/x-zip',
                'application/x-zip-compressed',
                'application/octet-stream',
            ],
        ],
        'txt' => [
            'file_type' => 'txt',
            'mimes' => ['text/plain', 'application/octet-stream'],
        ],
        'jpg' => [
            'file_type' => 'jpg',
            'mimes' => ['image/jpeg'],
        ],
        'jpeg' => [
            'file_type' => 'jpg',
            'mimes' => ['image/jpeg'],
        ],
        'png' => [
            'file_type' => 'png',
            'mimes' => ['image/png'],
        ],
    ];

    /**
     * @param array<string, mixed>|null $file
     * @return array{
     *     temporary_path: string,
     *     original_filename: string,
     *     extension: string,
     *     file_type: string,
     *     file_size: int,
     *     mime_type: string
     * }
     */
    public function validate(?array $file): array
    {
        if ($file === null || !isset($file['error'])) {
            throw new UploadValidationException(
                'Select a file to upload.',
                'missing_file'
            );
        }

        $error = (int) $file['error'];

        if ($error !== UPLOAD_ERR_OK) {
            throw new UploadValidationException(
                $this->uploadErrorMessage($error),
                $error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE
                    ? 'oversized_file'
                    : 'upload_transport_error'
            );
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        $clientName = (string) ($file['name'] ?? '');
        $originalFilename = basename(str_replace('\\', '/', $clientName));
        $extension = strtolower((string) pathinfo(
            $originalFilename,
            PATHINFO_EXTENSION
        ));

        if ($originalFilename === '' || mb_strlen($originalFilename) > 255) {
            throw new UploadValidationException(
                'The original filename is missing or too long.',
                'unsafe_filename',
                $extension
            );
        }

        if (!array_key_exists($extension, self::ALLOWED)) {
            throw new UploadValidationException(
                'Use a PDF, DOCX, PPTX, TXT, JPG/JPEG, or PNG file.',
                'disallowed_extension',
                $extension
            );
        }

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new UploadValidationException(
                'The uploaded file could not be verified.',
                'unverified_upload',
                $extension
            );
        }

        $size = filesize($temporaryPath);

        if (!is_int($size) || $size < 1) {
            throw new UploadValidationException(
                'Empty files cannot be uploaded.',
                'empty_file',
                $extension
            );
        }

        if ($size > self::MAX_FILE_SIZE_BYTES) {
            throw new UploadValidationException(
                'The file exceeds the 20 MB upload limit.',
                'oversized_file',
                $extension
            );
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);

        if (!is_string($mime)
            || !in_array($mime, self::ALLOWED[$extension]['mimes'], true)
        ) {
            throw new UploadValidationException(
                'The file content does not match its extension.',
                'mime_mismatch',
                $extension
            );
        }

        $this->validateStructure($temporaryPath, $extension);

        return [
            'temporary_path' => $temporaryPath,
            'original_filename' => $originalFilename,
            'extension' => self::ALLOWED[$extension]['file_type'],
            'file_type' => self::ALLOWED[$extension]['file_type'],
            'file_size' => $size,
            'mime_type' => $mime,
        ];
    }

    private function validateStructure(string $path, string $extension): void
    {
        if ($extension === 'pdf') {
            $this->validatePdf($path, $extension);

            return;
        }

        if ($extension === 'docx' || $extension === 'pptx') {
            $this->validateOfficeOpenXml($path, $extension);

            return;
        }

        if ($extension === 'txt') {
            $content = file_get_contents($path);

            if (!is_string($content)
                || str_contains($content, "\0")
                || !mb_check_encoding($content, 'UTF-8')
            ) {
                throw new UploadValidationException(
                    'The text file is unreadable or is not valid UTF-8 text.',
                    'corrupt_file',
                    $extension
                );
            }

            return;
        }

        $expectedImageType = in_array($extension, ['jpg', 'jpeg'], true)
            ? IMAGETYPE_JPEG
            : IMAGETYPE_PNG;
        $imageType = @exif_imagetype($path);

        if ($imageType !== $expectedImageType) {
            throw new UploadValidationException(
                'The image file is unreadable or corrupt.',
                'corrupt_file',
                $extension
            );
        }
    }

    private function validatePdf(string $path, string $extension): void
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new UploadValidationException(
                'The PDF file cannot be read.',
                'corrupt_file',
                $extension
            );
        }

        try {
            $header = fread($handle, 5);
            $size = filesize($path);

            if (!is_int($size) || fseek($handle, max(0, $size - 2048)) !== 0) {
                throw new UploadValidationException(
                    'The PDF file cannot be read safely.',
                    'corrupt_file',
                    $extension
                );
            }

            $tail = stream_get_contents($handle);

            if ($header !== '%PDF-'
                || !is_string($tail)
                || !str_contains($tail, '%%EOF')
            ) {
                throw new UploadValidationException(
                    'The PDF file is incomplete or corrupt.',
                    'corrupt_file',
                    $extension
                );
            }
        } finally {
            fclose($handle);
        }
    }

    private function validateOfficeOpenXml(
        string $path,
        string $extension
    ): void {
        $archive = new ZipArchive();
        $opened = $archive->open(
            $path,
            ZipArchive::RDONLY | ZipArchive::CHECKCONS
        );

        if ($opened !== true) {
            throw new UploadValidationException(
                'The Office document is unreadable or corrupt.',
                'corrupt_file',
                $extension
            );
        }

        try {
            $requiredEntry = $extension === 'docx'
                ? 'word/document.xml'
                : 'ppt/presentation.xml';

            if ($archive->locateName('[Content_Types].xml') === false
                || $archive->locateName($requiredEntry) === false
            ) {
                throw new UploadValidationException(
                    'The file is not a valid ' . strtoupper($extension) . ' document.',
                    'archive_disguised_as_office',
                    $extension
                );
            }
        } finally {
            $archive->close();
        }
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                'The file exceeds the configured upload limit.',
            UPLOAD_ERR_PARTIAL => 'The file upload was incomplete. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Select a file to upload.',
            default => 'The file could not be uploaded. Please try again.',
        };
    }
}