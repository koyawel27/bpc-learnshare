<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use PDO;

final class AiFeatureGate implements AiFeatureAvailability
{
    public function __construct(private readonly PDO $database)
    {
    }

    public function isEnabled(): bool
    {
        $statement = $this->database->prepare(
            'SELECT setting_value
             FROM system_settings
             WHERE setting_name = :setting_name
             LIMIT 1'
        );
        $statement->execute(['setting_name' => 'ai_enabled']);
        $value = $statement->fetchColumn();

        return is_string($value) && $value === 'enabled';
    }
}
