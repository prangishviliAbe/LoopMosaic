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
    const PROVIDER_ID = 'loop-mosaic';

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
// Register provider class - MUST BE EARLY
        add_action( 'jet-smart-filters/providers/register', [ $this, 'register_provider_class' ] );

        // Register provider list filter (ID => Name)
        add_filter( 'jet-smart-filters/providers/list', [ $this, 'register_provider_list' ] );
        
        // Register available providers (ClassName => Boolean)
        add_filter( 'jet-smart-filters/settings/get/avaliable_providers', [ $this, 'register_available_provider' ] ); // Typos happen
        add_filter( 'jet-smart-filters/settings/get/available_providers', [ $this, 'register_available_provider' ] ); // Correct spelling
        
        // Hooks for the Elementor Widget Dropdown (Crucial!)
        add_filter( 'jet-smart-filters/filter-control/provider-list', [ $this, 'register_provider_list' ] );
        add_filter( 'jet-smart-filters/print-provider-settings/provider-list', [ $this, 'register_provider_list' ] );
        add_filter( 'jet-smart-filters/blocks/allowed-providers', [ $this, 'register_provider_list' ] ); // FOUND IN LOGS!

        // Try to load the class definition EARLY so class_exists() checks pass
        if ( class_exists( 'Jet_Smart_Filters_Provider_Base' ) ) {
            if ( ! class_exists( 'Jet_Smart_Filters_Provider_LoopMosaic' ) ) {
                require_once LOOPMOSAIC_PATH . 'includes/class-jsf-provider.php';
            }
        }
        
        // Register the rest of the hooks on init
        add_action( 'init', [ $this, 'init_hooks' ], 10 );
    }

    /**
     * Initialize hooks
     */
    public function init_hooks() {
        if ( ! class_exists( 'Jet_Smart_Filters' ) ) {
            return;
        }

        // Register the widget setup for AJAX (store settings)
        // Register the widget setup for AJAX (store settings)
        // CORRECT HOOK: Fires BEFORE the wrapper is rendered, allowing add_render_attribute to work
        add_action( 'elementor/frontend/widget/before_render', [ $this, 'store_widget_settings' ] );
        
        // Handle AJAX request for filters (Custom)
        add_action( 'wp_ajax_loopmosaic_jsf_filter', [ $this, 'handle_filter_request' ] );
        add_action( 'wp_ajax_nopriv_loopmosaic_jsf_filter', [ $this, 'handle_filter_request' ] );

        // ROBUST AJAX INTERCEPTOR:
        // We hook early to check if this is OUR request
        add_action( 'wp_ajax_jet_smart_filters', [ $this, 'intercept_jsf_ajax' ], -10 );
        add_action( 'wp_ajax_nopriv_jet_smart_filters', [ $this, 'intercept_jsf_ajax' ], -10 );

        // Enqueue scripts
        add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'enqueue_filter_scripts' ] );
        
        // Brute Force Injection for Elementor Editor
        $jsf_widgets = [
            'jet-smart-filters-checkboxes',
            'jet-smart-filters-radio',
            'jet-smart-filters-select',
            'jet-smart-filters-range',
            'jet-smart-filters-check-range',
            'jet-smart-filters-date-range',
            'jet-smart-filters-date-period',
            'jet-smart-filters-rating',
            'jet-smart-filters-alphabet',
            'jet-smart-filters-search',
            'jet-smart-filters-color-image',
        ];
        
        foreach ( $jsf_widgets as $widget ) {
            add_action( "elementor/element/$widget/section_general/before_section_end", [ $this, 'inject_provider_into_elementor' ], 10, 2 );
            // Some widgets might use 'section_filter_settings' or similar, so let's be broad if needed
            // But 'section_general' is usually where 'content_provider' lives in JSF widgets.
            // Let's also try generic element update if specific one fails? No, specific is better for performance.
            // Checkboxes widget usually puts it in 'section_general'.
        }
        
        // Apply filters query
        add_action( 'pre_get_posts', [ $this, 'apply_filters_to_query' ] );

        // CORRECT FIX: Hook into the widget's query generation
        add_filter( 'loopmosaic/query/args', [ $this, 'on_query_args_filter' ], 10, 3 );

        // BRUTE FORCE: Inject Provider into Elementor Controls
        $jsf_widgets = [
            'jet-smart-filters-checkbox',
            'jet-smart-filters-select',
            'jet-smart-filters-radio',
            'jet-smart-filters-range',
            'jet-smart-filters-check-range',
            'jet-smart-filters-date-range',
            'jet-smart-filters-rating',
            'jet-smart-filters-alphabet',
            'jet-smart-filters-search',
            'jet-smart-filters-color-image',
        ];

        foreach ( $jsf_widgets as $widget ) {
            add_action( "elementor/element/{$widget}/section_general/before_section_end", [ $this, 'inject_provider_into_elementor' ], 10, 2 );
        }
    }

    /**
     * Intercept JSF AJAX requests safely
     */
    public function intercept_jsf_ajax() {
        $provider = isset( $_REQUEST['provider'] ) ? $_REQUEST['provider'] : 'UNKNOWN';
        $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'UNKNOWN';
        
if ( self::PROVIDER_ID !== $provider ) {
            return;
        }
// We link directly to the data provider hook
        add_filter( 'jet-smart-filters/render/ajax/data', [ $this, 'supply_ajax_data' ] );
    }

    /**
     * Supply data during JSF rendering via Filter
     */
    public function supply_ajax_data( $data ) {
// NOW it is safe to load the provider class, as we are deep in JSF execution
        if ( ! class_exists( 'Jet_Smart_Filters_Provider_Base' ) ) {
return $data;
        }

        if ( ! class_exists( 'Jet_Smart_Filters_Provider_LoopMosaic' ) ) {
            require_once LOOPMOSAIC_PATH . 'includes/class-jsf-provider.php';
        }

        if ( null === $this->provider ) {
            $this->provider = new Jet_Smart_Filters_Provider_LoopMosaic();
        }

        $content = $this->provider->ajax_get_content();
        
        $data['content'] = $content;
        $data['provider'] = self::PROVIDER_ID;
        // JSF expects certain keys for pagination etc, which ajax_get_content sets via set_props
        
return $data;
    }

    /**
     * Brute Force: Inject LoopMosaic into Elementor Control Options
     */
    public function inject_provider_into_elementor( $element, $args ) {
        // We look for the 'content_provider' control
        $control_data = $element->get_controls( 'content_provider' );
        
        if ( ! empty( $control_data ) && isset( $control_data['options'] ) ) {
            // Append our provider
            $control_data['options'][ self::PROVIDER_ID ] = esc_html__( 'LoopMosaic Grid', 'loop-mosaic' );
            
            // Update the control
            $element->update_control( 'content_provider', $control_data );
        }
    }

    /**
     * Register LoopMosaic in the provider list
     * e.g. 'loop-mosaic' => 'LoopMosaic Grid'
     */
    public function register_provider_list( $providers ) {
        if ( ! is_array( $providers ) ) {
             // Basic safety check
             return $providers;
        }
        
        $providers[ self::PROVIDER_ID ] = esc_html__( 'LoopMosaic Grid', 'loop-mosaic' );
        return $providers;
    }

    /**
     * Register LoopMosaic in the available providers settings
     * We add BOTH Class Name and ID to be safe.
     */
    public function register_available_provider( $providers ) {
        if ( ! is_array( $providers ) ) {
            return $providers;
        }

        try {
// Ensure class is loaded
            if ( class_exists( 'Jet_Smart_Filters_Provider_Base' ) && ! class_exists( 'Jet_Smart_Filters_Provider_LoopMosaic' ) ) {
                 require_once LOOPMOSAIC_PATH . 'includes/class-jsf-provider.php';
            }

            // Standard: ClassName => true
            $providers['Jet_Smart_Filters_Provider_LoopMosaic'] = true;
            
            // Defensive: ID => true (Just in case)
            $providers['loop-mosaic'] = true;
            
            // Defensive: Name => true (Very unlikely but harmless)
            $providers['LoopMosaic Grid'] = true;

        } catch ( Exception $e ) {
}
        
        return $providers;
    }

    /**
     * Register the provider class
     */
    public function register_provider_class( $providers_manager ) {
        try {
if ( ! class_exists( 'Jet_Smart_Filters_Provider_Base' ) ) {
return;
            }
    
            if ( ! class_exists( 'Jet_Smart_Filters_Provider_LoopMosaic' ) ) {
                require_once LOOPMOSAIC_PATH . 'includes/class-jsf-provider.php';
            }
            
            if ( class_exists( 'Jet_Smart_Filters_Provider_LoopMosaic' ) ) {
                // Ensure we don't re-register
                if ( null === $this->provider ) {
                    $this->provider = new Jet_Smart_Filters_Provider_LoopMosaic();
                }
                
                // Method check to avoid fatal error if register_provider doesn't exist on manager
                if ( method_exists( $providers_manager, 'register_provider' ) ) {
                    $providers_manager->register_provider( $this->provider, $this->provider->get_id() );
                    
                    // Register AJAX handlers for this provider
                    if ( method_exists( $this->provider, 'register_ajax_handler' ) ) {
                        $this->provider->register_ajax_handler();
                    }
                    
} else {
}
            }
        } catch ( Exception $e ) {
} catch ( Error $e ) {
}
    }

    /**
     * Store widget settings for AJAX requests
     */
    public function store_widget_settings( $widget ) {
        // Log every widget attempt to see if hook fires at all
        //
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

        // NOTE: All data-* attributes and classes are now added in mosaic-loop-widget.php
        // This hook just stores the settings for AJAX access
        
}

    /**
     * Apply filters to initial page load
     */
    public function apply_filters_to_query( $query ) {
        if ( ! function_exists( 'jet_smart_filters' ) ) {
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
} else {
}
        
        if ( empty( $settings ) ) {
wp_send_json_error( [ 'message' => 'Settings not found' ] );
        }

        // Get filter args from JetSmartFilters
        $filter_args = [];
        if ( function_exists( 'jet_smart_filters' ) ) {
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
            // Checkboxes (array of taxonomy terms)
            elseif ( $key === 'checkboxes' && is_array( $value ) ) {
                foreach ( $value as $tax_key => $terms ) {
                    if ( ! isset( $args['tax_query'] ) ) {
                        $args['tax_query'] = [ 'relation' => 'AND' ];
                    }
                    $args['tax_query'][] = [
                        'taxonomy' => sanitize_text_field( $tax_key ),
                        'field'    => 'term_id',
                        'terms'    => array_map( 'intval', (array) $terms ),
                    ];
                }
            }
            // Range filter (min/max)
            elseif ( strpos( $key, '_range_' ) !== false || strpos( $key, 'range_' ) === 0 ) {
                $meta_key = str_replace( [ '_range_', 'range_' ], '', $key );
                $range_values = is_array( $value ) ? $value : explode( ';', $value );
                
                if ( count( $range_values ) >= 2 ) {
                    if ( ! isset( $args['meta_query'] ) ) {
                        $args['meta_query'] = [ 'relation' => 'AND' ];
                    }
                    $args['meta_query'][] = [
                        'key'     => $meta_key,
                        'value'   => [ floatval( $range_values[0] ), floatval( $range_values[1] ) ],
                        'type'    => 'NUMERIC',
                        'compare' => 'BETWEEN',
                    ];
                }
            }
        }

        return $args;
    }

    /**
     * Build query args
     */
    public function build_query_args( $settings, $filter_args, $page = 1 ) {
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
     * Apply JSF args to widget query via hook
     */
    public function on_query_args_filter( $args, $settings, $query_id ) {
        if ( ! function_exists( 'jet_smart_filters' ) ) {
            return $args;
        }

        // Check if query ID matches (if one is set in settings)
        // $widget_query_id = isset( $settings['jsf_query_id'] ) ? $settings['jsf_query_id'] : 'default';
        
        // If query_id passed from widget is specific, we might want to validate
        // But usually, JSF filters in URL are global unless scoped. 
        // We simply retrieve current request filters.
        
        $filter_args = jet_smart_filters()->query->get_query_args();

        if ( empty( $filter_args ) ) {
            return $args;
        }

        // We can reuse the build_query_args logic or just the merge part.
        // Since $args is already built by the widget, we need a merge helper.
        return $this->merge_query_args( $args, $filter_args );
    }

    /**
     * Merge filter query args with base args
     */
    public function merge_query_args( $args, $filter_args ) {
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
     * Render filtered content
     */
    private function render_filtered_content( $query, $settings ) {
        if ( $query->have_posts() ) {
            $index = 0;
            $template_source = isset( $settings['template_source'] ) ? $settings['template_source'] : 'default';

            while ( $query->have_posts() ) {
                $query->the_post();

                $item_classes = [ 'loopmosaic-item' ];
                $item_attrs = '';

                // Add overlay for default template
                if ( 'default' === $template_source && ! empty( $settings['color_overlay'] ) && 'yes' === $settings['color_overlay'] ) {
                    // Custom Colors Logic
                    if ( ! empty( $settings['use_custom_overlay_colors'] ) && 'yes' === $settings['use_custom_overlay_colors'] && ! empty( $settings['custom_overlay_colors'] ) ) {
                        $custom_colors = $settings['custom_overlay_colors'];
                        $color_data = $custom_colors[ $index % count( $custom_colors ) ];
                        $color_hex = $color_data['overlay_color'];
                        
                        $text_inv_hex = ! empty( $color_data['overlay_text_color'] ) ? $color_data['overlay_text_color'] : '#ffffff';
                        $hover_inv_hex = ! empty( $color_data['overlay_text_hover_color'] ) ? $color_data['overlay_text_hover_color'] : '#ffffff';
                        $v_align = ! empty( $color_data['text_v_align'] ) ? $color_data['text_v_align'] : 'flex-end';
                        $h_align = ! empty( $color_data['text_h_align'] ) ? $color_data['text_h_align'] : 'flex-start';

                        // Map flex values to text-align values
                        $text_align_map = [
                            'flex-start' => 'left',
                            'center'     => 'center',
                            'flex-end'   => 'right',
                        ];
                        $text_align = isset( $text_align_map[ $h_align ] ) ? $text_align_map[ $h_align ] : 'left';

                        $opacity = isset( $settings['overlay_opacity']['size'] ) ? $settings['overlay_opacity']['size'] : 0.85;

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
                        
                        $item_classes[] = 'overlay-custom';

                        if ( ! empty( $settings['overlay_hover_effect'] ) && 'none' !== $settings['overlay_hover_effect'] ) {
                             $item_classes[] = 'overlay-hover-' . $settings['overlay_hover_effect'];
                        }
                        
                        $hover_opacity = isset($settings['overlay_hover_opacity_value']['size']) ? $settings['overlay_hover_opacity_value']['size'] : 0.5;
                        // Handle legacy float/int structure in JSF if needed, usually passed as array from widget settings but here we read from parsed settings
                        if ( isset($settings['overlay_hover_opacity_value']) && !is_array($settings['overlay_hover_opacity_value']) ) {
                             $hover_opacity = $settings['overlay_hover_opacity_value'];
                        }

                        $rgb_commas = "$r, $g, $b";

                        $item_attrs .= ' style="--lm-custom-overlay: ' . esc_attr( $rgba_color ) . '; --lm-custom-overlay-rgb: ' . $rgb_commas . '; --lm-custom-text: ' . esc_attr( $text_inv_hex ) . '; --lm-custom-text-hover: ' . esc_attr( $hover_inv_hex ) . '; --lm-custom-v-align: ' . $v_align . '; --lm-custom-h-align: ' . $h_align . '; --lm-custom-text-align: ' . $text_align . '; --lm-custom-hover-opacity: ' . $hover_opacity . ';"';
                    } else {
                        // Default Logic
                        $colors = [ 'purple', 'teal', 'gold', 'coral', 'cyan', 'green' ];
                        $item_classes[] = 'overlay-' . $colors[ $index % count( $colors ) ];
                    }
                }

                echo '<div class="' . esc_attr( implode( ' ', $item_classes ) ) . '"' . $item_attrs . '>';
                
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
            $no_posts_message = ! empty( $settings['no_posts_message'] ) ? $settings['no_posts_message'] : esc_html__( 'No posts found.', 'loop-mosaic' );
            echo '<div class="loopmosaic-no-posts">' . esc_html( $no_posts_message ) . '</div>';
        }
    }

    /**
     * Render default card
     */
    private function render_card( $settings ) {
        $image_size = isset( $settings['image_size'] ) ? $settings['image_size'] : 'large';
        $thumbnail = get_the_post_thumbnail_url( get_the_ID(), $image_size );
        $post_id = get_the_ID();
        
        $click_action = isset( $settings['click_action'] ) ? $settings['click_action'] : 'permalink';
        $link_url = get_permalink();
        $link_classes = [ 'loopmosaic-item__link' ];
        $popup_attr = '';
        
        if ( 'modal' === $click_action ) {
            $link_url = 'javascript:void(0);'; 
            $link_classes[] = 'loopmosaic-modal-trigger';
            $popup_attr = ' data-post-id="' . $post_id . '"';

            // Custom Template
            if ( ! empty( $settings['modal_use_custom_template'] ) && 'yes' === $settings['modal_use_custom_template'] ) {
                if ( ! empty( $settings['modal_auto_template'] ) && 'yes' === $settings['modal_auto_template'] ) {
                     $popup_attr .= ' data-auto-template="1"';
                } elseif ( ! empty( $settings['modal_template_id'] ) ) {
                     $popup_attr .= ' data-modal-template-id="' . esc_attr( $settings['modal_template_id'] ) . '"';
                }
            }
            
            if ( empty( $settings['show_gallery_in_modal'] ) || 'yes' !== $settings['show_gallery_in_modal'] ) {
                $popup_attr .= ' data-no-gallery="true"';
            }
        } elseif ( 'none' === $click_action ) {
            $link_url = 'javascript:void(0);';
        }
        ?>
        <a href="<?php echo esc_url( $link_url ); ?>" class="<?php echo esc_attr( implode( ' ', $link_classes ) ); ?>" aria-label="<?php the_title_attribute(); ?>"<?php echo $popup_attr; ?>></a>
        
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
                <div class="loopmosaic-item__excerpt">
                    <?php 
                    $length = isset( $settings['excerpt_length'] ) ? intval( $settings['excerpt_length'] ) : 20;
                    echo esc_html( wp_trim_words( get_the_excerpt(), $length, '...' ) ); 
                    ?>
                </div>
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
