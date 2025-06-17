<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 */

/**
 * Generate bs-col block with ecwid-product inside
 *
 * @param int $product_id Product ID
 *
 * @return string Rendered block HTML
 */
if (!function_exists('peaches_generate_product_col_block')) {
	function peaches_generate_product_col_block($product_id) {
		// Render the product block first
		$product_html = render_block(array(
			'blockName' => 'peaches/ecwid-product',
			'attrs' => array(
				'id' => $product_id,
				'showAddToCart' => true
			)
		));

		// If product rendering fails, return empty
		if (empty($product_html)) {
			return '';
		}

		// Wrap the product in a column div
		return sprintf(
			'<div class="col">%s</div>',
			$product_html
		);
	}
}

// Get the main plugin instance
$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
$ecwid_api = $ecwid_blocks->get_ecwid_api();

// Get product ID from the parent product detail store
$product_detail_state = wp_interactivity_state('peaches-ecwid-product-detail');
$product_id = isset($product_detail_state['productId']) ? $product_detail_state['productId'] : 0;

// If no product ID, don't render anything
if (empty($product_id)) {
	return;
}

// Get the main product
$product = $ecwid_api->get_product_by_id($product_id);
if (!$product) {
	return;
}

// Get related product IDs using the centralized method
$related_ids = $ecwid_api->get_related_product_ids($product);

// Convert to integers and limit by maxProducts
$max_products = isset($attributes['maxProducts']) ? $attributes['maxProducts'] : 4;
$related_ids = array_slice($related_ids, 0, $max_products);

// If no related products, don't render anything
if (empty($related_ids)) {
	return;
}

// Use the isInCarousel attribute set by the editor (much more reliable!)
$is_in_carousel = isset($attributes['isInCarousel']) ? $attributes['isInCarousel'] : false;

// Get computedClassName setting
$computed_classname = isset($attributes['computedClassName']) ? $attributes['computedClassName'] : '';

if ($is_in_carousel) {
	// In carousel: render bs-col blocks directly without any wrapper
	// Title and status messages can't be shown in carousel mode

	foreach ($related_ids as $related_product_id) {
		// Create bs-col block with product inside
		$col_block_html = peaches_generate_product_col_block($related_product_id);
		echo wp_interactivity_process_directives($col_block_html);
	}

	return; // Exit early - no container needed
} else {
	// Not in carousel: render with row wrapper

	// Prepare block attributes
	$block_props = get_block_wrapper_attributes();

	$title_text = !empty($attributes['customTitle']) ?
		$attributes['customTitle'] : __('Related Products', 'peaches');
	?>

	<div <?php echo $block_props; ?>>
		<?php if ($attributes['showTitle']): ?>
			<h3 class="related-products-title mb-4">
				<?php echo esc_html($title_text); ?>
			</h3>
		<?php endif; ?>

		<div class="related-products-container row <?php echo esc_attr($computed_classname); ?>">
			<?php foreach ($related_ids as $related_product_id): ?>
				<?php
				// Always create bs-col blocks with products inside
				$col_block_html = peaches_generate_product_col_block($related_product_id);
				echo wp_interactivity_process_directives($col_block_html);
				?>
			<?php endforeach; ?>
		</div>
	</div>

	<?php
}
