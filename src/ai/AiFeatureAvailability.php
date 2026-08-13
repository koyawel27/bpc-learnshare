<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

interface AiFeatureAvailability
{
    public function isEnabled(): bool;
}
