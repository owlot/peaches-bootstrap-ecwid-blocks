# Peaches Bootstrap Ecwid Blocks

Create beautiful, responsive Ecwid e-commerce pages with modern Bootstrap-styled Gutenberg blocks and advanced product management.

## 🆕 What's New in 0.3.4

### **Category Products Block**
Our newest block brings powerful category-based product displays to your Gutenberg editor:

- **🏷️ Universal Category Support**: Display products from any Ecwid category
- **⭐ Featured Products**: Special support for Store Front Page featured products
- **🎨 Full Customization**: Complete control over appearance and behavior
- **📱 Responsive Design**: Bootstrap-powered responsive layouts
- **🌍 Multilingual Ready**: Translation support for international stores
- **🚀 High Performance**: Efficient API calls with intelligent caching
- **🔄 Carousel Compatible**: Works seamlessly in carousel blocks

Perfect for creating curated product showcases, category landing pages, and promotional displays!

## Description

Peaches Bootstrap Ecwid Blocks transforms your Ecwid e-commerce store with modern, Bootstrap-styled Gutenberg blocks and comprehensive product management tools. Build custom product pages, manage ingredients libraries, organize product lines, and create stunning e-commerce experiences with the WordPress block editor.

### Key Features

- **🎨 Modern Gutenberg Blocks**: Professional Bootstrap-styled blocks for product displays, categories, and detailed product pages
- **📋 Advanced Product Management**: Comprehensive product settings, ingredients library, and media management system
- **🏷️ Product Lines Organization**: Group products by collections, fragrances, color schemes, or design series
- **🖼️ Named Media System**: Organize product media with tags like hero images, size charts, and instruction manuals
- **🏪 Category Products Block**: Display products from specific Ecwid categories with full customization control
- **🌍 Multilingual Support**: Full compatibility with Polylang and WPML for international stores
- **📄 Custom Product Detail Pages**: Create SEO-friendly product pages with custom URLs and metadata
- **⚡ High Performance**: Redis caching support and optimized database queries for fast page loads
- **📱 Bootstrap 5 Integration**: Modern, responsive designs with full Bootstrap 5 compatibility
- **🎯 Block Patterns**: Pre-built patterns for quick page creation
- **📝 Product Descriptions**: Multiple description types with rich text editing
- **🔗 Related Products**: Dynamic related product displays
- **🛒 Enhanced Add to Cart**: Customizable add to cart functionality

### What's New in Version 0.3.5

- **Category Products Block**: Display products from any Ecwid category including Featured Products from Store Front Page
- **Enhanced REST API**: New comprehensive `/category-products/{category_id}` endpoint with advanced filtering
- **Category Selection**: Intuitive dropdown with automatic category name detection
- **Smart Titles**: Automatic category name display with custom title override support
- **Performance Optimizations**: Improved API calls with intelligent caching and loading states
- **Bootstrap Integration**: Seamless responsive grid integration with Bootstrap column blocks

### Available Blocks

#### Product Display Blocks

**🛍️ Bootstrap ECWID Product**
- Display individual products with custom styling
- Automatic product data fetching from Ecwid API
- Responsive card layouts with hover effects
- Bootstrap grid integration
- Add to cart button integration

**📂 Bootstrap ECWID Category**
- Display product categories with thumbnails
- Automatic category grid layouts
- Custom category selection and filtering
- Responsive Bootstrap grid system
- SEO-friendly category URLs

**📂 Category Products Block**
- Display products from specific Ecwid categories
- Featured Products support (Store Front Page)
- Category selection dropdown with real-time preview
- Configurable product limits and responsive layouts
- Full product appearance customization
- Automatic category name detection and display
- Custom title support with smart fallbacks
- Bootstrap grid integration with column blocks

**🔗 Related Products Block**
- Display related products with customizable layouts and quantity limits
- Automatic related product detection from Ecwid data
- Bootstrap grid system integration
- Responsive design with hover effects
- Product appearance customization

#### Product Detail Blocks

**📄 Bootstrap ECWID Product Detail**
- Comprehensive product information display
- Image galleries with zoom functionality
- Product descriptions and specifications
- Integrated add to cart functionality
- Responsive layout system

**🧪 Product Ingredients Block**
- Display product ingredients with full details
- Multilingual ingredient names and descriptions
- Organized ingredient library system
- Responsive grid layouts
- Enhanced typography and styling

**🖼️ Product Gallery Image Block**
- Display specific product images using media tags
- Fallback image support for missing media
- Multiple media sources (WordPress, Ecwid, URLs)
- Responsive image handling
- Alternative text and accessibility support

**📝 Product Field Block**
- Display specific product data fields
- Custom field formatting and styling
- Responsive text handling
- Integration with Ecwid product data
- Multilingual field support

