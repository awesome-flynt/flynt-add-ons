<?php

/**
 * Editable llms.txt endpoint.
 *
 * Registers a translatable Markdown field under Translatable Options > SEO and
 * serves its content at /llms.txt. An empty field disables the endpoint.
 */

namespace Flynt\LlmsTxt;

use Flynt\Utils\Options;

add_action('template_redirect', __NAMESPACE__ . '\serve', 0);

function serve(): void
{
    $requestUri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $requestPath = wp_parse_url($requestUri, PHP_URL_PATH);
    $llmsPath = wp_parse_url(home_url('/llms.txt'), PHP_URL_PATH);

    if ($requestPath !== $llmsPath) {
        return;
    }

    $content = Options::getTranslatable('LlmsTxt', 'content');

    if (!is_string($content) || trim($content) === '') {
        status_header(404);
        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        send_nosniff_header();
        header('X-Robots-Tag: noindex');
        exit;
    }

    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=300, stale-while-revalidate=60');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
    send_nosniff_header();
    header('X-Robots-Tag: noindex');

    echo sanitize_textarea_field($content);
    exit;
}

Options::addTranslatable('LlmsTxt', [
    [
        'label' => __('LLMS.txt Content', 'flynt'),
        'instructions' => __('Enter the complete Markdown served at /llms.txt. The endpoint returns 404 when this field is empty.', 'flynt'),
        'name' => 'content',
        'type' => 'textarea',
        'rows' => 30,
        'new_lines' => '',
    ],
]);
