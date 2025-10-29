<?php
/**
 * Render template for Related Products block.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @package PeachesEcwidBlocks
 * @since   0.5.0
 */

// Get the main plugin instance and product data BEFORE caching check.
$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
$ecwid_api    = $ecwid_blocks->get_ecwid_api();

// Get product ID from the parent product detail store or attributes.
$product_detail_state = wp_interactivity_state( 'peaches-ecwid-product-detail' );
$product_id           = isset( $attributes['selectedProductId'] ) ? $attributes['selectedProductId'] : null;

// Use state product id if available.
if ( $product_detail_state ) {
	$product_id = isset( $product_detail_state['productId'] ) ? $product_detail_state['productId'] : null;
}

// If no product ID, don't render anything.
if ( empty( $product_id ) ) {
	return;
}

// Get the main product.
$product = $ecwid_api->get_product_by_id( $product_id );
if ( ! $product ) {
	return;
}

// Get related product IDs using the centralized method.
$related_ids = $ecwid_api->get_related_product_ids( $product );

// Convert to integers and limit by maxProducts.
$max_products = isset( $attributes['maxProducts'] ) ? $attributes['maxProducts'] : 4;
$related_ids  = array_slice( $related_ids, 0, $max_products );

// If no related products, don't render anything.
if ( empty( $related_ids ) ) {
	return;
}

// No caching for related products block to ensure interactivity works correctly.
// Individual product blocks within will still be cached for performance.

// Get computed className from attributes.
$computed_class_name = peaches_get_safe_string_attribute( $attributes, 'computedClassName' );

// Use the isInCarousel attribute set by the editor.
$is_in_carousel = isset( $attributes['isInCarousel'] ) ? $attributes['isInCarousel'] : false;

// Extract display settings.
$show_title   = isset( $attributes['showTitle'] ) ? $attributes['showTitle'] : true;
$custom_title = peaches_get_safe_string_attribute( $attributes, 'customTitle' );

if ( $is_in_carousel ) {
	// In carousel: render bs-col blocks directly without any wrapper.
	// Title and status messages can't be shown in carousel mode.

	foreach ( $related_ids as $related_product_id ) {
		// Create bs-col block with product inside, passing all settings.
		$col_block_html = peaches_generate_product_col_block( $related_product_id, $attributes );
		echo wp_interactivity_process_directives( $col_block_html );
	}

	return; // Exit early - no container needed.
} else {
	// Not in carousel: render with row wrapper.

	// Prepare block attributes.
	$block_props = get_block_wrapper_attributes();

	// Determine title text.
	$title_text = ! empty( $custom_title ) ? $custom_title : __( 'Related Products', 'peaches-bootstrap-ecwid-blocks' );
	?>

	<div <?php echo $block_props; ?>>
		<?php if ( $show_title ) : ?>
			<h3 class="related-products-title mb-4">
				<?php echo esc_html( $title_text ); ?>
			</h3>
		<?php endif; ?>

		<div class="related-products-container row <?php echo esc_attr( $computed_class_name ); ?>">
			<?php foreach ( $related_ids as $related_product_id ) : ?>
				<?php
				// Always create bs-col blocks with products inside, passing all settings.
				$col_block_html = peaches_generate_product_col_block( $related_product_id, $attributes );
				echo wp_interactivity_process_directives( $col_block_html );
				?>
			<?php endforeach; ?>
		</div>
	</div>

	<?php
}

// No caching for this block - individual products will be cached instead.
