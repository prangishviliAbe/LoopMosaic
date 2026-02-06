# Changelog

All notable changes to LoopMosaic will be documented in this file.

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
