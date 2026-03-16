# Changelog

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
