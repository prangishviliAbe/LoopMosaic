<?php
/**
 * LoopMosaic - Elementor Widget
 * 
 * @package LoopMosaic
 * @author Abe Prangishvili
 */

namespace LoopMosaic\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if (!defined('ABSPATH')) {
    exit;
}

class Mosaic_Loop_Widget extends Widget_Base
{

    public function get_name()
    {
        return 'loopmosaic-grid';
    }

    public function get_title()
    {
        return esc_html__('LoopMosaic Grid', 'loop-mosaic');
    }

    public function get_icon()
    {
        return 'eicon-gallery-grid';
    }

    public function get_categories()
    {
        return ['loop-mosaic', 'general'];
    }

    public function get_keywords()
    {
        return ['loop', 'mosaic', 'grid', 'masonry', 'jetengine', 'listing'];
    }

    public function get_style_depends()
    {
        return ['loop-mosaic-grid'];
    }

    public function get_script_depends()
    {
        return ['loop-mosaic-filters'];
    }

    protected function register_controls()
    {
        // === CONTENT TAB ===

        // Template Source Section
        $this->start_controls_section(
            'section_template',
        [
            'label' => esc_html__('Template Source', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_CONTENT,
        ]
        );

        $template_options = [
            'default' => esc_html__('Default Card', 'loop-mosaic'),
        ];

        // Add Elementor Loop option
        if (class_exists('\Elementor\Plugin')) {
            $template_options['elementor_loop'] = esc_html__('Elementor Loop Item', 'loop-mosaic');
        }

        // Add JetEngine option
        if (class_exists('Jet_Engine')) {
            $template_options['jetengine'] = esc_html__('JetEngine Listing', 'loop-mosaic');
        }

        $this->add_control(
            'template_source',
        [
            'label' => esc_html__('Template Source', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => $template_options,
            'default' => 'default',
        ]
        );

        // Elementor Loop Item Template
        $this->add_control(
            'elementor_loop_template',
        [
            'label' => esc_html__('Loop Template', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT2,
            'options' => $this->get_elementor_loop_templates(),
            'default' => '',
            'condition' => [
                'template_source' => 'elementor_loop',
            ],
            'description' => esc_html__('Select an Elementor Loop Item template', 'loop-mosaic'),
        ]
        );

        // JetEngine Listing Template
        if (class_exists('Jet_Engine')) {
            $this->add_control(
                'jetengine_listing',
            [
                'label' => esc_html__('Listing Template', 'loop-mosaic'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_jetengine_listings(),
                'default' => '',
                'condition' => [
                    'template_source' => 'jetengine',
                ],
                'description' => esc_html__('Select a JetEngine listing template', 'loop-mosaic'),
            ]
            );
        }

        $this->end_controls_section();

        // Query Section
        $this->start_controls_section(
            'section_query',
        [
            'label' => esc_html__('Query', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_CONTENT,
        ]
        );

        $this->add_control(
            'post_type',
        [
            'label' => esc_html__('Post Type', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => $this->get_post_types(),
            'default' => 'post',
        ]
        );

        $this->add_control(
            'posts_per_page',
        [
            'label' => esc_html__('Posts Per Page', 'loop-mosaic'),
            'type' => Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 150,
            'default' => 9,
        ]
        );

        $this->add_control(
            'orderby',
        [
            'label' => esc_html__('Order By', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'date' => esc_html__('Date', 'loop-mosaic'),
                'title' => esc_html__('Title', 'loop-mosaic'),
                'menu_order' => esc_html__('Menu Order', 'loop-mosaic'),
                'rand' => esc_html__('Random', 'loop-mosaic'),
                'comment_count' => esc_html__('Comment Count', 'loop-mosaic'),
            ],
            'default' => 'date',
        ]
        );

        $this->add_control(
            'order',
        [
            'label' => esc_html__('Order', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'DESC' => esc_html__('Descending', 'loop-mosaic'),
                'ASC' => esc_html__('Ascending', 'loop-mosaic'),
            ],
            'default' => 'DESC',
        ]
        );

        // Taxonomy Filter
        $this->add_control(
            'taxonomy_heading',
        [
            'label' => esc_html__('Taxonomy Filter', 'loop-mosaic'),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ]
        );

        // Dynamic taxonomy selects per post type
        $post_types = $this->get_post_types();
        foreach ($post_types as $pt_slug => $pt_label) {
            $pt_taxonomies = get_object_taxonomies($pt_slug, 'objects');
            $tax_options = ['' => esc_html__('None', 'loop-mosaic')];
            foreach ($pt_taxonomies as $tax) {
                if ($tax->public) {
                    $tax_options[$tax->name] = $tax->label;
                }
            }

            $this->add_control(
                'taxonomy_' . $pt_slug,
                [
                    'label' => esc_html__('Taxonomy', 'loop-mosaic'),
                    'type' => Controls_Manager::SELECT,
                    'options' => $tax_options,
                    'default' => '',
                    'condition' => [
                        'post_type' => $pt_slug,
                    ],
                ]
            );

            // Term multi-selects per taxonomy
            foreach ($pt_taxonomies as $tax) {
                if (!$tax->public) continue;
                $terms = get_terms([
                    'taxonomy' => $tax->name,
                    'hide_empty' => false,
                ]);
                $term_options = [];
                if (!is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $term_options[$term->slug] = $term->name;
                    }
                }

                $this->add_control(
                    'taxonomy_terms_' . $pt_slug . '_' . $tax->name,
                    [
                        'label' => sprintf(esc_html__('%s Terms', 'loop-mosaic'), $tax->label),
                        'type' => Controls_Manager::SELECT2,
                        'options' => $term_options,
                        'multiple' => true,
                        'label_block' => true,
                        'condition' => [
                            'post_type' => $pt_slug,
                            'taxonomy_' . $pt_slug => $tax->name,
                        ],
                    ]
                );
            }
        }

        // Exclude Posts
        $this->add_control(
            'exclude_posts_heading',
            [
                'label'     => esc_html__( 'Exclude Posts', 'loop-mosaic' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'exclude_posts',
            [
                'label'       => esc_html__( 'Exclude Posts', 'loop-mosaic' ),
                'type'        => Controls_Manager::SELECT2,
                'options'     => $this->get_all_posts_options(),
                'multiple'    => true,
                'label_block' => true,
                'description' => esc_html__( 'Select posts to exclude from the grid.', 'loop-mosaic' ),
            ]
        );

        $this->add_control(
            'enable_infinite_scroll',
        [
            'label' => esc_html__('Enable Infinite Scroll', 'loop-mosaic'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => esc_html__('Yes', 'loop-mosaic'),
            'label_off' => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default' => 'no',
            'separator' => 'before',
        ]
        );

        $this->add_control(
            'infinite_scroll_trigger',
        [
            'label' => esc_html__('Load More Trigger', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'scroll' => esc_html__('Scroll (Auto)', 'loop-mosaic'),
                'button' => esc_html__('Load More Button', 'loop-mosaic'),
            ],
            'default' => 'scroll',
            'condition' => [
                'enable_infinite_scroll' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'load_more_button_text',
        [
            'label' => esc_html__('Button Text', 'loop-mosaic'),
            'type' => Controls_Manager::TEXT,
            'default' => esc_html__('Load More', 'loop-mosaic'),
            'condition' => [
                'enable_infinite_scroll' => 'yes',
                'infinite_scroll_trigger' => 'button',
            ],
        ]
        );

        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section(
            'section_layout',
        [
            'label' => esc_html__('Layout', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_CONTENT,
        ]
        );

        $this->add_control(
            'columns',
        [
            'label' => esc_html__('Columns', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
            ],
            'default' => '4',
        ]
        );

        $this->add_control(
            'layout_mode',
        [
            'label' => esc_html__('Layout Mode', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'css_grid'   => esc_html__('CSS Grid (Mosaic)', 'loop-mosaic'),
                'masonry_js' => esc_html__('True Masonry (JS)', 'loop-mosaic'),
                'carousel'   => esc_html__('Carousel (Slider)', 'loop-mosaic'),
            ],
            'default' => 'css_grid',
        ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'column_target',
        [
            'label' => esc_html__('Target Column', 'loop-mosaic'),
            'type' => Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 6,
            'step' => 1,
            'default' => 1,
        ]
        );

        $repeater->add_control(
            'column_height',
        [
            'label' => esc_html__('Item Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range' => [
                'px' => [
                    'min' => 100,
                    'max' => 1000,
                ],
            ],
            'default' => [
                'size' => 300,
                'unit' => 'px',
            ],
            'selectors' => [
                // Selector is handled dynamically in render
            ],
        ]
        );

        $this->add_control(
            'column_rules',
        [
            'label' => esc_html__('Masonry Column Rules', 'loop-mosaic'),
            'type' => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => 'Column {{{ column_target }}} : {{{ column_height.size }}}{{{ column_height.unit }}}',
            'condition' => [
                'layout_mode' => 'masonry_js',
            ],
        ]
        );

        $this->add_control(
            'pattern',
        [
            'label' => esc_html__('Mosaic Pattern', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'classic' => esc_html__('Classic Mosaic', 'loop-mosaic'),
                'metro' => esc_html__('Metro Style', 'loop-mosaic'),
                'masonry' => esc_html__('Masonry', 'loop-mosaic'),
                'highlight' => esc_html__('Highlight First', 'loop-mosaic'),
                'uniform' => esc_html__('Uniform Grid', 'loop-mosaic'),
                'featured_grid' => esc_html__('Featured Grid (2+3)', 'loop-mosaic'),
                'featured_grid_2_4' => esc_html__('Featured Grid (2+4)', 'loop-mosaic'),
                'hero_grid' => esc_html__('Hero Grid (1+3)', 'loop-mosaic'),
            ],
            'default' => 'classic',
            'condition' => [
                'layout_mode' => 'css_grid',
            ],
        ]
        );

        $this->add_control(
            'highlight_item_height',
        [
            'label' => esc_html__('Highlight Item Height', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'full' => esc_html__('Full (2 Rows)', 'loop-mosaic'),
                'half' => esc_html__('Half (1 Row)', 'loop-mosaic'),
            ],
            'default' => 'full',
            'condition' => [
                'pattern' => ['metro', 'highlight', 'classic'],
                'layout_mode' => 'css_grid',
            ],
        ]
        );

        $this->add_control(
            'card_design_style',
        [
            'label' => esc_html__('Card Design', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'overlay' => esc_html__('Overlay Card', 'loop-mosaic'),
                'floating_icon' => esc_html__('Floating Icon Card', 'loop-mosaic'),
            ],
            'default' => 'overlay',
            'separator' => 'before',
            'render_type' => 'template',
        ]
        );

        $this->add_responsive_control(
            'column_gap',
        [
            'label' => esc_html__('Horizontal Gap', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 50,
                ],
            ],
            'default' => [
                'size' => 15,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-grid' => '--lm-column-gap: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'row_gap',
        [
            'label' => esc_html__('Vertical Gap', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 50,
                ],
            ],
            'default' => [
                'size' => 15,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-grid' => '--lm-row-gap: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'min_height',
        [
            'label' => esc_html__('Card Min Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range' => [
                'px' => [
                    'min' => 100,
                    'max' => 500,
                ],
                'vh' => [
                    'min' => 10,
                    'max' => 50,
                ],
            ],
            'default' => [
                'size' => 200,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item' => '--lm-min-height: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'max_height',
        [
            'label' => esc_html__('Card Max Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range' => [
                'px' => [
                    'min' => 200,
                    'max' => 1000,
                ],
                'vh' => [
                    'min' => 20,
                    'max' => 100,
                ],
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item' => '--lm-max-height: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->end_controls_section();

        // ── Carousel Settings ─────────────────────────────────────────────────
        $this->start_controls_section(
            'section_carousel',
        [
            'label'     => esc_html__('Carousel Settings', 'loop-mosaic'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['layout_mode' => 'carousel'],
        ]
        );

        $this->add_responsive_control(
            'carousel_height',
        [
            'label'      => esc_html__('Slide Height', 'loop-mosaic'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range'      => [
                'px' => ['min' => 200, 'max' => 1200, 'step' => 10],
                'vh' => ['min' => 20,  'max' => 100,  'step' => 1],
            ],
            'default'    => ['size' => 520, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .loopmosaic-swiper' => 'height: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_control(
            'carousel_border_radius',
        [
            'label'      => esc_html__('Border Radius', 'loop-mosaic'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .loopmosaic-swiper'                   => 'border-radius: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .loopmosaic-item'                     => 'border-radius: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .lm-stack-card'                       => 'border-radius: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0 0;',
            ],
        ]
        );

        $this->add_control(
            'carousel_loop',
        [
            'label'        => esc_html__('Loop', 'loop-mosaic'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => esc_html__('Yes', 'loop-mosaic'),
            'label_off'    => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
        ]
        );

        $this->add_control(
            'carousel_autoplay',
        [
            'label'        => esc_html__('Autoplay', 'loop-mosaic'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => esc_html__('Yes', 'loop-mosaic'),
            'label_off'    => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default'      => '',
        ]
        );

        $this->add_control(
            'carousel_autoplay_speed',
        [
            'label'     => esc_html__('Autoplay Delay (ms)', 'loop-mosaic'),
            'type'      => Controls_Manager::NUMBER,
            'min'       => 1000,
            'max'       => 10000,
            'step'      => 500,
            'default'   => 4000,
            'condition' => ['carousel_autoplay' => 'yes'],
        ]
        );

        $this->add_control(
            'carousel_speed',
        [
            'label'   => esc_html__('Transition Speed (ms)', 'loop-mosaic'),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 100,
            'max'     => 2000,
            'step'    => 100,
            'default' => 600,
        ]
        );

        $this->add_control(
            'carousel_dots',
        [
            'label'        => esc_html__('Pagination Switches', 'loop-mosaic'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => esc_html__('Yes', 'loop-mosaic'),
            'label_off'    => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
        ]
        );

        $this->add_control(
            'carousel_dots_color',
        [
            'label'     => esc_html__('Switch Color', 'loop-mosaic'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(255,255,255,0.45)',
            'selectors' => [
                '{{WRAPPER}} .swiper-pagination-bullet' => 'background: {{VALUE}};',
            ],
            'condition' => ['carousel_dots' => 'yes'],
        ]
        );

        $this->add_control(
            'carousel_dots_active_color',
        [
            'label'     => esc_html__('Active Switch Color', 'loop-mosaic'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .swiper-pagination-bullet-active' => 'background: {{VALUE}};',
            ],
            'condition' => ['carousel_dots' => 'yes'],
        ]
        );

        $this->add_control(
            'carousel_stack',
        [
            'label'        => esc_html__('Stacked Card Behind', 'loop-mosaic'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => esc_html__('Yes', 'loop-mosaic'),
            'label_off'    => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
            'description'  => esc_html__('Shows a peeking card behind the slide for a stacked-deck look.', 'loop-mosaic'),
        ]
        );

        $this->add_control(
            'carousel_stack_color',
        [
            'label'     => esc_html__('Stacked Card Color', 'loop-mosaic'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#184e4e',
            'selectors' => [
                '{{WRAPPER}} .lm-stack-card' => 'background: {{VALUE}};',
            ],
            'condition' => ['carousel_stack' => 'yes'],
        ]
        );

        $this->add_control(
            'carousel_stack_peek',
        [
            'label'      => esc_html__('Peek Amount', 'loop-mosaic'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 4, 'max' => 40]],
            'default'    => ['size' => 14, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .lm-stack-card' => 'top: calc(-1 * {{SIZE}}{{UNIT}});',
            ],
            'condition'  => ['carousel_stack' => 'yes'],
        ]
        );

        $this->add_control(
            'carousel_stack_inset',
        [
            'label'      => esc_html__('Side Inset', 'loop-mosaic'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .lm-stack-card' => 'left: {{SIZE}}{{UNIT}}; right: calc(var(--lm-carousel-nav-gap, 62px) + {{SIZE}}{{UNIT}});',
            ],
            'condition'  => ['carousel_stack' => 'yes'],
        ]
        );

        $this->end_controls_section();

        // ── Carousel Nav Style ────────────────────────────────────────────────
        $this->start_controls_section(
            'section_carousel_nav_style',
        [
            'label'     => esc_html__('Carousel Navigation', 'loop-mosaic'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['layout_mode' => 'carousel'],
        ]
        );

        $this->add_control(
            'carousel_nav_size',
        [
            'label'      => esc_html__('Button Size', 'loop-mosaic'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 28, 'max' => 80]],
            'default'    => ['size' => 44, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .lm-nav-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_control(
            'carousel_nav_icon_size',
        [
            'label'      => esc_html__('Icon Size', 'loop-mosaic'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 36]],
            'default'    => ['size' => 18, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .lm-nav-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_control(
            'carousel_nav_color',
        [
            'label'     => esc_html__('Icon Color', 'loop-mosaic'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .lm-nav-btn' => 'color: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'carousel_nav_bg',
        [
            'label'     => esc_html__('Background', 'loop-mosaic'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(8,28,28,0.52)',
            'selectors' => [
                '{{WRAPPER}} .lm-nav-btn' => 'background: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'carousel_nav_bg_hover',
        [
            'label'     => esc_html__('Background (Hover)', 'loop-mosaic'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(8,28,28,0.85)',
            'selectors' => [
                '{{WRAPPER}} .lm-nav-btn:hover' => 'background: {{VALUE}};',
            ],
        ]
        );

        $this->end_controls_section();

        // Image Settings Section
        $this->start_controls_section(
            'section_image_settings',
        [
            'label' => esc_html__('Image Settings', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_CONTENT,
        ]
        );

        $this->add_control(
            'image_fit',
        [
            'label' => esc_html__('Image Fit', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'cover' => esc_html__('Cover', 'loop-mosaic'),
                'contain' => esc_html__('Contain', 'loop-mosaic'),
                'fill' => esc_html__('Fill', 'loop-mosaic'),
                'none' => esc_html__('None (Auto)', 'loop-mosaic'),
                'scale-down' => esc_html__('Scale Down', 'loop-mosaic'),
            ],
            'default' => 'cover',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__image' => 'object-fit: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'image_position',
        [
            'label' => esc_html__('Image Position', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'center center' => esc_html__('Center Center', 'loop-mosaic'),
                'center top' => esc_html__('Center Top', 'loop-mosaic'),
                'center bottom' => esc_html__('Center Bottom', 'loop-mosaic'),
                'left center' => esc_html__('Left Center', 'loop-mosaic'),
                'left top' => esc_html__('Left Top', 'loop-mosaic'),
                'left bottom' => esc_html__('Left Bottom', 'loop-mosaic'),
                'right center' => esc_html__('Right Center', 'loop-mosaic'),
                'right top' => esc_html__('Right Top', 'loop-mosaic'),
                'right bottom' => esc_html__('Right Bottom', 'loop-mosaic'),
            ],
            'default' => 'center center',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__image' => 'object-position: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'image_size',
        [
            'label' => esc_html__('Image Size', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => $this->get_image_sizes(),
            'default' => 'large',
        ]
        );

        $this->add_control(
            'image_height_mode',
        [
            'label' => esc_html__('Image Height Mode', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'auto' => esc_html__('Auto (Natural)', 'loop-mosaic'),
                'fill' => esc_html__('Fill Card', 'loop-mosaic'),
                'fixed' => esc_html__('Fixed Height', 'loop-mosaic'),
            ],
            'default' => 'fill',
        ]
        );

        $this->add_responsive_control(
            'image_height',
        [
            'label' => esc_html__('Image Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'vh'],
            'range' => [
                'px' => [
                    'min' => 50,
                    'max' => 600,
                ],
                '%' => [
                    'min' => 10,
                    'max' => 100,
                ],
            ],
            'default' => [
                'size' => 100,
                'unit' => '%',
            ],
            'condition' => [
                'image_height_mode' => 'fixed',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__image' => 'height: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->end_controls_section();

        // Content Display Section
        $this->start_controls_section(
            'section_content_display',
        [
            'label' => esc_html__('Content Display', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_CONTENT,
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
            ],
        ]
        );

        $this->add_control(
            'show_title',
        [
            'label' => esc_html__('Show Title', 'loop-mosaic'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => esc_html__('Yes', 'loop-mosaic'),
            'label_off' => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default' => 'yes',
            'condition' => [
                'template_source' => 'default',
            ],
        ]
        );

        $this->add_control(
            'show_excerpt',
        [
            'label' => esc_html__('Show Excerpt', 'loop-mosaic'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => esc_html__('Yes', 'loop-mosaic'),
            'label_off' => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default' => 'no',
            'condition' => [
                'template_source' => 'default',
            ],
        ]
        );

        $this->add_control(
            'excerpt_length',
        [
            'label' => esc_html__('Excerpt Length', 'loop-mosaic'),
            'type' => Controls_Manager::NUMBER,
            'min' => 10,
            'max' => 100,
            'default' => 20,
            'condition' => [
                'template_source' => 'default',
                'show_excerpt' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'show_category',
        [
            'label' => esc_html__('Show Category', 'loop-mosaic'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => esc_html__('Yes', 'loop-mosaic'),
            'label_off' => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default' => 'yes',
            'condition' => [
                'template_source' => 'default',
            ],
        ]
        );

        $this->add_control(
            'floating_card_icon_heading',
        [
            'label' => esc_html__('Floating Card Icon', 'loop-mosaic'),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
            ],
        ]
        );

        $this->add_control(
            'show_floating_card_icon',
        [
            'label' => esc_html__('Show Icon', 'loop-mosaic'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => esc_html__('Yes', 'loop-mosaic'),
            'label_off' => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default' => 'yes',
            'render_type' => 'template',
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
            ],
        ]
        );

        $this->add_control(
            'floating_card_icon',
        [
            'label' => esc_html__('Icon', 'loop-mosaic'),
            'type' => Controls_Manager::ICONS,
            'default' => [
                'value' => 'fas fa-users',
                'library' => 'fa-solid',
            ],
            'render_type' => 'template',
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_icon' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'floating_card_icon_bg_color',
        [
            'label' => esc_html__('Icon Background', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#d62f67',
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_icon' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'floating_card_icon_color',
        [
            'label' => esc_html__('Icon Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_icon' => 'yes',
            ],
        ]
        );

        $floating_icon_repeater = new \Elementor\Repeater();

        $floating_icon_repeater->add_control(
            'icon',
            [
                'label' => esc_html__('Icon', 'loop-mosaic'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-users',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $floating_icon_repeater->add_control(
            'icon_bg_color',
            [
                'label' => esc_html__('Background', 'loop-mosaic'),
                'type' => Controls_Manager::COLOR,
                'default' => '#d62f67',
            ]
        );

        $floating_icon_repeater->add_control(
            'icon_color',
            [
                'label' => esc_html__('Color', 'loop-mosaic'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
            ]
        );

        $this->add_control(
            'floating_card_icon_items',
        [
            'label' => esc_html__('Icon Set', 'loop-mosaic'),
            'type' => Controls_Manager::REPEATER,
            'fields' => $floating_icon_repeater->get_controls(),
            'title_field' => '{{{ icon.value }}}',
            'render_type' => 'template',
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_icon' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'show_floating_card_arrow',
        [
            'label' => esc_html__('Show Arrow', 'loop-mosaic'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => esc_html__('Yes', 'loop-mosaic'),
            'label_off' => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default' => 'yes',
            'render_type' => 'template',
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
            ],
        ]
        );





        // Overlay Color Toggles
        $this->add_control(
            'color_overlay',
        [
            'label' => esc_html__('Enable Gradient Overlay', 'loop-mosaic'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => esc_html__('Yes', 'loop-mosaic'),
            'label_off' => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default' => 'yes',
            'condition' => [
                'template_source' => 'default',
            ],
        ]
        );

        // Custom Colors Toggle
        $this->add_control(
            'use_custom_overlay_colors',
        [
            'label' => esc_html__('Use Custom Overlay Colors', 'loop-mosaic'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => esc_html__('Yes', 'loop-mosaic'),
            'label_off' => esc_html__('No', 'loop-mosaic'),
            'return_value' => 'yes',
            'default' => '',
            'condition' => [
                'template_source' => 'default',
                'color_overlay' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'overlay_hover_effect',
        [
            'label' => esc_html__('Hover Effect', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'none' => esc_html__('None (Always Visible)', 'loop-mosaic'),
                'fade_in' => esc_html__('Fade In (Show on Hover)', 'loop-mosaic'),
                'fade_out' => esc_html__('Fade Out (Hide on Hover)', 'loop-mosaic'),
                'custom_opacity' => esc_html__('Custom Opacity (On Hover)', 'loop-mosaic'),
            ],
            'default' => 'none',
            'condition' => [
                'template_source' => 'default',
                'color_overlay' => 'yes',
                'use_custom_overlay_colors' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'overlay_hover_opacity_value',
        [
            'label' => esc_html__('Hover Opacity Value', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.05,
                ],
            ],
            'default' => [
                'unit' => 'px',
                'size' => 0.5,
            ],
            'condition' => [
                'template_source' => 'default',
                'color_overlay' => 'yes',
                'use_custom_overlay_colors' => 'yes',
                'overlay_hover_effect' => 'custom_opacity',
            ],
        ]
        );

        // Custom Colors Repeater
        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'overlay_color',
        [
            'label' => esc_html__('Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#8a2be2',
        ]
        );

        $repeater->add_control(
            'overlay_text_color',
        [
            'label' => esc_html__('Text Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
        ]
        );

        $repeater->add_control(
            'overlay_text_hover_color',
        [
            'label' => esc_html__('Text Hover Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
        ]
        );

        $repeater->add_control(
            'text_v_align',
        [
            'label' => esc_html__('Vertical Align', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'flex-start' => esc_html__('Top', 'loop-mosaic'),
                'center' => esc_html__('Middle', 'loop-mosaic'),
                'flex-end' => esc_html__('Bottom', 'loop-mosaic'),
            ],
            'default' => 'flex-end',
        ]
        );

        $repeater->add_control(
            'text_h_align',
        [
            'label' => esc_html__('Horizontal Align', 'loop-mosaic'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'flex-start' => esc_html__('Left', 'loop-mosaic'),
                'center' => esc_html__('Center', 'loop-mosaic'),
                'flex-end' => esc_html__('Right', 'loop-mosaic'),
            ],
            'default' => 'flex-start',
        ]
        );

        $this->add_control(
            'overlay_opacity',
        [
            'label' => esc_html__('Overlay Opacity', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 1,
                    'step' => 0.05,
                ],
            ],
            'default' => [
                'unit' => 'px',
                'size' => 0.85,
            ],
            'condition' => [
                'template_source' => 'default',
                'color_overlay' => 'yes',
                'use_custom_overlay_colors' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'custom_overlay_colors',
        [
            'label' => esc_html__('Custom Colors', 'loop-mosaic'),
            'type' => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'default' => [
                ['overlay_color' => '#8a2be2'], // Purple
                ['overlay_color' => '#008080'], // Teal
                ['overlay_color' => '#DAA520'], // Gold
                ['overlay_color' => '#FF7F50'], // Coral
                ['overlay_color' => '#00CED1'], // Cyan
                ['overlay_color' => '#3CB371'], // Green
            ],
            'title_field' => '{{{ overlay_color }}}',
            'condition' => [
                'template_source' => 'default',
                'color_overlay' => 'yes',
                'use_custom_overlay_colors' => 'yes',
            ],
        ]
        );

        $this->end_controls_section();

        // JetSmartFilters Section
        if (class_exists('Jet_Smart_Filters')) {
            $this->start_controls_section(
                'section_jetsmartfilters',
            [
                'label' => esc_html__('JetSmartFilters', 'loop-mosaic'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
            );

            $this->add_control(
                'enable_jsf',
            [
                'label' => esc_html__('Enable JetSmartFilters', 'loop-mosaic'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'loop-mosaic'),
                'label_off' => esc_html__('No', 'loop-mosaic'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
            );

            $this->add_control(
                'jsf_query_id',
            [
                'label' => esc_html__('Query ID', 'loop-mosaic'),
                'type' => Controls_Manager::TEXT,
                'default' => 'loopmosaic',
                'condition' => [
                    'enable_jsf' => 'yes',
                ],
                'description' => esc_html__('Use this ID in JetSmartFilters Query ID field', 'loop-mosaic'),
            ]
            );

            $this->end_controls_section();
        }

        // Relationship Query Section
        $this->start_controls_section(
            'lm_rel_query_section',
        [
            'label' => esc_html__( 'Relationship Query', 'loop-mosaic' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]
        );

        // Discover available relationship query IDs.
        // Built-in detection for "Post Relationship for Elementor", plus a
        // filter so any relationship plugin can register its own query IDs.
        $rel_ids = [];
        if ( class_exists( 'PR_Rel_Elementor' ) ) {
            $rel_ids['related_posts'] = esc_html__( 'Related posts (this post → linked posts)', 'loop-mosaic' );
            $rel_ids['referenced_by'] = esc_html__( 'Referenced by (posts that link to this one)', 'loop-mosaic' );
        }
        $rel_ids = apply_filters( 'loopmosaic/relationship_query_ids', $rel_ids );

        if ( ! empty( $rel_ids ) && is_array( $rel_ids ) ) {
            $options = [ '' => esc_html__( '— None —', 'loop-mosaic' ) ];
            foreach ( $rel_ids as $id => $label ) {
                $options[ (string) $id ] = $label;
            }

            $this->add_control(
                'lm_rel_query_id',
            [
                'label'       => esc_html__( 'Relationship Source', 'loop-mosaic' ),
                'type'        => Controls_Manager::SELECT,
                'options'     => $options,
                'default'     => '',
                'description' => esc_html__( 'Pull connected posts from your relationship plugin based on the current post.', 'loop-mosaic' ),
            ]
            );
        } else {
            $this->add_control(
                'lm_rel_query_notice',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__( 'No relationship plugin detected. Activate a compatible plugin (e.g. Post Relationship for Elementor) to pull connected posts automatically, or enter a Query ID manually below.', 'loop-mosaic' ),
                'content_classes' => 'elementor-control-field-description',
            ]
            );

            $this->add_control(
                'lm_rel_query_id',
            [
                'label'       => esc_html__( 'Query ID (manual)', 'loop-mosaic' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => 'related_posts',
                'description' => esc_html__( 'Enter the Query ID registered by your relationship plugin. Leave empty to disable.', 'loop-mosaic' ),
            ]
            );
        }

        $this->end_controls_section();

        // Interaction Section
        $this->start_controls_section(
            'section_interaction',
        [
            'label' => esc_html__('Interaction', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_CONTENT,
        ]
        );

        $this->add_control(
            'click_action',
        [
            'label' => esc_html__('Click Action', 'loop-mosaic'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'permalink',
            'options' => [
                'permalink' => esc_html__('Open Post (Permalink)', 'loop-mosaic'),
                'modal' => esc_html__('Open Built-in Modal', 'loop-mosaic'),
                'none' => esc_html__('None', 'loop-mosaic'),
            ],
        ]
        );

        $this->add_control(
            'show_gallery_in_modal',
        [
            'label' => esc_html__('Show Gallery in Modal', 'loop-mosaic'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'condition' => [
                'click_action' => 'modal',
            ],
        ]
        );

        $this->add_control(
            'modal_use_custom_template',
        [
            'label' => esc_html__('Use Custom Template', 'loop-mosaic'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'no',
            'condition' => [
                'click_action' => 'modal',
            ],
        ]
        );

        $this->add_control(
            'modal_auto_template',
        [
            'label' => esc_html__('Auto-Use Assigned Template', 'loop-mosaic'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'no',
            'description' => esc_html__('Automatically use the assigned Single Post template if available (Elementor Pro).', 'loop-mosaic'),
            'condition' => [
                'click_action' => 'modal',
                'modal_use_custom_template' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'modal_template_id',
        [
            'label' => esc_html__('Select Template', 'loop-mosaic'),
            'type' => \Elementor\Controls_Manager::SELECT2,
            'options' => $this->get_elementor_loop_templates(),
            'default' => '',
            'condition' => [
                'click_action' => 'modal',
                'modal_use_custom_template' => 'yes',
                'modal_auto_template' => '',
            ],
        ]
        );

        $this->end_controls_section();

        // No Posts Found Section
        $this->start_controls_section(
            'section_no_posts',
        [
            'label' => esc_html__('No Posts Found', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_CONTENT,
        ]
        );

        $this->add_control(
            'no_posts_message',
        [
            'label' => esc_html__('Message', 'loop-mosaic'),
            'type' => Controls_Manager::TEXTAREA,
            'default' => esc_html__('No posts found.', 'loop-mosaic'),
            'dynamic' => [
                'active' => true,
            ],
        ]
        );

        $this->end_controls_section();

        // Modal Style Section
        $this->start_controls_section(
            'section_modal_style',
        [
            'label' => esc_html__('Modal Style', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_STYLE,
            'condition' => [
                'click_action' => 'modal',
            ],
        ]
        );

        $this->add_responsive_control(
            'modal_width',
        [
            'label' => esc_html__('Modal Width', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'vw'],
            'range' => [
                'px' => [
                    'min' => 200,
                    'max' => 1600,
                ],
                '%' => [
                    'min' => 20,
                    'max' => 100,
                ],
                'vw' => [
                    'min' => 20,
                    'max' => 100,
                ],
            ],
            'default' => [
                'size' => 90,
                'unit' => '%',
            ],
            'selectors' => [
                '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'width: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'modal_max_width',
        [
            'label' => esc_html__('Modal Max Width', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'vw'],
            'range' => [
                'px' => [
                    'min' => 200,
                    'max' => 1920,
                ],
                '%' => [
                    'min' => 20,
                    'max' => 100,
                ],
                'vw' => [
                    'min' => 20,
                    'max' => 100,
                ],
            ],
            'default' => [
                'size' => 900,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'max-width: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'modal_height',
        [
            'label' => esc_html__('Modal Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'vh'],
            'range' => [
                'px' => [
                    'min' => 200,
                    'max' => 1200,
                ],
                '%' => [
                    'min' => 20,
                    'max' => 100,
                ],
                'vh' => [
                    'min' => 20,
                    'max' => 100,
                ],
            ],
            'default' => [
                'size' => 'auto',
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'height: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'modal_max_height',
        [
            'label' => esc_html__('Modal Max Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'vh'],
            'range' => [
                'px' => [
                    'min' => 200,
                    'max' => 1200,
                ],
                '%' => [
                    'min' => 20,
                    'max' => 100,
                ],
                'vh' => [
                    'min' => 20,
                    'max' => 100,
                ],
            ],
            'default' => [
                'size' => 90,
                'unit' => 'vh',
            ],
            'selectors' => [
                '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'max-height: {{SIZE}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'modal_padding',
        [
            'label' => esc_html__('Modal Padding', 'loop-mosaic'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'default' => [
                'top' => 30,
                'right' => 30,
                'bottom' => 30,
                'left' => 30,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-content, #loopmosaic-modal .loopmosaic-modal-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'modal_border_radius',
        [
            'label' => esc_html__('Border Radius', 'loop-mosaic'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'default' => [
                'top' => 12,
                'right' => 12,
                'bottom' => 12,
                'left' => 12,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
        );

        $this->add_control(
            'modal_background_color',
        [
            'label' => esc_html__('Background Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'background-color: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'modal_overlay_color',
        [
            'label' => esc_html__('Overlay Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => 'rgba(0,0,0,0.7)',
            'selectors' => [
                '{{WRAPPER}} ~ #loopmosaic-modal.loopmosaic-modal-overlay, #loopmosaic-modal.loopmosaic-modal-overlay' => 'background-color: {{VALUE}};',
            ],
        ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
        [
            'name' => 'modal_box_shadow',
            'label' => esc_html__('Box Shadow', 'loop-mosaic'),
            'selector' => '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container',
        ]
        );

        $this->add_control(
            'modal_title_heading',
            [
                'label' => esc_html__('Title', 'loop-mosaic'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'modal_title_color',
            [
                'label' => esc_html__('Title Color', 'loop-mosaic'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-title, #loopmosaic-modal .loopmosaic-modal-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'modal_title_typography',
                'selector' => '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-title, #loopmosaic-modal .loopmosaic-modal-title',
            ]
        );

        $this->add_responsive_control(
            'modal_title_margin',
            [
                'label' => esc_html__('Title Margin', 'loop-mosaic'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-title, #loopmosaic-modal .loopmosaic-modal-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_content_heading',
            [
                'label' => esc_html__('Content', 'loop-mosaic'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'modal_content_color',
            [
                'label' => esc_html__('Content Color', 'loop-mosaic'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-text, #loopmosaic-modal .loopmosaic-modal-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'modal_content_typography',
                'selector' => '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-text, #loopmosaic-modal .loopmosaic-modal-text',
            ]
        );

        $this->add_responsive_control(
            'modal_content_margin',
            [
                'label' => esc_html__('Content Margin', 'loop-mosaic'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-text, #loopmosaic-modal .loopmosaic-modal-text' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_image_heading',
            [
                'label' => esc_html__('Image', 'loop-mosaic'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'modal_image_border_radius',
            [
                'label' => esc_html__('Image Border Radius', 'loop-mosaic'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-image, #loopmosaic-modal .loopmosaic-modal-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'modal_image_margin',
            [
                'label' => esc_html__('Image Margin', 'loop-mosaic'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-image, #loopmosaic-modal .loopmosaic-modal-image' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // === STYLE TAB ===

        // Card Styling Section
        $this->start_controls_section(
            'section_card_style',
        [
            'label' => esc_html__('Card', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_STYLE,
        ]
        );

        $this->add_responsive_control(
            'card_padding',
        [
            'label' => esc_html__('Padding', 'loop-mosaic'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'default' => [
                'top' => 20,
                'right' => 20,
                'bottom' => 20,
                'left' => 20,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'card_border_radius',
        [
            'label' => esc_html__('Border Radius', 'loop-mosaic'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'default' => [
                'top' => 8,
                'right' => 8,
                'bottom' => 8,
                'left' => 8,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
        );

        $this->start_controls_tabs(
            'tabs_card_border',
            [
                'condition' => [
                    'card_design_style!' => 'floating_icon',
                ],
            ]
        );

        $this->start_controls_tab(
            'tab_card_border_normal',
            [
                'label' => esc_html__('Normal', 'loop-mosaic'),
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
        [
            'name' => 'card_border',
            'selector' => '{{WRAPPER}} .loopmosaic-item',
        ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_card_border_hover',
            [
                'label' => esc_html__('Hover', 'loop-mosaic'),
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
        [
            'name' => 'card_border_hover',
            'selector' => '{{WRAPPER}} .loopmosaic-item:hover',
        ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
        [
            'name' => 'card_box_shadow',
            'selector' => '{{WRAPPER}} .loopmosaic-item',
        ]
        );

        $this->add_control(
            'floating_card_style_heading',
        [
            'label' => esc_html__('Floating Icon Card', 'loop-mosaic'),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_image_height',
        [
            'label' => esc_html__('Image Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range' => [
                'px' => [
                    'min' => 120,
                    'max' => 520,
                ],
                'vh' => [
                    'min' => 10,
                    'max' => 60,
                ],
            ],
            'default' => [
                'size' => 210,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon' => '--lm-floating-image-height: {{SIZE}}{{UNIT}}; --lm-floating-template-image-height: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
            ],
        ]
        );

        $this->add_control(
            'floating_card_pattern_sizing_heading',
        [
            'label' => esc_html__('Pattern Sizing', 'loop-mosaic'),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'layout_mode' => 'css_grid',
                'pattern' => ['featured_grid', 'featured_grid_2_4', 'hero_grid'],
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_featured_image_height',
        [
            'label' => esc_html__('Featured Image Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range' => [
                'px' => [
                    'min' => 120,
                    'max' => 520,
                ],
                'vh' => [
                    'min' => 10,
                    'max' => 60,
                ],
            ],
            'default' => [
                'size' => 210,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-grid.pattern-featured_grid_2_4 .loopmosaic-card-floating-icon:nth-child(6n+1), {{WRAPPER}} .loopmosaic-grid.pattern-featured_grid_2_4 .loopmosaic-card-floating-icon:nth-child(6n+2), {{WRAPPER}} .loopmosaic-grid.pattern-featured_grid .loopmosaic-card-floating-icon:nth-child(5n+1), {{WRAPPER}} .loopmosaic-grid.pattern-featured_grid .loopmosaic-card-floating-icon:nth-child(5n+2), {{WRAPPER}} .loopmosaic-grid.pattern-hero_grid .loopmosaic-card-floating-icon:nth-child(4n+1)' => '--lm-floating-image-height: {{SIZE}}{{UNIT}}; --lm-floating-template-image-height: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'layout_mode' => 'css_grid',
                'pattern' => ['featured_grid', 'featured_grid_2_4', 'hero_grid'],
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_small_image_height',
        [
            'label' => esc_html__('Small Image Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range' => [
                'px' => [
                    'min' => 100,
                    'max' => 420,
                ],
                'vh' => [
                    'min' => 8,
                    'max' => 50,
                ],
            ],
            'default' => [
                'size' => 185,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-grid.pattern-featured_grid_2_4 .loopmosaic-card-floating-icon:nth-child(6n+3), {{WRAPPER}} .loopmosaic-grid.pattern-featured_grid_2_4 .loopmosaic-card-floating-icon:nth-child(6n+4), {{WRAPPER}} .loopmosaic-grid.pattern-featured_grid_2_4 .loopmosaic-card-floating-icon:nth-child(6n+5), {{WRAPPER}} .loopmosaic-grid.pattern-featured_grid_2_4 .loopmosaic-card-floating-icon:nth-child(6n+6), {{WRAPPER}} .loopmosaic-grid.pattern-featured_grid .loopmosaic-card-floating-icon:nth-child(5n+3), {{WRAPPER}} .loopmosaic-grid.pattern-featured_grid .loopmosaic-card-floating-icon:nth-child(5n+4), {{WRAPPER}} .loopmosaic-grid.pattern-featured_grid .loopmosaic-card-floating-icon:nth-child(5n+5), {{WRAPPER}} .loopmosaic-grid.pattern-hero_grid .loopmosaic-card-floating-icon:nth-child(4n+2), {{WRAPPER}} .loopmosaic-grid.pattern-hero_grid .loopmosaic-card-floating-icon:nth-child(4n+3), {{WRAPPER}} .loopmosaic-grid.pattern-hero_grid .loopmosaic-card-floating-icon:nth-child(4n+4)' => '--lm-floating-image-height: {{SIZE}}{{UNIT}}; --lm-floating-template-image-height: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'layout_mode' => 'css_grid',
                'pattern' => ['featured_grid', 'featured_grid_2_4', 'hero_grid'],
            ],
        ]
        );

        $this->add_control(
            'floating_card_content_bg',
        [
            'label' => esc_html__('Content Background', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon' => '--lm-floating-card-bg: {{VALUE}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_content_min_height',
        [
            'label' => esc_html__('Content Min Height', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 80,
                    'max' => 360,
                ],
            ],
            'default' => [
                'size' => 140,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon' => '--lm-floating-content-min-height: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_content_overlap',
        [
            'label' => esc_html__('Content Overlap', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 80,
                ],
            ],
            'default' => [
                'size' => 20,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon' => '--lm-floating-content-overlap: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_content_padding',
        [
            'label' => esc_html__('Content Padding', 'loop-mosaic'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default' => [
                'top' => 46,
                'right' => 64,
                'bottom' => 42,
                'left' => 24,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon' => '--lm-floating-content-padding-top: {{TOP}}{{UNIT}}; --lm-floating-content-padding-right: {{RIGHT}}{{UNIT}}; --lm-floating-content-padding-bottom: {{BOTTOM}}{{UNIT}}; --lm-floating-content-padding-left: {{LEFT}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_icon_size',
        [
            'label' => esc_html__('Icon Circle Size', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 34,
                    'max' => 96,
                ],
            ],
            'default' => [
                'size' => 58,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon' => '--lm-floating-icon-size: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .loopmosaic-card-floating-icon .loopmosaic-item__floating-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_icon' => 'yes',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_icon_left',
        [
            'label' => esc_html__('Icon Left Offset', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 80,
                ],
            ],
            'default' => [
                'size' => 24,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon' => '--lm-floating-icon-left: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_icon' => 'yes',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_icon_top_default',
        [
            'label' => esc_html__('Icon Top Offset', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => -80,
                    'max' => 40,
                ],
            ],
            'default' => [
                'size' => -29,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon:not(.loopmosaic-card-floating-template)' => '--lm-floating-icon-top: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => 'default',
                'card_design_style' => 'floating_icon',
                'show_floating_card_icon' => 'yes',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_icon_top_template',
        [
            'label' => esc_html__('Icon Top Position', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 40,
                    'max' => 520,
                ],
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-template' => '--lm-floating-template-icon-top: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => 'elementor_loop',
                'card_design_style' => 'floating_icon',
                'show_floating_card_icon' => 'yes',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_icon_font_size',
        [
            'label' => esc_html__('Icon Size', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 12,
                    'max' => 56,
                ],
            ],
            'default' => [
                'size' => 25,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon .loopmosaic-item__floating-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .loopmosaic-card-floating-icon .loopmosaic-item__floating-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_icon' => 'yes',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_arrow_size',
        [
            'label' => esc_html__('Arrow Size', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 12,
                    'max' => 48,
                ],
            ],
            'default' => [
                'size' => 24,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon .loopmosaic-item__floating-arrow' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_arrow' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'floating_card_arrow_color',
        [
            'label' => esc_html__('Arrow Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#d62f67',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon .loopmosaic-item__floating-arrow' => 'color: {{VALUE}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_arrow' => 'yes',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_arrow_right',
        [
            'label' => esc_html__('Arrow Right Offset', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 80,
                ],
            ],
            'default' => [
                'size' => 24,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon' => '--lm-floating-arrow-right: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_arrow' => 'yes',
            ],
        ]
        );

        $this->add_responsive_control(
            'floating_card_arrow_bottom',
        [
            'label' => esc_html__('Arrow Bottom Offset', 'loop-mosaic'),
            'type' => Controls_Manager::SLIDER,
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 80,
                ],
            ],
            'default' => [
                'size' => 26,
                'unit' => 'px',
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-card-floating-icon' => '--lm-floating-arrow-bottom: {{SIZE}}{{UNIT}};',
            ],
            'condition' => [
                'template_source' => ['default', 'elementor_loop'],
                'card_design_style' => 'floating_icon',
                'show_floating_card_arrow' => 'yes',
            ],
        ]
        );

        $this->add_control(
            'overlay_color',
        [
            'label' => esc_html__('Overlay Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__inner' => 'background: linear-gradient(to top, {{VALUE}} 0%, transparent 60%);',
            ],
        ]
        );

        $this->add_control(
            'card_content_v_align',
        [
            'label' => esc_html__('Vertical Alignment', 'loop-mosaic'),
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => [
                    'title' => esc_html__('Top', 'loop-mosaic'),
                    'icon' => 'eicon-align-start-v',
                ],
                'center' => [
                    'title' => esc_html__('Center', 'loop-mosaic'),
                    'icon' => 'eicon-align-center-v',
                ],
                'flex-end' => [
                    'title' => esc_html__('Bottom', 'loop-mosaic'),
                    'icon' => 'eicon-align-end-v',
                ],
            ],
            'default' => 'flex-end',
            'toggle' => true,
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__inner' => 'justify-content: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'card_content_h_align',
        [
            'label' => esc_html__('Horizontal Alignment', 'loop-mosaic'),
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => [
                    'title' => esc_html__('Left', 'loop-mosaic'),
                    'icon' => 'eicon-align-start-h',
                ],
                'center' => [
                    'title' => esc_html__('Center', 'loop-mosaic'),
                    'icon' => 'eicon-align-center-h',
                ],
                'flex-end' => [
                    'title' => esc_html__('Right', 'loop-mosaic'),
                    'icon' => 'eicon-align-end-h',
                ],
            ],
            'default' => 'flex-start',
            'toggle' => true,
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__inner' => 'align-items: {{VALUE}}; text-align: {{VALUE === "flex-start" ? "left" : (VALUE === "flex-end" ? "right" : "center")}};',
            ],
        ]
        );

        $this->end_controls_section();

        // Title Styling Section
        $this->start_controls_section(
            'section_title_style',
        [
            'label' => esc_html__('Title', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_STYLE,
        ]
        );

        $this->add_control(
            'title_color',
        [
            'label' => esc_html__('Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__title' => 'color: {{VALUE}};',
            ],
        ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
        [
            'name' => 'title_typography',
            'selector' => '{{WRAPPER}} .loopmosaic-item__title',
        ]
        );

        $this->add_responsive_control(
            'title_align',
        [
            'label' => esc_html__('Alignment', 'loop-mosaic'),
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                'left' => [
                    'title' => esc_html__('Left', 'loop-mosaic'),
                    'icon' => 'eicon-text-align-left',
                ],
                'center' => [
                    'title' => esc_html__('Center', 'loop-mosaic'),
                    'icon' => 'eicon-text-align-center',
                ],
                'right' => [
                    'title' => esc_html__('Right', 'loop-mosaic'),
                    'icon' => 'eicon-text-align-right',
                ],
            ],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__title' => 'text-align: {{VALUE}};',
            ],
        ]
        );

        $this->end_controls_section();

        // Category Styling Section
        $this->start_controls_section(
            'section_category_style',
        [
            'label' => esc_html__('Category', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_STYLE,
        ]
        );

        $this->add_control(
            'category_bg_color',
        [
            'label' => esc_html__('Background Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => 'rgba(255,255,255,0.2)',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__category' => 'background-color: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'category_text_color',
        [
            'label' => esc_html__('Text Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-item__category' => 'color: {{VALUE}};',
            ],
        ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
        [
            'name' => 'category_typography',
            'selector' => '{{WRAPPER}} .loopmosaic-item__category',
        ]
        );

        $this->end_controls_section();

        $this->register_load_more_style_controls();
        $this->register_no_posts_style_controls();
        $this->register_hover_overlay_style_controls();
        $this->register_animation_controls();
    }

    /**
     * Get post types
     */
    private function get_post_types()
    {
        $post_types = get_post_types(['public' => true], 'objects');
        $options = [];

        foreach ($post_types as $post_type) {
            if ('attachment' !== $post_type->name) {
                $options[$post_type->name] = $post_type->label;
            }
        }

        return $options;
    }

    /**
     * Get taxonomies
     */
    private function get_taxonomies()
    {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $options = ['' => esc_html__('None', 'loop-mosaic')];

        foreach ($taxonomies as $taxonomy) {
            $options[$taxonomy->name] = $taxonomy->label;
        }

        return $options;
    }

    /**
     * Get all posts from all public post types for exclude dropdown
     */
    private function get_all_posts_options() {
        $options = [];
        $post_types = get_post_types( [ 'public' => true ], 'objects' );

        foreach ( $post_types as $pt ) {
            if ( 'attachment' === $pt->name ) {
                continue;
            }

            $posts = get_posts( [
                'post_type'      => $pt->name,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ] );

            foreach ( $posts as $p ) {
                $options[ $p->ID ] = $p->post_title . ' (' . $pt->label . ')';
            }
        }

        return $options;
    }

    /**
     * Get JetEngine listing templates
     */
    private function get_jetengine_listings()
    {
        $listings = ['' => esc_html__('Select Template', 'loop-mosaic')];

        if (!class_exists('Jet_Engine')) {
            return $listings;
        }

        $listing_posts = get_posts([
            'post_type' => 'jet-engine',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        foreach ($listing_posts as $listing) {
            $listings[$listing->ID] = $listing->post_title;
        }

        return $listings;
    }

    /**
     * Get Elementor Popup templates
     */
    private function get_elementor_popups()
    {
        $popups = ['' => esc_html__('Select Popup', 'loop-mosaic')];

        if (!class_exists('\Elementor\Plugin')) {
            return $popups;
        }

        $popup_posts = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'popup',
                ],
            ],
        ]);

        foreach ($popup_posts as $popup) {
            $popups[$popup->ID] = $popup->post_title;
        }

        return $popups;
    }

    /**
     * Register Hover Overlay Controls
     */
    protected function register_hover_overlay_style_controls()
    {
        $this->start_controls_section(
            'section_hover_overlay',
            [
                'label' => esc_html__('Hover Overlay', 'loop-mosaic'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'enable_hover_overlay',
            [
                'label' => esc_html__('Enable Hover Overlay', 'loop-mosaic'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'loop-mosaic'),
                'label_off' => esc_html__('No', 'loop-mosaic'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        // --- Overlay Background ---
        $this->add_control(
            'hover_overlay_bg_color',
            [
                'label' => esc_html__('Overlay Background Color', 'loop-mosaic'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-overlay' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'hover_overlay_backdrop_blur',
            [
                'label' => esc_html__('Backdrop Blur (px)', 'loop-mosaic'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-overlay' => 'backdrop-filter: blur({{SIZE}}px); -webkit-backdrop-filter: blur({{SIZE}}px);',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        // --- Position Controls ---
        $this->add_control(
            'hover_overlay_position_heading',
            [
                'label' => esc_html__('Content Position', 'loop-mosaic'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'hover_overlay_h_align',
            [
                'label' => esc_html__('Horizontal Alignment', 'loop-mosaic'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'loop-mosaic'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'loop-mosaic'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'loop-mosaic'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-overlay' => 'justify-content: {{VALUE}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'hover_overlay_v_align',
            [
                'label' => esc_html__('Vertical Alignment', 'loop-mosaic'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Top', 'loop-mosaic'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => esc_html__('Middle', 'loop-mosaic'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Bottom', 'loop-mosaic'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-overlay' => 'align-items: {{VALUE}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

         $this->add_responsive_control(
            'hover_overlay_padding',
            [
                'label' => esc_html__('Overlay Padding', 'loop-mosaic'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-overlay' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'hover_overlay_offset_y',
            [
                'label' => esc_html__('Offset Y (↑↓)', 'loop-mosaic'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => -300,
                        'max' => 300,
                    ],
                    '%' => [
                        'min' => -100,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-content' => '--lm-hover-offset-y: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'hover_overlay_offset_x',
            [
                'label' => esc_html__('Offset X (←→)', 'loop-mosaic'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => -300,
                        'max' => 300,
                    ],
                    '%' => [
                        'min' => -100,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-content' => '--lm-hover-offset-x: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        // --- Icon Controls ---
        $this->add_control(
            'hover_overlay_icon_heading',
            [
                'label' => esc_html__('Icon', 'loop-mosaic'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'hover_overlay_icon',
            [
                'label' => esc_html__('Icon', 'loop-mosaic'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-link',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'hover_overlay_icon_color',
            [
                'label' => esc_html__('Icon Color', 'loop-mosaic'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-icon i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .loopmosaic-item__hover-icon svg' => 'fill: {{VALUE}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'hover_overlay_icon_size',
            [
                'label' => esc_html__('Icon Size', 'loop-mosaic'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .loopmosaic-item__hover-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        // --- Label Text Controls ---
        $this->add_control(
            'hover_overlay_label_heading',
            [
                'label' => esc_html__('Label Text', 'loop-mosaic'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'hover_overlay_label',
            [
                'label' => esc_html__('Label', 'loop-mosaic'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('e.g. View More', 'loop-mosaic'),
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'hover_overlay_label_color',
            [
                'label' => esc_html__('Label Color', 'loop-mosaic'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-label' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                    'hover_overlay_label!' => '',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'hover_overlay_label_typography',
                'selector' => '{{WRAPPER}} .loopmosaic-item__hover-label',
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                    'hover_overlay_label!' => '',
                ],
            ]
        );

        $this->add_responsive_control(
            'hover_overlay_gap',
            [
                'label' => esc_html__('Gap Between Icon & Label', 'loop-mosaic'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-content' => 'gap: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'hover_overlay_content_direction',
            [
                'label' => esc_html__('Layout Direction', 'loop-mosaic'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'column' => esc_html__('Vertical (Icon on top)', 'loop-mosaic'),
                    'row' => esc_html__('Horizontal (Side by side)', 'loop-mosaic'),
                    'column-reverse' => esc_html__('Vertical (Label on top)', 'loop-mosaic'),
                    'row-reverse' => esc_html__('Horizontal (Label first)', 'loop-mosaic'),
                ],
                'default' => 'column',
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__hover-content' => 'flex-direction: {{VALUE}};',
                ],
                'condition' => [
                    'enable_hover_overlay' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Animation Controls
     */
    protected function register_animation_controls()
    {
        $this->start_controls_section(
            'section_style_animations',
            [
                'label' => esc_html__('Scroll Animations', 'loop-mosaic'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'enable_animations',
            [
                'label' => esc_html__('Enable Animations', 'loop-mosaic'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'loop-mosaic'),
                'label_off' => esc_html__('No', 'loop-mosaic'),
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Animate cards as they scroll into view.', 'loop-mosaic'),
            ]
        );

        $this->add_control(
            'animation_type',
            [
                'label' => esc_html__('Animation Type', 'loop-mosaic'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fade-up',
                'options' => [
                    'fade-in' => esc_html__('Fade In', 'loop-mosaic'),
                    'fade-up' => esc_html__('Fade Up', 'loop-mosaic'),
                    'fade-down' => esc_html__('Fade Down', 'loop-mosaic'),
                    'fade-left' => esc_html__('Fade Left', 'loop-mosaic'),
                    'fade-right' => esc_html__('Fade Right', 'loop-mosaic'),
                    'slide-up' => esc_html__('Slide Up', 'loop-mosaic'),
                    'scale-in' => esc_html__('Scale In', 'loop-mosaic'),
                    'zoom-in' => esc_html__('Subtle Zoom', 'loop-mosaic'),
                    'blur-in' => esc_html__('Blur In (Premium)', 'loop-mosaic'),
                    'blur-up' => esc_html__('Blur Up (Premium)', 'loop-mosaic'),
                    '3d-flip-up' => esc_html__('3D Flip Up (Premium)', 'loop-mosaic'),
                    'skew-up' => esc_html__('Skew Up (Premium)', 'loop-mosaic'),
                    'reveal-left' => esc_html__('Reveal Left Wipe (Premium)', 'loop-mosaic'),
                ],
                'condition' => [
                    'enable_animations' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'animation_duration',
            [
                'label' => esc_html__('Animation Duration (ms)', 'loop-mosaic'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 2000,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 600,
                ],
                'condition' => [
                    'enable_animations' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'animation_stagger',
            [
                'label' => esc_html__('Stagger Delay (ms)', 'loop-mosaic'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 500,
                        'step' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 50,
                ],
                'description' => esc_html__('Delay between each card animating when multiple cards appear at once.', 'loop-mosaic'),
                'condition' => [
                    'enable_animations' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'disable_mobile_animations',
            [
                'label' => esc_html__('Disable on Mobile', 'loop-mosaic'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'loop-mosaic'),
                'label_off' => esc_html__('No', 'loop-mosaic'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Disable animations on devices under 768px for better performance.', 'loop-mosaic'),
                'condition' => [
                    'enable_animations' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_load_more_style_controls()
    {
        $this->start_controls_section(
            'section_style_load_more',
        [
            'label' => esc_html__('Load More Button', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_STYLE,
            'condition' => [
                'enable_infinite_scroll' => 'yes',
                'infinite_scroll_trigger' => 'button',
            ],
        ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
        [
            'name' => 'load_more_typography',
            'selector' => '{{WRAPPER}} .loopmosaic-load-more-btn',
        ]
        );

        $this->start_controls_tabs('tabs_load_more_style');

        // NORMAL STATE
        $this->start_controls_tab(
            'tab_load_more_normal',
        [
            'label' => esc_html__('Normal', 'loop-mosaic'),
        ]
        );

        $this->add_control(
            'load_more_text_color',
        [
            'label' => esc_html__('Text Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-load-more-btn' => 'color: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'load_more_bg_color',
        [
            'label' => esc_html__('Background Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'default' => '#8a2be2',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-load-more-btn' => 'background-color: {{VALUE}};',
            ],
        ]
        );

        $this->end_controls_tab();

        // HOVER STATE
        $this->start_controls_tab(
            'tab_load_more_hover',
        [
            'label' => esc_html__('Hover', 'loop-mosaic'),
        ]
        );

        $this->add_control(
            'load_more_text_color_hover',
        [
            'label' => esc_html__('Text Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-load-more-btn:hover' => 'color: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'load_more_bg_color_hover',
        [
            'label' => esc_html__('Background Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-load-more-btn:hover' => 'background-color: {{VALUE}};',
            ],
        ]
        );

        $this->add_control(
            'load_more_hover_animation',
        [
            'label' => esc_html__('Hover Animation', 'loop-mosaic'),
            'type' => Controls_Manager::HOVER_ANIMATION,
        ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
        [
            'name' => 'load_more_border',
            'selector' => '{{WRAPPER}} .loopmosaic-load-more-btn',
            'separator' => 'before',
        ]
        );

        $this->add_control(
            'load_more_border_radius',
        [
            'label' => esc_html__('Border Radius', 'loop-mosaic'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-load-more-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'load_more_padding',
        [
            'label' => esc_html__('Padding', 'loop-mosaic'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-load-more-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'load_more_margin',
        [
            'label' => esc_html__('Margin', 'loop-mosaic'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-load-more-btn-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'load_more_align',
        [
            'label' => esc_html__('Alignment', 'loop-mosaic'),
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                'left' => [
                    'title' => esc_html__('Left', 'loop-mosaic'),
                    'icon' => 'eicon-text-align-left',
                ],
                'center' => [
                    'title' => esc_html__('Center', 'loop-mosaic'),
                    'icon' => 'eicon-text-align-center',
                ],
                'right' => [
                    'title' => esc_html__('Right', 'loop-mosaic'),
                    'icon' => 'eicon-text-align-right',
                ],
            ],
            'default' => 'center',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-load-more-btn-wrapper' => 'text-align: {{VALUE}};',
            ],
        ]
        );

        $this->end_controls_section();
    }

    /**
     * Get JetPopups
     */
    private function get_jet_popups()
    {
        $popups = ['' => esc_html__('Select Popup', 'loop-mosaic')];

        $popup_posts = get_posts([
            'post_type' => 'jet-popup',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        foreach ($popup_posts as $popup) {
            $popups[$popup->ID] = $popup->post_title;
        }

        return $popups;
    }

    /**
     * Get Elementor Loop Item templates
     */
    private function get_elementor_loop_templates()
    {
        $templates = ['' => esc_html__('Select Template', 'loop-mosaic')];

        if (!class_exists('\Elementor\Plugin')) {
            return $templates;
        }

        // Get loop item templates
        $loop_templates = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'loop-item',
                ],
            ],
        ]);

        foreach ($loop_templates as $template) {
            $templates[$template->ID] = $template->post_title;
        }

        // Also get regular templates (for compatibility)
        $section_templates = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => ['section', 'page', 'container', 'single'],
                    'compare' => 'IN',
                ],
            ],
        ]);

        foreach ($section_templates as $template) {
            $type_label = 'Template';
            // Optional: Try to get specific type label if needed, or just append generic
            $templates[$template->ID] = $template->post_title . ' (' . $type_label . ')';
        }

        return $templates;
    }

    /**
     * Get available image sizes
     */
    private function get_image_sizes()
    {
        $sizes = [
            'thumbnail' => esc_html__('Thumbnail', 'loop-mosaic'),
            'medium' => esc_html__('Medium', 'loop-mosaic'),
            'large' => esc_html__('Large', 'loop-mosaic'),
            'full' => esc_html__('Full', 'loop-mosaic'),
        ];

        // Add custom sizes
        $additional_sizes = wp_get_additional_image_sizes();
        foreach ($additional_sizes as $size_name => $size_attrs) {
            $sizes[$size_name] = ucwords(str_replace(['-', '_'], ' ', $size_name));
        }

        return $sizes;
    }

    /**
     * Register No Posts Found Style Controls
     */
    private function register_no_posts_style_controls()
    {
        $this->start_controls_section(
            'section_no_posts_style',
        [
            'label' => esc_html__('No Posts Found Message', 'loop-mosaic'),
            'tab' => Controls_Manager::TAB_STYLE,
        ]
        );

        $this->add_control(
            'no_posts_color',
        [
            'label' => esc_html__('Color', 'loop-mosaic'),
            'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-no-posts' => 'color: {{VALUE}};',
            ],
        ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
        [
            'name' => 'no_posts_typography',
            'selector' => '{{WRAPPER}} .loopmosaic-no-posts',
        ]
        );

        $this->add_responsive_control(
            'no_posts_align',
        [
            'label' => esc_html__('Alignment', 'loop-mosaic'),
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                'left' => [
                    'title' => esc_html__('Left', 'loop-mosaic'),
                    'icon' => 'eicon-text-align-left',
                ],
                'center' => [
                    'title' => esc_html__('Center', 'loop-mosaic'),
                    'icon' => 'eicon-text-align-center',
                ],
                'right' => [
                    'title' => esc_html__('Right', 'loop-mosaic'),
                    'icon' => 'eicon-text-align-right',
                ],
            ],
            'default' => 'center',
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-no-posts' => 'text-align: {{VALUE}};',
            ],
        ]
        );

        $this->add_responsive_control(
            'no_posts_padding',
        [
            'label' => esc_html__('Padding', 'loop-mosaic'),
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors' => [
                '{{WRAPPER}} .loopmosaic-no-posts' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();

        // Build query args
        $orderby = $settings['orderby'];
        $order   = $settings['order'];
        
        // menu_order needs secondary sort by title and defaults to ASC
        if ('menu_order' === $orderby) {
            $orderby = 'menu_order title';
            $order   = ('DESC' === $order) ? 'ASC' : $order;
        }
        
        $args = [
            'post_type' => $settings['post_type'],
            'posts_per_page' => $settings['posts_per_page'],
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
        ];

        // Taxonomy filter (dynamic per post type)
        $post_type = $settings['post_type'];
        $taxonomy_key = 'taxonomy_' . $post_type;
        $selected_taxonomy = !empty($settings[$taxonomy_key]) ? $settings[$taxonomy_key] : '';
        $selected_terms = [];

        if (!empty($selected_taxonomy)) {
            $terms_key = 'taxonomy_terms_' . $post_type . '_' . $selected_taxonomy;
            $selected_terms = !empty($settings[$terms_key]) ? (array) $settings[$terms_key] : [];

            if (!empty($selected_terms)) {
                $args['tax_query'] = [
                    [
                        'taxonomy' => $selected_taxonomy,
                        'field' => 'slug',
                        'terms' => $selected_terms,
                    ],
                ];
            }
        }

        // Exclude Posts
        if ( ! empty( $settings['exclude_posts'] ) ) {
            $exclude_ids = array_map( 'intval', (array) $settings['exclude_posts'] );
            $exclude_ids = array_filter( $exclude_ids );
            if ( ! empty( $exclude_ids ) ) {
                $args['post__not_in'] = $exclude_ids;
            }
        }

        // Apply JetSmartFilters query ID
        $query_id = '';
        if (!empty($settings['enable_jsf']) && 'yes' === $settings['enable_jsf']) {
            $query_id = !empty($settings['jsf_query_id']) ? $settings['jsf_query_id'] : 'loopmosaic';
        }

        // Apply filters hook
        $args = apply_filters('loopmosaic/query/args', $args, $settings, $query_id);

        // Apply Relationship / Custom Elementor query hook.
        // Fires elementor/query/{id} — the same action PostRelation-Elementor,
        // JetEngine Relations, and any plugin using this Elementor standard
        // will hook into. They call $q->set('post__in', [...]) on the passed
        // WP_Query object; we read those vars back into $args before running
        // the real query. No DB round-trip for the mock object (no args = no query).
        if (!empty($settings['lm_rel_query_id'])) {

            $cq_id  = sanitize_key($settings['lm_rel_query_id']);
            $mock_q = new \WP_Query();
            do_action("elementor/query/{$cq_id}", $mock_q);

            if (isset($mock_q->query_vars['post__in'])) {
                $args['post__in'] = $mock_q->query_vars['post__in'];
            }
            if (!empty($mock_q->query_vars['orderby'])) {
                $args['orderby'] = $mock_q->query_vars['orderby'];
            }
            if (!empty($mock_q->query_vars['order'])) {
                $args['order'] = $mock_q->query_vars['order'];
            }
        }

        $query = new \WP_Query($args);

        // Grid classes
        $grid_classes = [
            'loopmosaic-grid',
            'columns-' . $settings['columns'],
            'pattern-' . $settings['pattern'],
        ];

        if (in_array($settings['pattern'], ['metro', 'highlight', 'classic']) && !empty($settings['highlight_item_height']) && 'half' === $settings['highlight_item_height']) {
            $grid_classes[] = 'highlight-height-half';
        }

        // Masonry Logic
        if (!empty($settings['layout_mode']) && 'masonry_js' === $settings['layout_mode']) {
            $grid_classes[] = 'loopmosaic-masonry';
            $this->add_render_attribute('jsf_grid_container', 'data-layout-mode', 'masonry');

            // Dynamic CSS for Column Rules
            if (!empty($settings['column_rules'])) {
                $css = '';
                $cols = intval($settings['columns']);
                $widget_id = $this->get_id();

                foreach ($settings['column_rules'] as $rule) {
                    $target = intval($rule['column_target']);
                    $height = $rule['column_height']['size'] . $rule['column_height']['unit'];

                    // nth-of-type logic: An+B where A=cols, B=target
                    $nth = "{$cols}n+{$target}";
                    $css .= ".elementor-element-{$widget_id} .loopmosaic-masonry .loopmosaic-item:nth-of-type({$nth}) { height: {$height} !important; } ";
                }

                if (!empty($css)) {
                    echo '<style>' . $css . '</style>';
                }
            }
        }

        // Image height mode class
        $image_height_mode = !empty($settings['image_height_mode']) ? $settings['image_height_mode'] : 'fill';
        $grid_classes[] = 'img-mode-' . $image_height_mode;

        // Template source
        $template_source = !empty($settings['template_source']) ? $settings['template_source'] : 'default';

        // Prepare Render Attributes for the Grid Container
        // We use a specific key 'jsf_grid_container' so our Compat class can target it!
        $this->add_render_attribute('jsf_grid_container', 'class', $grid_classes);

        // Animation attributes
        if (!empty($settings['enable_animations']) && 'yes' === $settings['enable_animations']) {
            $anim_settings = [
                'type' => $settings['animation_type'] ?? 'fade-up',
                'duration' => $settings['animation_duration']['size'] ?? 600,
                'stagger' => $settings['animation_stagger']['size'] ?? 50,
                'disableMobile' => !empty($settings['disable_mobile_animations']) && 'yes' === $settings['disable_mobile_animations'],
            ];
            $this->add_render_attribute('jsf_grid_container', 'data-lm-animations', wp_json_encode($anim_settings));
        }

        // JetSmartFilters data attributes
        $jsf_settings = []; // Define it here to use for infinite scroll too

        // Always prepare settings if JSF OR Infinite Scroll is used
        $is_infinite_scroll = !empty($settings['enable_infinite_scroll']) && 'yes' === $settings['enable_infinite_scroll'];

        if ($query_id || $is_infinite_scroll) {
            // Prepare settings for AJAX
            $jsf_settings = [
                'post_type' => $settings['post_type'],
                'posts_per_page' => $settings['posts_per_page'],
                'orderby' => $settings['orderby'],
                'order' => $settings['order'],
                'taxonomy' => $selected_taxonomy,
                'taxonomy_terms' => !empty($selected_terms) ? implode(',', $selected_terms) : '',
                'template_source' => $template_source,
                'card_design_style' => $settings['card_design_style'] ?? 'overlay',
                'show_title' => $settings['show_title'] ?? 'yes',
                'show_excerpt' => $settings['show_excerpt'] ?? 'yes',
                'show_category' => $settings['show_category'] ?? 'yes',
                'excerpt_length' => $settings['excerpt_length'] ?? 20,
                'show_floating_card_icon' => $settings['show_floating_card_icon'] ?? 'yes',
                'floating_card_icon' => $settings['floating_card_icon'] ?? [],
                'floating_card_icon_bg_color' => $settings['floating_card_icon_bg_color'] ?? '#d62f67',
                'floating_card_icon_color' => $settings['floating_card_icon_color'] ?? '#ffffff',
                'floating_card_icon_items' => $settings['floating_card_icon_items'] ?? [],
                'show_floating_card_arrow' => $settings['show_floating_card_arrow'] ?? 'yes',
                'color_overlay' => $settings['color_overlay'] ?? '',
                'infinite_scroll_trigger' => $settings['infinite_scroll_trigger'] ?? 'scroll',
                'use_custom_overlay_colors' => $settings['use_custom_overlay_colors'] ?? '',
                'overlay_hover_effect' => $settings['overlay_hover_effect'] ?? 'none',
                'overlay_hover_opacity_value' => $settings['overlay_hover_opacity_value']['size'] ?? 0.5,
                'custom_overlay_colors' => $settings['custom_overlay_colors'] ?? [],
                'overlay_opacity' => $settings['overlay_opacity']['size'] ?? 0.85,
                'image_size' => $settings['image_size'] ?? 'large',
                'card_content_v_align' => $settings['card_content_v_align'] ?? 'flex-end',
                'card_content_h_align' => $settings['card_content_h_align'] ?? 'flex-start',
                'title_align' => $settings['title_align'] ?? '',
                'click_action' => $settings['click_action'] ?? 'permalink',
                'click_popup_id' => $settings['click_popup_id'] ?? '',
                'click_jet_popup_id' => $settings['click_jet_popup_id'] ?? '',
                // Modal Specifics
                'modal_use_custom_template' => $settings['modal_use_custom_template'] ?? '',
                'modal_auto_template' => $settings['modal_auto_template'] ?? '',
                'modal_template_id' => $settings['modal_template_id'] ?? '',
                // Exclude Posts
                'exclude_posts' => $settings['exclude_posts'] ?? [],
            ];

            // Add template IDs if needed
            if ('elementor_loop' === $template_source && !empty($settings['elementor_loop_template'])) {
                $jsf_settings['elementor_loop_template'] = $settings['elementor_loop_template'];
            }
            if ('jetengine' === $template_source && !empty($settings['jetengine_listing'])) {
                $jsf_settings['jetengine_listing'] = $settings['jetengine_listing'];
            }
        }

        if ($query_id) {
            $this->add_render_attribute('jsf_grid_container', 'data-query-id', $query_id);
            $this->add_render_attribute('jsf_grid_container', 'data-provider', 'loop-mosaic');
            $this->add_render_attribute('jsf_grid_container', 'data-settings', wp_json_encode($jsf_settings));
        }

        // Add Infinite Scroll Attributes
        if ($is_infinite_scroll) {
            $this->add_render_attribute('jsf_grid_container', 'data-infinite-scroll', 'true');
            $this->add_render_attribute('jsf_grid_container', 'data-max-pages', $query->max_num_pages);
            $this->add_render_attribute('jsf_grid_container', 'data-paged', '1');

            // Ensure settings are present even if JSF is off
            if (!$query_id) {
                $this->add_render_attribute('jsf_grid_container', 'data-settings', wp_json_encode($jsf_settings));
            }
        }

        // ── Carousel render ───────────────────────────────────────────────────
        if (!empty($settings['layout_mode']) && 'carousel' === $settings['layout_mode']) {
            $this->render_carousel($settings, $query);
            return;
        }

        echo '<div ' . $this->get_render_attribute_string('jsf_grid_container') . '>';

        if ($query->have_posts()) {
            $index = 0;

            while ($query->have_posts()) {
                $query->the_post();
                // Single source of truth for item markup (see includes/class-renderer.php).
                echo \LoopMosaic_Renderer::render_item($settings, get_the_ID(), $index);
                $index++;
            }

            wp_reset_postdata();
        }
        else {
            echo \LoopMosaic_Renderer::render_no_posts($settings);
        }

        echo '</div>'; // End loopmosaic-grid

        // Load More Button Output (Outside Grid)
        if (!empty($settings['enable_infinite_scroll']) && 'yes' === $settings['enable_infinite_scroll'] &&
        isset($settings['infinite_scroll_trigger']) && 'button' === $settings['infinite_scroll_trigger']) {

            $btn_text = !empty($settings['load_more_button_text']) ? $settings['load_more_button_text'] : esc_html__('Load More', 'loop-mosaic');

            echo '<div class="loopmosaic-load-more-btn-wrapper">';
            echo '<button class="loopmosaic-load-more-btn" data-widget-id="' . esc_attr($this->get_id()) . '">';
            echo '<span class="loopmosaic-load-more-text">' . esc_html($btn_text) . '</span>';
            echo '<span class="loopmosaic-load-more-spinner"></span>';
            echo '</button>';
            echo '</div>';
        }
    }

    /**
     * Render the carousel (Swiper) layout.
     */
    private function render_carousel(array $settings, \WP_Query $query)
    {
        $carousel_id = 'lm-carousel-' . esc_attr($this->get_id());

        $carousel_cfg = [
            'loop'          => !empty($settings['carousel_loop']) && 'yes' === $settings['carousel_loop'],
            'speed'         => intval($settings['carousel_speed'] ?? 600),
            'autoplay'      => !empty($settings['carousel_autoplay']) && 'yes' === $settings['carousel_autoplay'],
            'autoplaySpeed' => intval($settings['carousel_autoplay_speed'] ?? 4000),
            'dots'          => !empty($settings['carousel_dots']) && 'yes' === $settings['carousel_dots'],
        ];

        $has_stack = empty($settings['carousel_stack']) || 'yes' === $settings['carousel_stack'];

        echo '<div class="loopmosaic-carousel-wrap" id="' . esc_attr($carousel_id) . '" data-carousel="' . esc_attr(wp_json_encode($carousel_cfg)) . '">';

        // Stage = card + side navigation. Its height drives the vertical
        // centering of the arrows, so pagination (placed below) won't shift them.
        echo '<div class="loopmosaic-carousel-stage">';

        // Stacked card peek — real DOM element (not ::before) so Swiper's
        // composited transition layers don't cause pseudo-element glitches.
        if ($has_stack) {
            echo '<div class="lm-stack-card" aria-hidden="true"></div>';
        }

        echo '<div class="swiper loopmosaic-swiper">';
        echo '<div class="swiper-wrapper">';

        if ($query->have_posts()) {
            $index = 0;

            while ($query->have_posts()) {
                $query->the_post();
                echo '<div class="swiper-slide">';
                echo \LoopMosaic_Renderer::render_item($settings, get_the_ID(), $index);
                echo '</div>';
                $index++;
            }

            wp_reset_postdata();
        } else {
            echo '<div class="swiper-slide">';
            echo \LoopMosaic_Renderer::render_no_posts($settings);
            echo '</div>';
        }

        echo '</div>'; // .swiper-wrapper

        echo '</div>'; // .loopmosaic-swiper

        // Navigation — right side, vertical (anchored to the stage = card height)
        echo '<div class="loopmosaic-carousel-nav">';

        echo '<button class="lm-nav-btn lm-nav-prev" aria-label="' . esc_attr__('Previous slide', 'loop-mosaic') . '">';
        echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="18 15 12 9 6 15"></polyline></svg>';
        echo '</button>';

        echo '<button class="lm-nav-btn lm-nav-next" aria-label="' . esc_attr__('Next slide', 'loop-mosaic') . '">';
        echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>';
        echo '</button>';

        echo '</div>'; // .loopmosaic-carousel-nav

        echo '</div>'; // .loopmosaic-carousel-stage

        // Pagination switches — rendered BELOW the card (outside the stage)
        // so they never overlap the slide content / photo.
        if ($carousel_cfg['dots']) {
            echo '<div class="swiper-pagination"></div>';
        }

        echo '</div>'; // .loopmosaic-carousel-wrap
    }
}
