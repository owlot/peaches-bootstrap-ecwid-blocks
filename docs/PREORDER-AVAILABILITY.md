# Pre-Order & Backorder Availability in Product Schema

## Overview

The Google Shopping Product Schema now automatically detects and properly marks products that are available for pre-order or backorder, even when out of stock.

---

## Availability Types

The system uses schema.org standard availability values:

### 1. InStock
**When:** Product has available stock or unlimited quantity

**Schema Output:**
```json
{
  "offers": {
    "availability": "https://schema.org/InStock"
  }
}
```

**Ecwid Settings:**
- Product is enabled
- Quantity > 0, OR unlimited = true

---

### 2. PreOrder
**When:** Product is out of stock but still purchasable (pre-order/backorder)

**Schema Output:**
```json
{
  "offers": {
    "availability": "https://schema.org/PreOrder"
  }
}
```

**Ecwid Settings:**
- Product is **enabled**
- Quantity = 0 (or marked out of stock)
- Allow out-of-stock purchases is configured

**Google Shopping Impact:**
- Shows "Pre-order" status in search results
- Allows customers to place orders
- Better than showing "Out of Stock"

---

### 3. OutOfStock
**When:** Product is out of stock and NOT available for purchase

**Schema Output:**
```json
{
  "offers": {
    "availability": "https://schema.org/OutOfStock"
  }
}
```

**Ecwid Settings:**
- Product is disabled, OR
- Quantity = 0 AND purchases not allowed

**Google Shopping Impact:**
- Shows "Out of stock" in search results
- Product may have lower visibility

---

## How Detection Works

### Detection Logic

```
IF product.inStock = true OR product.unlimited = true:
    → availability = "InStock"

ELSE IF product.inStock = false AND product.enabled = true AND product.quantity = 0:
    → availability = "PreOrder"

ELSE:
    → availability = "OutOfStock"
```

### Properties Checked

| Property | Source | Purpose |
|----------|--------|---------|
| `inStock` | Ecwid API | Primary stock indicator |
| `unlimited` | Ecwid API | Unlimited quantity products |
| `enabled` | Ecwid API | Product is active/purchasable |
| `quantity` | Ecwid API | Current stock count |

---

## Setting Up Pre-Order Products

### In Ecwid Admin

1. Go to **Catalog** → **Products**
2. Select your product
3. Go to **Inventory** section
4. Configure:
   - ✅ Set "In Stock" to **No** (or quantity to 0)
   - ✅ Check "Allow customers to purchase this product when it's out of stock"
   - ✅ Keep product **Enabled**
5. Save product

### Expected Result

The product schema will automatically show:
```json
{
  "@type": "Product",
  "name": "Your Product",
  "offers": {
    "@type": "Offer",
    "availability": "https://schema.org/PreOrder",
    "price": "29.95",
    "priceCurrency": "EUR"
  }
}
```

---

## Verification & Testing

### Method 1: View Page Source

1. Visit your product page
2. Right-click → View Page Source
3. Search for `application/ld+json`
4. Look for the `availability` property
5. Should see: `"availability": "https://schema.org/PreOrder"`

### Method 2: Google Rich Results Test

1. Go to: https://search.google.com/test/rich-results
2. Enter your product URL
3. Click "Test URL"
4. Check the detected Product schema
5. Verify "Availability" shows "PreOrder"

### Method 3: Debug Logging

Enable WordPress debug mode:

**wp-config.php:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check `/wp-content/debug.log` for:
```
[Product Schema] Product #12345 availability: 
  inStock=false, 
  unlimited=false, 
  enabled=true, 
  quantity=0, 
  available_for_purchase=true
```

---

## Common Scenarios

### Scenario 1: Limited Pre-Order
**Situation:** New product launching soon, taking pre-orders

**Ecwid Setup:**
- Enabled: ✅ Yes
- Quantity: 0 (or "Out of Stock")
- Allow out-of-stock purchases: ✅ Yes

**Result:** `PreOrder` ✅

---

### Scenario 2: Permanent Backorder
**Situation:** Made-to-order product, always available but not stocked

**Ecwid Setup:**
- Enabled: ✅ Yes
- Quantity: 0
- Allow out-of-stock purchases: ✅ Yes

**Result:** `PreOrder` ✅

**Alternative:** Set unlimited = true for `InStock` status

---

### Scenario 3: Temporarily Unavailable
**Situation:** Product sold out, not accepting orders yet

**Ecwid Setup:**
- Enabled: ❌ No (disable product)

**Result:** `OutOfStock` ✅

---

### Scenario 4: Coming Soon (Not Yet for Sale)
**Situation:** Product exists but shouldn't be purchased yet

**Ecwid Setup:**
- Enabled: ❌ No

**Result:** `OutOfStock` ✅

**Better Approach:** Keep product hidden until ready

---

## Troubleshooting

### Issue: Shows "OutOfStock" instead of "PreOrder"

**Check:**
1. Is product **enabled** in Ecwid? (Must be YES)
2. Is "Allow out-of-stock purchases" checked?
3. Is quantity actually 0 or negative?

