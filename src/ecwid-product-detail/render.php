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

require_once plugin_dir_path(__FILE__) . '../../includes/utils.php';

$product_slug = get_query_var('ecwid_product_slug', '');
$product_id = peaches_get_product_id_from_slug($product_slug);

// Get full product data if we have an ID
$product_data = null;
if (!empty($product_id)) {
    // Get product data using Ecwid API
    $product = Ecwid_Product::get_by_id($product_id);

    if ($product) {
        // Convert product to JSON-compatible array
        $product_data = array(
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'sku' => $product->sku,
            'description' => $product->description,
            'url' => $product->url,
            'imageUrl' => $product->imageUrl,
            'thumbnailUrl' => $product->thumbnailUrl,
            'images' => isset($product->galleryImages) ? $product->galleryImages : [],
            'categories' => isset($product->categories) ? $product->categories : [],
            'attributes' => isset($product->attributes) ? $product->attributes : [],
            'options' => isset($product->options) ? $product->options : [],
        );

        // Add additional media if available (for newer Ecwid API versions)
        if (isset($product->media) && isset($product->media->images)) {
            $product_data['media'] = $product->media;
        }
    }
}

// Adds the global state with both product ID and full product data
wp_interactivity_state('peaches-ecwid-product-detail', array(
    'productId' => $product_id,
    'productData' => $product_data
));

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
    // We already have the product from above
    if (!$product) {
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
?>
