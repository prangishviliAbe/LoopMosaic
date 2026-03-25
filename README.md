# LoopMosaic for Elementor

![LoopMosaic Cover](assets/LoopMosaic_cover.png)

**The ultimate Elementor addon for stunning post displays.** Create beautiful Mosaic, Grid, and Masonry layouts with advanced features including AJAX-powered modal popups, real-time JetSmartFilters search integration, infinite scroll pagination, and seamless support for Elementor Loop Items & JetEngine Listings.

[![Version](https://img.shields.io/badge/version-1.15.0-blue.svg)](https://github.com/prangishviliAbe/LoopMosaic)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-green.svg)](https://wordpress.org/)
[![Elementor](https://img.shields.io/badge/Elementor-3.0%2B-purple.svg)](https://elementor.com/)
[![License](https://img.shields.io/badge/license-GPL%20v2-red.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 🚀 Key Features

### 🎨 Flexible Layout Patterns

- **Classic Grid** — Clean, uniform post grid
- **Metro Mosaic** — Dynamic tile sizes for visual interest
- **Masonry** — Pinterest-style staggered layout
- **Highlight First** — Featured first post with smaller cards
- **Uniform Grid** — Equal-sized cards in perfect rows
- **Featured Grid** — Alternating pattern of 2+3 cards
- **Hero Grid** — Alternating pattern of 1 hero + 3 small cards

### ✨ Interactions & Animations

- **Scroll Animations** — Hardware-accelerated, staggered entry animations (Fade, Slide, Scale, Zoom) via IntersectionObserver.
- **Hover Effects** — Smooth scaling and image transitions.

### 📐 Full Customization

- **Columns**: 3-6 column layouts with responsive controls
- **Gaps & Padding**: Pixel-perfect spacing control
- **Border Radius**: Rounded corners for modern aesthetics
- **Color Overlays**: Gradient overlays with multiple color presets
- **Box Shadows**: Depth and elevation effects
- **Typography**: Full control over titles and category labels

### 🔗 Template Integrations

| Integration              | Description                             |
| ------------------------ | --------------------------------------- |
| **Default Cards**        | Built-in responsive card design         |
| **Elementor Loop Items** | Use your custom Loop Builder templates  |
| **JetEngine Listings**   | Full JetEngine listing template support |

### 🔍 JetSmartFilters Integration

- **Real-time Search** — Instant results as you type
- **AJAX Filtering** — No page reloads
- **Multiple Filter Types** — Search, checkboxes, dropdowns, range
- **Pagination Support** — Works with JSF pagination widgets

### ⚡ Built-in AJAX Modal System

Forget heavy third-party popup plugins! LoopMosaic includes a lightweight, performant modal system:

- **Instant Loading** — Content fetched dynamically via AJAX
- **Custom Templates** — Use any Elementor template as popup content
- **Dynamic Data** — Automatically pulls post data (title, image, content, custom fields)
- **Keyboard Navigation** — ESC key to close
- **Click Outside** — Close by clicking overlay
- **Smooth Animations** — Elegant open/close transitions

### 🔄 Infinite Scroll & Load More Button

- **Auto-Load** — Posts load automatically on scroll (Infinite Scroll mode)
- **Manual Button** — Click to load more posts (Load More Button mode)
- **Smooth Animation** — Fade-up animation for new items
- **Loading Indicator** — Built-in spinner
- **Smart Detection** — Stops when no more posts
- **Button Styling** — Full customization: text, colors, typography, padding, margin
- **Button Alignment** — Left, Center, Right alignment options
- **Works with Filters** — Compatible with JetSmartFilters

### 🛠️ Widget Controls

#### Query Builder

- Post Type selection (Posts, Pages, Custom Post Types)
- Taxonomy filtering (Categories, Tags, Custom Taxonomies)
- Order by (Date, Title, Menu Order, Random)
- Posts per page limit
- Offset support

#### Click Actions

| Action                  | Description                       |
| ----------------------- | --------------------------------- |
| **Open Single Post**    | Navigate to post permalink        |
| **Open Built-in Modal** | Open AJAX modal with post content |
| **None**                | Static grid (no click action)     |

#### Styling Options

- Title typography, color, alignment
- Category badge styling
- Overlay gradient colors
- Hover effects
- Box shadow presets

---

## 📦 Installation

### From GitHub

1. Download the latest release from [Releases](https://github.com/prangishviliAbe/LoopMosaic/releases)
2. Upload to `/wp-content/plugins/` directory
3. Activate via **Plugins** menu in WordPress

### Requirements

| Requirement     | Version                   |
| --------------- | ------------------------- |
| WordPress       | 6.0+                      |
| PHP             | 7.4+                      |
| Elementor       | 3.0+                      |
| Elementor Pro   | Optional (for Loop Items) |
| JetEngine       | Optional (for Listings)   |
| JetSmartFilters | Optional (for Filtering)  |

---

## 🎯 Usage Guide

### Basic Setup

1. Open Elementor Editor
2. Drag **LoopMosaic Grid** widget to your page
3. Configure query settings (post type, taxonomy, limit)
4. Choose layout pattern
5. Style as desired

### Using Custom Templates

#### With Elementor Loop Items

1. Create a Loop Item template in Elementor
2. In LoopMosaic widget, set **Template Source** to **Elementor Loop Item**
3. Select your template from dropdown

#### With JetEngine Listings

1. Create a Listing in JetEngine
2. In LoopMosaic widget, set **Template Source** to **JetEngine Listing**
3. Select your listing from dropdown

### Modal with Custom Template

1. Create a template in **Theme Builder** or **Saved Templates**
2. In LoopMosaic widget → **Interaction**
3. Set **Click Action** to **Open Built-in Modal**
4. Enable **Use Custom Template**
5. Select your template

### JetSmartFilters Integration

1. In LoopMosaic widget → **JetSmartFilters**
2. Enable **Enable JetSmartFilters**
3. Set a **Query ID** (e.g., `my-grid`)
4. Add JSF filter widgets (Search, Checkbox, etc.)
5. Set filter's **Content Provider** to **LoopMosaic Grid**
6. Set filter's **Query ID** to match (e.g., `my-grid`)

### Using Infinite Scroll or Load More Button

1. In LoopMosaic widget → **Query**
2. Enable **Enable Infinite Scroll**
3. Set **Posts Per Page** for initial load
4. Choose **Load Mode**:
   - **Infinite Scroll** — Auto-load on scroll
   - **Load More Button** — Manual click button
5. Customize button text and alignment
6. Style button in **Load More Button** tab
7. Save and test on frontend

---

## 📋 Changelog

### Version 1.12.8 (2026-03-16)
- **New:** Added **Featured Grid (2+3)** layout pattern — 2 large cards on top, 3 equal cards below, repeating cyclically.
- **New:** Added **Hero Grid (1+3)** layout pattern — 1 full-width hero card on top, 3 equal cards below, repeating cyclically.
- **New:** Responsive support for both new patterns — collapse to uniform grid on tablet/mobile.

### Version 1.12.7 (2026-03-16)
- **New:** Added **Exclude Posts** feature — searchable multi-select dropdown in Query settings to exclude specific posts from the grid.
- **New:** Exclude works with all post types and is compatible with Infinite Scroll / Load More.

### Version 1.12.6 (2026-02-13)
- **Improvement:** Comprehensive **responsive design overhaul** for mobile and tablet devices.
- **Improvement:** 5 breakpoints (1024px, 768px, 600px, 480px, 360px) for grid layout.
- **Improvement:** Touch device optimizations — hover effects removed, 44px touch targets.
- **Improvement:** Mobile modal uses **bottom-sheet UX** pattern on small screens.
- **Improvement:** Gallery columns reduce automatically on smaller screens.
- **Improvement:** Load More button full-width on mobile.

### Version 1.12.5 (2026-02-11)
- **Fix:** Resolved **Z-Index conflict** where Elementor Lightbox would open behind the LoopMosaic Modal.

### Version 1.12.4 (2026-02-11)
- **New:** Added support for **1 and 2 Column Layouts** in LoopMosaic widget.
- **Fix:** Resolved missing **Elementor Post Info (Meta Data)** in Modal Popup by enforcing correct post context.
- **Fix:** Fixed CSS visibility issues for Elementor dynamic content inside the Modal.

### Version 1.12.3 (2026-02-10)
- **Fix:** "LoopMosaic Grid" now correctly appears in the "Filter for" dropdown for **JetSmartFilters Checkbox, Radio, Select, and Range** widgets.
- **Improved:** Robust provider injection into Elementor controls for better compatibility.

### Version 1.12.2 (2026-02-10)
- **New:** Added "Under Image" Card Style option for alternative layouts.
- **Fix:** Resolved critical error with undefined `$colors` variable in AJAX requests.
- **Fix:** Fixed PHP fatal error caused by missing `get_overlay_class` method.
- **Fix:** Fixed potential crash with opacity slider controls returning unexpected data types.
- **Fix:** "Modal" click action now correctly works with **Elementor Loop Items** and **JetEngine Listings** (previously only worked with Default Cards).
- **Tweak:** Improved internal link generation logic for better compatibility across template types.

### Version 1.12.1 (2026-02-10)
- **Fix:** Restored missing "Load More" button controls and rendering logic.
- **Fix:** Corrected Infinite Scroll behavior issues.
- **Fix:** Improved "Load More" button styling and positioning integration.

### Version 1.12.0 (2026-02-10)
- **New:** Major refactor for better performance and stability.
- **New:** Enhanced "Load More" button vs Infinite Scroll handling.
- **New:** Improved custom overlay color logic.

### Version 1.9.15 (2026-02-09)

- ✅ **New**: No Posts Found Customization - custom message text and styling (color, typography, alignment, padding)
- ✅ **New**: Custom Overlay Colors - opacity slider, text color, and hover text color controls
- ✅ **Improved**: CSS variables for dynamic custom overlay styling
- ✅ **Fixed**: Text color CSS specificity issues in Elementor editor

### Version 1.9.6 (2026-02-09)

- 🔒 **Security**: Fixed critical vulnerability in Load More handler (enforced nonce verification)
- ✅ **Fixed**: Resolved popup click action not working on filtered items
- ✅ **Improved**: Synchronized AJAX rendering logic with main widget
- ✅ **Changed**: Removed strict dependency on JetSmartFilters for standalone usage

### Version 1.9 (2026-02-06)

- ✅ **New**: Load More button feature - choose between Infinite Scroll or manual button
- ✅ **New**: Button text customization
- ✅ **New**: Full button styling controls (colors, typography, padding, margin)
- ✅ **New**: Button alignment options (Left, Center, Right)
- ✅ **Improved**: Enhanced JavaScript for button click handling and state management
- ✅ **Improved**: Added loading spinner animation for button mode

### Version 1.8 (2026-02-06)

- 🧹 **Cleaned**: Removed all debug logging code for production release
- 🧹 **Cleaned**: Removed all console.log statements from JavaScript
- 🧹 **Cleaned**: Deleted old debug JavaScript files (v2-v9)
- ✅ **Optimized**: Single clean `mosaic-filters.js` for better performance

### Version 1.7 (2026-02-06)

- ✅ **New**: Comprehensive JetSmartFilters support for ALL filter types:
  - Search inputs
  - Checkbox filters
  - Radio button filters
  - Select/Dropdown filters
  - Range slider filters
  - Date picker filters
  - Apply/Reset buttons
- ✅ **Improved**: Enhanced PHP filter parser with checkbox and range support
- ✅ **Improved**: Better debouncing for filter requests

### Version 1.6 (2026-02-06)

- ✅ **Fixed**: JetSmartFilters search integration now works correctly
- ✅ **New**: Direct search input binding for reliable filtering
- ✅ **Improved**: Better debouncing for search requests
- ✅ **Fixed**: Duplicate data attributes issue resolved

### Version 1.5

- ✅ Added infinite scroll pagination
- ✅ Improved modal loading animation
- ✅ Better JetEngine compatibility

### Version 1.0

- 🚀 Initial release
- Mosaic, Grid, Masonry layouts
- AJAX Modal system
- Elementor Loop Item support
- JetEngine Listing support

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## 📄 License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## 👤 Author

**Abe Prangishvili**

- GitHub: [@prangishviliAbe](https://github.com/prangishviliAbe)

---

## ⭐ Support

If you find this plugin useful, please consider giving it a ⭐ on GitHub!
