# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.7.0] - 2025-10-29

### Added
- **Debug Manager** (@since 0.7.0)
  - New unified `Peaches_Debug_Manager` class for centralized debug tools
  - Product inspection tool for detailed product data analysis
  - System information display for debugging environment details
  - Integration with main plugin architecture
  - Admin menu integration under Peaches menu
  - Proper capability checks and security measures

### Enhanced
- **Logging Standards** (@since 0.7.0)
  - Standardized all `log_error()` and `log_info()` wrapper methods across codebase
  - All wrappers now follow consistent pattern using `Peaches_Ecwid_Utilities::log_error()`
  - Added `log_info()` methods for debug/informational logging
  - Replaced direct `error_log()` calls with private wrapper functions
  - All log messages include proper class identifiers for better traceability
  - Template functions and render files now use `is_debug_mode()` for cleaner debug checks
  - Fallback logging mechanism when utilities class is not available

- **Settings Organization** (@since 0.7.0)
  - New Debug tab in Ecwid Settings page
  - Debug mode toggle and debug tools access centralized
  - Clear navigation to Debug Tools page
  - Reference to Multilingual Settings for rewrite rules management

### Refactored
- **Code Standards** (@since 0.7.0)
  - All PHP files now use tabs for indentation
  - Proper spacing and Yoda conditions throughout
  - Complete PHPDoc blocks for all methods
  - Escaped output and sanitized input across all files
  - Translatable user-facing strings

- **Logging Architecture** (@since 0.7.0)
  - Bootstrap Blocks classes wrap `Peaches_Utilities::log_error()`
  - Ecwid Blocks classes wrap `Peaches_Ecwid_Utilities::log_error()`
  - Debug logging separated from error logging using `log_info()`
  - Consistent error context using arrays instead of string concatenation

### Fixed
- **Static Method Context** (@since 0.7.0)
  - Fixed "Using $this when not in object context" error in `Peaches_Responsive_Sizes_Calculator`
  - Made log methods static to match class architecture

### Deprecated
- **Legacy Product Debug Class** (@since 0.7.0)
  - Old product debug class marked as deprecated
  - Shows redirect notice pointing to new unified Debug Tools

### Documentation
- **Logging Patterns** (@since 0.7.0)
  - Documented standardized logging wrapper pattern
  - Guidelines for using `log_info()` vs `log_error()`
  - Examples of proper class identifier usage

## [0.6.2] - 2025-10-29

### Added
- **GTM Product Block Tracking** (@since 0.6.2)
  - Product impression tracking when products become visible in viewport (50% threshold)
  - Product click tracking when users navigate from listing to detail pages
  - Intersection Observer API integration for efficient viewport detection
  - Enhanced E-commerce event format for GTM dataLayer
  - Product data (id, name, price, brand, category, variant) passed to frontend via Interactivity API
  - Comprehensive documentation for GTM setup and configuration
  - Event tracking for product cards on homepage, shop page, and category listings

### Enhanced
- **Ecwid Integration** (@since 0.6.2)
  - Seamless integration with Ecwid's native add-to-cart tracking (GA4 format)
  - No duplicate event tracking - leverages Ecwid's built-in analytics for cart events
  - Category data extraction from Ecwid product objects for enhanced tracking

### Refactored
- **Product Debug Tool** (@since 0.6.2)
  - Converted from procedural code to proper class-based architecture
  - New `Peaches_Ecwid_Product_Debug` class following plugin patterns
  - Integrated into main plugin initialization via `class-ecwid-blocks.php`
  - Improved dependency injection using Ecwid API instance
  - Enhanced security with proper capability checks
  - Maintained admin page and shortcode functionality
  - Admin-only initialization for better performance

### Fixed
- **Product Debug Tool Early Execution** (@since 0.6.2)
  - Fixed fatal error caused by calling `current_user_can()` before WordPress fully loaded
  - Moved capability checks from `init_hooks()` to actual callback methods
  - Ensures proper WordPress initialization order

### Documentation
- **GTM Tracking Guides** (@since 0.6.2)
  - Complete GTM setup step-by-step guide with tag and trigger configuration
  - Quick reference guide for event structures and testing
  - Visual guide for viewing events in browser console and GTM Preview Mode
  - Troubleshooting documentation for common issues
  - Implementation summary with complete e-commerce funnel tracking

