<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use RuntimeException;

final class LocalProcessingException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reason
    ) {
        parent::__construct($message);
    }
}
