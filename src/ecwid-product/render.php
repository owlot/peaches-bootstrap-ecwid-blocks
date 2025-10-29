<?php
/**
 * PHP file to use when rendering the ecwid-product block on the server.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.0
 */

// Get the product ID from attributes.
$product_id = isset( $attributes['id'] ) ? absint( $attributes['id'] ) : 0;

// If no product ID, don't render anything.
if ( empty( $product_id ) ) {
	return;
}

// Fetch product data BEFORE caching check to ensure interactivity context is always fresh.
// Get the main plugin instance and fetch product data.
$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
$product      = null;
$product_url  = '';

if ( $ecwid_blocks ) {
	$ecwid_api = $ecwid_blocks->get_ecwid_api();
	if ( $ecwid_api ) {
		// Fetch product data from Ecwid.
		$product = $ecwid_api->get_product_by_id( $product_id );

		if ( $product ) {
			// Get product manager to build URL.
			$product_manager = $ecwid_blocks->get_product_manager();
			if ( $product_manager && method_exists( $product_manager, 'build_product_url' ) ) {
				$current_lang = Peaches_Ecwid_Utilities::get_current_language();
				$product_url  = $product_manager->build_product_url( $product, $current_lang );
			}
		}
	}
}

// If no product found, don't render.
if ( ! $product ) {
	return;
}

// Generate responsive image data for main product image.
$main_image_data = null;
if ( ! empty( $product->thumbnailUrl ) && class_exists( 'Peaches_Ecwid_Image_Utilities' ) ) {
	$main_image_data = Peaches_Ecwid_Image_Utilities::generate_ecwid_image_data( $product, 0, 'gallery' );
}

// Get all necessary data BEFORE caching check to ensure fresh interactivity context.
// Get show add to cart setting.
$show_add_to_cart = isset( $attributes['showAddToCart'] ) ? $attributes['showAddToCart'] : true;

// Get hover media tag setting.
$hover_media_tag = isset( $attributes['hoverMediaTag'] ) ? sanitize_text_field( $attributes['hoverMediaTag'] ) : '';

// Get computed className from attributes.
$computed_class_name = peaches_get_safe_string_attribute( $attributes, 'computedClassName' );

// Get hover image data if tag is specified (with full responsive data).
$hover_image_data = null;
if ( ! empty( $hover_media_tag ) ) {
	// Get full media data with srcset for responsive images.
	// Use 'medium' size for carousel context - browser will select optimal size from srcset.
	$hover_image_data = peaches_get_product_media_data( $product_id, $hover_media_tag, 'medium' );

	// Debug: Log the hover image retrieval.
	if ( class_exists( 'Peaches_Ecwid_Utilities' ) && Peaches_Ecwid_Utilities::is_debug_mode() ) {
		Peaches_Ecwid_Utilities::log_error( '[INFO] [Ecwid Product Block] Hover image debug', array( 'product_id' => $product_id, 'tag' => $hover_media_tag, 'data' => $hover_image_data ) );
	}
}

// Extract product subtitle from attributes - ensure values are strings.
$product_subtitle = '';
if ( ! empty( $product->attributes ) ) {
	foreach ( $product->attributes as $attr ) {
		// Ensure attr has required properties and they're not null.
		if ( ! isset( $attr->name ) || ! isset( $attr->value ) ) {
			continue;
		}

		$attr_name = strtolower( (string) $attr->name );
		if ( false !== strpos( $attr_name, 'ondertitel' ) ||
			false !== strpos( $attr_name, 'subtitle' ) ||
			false !== strpos( $attr_name, 'sub-title' ) ||
			false !== strpos( $attr_name, 'tagline' ) ) {
			// Ensure the value is a string and not null.
			$product_subtitle = (string) $attr->value;
			break;
		}
	}
}

// Handle button text with new multilingual system support.
$button_text = __( 'Add to cart', 'peaches' );
if ( isset( $attributes['buttonText'] ) ) {
	// Ensure buttonText is a string, never null.
	$button_text = peaches_get_safe_string_attribute( $attributes, 'buttonText' );

	// Only proceed if we have a non-empty buttonText.
	if ( ! empty( $button_text ) ) {
		// Check for new multilingual system first (preferred).
		if ( ! empty( $attributes['translations']['buttonText'] ) ) {
			$current_language = peaches_get_render_language();
			$default_language = peaches_default_language();

			// If not default language, look for translation.
			if ( $current_language !== $default_language ) {
				$button_translations = $attributes['translations']['buttonText'];
				if ( isset( $button_translations[ $current_language ] ) && ! empty( $button_translations[ $current_language ] ) ) {
					$button_text = (string) $button_translations[ $current_language ];
				}
			}
		} elseif ( function_exists( 'peaches_get_translated_content' ) && ! empty( $attributes['translations'] ) ) {
			// Fallback to old system for backward compatibility.
			$translated_button_text = peaches_get_translated_content( $attributes, 'buttonText' );
			if ( ! empty( $translated_button_text ) ) {
				$button_text = (string) $translated_button_text;
			}
		}
	}
}

