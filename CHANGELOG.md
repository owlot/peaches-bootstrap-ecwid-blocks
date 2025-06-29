# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
# Changelog

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
