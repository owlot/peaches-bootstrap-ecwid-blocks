<?php
/**
 * Server-side render file for ecwid-product-description block
 *
 * Implements hybrid server-side + client-side rendering approach for optimal performance.
 * Data is pre-loaded server-side, view.js handles only interactive features.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 */

// Get attributes with defaults
$selected_product_id = isset($attributes['selectedProductId']) ? absint($attributes['selectedProductId']) : 0;
$description_type = isset($attributes['descriptionType']) ? sanitize_text_field($attributes['descriptionType']) : 'usage';
$display_title = isset($attributes['displayTitle']) ? (bool) $attributes['displayTitle'] : true;
$custom_title = isset($attributes['customTitle']) ? sanitize_text_field($attributes['customTitle']) : '';

// Get current language for API requests
$current_language = Peaches_Ecwid_Utilities::get_current_language();
$product_detail_state = wp_interactivity_state('peaches-ecwid-product-detail');
$product_id = $selected_product_id;
$product_data = null;

// Check if we have product data from the product-detail block
if (!empty($product_detail_state['productId']) && !empty($product_detail_state['productData'])) {
	$product_id = absint($product_detail_state['productId']);
	$product_data = $product_detail_state['productData'];
}

// Early return if no product ID
if (empty($product_id)) {
	return;
}

// If we don't have product data from global state, fetch it
if (empty($product_data)) {
	$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
	if (!$ecwid_blocks) {
		return;
	}

	$ecwid_api = $ecwid_blocks->get_ecwid_api();
	if ($ecwid_api && method_exists($ecwid_api, 'get_product_description_by_type')) {
		// We only need the description, not the full product
		$description_data = $ecwid_api->get_product_description_by_type(
			$product_id,
			$description_type,
			$current_language
		);
	}
} else {
	// We have product data from global state, get description from API
	$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
	$description_data = null;

	if ($ecwid_blocks) {
		$ecwid_api = $ecwid_blocks->get_ecwid_api();
		if ($ecwid_api && method_exists($ecwid_api, 'get_product_description_by_type')) {
			$description_data = $ecwid_api->get_product_description_by_type(
				$product_id,
				$description_type,
				$current_language
			);
		}
	}
}

// If no description data, don't render anything
if (!$description_data || empty($description_data['content'])) {
	return;
}

// Determine the title to display
$display_title_text = '';
if ($display_title) {
	if (!empty($custom_title)) {
		$display_title_text = $custom_title;
	} elseif (!empty($description_data['title'])) {
		$display_title_text = $description_data['title'];
	} else {
		// Fallback to type-based title
		$type_titles = [
			'usage' => __('Usage Instructions', 'peaches'),
			'ingredients' => __('Ingredients', 'peaches'),
			'care' => __('Care Instructions', 'peaches'),
			'shipping' => __('Shipping Information', 'peaches'),
			'warranty' => __('Warranty', 'peaches'),
		];
		$display_title_text = isset($type_titles[$description_type])
			? $type_titles[$description_type]
			: __('Product Description', 'peaches');
	}
}

// Prepare interactivity context with pre-loaded data
$context = [
	'selectedProductId' => $product_id,
	'descriptionType' => $description_type,
	'displayTitle' => $display_title,
	'customTitle' => $custom_title,
	'descriptionContent' => $description_data['content'],
	'descriptionTitle' => $description_data['title'] ?? '',
	'isLoaded' => true, // Flag to indicate data is pre-loaded
];

// Get block wrapper attributes with interactivity
$wrapper_attributes = get_block_wrapper_attributes([
	'data-wp-interactive' => 'peaches-ecwid-product-description',
	'data-wp-context' => wp_json_encode($context),
]);
?>

<div <?php echo $wrapper_attributes; ?>>
	<?php if ($display_title && !empty($display_title_text)): ?>
		<h4 class="product-description-title mb-3">
			<?php echo esc_html($display_title_text); ?>
		</h4>
	<?php endif; ?>

	<div class="product-description-content">
		<?php
		// Output the description content (may contain HTML)
		// Use wp_kses_post to allow safe HTML tags
		echo wp_kses_post($description_data['content']);
		?>
	</div>
</div>
