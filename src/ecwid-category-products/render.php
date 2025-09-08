<?php

// Get the main plugin instance and fetch products BEFORE caching check
$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
$ecwid_api = $ecwid_blocks->get_ecwid_api();

// Get category ID from attributes
$selected_category_id = isset($attributes['selectedCategoryId']) ? $attributes['selectedCategoryId'] : 0;

// If no category ID, don't render anything
if ($selected_category_id === null || $selected_category_id === '') {
	return;
}

// Get category products using search_products method
$max_products = isset($attributes['maxProducts']) ? $attributes['maxProducts'] : 4;

try {
	// Build search options for the selected category
	$search_options = array(
		'category' => (int) $selected_category_id,
		'limit' => $max_products,
		'enabled' => true,
	);

	// Get products from the specified category
	$category_products = $ecwid_api->search_products('', $search_options);

	// Extract product IDs
	$category_product_ids = array();
	if (!empty($category_products) && is_array($category_products)) {
		foreach ($category_products as $product) {
			if (isset($product['id'])) {
				$category_product_ids[] = (int) $product['id'];
			}
		}
	}

	// Limit to max products
	$category_product_ids = array_slice($category_product_ids, 0, $max_products);

} catch (Exception $e) {
	error_log('Category Products Block: Error fetching products: ' . $e->getMessage());
	$category_product_ids = array();
}

// No caching for category products block to ensure interactivity works correctly
// Individual product blocks within will still be cached for performance

// If no category products, don't render anything
if (empty($category_product_ids)) {
	return;
}

// Get computed className from attributes
$computed_class_name = peaches_get_safe_string_attribute($attributes, 'computedClassName');

// Use the isInCarousel attribute set by the editor
$is_in_carousel = isset($attributes['isInCarousel']) ? $attributes['isInCarousel'] : false;

// Extract display settings
$show_title = isset($attributes['showTitle']) ? $attributes['showTitle'] : true;
$custom_title = peaches_get_safe_string_attribute($attributes, 'customTitle');

if ($is_in_carousel) {
	// In carousel: render bs-col blocks directly without any wrapper
	// Title and status messages can't be shown in carousel mode

	foreach ($category_product_ids as $category_product_id) {
		// Create bs-col block with product inside, passing all settings
		$col_block_html = peaches_generate_product_col_block($category_product_id, $attributes);
		echo wp_interactivity_process_directives($col_block_html);
	}

	return; // Exit early - no container needed
} else {
	// Not in carousel: render with row wrapper

	// Prepare block attributes
	$block_props = get_block_wrapper_attributes();

	// Determine title text based on category
	if (!empty($custom_title)) {
		$title_text = $custom_title;
	} elseif ($selected_category_id === 0) {
		$title_text = __('Featured Products', 'peaches-bootstrap-ecwid-blocks');
	} else {
		// Try to get category name
		$category_name = null;
		try {
			$categories = $ecwid_api->get_categories();
			if (!empty($categories) && is_array($categories)) {
				foreach ($categories as $category) {
					if (isset($category->id) && $category->id == $selected_category_id) {
						$category_name = $category->name;
						break;
					}
				}
			}
		} catch (Exception $e) {
			// Fallback if category fetch fails
		}

		$title_text = $category_name ? $category_name : __('Category Products', 'peaches-bootstrap-ecwid-blocks');
	}
	?>

	<div <?php echo $block_props; ?>>
		<?php if ($show_title): ?>
			<h3 class="category-products-title mb-4">
				<?php echo esc_html($title_text); ?>
			</h3>
		<?php endif; ?>

		<div class="category-products-container row <?php echo esc_attr($computed_class_name); ?>">
			<?php foreach ($category_product_ids as $category_product_id): ?>
				<?php
				// Always create bs-col blocks with products inside, passing all settings
				$col_block_html = peaches_generate_product_col_block($category_product_id, $attributes);
				echo wp_interactivity_process_directives($col_block_html);
				?>
			<?php endforeach; ?>
		</div>
	</div>

	<?php
}

// No caching for this block - individual products will be cached instead