// Format price.
$formatted_price = '';
if ( isset( $product->price ) ) {
	$formatted_price = '€ ' . number_format( $product->price, 2, ',', '.' );
}

// Get category name for GTM tracking.
$category_name = '';
if ( ! empty( $product->categories ) && is_array( $product->categories ) ) {
	$first_category = reset( $product->categories );
	if ( ! empty( $first_category->name ) ) {
		$category_name = $first_category->name;
	}
}

// Set global interactivity state BEFORE caching check to ensure fresh data.
// Only store data needed for interactivity - display data is already in HTML.
wp_interactivity_state(
	'peaches-ecwid-product',
	array(
		'products' => array(
			$product_id => array(
				'hoverImageUrl' => $hover_image_data ? $hover_image_data['url'] : '',
				'productUrl'    => $product_url,
				// GTM tracking data.
				'id'            => $product->id,
				'name'          => $product->name,
				'price'         => isset( $product->price ) ? $product->price : 0,
				'brand'         => get_bloginfo( 'name' ),
				'category'      => $category_name,
				'variant'       => ! empty( $product->sku ) ? $product->sku : '',
			),
		),
	)
);

// Now check for cached block HTML using cache helper.
$cache_result = peaches_ecwid_start_product_block_cache( 'ecwid-product', $attributes, $content );
if ( false === $cache_result ) {
	return; // Cached content was served - but interactivity state is fresh!
}
$cache_manager = $cache_result['cache_manager'] ?? null;
$cache_factors = $cache_result['cache_factors'] ?? null;

// Prepare block wrapper attributes with computed Bootstrap classes.
// Context contains basic interaction state - product data comes from global state.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                 => $computed_class_name,
		'data-wp-interactive'   => 'peaches-ecwid-product',
		'data-wp-context'       => json_encode(
			array(
				'productId'          => $product_id,
				'isLoading'          => false,
				'isHovering'         => false,
				'impressionTracked'  => false,
			),
			JSON_HEX_QUOT
		),
		'data-wp-init'          => 'callbacks.initProduct',
	)
);
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="ratio ratio-1x1 product-image-container"
		 data-wp-on--mouseenter="actions.handleMouseEnter"
		 data-wp-on--mouseleave="actions.handleMouseLeave">
		<?php
		// Main image with smart responsive sizes.
		if ( ! empty( $main_image_data ) && function_exists( 'peaches_generate_responsive_image_html' ) ) {
			echo peaches_generate_responsive_image_html(
				$main_image_data,
				array(
					'class'             => 'card-img-top product-image-main',
					'role'              => 'button',
					'data-wp-class--visible' => '!context.isHovering',
				)
			);
		} else {
			// Fallback if responsive image function not available.
			?>
			<img class="card-img-top product-image-main"
				 src="<?php echo esc_url( $product->thumbnailUrl ?? '' ); ?>"
				 alt="<?php echo esc_attr( $product->name ); ?>"
				 loading="lazy"
				 role="button"
				 data-wp-class--visible="!context.isHovering">
			<?php
		}
		?>

		<?php
		// Hover image with smart responsive sizes.
		if ( ! empty( $hover_image_data ) && function_exists( 'peaches_generate_responsive_image_html' ) ) {
			echo peaches_generate_responsive_image_html(
				$hover_image_data,
				array(
					'class'                  => 'card-img-top product-image-hover',
					'alt'                    => esc_attr( $product->name . ' - hover image' ),
					'role'                   => 'button',
					'data-wp-on--click'      => 'actions.navigateToProduct',
					'data-wp-class--visible' => 'context.isHovering',
				)
			);
		}
		?>
	</div>

	<div
		class="card-body p-2 p-md-3 d-flex flex-wrap align-content-between"
		role="button"
		 data-wp-on--click="actions.navigateToProduct"
	>
		<h5 role="button" class="card-title">
			<?php echo esc_html( $product->name ); ?>
		</h5>
		<?php if ( ! empty( $product_subtitle ) ) : ?>
			<p class="card-subtitle mb-2 text-muted"><?php echo esc_html( $product_subtitle ); ?></p>
		<?php endif; ?>
	</div>

	<div class="card-footer p-2 p-md-3 border-0 hstack justify-content-between">
		<div class="card-text fw-bold lead">
			<?php echo esc_html( $formatted_price ); ?>
		</div>
		<?php if ( $show_add_to_cart ) : ?>
			<button title="<?php echo esc_html( $button_text ); ?>"
					class="add-to-cart btn pe-0"
					aria-label="<?php echo esc_html( $button_text ); ?>"
					data-wp-on--click="actions.addToCart"></button>
		<?php endif; ?>
	</div>
</div>

<?php
// Cache the rendered HTML using cache helper.
peaches_ecwid_end_block_cache( 'ecwid-product', $cache_manager, $cache_factors, 300 );