**📋 Product Lines Block**
- Display organized product line information
- Product line taxonomy integration
- Responsive grid layouts
- Custom styling and formatting
- Media integration for product lines

#### Layout and Design Blocks

**🎠 Bootstrap Carousel Integration**
- Carousel-compatible versions of product blocks
- Smooth transitions and responsive behavior
- Touch/swipe support for mobile devices
- Automatic slide management
- Bootstrap carousel compatibility

## Installation

### Prerequisites

Before installing Peaches Bootstrap Ecwid Blocks, ensure you have:

- **WordPress**: 6.7.0 or later
- **PHP**: 7.4 or later (8.0+ recommended)
- **Ecwid Shopping Cart Plugin**: Latest version
- **Memory**: 256MB minimum (512MB recommended)

### Installation Methods

#### Method 1: WordPress Admin Dashboard

1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin
5. Configure Ecwid integration in **Peaches > Ecwid Blocks**

#### Method 2: Manual Installation

1. Extract the plugin files to `/wp-content/plugins/peaches-bootstrap-ecwid-blocks/`
2. Activate the plugin through the WordPress admin
3. Configure settings in **Peaches > Ecwid Blocks**

### Initial Configuration

1. **Ecwid Setup**: Ensure Ecwid Shopping Cart plugin is active and configured
2. **Store Configuration**: Verify your Ecwid store ID and settings
3. **Cache Settings**: Configure caching preferences (Redis recommended for high-traffic sites)
4. **Multilingual Setup**: Configure language settings if using Polylang or WPML

## Block Usage Examples

### **Category Products Block Usage**

**Basic Category Display:**
```html
<!-- wp:peaches/category-products {"selectedCategoryId":123,"maxProducts":6} -->
<div class="wp-block-peaches-category-products">
    <h3 class="category-products-title mb-4">Electronics</h3>
    <div class="category-products-container row">
        <!-- Product columns automatically generated -->
    </div>
</div>
<!-- /wp:peaches/category-products -->
```

**Featured Products Showcase:**
```html
<!-- wp:peaches/category-products {"selectedCategoryId":0,"customTitle":"Our Best Sellers","maxProducts":4,"showCardHoverShadow":true} -->
<div class="wp-block-peaches-category-products">
    <h3 class="category-products-title mb-4">Our Best Sellers</h3>
    <div class="category-products-container row">
        <!-- Featured product columns -->
    </div>
</div>
<!-- /wp:peaches/category-products -->
```

**Carousel Integration:**
```html
<!-- wp:peaches/bs-carousel -->
    <!-- wp:peaches/category-products {"selectedCategoryId":0,"maxProducts":8,"isInCarousel":true} /-->
<!-- /wp:peaches/bs-carousel -->
```

### **Product Detail Block**

```html
<!-- wp:peaches/ecwid-product-detail {"selectedProductId":123456} -->
<div class="wp-block-peaches-ecwid-product-detail">
    <!-- Product detail content automatically generated -->
</div>
<!-- /wp:peaches/ecwid-product-detail -->
```

### **Product Ingredients Block**

```html
<!-- wp:peaches/product-ingredients {"selectedProductId":123456,"displayMode":"grid"} -->
<div class="wp-block-peaches-product-ingredients">
    <!-- Ingredients grid automatically generated -->
</div>
<!-- /wp:peaches/product-ingredients -->
```

### **Bootstrap Grid Integration**

```html
<!-- wp:peaches/bs-row {"rowCols":3,"gutter":"4"} -->
<div class="row row-cols-1 row-cols-md-3 g-4">
    <!-- wp:peaches/bs-col -->
    <div class="col">
        <!-- wp:peaches/ecwid-product {"id":123} /-->
    </div>
    <!-- /wp:peaches/bs-col -->
</div>
<!-- /wp:peaches/bs-row -->
```

### **Advanced Customization**

```html
<!-- wp:peaches/ecwid-product {
    "id":123456,
    "showAddToCart":true,
    "buttonText":"Buy Now",
    "showCardHoverShadow":true,
    "showCardHoverJump":true,
    "hoverMediaTag":"product_hover",
    "className":"custom-product-card"
} -->
```

### **Bootstrap Attribute Examples**

```json
// Row configuration
{ 'rowCols': 4, 'gapX': '5' }

// Column configuration
{ 'colXs': 12, 'colMd': 6, 'colLg': 3 }

// Spacing configuration
{ 'paddingTop': '5', 'marginBottom': '4' }
```

## REST API Documentation

### **Category Products Endpoint**

```
GET /wp-json/peaches/v1/category-products/{category_id}
```

