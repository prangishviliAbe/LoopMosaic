<?php
/**
 * Plugin Name: LoopMosaic for Elementor
 * Description: The ultimate Elementor addon for stunning post displays. Create beautiful Mosaic, Grid, and Masonry layouts with advanced features including AJAX-powered modal popups, real-time JetSmartFilters search integration, infinite scroll pagination, and seamless support for Elementor Loop Items & JetEngine Listings. Perfect for portfolios, blogs, product showcases, and dynamic content archives.
 * Version: 1.20.8
 * Author: Abe Prangishvili
 * Author URI: https://github.com/prangishviliAbe/LoopMosaic
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: loop-mosaic
 * Domain Path: /languages
 * Elementor tested up to: 3.18
 * Elementor Pro tested up to: 3.18
 * GitHub Plugin URI: prangishviliAbe/LoopMosaic
 * Primary Branch: main
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('LOOPMOSAIC_VERSION', '1.20.8');
define('LOOPMOSAIC_PATH', plugin_dir_path(__FILE__));
define('LOOPMOSAIC_URL', plugin_dir_url(__FILE__));
define('LOOPMOSAIC_BASENAME', plugin_basename(__FILE__));

/**
 * Main LoopMosaic Class
 */
final class LoopMosaic
{

    /**
     * Instance
     */
    private static $_instance = null;

    /**
     * Ensures only one instance of the class is loaded
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'init'], 0);
    }

    /**
     * Initialize the plugin
     */
    public function init()
    {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_elementor']);
            return;
        }

        // Load translations
        load_plugin_textdomain('loop-mosaic', false, dirname(LOOPMOSAIC_BASENAME) . '/languages');

