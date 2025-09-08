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

// Fetch product data BEFORE caching check to ensure interactivity context is always fresh
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

// Get all necessary data BEFORE caching check to ensure fresh interactivity context
// Get show add to cart setting
$show_add_to_cart = isset($attributes['showAddToCart']) ? $attributes['showAddToCart'] : true;

// Get hover media tag setting
$hover_media_tag = isset($attributes['hoverMediaTag']) ? sanitize_text_field($attributes['hoverMediaTag']) : '';

// Get computed className from attributes
$computed_class_name = peaches_get_safe_string_attribute($attributes, 'computedClassName');

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

// Extract product subtitle from attributes - ensure values are strings
$product_subtitle = '';
if (!empty($product->attributes)) {
	foreach ($product->attributes as $attr) {
		// Ensure attr has required properties and they're not null
		if (!isset($attr->name) || !isset($attr->value)) {
			continue;
		}

		$attr_name = strtolower((string)$attr->name);
		if (strpos($attr_name, 'ondertitel') !== false ||
			strpos($attr_name, 'subtitle') !== false ||
			strpos($attr_name, 'sub-title') !== false ||
			strpos($attr_name, 'tagline') !== false) {
			// Ensure the value is a string and not null
			$product_subtitle = (string)$attr->value;
			break;
		}
	}
}

// Handle button text with new multilingual system support
$button_text = __('Add to cart', 'peaches');
if (isset($attributes['buttonText'])) {
	// Ensure buttonText is a string, never null
	$button_text = peaches_get_safe_string_attribute($attributes, 'buttonText');

	// Only proceed if we have a non-empty buttonText
	if (!empty($button_text)) {
		// Check for new multilingual system first (preferred)
		if (!empty($attributes['translations']['buttonText'])) {
			$current_language = peaches_get_render_language();
			$default_language = peaches_default_language();

			// If not default language, look for translation
			if ($current_language !== $default_language) {
				$button_translations = $attributes['translations']['buttonText'];
				if (isset($button_translations[$current_language]) && !empty($button_translations[$current_language])) {
					$button_text = (string)$button_translations[$current_language];
				}
			}
		}
		// Fallback to old system for backward compatibility
		elseif (function_exists('peaches_get_translated_content') && !empty($attributes['translations'])) {
			$translated_button_text = peaches_get_translated_content($attributes, 'buttonText');
			if (!empty($translated_button_text)) {
				$button_text = (string)$translated_button_text;
			}
		}
	}
}

// Format price
$formatted_price = '';
if (isset($product->price)) {
	$formatted_price = '€ ' . number_format($product->price, 2, ',', '.');
}

// Set global interactivity state BEFORE caching check to ensure fresh data
// Only store data needed for interactivity - display data is already in HTML
wp_interactivity_state('peaches-ecwid-product', array(
	'products' => array(
		$product_id => array(
			'hoverImageUrl' => $hover_image_url,
			'productUrl' => $product_url
		)
	)
));

// Now check for cached block HTML using cache helper
$cache_result = peaches_ecwid_start_product_block_cache('ecwid-product', $attributes, $content);
if ($cache_result === false) {
    return; // Cached content was served - but interactivity state is fresh!
}
$cache_manager = $cache_result['cache_manager'] ?? null;
$cache_factors = $cache_result['cache_factors'] ?? null;

// Prepare block wrapper attributes with computed Bootstrap classes
// Context contains basic interaction state - product data comes from global state
$wrapper_attributes = get_block_wrapper_attributes(array(
	'class' => $computed_class_name,
	'data-wp-interactive' => 'peaches-ecwid-product',
	'data-wp-context' => json_encode(array(
		'productId' => $product_id,
		'isLoading' => false,
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
			 role="button"
			 data-wp-class--visible="!context.isHovering">

		<?php if (!empty($hover_image_url)): ?>
			<img class="card-img-top product-image-hover"
				 src="<?php echo esc_url($hover_image_url); ?>"
				 alt="<?php echo esc_attr($product->name . ' - hover image'); ?>"
				 role="button"
				 data-wp-on--click="actions.navigateToProduct"
				 data-wp-class--visible="context.isHovering">
		<?php endif; ?>
	</div>

	<div
		class="card-body p-2 p-md-3 d-flex flex-wrap align-content-between"
		role="button"
		 data-wp-on--click="actions.navigateToProduct"
	>
		<h5 role="button" class="card-title">
			<?php echo esc_html($product->name); ?>
		</h5>
		<?php if (!empty($product_subtitle)): ?>
			<p class="card-subtitle mb-2 text-muted"><?php echo esc_html($product_subtitle); ?></p>
		<?php endif; ?>
	</div>

	<div class="card-footer p-2 p-md-3 border-0 hstack justify-content-between">
		<div class="card-text fw-bold lead">
			<?php echo esc_html($formatted_price); ?>
		</div>
		<?php if ($show_add_to_cart): ?>
			<button title="<?php echo esc_html($button_text); ?>"
					class="add-to-cart btn pe-0"
					aria-label="<?php echo esc_html($button_text); ?>"
					data-wp-on--click="actions.addToCart"></button>
		<?php endif; ?>
	</div>
</div>

<?php
// Cache the rendered HTML using cache helper
peaches_ecwid_end_block_cache('ecwid-product', $cache_manager, $cache_factors, 300);
?>