## [0.6.1] - 2025-10-07

### Added
- **Dynamic SEO Metadata for Product Pages** (@since 0.6.1)
  - Automatic page title generation based on product slug using WordPress `document_title_parts` filter
  - Dynamic meta descriptions extracted from Ecwid product descriptions (HTML stripped, 30 words max)
  - Product image integration for Open Graph and social media sharing meta tags
  - Smart template page detection using configurable settings from `Peaches -> Ecwid Blocks -> Templates`

### Enhanced
- **Template Page Configuration Integration** (@since 0.6.1)
  - SEO filters now respect user-configured template page from plugin settings
  - Robust fallback system: settings → auto-generated page → error logging
  - Template page validation ensuring configured pages exist and are published
  - Backwards compatibility with existing "product-detail" auto-generated pages

## [0.6.0] - 2025-09-18

### Added
- **Advanced Translation Support for Product Descriptions** (@since 0.6.0)
  - Full multilingual support for custom product descriptions using peaches-multilingual plugin
  - Nested translation data structure with language-specific content storage
  - Page-level language switcher for product settings management
  - Automatic translation field display based on available languages
  - Translation support for all description types (usage, ingredients, care, warranty, features, etc.)

- **Enhanced Language Detection** (@since 0.6.0)
  - Hierarchical plugin detection prioritizing peaches-multilingual functions
  - Fallback support for Polylang and WPML when peaches-multilingual unavailable
  - New utility methods: `is_peaches_multilingual_available()`, `get_render_language()`
  - Centralized language detection through `Peaches_Ecwid_Utilities` class

- **Product Settings Page Translation UI** (@since 0.6.0)
  - Language switcher integration with Bootstrap styling
  - Dynamic translation field visibility based on selected language
  - TinyMCE editor initialization for translated content fields
  - Ingredient dropdown refresh functionality for language-specific content

- **Frontend Translation Rendering** (@since 0.6.0)
  - Language-aware product description blocks with automatic content switching
  - Integration with WordPress Interactivity API for dynamic language detection
  - Cache invalidation system supporting multilingual content
  - Translation-aware REST API endpoints with language parameter support

- **Complete i18n Implementation** (@since 0.6.0)
  - Full internationalization support with proper text domain (`peaches`)
  - POT template file with 100+ translatable strings
  - Complete Dutch (nl_NL) translation included
  - English (en_US) reference translation provided
  - NPM scripts for translation management (`i18n:extract`, `i18n:update`, `i18n:compile`)
  - Comprehensive translator documentation and guidelines

### Enhanced
- **Database Query Optimization** (@since 0.6.0)
  - Language filtering disabled for Polylang/WPML queries to prevent content exclusion
  - Enhanced cache invalidation covering both Redis and internal object caches
  - Translation data preservation during validation and sanitization
  - Query parameters: `'lang' => ''` and `'suppress_filters' => true` for multilingual compatibility

### Fixed
- **Translation Data Retrieval** (@since 0.6.0)
  - Fixed validation logic stripping translation arrays during database retrieval
  - Resolved cache invalidation issues causing stale translation data
  - Fixed English translations showing Dutch content due to missing translation preservation
  - Corrected frontend block rendering with proper language-specific content display

### Breaking Changes
- **Method Visibility**: `Peaches_Ecwid_Utilities::get_default_language()` changed from private to public
- **Translation Data Structure**: Product descriptions now use nested translation arrays
- **Language Detection Priority**: peaches-multilingual functions now take precedence over Polylang/WPML

## [0.5.0] - 2025-08-15

### Added
- **Advanced Block Caching System** (@since 0.5.0)
  - Redis integration for high-performance block caching
  - Product-aware cache keys with language and user context support
  - Cache invalidation hooks for automated cache management
  - Helper functions: `peaches_ecwid_start_product_block_cache()`, `peaches_ecwid_end_block_cache()`

- **Enhanced Product Settings Management** (@since 0.5.0)
  - Page-level language switcher for multilingual content management
  - Improved AJAX handlers for dynamic content updates
  - Enhanced validation and sanitization for product data

