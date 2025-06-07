# Peaches Bootstrap Ecwid Blocks

Create beautiful, responsive Ecwid e-commerce pages with modern Bootstrap-styled Gutenberg blocks and advanced product management.

## Description

Peaches Bootstrap Ecwid Blocks transforms your Ecwid e-commerce store with modern, Bootstrap-styled Gutenberg blocks and comprehensive product management tools. Build custom product pages, manage ingredients libraries, organize product lines, and create stunning e-commerce experiences with the WordPress block editor.

### Features

- **Modern Gutenberg Blocks**: Professional Bootstrap-styled blocks for product displays, categories, and detailed product pages
- **Advanced Product Management**: Comprehensive product settings, ingredients library, and media management
- **Product Lines Organization**: Group products by collections, fragrances, color schemes, or design series
- **Named Media System**: Organize product media with tags like hero images, size charts, and instruction manuals
- **Multilingual Support**: Full compatibility with Polylang and WPML for international stores
- **Custom Product Detail Pages**: Create SEO-friendly product pages with custom URLs and metadata
- **Redis Caching**: High-performance caching with Redis support for fast page loads
- **Bootstrap 5 Integration**: Modern, responsive designs with full Bootstrap 5 compatibility
- **Block Patterns**: Pre-built patterns for quick page creation

### Available Blocks

#### Product Display Blocks

**Bootstrap ECWID Product**
- Display individual products with custom styling
- Automatic product data fetching from Ecwid API
- Responsive card layouts with hover effects
- Bootstrap grid integration

**Bootstrap ECWID Category**
- Display product categories in grid layouts
- Configurable responsive columns (2-4 per row)
- Automatic category thumbnails and navigation
- Loading states and smooth interactions

#### Product Detail Blocks

**Bootstrap ECWID Product Detail Template**
- Complete product page template with context sharing
- Test product preview in editor
- SEO-friendly product URLs
- Bootstrap-styled layout containers

**Bootstrap ECWID Product Field**
- Display specific product information (title, price, description, custom fields)
- Configurable HTML tags for semantic markup
- Dynamic content updates
- Support for custom product attributes

**Bootstrap ECWID Product Images**
- Product image gallery with thumbnails
- Multiple layout options (standard, thumbnails below/side)
- Configurable image sizes and quantities
- Responsive design with touch gestures

**Bootstrap ECWID Product Gallery Image**
- Display specific media based on media tags
- Support for images, videos, audio, and documents
- Fallback options for missing media
- Media type-specific controls (autoplay, loop, etc.)

**Bootstrap ECWID Product Add to Cart**
- Customizable add to cart buttons
- Quantity selectors with validation
- Out of stock handling
- Bootstrap button styling with theme colors

**Bootstrap ECWID Product Ingredients**
- Display product ingredients in Bootstrap accordion
- Multilingual ingredient support
- Rich text descriptions
- Expandable/collapsible interface

### Product Management System

#### Product Configuration
- Link WordPress posts to specific Ecwid products via ID or SKU
- Organize products with tags and product lines
- Manage product-specific media and ingredients
- Custom product attributes and metadata

#### Ingredients Library
- Create reusable ingredient entries
- Multilingual ingredient descriptions
- Central library for consistent information
- Easy assignment to multiple products

#### Product Lines
- Organize products into collections (fragrances, color schemes, etc.)
- Line-specific media and descriptions
- Hierarchical organization
- Type classification system

#### Media Tags System
- Predefined media tags (hero_image, size_chart, product_video, etc.)
- Support for multiple media types (image, video, audio, document)
- Flexible media sources:
  - WordPress media library uploads
  - External URLs
  - Direct Ecwid product images
- Category-based organization (primary, secondary, reference, media)

## Requirements

