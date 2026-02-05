<?php
/**
 * Plugin Name: LoopMosaic
 * Description: Advanced Elementor addon for displaying posts in flexible Mosaic, Grid, and Masonry layouts. Features built-in AJAX Modals, full JetSmartFilters compatibility, and support for Elementor Loop Items & JetEngine Listings.
 * Version: 1.2.1
 * Author: Abe Prangishvili
 * Author URI: https://github.com/prangishviliAbe
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: loop-mosaic
 * Domain Path: /languages
 * Elementor tested up to: 3.18
 * Elementor Pro tested up to: 3.18
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'LOOPMOSAIC_VERSION', '1.2.1' );
define( 'LOOPMOSAIC_PATH', plugin_dir_path( __FILE__ ) );
define( 'LOOPMOSAIC_URL', plugin_dir_url( __FILE__ ) );
define( 'LOOPMOSAIC_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main LoopMosaic Class
 */
final class LoopMosaic {

    /**
     * Instance
     */
    private static $_instance = null;

    /**
     * Ensures only one instance of the class is loaded
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if Elementor is installed and activated
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_missing_elementor' ] );
            return;
        }

        // Load translations
        load_plugin_textdomain( 'loop-mosaic', false, dirname( LOOPMOSAIC_BASENAME ) . '/languages' );

        // Register widgets
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );

        // Enqueue styles
        add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'enqueue_styles' ] );
        add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_styles' ] );

        // Enqueue scripts
        add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // AJAX Actions
        add_action( 'wp_ajax_loopmosaic_get_modal_content', [ $this, 'ajax_get_modal_content' ] );
        add_action( 'wp_ajax_nopriv_loopmosaic_get_modal_content', [ $this, 'ajax_get_modal_content' ] );

        // Load JetEngine compatibility
        if ( class_exists( 'Jet_Engine' ) ) {
            require_once LOOPMOSAIC_PATH . 'includes/jetengine-compat.php';
        }

        // Load JetSmartFilters compatibility
        if ( class_exists( 'Jet_Smart_Filters' ) ) {
            require_once LOOPMOSAIC_PATH . 'includes/jetsmartfilters-compat.php';
        }

        // Add widget categories
        add_action( 'elementor/elements/categories_registered', [ $this, 'add_widget_categories' ] );
    }

    /**
     * Admin notice for missing Elementor
     */
    public function admin_notice_missing_elementor() {
        $message = sprintf(
            esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'loop-mosaic' ),
            '<strong>LoopMosaic</strong>',
            '<strong>Elementor</strong>'
        );

        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    /**
     * Register widget categories
     */
    public function add_widget_categories( $elements_manager ) {
        $elements_manager->add_category(
            'loop-mosaic',
            [
                'title' => esc_html__( 'LoopMosaic', 'loop-mosaic' ),
                'icon'  => 'eicon-gallery-grid',
            ]
        );
    }

    /**
     * Register widgets
     */
    public function register_widgets( $widgets_manager ) {
        require_once LOOPMOSAIC_PATH . 'widgets/mosaic-loop-widget.php';
        $widgets_manager->register( new \LoopMosaic\Widgets\Mosaic_Loop_Widget() );
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        wp_enqueue_style( 'loop-mosaic-grid', LOOPMOSAIC_URL . 'assets/css/mosaic-grid.css', [], LOOPMOSAIC_VERSION );
        wp_enqueue_style( 'loop-mosaic-modal', LOOPMOSAIC_URL . 'assets/css/mosaic-modal.css', [], LOOPMOSAIC_VERSION );
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'loop-mosaic-filters',
            LOOPMOSAIC_URL . 'assets/js/mosaic-filters.js',
            [ 'jquery', 'elementor-frontend' ],
            LOOPMOSAIC_VERSION,
            true
        );

