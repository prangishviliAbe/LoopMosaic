<?php
/**
 * JetEngine Compatibility Layer
 * 
 * @package LoopMosaic
 * @author Abe Prangishvili
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class LoopMosaic_JetEngine_Compat
 * 
 * Provides integration with JetEngine listing templates
 */
class LoopMosaic_JetEngine_Compat {

    /**
     * Instance
     */
    private static $_instance = null;

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
        add_action( 'init', [ $this, 'init_hooks' ] );
    }

    /**
     * Initialize hooks
     */
    public function init_hooks() {
        // Add LoopMosaic as a source for JetEngine
        add_filter( 'jet-engine/listing/grid/source-options', [ $this, 'add_source_option' ] );
        
        // Register listing callbacks
        add_filter( 'jet-engine/listing/grid/widget-element-settings', [ $this, 'add_widget_settings' ], 10, 2 );
        
        // Support for JetEngine dynamic data
        add_filter( 'jet-engine/listing/content/post', [ $this, 'setup_listing_post' ], 10, 2 );
    }

    /**
     * Add LoopMosaic as a source option in JetEngine
     */
    public function add_source_option( $sources ) {
        $sources['loopmosaic'] = esc_html__( 'LoopMosaic Grid', 'loop-mosaic' );
        return $sources;
    }

    /**
     * Add widget settings for JetEngine integration
     */
    public function add_widget_settings( $settings, $widget ) {
        if ( isset( $settings['_skin'] ) && 'loopmosaic' === $settings['_skin'] ) {
            $settings['mosaic_enabled'] = true;
        }
        return $settings;
    }

    /**
     * Setup post for listing render
     */
    public function setup_listing_post( $content, $post ) {
        // Ensure post data is available for JetEngine dynamic tokens
        if ( $post instanceof WP_Post ) {
            global $post;
            setup_postdata( $post );
        }
        return $content;
    }

    /**
     * Get available listing templates
     */
    public static function get_listing_templates() {
        $listings = [];

        if ( ! class_exists( 'Jet_Engine' ) ) {
            return $listings;
        }

        $args = [
            'post_type'      => jet_engine()->listings->post_type->slug(),
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];

        $listing_posts = get_posts( $args );

        if ( ! empty( $listing_posts ) ) {
            foreach ( $listing_posts as $listing ) {
                $listings[ $listing->ID ] = $listing->post_title;
            }
        }

        return $listings;
    }

    /**
     * Render a JetEngine listing template
     */
    public static function render_listing( $listing_id, $post_id = null ) {
        if ( ! class_exists( 'Jet_Engine' ) || ! $listing_id ) {
            return '';
        }

        if ( $post_id ) {
            global $post;
            $post = get_post( $post_id );
            setup_postdata( $post );
        }

        $listing_renderer = jet_engine()->listings;
        
        if ( ! $listing_renderer ) {
            return '';
        }

        // Get listing content
        $content = '';
        
        if ( method_exists( $listing_renderer, 'get_listing_item_content' ) ) {
            $content = $listing_renderer->get_listing_item_content( $listing_id );
        } else {
            // Fallback for different JetEngine versions
            $listing_document = \Elementor\Plugin::$instance->documents->get( $listing_id );
            
            if ( $listing_document ) {
                $content = $listing_document->get_content();
            }
        }

        if ( $post_id ) {
            wp_reset_postdata();
        }

        return $content;
    }

    /**
     * Check if JetEngine is active and configured
     */
    public static function is_active() {
        return class_exists( 'Jet_Engine' ) && function_exists( 'jet_engine' );
    }
}

// Initialize
LoopMosaic_JetEngine_Compat::instance();
