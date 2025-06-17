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

// Get computed className from attributes
$computed_class_name = isset($attributes['computedClassName']) ? $attributes['computedClassName'] : '';

// Prepare block wrapper attributes with computed Bootstrap classes
$wrapper_attributes = get_block_wrapper_attributes(array(
	'class' => trim('card h-100 border-0 ' . $computed_class_name),
	'data-wp-interactive' => 'peaches-ecwid-product',
	'data-wp-context' => json_encode(array(
		'productId' => $product_id,
		'isLoading' => true,
		'product' => null
	), JSON_HEX_QUOT),
	'data-wp-init' => 'callbacks.initProduct'
));
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="ratio ratio-1x1">
		<img class="card-img-top"
			 data-wp-bind--src="state.productImage"
			 data-wp-bind--alt="state.productName"
			 alt="<?php echo esc_attr__('Product image', 'peaches'); ?>">
	</div>

	<div class="card-body p-2 p-md-3 d-flex row-cols-1 flex-wrap align-content-between">
		<h5 role="button"
			class="card-title"
			data-wp-text="state.productName"
			data-wp-on--click="actions.navigateToProduct">
			<?php echo esc_html__('Product Name', 'peaches'); ?>
		</h5>
		<p class="card-text text-muted" data-wp-text="state.productSubtitle"></p>
	</div>

	<div class="card-footer border-0 hstack justify-content-between">
		<div class="card-text fw-bold lead"
			 data-wp-text="state.productPrice"></div>
		<?php if ($show_add_to_cart): ?>
			<button title="<?php echo esc_attr__('Add to cart', 'peaches'); ?>"
					class="add-to-cart btn pe-0"
					aria-label="<?php echo esc_attr__('Add to cart', 'peaches'); ?>"
					data-wp-on--click="actions.addToCart"></button>
		<?php endif; ?>
	</div>

	<div data-wp-bind--hidden="!context.isLoading" class="text-center my-3">
		<div class="spinner-border text-primary" role="status">
			<span class="visually-hidden">
				<?php echo esc_html__('Loading product…', 'peaches'); ?>
			</span>
		</div>
	</div>
</div>
