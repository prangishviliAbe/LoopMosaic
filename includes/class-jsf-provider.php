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
        $args = [
            'post_type'      => isset( $settings['post_type'] ) ? $settings['post_type'] : 'post',
            'posts_per_page' => isset( $settings['posts_per_page'] ) ? intval( $settings['posts_per_page'] ) : 9,
            'orderby'        => isset( $settings['orderby'] ) ? $settings['orderby'] : 'date',
            'order'          => isset( $settings['order'] ) ? $settings['order'] : 'DESC',
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
            $template_source = isset( $settings['template_source'] ) ? $settings['template_source'] : 'default';

            while ( $query->have_posts() ) {
                $query->the_post();

                $item_classes = [ 'loopmosaic-item' ];

                // Add overlay class for default template
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

        // Determine click action link
        $click_action = ! empty( $settings['click_action'] ) ? $settings['click_action'] : 'permalink';
        $link_url = get_the_permalink();
        $popup_attr = '';
        $link_classes = [ 'loopmosaic-item__link' ];
        
        if ( 'modal' === $click_action ) {
            $link_url = '#';
            $link_classes[] = 'loopmosaic-modal-trigger';
            $popup_attr = ' data-post-id="' . get_the_ID() . '"';
        } elseif ( 'none' === $click_action ) {
            $link_url = 'javascript:void(0);';
        }
        ?>
        <a href="<?php echo esc_url( $link_url ); ?>" class="<?php echo esc_attr( implode( ' ', $link_classes ) ); ?>" aria-label="<?php the_title_attribute(); ?>"<?php echo $popup_attr; ?>></a>
        
        <?php if ( $thumbnail ) : ?>
            <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php the_title_attribute(); ?>" class="loopmosaic-item__image">
        <?php endif; ?>
        
        <?php
        $inner_styles = [];
        if ( ! empty( $settings['card_content_v_align'] ) ) {
            $inner_styles[] = 'justify-content: ' . esc_attr( $settings['card_content_v_align'] );
        }
        if ( ! empty( $settings['card_content_h_align'] ) ) {
            $inner_styles[] = 'align-items: ' . esc_attr( $settings['card_content_h_align'] );
            
            // Map flex values to text-align
            $text_align = 'center';
            if ( 'flex-start' === $settings['card_content_h_align'] ) {
                $text_align = 'left';
            } elseif ( 'flex-end' === $settings['card_content_h_align'] ) {
                $text_align = 'right';
            }
            $inner_styles[] = 'text-align: ' . $text_align;
        }
        $inner_style_attr = ! empty( $inner_styles ) ? ' style="' . implode( '; ', $inner_styles ) . '"' : '';
        ?>

        <div class="loopmosaic-item__inner"<?php echo $inner_style_attr; ?>>
            <?php if ( ! empty( $settings['show_category'] ) && 'yes' === $settings['show_category'] ) : ?>
                <?php
                $categories = get_the_category();
                if ( ! empty( $categories ) ) :
                ?>
                    <span class="loopmosaic-item__category"><?php echo esc_html( $categories[0]->name ); ?></span>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( ! empty( $settings['show_title'] ) && 'yes' === $settings['show_title'] ) : ?>
                <?php
                $title_styles = [];
                if ( ! empty( $settings['title_align'] ) ) {
                    $title_styles[] = 'text-align: ' . esc_attr( $settings['title_align'] );
                }
                $title_style_attr = ! empty( $title_styles ) ? ' style="' . implode( '; ', $title_styles ) . '"' : '';
                ?>
                <h3 class="loopmosaic-item__title"<?php echo $title_style_attr; ?>><?php the_title(); ?></h3>
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
     * Render Elementor Loop template
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
     * Render JetEngine Listing template
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