**Parameters:**
- `category_id` (required): Category ID (use 0 for Featured Products)
- `limit`: Number of products to return (default: 20, max: 100)
- `offset`: Pagination offset (default: 0)
- `sort_by`: Sort field ('name', 'price', 'created', 'updated')
- `sort_order`: Sort direction ('asc', 'desc')
- `enabled`: Filter enabled products only (default: true)
- `in_stock`: Filter in-stock products only
- `return_ids_only`: Return product IDs only instead of full data (default: false)

**Examples:**
```bash
# Get featured products (IDs only)
curl "/wp-json/peaches/v1/category-products/0?return_ids_only=true&limit=6"

# Get category products with full data
curl "/wp-json/peaches/v1/category-products/123?limit=20&sort_by=price&sort_order=desc"

# Get in-stock products from category
curl "/wp-json/peaches/v1/category-products/456?in_stock=true&enabled=true"
```

**Response Format:**
```json
{
  "success": true,
  "category_id": 0,
  "category_type": "featured",
  "category_name": "Store Front Page",
  "description": "Featured products displayed on the store's front page",
  "count": 4,
  "limit": 20,
  "offset": 0,
  "sort_by": "name",
  "sort_order": "asc",
  "product_ids": [123, 456, 789, 101112]
}
```

### **Product Ingredients Endpoint**

```
GET /wp-json/peaches/v1/product-ingredients/{product_id}
```

### **Related Products Endpoint**

```
GET /wp-json/peaches/v1/related-products/{product_id}
```

### **Product Data Endpoint**

```
GET /wp-json/peaches/v1/products/{product_id}
```

### **Categories Endpoint**

```
GET /wp-json/peaches/v1/categories
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

- Comprehensive input validation and sanitization
- Secure credential management (via Ecwid Shopping Cart plugin)
- Error handling without sensitive data exposure
- API request caching to reduce external calls
- Debug logging with controlled access

## Troubleshooting

### Common Issues

**Blocks not appearing:**
- Ensure Ecwid Shopping Cart plugin is active
- Check WordPress version compatibility (6.7+)
- Verify user permissions for block editor

**Product data not loading:**
- Confirm Ecwid store ID is configured correctly
- Review cache settings and clear if needed
- Enable debug mode for detailed error logs

**Category products not displaying:**
- Verify category ID exists in Ecwid store
- Check if category has enabled products
- Review REST API endpoint response for errors
- Clear cache and retry

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

**Description editor not working:**
- Check if WordPress editor is properly loaded
- Verify TinyMCE initialization in browser console
- Clear browser cache and try again
- Ensure no JavaScript conflicts with other plugins

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

## Developer Documentation

### **Category Products Block Development**

**Block Registration:**
- Block name: `peaches/category-products`
- Supports: Custom CSS classes, spacing, color settings
- Parent blocks: Any (carousel-aware rendering)
- Inner blocks: `peaches/bs-col` with `peaches/ecwid-product`

**Attributes:**
```json
{
  "selectedCategoryId": 0,
  "maxProducts": 4,
  "showTitle": true,
  "customTitle": "",
  "showAddToCart": true,
  "buttonText": "Add to cart",
  "showCardHoverShadow": true,
  "showCardHoverJump": true,
  "hoverMediaTag": "",
  "translations": {},
  "isInCarousel": false
}
```

**Hooks and Filters:**
- `peaches_category_products_query_args`: Filter API query arguments
- `peaches_category_products_title`: Filter category title display
- `peaches_category_products_render`: Filter block output

**Performance Considerations:**
- API calls are cached using WordPress transients
- Redis support for high-traffic sites
- Efficient product ID fetching with return_ids_only parameter
- Lazy loading compatible with proper markup structure

### Custom Block Development

```php
// Register custom block variation
function register_custom_product_block() {
    register_block_type('my-theme/custom-product', array(
        'render_callback' => 'render_custom_product_block',
        'attributes' => array(
            'productId' => array('type' => 'number'),
            'customStyle' => array('type' => 'string')
        )
    ));
}
add_action('init', 'register_custom_product_block');
```

### Extending Product Data

```php
// Add custom product field
function add_custom_product_field($product_data, $product_id) {
    $product_data['custom_field'] = get_custom_product_data($product_id);
    return $product_data;
}
add_filter('peaches_product_data', 'add_custom_product_field', 10, 2);
```

### Custom Media Tags

```php
// Register custom media tag
function register_custom_media_tags($tags) {
    $tags['custom_gallery'] = array(
        'name' => 'Custom Gallery',
        'description' => 'Custom product gallery images',
        'category' => 'primary',
        'expectedMediaType' => 'image',
        'required' => false
    );
    return $tags;
}
add_filter('peaches_media_tags', 'register_custom_media_tags');
```

## Support

For support, bug reports, or feature requests:
- Create an issue on [GitHub](https://github.com/owlot/peaches-bootstrap-ecwid-blocks)
- Check the WordPress.org support forums
- Review the documentation

## License

GPL v3 or later - see [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history and release notes.

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
