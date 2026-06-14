# Changelog

## [1.20.0] - 2026-06-14
### Fixed
- **Slide transition glitch (root cause)**: `will-change: transform` on the swiper container was promoting it to a GPU compositing layer, which caused the `overflow: hidden` clip to stop applying to GPU-composited child slides — letting transitioning slides bleed into the stacked card area. Replaced with `transform: translateZ(0)` which composites the swiper itself without breaking child clipping.
- **Stage/stacked card animated by theme**: The theme applies `transition: all` to all elements. During Swiper transitions, any property change on the stage would animate smoothly rather than snap, causing visual glitches. Fixed with `transition: none !important` on both `.loopmosaic-carousel-stage` and `.lm-stack-card`.
- **Hover lift "coming forward"**: Elementor Loop Template containers (`.e-con`) apply a `box-shadow` change on hover (0.3s transition) in addition to transform, creating a "lift forward" illusion. Extended the hover override to include `box-shadow: none !important` in addition to `transform: none !important`.

## [1.19.9] - 2026-06-14
### Fixed
- **Stacked card slide glitch**: Replaced `::before` pseudo-element with a real `<div class="lm-stack-card">` DOM element. Pseudo-elements on Swiper's parent container got repainted during GPU-composited slide transitions, causing flicker. A real element stays stable.
- **Hover lift (all template types)**: Extended the hover-transform override to cover `.swiper-slide`, `.e-con`, `.elementor-widget-wrap`, and `.jet-listing-grid__item` in addition to `.loopmosaic-item`. Elementor Loop Templates and JetEngine Listings use those containers and were still lifting.

## [1.19.8] - 2026-06-14
### Fixed
- **Stacked card color**: Lightened further to `rgba(22,82,68,0.74)`.

## [1.19.7] - 2026-06-14
### Fixed
- **Carousel border radius**: Default raised from 18 px to 24 px to match rounder reference design.
- **Stacked card color**: Lightened default from `rgba(8,35,32,0.92)` to `rgba(15,58,50,0.82)` — less dark, more visible against section background.
- **Stacked card corners**: `carousel_border_radius` selector now also targets `::before` (`border-radius: {{SIZE}} {{SIZE}} 0 0`) so the peek corners always match the main card.

## [1.19.6] - 2026-06-14
### Fixed
- **Stacked card Elementor default**: The `carousel_stack_color` control default was `rgba(10,41,38,0.5)` (50% opacity). Elementor inlines control defaults as CSS, which overrides the stylesheet — so the CSS file change in v1.19.5 had no effect. Default is now `rgba(8,35,32,0.92)` (solid dark teal).

## [1.19.5] - 2026-06-14
### Fixed
- **Stacked card opacity**: Raised pseudo-element background opacity from 0.5 to 0.92 so the stacked card peek is clearly visible against the slide.

## [1.19.4] - 2026-06-14
### Added
- **Stacked card behind carousel**: New decorative "stacked-deck" peek behind the slide (on by default). Controls: enable toggle, color, peek amount, and side inset.

## [1.19.3] - 2026-06-14
### Fixed
- **Carousel hover lift**: Disabled the grid's `translateY(-4px)` card-lift inside the carousel, so slides no longer jump "forward" on hover.

## [1.19.2] - 2026-06-14
### Fixed
- **Carousel pagination position**: Pagination switches are now rendered *below* the card instead of on top of the slide, so they no longer overlap slide content (titles, buttons) on content-heavy slides.
- **Arrow alignment**: Introduced a stage wrapper so the right-side navigation arrows stay vertically centered on the card even with pagination placed below.

## [1.19.1] - 2026-06-14
### Changed
- **Carousel pagination switches**: Pagination is now enabled by default and restyled as modern "switch" indicators — inactive bullets are small dots, the active one elongates into a pill, matching the reference design.
- Added **Switch Color** and **Active Switch Color** controls to the Carousel Navigation style panel.

