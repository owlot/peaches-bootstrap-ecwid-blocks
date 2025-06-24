<?php
/**
 * PHP file to use when rendering the ecwid-product block on the server.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 */

// Get the product ID from attributes
$product_id = isset($attributes['id']) ? absint($attributes['id']) : 0;

// If no product ID, don't render anything
if (empty($product_id)) {
	return;
}

// Get show add to cart setting
$show_add_to_cart = isset($attributes['showAddToCart']) ? $attributes['showAddToCart'] : true;

// Get hover media tag setting
$hover_media_tag = isset($attributes['hoverMediaTag']) ? sanitize_text_field($attributes['hoverMediaTag']) : '';

// Get computed className from attributes
$computed_class_name = isset($attributes['computedClassName']) ? $attributes['computedClassName'] : '';

// Get the main plugin instance and fetch product data
$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
$product = null;
$product_url = '';

if ($ecwid_blocks) {
	$ecwid_api = $ecwid_blocks->get_ecwid_api();
	if ($ecwid_api) {
		// Fetch product data from Ecwid
		$product = $ecwid_api->get_product_by_id($product_id);

		if ($product) {
			// Get product manager to build URL
			$product_manager = $ecwid_blocks->get_product_manager();
			if ($product_manager && method_exists($product_manager, 'build_product_url')) {
				$current_lang = Peaches_Ecwid_Utilities::get_current_language();
				$product_url = $product_manager->build_product_url($product, $current_lang);
			}
		}
	}
}

// If no product found, don't render
if (!$product) {
	return;
}

// Get hover image URL if tag is specified
$hover_image_url = '';
if (!empty($hover_media_tag)) {
	// Use the template function for consistency
	$hover_image_url = peaches_get_product_media_url($product_id, $hover_media_tag, 'large');

	// Debug: Log the hover image retrieval
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log("Hover image debug - Product ID: {$product_id}, Tag: {$hover_media_tag}, URL: " . ($hover_image_url ?: 'NULL'));
	}
}

// Extract product subtitle from attributes
$product_subtitle = '';
if (!empty($product->attributes)) {
	foreach ($product->attributes as $attr) {
		$attr_name = strtolower($attr->name);
		if (strpos($attr_name, 'ondertitel') !== false ||
			strpos($attr_name, 'subtitle') !== false ||
			strpos($attr_name, 'sub-title') !== false ||
			strpos($attr_name, 'tagline') !== false) {
			$product_subtitle = $attr->value;
			break;
		}
	}
}

// Format price
$formatted_price = '';
if (isset($product->price)) {
	$formatted_price = '€ ' . number_format($product->price, 2, ',', '.');
}

// Prepare block wrapper attributes with computed Bootstrap classes
$wrapper_attributes = get_block_wrapper_attributes(array(
	'class' => trim('card h-100 border-0 ' . $computed_class_name),
	'data-wp-interactive' => 'peaches-ecwid-product',
	'data-wp-context' => json_encode(array(
		'productId' => $product_id,
		'isLoading' => false,
		'product' => array(
			'id' => $product->id,
			'name' => $product->name,
			'thumbnailUrl' => $product->thumbnailUrl ?? '',
			'price' => $product->price ?? 0,
			'url' => $product_url,
			'subtitle' => $product_subtitle
		),
		'hoverImageUrl' => $hover_image_url,
		'isHovering' => false
	), JSON_HEX_QUOT),
	'data-wp-init' => 'callbacks.initProduct'
));
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="ratio ratio-1x1 product-image-container"
		 data-wp-on--mouseenter="actions.handleMouseEnter"
		 data-wp-on--mouseleave="actions.handleMouseLeave">
		<img class="card-img-top product-image-main"
			 src="<?php echo esc_url($product->thumbnailUrl ?? ''); ?>"
			 alt="<?php echo esc_attr($product->name); ?>"
			 data-wp-class--visible="!context.isHovering">

		<?php if (!empty($hover_image_url)): ?>
			<img class="card-img-top product-image-hover"
				 src="<?php echo esc_url($hover_image_url); ?>"
				 alt="<?php echo esc_attr($product->name . ' - hover image'); ?>"
				 data-wp-class--visible="context.isHovering">
		<?php endif; ?>
	</div>

	<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
		<h5 role="button"
			class="card-title"
			data-wp-on--click="actions.navigateToProduct">
			<?php echo esc_html($product->name); ?>
		</h5>
		<?php if (!empty($product_subtitle)): ?>
			<p class="card-text text-muted"><?php echo esc_html($product_subtitle); ?></p>
		<?php endif; ?>
	</div>

	<div class="card-footer border-0 hstack justify-content-between">
		<div class="card-text fw-bold lead">
			<?php echo esc_html($formatted_price); ?>
		</div>
		<?php if ($show_add_to_cart): ?>
			<button title="<?php echo esc_attr__('Add to cart', 'peaches'); ?>"
					class="add-to-cart btn pe-0"
					aria-label="<?php echo esc_attr__('Add to cart', 'peaches'); ?>"
					data-wp-on--click="actions.addToCart"></button>
		<?php endif; ?>
	</div>
</div>
