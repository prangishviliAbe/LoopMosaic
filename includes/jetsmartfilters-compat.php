<?php
/**
 * JetSmartFilters Compatibility Layer
 * 
 * @package LoopMosaic
 * @author Abe Prangishvili
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class LoopMosaic_JetSmartFilters_Compat
 * 
 * Provides integration with JetSmartFilters for AJAX filtering
 */
class LoopMosaic_JetSmartFilters_Compat {

    /**
     * Provider ID
     */
    const PROVIDER_ID = 'loopmosaic';

    /**
     * Instance
     */
    private static $_instance = null;

    /**
     * Provider instance
     */
    public $provider = null;

    /**
     * Singleton instance
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
        add_action( 'init', [ $this, 'init_hooks' ], 10 );
    }

    /**
     * Initialize hooks
     */
    public function init_hooks() {
        // Register as a filter provider in the list
        add_filter( 'jet-smart-filters/providers/list', [ $this, 'register_provider' ] );
        
        // Register the provider class
        add_action( 'jet-smart-filters/providers/register', [ $this, 'register_provider_class' ] );
        
        // Add AJAX handlers
        add_action( 'wp_ajax_loopmosaic_jsf_filter', [ $this, 'handle_filter_request' ] );
        add_action( 'wp_ajax_nopriv_loopmosaic_jsf_filter', [ $this, 'handle_filter_request' ] );
        
        // Enqueue filter scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_filter_scripts' ] );
        
        // Store widget settings for AJAX
        add_action( 'elementor/frontend/widget/before_render', [ $this, 'store_widget_settings' ], 10, 1 );
        
        // Apply filters to initial page load
        add_action( 'pre_get_posts', [ $this, 'apply_filters_to_query' ] );
    }

    /**
     * Register LoopMosaic as a filter provider
     */
    public function register_provider( $providers ) {
        $providers[ self::PROVIDER_ID ] = esc_html__( 'LoopMosaic Grid', 'loop-mosaic' );
        return $providers;
    }

    /**
     * Register the provider class
     */
    public function register_provider_class( $providers_manager ) {
        if ( ! class_exists( 'Jet_Smart_Filters_Provider_Base' ) ) {
            return;
        }

        require_once LOOPMOSAIC_PATH . 'includes/class-jsf-provider.php';
        
        if ( class_exists( 'Jet_Smart_Filters_Provider_LoopMosaic' ) ) {
            $this->provider = new Jet_Smart_Filters_Provider_LoopMosaic();
            $providers_manager->register_provider( $this->provider );
        }
    }

    /**
     * Store widget settings for AJAX requests
     */
    public function store_widget_settings( $widget ) {
        if ( 'loopmosaic-grid' !== $widget->get_name() ) {
            return;
        }

        $settings = $widget->get_settings_for_display();
        
        if ( empty( $settings['enable_jsf'] ) || 'yes' !== $settings['enable_jsf'] ) {
            return;
        }

        $query_id = ! empty( $settings['jsf_query_id'] ) ? $settings['jsf_query_id'] : 'default';
        
        // Store in transient for AJAX access
        set_transient( 'loopmosaic_settings_' . $query_id, $settings, HOUR_IN_SECONDS );
        
        // Store in provider if available
        if ( $this->provider ) {
            $this->provider->store_settings( $settings, $widget->get_id(), $query_id );
        }
    }

    /**
     * Apply filters to initial page load
     */
    public function apply_filters_to_query( $query ) {
        if ( ! class_exists( 'Jet_Smart_Filters' ) ) {
            return;
        }

        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        // Check for filter params in URL
        $filter_args = jet_smart_filters()->query->get_query_args();
        
        if ( empty( $filter_args ) ) {
            return;
        }

        // Apply via filter hook
        do_action( 'loopmosaic/jsf/apply_filters', $filter_args, $query );
    }

