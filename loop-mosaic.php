<?php
/**
 * Plugin Name: LoopMosaic
 * Description: The ultimate Elementor addon for stunning post displays. Create beautiful Mosaic, Grid, and Masonry layouts with advanced features including AJAX-powered modal popups, real-time JetSmartFilters search integration, infinite scroll pagination, and seamless support for Elementor Loop Items & JetEngine Listings. Perfect for portfolios, blogs, product showcases, and dynamic content archives.
 * Version: 1.9.15
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

define( 'LOOPMOSAIC_VERSION', '1.9.15' );
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
        add_action( 'plugins_loaded', [ $this, 'init' ], 0 );
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
        
        add_action( 'wp_ajax_loopmosaic_load_more', [ $this, 'ajax_load_more' ] );
        add_action( 'wp_ajax_nopriv_loopmosaic_load_more', [ $this, 'ajax_load_more' ] );

        // Load JetEngine compatibility
        if ( class_exists( 'Jet_Engine' ) ) {
            require_once LOOPMOSAIC_PATH . 'includes/jetengine-compat.php';
        }

        // Load JetSmartFilters compatibility
        require_once LOOPMOSAIC_PATH . 'includes/jetsmartfilters-compat.php';

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
        // Dependencies - jQuery is essential.
        // We do NOT depend on 'jet-smart-filters' here because it might not be loaded on the page
        // if no filter widget is present. We handle its absence in JS.
        $deps = [ 'jquery' ]; 


        wp_enqueue_script(
            'loop-mosaic-filters',
            LOOPMOSAIC_URL . 'assets/js/mosaic-filters.js',
            $deps,
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
                    // Silent fail - errors are handled gracefully
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
                // Silent fail - errors are handled gracefully
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

    /**
     * AJAX Load More for Infinite Scroll
     */
    public function ajax_load_more() {
        // Nonce verification with fallback - infinite scroll is read-only, safe for public access
        $nonce_valid = false;
        if ( isset( $_POST['nonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'loop_mosaic_nonce' );
        }
        
        // If nonce fails and this is a JSF request, try that nonce
        if ( ! $nonce_valid && isset( $_POST['nonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'loopmosaic_jsf_nonce' );
        }

        if ( ! $nonce_valid ) {
            wp_send_json_error( 'Invalid Security Token' );
        }

        $paged    = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
        $settings = isset( $_POST['settings'] ) ? json_decode( stripslashes( $_POST['settings'] ), true ) : [];
        
        if ( empty( $settings ) ) {
            wp_send_json_error( 'Invalid Settings' );
        }

        // Build Query Args
        $args = [
            'post_type'      => isset( $settings['post_type'] ) ? $settings['post_type'] : 'post',
            'posts_per_page' => isset( $settings['posts_per_page'] ) ? $settings['posts_per_page'] : 9,
            'orderby'        => isset( $settings['orderby'] ) ? $settings['orderby'] : 'date',
            'order'          => isset( $settings['order'] ) ? $settings['order'] : 'DESC',
            'post_status'    => 'publish',
            'paged'          => $paged,
        ];

        // Taxonomy
        if ( ! empty( $settings['taxonomy'] ) && ! empty( $settings['taxonomy_terms'] ) ) {
            $terms = array_map( 'trim', explode( ',', $settings['taxonomy_terms'] ) );
            $args['tax_query'] = [
                [
                    'taxonomy' => $settings['taxonomy'],
                    'field'    => 'slug',
                    'terms'    => $terms,
                ],
            ];
        }
        
        // Apply filters hook for compatibility
        $query_id = isset( $settings['jsf_query_id'] ) ? $settings['jsf_query_id'] : 'default';
        $args = apply_filters( 'loopmosaic/query/args', $args, $settings, $query_id );
        
        $query = new \WP_Query( $args );
        $html = '';

        if ( $query->have_posts() ) {
            $template_source = isset( $settings['template_source'] ) ? $settings['template_source'] : 'default';
            $index = 0;

            while ( $query->have_posts() ) {
                $query->the_post();
                
                $item_classes = [ 'loopmosaic-item', 'loopmosaic-item-new' ]; // Added new class for animation
                $item_attrs = '';

                if ( 'default' === $template_source && ! empty( $settings['color_overlay'] ) && 'yes' === $settings['color_overlay'] ) {
                    // Custom Colors Logic
                    if ( ! empty( $settings['use_custom_overlay_colors'] ) && 'yes' === $settings['use_custom_overlay_colors'] && ! empty( $settings['custom_overlay_colors'] ) ) {
                        $custom_colors = $settings['custom_overlay_colors'];
                        $color_data = $custom_colors[ $index % count( $custom_colors ) ];
                        $color_hex = $color_data['overlay_color'];
                        
                        // Handle Opacity
                        $opacity = 0.85;
                        if ( isset( $settings['overlay_opacity'] ) ) {
                             if ( is_array( $settings['overlay_opacity'] ) ) {
                                  $opacity = isset( $settings['overlay_opacity']['size'] ) ? $settings['overlay_opacity']['size'] : 0.85;
                             } else {
                                  $opacity = $settings['overlay_opacity'];
                             }
                        }
                        
                        // Convert Hex to RGBA
                        $color_hex = str_replace('#', '', $color_hex);
                        if ( strlen( $color_hex ) == 3 ) {
                            $r = hexdec( substr( $color_hex, 0, 1 ) . substr( $color_hex, 0, 1 ) );
                            $g = hexdec( substr( $color_hex, 1, 1 ) . substr( $color_hex, 1, 1 ) );
                            $b = hexdec( substr( $color_hex, 2, 1 ) . substr( $color_hex, 2, 1 ) );
                        } else {
                            $r = hexdec( substr( $color_hex, 0, 2 ) );
                            $g = hexdec( substr( $color_hex, 2, 2 ) );
                            $b = hexdec( substr( $color_hex, 4, 2 ) );
                        }
                        $rgba_color = "rgba($r, $g, $b, $opacity)";
                        
                        // Text Color
                        $text_color = ! empty( $color_data['overlay_text_color'] ) ? $color_data['overlay_text_color'] : '#ffffff';
                        $text_hover_color = ! empty( $color_data['overlay_text_hover_color'] ) ? $color_data['overlay_text_hover_color'] : '#ffffff';
                        
                        $item_classes[] = 'overlay-custom';
                        $item_attrs .= ' style="--lm-custom-overlay: ' . esc_attr( $rgba_color ) . '; --lm-custom-text: ' . esc_attr( $text_color ) . '; --lm-custom-text-hover: ' . esc_attr( $text_hover_color ) . ';"';
                    } else {
                        // Helper for overlay color
                        $colors = [ 'purple', 'teal', 'gold', 'coral', 'cyan', 'green' ];
                        $color_class = 'overlay-' . $colors[ $index % count( $colors ) ];
                        $item_classes[] = $color_class;
                    }
                }

                if ( 'default' === $template_source && 'popup' === ( $settings['click_action'] ?? 'permalink' ) && ! empty( $settings['click_popup_id'] ) ) {
                    $item_attrs .= ' data-popup-id="' . esc_attr( $settings['click_popup_id'] ) . '"';
                }

                $html .= '<div class="' . esc_attr( implode( ' ', $item_classes ) ) . '"' . $item_attrs . '>';

                // Inner Content
                if ( 'elementor_loop' === $template_source && ! empty( $settings['elementor_loop_template'] ) ) {
                     if ( class_exists( '\Elementor\Plugin' ) ) {
                         $html .= \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $settings['elementor_loop_template'], true );
                     }
                } elseif ( 'jetengine' === $template_source && ! empty( $settings['jetengine_listing'] ) ) {
                    if ( class_exists( 'Jet_Engine' ) ) {
                        $html .= jet_engine()->listings->get_listing_item_content( $settings['jetengine_listing'] );
                    }
                } else {
                    // Default Card
                    $post_id = get_the_ID();
                    $image_size = ! empty( $settings['image_size'] ) ? $settings['image_size'] : 'large';
                    $thumbnail  = get_the_post_thumbnail_url( $post_id, $image_size );
                    
                    $link_url = get_the_permalink( $post_id );
                    $link_classes = [ 'loopmosaic-item__link' ];
                    $popup_attr = '';
                    
                    $click_action = isset( $settings['click_action'] ) ? $settings['click_action'] : 'permalink';
                    if ( 'modal' === $click_action ) {
                        $link_url = '#'; 
                        $link_classes[] = 'loopmosaic-modal-trigger';
                        $popup_attr = ' data-post-id="' . $post_id . '"';
                        
                        // Pass modal template settings
                         if ( ! empty( $settings['modal_use_custom_template'] ) && 'yes' === $settings['modal_use_custom_template'] ) {
                             if ( ! empty( $settings['modal_auto_template'] ) && 'yes' === $settings['modal_auto_template'] ) {
                                  $popup_attr .= ' data-auto-template="1"';
                             } elseif ( ! empty( $settings['modal_template_id'] ) ) {
                                  $popup_attr .= ' data-modal-template-id="' . esc_attr( $settings['modal_template_id'] ) . '"';
                             }
                         }
                    } elseif ( 'none' === $click_action ) {
                        $link_url = '#';
                    }

                    $html .= '<a href="' . esc_url( $link_url ) . '" class="' . esc_attr( implode( ' ', $link_classes ) ) . '" aria-label="' . the_title_attribute( 'echo=0' ) . '"' . $popup_attr . '></a>';
                    
                    if ( $thumbnail ) {
                        $html .= '<img src="' . esc_url( $thumbnail ) . '" alt="' . the_title_attribute( 'echo=0' ) . '" class="loopmosaic-item__image">';
                    }
                    
                    // Styles
                    $inner_styles = [];
                    if ( ! empty( $settings['card_content_v_align'] ) ) $inner_styles[] = 'justify-content: ' . $settings['card_content_v_align'];
                    if ( ! empty( $settings['card_content_h_align'] ) ) $inner_styles[] = 'align-items: ' . $settings['card_content_h_align'];
                    $inner_attr = ! empty( $inner_styles ) ? ' style="' . implode( '; ', $inner_styles ) . '"' : '';

                    $html .= '<div class="loopmosaic-item__inner"' . $inner_attr . '>';
                    
                    if ( ! empty( $settings['show_category'] ) && 'yes' === $settings['show_category'] ) {
                        $cats = get_the_category();
                        if ( ! empty( $cats ) ) {
                            $html .= '<span class="loopmosaic-item__category">' . esc_html( $cats[0]->name ) . '</span>';
                        }
                    }
                    
                    if ( ! empty( $settings['show_title'] ) && 'yes' === $settings['show_title'] ) {
                        $t_styles = [];
                        if ( ! empty( $settings['title_align'] ) ) $t_styles[] = 'text-align: ' . $settings['title_align'];
                        $t_attr = ! empty( $t_styles ) ? ' style="' . implode( '; ', $t_styles ) . '"' : '';
                        $html .= '<h3 class="loopmosaic-item__title"' . $t_attr . '>' . get_the_title() . '</h3>';
                    }
                    
                     if ( ! empty( $settings['show_excerpt'] ) && 'yes' === $settings['show_excerpt'] ) {
                         $len = isset( $settings['excerpt_length'] ) ? intval( $settings['excerpt_length'] ) : 20;
                         $html .= '<p class="loopmosaic-item__excerpt">' . esc_html( wp_trim_words( get_the_excerpt(), $len, '...' ) ) . '</p>';
                     }

                    $html .= '</div>'; // End inner
                }

                $html .= '</div>'; // End item
                $index++;
            }
        }
        
        wp_reset_postdata();
        
        wp_send_json_success( [
            'content' => $html,
            'max_pages' => $query->max_num_pages,
            'found_posts' => $query->found_posts
        ] );
    }
}

// Initialize the plugin
LoopMosaic::instance();