- WordPress 6.7 or later
- PHP 7.4 or later
- [Ecwid Shopping Cart](https://wordpress.org/plugins/ecwid-ecommerce-shopping-cart/) plugin
- Bootstrap 5 theme or framework (recommended)
- Redis server (optional, for enhanced caching)

## Installation

1. Upload the `peaches-bootstrap-ecwid-blocks` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Install and activate the Ecwid Shopping Cart plugin
4. Configure your Ecwid store ID and API credentials
5. Visit **Peaches > Ecwid Products** to configure advanced settings

## Usage

### Block Editor

#### Creating Product Displays

1. **Add Product Blocks**: Insert ECWID Product or Category blocks
2. **Configure Layout**: Set responsive grid options (xs, sm, md, lg, xl, xxl)
3. **Product Selection**: Choose specific products by ID or let blocks auto-populate
4. **Styling**: Apply Bootstrap classes and custom styling

#### Building Product Detail Pages

1. **Create Product Template**: Add the Product Detail Template block
2. **Add Components**: Insert Product Field, Images, Add to Cart, and Ingredients blocks
3. **Configure Fields**: Select which product information to display
4. **Test Preview**: Use test product feature to preview layouts
5. **Publish**: Pages automatically handle product URLs and SEO

#### Using Block Patterns

Quick-start with pre-built patterns:
- **Categories Row**: 2-4 category display per row
- **Products Carousel**: Responsive product slider
- **Product Detail Page**: Complete product page layout

### Product Management

#### Setting Up Product Configuration

1. Go to **Peaches > Ecwid Products > Product Configuration**
2. Click **Add New Product Configuration**
3. Enter Ecwid Product ID or SKU
4. Configure ingredients, media, lines, and tags
5. Save to apply settings

#### Managing Ingredients Library

1. Navigate to **Peaches > Ecwid Products > Ingredients Library**
2. Create ingredient entries with descriptions
3. Add multilingual translations if needed
4. Assign ingredients to product configurations

#### Organizing Product Lines

1. Access **Peaches > Ecwid Products > Product Lines**
2. Create new product lines with types (fragrance, color_scheme, etc.)
3. Add line-specific media and descriptions
4. Assign products to appropriate lines

#### Setting Up Media Tags

1. Visit **Peaches > Ecwid Products > Media Tags**
2. Create or edit media tags for consistent organization
3. Define expected media types (image, video, audio, document)
4. Organize tags by categories (primary, secondary, reference, media)

### Frontend Display

#### Custom Product URLs

The plugin creates SEO-friendly URLs for product pages:
- Single language: `/shop/product-slug/`
- Multilingual: `/en/shop/product-slug/`, `/fr/boutique/product-slug/`

#### Automatic Product Data

- Product information loads automatically from Ecwid
- Real-time price and inventory updates
- Multilingual content based on configured languages
- Cached for optimal performance

### Advanced Configuration

#### Caching Settings

Access **Peaches > Ecwid Blocks > General** to configure:
- **Cache Duration**: Set API response cache time (1-1440 minutes)
- **Redis Caching**: Enable Redis for improved performance
- **Debug Mode**: Enable detailed logging for troubleshooting

#### Template Settings

Configure product page behavior:
- **Product Template Page**: Choose custom template page
- **Breadcrumbs**: Enable/disable navigation breadcrumbs
- **URL Structure**: Customize product URL patterns

## Developer Information

### Adding Custom Blocks

Extend the plugin with custom product blocks:

```php
// Register block with product context support
register_block_type('your-namespace/product-block', array(
    'usesContext' => array('peaches/testProductData'),
    'render_callback' => 'your_block_render_callback'
));
```

### Template Functions

Use template functions in your theme:

```php
<?php
// Get product media by tag
$hero_image = peaches_get_product_media_url($product_id, 'hero_image', 'large');
if ($hero_image) {
    echo '<img src="' . esc_url($hero_image) . '" alt="Product Hero Image">';
}

// Display product media with fallbacks
peaches_the_product_media($product_id, 'size_chart', 'medium', array(
    'class' => 'img-fluid',
    'loading' => 'lazy'
));

// Get all available media tags
$media_tags = peaches_get_available_media_tags();
?>
```

### REST API Endpoints

Access product data via REST API:

```javascript
// Get product ingredients
fetch('/wp-json/peaches/v1/product-ingredients/123')
    .then(response => response.json())
    .then(data => console.log(data.ingredients));

// Get product media by tag
fetch('/wp-json/peaches/v1/product-media/123/tag/hero_image')
    .then(response => response.json())
    .then(data => console.log(data.data.url));
```

### Hooks and Filters

**Filters:**
```php
// Modify supported block list
add_filter('peaches_ecwid_supported_blocks', function($blocks) {
    $blocks[] = 'your-namespace/custom-block';
    return $blocks;
});

// Customize product URL structure
add_filter('peaches_ecwid_product_url', function($url, $product, $language) {
    // Custom URL logic
    return $url;
}, 10, 3);

// Modify media tag validation
add_filter('peaches_media_tag_validation', function($is_valid, $tag_key, $media_url) {
    // Custom validation logic
    return $is_valid;
}, 10, 3);
```

**Actions:**
```php
// Hook into product save
add_action('peaches_product_settings_saved', function($post_id, $product_data) {
    // Custom logic after product settings save
}, 10, 2);

// Custom ingredient processing
add_action('peaches_ingredient_updated', function($ingredient_id, $data) {
    // Process ingredient updates
}, 10, 2);
```

### JavaScript Events

Listen for block interactions:

```javascript
// Product data loaded
document.addEventListener('peaches:productLoaded', function(event) {
    console.log('Product loaded:', event.detail.product);
});

// Media tag selected
document.addEventListener('peaches:mediaTagSelected', function(event) {
    console.log('Media tag:', event.detail.tagKey);
    console.log('Media data:', event.detail.mediaData);
});
```

## Styling and Customization

### Bootstrap Integration

The plugin leverages Bootstrap 5 classes extensively:

```css
/* Customize product cards */
.wp-block-peaches-ecwid-product .card {
    transition: transform 0.2s ease-in-out;
    border: 1px solid #dee2e6;
}

.wp-block-peaches-ecwid-product .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Style add to cart buttons */
.wp-block-peaches-ecwid-product-add-to-cart .btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    font-weight: 600;
}

/* Customize ingredients accordion */
.wp-block-peaches-ecwid-product-ingredients .accordion-item {
    border: 1px solid #dee2e6;
    margin-bottom: 0.5rem;
}
```

### Media Gallery Customization

```css
/* Thumbnail gallery styling */
.product-gallery.thumbnails-below-gallery .thumbnail-image {
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.product-gallery.thumbnails-below-gallery .thumbnail-image:hover {
    opacity: 0.8;
}

.product-gallery.thumbnails-below-gallery .thumbnail-image.active {
    border: 2px solid #0d6efd;
}
```

### Responsive Breakpoints

Configure responsive behavior using Bootstrap grid:

```php
// In block attributes
'xs' => array('rowCols' => 2, 'gapX' => '3'),
'md' => array('rowCols' => 3, 'gapX' => '4'),
'lg' => array('rowCols' => 4, 'gapX' => '5')
```

## Performance Optimization

### Caching Configuration

**WordPress Transients** (Default):
- Automatic cache management
- 60-minute default duration
- No additional server requirements

**Redis Caching** (Recommended):
```php
// wp-config.php
define('WP_REDIS_HOST', '127.0.0.1');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_PASSWORD', 'your-password'); // Optional
define('WP_REDIS_DATABASE', 0); // Optional
```

### Database Optimization

- Efficient product queries with proper indexing
- Cached ingredient and media lookups
- Optimized term relationships
- Minimal database calls per page load

### Asset Loading

- Selective script loading based on block usage
- Optimized CSS with minimal redundancy
- Progressive image loading for galleries
- Efficient block registration system

## Multilingual Support

### Polylang Integration

- Automatic language detection and URL generation
- Translated ingredient names and descriptions
- Language-specific shop paths
- Proper hreflang implementation

### WPML Integration

- Full WPML compatibility
- String translation for ingredients
- Language-specific configuration
- Advanced translation management

### Custom Language Configuration

```php
// Set custom shop paths per language
$multilingual_settings = array(
    'en' => 'shop',
    'fr' => 'boutique',
    'de' => 'geschaeft',
    'es' => 'tienda'
);
```

## Security

### Data Validation

- Comprehensive input sanitization
- Nonce verification for all AJAX requests
- Capability checks for admin functions
- SQL injection prevention

### File Upload Security

- Media type validation
- File size restrictions
- Sanitized file names
- Secure upload handling

### API Security

- Rate limiting for Ecwid API calls
- Secure credential storage
- Input validation for product IDs
- Error handling without data exposure

## Troubleshooting

### Common Issues

**Blocks not appearing:**
- Ensure Ecwid Shopping Cart plugin is active
- Check WordPress version compatibility (6.7+)
- Verify user permissions for block editor

**Product data not loading:**
- Confirm Ecwid store ID is configured correctly
- Check API credentials and permissions
- Review cache settings and clear if needed
- Enable debug mode for detailed error logs

**Media not displaying:**
- Verify media tag configuration
- Check file permissions and URLs
- Ensure media types match tag expectations
- Review fallback settings

**Performance issues:**
- Enable Redis caching if available
- Adjust cache duration settings
- Optimize image sizes and formats
- Review debug logs for slow queries

### Debug Mode

Enable debug logging in **Peaches > Ecwid Blocks > General**:
- Detailed API call logging
- Block rendering information
- Cache performance metrics
- Error tracking and reporting

### Cache Management

Clear caches when needed:
- **Admin Interface**: Use "Clear Cache" button in settings
- **WP-CLI**: `wp transient delete --all`
- **Redis**: Use Redis CLI or admin tools

## Migration and Updates

### From Previous Versions

The plugin includes automatic database migration:
- Post type transitions (product_ingredients → product_settings)
- Taxonomy updates (product_groups → product_lines)
- Data structure modernization
- Backward compatibility maintenance

### Backup Recommendations

Before major updates:
1. **Database Backup**: Full WordPress database backup
2. **Media Backup**: Product images and uploaded files
3. **Configuration Export**: Plugin settings and configurations
4. **Test Environment**: Verify updates in staging environment

## Changelog

### Version 0.2.1
- Enhanced media management with multiple source types
- Added support for videos, audio, and documents
- Improved media tag system with category organization
- Better fallback handling for missing media
- Performance optimizations for large product catalogs

### Version 0.2.0
- Complete refactor with improved architecture
- Added Product Lines management (replacing Product Groups)
- Enhanced Ingredients Library with multilingual support
- New Media Tags system for organized media management
- Improved admin interface with tabbed navigation
- Better error handling and validation
- Enhanced security measures

### Version 0.1.2
- Added rewrite rules for custom product URLs
- Improved multilingual support with Polylang and WPML
- Enhanced caching system with Redis support
- Better product data handling and validation
- Added product template management

### Version 0.1.0
- Initial release with core Gutenberg blocks
- Basic product and category display blocks
- Simple product management interface
- Bootstrap 5 integration

## Support

For support, bug reports, or feature requests:
- Create an issue on GitHub
- Check the WordPress.org support forums
- Review the documentation wiki
- Contact support through **Peaches > Ecwid Blocks** settings page

## License

GPL v2 or later - see LICENSE file for details.

## Disclaimer

**Use at Your Own Risk:** This plugin is provided "as is" without warranty of any kind, either expressed or implied. The authors and contributors disclaim all warranties with regard to this software including all implied warranties of merchantability and fitness. In no event shall the authors be liable for any special, direct, indirect, or consequential damages or any damages whatsoever resulting from loss of use, data or profits, whether in an action of contract, negligence or other tortious action, arising out of or in connection with the use or performance of this software.

**Important Notes:**
- Always test thoroughly in a staging environment before deploying to production
- Create complete backups before installation or updates
- Verify compatibility with your specific WordPress and Ecwid setup
- Monitor your site after installation for any conflicts or issues
- The plugin modifies product URLs and may affect SEO if not properly configured
- Redis caching requires proper server configuration and maintenance

**Recommended Precautions:**
- Backup your database before activation
- Test all functionality in a development environment
- Review all settings before applying changes
- Monitor site performance after installation
- Keep regular backups of your product configurations and media
- Ensure Ecwid API credentials are secure and regularly rotated

## Credits

Developed by Peaches.io for the WordPress and Ecwid community.

**AI Development Notice:** This plugin was developed with assistance from AI technology (Claude by Anthropic) for code optimization, documentation, and best practices implementation. All code has been thoroughly reviewed, tested, and validated to ensure quality, security, and WordPress standards compliance.

Special thanks to:
- WordPress Core Team for Gutenberg and Interactivity API
- Ecwid team for the e-commerce platform and API
- Bootstrap team for the styling framework
- Redis team for high-performance caching
- Polylang and WPML teams for multilingual standards
- Anthropic's Claude AI for development assistance and code optimization