**Debug:**
```bash
# Enable debug logging
# Check /wp-content/debug.log
grep "Product Schema" /wp-content/debug.log
```

**Common Fix:**
- Re-save product in Ecwid admin
- Clear WordPress cache
- Clear Ecwid cache (if applicable)

---

### Issue: Shows "InStock" but product is out of stock

**Check:**
1. Ecwid product sync - may be cached
2. Check actual Ecwid inventory
3. Clear all caches

**Debug:**
Look at debug log to see what values are detected

---

### Issue: Google Shopping rejects pre-order products

**Cause:** Some product categories don't support pre-orders in Google Merchant Center

**Solution:**
1. Check Google Merchant Center policies
2. Verify product category allows pre-orders
3. Consider using "InStock" with long shipping times instead

---

## Google Shopping Best Practices

### For Pre-Order Products

1. **Add Pre-Order Text to Description**
   ```
   Pre-order now! Ships in 2-4 weeks.
   ```

2. **Set Realistic Shipping Times**
   - Configure extended shipping in Ecwid
   - Add expected ship date to product description

3. **Use Product Attributes**
   - Add "Availability Date" attribute
   - Add "Shipping Delay" note

4. **Monitor Merchant Center**
   - Check for pre-order policy violations
   - Update availability dates as needed

---

## Schema.org Availability Options

Full list of supported values (we use the first 3):

- ✅ `https://schema.org/InStock` - Currently available
- ✅ `https://schema.org/PreOrder` - Available for pre-order
- ✅ `https://schema.org/OutOfStock` - Not available
- `https://schema.org/BackOrder` - Available on backorder
- `https://schema.org/Discontinued` - No longer available
- `https://schema.org/InStoreOnly` - Only in physical stores
- `https://schema.org/LimitedAvailability` - Very limited stock
- `https://schema.org/OnlineOnly` - Only online
- `https://schema.org/PreSale` - Pre-sale period
- `https://schema.org/SoldOut` - Completely sold out

**Note:** We use `PreOrder` for both pre-orders and backorders as it's most widely recognized.

---

## Technical Details

### Code Location

**File:** `includes/class-rewrite-manager.php`

**Method:** `get_product_availability()`

**Logic:**
```php
private function get_product_availability($product) {
    $in_stock = !empty($product->inStock);
    $unlimited = !empty($product->unlimited);
    $enabled = isset($product->enabled) && $product->enabled;
    $quantity = isset($product->quantity) ? intval($product->quantity) : 0;
    
    if ($in_stock || $unlimited) {
        return 'https://schema.org/InStock';
    } elseif (!$in_stock && $enabled && $quantity <= 0) {
        return 'https://schema.org/PreOrder';
    } else {
        return 'https://schema.org/OutOfStock';
    }
}
```

---

## FAQs

**Q: Will this affect my Google Shopping campaigns?**
A: Yes, positively! PreOrder status is better than OutOfStock for maintaining visibility.

**Q: Can I force a specific availability status?**
A: Not via settings, but you can modify the `get_product_availability()` method.

**Q: Does this work with all Ecwid versions?**
A: Yes, it uses standard Ecwid API properties available in all versions.

**Q: What about BackOrder vs PreOrder?**
A: We use PreOrder for both as it's more widely recognized and supported.

**Q: Will customers see "PreOrder" on my site?**
A: No, this only affects the schema markup. Your site displays whatever you configure in your blocks.

**Q: Does this work with variable products?**
A: The schema reflects the parent product. Variants are handled by Ecwid's own system.

---

## Examples

### Example 1: Basic Pre-Order Product

**Ecwid Settings:**
```
Product Name: Limited Edition T-Shirt
Enabled: Yes
Quantity: 0
Allow out-of-stock purchases: Yes
```

**Generated Schema:**
```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Limited Edition T-Shirt",
  "offers": {
    "@type": "Offer",
    "price": "29.95",
    "priceCurrency": "EUR",
    "availability": "https://schema.org/PreOrder",
    "url": "https://example.com/shop/limited-edition-t-shirt"
  }
}
```

**Google Shopping Display:**
- ✅ Shows in search results
- 🏷️ Labeled as "Pre-order"
- 💰 Shows price
- 🖼️ Shows product image

---

### Example 2: Unlimited Custom Product

**Ecwid Settings:**
```
Product Name: Custom Engraved Mug
Enabled: Yes
Unlimited: Yes
```

**Generated Schema:**
```json
{
  "availability": "https://schema.org/InStock"
}
```

**Note:** Unlimited products always show as InStock regardless of quantity.

---

## Related Documentation

- [Google Shopping Integration Guide](./GOOGLE-SHOPPING-GTM-INTEGRATION.md)
- [Quick Start Guide](./GOOGLE-SHOPPING-QUICK-START.md)
- [Schema.org Product Specification](https://schema.org/Product)
- [Google Merchant Center Pre-order Policy](https://support.google.com/merchants/answer/9199813)

---

**Last Updated:** 2024  
**Feature Version:** 0.6.1+  
**Status:** Production Ready ✅