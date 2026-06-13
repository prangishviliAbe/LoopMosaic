<?php
/**
 * LoopMosaic Shared Item Renderer
 *
 * Single source of truth for rendering a grid item. Used by the widget's
 * initial render, the Load More / Infinite Scroll AJAX handler, and the
 * JetSmartFilters AJAX paths so every code path produces identical markup.
 *
 * All methods assume they are called inside the WordPress loop (i.e. after
 * `$query->the_post()`), exactly like the original inline rendering code.
 *
 * @package LoopMosaic
 * @author Abe Prangishvili
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('LoopMosaic_Renderer')) {

    class LoopMosaic_Renderer
    {
        /**
         * Overlay color presets cycled per item.
         */
        const OVERLAY_COLORS = ['purple', 'teal', 'gold', 'coral', 'cyan', 'green'];

        /**
         * Render a single grid item and return its HTML.
         *
         * @param array $settings Widget settings (full array or AJAX subset).
         * @param int   $post_id  Current post ID.
         * @param int   $index    Zero-based item index (for color/icon cycling).
         * @return string
         */
        public static function render_item($settings, $post_id, $index = 0)
        {
            $template_source = !empty($settings['template_source']) ? $settings['template_source'] : 'default';
            $card_design_style = !empty($settings['card_design_style']) ? $settings['card_design_style'] : 'overlay';
            $is_floating_icon_card = 'floating_icon' === $card_design_style;

            $item_classes = ['loopmosaic-item'];
            $item_attrs = '';

            if ($is_floating_icon_card) {
                $item_classes[] = 'loopmosaic-card-floating-icon';
                if ('default' !== $template_source) {
                    $item_classes[] = 'loopmosaic-card-floating-template';
                }
            }

            // Color overlay (default template, non-floating only).
            if ('default' === $template_source && !$is_floating_icon_card
                && !empty($settings['color_overlay']) && 'yes' === $settings['color_overlay']) {
                $item_attrs .= self::build_overlay($settings, $index, $item_classes);
            }

            // Elementor popup (distinct from built-in modal) trigger attribute.
            if ('default' === $template_source && 'popup' === ($settings['click_action'] ?? 'permalink')
                && !empty($settings['click_popup_id'])) {
                $item_attrs .= ' data-popup-id="' . esc_attr($settings['click_popup_id']) . '"';
            }

            $html = '<div class="' . esc_attr(implode(' ', $item_classes)) . '"' . $item_attrs . '>';

            // Non-default templates render the overlay link, hover overlay and
            // floating decorations at the item level. The default card renders
            // these internally (so the image can host the media wrapper).
            if ('default' !== $template_source) {
                $html .= self::render_overlay_link($settings, $post_id);
                $html .= self::render_hover_overlay($settings);

                if ($is_floating_icon_card) {
                    $html .= self::render_floating_icon($settings, $index);
                    $html .= self::render_floating_arrow($settings);
                }
            }

            switch ($template_source) {
                case 'elementor_loop':
                    $template_id = !empty($settings['elementor_loop_template']) ? $settings['elementor_loop_template'] : '';
                    if ($is_floating_icon_card) {
                        $html .= '<div class="loopmosaic-item__template-content">';
                    }
                    $html .= self::render_elementor_template($template_id);
                    if ($is_floating_icon_card) {
                        $html .= '</div>';
                    }
                    break;

                case 'jetengine':
                    $listing_id = !empty($settings['jetengine_listing']) ? $settings['jetengine_listing'] : '';
                    if ($is_floating_icon_card) {
                        $html .= '<div class="loopmosaic-item__template-content">';
                    }
                    $html .= self::render_jetengine_listing($listing_id);
                    if ($is_floating_icon_card) {
                        $html .= '</div>';
                    }
                    break;

                default:
                    $html .= self::render_default_card($settings, $post_id, $index);
                    break;
            }

            $html .= '</div>';

            return $html;
        }

        /**
         * Build the color-overlay class list and inline CSS variables.
         * Appends classes to $item_classes by reference, returns the style attr.
         *
         * @return string Attribute string (leading space) or ''.
         */
        private static function build_overlay($settings, $index, array &$item_classes)
        {
            // Default preset cycling.
            if (empty($settings['use_custom_overlay_colors']) || 'yes' !== $settings['use_custom_overlay_colors']
                || empty($settings['custom_overlay_colors'])) {
                $item_classes[] = 'overlay-' . self::OVERLAY_COLORS[$index % count(self::OVERLAY_COLORS)];
                return '';
            }

            $custom_colors = $settings['custom_overlay_colors'];
            $color_data = $custom_colors[$index % count($custom_colors)];
            $color_hex = isset($color_data['overlay_color']) ? $color_data['overlay_color'] : '#000000';

            $opacity = self::num($settings['overlay_opacity'] ?? null, 0.85);
            list($rgba, $rgb_commas) = self::hex_to_rgba($color_hex, $opacity);

            $text_color = !empty($color_data['overlay_text_color']) ? $color_data['overlay_text_color'] : '#ffffff';
            $hover_color = !empty($color_data['overlay_text_hover_color']) ? $color_data['overlay_text_hover_color'] : '#ffffff';
            $v_align = !empty($color_data['text_v_align']) ? $color_data['text_v_align'] : 'flex-end';
            $h_align = !empty($color_data['text_h_align']) ? $color_data['text_h_align'] : 'flex-start';

            $text_align_map = ['flex-start' => 'left', 'center' => 'center', 'flex-end' => 'right'];
            $text_align = isset($text_align_map[$h_align]) ? $text_align_map[$h_align] : 'left';

            $item_classes[] = 'overlay-custom';
            if (!empty($settings['overlay_hover_effect']) && 'none' !== $settings['overlay_hover_effect']) {
                $item_classes[] = 'overlay-hover-' . preg_replace('/[^a-z0-9_-]/i', '', $settings['overlay_hover_effect']);
            }

            $hover_opacity = self::num($settings['overlay_hover_opacity_value'] ?? null, 0.5);

            // Every value is escaped to keep this safe even when $settings
            // originates from an untrusted AJAX payload.
            $vars = [
                '--lm-custom-overlay: ' . esc_attr($rgba),
                '--lm-custom-overlay-rgb: ' . esc_attr($rgb_commas),
                '--lm-custom-text: ' . esc_attr($text_color),
                '--lm-custom-text-hover: ' . esc_attr($hover_color),
                '--lm-custom-v-align: ' . esc_attr($v_align),
                '--lm-custom-h-align: ' . esc_attr($h_align),
                '--lm-custom-text-align: ' . esc_attr($text_align),
                '--lm-custom-hover-opacity: ' . esc_attr($hover_opacity),
            ];

            return ' style="' . implode('; ', $vars) . ';"';
        }

        /**
         * Convert a hex color to an rgba() string + "r, g, b" component string.
         *
         * @return array [string $rgba, string $rgb_commas]
         */
        public static function hex_to_rgba($hex, $opacity = 1)
        {
            $hex = str_replace('#', '', (string) $hex);
            // Keep only valid hex chars to avoid garbage in the output.
            $hex = preg_replace('/[^0-9a-fA-F]/', '', $hex);

            if (strlen($hex) === 3) {
                $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
                $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
                $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
            } elseif (strlen($hex) >= 6) {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
            } else {
                $r = $g = $b = 0;
            }

            $opacity = (float) $opacity;

            return ["rgba($r, $g, $b, $opacity)", "$r, $g, $b"];
        }

        /**
         * Normalize a control value that may be a scalar or a slider array
         * with a 'size' key.
         */
        private static function num($value, $default = 0)
        {
            if (is_array($value)) {
                return isset($value['size']) ? $value['size'] : $default;
            }
            return (null === $value || '' === $value) ? $default : $value;
        }

        /**
         * Resolve the click action, link URL and trigger attributes for a post.
         *
         * @return array [string $link_url, array $link_classes, string $popup_attr, string $click_action]
         */
        private static function resolve_link($settings, $post_id, $modal_href = '#')
        {
            $click_action = !empty($settings['click_action']) ? $settings['click_action'] : 'permalink';
            $click_action = function_exists('loopmosaic_get_click_action')
                ? loopmosaic_get_click_action($post_id, $click_action)
                : $click_action;

            $redirect_url = function_exists('loopmosaic_get_redirect_url')
                ? loopmosaic_get_redirect_url($post_id)
                : '';

            $link_url = $redirect_url ? $redirect_url : get_the_permalink($post_id);
            $link_classes = ['loopmosaic-item__link'];
            $popup_attr = '';

            if ('modal' === $click_action) {
                $link_url = $modal_href;
                $link_classes[] = 'loopmosaic-modal-trigger';
                $popup_attr = ' data-post-id="' . esc_attr($post_id) . '"';

                if (!empty($settings['modal_use_custom_template']) && 'yes' === $settings['modal_use_custom_template']) {
                    if (!empty($settings['modal_auto_template']) && 'yes' === $settings['modal_auto_template']) {
                        $popup_attr .= ' data-auto-template="1"';
                    } elseif (!empty($settings['modal_template_id'])) {
                        $popup_attr .= ' data-modal-template-id="' . esc_attr($settings['modal_template_id']) . '"';
                    }
                }

                if (empty($settings['show_gallery_in_modal']) || 'yes' !== $settings['show_gallery_in_modal']) {
                    $popup_attr .= ' data-no-gallery="true"';
                }
            } elseif ('none' === $click_action) {
                $link_url = 'javascript:void(0);';
            }

            return [$link_url, $link_classes, $popup_attr, $click_action];
        }

        /**
         * Overlay link for non-default templates (Elementor Loop / JetEngine).
         */
        private static function render_overlay_link($settings, $post_id)
        {
            $redirect_url = function_exists('loopmosaic_get_redirect_url') ? loopmosaic_get_redirect_url($post_id) : '';
            $click_action = !empty($settings['click_action']) ? $settings['click_action'] : 'permalink';
            $click_action = function_exists('loopmosaic_get_click_action')
                ? loopmosaic_get_click_action($post_id, $click_action)
                : $click_action;

            // Only render the overlay link when it has a purpose.
            $needs_link = ('modal' === $click_action || 'none' === $click_action) || $redirect_url;
            if (!$needs_link) {
                return '';
            }

            list($link_url, $link_classes, $popup_attr) = self::resolve_link($settings, $post_id, '#');

            // the_title_attribute() already returns an esc_attr'd value.
            return '<a href="' . esc_url($link_url) . '" class="' . esc_attr(implode(' ', $link_classes)) . '" aria-label="'
                . the_title_attribute(['echo' => false]) . '"' . $popup_attr . '></a>';
        }

        /**
         * Hover overlay (icon + label) rendered above templates.
         */
        private static function render_hover_overlay($settings)
        {
            if (empty($settings['enable_hover_overlay']) || 'yes' !== $settings['enable_hover_overlay']) {
                return '';
            }

            $html = '<div class="loopmosaic-item__hover-overlay"><div class="loopmosaic-item__hover-content">';
            if (!empty($settings['hover_overlay_icon']['value'])) {
                $html .= '<div class="loopmosaic-item__hover-icon">';
                ob_start();
                \Elementor\Icons_Manager::render_icon($settings['hover_overlay_icon'], ['aria-hidden' => 'true']);
                $html .= ob_get_clean();
                $html .= '</div>';
            }
            if (!empty($settings['hover_overlay_label'])) {
                $html .= '<span class="loopmosaic-item__hover-label">' . esc_html($settings['hover_overlay_label']) . '</span>';
            }
            $html .= '</div></div>';

            return $html;
        }

        /**
         * Resolve the floating icon data (with per-item cycling).
         */
        private static function get_floating_icon_data($settings, $index)
        {
            $icon = !empty($settings['floating_card_icon']) ? $settings['floating_card_icon'] : [];
            $bg_color = !empty($settings['floating_card_icon_bg_color']) ? $settings['floating_card_icon_bg_color'] : '#d62f67';
            $color = !empty($settings['floating_card_icon_color']) ? $settings['floating_card_icon_color'] : '#ffffff';

            if (!empty($settings['floating_card_icon_items']) && is_array($settings['floating_card_icon_items'])) {
                $icon_items = array_values(array_filter($settings['floating_card_icon_items'], function ($item) {
                    return !empty($item['icon']['value']);
                }));

                if (!empty($icon_items)) {
                    $item = $icon_items[$index % count($icon_items)];
                    $icon = !empty($item['icon']) ? $item['icon'] : $icon;
                    $bg_color = !empty($item['icon_bg_color']) ? $item['icon_bg_color'] : $bg_color;
                    $color = !empty($item['icon_color']) ? $item['icon_color'] : $color;
                }
            }

            return ['icon' => $icon, 'bg_color' => $bg_color, 'color' => $color];
        }

        /**
         * Floating icon decoration.
         */
        private static function render_floating_icon($settings, $index)
        {
            if (empty($settings['show_floating_card_icon']) || 'yes' !== $settings['show_floating_card_icon']) {
                return '';
            }

            $data = self::get_floating_icon_data($settings, $index);
            if (empty($data['icon']['value'])) {
                return '';
            }

            $style = '--lm-floating-icon-bg: ' . esc_attr($data['bg_color']) . '; --lm-floating-icon-color: ' . esc_attr($data['color']) . ';';
            ob_start();
            \Elementor\Icons_Manager::render_icon($data['icon'], ['aria-hidden' => 'true']);
            $icon_html = ob_get_clean();

            return '<span class="loopmosaic-item__floating-icon" style="' . esc_attr($style) . '" aria-hidden="true">' . $icon_html . '</span>';
        }

        /**
         * Floating arrow decoration.
         */
        private static function render_floating_arrow($settings)
        {
            if (empty($settings['show_floating_card_arrow']) || 'yes' !== $settings['show_floating_card_arrow']) {
                return '';
            }
            return '<span class="loopmosaic-item__floating-arrow" aria-hidden="true">&rarr;</span>';
        }

        /**
         * Default built-in card.
         */
        private static function render_default_card($settings, $post_id, $index)
        {
            $card_design_style = !empty($settings['card_design_style']) ? $settings['card_design_style'] : 'overlay';
            $is_floating_icon_card = 'floating_icon' === $card_design_style;

            $image_size = !empty($settings['image_size']) ? $settings['image_size'] : 'large';
            $thumbnail = get_the_post_thumbnail_url($post_id, $image_size);

            list($link_url, $link_classes, $popup_attr) = self::resolve_link($settings, $post_id, 'javascript:void(0);');

            // the_title_attribute() already returns an esc_attr'd value.
            $html = '<a href="' . esc_url($link_url) . '" class="' . esc_attr(implode(' ', $link_classes)) . '" aria-label="'
                . the_title_attribute(['echo' => false]) . '"' . $popup_attr . '></a>';

            // Media wrapper with optional hover overlay.
            if ($thumbnail) {
                $html .= '<div class="loopmosaic-item__media">';
                $html .= '<img src="' . esc_url($thumbnail) . '" alt="' . the_title_attribute(['echo' => false]) . '" class="loopmosaic-item__image">';
                $html .= self::render_hover_overlay($settings);
                $html .= '</div>';
            }

            // Inner content alignment (only meaningful for overlay-style cards).
            $inner_styles = [];
            if (!$is_floating_icon_card && !empty($settings['card_content_v_align'])) {
                $inner_styles[] = 'justify-content: ' . esc_attr($settings['card_content_v_align']);
            }
            if (!$is_floating_icon_card && !empty($settings['card_content_h_align'])) {
                $inner_styles[] = 'align-items: ' . esc_attr($settings['card_content_h_align']);
            }
            $inner_attr = !empty($inner_styles) ? ' style="' . implode('; ', $inner_styles) . '"' : '';

            $html .= '<div class="loopmosaic-item__inner"' . $inner_attr . '>';

            if ($is_floating_icon_card) {
                $html .= self::render_floating_icon($settings, $index);
            }

            if (!empty($settings['show_category']) && 'yes' === $settings['show_category']) {
                $categories = get_the_category();
                if (!empty($categories)) {
                    $html .= '<span class="loopmosaic-item__category">' . esc_html($categories[0]->name) . '</span>';
                }
            }

            if (!empty($settings['show_title']) && 'yes' === $settings['show_title']) {
                $title_attr = '';
                if (!empty($settings['title_align'])) {
                    $title_attr = ' style="text-align: ' . esc_attr($settings['title_align']) . '"';
                }
                // Title keeps the_title filter output (HTML allowed), matching
                // core convention; post titles are author/admin-controlled.
                $html .= '<h3 class="loopmosaic-item__title"' . $title_attr . '>' . get_the_title($post_id) . '</h3>';
            }

            if (!empty($settings['show_excerpt']) && 'yes' === $settings['show_excerpt']) {
                $length = isset($settings['excerpt_length']) ? intval($settings['excerpt_length']) : 20;
                $html .= '<p class="loopmosaic-item__excerpt">' . esc_html(wp_trim_words(get_the_excerpt(), $length, '...')) . '</p>';
            }

            if ($is_floating_icon_card) {
                $html .= self::render_floating_arrow($settings);
            }

            $html .= '</div>';

            return $html;
        }

        /**
         * Render an Elementor Loop template.
         */
        private static function render_elementor_template($template_id)
        {
            if (!$template_id || !class_exists('\Elementor\Plugin')) {
                return '<div class="loopmosaic-template-placeholder">' . esc_html__('Please select a template', 'loop-mosaic') . '</div>';
            }
            return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($template_id, true);
        }

        /**
         * Render a JetEngine listing template.
         */
        private static function render_jetengine_listing($listing_id)
        {
            if (!$listing_id || !class_exists('Jet_Engine')) {
                return '';
            }
            $listing = jet_engine()->listings;
            return $listing ? $listing->get_listing_item_content($listing_id) : '';
        }

        /**
         * Render the "no posts" placeholder.
         */
        public static function render_no_posts($settings = [])
        {
            $message = !empty($settings['no_posts_message'])
                ? $settings['no_posts_message']
                : esc_html__('No posts found.', 'loop-mosaic');
            return '<div class="loopmosaic-no-posts">' . esc_html($message) . '</div>';
        }
    }
}
