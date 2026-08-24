<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

$checks = 0;

function relatedSurfaceContains(
    string $needle,
    string $haystack,
    string $message
): void {
    global $checks;

    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException($message);
    }

    $checks++;
}

function relatedSurfaceNotContains(
    string $needle,
    string $haystack,
    string $message
): void {
    global $checks;

    if (str_contains($haystack, $needle)) {
        throw new RuntimeException($message);
    }

    $checks++;
}

$root = dirname(__DIR__, 2);
$route = file_get_contents($root . '/public/index.php');
$view = file_get_contents($root . '/src/Views/resource/show.php');
$style = file_get_contents($root . '/public/assets/css/app.css');
$environment = file_get_contents($root . '/.env.example');
$relations = file_get_contents(
    $root . '/src/ai/DatabaseRelatedResourceMetadata.php'
);

if (
    $route === false
    || $view === false
    || $style === false
    || $environment === false
    || $relations === false
) {
    throw new RuntimeException(
        'Required related-resource surface source is unreadable.'
    );
}

$route = str_replace("\r\n", "\n", $route);
$view = str_replace("\r\n", "\n", $view);
$style = str_replace("\r\n", "\n", $style);
$environment = str_replace("\r\n", "\n", $environment);
$relations = str_replace("\r\n", "\n", $relations);

$relatedRouteStart = strpos(
    $route,
    "if (Environment::getBool('AI_RELATED_RESOURCES_ENABLED', false))"
);
$relatedRouteEnd = $relatedRouteStart === false
    ? false
    : strpos($route, "\n    \$renderPage('resource/show'", $relatedRouteStart);

if ($relatedRouteStart === false || $relatedRouteEnd === false) {
    throw new RuntimeException('The guarded related-resource route block is missing.');
}

$relatedRoute = substr(
    $route,
    $relatedRouteStart,
    $relatedRouteEnd - $relatedRouteStart
);

relatedSurfaceContains(
    'AI_RELATED_RESOURCES_ENABLED=false',
    $environment,
    'Related resources must remain default-off.'
);
relatedSurfaceContains(
    "Environment::getBool('AI_RELATED_RESOURCES_ENABLED', false)",
    $relatedRoute,
    'The route does not enforce the environment gate.'
);
relatedSurfaceContains(
    '$relatedFeatureGate->isEnabled()',
    $relatedRoute,
    'The route does not enforce the live database AI gate.'
);
relatedSurfaceContains(
    'new DatabaseRelatedResourceMetadata(',
    $relatedRoute,
    'The accepted Gate 5C relation service is not reused.'
);
relatedSurfaceContains(
    'new DatabaseAiSourceEligibility(',
    $relatedRoute,
    'Live requester, resource, and protected-file revalidation is missing.'
);
relatedSurfaceContains(
    'new SourceAttributionPresenter()',
    $relatedRoute,
    'Protected resource-link presentation is missing.'
);
relatedSurfaceContains(
    "(int) \$account['id'],\n                    \$resourceId,\n                    5",
    $relatedRoute,
    'The route does not bind suggestions to the active requester and target.'
);
relatedSurfaceContains(
    "error_log('Related-resource suggestions failed safely.');",
    $relatedRoute,
    'The route lacks a generic safe failure log.'
);
relatedSurfaceNotContains(
    'getMessage()',
    $relatedRoute,
    'The related-resource route must not log dependency details.'
);
relatedSurfaceContains(
    "'relatedResources' => \$relatedResources",
    $route,
    'The guarded outcome is not passed to the detail view.'
);

relatedSurfaceContains(
    'id="related-resources-heading">Related resources</h2>',
    $view,
    'The Related resources heading is missing.'
);
relatedSurfaceContains(
    'Suggestions use shared resource tags and are checked again',
    $view,
    'The relationship explanation is missing.'
);
relatedSurfaceContains(
    "e((string) (\$suggestion['href'] ?? '/resources'))",
    $view,
    'The protected suggestion link is not escaped.'
);
relatedSurfaceContains(
    "e((string) (\$suggestion['title'] ?? 'Approved resource'))",
    $view,
    'The suggestion title is not escaped.'
);
relatedSurfaceContains(
    "e((string) (\$suggestion['file_type'] ?? 'FILE'))",
    $view,
    'The suggestion file type is not escaped.'
);
relatedSurfaceNotContains(
    'reason_code',
    $view,
    'Internal relation reason codes must not reach the interface.'
);
relatedSurfaceNotContains(
    'stored_filename',
    $view,
    'Protected stored filenames must not reach the interface.'
);
relatedSurfaceContains(
    'Search all resources',
    $view,
    'The repository fallback action is missing.'
);

relatedSurfaceContains(
    '.related-resources-panel',
    $style,
    'The related-resource panel styling is missing.'
);
relatedSurfaceContains(
    '.related-resource-link:focus-visible',
    $style,
    'The related-resource links lack visible keyboard focus.'
);
relatedSurfaceContains(
    "@media (max-width: 36rem)",
    $style,
    'The existing narrow-screen breakpoint is missing.'
);
relatedSurfaceContains(
    '.related-resource-link {',
    substr($style, (int) strrpos($style, '@media (max-width: 36rem)')),
    'The related-resource list does not adapt at the narrow breakpoint.'
);

relatedSurfaceContains(
    'private const MAX_SUGGESTIONS = 5;',
    $relations,
    'The accepted five-suggestion bound changed.'
);
relatedSurfaceContains(
    'No useful related resource is currently available.',
    $relations,
    'The safe no-result behavior is missing.'
);

foreach (['Ollama', 'Groq', 'OpenAI', 'EmbeddingAdapter'] as $modelTerm) {
    relatedSurfaceNotContains(
        $modelTerm,
        $relations,
        'The metadata fallback must not depend on a model provider.'
    );
}

fwrite(STDOUT, sprintf(
    "GATE 5C RELATED-RESOURCE SURFACE CHECKS PASSED: %d/%d\n",
    $checks,
    28
));
fwrite(
    STDOUT,
    "No model request, embedding rerun, live database write, feature activation, schema/register change, commit, or push occurred.\n"
);
