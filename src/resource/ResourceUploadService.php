<?php

declare(strict_types=1);

namespace BpcLearnShare\Resource;

use RuntimeException;
use Throwable;

final class ResourceUploadService
{
    public function __construct(
        private readonly ResourceRepository $resources,
        private readonly string $storageDirectory
    ) {
    }

    /**
     * @param array<string, mixed> $account
     * @param array<string, mixed> $resource
     * @param array<string, mixed> $file
     */
    public function createPending(
        array $account,
        array $resource,
        array $file
    ): int {
        if (!in_array(
            (string) ($account['role'] ?? ''),
            ['student', 'teacher_instructor'],
            true
        )) {
            throw new RuntimeException(
                'This account role cannot submit ordinary uploads.'
            );
        }

        if (!is_dir($this->storageDirectory)
            || !is_writable($this->storageDirectory)
        ) {
            throw new RuntimeException(
                'Protected upload storage is unavailable.'
            );
        }

        $storedFilename = bin2hex(random_bytes(32))
            . '.'
            . $file['extension'];
        $destination = $this->storageDirectory
            . DIRECTORY_SEPARATOR
            . $storedFilename;

        if (!move_uploaded_file($file['temporary_path'], $destination)) {
            throw new RuntimeException(
                'The validated file could not be moved into protected storage.'
            );
        }

        try {
            return $this->resources->createPending(
                (int) $account['id'],
                $resource,
                $file,
                $storedFilename
            );
        } catch (Throwable $exception) {
            if (is_file($destination) && !unlink($destination)) {
                error_log(
                    '[BPC LearnShare] Orphan upload cleanup failed after database error.'
                );
            }

            throw $exception;
        }
    }
}