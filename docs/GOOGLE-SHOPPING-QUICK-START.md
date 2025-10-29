# Google Shopping & GTM Integration - Quick Start Guide

A quick reference for implementing Google Shopping and Google Tag Manager on your Munay-Ki product pages.

## ✅ What's Already Working

The following features are **automatically enabled** on all product detail pages:

### 1. Product Schema (JSON-LD)
- ✅ Automatically generated for every product
- ✅ Includes name, description, price, images, SKU
- ✅ Shows stock availability (InStock, OutOfStock, PreOrder)
- ✅ Detects pre-order/backorder status automatically
- ✅ Ready for Google Shopping

### 2. SEO Meta Tags
- ✅ Canonical URL (correct product URL)
- ✅ Meta description
- ✅ Open Graph tags (Facebook, LinkedIn)
- ✅ Twitter Card tags

### 3. GTM Data Layer Events
- ✅ Product view tracking
- ✅ Add to cart tracking
- ✅ Enhanced e-commerce data structure

---

## 🚀 Quick Setup (5 Minutes)

### Step 1: Install Google Tag Manager

**Option A - Plugin (Easiest):**
1. Install "Google Tag Manager for WordPress" plugin
2. Add your GTM-XXXXXXX ID
3. Done!

**Option B - Manual:**
Add GTM code to `wp_head` and `wp_body_open` hooks.

### Step 2: Configure GTM Tags

Create these two tags in GTM:

#### Tag 1: Product View
```
Type: GA4 Event
Event Name: view_item
Trigger: Custom Event = "productView"
```

#### Tag 2: Add to Cart
```
Type: GA4 Event
Event Name: add_to_cart
Trigger: Custom Event = "addToCart"
```

### Step 3: Test
1. Enable GTM Preview mode
2. Visit a product page → Check for `productView` event
3. Click "Add to Cart" → Check for `addToCart` event
4. Publish when working!

---

## 📊 Data Layer Structure

### Product View Event
```javascript
{
  event: "productView",
  ecommerce: {
    detail: {
      products: [{
        id: 12345,
        name: "Product Name",
        price: 29.95,
        brand: "Brand Name",
        category: "Category",
        variant: "SKU-123"
      }]
    }
  }
}
```

### Add to Cart Event
```javascript
{
  event: "addToCart",
  ecommerce: {
    add: {
      products: [{
        id: 12345,
        name: "Product Name",
        price: 29.95,
        brand: "Brand Name",
        category: "Category",
        variant: "SKU-123",
        quantity: 1
      }]
    }
  }
}
```

---

## 🔍 Testing Your Setup

### Test Product Schema (Google)
1. Go to: https://search.google.com/test/rich-results
2. Enter your product URL
3. Should see "Product" schema detected ✅

### Test GTM Events (Browser)
1. Open product page
2. Press F12 (DevTools)
3. Type in console: `dataLayer`
4. Should see events array with `productView` ✅
5. Click "Add to Cart"
6. Should see new `addToCart` event ✅

### Test in GTM Preview
1. GTM → Preview mode
2. Enter your site URL
3. Navigate to product page
4. See `productView` fire in GTM debugger ✅
5. Click "Add to Cart"
6. See `addToCart` fire in GTM debugger ✅

---

## 🎯 Optimization Tips

### Set Product Brands
To show accurate brand info in Google Shopping:

1. In Ecwid → Edit Product
2. Go to "Attributes" section
3. Add: `Name: "Brand"`, `Value: "Your Brand"`
4. Save

Without this, site name is used as brand.

### Improve Product Schema
- ✅ Add detailed descriptions (200+ words)
- ✅ Upload multiple high-quality images
- ✅ Set unique SKUs for all products
- ✅ Keep stock status updated
- ✅ Configure pre-order settings properly in Ecwid
- ✅ Use descriptive product names

### Optimize GTM Tracking
- ✅ Test in preview mode before publishing
- ✅ Create separate workspace for changes
- ✅ Add Google Analytics 4 property
- ✅ Enable enhanced e-commerce in GA4

---

## 🐛 Common Issues & Fixes

### Issue: "Schema not detected"
**Fix:** 
- Clear all caches (WordPress + CDN)
- Wait 5 minutes
- Retest

### Issue: "GTM events not firing"
**Fix:**
- Check GTM code is installed
- Check browser console for JS errors
- Verify `window.dataLayer` exists
- Ensure Ecwid is loaded

### Issue: "Wrong currency code"
**Fix:**
- Check Ecwid store currency settings
- Currency auto-detected from formatted price
- Default is EUR

### Issue: "PreOrder not showing (shows OutOfStock)"
**Fix:**
- Ensure product is **enabled** in Ecwid
- Set quantity to 0 or mark as out of stock
- Verify "allow out of stock purchases" is configured
- Enable WP_DEBUG_LOG to check detection

### Issue: "No images in schema"
**Fix:**
- Verify product has images in Ecwid
- Check gallery images are uploaded
- Re-save product in Ecwid

---

## 📋 Checklist for Google Shopping

Before submitting to Google Merchant Center:

- [ ] Product Schema visible in Rich Results Test
- [ ] All products have unique SKUs
- [ ] All products have descriptions (200+ words)
- [ ] All products have high-quality images (800x800+)
- [ ] All products have accurate prices
- [ ] Stock status is up to date
- [ ] Pre-order products properly configured
- [ ] Brand attributes are set
- [ ] GTM events are firing correctly
- [ ] Canonical URLs are correct
- [ ] No duplicate content issues

---

## 🔗 Useful Links

- [Rich Results Test](https://search.google.com/test/rich-results)
- [Schema Validator](https://validator.schema.org/)
- [Google Tag Manager](https://tagmanager.google.com/)
- [Google Merchant Center](https://merchants.google.com/)
- [Full Documentation](./GOOGLE-SHOPPING-GTM-INTEGRATION.md)

---

## 💡 Pro Tips

1. **Test on Staging First** - Don't test directly on production
2. **Use GTM Environments** - Create dev/staging/prod environments
3. **Monitor Weekly** - Check Google Search Console for issues
4. **Update Regularly** - Keep product data fresh
5. **Track Conversions** - Set up GA4 purchase tracking

---

## 🎓 Next Steps

### For Basic Setup:
1. ✅ Install GTM (you're done!)
2. ✅ Create product view tag
3. ✅ Create add-to-cart tag
4. ✅ Test and publish

### For Advanced Setup:
1. Set up Google Analytics 4
2. Configure enhanced e-commerce
3. Create conversion funnels
4. Set up remarketing audiences
5. Submit to Google Merchant Center
6. Create Google Shopping campaigns

### For Optimization:
1. Add brand attributes to all products
2. Improve product descriptions
3. Upload more product images
4. Set up product reviews (future)
5. Monitor and optimize regularly

---

## 📞 Need Help?

Check the [full documentation](./GOOGLE-SHOPPING-GTM-INTEGRATION.md) for:
- Detailed setup instructions
- Customization options
- Troubleshooting guide
- Best practices
- Advanced features

---

**Status:** ✅ Ready to use (v0.6.1)
**Last Updated:** 2024