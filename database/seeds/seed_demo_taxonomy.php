<?php

declare(strict_types=1);

use BpcLearnShare\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__, 2) . '/src/bootstrap.php';

$values = [
    'courses' => [
        'BS Information Systems',
    ],
    'subjects' => [
        'Database Management Systems',
        'Research Methods',
        'Systems Analysis and Design',
        'Web Systems and Technologies',
    ],
    'year_levels' => [
        '1st Year',
        '2nd Year',
        '3rd Year',
        '4th Year',
    ],
    'resource_types' => [
        'Handout',
        'Module',
        'Notes',
        'Presentation',
        'Reviewer',
        'Study Guide',
    ],
    'tags' => [
        'Database',
        'Programming',
        'Research',
        'Security',
        'Usability',
    ],
];

$database = Database::connection();
$database->beginTransaction();

try {
    foreach ($values as $table => $names) {
        if (!in_array(
            $table,
            ['courses', 'subjects', 'year_levels', 'resource_types', 'tags'],
            true
        )) {
            throw new RuntimeException('Unsupported taxonomy table.');
        }

        $statement = $database->prepare(
            "INSERT IGNORE INTO {$table} (name, is_active)
             VALUES (:name, 1)"
        );

        foreach ($names as $name) {
            $statement->execute(['name' => $name]);
        }
    }

    $database->commit();
} catch (Throwable $exception) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }

    fwrite(STDERR, "Demo taxonomy setup failed.\n");
    exit(1);
}

$counts = [];

foreach (array_keys($values) as $table) {
    $counts[$table] = (int) $database
        ->query("SELECT COUNT(*) FROM {$table} WHERE is_active = 1")
        ->fetchColumn();
}

fwrite(
    STDOUT,
    "Local demonstration taxonomy is ready.\n"
    . "These are seed values for the prototype, not a final official BPC list.\n"
    . json_encode($counts, JSON_UNESCAPED_SLASHES)
    . "\n"
);