        wp_localize_script( 'loop-mosaic-filters', 'loopMosaicConfig', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'loop_mosaic_nonce' ),
        ] );
    }

    /**
     * AJAX Get Modal Content
     */
    public function ajax_get_modal_content() {
        check_ajax_referer( 'loop_mosaic_nonce', 'nonce' );

        $post_id       = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $template_id   = isset( $_POST['template_id'] ) ? intval( $_POST['template_id'] ) : 0;
        $auto_template = isset( $_POST['auto_template'] ) ? $_POST['auto_template'] : false;

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid Post ID' );
        }

        // Get Post
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( 'Post not found' );
        }

        setup_postdata( $post );

        // Render Content
        $html = '';

        if ( $auto_template && ! $template_id && class_exists( '\ElementorPro\Plugin' ) ) {
             // Mock Global Query for Elementor Conditions
            global $wp_query, $wp_the_query;
            $original_query = $wp_query;
            $original_the_query = $wp_the_query;
            
            try {
                $mock_query = new \WP_Query( [
                    'p'              => $post_id,
                    'post_type'      => $post->post_type,
                    'posts_per_page' => 1,
                ] );
                
                // Force singular state
                $mock_query->is_singular = true;
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
                if ( function_exists( 'jet_engine' ) ) {
                    jet_engine()->listings->data->set_current_object( $post );
                }

                $theme_builder = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' );
                $conditions_manager = $theme_builder->get_conditions_manager();
                
                try {
                     $loc_templates = $conditions_manager->get_location_templates( 'single' ); 
                     
                     if ( ! empty( $loc_templates ) && is_array( $loc_templates ) ) {
                         foreach ( $loc_templates as $key => $val ) {
                             // Check key
                             $doc_key = \Elementor\Plugin::$instance->documents->get( $key );
                             if ( $doc_key && $doc_key->is_built_with_elementor() ) {
                                 $template_id = $key;
                                 break;
                             }
                             
                             // Check value
                             $doc_val = \Elementor\Plugin::$instance->documents->get( $val );
                             if ( $doc_val && $doc_val->is_built_with_elementor() ) {
                                 $template_id = $val;
                                 break;
                             }
                         }
                     }
                } catch ( \Throwable $e ) {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'LoopMosaic Auto Template Error: ' . $e->getMessage() );
                    }
                }

                if ( $template_id && class_exists( '\Elementor\Plugin' ) ) {
                    // Attempt 1: Standard API
                    $html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );
                    
                    // FIX: Force Load CSS for this Template
                    if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
                        $css_file = new \Elementor\Core\Files\CSS\Post( $template_id );
                        $css_file->enqueue(); 
                        $css = $css_file->get_content();
                        
                        if ( ! empty( $css ) ) {
                            $html = '<style>' . $css . '</style>' . $html;
                        }
                    }
                    
                    // Validation: Check if it actually rendered widgets.
                    $has_content = strpos( $html, 'elementor-widget' ) !== false || strpos( $html, 'elementor-section' ) !== false || ( strpos( $html, 'e-con-inner' ) !== false && strlen(strip_tags($html)) > 10 );

                    // Attempt 2: Shortcode Fallback
                    if ( empty( $html ) || ! $has_content ) {
                        $html_shortcode = do_shortcode( '[elementor-template id="' . $template_id . '"]' );
                        if ( ! empty( $html_shortcode ) ) {
                            $html = $html_shortcode;
                        }
                    }

                    if ( ! empty( $html ) ) {
                        $html = '<div class="loopmosaic-elementor-content">' . $html . '</div>';
                    }
                }
            } catch ( \Throwable $e ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'LoopMosaic Auto Template Error (Global Block): ' . $e->getMessage() );
                }
            }

            // Restore query
            $wp_query = $original_query;
            $wp_the_query = $original_the_query;
        }

        // Render Default if no Elementor content generated
        if ( empty( $html ) ) {
            if ( $template_id && class_exists( '\Elementor\Plugin' ) && empty($auto_template) ) {
                // If it was manual template ID, render here
                $html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );
                $html = '<div class="loopmosaic-elementor-content">' . $html . '</div>';
            } else {
                // Default Render logic
                $image_url = get_the_post_thumbnail_url( $post_id, 'full' );
                if ( $image_url ) {
                    $html .= '<img src="' . esc_url( $image_url ) . '" class="loopmosaic-modal-image" alt="' . esc_attr( $post->post_title ) . '">';
                }
    
                $content = apply_filters( 'the_content', $post->post_content );
                
                $html .= '<div class="loopmosaic-modal-body">';
                $html .= '<h2 class="loopmosaic-modal-title">' . get_the_title( $post ) . '</h2>';
                $html .= '<div class="loopmosaic-modal-text">' . $content . '</div>';
                $html .= '</div>';
            }
        }

        wp_reset_postdata();

        wp_send_json_success( [ 'content' => $html ] );
    }
}

// Initialize the plugin
LoopMosaic::instance();
