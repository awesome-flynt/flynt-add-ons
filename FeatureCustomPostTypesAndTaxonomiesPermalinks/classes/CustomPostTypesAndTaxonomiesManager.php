<?php

/**
 * A class to handle custom archive pages and permalinks for post types and taxonomies.
 *
 * Adds an options page to the admin area where the archive page and slug can be set.
 * For defined post types, the archive page and slug can be set at the option page.
 * For defined taxonomies, the slug can be set at the option page.
 */

namespace Flynt\Components\FeatureCustomPostTypesAndTaxonomiesPermalinks;

use Flynt\Utils\Options;
use Timber\Timber;
use WP_Admin_Bar;
use WP_Post;

class CustomPostTypesAndTaxonomiesManager
{
    private const POST_TYPE_OPTIONS_NAME = 'CustomPostTypesPermalinks';
    private const TAXONOMY_OPTIONS_NAME = 'CustomTaxonomiesPermalinks';
    private const OPTIONS_CATEGORY = 'Default';

    private $postTypes = [];
    private $taxonomies = [];

    private static $instance = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter('register_post_type_args', [$this, 'addPostType'], 10, 2);
        add_filter('register_taxonomy_args', [$this, 'addTaxonomy'], 10, 2);
        add_action('init', [$this, 'registerModifiedPostTypesAndTaxonomies'], 10, 2);

        add_action('template_redirect', [$this, 'redirectSlugToArchivePage']);
        add_filter('rewrite_rules_array', [$this, 'modifyTaxonomyRewriteRules']);

        add_filter('timber/context', [$this, 'addArchivePageToContext']);
        add_filter('wp_title', [$this, 'updateTitle']);
        add_filter('get_the_archive_title', [$this, 'updateTitle']);

        add_filter('wp_nav_menu_objects', [$this, 'updateNaveMenuObjects'], 10, 1);

        add_action('admin_bar_menu', [$this, 'addAdminBarEditLink'], 80);
        add_action('display_post_states', [$this, 'addPostStateLabel'], 10, 2);