        // Shared item renderer (single source of truth for all render paths)
        require_once LOOPMOSAIC_PATH . 'includes/class-renderer.php';

        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);

        // Enqueue styles
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_styles']);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'enqueue_styles']);

        // Enqueue scripts
        add_action('elementor/frontend/after_enqueue_scripts', [$this, 'enqueue_scripts']);

        // AJAX Actions
        add_action('wp_ajax_loopmosaic_get_modal_content', [$this, 'ajax_get_modal_content']);
        add_action('wp_ajax_nopriv_loopmosaic_get_modal_content', [$this, 'ajax_get_modal_content']);

        add_action('wp_ajax_loopmosaic_load_more', [$this, 'ajax_load_more']);
        add_action('wp_ajax_nopriv_loopmosaic_load_more', [$this, 'ajax_load_more']);

        // Load JetEngine compatibility
        if (class_exists('Jet_Engine')) {
            require_once LOOPMOSAIC_PATH . 'includes/jetengine-compat.php';
        }

        // Load JetSmartFilters compatibility
        require_once LOOPMOSAIC_PATH . 'includes/jetsmartfilters-compat.php';

        // Add widget categories
        add_action('elementor/elements/categories_registered', [$this, 'add_widget_categories']);
    }

    /**
     * Admin notice for missing Elementor
     */
    public function admin_notice_missing_elementor()
    {
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'loop-mosaic'),
            '<strong>LoopMosaic</strong>',
            '<strong>Elementor</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Register widget categories
     */
    public function add_widget_categories($elements_manager)
    {
        $elements_manager->add_category(
            'loop-mosaic',
        [
            'title' => esc_html__('LoopMosaic', 'loop-mosaic'),
            'icon' => 'eicon-gallery-grid',
        ]
        );
    }

    /**
     * Register widgets
     */
    public function register_widgets($widgets_manager)
    {
        require_once LOOPMOSAIC_PATH . 'widgets/mosaic-loop-widget.php';
        $widgets_manager->register(new \LoopMosaic\Widgets\Mosaic_Loop_Widget());
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles()
    {
        $grid_css      = LOOPMOSAIC_PATH . 'assets/css/mosaic-grid.css';
        $modal_css     = LOOPMOSAIC_PATH . 'assets/css/mosaic-modal.css';
        $carousel_css  = LOOPMOSAIC_PATH . 'assets/css/mosaic-carousel.css';

        wp_enqueue_style('loop-mosaic-grid',     LOOPMOSAIC_URL . 'assets/css/mosaic-grid.css',     [], file_exists($grid_css)     ? filemtime($grid_css)     : LOOPMOSAIC_VERSION);
        wp_enqueue_style('loop-mosaic-modal',    LOOPMOSAIC_URL . 'assets/css/mosaic-modal.css',    [], file_exists($modal_css)    ? filemtime($modal_css)    : LOOPMOSAIC_VERSION);
        wp_enqueue_style('loop-mosaic-carousel', LOOPMOSAIC_URL . 'assets/css/mosaic-carousel.css', [], file_exists($carousel_css) ? filemtime($carousel_css) : LOOPMOSAIC_VERSION);

        // Swiper CSS (loaded only once; no-op if already enqueued by another plugin)
        wp_enqueue_style(
            'swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
            [],
            '11'
        );
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts()
    {
        // Dependencies - jQuery is essential.
        // We do NOT depend on 'jet-smart-filters' here because it might not be loaded on the page
        // if no filter widget is present. We handle its absence in JS.
        $deps = ['jquery', 'masonry', 'imagesloaded'];

        // Swiper JS
        wp_enqueue_script(
            'swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
            [],
            '11',
            true
        );

        wp_enqueue_script(
            'loop-mosaic-filters',
            LOOPMOSAIC_URL . 'assets/js/mosaic-filters.js',
            $deps,
            LOOPMOSAIC_VERSION,
            true
        );

        $carousel_js = LOOPMOSAIC_PATH . 'assets/js/mosaic-carousel.js';
        wp_enqueue_script(
            'loop-mosaic-carousel',
            LOOPMOSAIC_URL . 'assets/js/mosaic-carousel.js',
            ['jquery', 'swiper'],
            file_exists($carousel_js) ? filemtime($carousel_js) : LOOPMOSAIC_VERSION,
            true
        );

        wp_localize_script('loop-mosaic-filters', 'loopMosaicConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('loop_mosaic_nonce'),
        ]);
    }

    /**
     * AJAX Get Modal Content
     */
    public function ajax_get_modal_content()
    {
        check_ajax_referer('loop_mosaic_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $auto_template = !empty($_POST['auto_template']) && 'false' !== $_POST['auto_template'];

        if (!$post_id) {
            wp_send_json_error('Invalid Post ID');
        }

        // Get Post
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        // Only expose publicly viewable content. This prevents enumerating
        // private/draft/password-protected posts via the modal endpoint (IDOR).
        if ('publish' !== $post->post_status || !empty($post->post_password)) {
            wp_send_json_error('Post not available');
        }

        // A manually supplied template id must be an actual Elementor template,
        // not an arbitrary post id, so the endpoint can't render private content.
        if ($template_id && 'elementor_library' !== get_post_type($template_id)) {
            $template_id = 0;
        }

        setup_postdata($post);

        // Render Content
        $html = '';

        if ($auto_template && !$template_id && class_exists('\ElementorPro\Plugin')) {
            // Mock Global Query for Elementor Conditions
            global $wp_query, $wp_the_query;
            $original_query = $wp_query;
            $original_the_query = $wp_the_query;

            try {
                $mock_query = new \WP_Query([
                    'p' => $post_id,
                    'post_type' => $post->post_type,
                    'posts_per_page' => 1,
                ]);

                // Force singular state
                $mock_query->is_singular = true;
                $mock_query->is_single = true; // Added explicit single check
                $mock_query->is_page = false;
                $mock_query->is_home = false;
                $mock_query->is_archive = false;
                $mock_query->is_admin = false;
                $mock_query->in_the_loop = true;
                $mock_query->queried_object = $post;
                $mock_query->queried_object_id = $post_id;
                $mock_query->post = $post;

                // Replace globals
                $wp_query = $mock_query;
                $wp_the_query = $mock_query;
                $GLOBALS['post'] = $post;

                // Fix for JetEngine Context
                if (function_exists('jet_engine')) {
                    jet_engine()->listings->data->set_current_object($post);
                }

                $theme_builder = \ElementorPro\Plugin::instance()->modules_manager->get_modules('theme-builder');
                $conditions_manager = $theme_builder->get_conditions_manager();

                try {
                    $loc_templates = $conditions_manager->get_location_templates('single');

                    if (!empty($loc_templates) && is_array($loc_templates)) {
                        foreach ($loc_templates as $key => $val) {
                            // Check key
                            $doc_key = \Elementor\Plugin::$instance->documents->get($key);
                            if ($doc_key && $doc_key->is_built_with_elementor()) {
                                $template_id = $key;
                                break;
                            }

                            // Check value
                            $doc_val = \Elementor\Plugin::$instance->documents->get($val);
                            if ($doc_val && $doc_val->is_built_with_elementor()) {
                                $template_id = $val;
                                break;
                            }
                        }
                    }
                }
                catch (\Throwable $e) {
                // Silent fail - errors are handled gracefully
                }

                if ($template_id && class_exists('\Elementor\Plugin')) {
                    // FIX: Switch Elementor's Global Post Context for Dynamic Tags
                    $elementor_db_switched = false;
                    if (\Elementor\Plugin::$instance->db->switch_to_post($post_id)) {
                        $elementor_db_switched = true;
                    }

                    // Attempt 1: Standard API
                    $html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($template_id, true);

                    // FIX: Restore Elementor Context
                    if ($elementor_db_switched) {
                        \Elementor\Plugin::$instance->db->switch_to_post(0); // Restore
                    }

                    // FIX: Force Load CSS for this Template
                    if (class_exists('\Elementor\Core\Files\CSS\Post')) {
                        $css_file = new \Elementor\Core\Files\CSS\Post($template_id);
                        $css_file->enqueue();
                        $css = $css_file->get_content();

                        if (!empty($css)) {
                            $html = '<style>' . $css . '</style>' . $html;
                        }
                    }

                    // Validation: Check if it actually rendered widgets.
                    $has_content = strpos($html, 'elementor-widget') !== false || strpos($html, 'elementor-section') !== false || (strpos($html, 'e-con-inner') !== false && strlen(strip_tags($html)) > 10);

                    // Attempt 2: Shortcode Fallback
                    if (empty($html) || !$has_content) {
                        $html_shortcode = do_shortcode('[elementor-template id="' . $template_id . '"]');
                        if (!empty($html_shortcode)) {
                            $html = $html_shortcode;
                        }
                    }

                    if (!empty($html)) {
                        $html = '<div class="loopmosaic-elementor-content">' . $html . '</div>';
                    }
                }
            }
            catch (\Throwable $e) {
            // Silent fail - errors are handled gracefully
            }

            // Restore query
            $wp_query = $original_query;
            $wp_the_query = $original_the_query;
        }

        // Render Default if no Elementor content generated
        if (empty($html)) {
            if ($template_id && class_exists('\Elementor\Plugin') && empty($auto_template)) {
                // If it was manual template ID, render here
                $html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($template_id, true);
                $html = '<div class="loopmosaic-elementor-content">' . $html . '</div>';
            }
            else {
                // Default Render logic
                $image_url = get_the_post_thumbnail_url($post_id, 'full');
                if ($image_url) {
                    $html .= '<img src="' . esc_url($image_url) . '" class="loopmosaic-modal-image" alt="' . esc_attr($post->post_title) . '">';
                }

                $content = apply_filters('the_content', $post->post_content);

                $html .= '<div class="loopmosaic-modal-body">';
                $html .= '<h2 class="loopmosaic-modal-title">' . get_the_title($post) . '</h2>';
                $html .= '<div class="loopmosaic-modal-text">' . $content . '</div>';
                $html .= '</div>';
            }
        }

        wp_reset_postdata();

        wp_send_json_success(['content' => $html]);
    }

    /**
     * AJAX Load More for Infinite Scroll
     */
    public function ajax_load_more()
    {
        // Nonce verification with fallback - infinite scroll is read-only, safe for public access
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'loop_mosaic_nonce');
        }

        // If nonce fails and this is a JSF request, try that nonce
        if (!$nonce_valid && isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'loopmosaic_jsf_nonce');
        }

        if (!$nonce_valid) {
            wp_send_json_error('Invalid Security Token');
        }

        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : [];

        if (empty($settings)) {
            wp_send_json_error('Invalid Settings');
        }

        $settings = loopmosaic_sanitize_query_settings($settings);

        // Build Query Args
        $orderby = isset($settings['orderby']) ? $settings['orderby'] : 'date';
        $order   = isset($settings['order']) ? $settings['order'] : 'DESC';
        
        // menu_order needs secondary sort by title and defaults to ASC
        if ('menu_order' === $orderby) {
            $orderby = 'menu_order title';
            $order   = ('DESC' === $order) ? 'ASC' : $order; // Default to ASC for menu_order
        }
        
        $args = [
            'post_type' => isset($settings['post_type']) ? $settings['post_type'] : 'post',
            'posts_per_page' => isset($settings['posts_per_page']) ? $settings['posts_per_page'] : 9,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
            'paged' => $paged,
        ];

        // Taxonomy
        if (!empty($settings['taxonomy']) && !empty($settings['taxonomy_terms'])) {
            $terms = array_map('trim', explode(',', $settings['taxonomy_terms']));
            $args['tax_query'] = [
                [
                    'taxonomy' => $settings['taxonomy'],
                    'field' => 'slug',
                    'terms' => $terms,
                ],
            ];
        }

        // Exclude Posts
        if (!empty($settings['exclude_posts'])) {
            $exclude_ids = array_map('intval', (array) $settings['exclude_posts']);
            $exclude_ids = array_filter($exclude_ids);
            if (!empty($exclude_ids)) {
                $args['post__not_in'] = $exclude_ids;
            }
        }

        // Apply filters hook for compatibility
        $query_id = isset($settings['jsf_query_id']) ? $settings['jsf_query_id'] : 'default';
        $args = apply_filters('loopmosaic/query/args', $args, $settings, $query_id);

        $query = new \WP_Query($args);
        $html = '';

        if ($query->have_posts()) {
            $index = 0;

            while ($query->have_posts()) {
                $query->the_post();

                // Single source of truth for item markup (see class-renderer.php).
                $item_html = LoopMosaic_Renderer::render_item($settings, get_the_ID(), $index);

                // Tag the wrapper as "new" so the JS can play the entry animation.
                $item_html = preg_replace('/class="loopmosaic-item/', 'class="loopmosaic-item loopmosaic-item-new', $item_html, 1);

                $html .= $item_html;
                $index++;
            }
        }

        wp_reset_postdata();

        wp_send_json_success([
            'content' => $html,
            'max_pages' => $query->max_num_pages,
            'found_posts' => $query->found_posts
        ]);
    }
}