### Enhanced
- **Performance Optimizations** (@since 0.5.0)
  - Significant improvement in block render times through Redis caching
  - Reduced database queries with intelligent cache strategies
  - Optimized language detection with static caching

## [0.4.7] - 2025-08-10

### Added
- **Enhanced API Methods** (@since 0.4.7)
  - Extended Ecwid API integration with additional product data endpoints
  - Improved error handling and response validation

## [0.4.6] - 2025-08-08

### Added
- **SEO Sitemap Integration** (@since 0.4.6)
  - Comprehensive sitemap manager for Ecwid product pages
  - WordPress sitemap integration for better search engine visibility
  - Automatic product URL generation and indexing
  - Configurable sitemap settings for product types
  - SEO-optimized product page structure

### Enhanced
- **Product URL Management** (@since 0.4.6)
  - Improved product page URLs for SEO optimization
  - Enhanced permalink structure for product detail pages

## [0.4.0] - 2025-08-01

### Added
- **Mollie Payment Integration** (@since 0.4.0)
  - Full Mollie payment gateway integration for subscription products
  - Subscription management with recurring payment support
  - Mollie subscriptions block for displaying subscription options
  - Customer subscription management interface
  - Payment webhook handling for subscription updates

- **Category Products Block** (@since 0.4.0)
  - New block for displaying products from specific Ecwid categories
  - Configurable product display options and filtering
  - Category-based product listings with pagination support

### Enhanced
- **Payment Processing** (@since 0.4.0)
  - Enhanced payment flow with Mollie integration
  - Improved subscription handling and customer management

## [0.3.4] - 2025-07-20

### Added
- **Enhanced REST API Endpoints** (@since 0.3.4)
  - Extended product data retrieval with additional metadata
  - Improved API response formatting and error handling

## [0.3.3] - 2025-07-12

### Added
- **Enhanced Multilingual Support** (@since 0.3.3)
  - Multilingual configuration for `ecwid-product` and `ecwid-product-add-to-cart` blocks
  - Button text and out-of-stock text translation support
  - JavaScript multilingual configuration integration (`add_js_multilingual_config()`)
  - Runtime block translation application (`apply_block_translations()`)

### Changed
- **Block Registration**: Optimized block registration process
  - Added fallback support for older WordPress versions
  - Improved error handling for missing block metadata files
  - Improved path resolution for build vs. source directories

### Enhanced
- **New Helper Methods** (@since 0.3.3)
  - `register_single_block()` for individual block registration
  - `register_multilingual_blocks()` for multilingual system integration
  - `get_registered_blocks()`, `has_multilingual_support()`, `get_block_multilingual_config()` utility methods
  - `log_info()` and `log_error()` methods for enhanced debugging

## [0.3.2] - 2025-06-30

### Added
- **Product Lines Image Support**: Enhanced `ecwid-product-field` block with media image display for product lines
  - Media tag selection dropdown populated from available line media
  - Image size options: Small (32px), Medium (48px), Large (64px) thumbnails
  - Image position control: Before or after text content
  - Bootstrap integration for consistent styling and responsive design
- **Enhanced Media Integration**: New REST API endpoint for product line media
  - `/wp-json/peaches/v1/product-lines/{line_id}/media` - Get media with attachment info
  - Enhanced media data includes thumbnail URLs, alt text, and attachment metadata
- **Editor Preview Enhancement**: True WYSIWYG image preview in block editor
  - Real-time media tag dropdown updates based on available line media
  - Consistent image rendering between editor and frontend
- **CSS Utility Classes**: New Bootstrap-compatible size utilities
  - `.width-32`, `.width-48`, `.width-64` for consistent image sizing
  - `.height-32`, `.height-48`, `.height-64` with responsive adjustments
- **Performance Optimization**: Smart media fetching
  - Media only loaded when image display is enabled and media tag is selected
  - Efficient API calls prevent unnecessary data transfer

### Enhanced
- **Product Field Block**: Extended with comprehensive image support while maintaining full backward compatibility
- **Media Tag System**: Leveraged existing product line media infrastructure for seamless integration
- **Bootstrap Settings Integration**: Image alignment managed through existing Bootstrap utility panels
- **Template Architecture**: Clean separation between structural rendering (save.js) and dynamic data binding (view.js)

## [0.3.1] - 2025-06-29