        // YOAST SEO.
        add_action('add_meta_boxes', [$this, 'yoastRemoveMetaBoxOnCustomArchivePage'], 10, 2);
        add_action('admin_notices', [$this, 'yoastAddAdminNoticeOnCustomArchivePage']);
    }

    /**
     * Initialize the class.
     *
     * @return void
     */
    public static function init()
    {
        self::getInstance();
    }

    /**
     * Get the instance of this class.
     *
     * @return CustomPostTypesAndTaxonomiesManager
     */
    public static function getInstance(): CustomPostTypesAndTaxonomiesManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register options page.
     *
     * @return void
     */
    private function registerOptions(): void
    {
        // Only show options page if user can manage_options, but keep options for further use.
        if (!current_user_can('manage_options') && is_admin()) {
            return;
        }

        $message = [
            'label' => '',
            'name' => 'message',
            'type' => 'message',
            'message' => sprintf(
                __('Re-save permalinks once after made changes here! %s%s%s', 'flynt'),
                '<a href="' . admin_url('options-permalink.php') . '" target="_blank" rel="noopener noreferrer">',
                __('Permalink Settings'),
                '</a>'
            ),
            'new_lines' => 'wpautop',
            'esc_html' => 0
        ];

        if ($this->postTypes) {
            $optionFields = array_values(array_map(function ($postTypeKey, $postType) {
                return [
                    'label' => $postType['labels']['singular_name'] ?? $postType['label'] ?? $postTypeKey,
                    'name' => $postTypeKey,
                    'type' => 'group',
                    'sub_fields' => [
                        [
                            'label' => $postType['labels']['archives'] ?? __('Archive page', 'flynt'),
                            'instructions' => sprintf(
                                __('Select a page to use it as “%s” page.', 'flynt'),
                                $postType['labels']['archives'] ?? $postTypeKey,
                            ),
                            'name' => 'pageForPostTypeArchive',
                            'type' => 'post_object',
                            'post_type' => [
                                0 => 'page'
                            ],
                            'allow_null' => 1,
                            'multiple' => 0,
                            'return_format' => 'object',
                            'ui' => 1,
                            'wrapper' => [
                                'width' => '50%',
                            ]
                        ],
                        [
                            'label' => sprintf(
                                __('%s Slug', 'flynt'),
                                $postType['labels']['singular_name'] ?? $postType['label'] ?? $postTypeKey,
                            ),
                            'instructions' => sprintf(
                                __('Permalink for ”%s“ pages.', 'flynt'),
                                $postType['labels']['singular_name'] ?? $postType['label'] ?? $postTypeKey,
                            ),
                            'name' => 'slug',
                            'type' => 'text',
                            'placeholder' => $postType['rewrite']['slug'] ?? $postTypeKey,
                            'wrapper' => [
                                'width' => '50%',
                            ]
                        ],
                    ]
                ];
            }, array_keys($this->postTypes), $this->postTypes));

            Options::addGlobal(self::POST_TYPE_OPTIONS_NAME, [
                $message,
                ...$optionFields,
            ], self::OPTIONS_CATEGORY);
        }

        if ($this->taxonomies) {
            $optionFields = array_values(array_map(function ($taxonomyKey, $taxonomy) {
                return [
                    'label' => $taxonomy['labels']['singular_name'] ??  $taxonomy['labels']['name'] ?? $taxonomyKey,
                    'name' => $taxonomyKey,
                    'type' => 'group',
                    'sub_fields' => [
                        [
                            'label' => sprintf(
                                __('%s Slug', 'flynt'),
                                $taxonomy['labels']['singular_name'] ??  $taxonomy['labels']['name'] ?? $taxonomyKey,
                            ),
                            'instructions' => sprintf(
                                __('Permalink for ”%s“ taxonomy pages.', 'flynt'),
                                $taxonomy['labels']['singular_name'] ??  $taxonomy['labels']['name'] ?? $taxonomyKey,
                            ),
                            'name' => 'slug',
                            'type' => 'text',
                            'placeholder' => $taxonomy['rewrite']['slug'] ?? $taxonomyKey,
                            'wrapper' => [
                                'width' => '50%',
                            ],
                        ],
                    ],
                ];
            }, array_keys($this->taxonomies), $this->taxonomies));

            Options::addGlobal(self::TAXONOMY_OPTIONS_NAME, [
                $message,
                ...$optionFields,
            ], self::OPTIONS_CATEGORY);
        }
    }

    /**
     * Add post type to class property and register options page.
     *
     * @param array $args
     * @param string $postType
     * @return array
     */
    public function addPostType(array $args, string $postType): array
    {
        $isBuiltIn = $args['_builtin'] ?? null;
        $isPublic = $args['public'] ?? null;
        $hasArchive = $args['has_archive'] ?? null;
        if (!$isBuiltIn && $isPublic && $hasArchive) {
            $this->postTypes[$postType] = $args;
            $this->registerOptions();
        }
        return $args;
    }

    /**
     * Add registered taxonomy args to class property.
     *
     * @param array $args
     * @param string $taxonomy
     * @return array
     */
    public function addTaxonomy(array $args, string $taxonomy): array
    {
        $isBuiltIn = $args['_builtin'] ?? null;
        $isPublic = $args['public'] ?? null;
        if (!$isBuiltIn && $isPublic) {
            $this->taxonomies[$taxonomy] = $args;
            $this->registerOptions();
        }

        return $args;
    }

    /**
     * Modify post types and taxonomies.
     *
     * @return void
     */
    public static function registerModifiedPostTypesAndTaxonomies(): void
    {
        self::getInstance()->registerModifiedPostTypes();
        self::getInstance()->registerModifiedTaxonomies();
    }

    /**
     * Modify post type args, register post type and add rewrite rule.
     *
     * @return void
     */
    private function registerModifiedPostTypes(): void
    {
        foreach ($this->postTypes as $postType => &$args) {
            $originalArgs = $args;
            $translatableOptions = Options::getGlobal(self::POST_TYPE_OPTIONS_NAME)[$postType] ?? null;

            $isArchivePage = isset($translatableOptions['pageForPostTypeArchive']) && !empty($translatableOptions['pageForPostTypeArchive']);
            $isSlug = isset($translatableOptions['slug']) && !empty($translatableOptions['slug']);

            if ($isArchivePage) {
                $args['has_archive'] = $translatableOptions['pageForPostTypeArchive']->post_name;
                $args['rewrite']['slug'] = $translatableOptions['pageForPostTypeArchive']->post_name;
            }

            if ($isSlug) {
                $args['rewrite']['slug'] = $translatableOptions['slug'];
            }

            if ($isArchivePage && $isSlug) {
                add_rewrite_rule(
                    $translatableOptions['slug'] . '/?$',
                    'index.php?pagename=' . $translatableOptions['slug'],
                    'top'
                );
            }

            if (serialize($originalArgs) !== serialize($args)) {
                register_post_type($postType, $args);
            }
        }
    }

    /**
     * Modify taxonomy args, re-register taxonomy.
     *
     * @return void
     */
    private function registerModifiedTaxonomies(): void
    {

        foreach ($this->taxonomies as $taxonomy => $args) {
            $originalArgs = $args;
            $translatableOptions = Options::getGlobal(self::TAXONOMY_OPTIONS_NAME)[$taxonomy] ?? null;
            $isSlug = isset($translatableOptions['slug']);

            if ($isSlug) {
                $args['rewrite']['slug'] = $translatableOptions['slug'];
            }

            if (serialize($originalArgs) !== serialize($args)) {
                $objectType = get_taxonomy($taxonomy)->object_type;
                register_taxonomy($taxonomy, $objectType, $args);
            }
        }
    }

    /**
     * Redirect slug to archive page.
     *
     * @return void
     */
    public function redirectSlugToArchivePage(): void
    {
        global $wp_query;
        $postTypes = array_keys($this->postTypes);

        foreach ($postTypes as $postType) {
            $postTypeOptions = Options::getGlobal(self::POST_TYPE_OPTIONS_NAME)[$postType] ?? null;
            $pageForPostTypeArchive = $postTypeOptions['pageForPostTypeArchive'] ?? null;
            $slug = $postTypeOptions['slug'] ?? null;
            if (
                isset($wp_query->query['pagename'])
                && $wp_query->query['pagename'] === $slug
                && $pageForPostTypeArchive
            ) {
                wp_redirect(get_post_type_archive_link($postType), 301);
                exit();
            }
        }
    }

    /**
     * Modify taxonomy rewrite rules.
     *
     * @param array $rules
     * @return array
     */
    public function modifyTaxonomyRewriteRules(array $rules): array
    {
        $translatableOptions = Options::getGlobal(self::TAXONOMY_OPTIONS_NAME);

        foreach (array_keys($this->taxonomies) as $taxonomy) {
            $translatableOptionsForTaxonomy = $translatableOptions[$taxonomy] ?? null;

            if (isset($translatableOptionsForTaxonomy['slug'])) {
                $slug = $translatableOptionsForTaxonomy['slug'];
                $filteredRules = array_filter($rules, fn ($key) => strpos($key, $slug) === 0, ARRAY_FILTER_USE_KEY);
                $nonMatchingRules = array_diff_key($rules, $filteredRules);
                $rules = $filteredRules + $nonMatchingRules;
            }
        }

        return $rules;
    }

    /**
     * Add archive page to timber context.
     *
     * @param array $context
     * @return array
     */
    public static function addArchivePageToContext($context): array
    {
        $queriedObject = get_queried_object();
        $postType = $queriedObject->name ?? null;
        $pageForPostTypeArchive = Options::getGlobal(self::POST_TYPE_OPTIONS_NAME)[$postType]['pageForPostTypeArchive'] ?? null;

        if (isset($pageForPostTypeArchive->id)) {
            $context['post'] = Timber::get_post($pageForPostTypeArchive->ID);
        }
        return $context;
    }

    /**
     * Add edit link to admin bar.
     *
     * @param WP_Admin_Bar $wpAdminBar
     * @return void
     */
    public static function addAdminBarEditLink(WP_Admin_Bar $wpAdminBar): void
    {
        if ((is_admin() || !is_admin_bar_showing()) && !is_archive() && !is_404()) {
            return;
        }

        $queriedObject = get_queried_object();
        $postType = $queriedObject->name ?? null;
        $pageForPostTypeArchive = Options::getGlobal(self::POST_TYPE_OPTIONS_NAME)[$postType]['pageForPostTypeArchive'] ?? null;

        if (isset($pageForPostTypeArchive->id)) {
            $editPostLink = get_edit_post_link($pageForPostTypeArchive->id);
            $wpAdminBar->add_menu(
                [
                    'id' => 'edit',
                    'title' => __('Edit Page'),
                    'href' => $editPostLink,
                    'parent' => false,
                ]
            );
        }
    }

    /**
     * Add post state label.
     *
     * @param array $postStates
     * @param WP_Post $post
     * @return array
     */
    public function addPostStateLabel(array $postStates, WP_Post $post): array
    {
        $postTypes = array_keys($this->postTypes);
        foreach ($postTypes as $postType) {
            $translatableOptions = Options::getGlobal(self::POST_TYPE_OPTIONS_NAME)[$postType];

            if (
                isset($translatableOptions['pageForPostTypeArchive']->ID)
                && $post->ID == $translatableOptions['pageForPostTypeArchive']->ID
            ) {
                $postStates[] = $this->postTypes[$postType]['labels']['archives'] ?? __('Archive page', 'flynt');
            }
        }
        return $postStates;
    }

    /**
     * Update title.
     *
     * @param string $title
     * @return string
     */
    public static function updateTitle(string $title): string
    {
        if (is_search()) {
            return $title;
        }

        if (is_archive() && !is_tax()) {
            $title = self::updateCustomArchiveTitle($title);
        }

        if (is_tax()) {
            $title = self::updateTaxonomyTitle($title);
        }

        return $title;
    }

    /**
     * Update title for custom archive pages.
     *
     * @param string $title
     * @return string
     */
    private static function updateCustomArchiveTitle(string $title): string
    {
        $queriedObject = get_queried_object();
        $postType = $queriedObject->name ?? null;
        $pageForPostTypeArchive = Options::getGlobal(self::POST_TYPE_OPTIONS_NAME)[$postType]['pageForPostTypeArchive'] ?? null;

        if (isset($pageForPostTypeArchive->id)) {
            return esc_attr($pageForPostTypeArchive->post_title);
        }
        return $title;
    }

    /**
     * Update title for taxonomy pages.
     *
     * @param string $title
     * @return string
     */
    private static function updateTaxonomyTitle(string $title): string
    {
        $queriedObject = get_queried_object();
        $taxonomy = $queriedObject->taxonomy ?? null;
        $taxonomyOptions = Options::getGlobal(self::TAXONOMY_OPTIONS_NAME)[$taxonomy] ?? null;

        if ($taxonomyOptions) {
            return esc_attr($queriedObject->name);
        }

        return $title;
    }

    /**
     * Update nav menu objects.
     *
     * @param array $items
     * @return array
     */
    public function updateNaveMenuObjects(array $sortedMenuItems): array
    {
        global $wp_query;

        $queriedObject = get_queried_object();

        if (!$queriedObject) {
            return $sortedMenuItems;
        }

        $queriedPostType = false;
        $queriedTaxonomy = false;

        if (is_singular()) {
            $queriedPostType = $queriedObject->post_type;
        }

        if (is_post_type_archive()) {
            $queriedPostType = $queriedObject->name;
        }

        if (is_archive() && is_string($wp_query->get('post_type'))) {
            $queryPostType  = $wp_query->get('post_type');
            $queriedPostType = $queryPostType ?: 'post';
        }

        if (is_tax()) {
            $queriedTaxonomy = $queriedObject->taxonomy;
            foreach ($this->postTypes as $postTypeKey => $postType) {
                if (in_array($queriedTaxonomy, $postType['taxonomies'])) {
                    $queriedPostType = $postTypeKey;
                    break;
                }
            }
        }

        if (!$queriedPostType) {
            return $sortedMenuItems;
        }

        $translatableOptions = Options::getGlobal(self::POST_TYPE_OPTIONS_NAME)[$queriedPostType] ?? null;
        $pageForPostTypeArchive = $translatableOptions['pageForPostTypeArchive'] ?? null;
        if (!isset($pageForPostTypeArchive->ID)) {
            return $sortedMenuItems;
        }

        foreach ($sortedMenuItems as &$item) {
            if ($item->type === 'post_type' && $item->object === 'page' && intval($item->object_id) === $pageForPostTypeArchive->ID) {
                if (is_singular($queriedPostType) || is_tax($queriedTaxonomy)) {
                    $item->classes[] = 'current-menu-item-ancestor';
                    $item->current_item_ancestor = true;
                    $sortedMenuItems = $this->recursiveAddAncestor($item, $sortedMenuItems);
                }
                if (is_post_type_archive($queriedPostType)) {
                    $item->classes[] = 'current-menu-item';
                    $item->current = true;
                    $sortedMenuItems = $this->recursiveAddAncestor($item, $sortedMenuItems);
                }
                if (is_archive() && $queriedPostType === $wp_query->get('post_type')) {
                    $sortedMenuItems = $this->recursiveAddAncestor($item, $sortedMenuItems);
                }
            }
        }

        return $sortedMenuItems;
    }

    /**
     * Recursively add the ancestor.
     *
     * @param object $child
     * @param array $items
     * @return array
     */
    protected function recursiveAddAncestor($child, $items)
    {

        if (!intval($child->menu_item_parent)) {
            return $items;
        }

        foreach ($items as $item) {
            if (intval($item->ID) === intval($child->menu_item_parent)) {
                $item->classes[] = 'current-menu-item-ancestor';
                $item->current_item_ancestor = true;
                if (intval($item->menu_item_parent)) {
                    $items = $this->recursiveAddAncestor($item, $items);
                }
                break;
            }
        }

        return $items;
    }

    /**
     * Remove Yoast SEO meta box on custom archive page.
     *
     * @param string $postType
     * @param string $context
     * @return void
     */
    public function yoastRemoveMetaBoxOnCustomArchivePage(string $post_type, WP_Post $post): void
    {
        if ($post_type === 'page') {
            $postTypes = array_keys($this->postTypes);
            foreach ($postTypes as $postType) {
                $translatableOptions = Options::getGlobal(self::POST_TYPE_OPTIONS_NAME)[$postType] ?? null;
                $pageForPostTypeArchive = $translatableOptions['pageForPostTypeArchive'] ?? null;
                if (isset($pageForPostTypeArchive->ID) && $pageForPostTypeArchive->ID === $post->ID) {
                    remove_meta_box('wpseo_meta', 'page', 'normal');
                }
            }
        }
    }

    /**
     * Add admin notice for custom archive page if Yoast SEO plugin is active
     * and the current page is a custom archive page.
     *
     * @return void
     */
    public function yoastAddAdminNoticeOnCustomArchivePage(): void
    {
        global $pagenow;
        if ('post.php' !== $pagenow) {
            return;
        }

        $isYoastPluginActive = is_plugin_active('wordpress-seo/wp-seo.php');
        if (!$isYoastPluginActive) {
            return;
        }

        global $post;
        $postTypes = array_keys($this->postTypes);
        foreach ($postTypes as $postType) {
            $translatableOptions = Options::getGlobal(self::POST_TYPE_OPTIONS_NAME)[$postType] ?? null;
            $pageForPostTypeArchive = $translatableOptions['pageForPostTypeArchive'] ?? null;
            if (isset($pageForPostTypeArchive->ID) && $pageForPostTypeArchive->ID === $post->ID) {
                $title = 'YOAST SEO meta box is disabled for this Custom Post Type archive page!';
                $slug = $translatableOptions['slug'] !== '' ? $translatableOptions['slug'] : $post->post_name;
                $yoastSettingsPageUrl = admin_url('admin.php?page=wpseo_page_settings#/post-type/' . $slug);
                $message = sprintf(
                    __('Change its settings on the %1$sYoast SEO > Content Types > %2$s%3$s page at the %4$s section.', 'flynt'),
                    "<a href='{$yoastSettingsPageUrl}' target='_blank' rel='noopener noreferrer'>",
                    $this->postTypes[$postType]['labels']['name'],
                    "</a>",
                    "<strong>" . $this->postTypes[$postType]['labels']['archives'] . "</strong>"
                );
                echo '<div class="notice notice-warning"><p><strong>' . esc_html($title) . '</strong></p><p>' . wp_kses_post($message) . '</p></div>';
            }
        }
    }
}
