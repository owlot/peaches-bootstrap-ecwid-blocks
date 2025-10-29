# Google Shopping & Google Tag Manager Integration

This document describes the Google Shopping structured data and Google Tag Manager (GTM) integration for Peaches Ecwid product pages.

## Table of Contents

1. [Overview](#overview)
2. [Google Shopping Product Schema](#google-shopping-product-schema)
3. [Google Tag Manager Events](#google-tag-manager-events)
4. [Setup Instructions](#setup-instructions)
5. [Testing & Validation](#testing--validation)
6. [Customization](#customization)
7. [Troubleshooting](#troubleshooting)

---

## Overview

The Munay-Ki webshop now includes automatic Google Shopping optimization through:

- **Product Schema (JSON-LD)** - Structured data markup for rich search results
- **GTM Data Layer Events** - Enhanced e-commerce tracking for product views and cart actions
- **Open Graph & Twitter Cards** - Social media sharing optimization

All of this is automatically generated when using the `ecwid-product-detail` Gutenberg block.

---

## Google Shopping Product Schema

### What is Product Schema?

Product Schema is structured data that helps Google understand your product information. This enables:

- **Rich Search Results** - Product ratings, prices, and availability in search results
- **Google Shopping Integration** - Direct product feed to Google Merchant Center
- **Better SEO** - Improved visibility in search engines

### Automatically Included Data

The system automatically generates JSON-LD structured data for each product, including:

```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Product Name",
  "description": "Product description...",
  "url": "https://example.com/shop/product-slug",
  "image": ["image1.jpg", "image2.jpg"],
  "sku": "PRODUCT-SKU",
  "brand": {
    "@type": "Brand",
    "name": "Brand Name"
  },
  "offers": {
    "@type": "Offer",
    "url": "https://example.com/shop/product-slug",
    "priceCurrency": "EUR",
    "price": "29.95",
    "availability": "https://schema.org/InStock",
    "seller": {
      "@type": "Organization",
      "name": "Your Shop Name"
    }
  }
}
```

### Fields Included

| Field | Source | Notes |
|-------|--------|-------|
| `name` | Ecwid product name | Required |
| `description` | Ecwid product description | HTML stripped |
| `url` | Current product URL | Canonical URL |
| `image` | Product images | All gallery images included |
| `sku` | Product SKU | If available in Ecwid |
| `brand` | Product attributes or site name | Looks for "brand" or "merk" attribute |
| `price` | Product price | From Ecwid |
| `priceCurrency` | Extracted from formatted price | Defaults to EUR |
| `availability` | Product stock status | InStock, OutOfStock, or PreOrder |

### Brand Detection

The system looks for brand information in this order:

1. Product attribute named "brand" (case-insensitive)
2. Product attribute named "merk" (Dutch for brand)
3. Falls back to your WordPress site name

To set a custom brand per product, add a product attribute in Ecwid called "Brand".

### Availability Detection

The system intelligently detects product availability:

- **InStock** - Product has stock or unlimited quantity
- **PreOrder** - Product is out of stock but enabled for purchase (pre-order/backorder)
- **OutOfStock** - Product is out of stock and disabled

**Pre-order Detection:**
When a product is:
- ✅ Enabled in Ecwid
- ❌ Out of stock (quantity = 0)
- ✅ Still purchasable

The schema will show `"availability": "https://schema.org/PreOrder"` instead of `OutOfStock`.

**Debug Mode:**
Enable WordPress debug logging to see availability detection:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for entries like:
```
[Product Schema] Product #12345 availability: inStock=false, unlimited=false, enabled=true, quantity=0, available_for_purchase=true
```

---

## Google Tag Manager Events

### Overview

The integration pushes two key events to the GTM data layer:

1. **Product View** - When a user views a product detail page
2. **Add to Cart** - When a user adds a product to their cart

### Product View Event

Automatically triggered when a product detail page loads.

**Event Structure:**

```javascript
{
  "event": "productView",
  "ecommerce": {
    "detail": {
      "products": [{
        "id": 12345,
        "name": "Product Name",
        "price": 29.95,
        "brand": "Brand Name",
        "category": "Category Name", // May be empty if not provided by Ecwid
        "variant": "SKU-123"
      }]
    }
  }
}
```

**Note:** The `category` field may be empty if the product data from Ecwid doesn't include category names. This is a known limitation that avoids additional API calls which could slow down page loads.

### Add to Cart Event

Triggered when a user clicks the "Add to Cart" button in the `ecwid-product-add-to-cart` block.

**Event Structure:**

```javascript
{
  "event": "addToCart",
  "ecommerce": {
    "add": {
      "products": [{
        "id": 12345,
        "name": "Product Name",
        "price": 29.95,
        "brand": "Brand Name",
        "category": "Category Name", // May be empty if not provided by Ecwid
        "variant": "SKU-123",
        "quantity": 1
      }]
    }
  }
}
```

**Note:** The `category` field may be empty if the product data from Ecwid doesn't include category names.

---

## Setup Instructions

### 1. Enable Product Schema (Automatic)

Product Schema is automatically enabled when using the `ecwid-product-detail` block. No configuration needed!

### 2. Set Up Google Tag Manager

**Step 1: Create GTM Account**

1. Go to [Google Tag Manager](https://tagmanager.google.com/)
2. Create an account if you don't have one
3. Create a container for your website
4. Copy the GTM container code

**Step 2: Install GTM on WordPress**

Option A - Using a Plugin (Recommended):
1. Install "Google Tag Manager for WordPress" plugin
2. Enter your GTM container ID (GTM-XXXXXXX)
3. Save settings

Option B - Manual Installation:
1. Add GTM code to your theme's `header.php` (after `<head>`)
2. Add noscript version after `<body>`

**Step 3: Configure GTM Tags**

1. In GTM, go to **Tags** → **New**
2. Create tags for each event type:

#### Product View Tag

- **Tag Type:** Google Analytics - Universal Analytics / GA4 Event
- **Track Type:** Event
- **Category:** Ecommerce
- **Action:** Product View
- **Trigger:** Custom Event → `productView`

#### Add to Cart Tag

- **Tag Type:** Google Analytics - Universal Analytics / GA4 Event
- **Track Type:** Event
- **Category:** Ecommerce
- **Action:** Add to Cart
- **Trigger:** Custom Event → `addToCart`

**Step 4: Set Up Enhanced Ecommerce (GA4)**

For Google Analytics 4:

1. In GTM, create a **Google Analytics: GA4 Event** tag
2. **Event Name:** `view_item` (for product views)
3. **Event Parameters:**
   - Add parameter: `items` → `{{Ecommerce Items}}`
4. **Trigger:** Custom Event → `productView`

Repeat for `add_to_cart` event.

### 3. Configure Google Merchant Center (Optional)

For Google Shopping ads:

1. Go to [Google Merchant Center](https://merchants.google.com/)
2. Create/verify your account
3. Add your website
4. The Product Schema data will be automatically detected
5. Submit your product feed

---

## Testing & Validation

### Test Product Schema

**Method 1: Google Rich Results Test**

1. Go to [Rich Results Test](https://search.google.com/test/rich-results)
2. Enter your product page URL
3. Verify the Product schema is detected
4. Check for any errors or warnings

**Method 2: Schema.org Validator**

1. Go to [Schema Markup Validator](https://validator.schema.org/)
2. Enter your product page URL
3. Verify all fields are correctly populated

### Test GTM Events

**Method 1: GTM Preview Mode**

1. In GTM, click **Preview**
2. Enter your product page URL
3. Navigate to a product page
4. Check that `productView` event fires
5. Click "Add to Cart"
6. Check that `addToCart` event fires with correct data

**Method 2: Browser Console**

1. Open your product page
2. Open browser DevTools (F12)
3. Go to Console tab
4. Type: `dataLayer`
5. Inspect the data layer array for events

**Method 3: Google Tag Assistant**

1. Install [Tag Assistant Chrome Extension](https://chrome.google.com/webstore/detail/tag-assistant-legacy-by-g/kejbdjndbnbjgmefkgdddjlbokphdefk)
2. Visit your product page
3. Click Tag Assistant icon
4. Verify tags are firing correctly

### Check SEO Meta Tags

View page source and verify these tags are present:

```html
<!-- Canonical URL -->
<link rel="canonical" href="https://example.com/shop/product-slug" />

<!-- Product Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  ...
}
</script>

<!-- Open Graph -->
<meta property="og:title" content="Product Name" />
<meta property="og:type" content="product" />
<meta property="og:image" content="product-image.jpg" />

<!-- GTM Data Layer -->
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({...});
</script>
```

---

## Customization

### Custom Brand Names

To set a custom brand per product:

1. In Ecwid admin, go to **Catalog** → **Products**
2. Edit the product
3. Go to **Attributes** section
4. Add attribute: `name: "Brand"`, `value: "Your Brand Name"`
5. Save product

### Custom Categories in GTM Events

Categories are automatically pulled from Ecwid. To ensure accurate category tracking:

1. Properly categorize products in Ecwid
2. Use meaningful category names
3. Avoid nested categories (only the first category is used)

### Extend GTM Events

To add custom data to GTM events, you can hook into the product data. Example:

**File:** `wordpress/peaches-bootstrap-ecwid-blocks/includes/class-rewrite-manager.php`

Look for the `add_gtm_product_view()` method and add custom fields:

```php
$gtm_product = array(
    'event' => 'productView',
    'ecommerce' => array(
        'detail' => array(
            'products' => array(
                array(
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => !empty($product->price) ? $product->price : 0,
                    'brand' => get_bloginfo('name'),
                    'category' => !empty($product->categoryIds) && is_array($product->categoryIds) ?
                        $this->get_category_name($product->categoryIds[0]) : '',
                    'variant' => !empty($product->sku) ? $product->sku : '',
                    // Add your custom fields here
                    'customField' => 'Custom Value',
                )
            )
        )
    )
);
```

### Filter Currency Code

The system auto-detects currency from product data. To force a specific currency, modify the `extract_currency_code()` method:

```php
private function extract_currency_code($product) {
    // Force EUR for all products
    return 'EUR';
}
```

---

## Troubleshooting

### Product Schema Not Showing

**Problem:** Rich results test doesn't find schema

**Solutions:**
1. Check if the `ecwid-product-detail` block is used on the page
2. View page source and verify `<script type="application/ld+json">` exists
3. Check for PHP errors in WordPress debug log
4. Clear all caches (WordPress, CDN, browser)

### GTM Events Not Firing

**Problem:** Events don't appear in GTM preview

**Solutions:**
1. Verify GTM container code is installed correctly
2. Check browser console for JavaScript errors
3. Ensure `window.dataLayer` exists (type in console)
4. Verify the `ecwid-product-add-to-cart` block is present
5. Check if Ecwid JavaScript API is loaded

### Missing Product Images in Schema

**Problem:** Images array is empty in schema

**Solutions:**
1. Verify product has images in Ecwid
2. Check if `thumbnailUrl` and `imageUrl` fields exist
3. Ensure gallery images are properly uploaded

### Wrong Currency Code

**Problem:** Currency shows as EUR but should be USD

**Solutions:**
1. Check Ecwid store settings for default currency
2. Verify product price formatting includes currency symbol
3. Modify `extract_currency_code()` to force your currency

### Category Not Showing in GTM
### Issue: "Wrong Availability Status"

**Problem:** Schema shows OutOfStock but product is available for pre-order

**Solutions:**
1. Verify product is **enabled** in Ecwid admin
2. Check that quantity is set to 0 (or explicitly out of stock)
3. Enable debug logging to see detection values:
   - Add `define('WP_DEBUG', true);` to wp-config.php
   - Add `define('WP_DEBUG_LOG', true);` to wp-config.php
4. Check `/wp-content/debug.log` for availability detection
5. Verify Ecwid product settings allow out-of-stock purchases

### Issue: "Category Not Showing in GTM"

**Problem:** Category field is empty in GTM events

**Solutions:**
1. Verify product is assigned to a category in Ecwid
2. **Known Limitation:** Category names may not be available in Ecwid product data
3. This is expected behavior to avoid additional API calls that slow down page loads
4. If category tracking is critical, consider manually adding category data via product attributes
5. Alternative: Use Google Analytics content grouping based on URL patterns instead

---

## Best Practices

### SEO Optimization

1. **Use Descriptive Product Names** - Include key features and benefits
2. **Write Detailed Descriptions** - At least 200 words with natural keyword usage
3. **Add High-Quality Images** - Multiple angles, minimum 800x800px
4. **Set SKUs** - Unique identifiers help with tracking
5. **Configure Stock Settings Properly** - Use PreOrder for backorder items
6. **Keep Product Status Current** - Enable/disable products appropriately

### GTM Tracking

1. **Test Before Going Live** - Always use GTM preview mode
2. **Create a GTM Workspace** - Don't work directly in live container
3. **Document Your Tags** - Add descriptions to all tags and triggers
4. **Set Up Alerts** - Get notified when tags stop firing
5. **Regular Audits** - Check GTM monthly for issues

### Google Shopping

1. **Complete Product Data** - Fill all available Ecwid fields
2. **Use Brand Attributes** - Set brand for every product
3. **High-Quality Images** - Follow Google's image requirements
4. **Competitive Pricing** - Keep prices updated
5. **Monitor Merchant Center** - Check for disapproved products weekly

---

## Additional Resources

- [Google Product Schema Documentation](https://developers.google.com/search/docs/appearance/structured-data/product)
- [Google Tag Manager Guide](https://support.google.com/tagmanager)
- [Google Merchant Center Help](https://support.google.com/merchants)
- [Schema.org Product Specification](https://schema.org/Product)
- [Enhanced Ecommerce Dev Guide](https://developers.google.com/analytics/devguides/collection/ua/gtm/enhanced-ecommerce)

---

## Support

For issues or questions:

1. Check this documentation first
2. Search existing GitHub issues
3. Create a new issue with:
   - Detailed description
   - Product URL example
   - Browser console errors (if any)
   - GTM preview screenshots (if relevant)

---

**Last Updated:** 2024
**Version:** 0.6.1