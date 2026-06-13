<?php
/**
 * JetSmartFilters Provider Class for LoopMosaic
 * 
 * @package LoopMosaic
 * @author Abe Prangishvili
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if base class exists
if ( ! class_exists( 'Jet_Smart_Filters_Provider_Base' ) ) {
    return;
}

/**
 * Class Jet_Smart_Filters_Provider_LoopMosaic
 */
class Jet_Smart_Filters_Provider_LoopMosaic extends Jet_Smart_Filters_Provider_Base {

    /**
     * Watch for default query
     */
    public $query_id = 'default';

    /**
     * Store settings
     */
    private $settings = [];

    /**
     * Provider ID
     */
    public function get_id() {
        return 'loop-mosaic';
    }

    /**
     * Provider name
     */
    public function get_name() {
        return esc_html__( 'LoopMosaic Grid', 'loop-mosaic' );
    }

    /**
     * Check if this provider is in use
     */
    public function is_active() {
        return true;
    }

    /**
     * Store provider settings from widget
     */
    public function store_settings( $settings, $widget_id = '', $query_id = 'default' ) {
        $this->settings[ $query_id ] = $settings;
        $this->query_id = $query_id;
    }

    /**
     * Get provider settings
     */
    public function get_stored_settings( $query_id = 'default' ) {
        if ( isset( $this->settings[ $query_id ] ) ) {
            return $this->settings[ $query_id ];
        }
        
        // Fallback to transient
        $transient_key = 'loopmosaic_settings_' . $query_id;
        $settings = get_transient( $transient_key );
        
        if ( $settings ) {
            $this->settings[ $query_id ] = $settings;
            return $settings;
        }
        
        return [];
    }

    /**
     * Get filtered provider content
     */
    public function ajax_get_content() {
        
        $query_id = isset( $_REQUEST['query_id'] ) ? $_REQUEST['query_id'] : 'default';
        $settings = $this->get_stored_settings( $query_id );
        
        if ( empty( $settings ) && ! empty( $_REQUEST['settings'] ) ) {
            $settings = $_REQUEST['settings'];
        }

        // Harden query-affecting values (settings may originate from the client).
        if ( function_exists( 'loopmosaic_sanitize_query_settings' ) ) {
            $settings = loopmosaic_sanitize_query_settings( $settings );
        }

        $props = isset( $_REQUEST['props'] ) ? $_REQUEST['props'] : [];
        
        // Get query vars from JetSmartFilters
        $query_args = jet_smart_filters()->query->get_query_args();
        
        // Build base query
        $args = $this->build_query_args( $settings );
        
        // Merge with filter query args
        if ( ! empty( $query_args ) ) {
            $args = $this->merge_query_args( $args, $query_args );
        }
        
        // Run query
        $query = new WP_Query( $args );
        
        // Store query for pagination
        jet_smart_filters()->query->set_props(
            $this->get_id(),
            [
                'found_posts'   => $query->found_posts,
                'max_num_pages' => $query->max_num_pages,
                'page'          => $query->get( 'paged' ) ? $query->get( 'paged' ) : 1,
                'query_type'    => 'posts_query',
                'query_meta'    => [],
            ],
            $query_id
        );

        // Start output
        ob_start();
        $this->render_posts( $query, $settings );
        return ob_get_clean();
    }

    /**
     * Get filtered provider content for page reload mode
     */
    public function get_content( $query_id = 'default' ) {
        $settings = $this->get_stored_settings( $query_id );
        
        // Get query vars from URL
        $query_args = jet_smart_filters()->query->get_query_args();
        
        // Build base query
        $args = $this->build_query_args( $settings );
        
        // Merge with filter query args
        if ( ! empty( $query_args ) ) {
            $args = $this->merge_query_args( $args, $query_args );
        }
        
        // Run query
        $query = new WP_Query( $args );
        
        // Start output
        ob_start();
        $this->render_posts( $query, $settings );
        return ob_get_clean();
    }

