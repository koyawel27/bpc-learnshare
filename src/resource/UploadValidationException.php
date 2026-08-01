<?php

declare(strict_types=1);

namespace BpcLearnShare\Resource;

use RuntimeException;

final class UploadValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $category,
        private readonly string $attemptedExtension = ''
    ) {
        parent::__construct($message);
    }

    public function category(): string
    {
        return $this->category;
    }

    public function attemptedExtension(): string
    {
        return $this->attemptedExtension;
    }
}