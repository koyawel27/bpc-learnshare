<?php

declare(strict_types=1);

namespace BpcLearnShare\Moderation;

use RuntimeException;

final class ModerationDecisionException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $category,
        private readonly int $httpStatus = 422
    ) {
        parent::__construct($message);
    }

    public function category(): string
    {
        return $this->category;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