    /**
     * Build query args from settings
     */
    private function build_query_args( $settings ) {
        $orderby = isset( $settings['orderby'] ) ? $settings['orderby'] : 'date';
        $order   = isset( $settings['order'] ) ? $settings['order'] : 'DESC';
        
        // menu_order needs secondary sort by title and defaults to ASC
        if ( 'menu_order' === $orderby ) {
            $orderby = 'menu_order title';
            $order   = ( 'DESC' === $order ) ? 'ASC' : $order;
        }
        
        $args = [
            'post_type'      => isset( $settings['post_type'] ) ? $settings['post_type'] : 'post',
            'posts_per_page' => isset( $settings['posts_per_page'] ) ? intval( $settings['posts_per_page'] ) : 9,
            'orderby'        => $orderby,
            'order'          => $order,
            'post_status'    => 'publish',
        ];

        // Apply taxonomy filter from widget settings
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

        // Exclude Posts
        if ( ! empty( $settings['exclude_posts'] ) ) {
            $exclude_ids = array_map( 'intval', (array) $settings['exclude_posts'] );
            $exclude_ids = array_filter( $exclude_ids );
            if ( ! empty( $exclude_ids ) ) {
                $args['post__not_in'] = $exclude_ids;
            }
        }

        return $args;
    }

    /**
     * Merge filter query args with base args
     */
    private function merge_query_args( $args, $filter_args ) {
        // Handle tax_query merge
        if ( ! empty( $filter_args['tax_query'] ) ) {
            if ( empty( $args['tax_query'] ) ) {
                $args['tax_query'] = [ 'relation' => 'AND' ];
            }
            foreach ( $filter_args['tax_query'] as $tax_query ) {
                if ( is_array( $tax_query ) && ! isset( $tax_query['relation'] ) ) {
                    $args['tax_query'][] = $tax_query;
                }
            }
            unset( $filter_args['tax_query'] );
        }

        // Handle meta_query merge
        if ( ! empty( $filter_args['meta_query'] ) ) {
            if ( empty( $args['meta_query'] ) ) {
                $args['meta_query'] = [ 'relation' => 'AND' ];
            }
            foreach ( $filter_args['meta_query'] as $meta_query ) {
                if ( is_array( $meta_query ) && ! isset( $meta_query['relation'] ) ) {
                    $args['meta_query'][] = $meta_query;
                }
            }
            unset( $filter_args['meta_query'] );
        }

        // Merge remaining args
        return array_merge( $args, $filter_args );
    }

    /**
     * Render posts
     */
    private function render_posts( $query, $settings ) {
        if ( $query->have_posts() ) {
            $index = 0;

            while ( $query->have_posts() ) {
                $query->the_post();
                // Single source of truth for item markup (see class-renderer.php).
                echo LoopMosaic_Renderer::render_item( $settings, get_the_ID(), $index );
                $index++;
            }

            wp_reset_postdata();
        } else {
            echo LoopMosaic_Renderer::render_no_posts( $settings );
        }
    }

    /**
     * Get provider wrapper selector
     */
    public function get_wrapper_selector() {
        return '.loopmosaic-grid[data-provider="loopmosaic"]';
    }

    /**
     * Apply filters in request
     */
    public function apply_filters_in_request() {
        $args = jet_smart_filters()->query->get_query_args();

        if ( ! $args ) {
            return;
        }
        

        add_filter( 'loopmosaic/query/args', function( $query_args ) use ( $args ) {
            return $this->merge_query_args( $query_args, $args );
        } );
    }

    /**
     * Get provider content wrapper selector
     */
    public function get_content_selector() {
        return '.loopmosaic-grid';
    }

    /**
     * Get page URL
     */
    public function get_page_url() {
        return get_the_permalink();
    }

    /**
     * Register provider in JetSmartFilters
     */
    public function register_ajax_handler() {
        add_action( 'wp_ajax_jet_smart_filters', [ $this, 'ajax_response' ], 0 );
        add_action( 'wp_ajax_nopriv_jet_smart_filters', [ $this, 'ajax_response' ], 0 );
    }

    /**
     * Ajax response
     */
    public function ajax_response() {
        $provider = isset( $_REQUEST['provider'] ) ? $_REQUEST['provider'] : '';
        
        
        if ( $this->get_id() !== $provider ) {
            return;
        }


        add_filter( 'jet-smart-filters/render/ajax/data', [ $this, 'add_ajax_data' ] );
    }

    /**
     * Add ajax data
     */
    public function add_ajax_data( $data ) {
        $content = $this->ajax_get_content();
        
        $data['content'] = $content;
        $data['provider'] = $this->get_id();
        
        return $data;
    }
}
