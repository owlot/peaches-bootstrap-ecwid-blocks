<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

// Get product data and set interactivity state BEFORE caching check
// This ensures client-side blocks always have access to the global state
$product_id = peaches_ecwid_get_effective_product_id($attributes);
$product_data = null;

if (!empty($product_id)) {
    $ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
    $ecwid_api = $ecwid_blocks->get_ecwid_api();
    $product = $ecwid_api->get_product_by_id($product_id);
    
    if ($product) {
        $product_data = (array) $product;
        
        // Ensure common fields are properly set
        if (!isset($product_data['description'])) {
            $product_data['description'] = '';
        }
        if (!isset($product_data['galleryImages'])) {
            $product_data['galleryImages'] = array();
        }
        if (!isset($product_data['media'])) {
            $product_data['media'] = null;
        }
        if (!isset($product_data['inStock'])) {
            $product_data['inStock'] = true;
        }
        if (!isset($product_data['compareToPrice'])) {
            $product_data['compareToPrice'] = null;
        }
    }
}

// Always set the global state - this is critical for client-side blocks
wp_interactivity_state('peaches-ecwid-product-detail', array(
    'productId' => $product_id,
    'productData' => $product_data
));

// Now check for cached block HTML using product-aware caching
$cache_result = peaches_ecwid_start_product_block_cache('ecwid-product-detail', $attributes, $content);
if ($cache_result === false) {
    return; // Cached content was served
}
$cache_manager = $cache_result['cache_manager'] ?? null;
$cache_factors = $cache_result['cache_factors'] ?? null;

// If no product ID is found in the URL, check if we're in the admin preview
if (empty($product_id) && is_admin()) {
    // In admin preview, just show placeholder message
?>
    <div class="alert alert-info">
        <?php echo __('This is a dynamic product detail template. The actual product will be displayed based on the URL when viewed on the frontend.', 'ecwid-shopping-cart') ?>
    </div>
<?php
}
// If we have a product ID, try to get product details
if (!empty($product_id)) {
    // We already have the product data from above
    if (empty($product_data)) {
?>
        <div class="alert alert-warning">
            <?php echo __('Product could not be found', 'ecwid-shopping-cart'); ?>
        </div>
<?php
    } else {
        echo $content;
    }
} else {
?>
    <div class="alert alert-warning">
        <?php echo __('No product found. Please check the URL and try again.', 'ecwid-shopping-cart'); ?>
    </div>
<?php
}

// Cache the rendered HTML
peaches_ecwid_end_block_cache('ecwid-product-detail', $cache_manager, $cache_factors, 300);
?>
