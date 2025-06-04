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

// Get the main plugin instance
$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
$ecwid_api = $ecwid_blocks->get_ecwid_api();

// Ensure proper language detection for Polylang and WPML
$current_lang = '';

if (function_exists('pll_current_language')) {
    $current_lang = pll_current_language();

    // Set the language for the current request (Polylang)
    if ($current_lang && function_exists('pll_set_language')) {
        pll_set_language($current_lang);
    }
} elseif (defined('ICL_LANGUAGE_CODE')) {
    $current_lang = ICL_LANGUAGE_CODE;

    // Set the language for WPML
    if (class_exists('SitePress')) {
        global $sitepress;
        if ($sitepress) {
            $sitepress->switch_lang($current_lang);
        }
    }
}

$product_slug = get_query_var('ecwid_product_slug', '');
$product_id = 0;

// Use the utility function to get product ID from slug
if (class_exists('Peaches_Ecwid_Utilities') && !empty($product_slug)) {
    $product_id = $ecwid_api->get_product_id_from_slug($product_slug);
}

// Get full product data if we have an ID
$product_data = null;
if (!empty($product_id)) {
    // Get product data using Ecwid API
    $product = $ecwid_api->get_product_by_id($product_id);

if ($product) {
		// Convert the entire product object to array for JSON response
		// This preserves all data from Ecwid API
		$product_data = (array) $product;

		// Ensure common fields are properly set (in case they're missing)
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
			$product_data['inStock'] = true; // Default assumption
		}
		if (!isset($product_data['compareToPrice'])) {
			$product_data['compareToPrice'] = null;
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