## [1.19.0] - 2026-06-14
### Added
- **Carousel Layout**: New `layout_mode` option "Carousel (Slider)" powered by Swiper 11. Each post becomes a slide; Elementor Loop Templates and JetEngine Listings render inside slides at full height.
- **Right-side vertical navigation**: Two circular prev/next buttons (↑↓) positioned to the right of the carousel, matching the project card UI design.
- **Carousel Settings panel** (Content tab, visible when Carousel mode is active): slide height (px/vh, responsive), border radius, loop toggle, autoplay + delay, transition speed, pagination dots toggle.
- **Carousel Navigation Style panel** (Style tab): button size, icon size, icon color, background color, hover background color — all fully editable via Elementor controls.
- Mobile layout: on ≤768px the navigation moves to bottom-right horizontal row automatically.

## [1.18.1] - 2026-06-13
### Performance
- **Redirect scan guard**: `loopmosaic_get_redirect_url()` now skips four regex patterns when post content does not contain the substring `"location"`. Posts without JS redirects (the vast majority) pay only a fast `stripos` check instead of four full regex passes.

## [1.18.0] - 2026-06-13
### Security
- **AJAX query hardening**: Settings arriving from the client (Load More / JetSmartFilters) are now sanitized before reaching `WP_Query` — `post_type` must be publicly queryable, `taxonomy` must be registered, `orderby` is whitelisted, `order` is normalized, and `posts_per_page` is clamped (no unbounded queries).
- **Modal endpoint (IDOR)**: The modal AJAX handler now only serves publicly viewable posts (published, non-password-protected) and rejects arbitrary `template_id` values that are not actual Elementor templates, preventing enumeration of private/draft content.
- **Output escaping**: All inline overlay CSS variables are now consistently escaped, closing an attribute-injection vector on the unauthenticated filter endpoint.

### Changed
- **Unified renderer**: Item markup is now produced by a single `LoopMosaic_Renderer` class used by the widget, Load More / Infinite Scroll, and both JetSmartFilters paths. Previously the rendering logic was duplicated across five locations, which caused inconsistent markup between initial load and AJAX responses.

### Fixed
- **Consistent AJAX markup**: Load More and filtered results now include the media wrapper, hover overlay, custom overlay colors, and floating-icon decorations that the initial render produced.
- **Masonry after filtering**: Masonry layouts are now re-laid out after a JetSmartFilters custom AJAX filter, and pagination resets to page 1 against the filtered set.
- **Multi-grid filters**: Filter values are now scoped to their grid's query id, so multiple LoopMosaic grids on one page no longer cross-contaminate.

### Removed
- Dead `includes/jetsmartfilters-compat-clean.php` file, duplicate JavaScript helpers (`disableNativeLinks`, `initLoadMoreButton`), a duplicate Elementor provider-injection hook loop, and leftover `console.log` debug calls.

## [1.17.1] - 2026-04-30
### Fixed
- **Install Package Slug**: Rebuilt the release ZIP with the canonical lowercase `loop-mosaic` plugin folder to avoid invalid plugin name warnings during upload/install.
- **Package Contents**: Removed the debug `test.php` file from the plugin codebase and release package.

## [1.17.0] - 2026-04-30
### Added
- **Floating Icon Card Design**: Added a new card design option with image-first layout, floating circular icons, arrow indicators, and polished white content panels.
- **Elementor Loop Item Support**: Extended Floating Icon Card styling to Elementor Loop Item templates while preserving the selected loop template content.
- **Icon Controls**: Added controls for enabling/disabling icons, selecting icons, defining icon sets, and customizing icon colors and sizes.
- **Pattern-Aware Sizing**: Added separate featured and small card image height controls for Featured Grid (2+3), Featured Grid (2+4), and Hero Grid layouts.
- **Floating Card Layout Controls**: Added controls for image height, content minimum height, overlap, padding, icon offsets, and arrow positioning.
- **Classic Card Borders**: Added normal and hover border controls for the existing classic/overlay card style.

### Changed
- Floating card image and icon placement now adapts to layout patterns so 2+4 grids keep balanced proportions across large and small cards.
- Load More and infinite scroll rendering now preserves Floating Icon Card settings for newly loaded items.

## [1.16.1] - 2026-04-29
### Fixed
- **Redirect Script Handling**: Posts containing inline JavaScript redirects such as `window.location.replace("...")` now link directly to the target URL instead of opening the LoopMosaic modal first.
- **Template Render Consistency**: Applied redirect URL detection across default cards, AJAX Load More, JetSmartFilters rendering, and provider-based rendering.
- **Plugin Title**: Updated the WordPress plugin header title to "LoopMosaic for Elementor".

