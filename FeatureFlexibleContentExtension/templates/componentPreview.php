<?php

use Timber\Timber;

use function Flynt\Components\FeatureFlexibleContentExtension\getComponentData;
use function Flynt\Components\FeatureFlexibleContentExtension\getScreenshotData;

$postId = isset($_GET['postId']) ? sanitize_text_field(wp_unslash($_GET['postId'])) : null;
$componentName = isset($_GET['componentName']) ? sanitize_text_field(wp_unslash($_GET['componentName'])) : null;
$flexibleContentFieldName = isset($_GET['flexibleContentFieldName']) ? sanitize_text_field(wp_unslash($_GET['flexibleContentFieldName'])) : null;
$layoutIndex = isset($_GET['layoutIndex']) ? sanitize_text_field(wp_unslash($_GET['layoutIndex'])) : null;

$context = Timber::context(
    [
        'componentName' => $componentName,
        'componentData' => getComponentData($postId, $flexibleContentFieldName, $layoutIndex, $componentName),
        'componentScreenshot' => getScreenshotData($componentName),
    ]
);

return Timber::render('componentPreview.twig', $context);