// Global helper to sanitize query-affecting settings that arrive from the client
// via AJAX (Load More / JetSmartFilters). Display-only settings are escaped at
// render time, so this focuses on values that reach WP_Query.
if (!function_exists('loopmosaic_sanitize_query_settings')) {
    function loopmosaic_sanitize_query_settings($settings) {
        if (!is_array($settings)) {
            return [];
        }

        // post_type: must exist and be publicly queryable.
        if (isset($settings['post_type'])) {
            $post_type = is_array($settings['post_type'])
                ? array_map('sanitize_key', $settings['post_type'])
                : sanitize_key($settings['post_type']);

            $valid = function ($type) {
                $obj = get_post_type_object($type);
                return $obj && (!empty($obj->public) || !empty($obj->publicly_queryable));
            };

            if (is_array($post_type)) {
                $post_type = array_values(array_filter($post_type, $valid));
                $settings['post_type'] = !empty($post_type) ? $post_type : 'post';
            } else {
                $settings['post_type'] = $valid($post_type) ? $post_type : 'post';
            }
        }

        // taxonomy: must be a registered taxonomy.
        if (!empty($settings['taxonomy'])) {
            $taxonomy = sanitize_key($settings['taxonomy']);
            $settings['taxonomy'] = taxonomy_exists($taxonomy) ? $taxonomy : '';
        }

        // taxonomy_terms: comma-separated slugs.
        if (!empty($settings['taxonomy_terms'])) {
            $terms = array_map('sanitize_title', array_map('trim', explode(',', $settings['taxonomy_terms'])));
            $settings['taxonomy_terms'] = implode(',', array_filter($terms));
        }

        // orderby: whitelist.
        $allowed_orderby = ['date', 'title', 'menu_order', 'rand', 'modified', 'ID', 'name', 'comment_count', 'author'];
        if (isset($settings['orderby']) && !in_array($settings['orderby'], $allowed_orderby, true)) {
            $settings['orderby'] = 'date';
        }

        // order: ASC/DESC only.
        if (isset($settings['order'])) {
            $settings['order'] = ('ASC' === strtoupper($settings['order'])) ? 'ASC' : 'DESC';
        }

        // posts_per_page: clamp to a sane range (no -1 / unbounded queries).
        if (isset($settings['posts_per_page'])) {
            $ppp = intval($settings['posts_per_page']);
            $settings['posts_per_page'] = max(1, min(100, $ppp));
        }

        // exclude_posts: integers only.
        if (!empty($settings['exclude_posts'])) {
            $settings['exclude_posts'] = array_values(array_filter(array_map('intval', (array) $settings['exclude_posts'])));
        }

        return $settings;
    }
}

