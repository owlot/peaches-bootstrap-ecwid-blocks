<?php

/**
 * Helper function to get product ID from slug with caching
 */
function peaches_get_product_id_from_slug($slug) {
	if ($slug) {
		$ecwid_store_id = EcwidPlatform::get_store_id();
	    $slug_api_url = "https://app.ecwid.com/storefront/api/v1/{$ecwid_store_id}/catalog/slug";

		$slug_response = wp_remote_post($slug_api_url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => json_encode(array(
				'slug' => $slug
			))
		));

	    if (!is_wp_error($slug_response) && wp_remote_retrieve_response_code($slug_response) === 200) {
			$slug_data = json_decode(wp_remote_retrieve_body($slug_response), true);

			// Check if we found a valid product
			if (!empty($slug_data) && $slug_data['type'] === 'product' && !empty($slug_data['entityId'])) {
				// Get the product ID
				return $slug_data['entityId'];
			}
	    }
	}

	return 0; // No product found
}


/**
 * Generate breadcrumb navigation for product detail page
 */
function peaches_ecwid_breadcrumbs($product) {
	if (!$product) {
		return '';
	}

	// Get store page URL - this should be the main page where your store is embedded
	$store_page_id = get_option('ecwid_store_page_id');
	$store_url = $store_page_id ? get_permalink($store_page_id) : home_url();
	$store_name = __('Shop', 'ecwid-shopping-cart');

	// Check if product has category info
	$category_name = '';
	$category_url = '';
	if (isset($product->categoryIds) && !empty($product->categoryIds) && is_array($product->categoryIds)) {
		$category_id = $product->categoryIds[0]; // Use first category

		// Try to get category info
		$api = new Ecwid_Api_V3();
		$category = $api->get_category($category_id);

		if ($category) {
			$category_name = $category->name;
			$category_url = $category->url;
		}
	}

	// Build breadcrumb HTML
	$breadcrumb = '<nav aria-label="breadcrumb">
		<ol class="breadcrumb">';

	// Home link
	$breadcrumb .= '<li class="breadcrumb-item"><a href="' . esc_url(home_url()) . '">' . __('Home', 'ecwid-shopping-cart') . '</a></li>';

	// Store link
	$breadcrumb .= '<li class="breadcrumb-item"><a href="' . esc_url($store_url) . '">' . esc_html($store_name) . '</a></li>';

	// Category link (if available)
	if ($category_name && $category_url) {
		$breadcrumb .= '<li class="breadcrumb-item"><a href="' . esc_url($category_url) . '">' . esc_html($category_name) . '</a></li>';
	}

	// Current product
	$breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . esc_html($product->name) . '</li>';

	$breadcrumb .= '</ol></nav>';

	return $breadcrumb;
}

/**
 * Generate related products carousel
 */
function peaches_ecwid_related_products($product) {
	if (!$product || empty($product->relatedProducts)) {
		return '';
	}

	$api = new Ecwid_Api_V3();
	$related_products = [];

	if(isset($product->relatedProducts->productIds) && is_array($product->relatedProducts->productIds)) {
		$related_products = $api->get_products([
			'productId' => join(',', $product->relatedProducts->productIds)
		]);

		if (!$related_products || !isset($related_products->items) || count($related_products->items) <= 1) {
			return '';
		}
	}

	if(isset($product->relatedProducts->relatedCategory) && $product->relatedProducts->relatedCategory->enabled) {
		$related_category = $product->relatedProducts->relatedCategory;

		$filter = [
			'visibleInStorefront' => true,
			'enabled' => true,
			'limit' => $related_category->productCount + 1 // Get one extra to exclude current product
		];

		if(isset($related_category->categoryId)) {
			$filter['category'] = $related_category->categoryId;
		}

		$related_products = $api->get_products($filter);

		if (!$related_products || !isset($related_products->items) || count($related_products->items) <= 1) {
			return '';
		}

		// Filter out current product
		$filtered_products = array_filter($related_products->items, function($item) use ($product) {
			return $item->id != $product->id;
		});

		// Get only the needed number of products
		$related_products = array_slice($filtered_products, 0, $related_category->productCount);

		// If after filtering we have no products, return empty
		if (!$related_products || !isset($related_products->items) || count($related_products->items) <= 1) {
			return '';
		}
	}

	// Build HTML
	$output = '<div class="related-products my-5">
		<h3>' . __('Related Products', 'ecwid-shopping-cart') . '</h3>
		<div class="row row-cols-2 row-cols-md-4 g-4">';

	foreach ($related_products->items as $related) {
		$output .= '<div class="col">';
		$output .= peaches_ecwid_product_render(array('id' => $related->id), null);
		$output .= '</div>';
	}

	$output .= '</div></div>';

	return $output;
}

/**
 * Helper function to build standard gallery layout
 */
