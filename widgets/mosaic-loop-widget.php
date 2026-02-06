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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mosaic_Loop_Widget extends Widget_Base {

    public function get_name() {
        return 'loopmosaic-grid';
    }

    public function get_title() {
        return esc_html__( 'LoopMosaic Grid', 'loop-mosaic' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'loop-mosaic', 'general' ];
    }

    public function get_keywords() {
        return [ 'loop', 'mosaic', 'grid', 'masonry', 'jetengine', 'listing' ];
    }

    public function get_style_depends() {
        return [ 'loop-mosaic-grid' ];
    }

    public function get_script_depends() {
        return [ 'loop-mosaic-filters' ];
    }

    protected function register_controls() {
        // === CONTENT TAB ===
        
        // Template Source Section
        $this->start_controls_section(
            'section_template',
            [
                'label' => esc_html__( 'Template Source', 'loop-mosaic' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $template_options = [
            'default' => esc_html__( 'Default Card', 'loop-mosaic' ),
        ];

        // Add Elementor Loop option
        if ( class_exists( '\Elementor\Plugin' ) ) {
            $template_options['elementor_loop'] = esc_html__( 'Elementor Loop Item', 'loop-mosaic' );
        }

        // Add JetEngine option
        if ( class_exists( 'Jet_Engine' ) ) {
            $template_options['jetengine'] = esc_html__( 'JetEngine Listing', 'loop-mosaic' );
        }

        $this->add_control(
            'template_source',
            [
                'label'   => esc_html__( 'Template Source', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => $template_options,
                'default' => 'default',
            ]
        );

        // Elementor Loop Item Template
        $this->add_control(
            'elementor_loop_template',
            [
                'label'       => esc_html__( 'Loop Template', 'loop-mosaic' ),
                'type'        => Controls_Manager::SELECT2,
                'options'     => $this->get_elementor_loop_templates(),
                'default'     => '',
                'condition'   => [
                    'template_source' => 'elementor_loop',
                ],
                'description' => esc_html__( 'Select an Elementor Loop Item template', 'loop-mosaic' ),
            ]
        );

        // JetEngine Listing Template
        if ( class_exists( 'Jet_Engine' ) ) {
            $this->add_control(
                'jetengine_listing',
                [
                    'label'       => esc_html__( 'Listing Template', 'loop-mosaic' ),
                    'type'        => Controls_Manager::SELECT2,
                    'options'     => $this->get_jetengine_listings(),
                    'default'     => '',
                    'condition'   => [
                        'template_source' => 'jetengine',
                    ],
                    'description' => esc_html__( 'Select a JetEngine listing template', 'loop-mosaic' ),
                ]
            );
        }

        $this->end_controls_section();

        // Query Section
        $this->start_controls_section(
            'section_query',
            [
                'label' => esc_html__( 'Query', 'loop-mosaic' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'post_type',
            [
                'label'   => esc_html__( 'Post Type', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => $this->get_post_types(),
                'default' => 'post',
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label'   => esc_html__( 'Posts Per Page', 'loop-mosaic' ),
                'type'    => Controls_Manager::NUMBER,
                'min'     => 1,
                'max'     => 50,
                'default' => 9,
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'   => esc_html__( 'Order By', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'date'          => esc_html__( 'Date', 'loop-mosaic' ),
                    'title'         => esc_html__( 'Title', 'loop-mosaic' ),
                    'menu_order'    => esc_html__( 'Menu Order', 'loop-mosaic' ),
                    'rand'          => esc_html__( 'Random', 'loop-mosaic' ),
                    'comment_count' => esc_html__( 'Comment Count', 'loop-mosaic' ),
                ],
                'default' => 'date',
            ]
        );

        $this->add_control(
            'order',
            [
                'label'   => esc_html__( 'Order', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'DESC' => esc_html__( 'Descending', 'loop-mosaic' ),
                    'ASC'  => esc_html__( 'Ascending', 'loop-mosaic' ),
                ],
                'default' => 'DESC',
            ]
        );

        // Taxonomy Filter
        $this->add_control(
            'taxonomy_heading',
            [
                'label'     => esc_html__( 'Taxonomy Filter', 'loop-mosaic' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'taxonomy',
            [
                'label'   => esc_html__( 'Taxonomy', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => $this->get_taxonomies(),
                'default' => '',
            ]
        );

        $this->add_control(
            'taxonomy_terms',
            [
                'label'       => esc_html__( 'Terms', 'loop-mosaic' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__( 'Enter term slugs, comma separated', 'loop-mosaic' ),
                'condition'   => [
                    'taxonomy!' => '',
                ],
            ]
        );

        $this->add_control(
            'enable_infinite_scroll',
            [
                'label'        => esc_html__( 'Enable Infinite Scroll', 'loop-mosaic' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'loop-mosaic' ),
                'label_off'    => esc_html__( 'No', 'loop-mosaic' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'separator'    => 'before',
            ]
        );

        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section(
            'section_layout',
            [
                'label' => esc_html__( 'Layout', 'loop-mosaic' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'   => esc_html__( 'Columns', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'default' => '4',
            ]
        );

        $this->add_control(
            'pattern',
            [
                'label'   => esc_html__( 'Mosaic Pattern', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'classic'   => esc_html__( 'Classic Mosaic', 'loop-mosaic' ),
                    'metro'     => esc_html__( 'Metro Style', 'loop-mosaic' ),
                    'masonry'   => esc_html__( 'Masonry', 'loop-mosaic' ),
                    'highlight' => esc_html__( 'Highlight First', 'loop-mosaic' ),
                    'uniform'   => esc_html__( 'Uniform Grid', 'loop-mosaic' ),
                ],
                'default' => 'classic',
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label'      => esc_html__( 'Gap', 'loop-mosaic' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default'    => [
                    'size' => 15,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .loopmosaic-grid' => '--lm-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'min_height',
            [
                'label'      => esc_html__( 'Card Min Height', 'loop-mosaic' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range'      => [
                    'px' => [
                        'min' => 100,
                        'max' => 500,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 50,
                    ],
                ],
                'default'    => [
                    'size' => 200,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .loopmosaic-item' => '--lm-min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Image Settings Section
        $this->start_controls_section(
            'section_image_settings',
            [
                'label'     => esc_html__( 'Image Settings', 'loop-mosaic' ),
                'tab'       => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'image_fit',
            [
                'label'   => esc_html__( 'Image Fit', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'cover'      => esc_html__( 'Cover', 'loop-mosaic' ),
                    'contain'    => esc_html__( 'Contain', 'loop-mosaic' ),
                    'fill'       => esc_html__( 'Fill', 'loop-mosaic' ),
                    'none'       => esc_html__( 'None (Auto)', 'loop-mosaic' ),
                    'scale-down' => esc_html__( 'Scale Down', 'loop-mosaic' ),
                ],
                'default'   => 'cover',
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__image' => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'image_position',
            [
                'label'   => esc_html__( 'Image Position', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'center center' => esc_html__( 'Center Center', 'loop-mosaic' ),
                    'center top'    => esc_html__( 'Center Top', 'loop-mosaic' ),
                    'center bottom' => esc_html__( 'Center Bottom', 'loop-mosaic' ),
                    'left center'   => esc_html__( 'Left Center', 'loop-mosaic' ),
                    'left top'      => esc_html__( 'Left Top', 'loop-mosaic' ),
                    'left bottom'   => esc_html__( 'Left Bottom', 'loop-mosaic' ),
                    'right center'  => esc_html__( 'Right Center', 'loop-mosaic' ),
                    'right top'     => esc_html__( 'Right Top', 'loop-mosaic' ),
                    'right bottom'  => esc_html__( 'Right Bottom', 'loop-mosaic' ),
                ],
                'default'   => 'center center',
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__image' => 'object-position: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label'   => esc_html__( 'Image Size', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => $this->get_image_sizes(),
                'default' => 'large',
            ]
        );

        $this->add_control(
            'image_height_mode',
            [
                'label'   => esc_html__( 'Image Height Mode', 'loop-mosaic' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'auto'  => esc_html__( 'Auto (Natural)', 'loop-mosaic' ),
                    'fill'  => esc_html__( 'Fill Card', 'loop-mosaic' ),
                    'fixed' => esc_html__( 'Fixed Height', 'loop-mosaic' ),
                ],
                'default' => 'fill',
            ]
        );

        $this->add_responsive_control(
            'image_height',
            [
                'label'      => esc_html__( 'Image Height', 'loop-mosaic' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'vh' ],
                'range'      => [
                    'px' => [
                        'min' => 50,
                        'max' => 600,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'size' => 100,
                    'unit' => '%',
                ],
                'condition'  => [
                    'image_height_mode' => 'fixed',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .loopmosaic-item__image' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Content Display Section
        $this->start_controls_section(
            'section_content_display',
            [
                'label'     => esc_html__( 'Content Display', 'loop-mosaic' ),
                'tab'       => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'template_source' => 'default',
                ],
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label'        => esc_html__( 'Show Title', 'loop-mosaic' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'loop-mosaic' ),
                'label_off'    => esc_html__( 'No', 'loop-mosaic' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_excerpt',
            [
                'label'        => esc_html__( 'Show Excerpt', 'loop-mosaic' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'loop-mosaic' ),
                'label_off'    => esc_html__( 'No', 'loop-mosaic' ),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        $this->add_control(
            'excerpt_length',
            [
                'label'     => esc_html__( 'Excerpt Length', 'loop-mosaic' ),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 10,
                'max'       => 100,
                'default'   => 20,
                'condition' => [
                    'show_excerpt' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_category',
            [
                'label'        => esc_html__( 'Show Category', 'loop-mosaic' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'loop-mosaic' ),
                'label_off'    => esc_html__( 'No', 'loop-mosaic' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'color_overlay',
            [
                'label'        => esc_html__( 'Color Overlay', 'loop-mosaic' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'loop-mosaic' ),
                'label_off'    => esc_html__( 'No', 'loop-mosaic' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => esc_html__( 'Apply colorful overlays to cards', 'loop-mosaic' ),
            ]
        );

        $this->end_controls_section();

        // JetSmartFilters Section
        if ( class_exists( 'Jet_Smart_Filters' ) ) {
            $this->start_controls_section(
                'section_jetsmartfilters',
                [
                    'label' => esc_html__( 'JetSmartFilters', 'loop-mosaic' ),
                    'tab'   => Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'enable_jsf',
                [
                    'label'        => esc_html__( 'Enable JetSmartFilters', 'loop-mosaic' ),
                    'type'         => Controls_Manager::SWITCHER,
                    'label_on'     => esc_html__( 'Yes', 'loop-mosaic' ),
                    'label_off'    => esc_html__( 'No', 'loop-mosaic' ),
                    'return_value' => 'yes',
                    'default'      => 'no',
                ]
            );

            $this->add_control(
                'jsf_query_id',
                [
                    'label'       => esc_html__( 'Query ID', 'loop-mosaic' ),
                    'type'        => Controls_Manager::TEXT,
                    'default'     => 'loopmosaic',
                    'condition'   => [
                        'enable_jsf' => 'yes',
                    ],
                    'description' => esc_html__( 'Use this ID in JetSmartFilters Query ID field', 'loop-mosaic' ),
                ]
            );

            $this->end_controls_section();
        }

        // Interaction Section
        $this->start_controls_section(
            'section_interaction',
            [
                'label' => esc_html__( 'Interaction', 'loop-mosaic' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'click_action',
            [
                'label'   => esc_html__( 'Click Action', 'loop-mosaic' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'permalink',
                'options' => [
                    'permalink' => esc_html__( 'Open Post (Permalink)', 'loop-mosaic' ),
                    'modal'     => esc_html__( 'Open Built-in Modal', 'loop-mosaic' ),
                    'none'      => esc_html__( 'None', 'loop-mosaic' ),
                ],
            ]
        );

        $this->add_control(
            'show_gallery_in_modal',
            [
                'label'     => esc_html__( 'Show Gallery in Modal', 'loop-mosaic' ),
                'type'      => \Elementor\Controls_Manager::SWITCHER,
                'default'   => 'yes',
                'condition' => [
                    'click_action' => 'modal',
                ],
            ]
        );

        $this->add_control(
            'modal_use_custom_template',
            [
                'label'     => esc_html__( 'Use Custom Template', 'loop-mosaic' ),
                'type'      => \Elementor\Controls_Manager::SWITCHER,
                'default'   => 'no',
                'condition' => [
                    'click_action' => 'modal',
                ],
            ]
        );

        $this->add_control(
            'modal_auto_template',
            [
                'label'     => esc_html__( 'Auto-Use Assigned Template', 'loop-mosaic' ),
                'type'      => \Elementor\Controls_Manager::SWITCHER,
                'default'   => 'no',
                'description' => esc_html__( 'Automatically use the assigned Single Post template if available (Elementor Pro).', 'loop-mosaic' ),
                'condition' => [
                    'click_action'              => 'modal',
                    'modal_use_custom_template' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'modal_template_id',
            [
                'label'       => esc_html__( 'Select Template', 'loop-mosaic' ),
                'type'        => \Elementor\Controls_Manager::SELECT2,
                'options'     => $this->get_elementor_loop_templates(),
                'default'     => '',
                'condition'   => [
                    'click_action'              => 'modal',
                    'modal_use_custom_template' => 'yes',
                    'modal_auto_template'       => '',
                ],
            ]
        );

        $this->end_controls_section();

        // Modal Style Section
        $this->start_controls_section(
            'section_modal_style',
            [
                'label'     => esc_html__( 'Modal Style', 'loop-mosaic' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'click_action' => 'modal',
                ],
            ]
        );

        $this->add_responsive_control(
            'modal_width',
            [
                'label'      => esc_html__( 'Modal Width', 'loop-mosaic' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'vw' ],
                'range'      => [
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
                'default'    => [
                    'size' => 90,
                    'unit' => '%',
                ],
                'selectors'  => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'modal_max_width',
            [
                'label'      => esc_html__( 'Modal Max Width', 'loop-mosaic' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'vw' ],
                'range'      => [
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
                'default'    => [
                    'size' => 900,
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'modal_height',
            [
                'label'      => esc_html__( 'Modal Height', 'loop-mosaic' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'vh' ],
                'range'      => [
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
                'default'    => [
                    'size' => 'auto',
                    'unit' => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'modal_max_height',
            [
                'label'      => esc_html__( 'Modal Max Height', 'loop-mosaic' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%', 'vh' ],
                'range'      => [
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
                'default'    => [
                    'size' => 90,
                    'unit' => 'vh',
                ],
                'selectors'  => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'max-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'modal_padding',
            [
                'label'      => esc_html__( 'Modal Padding', 'loop-mosaic' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 30,
                    'right'  => 30,
                    'bottom' => 30,
                    'left'   => 30,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-content, #loopmosaic-modal .loopmosaic-modal-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'modal_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'loop-mosaic' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'default'    => [
                    'top'    => 12,
                    'right'  => 12,
                    'bottom' => 12,
                    'left'   => 12,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_background_color',
            [
                'label'     => esc_html__( 'Background Color', 'loop-mosaic' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_overlay_color',
            [
                'label'     => esc_html__( 'Overlay Color', 'loop-mosaic' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(0,0,0,0.7)',
                'selectors' => [
                    '{{WRAPPER}} ~ #loopmosaic-modal.loopmosaic-modal-overlay, #loopmosaic-modal.loopmosaic-modal-overlay' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'modal_box_shadow',
                'label'    => esc_html__( 'Box Shadow', 'loop-mosaic' ),
                'selector' => '{{WRAPPER}} ~ #loopmosaic-modal .loopmosaic-modal-container, #loopmosaic-modal .loopmosaic-modal-container',
            ]
        );

        $this->end_controls_section();

        // === STYLE TAB ===

        // Card Styling Section
        $this->start_controls_section(
            'section_card_style',
            [
                'label' => esc_html__( 'Card', 'loop-mosaic' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label'      => esc_html__( 'Padding', 'loop-mosaic' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default'    => [
                    'top'    => 20,
                    'right'  => 20,
                    'bottom' => 20,
                    'left'   => 20,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .loopmosaic-item__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'loop-mosaic' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'default'    => [
                    'top'    => 8,
                    'right'  => 8,
                    'bottom' => 8,
                    'left'   => 8,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .loopmosaic-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .loopmosaic-item',
            ]
        );

        $this->add_control(
            'overlay_color',
            [
                'label'     => esc_html__( 'Overlay Color', 'loop-mosaic' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__inner' => 'background: linear-gradient(to top, {{VALUE}} 0%, transparent 60%);',
                ],
            ]
        );

        $this->add_control(
            'card_content_v_align',
            [
                'label'   => esc_html__( 'Vertical Alignment', 'loop-mosaic' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__( 'Top', 'loop-mosaic' ),
                        'icon'  => 'eicon-align-start-v',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'loop-mosaic' ),
                        'icon'  => 'eicon-align-center-v',
                    ],
                    'flex-end' => [
                        'title' => esc_html__( 'Bottom', 'loop-mosaic' ),
                        'icon'  => 'eicon-align-end-v',
                    ],
                ],
                'default'   => 'flex-end',
                'toggle'    => true,
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__inner' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_content_h_align',
            [
                'label'   => esc_html__( 'Horizontal Alignment', 'loop-mosaic' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__( 'Left', 'loop-mosaic' ),
                        'icon'  => 'eicon-align-start-h',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'loop-mosaic' ),
                        'icon'  => 'eicon-align-center-h',
                    ],
                    'flex-end' => [
                        'title' => esc_html__( 'Right', 'loop-mosaic' ),
                        'icon'  => 'eicon-align-end-h',
                    ],
                ],
                'default'   => 'flex-start',
                'toggle'    => true,
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
                'label' => esc_html__( 'Title', 'loop-mosaic' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => esc_html__( 'Color', 'loop-mosaic' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .loopmosaic-item__title',
            ]
        );

        $this->add_responsive_control(
            'title_align',
            [
                'label'   => esc_html__( 'Alignment', 'loop-mosaic' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__( 'Left', 'loop-mosaic' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'loop-mosaic' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__( 'Right', 'loop-mosaic' ),
                        'icon'  => 'eicon-text-align-right',
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
                'label' => esc_html__( 'Category', 'loop-mosaic' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'category_bg_color',
            [
                'label'     => esc_html__( 'Background Color', 'loop-mosaic' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(255,255,255,0.2)',
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__category' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'category_text_color',
            [
                'label'     => esc_html__( 'Text Color', 'loop-mosaic' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .loopmosaic-item__category' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'category_typography',
                'selector' => '{{WRAPPER}} .loopmosaic-item__category',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get post types
     */
    private function get_post_types() {
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        $options    = [];

        foreach ( $post_types as $post_type ) {
            if ( 'attachment' !== $post_type->name ) {
                $options[ $post_type->name ] = $post_type->label;
            }
        }

        return $options;
    }

    /**
     * Get taxonomies
     */
    private function get_taxonomies() {
        $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
        $options    = [ '' => esc_html__( 'None', 'loop-mosaic' ) ];

        foreach ( $taxonomies as $taxonomy ) {
            $options[ $taxonomy->name ] = $taxonomy->label;
        }

        return $options;
    }

    /**
     * Get JetEngine listing templates
     */
    private function get_jetengine_listings() {
        $listings = [ '' => esc_html__( 'Select Template', 'loop-mosaic' ) ];

        if ( ! class_exists( 'Jet_Engine' ) ) {
            return $listings;
        }

        $listing_posts = get_posts( [
            'post_type'      => 'jet-engine',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ] );

        foreach ( $listing_posts as $listing ) {
            $listings[ $listing->ID ] = $listing->post_title;
        }

        return $listings;
    }

    /**
     * Get Elementor Popup templates
     */
    private function get_elementor_popups() {
        $popups = [ '' => esc_html__( 'Select Popup', 'loop-mosaic' ) ];

        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            return $popups;
        }

        $popup_posts = get_posts( [
            'post_type'      => 'elementor_library',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_elementor_template_type',
                    'value' => 'popup',
                ],
            ],
        ] );

        foreach ( $popup_posts as $popup ) {
            $popups[ $popup->ID ] = $popup->post_title;
        }

        return $popups;
    }

    /**
     * Get JetPopups
     */
    private function get_jet_popups() {
        $popups = [ '' => esc_html__( 'Select Popup', 'loop-mosaic' ) ];

        $popup_posts = get_posts( [
            'post_type'      => 'jet-popup',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ] );

        foreach ( $popup_posts as $popup ) {
            $popups[ $popup->ID ] = $popup->post_title;
        }

        return $popups;
    }

    /**
     * Get Elementor Loop Item templates
     */
    private function get_elementor_loop_templates() {
        $templates = [ '' => esc_html__( 'Select Template', 'loop-mosaic' ) ];

        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            return $templates;
        }

        // Get loop item templates
        $loop_templates = get_posts( [
            'post_type'      => 'elementor_library',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_elementor_template_type',
                    'value' => 'loop-item',
                ],
            ],
        ] );

        foreach ( $loop_templates as $template ) {
            $templates[ $template->ID ] = $template->post_title;
        }

        // Also get regular templates (for compatibility)
        $section_templates = get_posts( [
            'post_type'      => 'elementor_library',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_elementor_template_type',
                    'value'   => [ 'section', 'page', 'container', 'single' ],
                    'compare' => 'IN',
                ],
            ],
        ] );

        foreach ( $section_templates as $template ) {
            $type_label = 'Template';
            // Optional: Try to get specific type label if needed, or just append generic
            $templates[ $template->ID ] = $template->post_title . ' (' . $type_label . ')';
        }

        return $templates;
    }

    /**
     * Get available image sizes
     */
    private function get_image_sizes() {
        $sizes = [
            'thumbnail' => esc_html__( 'Thumbnail', 'loop-mosaic' ),
            'medium'    => esc_html__( 'Medium', 'loop-mosaic' ),
            'large'     => esc_html__( 'Large', 'loop-mosaic' ),
            'full'      => esc_html__( 'Full', 'loop-mosaic' ),
        ];

        // Add custom sizes
        $additional_sizes = wp_get_additional_image_sizes();
        foreach ( $additional_sizes as $size_name => $size_attrs ) {
            $sizes[ $size_name ] = ucwords( str_replace( [ '-', '_' ], ' ', $size_name ) );
        }

        return $sizes;
    }

    /**
     * Get color overlay class
     */
    private function get_overlay_class( $index ) {
        $colors = [ 'purple', 'teal', 'gold', 'coral', 'cyan', 'green' ];
        return 'overlay-' . $colors[ $index % count( $colors ) ];
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        // Build query args
        $args = [
            'post_type'      => $settings['post_type'],
            'posts_per_page' => $settings['posts_per_page'],
            'orderby'        => $settings['orderby'],
            'order'          => $settings['order'],
            'post_status'    => 'publish',
        ];

        // Taxonomy filter
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

        // Apply JetSmartFilters query ID
        $query_id = '';
        if ( ! empty( $settings['enable_jsf'] ) && 'yes' === $settings['enable_jsf'] ) {
            $query_id = ! empty( $settings['jsf_query_id'] ) ? $settings['jsf_query_id'] : 'loopmosaic';
        }

        // Apply filters hook
        $args = apply_filters( 'loopmosaic/query/args', $args, $settings, $query_id );

        $query = new \WP_Query( $args );

        // Grid classes
        $grid_classes = [
            'loopmosaic-grid',
            'columns-' . $settings['columns'],
            'pattern-' . $settings['pattern'],
        ];

        // Image height mode class
        $image_height_mode = ! empty( $settings['image_height_mode'] ) ? $settings['image_height_mode'] : 'fill';
        $grid_classes[] = 'img-mode-' . $image_height_mode;

        // Template source
        $template_source = ! empty( $settings['template_source'] ) ? $settings['template_source'] : 'default';

        // Prepare Render Attributes for the Grid Container
        // We use a specific key 'jsf_grid_container' so our Compat class can target it!
        $this->add_render_attribute( 'jsf_grid_container', 'class', $grid_classes );

        // JetSmartFilters data attributes
        $jsf_settings = []; // Define it here to use for infinite scroll too
        
        // Always prepare settings if JSF OR Infinite Scroll is used
        $is_infinite_scroll = ! empty( $settings['enable_infinite_scroll'] ) && 'yes' === $settings['enable_infinite_scroll'];

        if ( $query_id || $is_infinite_scroll ) {
            // Prepare settings for AJAX
            $jsf_settings = [
                'post_type'       => $settings['post_type'],
                'posts_per_page'  => $settings['posts_per_page'],
                'orderby'         => $settings['orderby'],
                'order'           => $settings['order'],
                'taxonomy'        => $settings['taxonomy'] ?? '',
                'taxonomy_terms'  => $settings['taxonomy_terms'] ?? '',
                'template_source' => $template_source,
                'show_title'      => $settings['show_title'] ?? 'yes',
                'show_excerpt'    => $settings['show_excerpt'] ?? 'yes',
                'show_category'   => $settings['show_category'] ?? 'yes',
                'excerpt_length'  => $settings['excerpt_length'] ?? 20,
                'color_overlay'   => $settings['color_overlay'] ?? '',
                'image_size'      => $settings['image_size'] ?? 'large',
                'card_content_v_align' => $settings['card_content_v_align'] ?? 'flex-end',
                'card_content_h_align' => $settings['card_content_h_align'] ?? 'flex-start',
                'title_align'     => $settings['title_align'] ?? '',
                'click_action'    => $settings['click_action'] ?? 'permalink',
                'click_popup_id'  => $settings['click_popup_id'] ?? '',
                'click_jet_popup_id' => $settings['click_jet_popup_id'] ?? '',
                // Modal Specifics
                'modal_use_custom_template' => $settings['modal_use_custom_template'] ?? '',
                'modal_auto_template'       => $settings['modal_auto_template'] ?? '',
                'modal_template_id'         => $settings['modal_template_id'] ?? '',
            ];
            
            // Add template IDs if needed
            if ( 'elementor_loop' === $template_source && ! empty( $settings['elementor_loop_template'] ) ) {
                $jsf_settings['elementor_loop_template'] = $settings['elementor_loop_template'];
            }
            if ( 'jetengine' === $template_source && ! empty( $settings['jetengine_listing'] ) ) {
                $jsf_settings['jetengine_listing'] = $settings['jetengine_listing'];
            }
        }

        if ( $query_id ) {
            $this->add_render_attribute( 'jsf_grid_container', 'data-query-id', $query_id );
            $this->add_render_attribute( 'jsf_grid_container', 'data-provider', 'loop-mosaic' );
            $this->add_render_attribute( 'jsf_grid_container', 'data-settings', wp_json_encode( $jsf_settings ) );
        }

        // Add Infinite Scroll Attributes
        if ( $is_infinite_scroll ) {
             $this->add_render_attribute( 'jsf_grid_container', 'data-infinite-scroll', 'true' );
             $this->add_render_attribute( 'jsf_grid_container', 'data-max-pages', $query->max_num_pages );
             $this->add_render_attribute( 'jsf_grid_container', 'data-paged', '1' );
             
             // Ensure settings are present even if JSF is off
             if ( ! $query_id ) {
                 $this->add_render_attribute( 'jsf_grid_container', 'data-settings', wp_json_encode( $jsf_settings ) );
             }
        }

        echo '<div ' . $this->get_render_attribute_string( 'jsf_grid_container' ) . '>';

        if ( $query->have_posts() ) {
            $index = 0;

            while ( $query->have_posts() ) {
                $query->the_post();

                // Item classes
                $item_classes = [ 'loopmosaic-item' ];

                // Add color overlay class for default template
                if ( 'default' === $template_source && ! empty( $settings['color_overlay'] ) && 'yes' === $settings['color_overlay'] ) {
                    $item_classes[] = $this->get_overlay_class( $index );
                }

                // Prepare item attributes
                $item_attrs = '';
                if ( 'default' === $template_source && 'popup' === ( $settings['click_action'] ?? 'permalink' ) && ! empty( $settings['click_popup_id'] ) ) {
                    $item_attrs = ' data-popup-id="' . esc_attr( $settings['click_popup_id'] ) . '"';
                }

                echo '<div class="' . esc_attr( implode( ' ', $item_classes ) ) . '"' . $item_attrs . '>';

                // Render based on template source
                switch ( $template_source ) {
                    case 'elementor_loop':
                        $template_id = ! empty( $settings['elementor_loop_template'] ) ? $settings['elementor_loop_template'] : '';
                        $this->render_elementor_loop_template( $template_id );
                        break;

                    case 'jetengine':
                        $listing_id = ! empty( $settings['jetengine_listing'] ) ? $settings['jetengine_listing'] : '';
                        $this->render_jetengine_listing( $listing_id );
                        break;

                    default:
                        // Robust ID retrieval
                        $current_id = get_the_ID();
                        if ( isset( $query->posts[ $index ] ) && $query->posts[ $index ] instanceof \WP_Post ) {
                            $current_id = $query->posts[ $index ]->ID;
                        }
                        $this->render_default_card( $settings, $current_id );
                        break;
                }

                echo '</div>';

                $index++;
            }

            wp_reset_postdata();
        } else {
            echo '<div class="loopmosaic-no-posts">' . esc_html__( 'No posts found.', 'loop-mosaic' ) . '</div>';
        }

        echo '</div>';
    }

    /**
     * Render default card content
     */
    private function render_default_card( $settings, $post_id = null ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        $image_size = ! empty( $settings['image_size'] ) ? $settings['image_size'] : 'large';
        $thumbnail  = get_the_post_thumbnail_url( $post_id, $image_size );

        // Determine click action link
        $click_action = ! empty( $settings['click_action'] ) ? $settings['click_action'] : 'permalink';
        $link_url = get_the_permalink( $post_id );
        $popup_attr = '';
        $link_classes = [ 'loopmosaic-item__link' ];
        
        if ( 'modal' === $click_action ) {
            $link_url = '#'; 
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
            $link_url = '#';
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
                    <?php echo esc_html( wp_trim_words( get_the_excerpt(), $settings['excerpt_length'], '...' ) ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render JetEngine listing template
     */
    private function render_jetengine_listing( $listing_id ) {
        if ( ! class_exists( 'Jet_Engine' ) || ! $listing_id ) {
            return;
        }

        // Get the listing content
        $listing = jet_engine()->listings;
        
        if ( $listing ) {
            echo $listing->get_listing_item_content( $listing_id );
        }
    }

    /**
     * Render Elementor Loop Item template
     */
    private function render_elementor_loop_template( $template_id ) {
        if ( ! class_exists( '\Elementor\Plugin' ) || ! $template_id ) {
            // Fallback message
            echo '<div class="loopmosaic-template-placeholder">' . esc_html__( 'Please select a template', 'loop-mosaic' ) . '</div>';
            return;
        }

        // Set up post data for dynamic tags
        $post_id = get_the_ID();
        
        // Use Elementor's frontend renderer
        $frontend = \Elementor\Plugin::$instance->frontend;
        
        if ( $frontend ) {
            // Get the template content with current post context
            echo $frontend->get_builder_content_for_display( $template_id, true );
        }
    }
}