// Global helper to get redirect URL from redirect plugins or inline redirect scripts.
if (!function_exists('loopmosaic_get_redirect_url')) {
    function loopmosaic_get_redirect_url($post_id) {
        static $redirect_cache = [];

        $post_id = intval($post_id);
        if (!$post_id) {
            return '';
        }

        if (array_key_exists($post_id, $redirect_cache)) {
            return $redirect_cache[$post_id];
        }

        $redirect_meta_keys = [
            'redirect', '_redirect', '_redirect_url', '_page_redirect', '_links_to',
            '_pprredirect_url', '_spr_redirect_url', '_simple_post_redirect_url',
            'spr_redirect_url', 'srx_redirect_url', '_wp_redirect_url'
        ];

        $redirect_meta_keys = apply_filters('loopmosaic/redirect_meta_keys', $redirect_meta_keys);

        foreach ($redirect_meta_keys as $key) {
            $url = get_post_meta($post_id, $key, true);
            if (!empty($url) && (filter_var($url, FILTER_VALIDATE_URL) || strpos($url, '/') === 0)) {
                $redirect_cache[$post_id] = $url;
                return $redirect_cache[$post_id];
            }
        }

        $all_meta = get_post_meta($post_id);
        if (!empty($all_meta) && is_array($all_meta)) {
            $ignore_keys = ['_thumbnail_id', '_edit_lock', '_edit_last', '_wp_page_template'];

            foreach ($all_meta as $key => $values) {
                if (in_array($key, $ignore_keys, true)) {
                    continue;
                }

                if (stripos($key, 'redirect') !== false || stripos($key, '_links_to') !== false) {
                    foreach ((array) $values as $url) {
                        if (!empty($url) && (filter_var($url, FILTER_VALIDATE_URL) || strpos($url, '/') === 0)) {
                            $redirect_cache[$post_id] = $url;
                            return $redirect_cache[$post_id];
                        }
                    }
                }
            }
        }

        // Only scan content with regex when it could possibly contain a JS
        // redirect. Every pattern below requires "location", so this quick
        // substring check skips the four regexes for the vast majority of posts.
        $content = get_post_field('post_content', $post_id);
        if (!empty($content) && stripos($content, 'location') !== false) {
            $patterns = [
                '/window\.location\.replace\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/i',
                '/window\.location\.href\s*=\s*[\'"]([^\'"]+)[\'"]/i',
                '/document\.location\.href\s*=\s*[\'"]([^\'"]+)[\'"]/i',
                '/location\.href\s*=\s*[\'"]([^\'"]+)[\'"]/i',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    $url = html_entity_decode($matches[1], ENT_QUOTES, get_bloginfo('charset'));
                    if (!empty($url) && (filter_var($url, FILTER_VALIDATE_URL) || strpos($url, '/') === 0)) {
                        $redirect_cache[$post_id] = $url;
                        return $redirect_cache[$post_id];
                    }
                }
            }
        }

        $redirect_cache[$post_id] = '';
        return $redirect_cache[$post_id];
    }
}

// Global helper to determine click action based on redirect meta keys
if (!function_exists('loopmosaic_get_click_action')) {
    function loopmosaic_get_click_action($post_id, $default_action) {
        if ('permalink' === $default_action || 'none' === $default_action) {
            return $default_action;
        }

        if (function_exists('loopmosaic_get_redirect_url') && loopmosaic_get_redirect_url($post_id)) {
            return 'permalink';
        }
        
        return $default_action;
    }
}

// Initialize the plugin
LoopMosaic::instance();