function build_gallery_standard($images) {
	$output = '<div class="product-gallery standard-gallery">';

	if (!empty($images)) {
		$main_image = $images[0];
		$output .= '<div class="main-image mb-3">
			<img src="' . esc_url($main_image) . '" class="img-fluid rounded" alt="Product image">
			</div>';
	}

	$output .= '</div>';
	return $output;
}

/**
 * Helper function to build gallery with thumbnails below
 */
function build_gallery_thumbnails_below($images) {
	if (empty($images)) {
		return '';
	}

	$main_image = $images[0];

	$output = '<div class="product-gallery thumbnails-below-gallery">';
	$output .= '<div class="main-image mb-3">
		<img src="' . esc_url($main_image) . '" class="img-fluid rounded main-gallery-image" alt="Product image">
		</div>';

	if (count($images) > 1) {
		$output .= '<div class="thumbnails-row d-flex flex-wrap">';
		foreach ($images as $index => $image) {
			$active_class = ($index === 0) ? 'active' : '';
			$output .= '<div class="thumbnail-container p-1" style="width: 80px;">
				<img src="' . esc_url($image) . '"
				class="img-thumbnail thumbnail-image ' . $active_class . '"
					alt="Thumbnail"
					data-full-image="' . esc_url($image) . '">
					</div>';
		}
		$output .= '</div>';

		// Add JavaScript for thumbnail clicks
		$output .= '
	<script>
	document.addEventListener("DOMContentLoaded", function() {
	    const thumbnails = document.querySelectorAll(".thumbnail-image");
	    const mainImage = document.querySelector(".main-gallery-image");

	    thumbnails.forEach(function(thumbnail) {
		thumbnail.addEventListener("click", function() {
		    // Remove active class from all thumbnails
		    thumbnails.forEach(thumb => thumb.classList.remove("active"));

		    // Add active class to clicked thumbnail
		    this.classList.add("active");

		    // Update main image
		    mainImage.src = this.getAttribute("data-full-image");
		});
	    });
	});
	</script>';
	}

	$output .= '</div>';
	return $output;
}

/**
 * Helper function to build gallery with thumbnails on the side
 */
function build_gallery_thumbnails_side($images) {
	if (empty($images)) {
		return '';
	}

	$main_image = $images[0];

	$output = '<div class="product-gallery thumbnails-side-gallery">';
	$output .= '<div class="row">';

	// Thumbnails on the left (only if we have more than one image)
	if (count($images) > 1) {
		$output .= '<div class="col-2 d-flex flex-column">';
		foreach ($images as $index => $image) {
			$active_class = ($index === 0) ? 'active' : '';
			$output .= '<div class="thumbnail-container mb-2">
				<img src="' . esc_url($image) . '"
				class="img-thumbnail thumbnail-image ' . $active_class . '"
					alt="Thumbnail"
					data-full-image="' . esc_url($image) . '">
					</div>';
		}
		$output .= '</div>';

		// Main image on the right
		$output .= '<div class="col-10">';
	} else {
		// If only one image, use full width
		$output .= '<div class="col-12">';
	}

	$output .= '<div class="main-image">
		<img src="' . esc_url($main_image) . '" class="img-fluid rounded main-gallery-image" alt="Product image">
		</div>';
	$output .= '</div>'; // End column

	$output .= '</div>'; // End row

	// Add JavaScript for thumbnail clicks (only if we have more than one image)
	if (count($images) > 1) {
		$output .= '
	<script>
	document.addEventListener("DOMContentLoaded", function() {
	    const thumbnails = document.querySelectorAll(".thumbnail-image");
	    const mainImage = document.querySelector(".main-gallery-image");

	    thumbnails.forEach(function(thumbnail) {
		thumbnail.addEventListener("click", function() {
		    // Remove active class from all thumbnails
		    thumbnails.forEach(thumb => thumb.classList.remove("active"));

		    // Add active class to clicked thumbnail
		    this.classList.add("active");

		    // Update main image
		    mainImage.src = this.getAttribute("data-full-image");
		});
	    });
	});
	</script>';
	}

	$output .= '</div>'; // End gallery
	return $output;
}

function build_add_to_cart_block() {
	?>
		<div class="add-to-cart-form d-flex align-items-center mb-3">
			<div class="me-3">
				<div class="input-group" style="max-width: 150px;">
					<button class="btn btn-outline-secondary quantity-decrease" type="button" data-wp-on--click="actions.decreaseAmount">-</button>
					<input type="number" class="form-control text-center product-quantity" data-wp-bind--value="context.amount" min="1" data-wp-on--input="actions.setAmount">
					<button class="btn btn-outline-secondary quantity-increase" type="button" data-wp-on--click="actions.increaseAmount">+</button>
				</div>
			</div>
			<div>
				<button class="btn btn-primary add-to-cart-button" data-wp-on--click="actions.addToCart">
					<?php echo  __('Add to Cart', 'ecwid-shopping-cart'); ?>
				</button>
			</div>
		</div>

	<?php
}

?>