    /**
     * Enqueue filter scripts
     */
    public function enqueue_filter_scripts() {
        if ( ! class_exists( 'Jet_Smart_Filters' ) ) {
            return;
        }

        wp_localize_script( 'loop-mosaic-filters', 'loopMosaicJSF', [
            'provider' => self::PROVIDER_ID,
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'loopmosaic_jsf_nonce' ),
        ] );
    }

    /**
     * Handle AJAX filter request
     */
    public function handle_filter_request() {
        // Verify nonce
        if ( ! check_ajax_referer( 'loopmosaic_jsf_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce' ] );
        }

        $query_id = isset( $_POST['query_id'] ) ? sanitize_text_field( $_POST['query_id'] ) : 'default';
        $page     = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        
        // Get settings from transient or POST
        $settings = get_transient( 'loopmosaic_settings_' . $query_id );
        
        if ( ! $settings && isset( $_POST['settings'] ) ) {
            $settings = json_decode( stripslashes( $_POST['settings'] ), true );
        }
        
        if ( empty( $settings ) ) {
            wp_send_json_error( [ 'message' => 'Settings not found' ] );
        }

        // Get filter args from JetSmartFilters
        $filter_args = [];
        if ( class_exists( 'Jet_Smart_Filters' ) ) {
            $filter_args = jet_smart_filters()->query->get_query_args();
        }
        
        // Also check POST filters
        if ( isset( $_POST['filters'] ) ) {
            $post_filters = is_array( $_POST['filters'] ) ? $_POST['filters'] : json_decode( stripslashes( $_POST['filters'] ), true );
            if ( ! empty( $post_filters ) ) {
                $filter_args = array_merge( $filter_args, $this->parse_filters( $post_filters ) );
            }
        }

        // Build query args
        $args = $this->build_query_args( $settings, $filter_args, $page );

        // Run query
        $query = new WP_Query( $args );

        ob_start();
        $this->render_filtered_content( $query, $settings );
        $content = ob_get_clean();

        wp_send_json_success( [
            'content'       => $content,
            'found_posts'   => $query->found_posts,
            'max_pages'     => $query->max_num_pages,
            'current_page'  => $page,
            'query_id'      => $query_id,
        ] );
    }

    /**
     * Parse filter values from request
     */
    private function parse_filters( $filters ) {
        $args = [];

        foreach ( $filters as $key => $value ) {
            if ( empty( $value ) ) {
                continue;
            }

            // Taxonomy filters
            if ( strpos( $key, '_tax_' ) !== false || strpos( $key, 'tax_' ) === 0 ) {
                $taxonomy = str_replace( [ '_tax_', 'tax_' ], '', $key );
                
                if ( ! isset( $args['tax_query'] ) ) {
                    $args['tax_query'] = [ 'relation' => 'AND' ];
                }

                $args['tax_query'][] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => is_array( $value ) ? $value : explode( ',', $value ),
                ];
            }
            // Meta filters
            elseif ( strpos( $key, '_meta_' ) !== false || strpos( $key, 'meta_' ) === 0 ) {
                $meta_key = str_replace( [ '_meta_', 'meta_' ], '', $key );
                
                if ( ! isset( $args['meta_query'] ) ) {
                    $args['meta_query'] = [ 'relation' => 'AND' ];
                }

                $args['meta_query'][] = [
                    'key'     => $meta_key,
                    'value'   => $value,
                    'compare' => is_array( $value ) ? 'IN' : '=',
                ];
            }
            // Search
            elseif ( $key === 's' || $key === '_s' ) {
                $args['s'] = sanitize_text_field( $value );
            }
            // Date filters
            elseif ( $key === 'date_from' || $key === '_date_from' ) {
                if ( ! isset( $args['date_query'] ) ) {
                    $args['date_query'] = [];
                }
                $args['date_query']['after'] = $value;
            }
            elseif ( $key === 'date_to' || $key === '_date_to' ) {
                if ( ! isset( $args['date_query'] ) ) {
                    $args['date_query'] = [];
                }
                $args['date_query']['before'] = $value;
            }
            // Author
            elseif ( $key === 'author' || $key === '_author' ) {
                $args['author'] = intval( $value );
            }
            // Ordering
            elseif ( $key === 'orderby' || $key === '_orderby' ) {
                $args['orderby'] = sanitize_text_field( $value );
            }
            elseif ( $key === 'order' || $key === '_order' ) {
                $args['order'] = strtoupper( sanitize_text_field( $value ) );
            }
        }

        return $args;
    }

    /**
     * Build query args
     */
    private function build_query_args( $settings, $filter_args, $page = 1 ) {
        $args = [
            'post_type'      => isset( $settings['post_type'] ) ? $settings['post_type'] : 'post',
            'posts_per_page' => isset( $settings['posts_per_page'] ) ? intval( $settings['posts_per_page'] ) : 9,
            'orderby'        => isset( $settings['orderby'] ) ? $settings['orderby'] : 'date',
            'order'          => isset( $settings['order'] ) ? $settings['order'] : 'DESC',
            'post_status'    => 'publish',
            'paged'          => $page,
        ];

        // Base taxonomy from widget
        if ( ! empty( $settings['taxonomy'] ) && ! empty( $settings['taxonomy_terms'] ) ) {
            $terms = array_map( 'trim', explode( ',', $settings['taxonomy_terms'] ) );
            $args['tax_query'] = [
                'relation' => 'AND',
                [
                    'taxonomy' => $settings['taxonomy'],
                    'field'    => 'slug',
                    'terms'    => $terms,
                ],
            ];
        }

        // Merge filter args
        if ( ! empty( $filter_args ) ) {
            // Handle tax_query merge
            if ( ! empty( $filter_args['tax_query'] ) ) {
                if ( empty( $args['tax_query'] ) ) {
                    $args['tax_query'] = [ 'relation' => 'AND' ];
                }
                foreach ( $filter_args['tax_query'] as $tq ) {
                    if ( is_array( $tq ) && ! isset( $tq['relation'] ) ) {
                        $args['tax_query'][] = $tq;
                    }
                }
                unset( $filter_args['tax_query'] );
            }

            // Handle meta_query merge
            if ( ! empty( $filter_args['meta_query'] ) ) {
                if ( empty( $args['meta_query'] ) ) {
                    $args['meta_query'] = [ 'relation' => 'AND' ];
                }
                foreach ( $filter_args['meta_query'] as $mq ) {
                    if ( is_array( $mq ) && ! isset( $mq['relation'] ) ) {
                        $args['meta_query'][] = $mq;
                    }
                }
                unset( $filter_args['meta_query'] );
            }

            // Merge remaining
            $args = array_merge( $args, $filter_args );
        }

        return $args;
    }

    /**
     * Render filtered content
     */
    private function render_filtered_content( $query, $settings ) {
        if ( $query->have_posts() ) {
            $index = 0;
            $template_source = isset( $settings['template_source'] ) ? $settings['template_source'] : 'default';

            while ( $query->have_posts() ) {
                $query->the_post();

                $item_classes = [ 'loopmosaic-item' ];

                // Add overlay for default template
                if ( 'default' === $template_source && ! empty( $settings['color_overlay'] ) && 'yes' === $settings['color_overlay'] ) {
                    $colors = [ 'purple', 'teal', 'gold', 'coral', 'cyan', 'green' ];
                    $item_classes[] = 'overlay-' . $colors[ $index % count( $colors ) ];
                }

                echo '<div class="' . esc_attr( implode( ' ', $item_classes ) ) . '">';
                
                switch ( $template_source ) {
                    case 'elementor_loop':
                        $this->render_elementor_template( $settings );
                        break;
                    case 'jetengine':
                        $this->render_jetengine_template( $settings );
                        break;
                    default:
                        $this->render_card( $settings );
                        break;
                }
                
                echo '</div>';

                $index++;
            }

            wp_reset_postdata();
        } else {
            echo '<div class="loopmosaic-no-posts">' . esc_html__( 'No posts found.', 'loop-mosaic' ) . '</div>';
        }
    }

    /**
     * Render default card
     */
    private function render_card( $settings ) {
        $image_size = isset( $settings['image_size'] ) ? $settings['image_size'] : 'large';
        $thumbnail = get_the_post_thumbnail_url( get_the_ID(), $image_size );
        ?>
        <a href="<?php the_permalink(); ?>" class="loopmosaic-item__link" aria-label="<?php the_title_attribute(); ?>"></a>
        
        <?php if ( $thumbnail ) : ?>
            <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php the_title_attribute(); ?>" class="loopmosaic-item__image">
        <?php endif; ?>
        
        <div class="loopmosaic-item__inner">
            <?php if ( ! empty( $settings['show_category'] ) && 'yes' === $settings['show_category'] ) : ?>
                <?php
                $categories = get_the_category();
                if ( ! empty( $categories ) ) :
                ?>
                    <span class="loopmosaic-item__category"><?php echo esc_html( $categories[0]->name ); ?></span>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( ! empty( $settings['show_title'] ) && 'yes' === $settings['show_title'] ) : ?>
                <h3 class="loopmosaic-item__title"><?php the_title(); ?></h3>
            <?php endif; ?>

            <?php if ( ! empty( $settings['show_excerpt'] ) && 'yes' === $settings['show_excerpt'] ) : ?>
                <p class="loopmosaic-item__excerpt">
                    <?php 
                    $length = isset( $settings['excerpt_length'] ) ? intval( $settings['excerpt_length'] ) : 20;
                    echo esc_html( wp_trim_words( get_the_excerpt(), $length, '...' ) ); 
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Elementor template
     */
    private function render_elementor_template( $settings ) {
        $template_id = isset( $settings['elementor_loop_template'] ) ? $settings['elementor_loop_template'] : '';
        
        if ( ! $template_id || ! class_exists( '\Elementor\Plugin' ) ) {
            $this->render_card( $settings );
            return;
        }

        echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );
    }

    /**
     * Render JetEngine template
     */
    private function render_jetengine_template( $settings ) {
        $listing_id = isset( $settings['jetengine_listing'] ) ? $settings['jetengine_listing'] : '';
        
        if ( ! $listing_id || ! class_exists( 'Jet_Engine' ) ) {
            $this->render_card( $settings );
            return;
        }

        $listing = jet_engine()->listings;
        if ( $listing ) {
            echo $listing->get_listing_item_content( $listing_id );
        }
    }

    /**
     * Check if JetSmartFilters is active
     */
    public static function is_active() {
        return class_exists( 'Jet_Smart_Filters' );
    }
}

// Initialize
LoopMosaic_JetSmartFilters_Compat::instance();