## [1.16.0] - 2026-04-20
### Added
- **Modal Styling Controls**: Added Elementor controls for modal width, height, padding, border radius, overlay color, box shadow, title typography, content typography, and image spacing.

### Changed
- **Query Limit**: Increased the widget Posts Per Page control maximum to 150.

## [1.15.0] - 2026-03-25
### Added
- **New Layout Pattern**: Added "Featured Grid (2+4)", a premium layout displaying 2 large items and 4 smaller ones.
- **Premium Scroll Animations**: Added 5 new high-end animations: Blur In, Blur Up, 3D Flip Up, Skew Up, and Reveal Left Wipe.
- **Enhanced Gap Controls**: Replaced the global gap option with individual Horizontal (`column_gap`) and Vertical (`row_gap`) responsive controls.

### Changed
- **Animation Easing**: Upgraded all scroll animation easing curves to an Apple-style, smoother `cubic-bezier` function.

### Fixed
- **Query Ordering**: Fixed the `menu_order` fallback bug that caused unpredictable sorting by appending secondary `title` sort.
- **JSF Exclude Posts**: Ensured `exclude_posts` correctly applies across the JetSmartFilters layer, provider, and AJAX Load More calls.
- **Redirect Conflicts**: Bypassed modal popups for posts containing hardcoded JavaScript redirects or URL meta values.

## [1.14.0] - 2026-03-24
### Fixed
- **Taxonomy Filter**: Taxonomy dropdown now shows only taxonomies relevant to the selected Post Type instead of all system taxonomies.
- **Terms Selection**: Replaced manual slug text input with a multi-select dropdown that auto-populates available terms for the selected taxonomy.

## [1.13.1] - 2026-03-16
### Fixed
- **Scroll Animations**: Fixed an issue where the animation settings panel did not render in the Elementor editor properly.

## [1.13.0] - 2026-03-16
### Added
- **Scroll Animations**: A high-performance, scroll-triggered animation system for post cards.
- Support for multiple animation types: Fade In, Fade Up, Fade Down, Fade Left, Fade Right, Slide Up, Scale In, Subtle Zoom.
- Widget controls for animation duration, stagger delay, and disabling on mobile devices.
- Uses vanilla JS IntersectionObserver and hardware-accelerated CSS for smooth, jank-free performance.

## [1.12.8] - 2026-03-16
### Added
- **Featured Grid (2+3)**: New layout pattern with 2 large cards on top and 3 equal cards below, repeating cyclically.
- **Hero Grid (1+3)**: New layout pattern with 1 full-width hero card on top and 3 equal cards below, repeating cyclically.
- Responsive support: both new patterns collapse to uniform grid on tablet and mobile.

## [1.12.7] - 2026-03-16
### Added
- **Exclude Posts**: New multi-select dropdown in Query settings to exclude specific posts from the grid.
- Posts are listed by title with post type label for easy identification.
- Works with all post types and compatible with Infinite Scroll / Load More.

## [1.12.1] - 2026-02-10
### Fixed
- **Missing Styles**: Restored CSS for "Load More" button and spinner animation which were missing in the previous build.

## [1.12.0] - 2026-02-09
### Added
- **Load More Button**: 
    - Added "Load More" button as a pagination trigger option (replacing the need for Item-level Read More).
    - New `infinite_scroll_trigger` control in Pagination settings (Scroll vs Button).
    - Full styling controls for the Load More button (Typography, Colors, Borders, Spacing).
- **Infinite Scroll Enhancements**:
    - Clean separation of "Scroll" and "Button" trigger logic.
    - Added loading spinner and states to the button.

### Fixed
- **Overlay Styles in AJAX**:
    - Fixed issue where custom overlay styling (colors, opacity) was lost on items loaded via AJAX (Infinite Scroll/Load More).
- **Layout Issues**:
    - Moved the "Load More" button outside the grid container to ensure logical DOM order and correct visual placement below all items.
    - Resolved z-index stacking context issues for buttons.