### Added
- **Product Lines Support in Product Field Block**: Extended the existing `ecwid-product-field` block to display product lines with multiple display modes (@since 0.3.1)
- **Product Lines REST API**: New REST endpoints for fetching product lines and line types
  - `/wp-json/peaches/v1/product-lines/{product_id}` - Get product lines for a specific product
  - `/wp-json/peaches/v1/line-types` - Get all available line types
- **Product Lines Display Modes**: Multiple visual styles for displaying product lines
  - Badges - Bootstrap badge styling with custom colors
  - Pills - Rounded pill badges
  - List - Unordered list with optional descriptions
  - Inline - Comma-separated or custom separator format
- **Product Lines Filtering**: Advanced filtering options for `lines_filtered` field type
  - Filter by line type (fragrance, color_scheme, design_collection, etc.)
  - Maximum lines limit (0 = unlimited)
  - Show/hide line descriptions
  - Custom separator for inline mode
- **Enhanced Color Support**: Product line badges and pills respect block editor color settings
- **Multilingual Support**: Product lines integration with existing language detection utilities

### Changed
- **Product Field Block**: Enhanced with comprehensive product lines support while maintaining backward compatibility
- **REST API Architecture**: Added product lines manager dependency injection for better maintainability
- **Editor Experience**: True WYSIWYG rendering - product lines display exactly as they appear on frontend
- **Code Consistency**: Standardized fetch patterns across blocks for improved maintainability

## [0.3.0] - 2025-06-28

### Added
- **Block Independence**: All Ecwid product blocks can now work without `ecwid-product-detail` ancestor
- **Smart Product Selection**: Automatic product selection UI when no parent context exists
- **Shared Utilities**: `useEcwidProductData` hook and `ProductSelectionPanel` component for consistent behavior
- **Translation System**: Full WordPress i18n support with exportable `__()` function for view.js files
- **Global Language Functions**: `getCurrentLanguageForAPI()` and `getLanguageAwareApiUrl()` available site-wide

### Enhanced
- **Modern WPML API**: Migrated from deprecated `ICL_LANGUAGE_CODE` to filter-based API
- **Language Detection**: Simplified logic with cookie support for both Polylang and WPML
- **Field Value Extraction**: Dynamic language-aware product attribute handling
- **API Consistency**: Standardized on query string language parameters (`?lang=xx`)

### Fixed
- **Translation Issues**: Resolved webpack import errors for `@wordpress/i18n` in view.js files
- **Language Values**: Fixed hardcoded `.nl` values, now uses current language dynamically
- **Product Selection UX**: Improved visual feedback and error handling

### Breaking Changes
- Removed `ancestor` requirement from all product block `block.json` files
- API language parameters now use query string only (no custom headers)

### Migration
- Existing implementations continue working unchanged
- New standalone usage automatically available
- No action required for current users

## [0.2.7] - 2025-06-23

### Added
- Product hover image functionality with media tag selection
- Template functions for product media URL retrieval
- Enhanced product card interactions with smooth transitions

## [0.2.6] - 2025-06-17

### Added
- Gallery tags and ingredient translations fixes
- Easy create new product settings from table functionality
- Product Media Manager with enhanced functionality (@since 0.2.6)
- REST API endpoints for product data (@since 0.2.5)
- Comprehensive product media management system
- Product related products block
- Product description blocks with multiple types
- Enhanced media tags system with category organization

### Changed
- Improved admin interface with tabbed navigation
- Enhanced product settings management
- Better error handling and validation throughout
- Optimized media management workflow

### Fixed
- Product settings table column titles
- Multilingual site compatibility for product links
- Block patterns integration issues
- Gallery image block formatting
- Add to cart button functionality in product cards
- Gallery tags and ingredient translations
- Column title in product settings table
- Patterns using block updates
- Ecwid-product-gallery-image block format fixes
- Product links with multilingual sites

## [0.2.5] - 2025-06-17

### Added
- REST API handler class (@since 0.2.5)
- Consolidated REST API endpoints for plugin functionality
- Enhanced API integration for product data retrieval
- Improved product ingredients API endpoints

## [0.2.4] - 2025-06-17

### Added
- Product descriptions management system (@since 0.2.4)
- Multiple description types (usage, ingredients, care, warranty, features, technical, custom)
- Enhanced admin scripts for product descriptions
- WordPress editor integration for description content
- Description validation and sanitization