## [1.11.1] - 2026-02-09
### Changed
- Removed Item-level "Read More" button in favor of the new global "Load More" pagination approach.
- Refactored rendering logic to support conditional pagination triggers.

## [1.10.0] - 2026-02-09
### Added
- **True Masonry Layout**:
    - New `layout_mode` control to switch between 'CSS Grid' and 'True Masonry (JS)'.
    - **Masonry Column Rules**: Repeater control to set specific heights for cards in specific columns.
    - Automatic gap filling using `masonry.js`.

### Fixed
- **Half Height Highlight Item**:
    - Resolved issue where 'Half Height' setting for Classic/Metro patterns was not applying correctly after save.
    - Restored missing CSS for `pattern-classic`.
- **Clickability Regression**:
    - Fixed issue where links and popups were not clickable due to structural CSS changes.
    - Restored `z-index` and positioning for link elements.
- **Initial Load Styles**:
    - Fixed missing `overlay-custom` class on initial widget render, ensuring custom styles appear immediately.

## [1.9.15] - 2026-02-09
### Added
- **No Posts Found Customization**:
    - Add Content control (Textarea) for custom "No posts found" message.
    - Add Style controls (Color, Typography, Alignment, Padding) for message.
- **Custom Overlay Colors**:
    - Add `overlay_opacity` slider control.
    - Add `overlay_text_color` control to repeater.
    - Add `overlay_text_hover_color` control to repeater.

### Changed
- Refactored `render` methods in widget and JSF compatibility to support new overlay features.
- Updated CSS to use CSS variables for custom colors and text colors.
- Bumped version to `1.9.15`.

## [1.9.14] - 2026-02-09
- Internal fix: Register No Posts Found style controls properly.

## [1.9.13] - 2026-02-09
- Major update for "No posts found" customization features.

## [1.9.12] - 2026-02-09
- Added Hover Text Color control.

## [1.9.11] - 2026-02-09
- Fixed Text Color CSS specificity issue.


All notable changes to LoopMosaic will be documented in this file.

## [1.9.6] - 2026-02-09

### Security
- Fixed critical vulnerability in Load More handler (enforced nonce verification)

### Fixed
- Resolved popup click action not working on filtered items
- Synchronized AJAX rendering logic with main widget

### Changed
- Removed strict dependency on JetSmartFilters for standalone usage

## [1.9] - 2026-02-06

### Added

- Load More button feature - choose between Infinite Scroll or manual button
- Button text customization field
- Full button styling controls:
  - Text Color
  - Background Color
  - Hover Background Color
  - Typography
  - Padding
  - Border Radius
  - Box Shadow
  - Margin (Top/Bottom/Left/Right)
- Button alignment options (Left, Center, Right)

### Improved

- Enhanced JavaScript for button click handling
- Added loading spinner animation for button mode
- Button state management (loading, disabled, hidden when finished)
- Smooth staggered fade-in animation for infinite scroll (new items only)
- Better cubic-bezier easing for elegant animation
- Fixed 403 nonce verification error for infinite scroll

## [1.8] - 2026-02-06

### Changed

- Removed all debug logging code for production release
- Removed all console.log statements from JavaScript
- Deleted old debug JavaScript files (v2-v9)
- Optimized to single clean `mosaic-filters.js` for better performance

## [1.7] - 2026-02-06

### Added

- Comprehensive JetSmartFilters support for ALL filter types:
  - Search inputs
  - Checkbox filters
  - Radio button filters
  - Select/Dropdown filters
  - Range slider filters
  - Date picker filters
  - Apply/Reset buttons

### Improved

- Enhanced PHP filter parser with checkbox and range support
- Better debouncing for filter requests

## [1.6] - 2026-02-06

### Fixed

- JetSmartFilters search integration now works correctly
- Duplicate data attributes issue resolved

### Added

- Direct search input binding for reliable filtering

### Improved

- Better debouncing for search requests

## [1.5] - 2026-02-05

### Added

- Infinite scroll pagination

### Improved

- Modal loading animation
- Better JetEngine compatibility

## [1.0] - 2026-02-01

### Added

- Initial release
- Mosaic, Grid, Masonry layouts
- AJAX Modal system
- Elementor Loop Item support
- JetEngine Listing support
- Full customization controls
- Template integrations