### Changed
- Enhanced product settings manager with descriptions support
- Improved admin interface for managing product content
- Better organization of product-related functionality

## [0.2.3] - 2025-06-17

### Fixed
- Product-specific bug fixes and improvements
- Enhanced block compatibility
- Improved error handling in various components

## [0.2.2] - 2025-06-17

### Changed
- **BREAKING**: Renamed `master_ingredient` post type to `product_ingredient` (@since 0.2.0)
- Database migration from `master_ingredient` to `product_ingredient`
- Updated all references from "master" terminology to "library" for better clarity
- Enhanced ingredients library management

### Fixed
- Database consistency issues with ingredient management
- User meta updates for ingredient-related screen options
- Cache clearing and rewrite rules updates

## [0.2.1] - 2025-06-17

### Added
- Product Media Manager class (@since 0.2.1)
- Enhanced media management with multiple source types
- Support for videos, audio, and documents
- Media tag system with category organization (primary, secondary, reference, media)
- Better fallback handling for missing media
- Frontend and admin script enqueuing for media management

### Changed
- **BREAKING**: Removed product groups post type in favor of product lines taxonomy
- Enhanced media tags system with better organization
- Performance optimizations for large product catalogs
- Improved media handling across the plugin

### Removed
- Product Groups post type (migrated to Product Lines taxonomy)
- Legacy product group assignments and metadata

## [0.2.0] - 2025-06-17

### Added
- Database Migration system (@since 0.2.0)
- Product Settings Manager with comprehensive interface (@since 0.2.0)
- Media Tags Manager for organized media management (@since 0.2.0)
- Utilities class with enhanced error handling (@since 0.2.0)
- Enhanced Navigation system (@since 0.2.0)
- Block Patterns system (@since 0.2.0)
- Product Lines management (replacing Product Groups)
- Ingredients Library with multilingual support
- Settings Manager with improved configuration options
- Enhanced Ecwid API integration
- Comprehensive error logging and debugging

### Changed
- **BREAKING**: Complete plugin architecture refactor
- **BREAKING**: Renamed `product_ingredients` post type to `product_settings`
- Improved admin interface with tabbed navigation
- Enhanced security measures throughout the plugin
- Better error handling and validation
- Modernized code structure and organization

### Removed
- Legacy product ingredients post type (renamed to product_settings)
- Old admin interface components
- Deprecated functionality and legacy code

## [0.1.2] - 2024-XX-XX

### Added
- Rewrite rules for custom product URLs
- Enhanced multilingual support with Polylang and WPML
- Redis caching support for improved performance
- Product template management system

### Changed
- Improved product data handling and validation
- Enhanced caching system implementation
- Better URL structure for product pages

## [0.1.0] - 2024-XX-XX

### Added
- Initial release with core Gutenberg blocks
- Bootstrap ECWID Product block (@version 0.1.0)
- Bootstrap ECWID Category block (@version 0.1.0)
- Basic product and category display blocks
- Simple product management interface
- Bootstrap 5 integration
- WordPress 6.7+ compatibility
- PHP 7.4+ support

### Features
- Responsive Bootstrap grid system integration
- Gutenberg block editor support
- Ecwid Shopping Cart plugin integration
- WordPress media library integration
- Multilingual support foundation

---

## Migration Notes

### 0.2.0 Breaking Changes
- **Post Type Rename**: `product_ingredients` → `product_settings`
- **Architecture Change**: Complete plugin refactor with new class structure
- **Admin Interface**: New tabbed interface replaces old admin pages

### 0.2.1 Breaking Changes
- **Post Type Removal**: `product_groups` post type removed
- **Taxonomy Migration**: Product groups migrated to `product_line` taxonomy

### 0.2.2 Breaking Changes
- **Post Type Rename**: `master_ingredient` → `product_ingredient`
- **Terminology Update**: "Master" references changed to "Library"

## Support

For detailed upgrade instructions and migration guides, please refer to the [README.md](README.md) file.

For support, bug reports, or feature requests:
- Create an issue on [GitHub](https://github.com/owlot/peaches-bootstrap-ecwid-blocks)
- Check the WordPress.org support forums
