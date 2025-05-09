<?php
/**
 * Utilities class
 *
 * Provides utility functions used throughout the plugin.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Ecwid_Utilities
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
class Peaches_Ecwid_Utilities {
	/**
	 * Sanitize attributes.
	 *
	 * @since 0.1.2
	 * @param array $attributes The attributes to sanitize.
	 * @return array The sanitized attributes.
	 */
	public static function sanitize_attributes($attributes) {
		if (!is_array($attributes)) {
			return array();
		}

		$sanitized = array();
		foreach ($attributes as $key => $value) {
			if (is_array($value)) {
				$sanitized[$key] = self::sanitize_attributes($value);
			} elseif (is_numeric($value)) {
				$sanitized[$key] = absint($value);
			} else {
				$sanitized[$key] = sanitize_text_field($value);
			}
		}
		return $sanitized;
	}

	/**
	 * Get current language.
	 *
	 * @since 0.1.2
	 * @return string The current language code.
	 */
	public static function get_current_language() {
		if (function_exists('pll_current_language')) {
			return pll_current_language();
		} elseif (defined('ICL_LANGUAGE_CODE')) {
			return ICL_LANGUAGE_CODE;
		}
		return '';
	}

	/**
	 * Get translated post.
	 *
	 * @since 0.1.2
	 * @param int    $post_id The post ID.
	 * @param string $lang    The language code.
	 * @return int The translated post ID.
	 */
	public static function get_translated_post($post_id, $lang = null) {
		if ($lang === null) {
			$lang = self::get_current_language();
		}

		if (function_exists('pll_get_post')) {
			return pll_get_post($post_id, $lang);
		} elseif (function_exists('icl_object_id')) {
			return icl_object_id($post_id, 'page', false, $lang);
		}

		return $post_id;
	}

	/**
	 * Set language for multilingual support.
	 *
	 * @since 0.1.2
	 * @return void
	 */
	public static function set_language() {
		// Polylang support
		if (function_exists('pll_current_language')) {
			add_filter('parse_request', function($wp) {
				if (preg_match('#^winkel/([^/]+)/?$#', $wp->request)) {
					$curlang = pll_current_language();
					if ($curlang) {
						// The correct way to set language in Polylang
						add_filter('pll_preferred_language', function() use ($curlang) {
							return $curlang;
						});

						// Set the language for Polylang
						if (function_exists('pll_set_language')) {
							pll_set_language($curlang);
						}
					}
				}
				return $wp;
			}, 1);
		}
		// WPML support
		elseif (defined('ICL_LANGUAGE_CODE') && class_exists('SitePress')) {
			global $sitepress;
			if ($sitepress) {
				add_filter('parse_request', function($wp) {
					if (preg_match('#^winkel/([^/]+)/?$#', $wp->request)) {
						if (defined('ICL_LANGUAGE_CODE')) {
							$sitepress->switch_lang(ICL_LANGUAGE_CODE);
						}
					}
					return $wp;
				}, 1);
			}
		}
	}

	/**
	 * Build gallery HTML based on layout.
	 *
	 * @since 0.1.2
	 * @param array  $images Array of image URLs.
	 * @param string $layout Gallery layout type.
	 * @return string HTML for gallery.
	 */
	public static function build_gallery_html($images, $layout = 'standard') {
		if (empty($images)) {
			return '';
		}

		switch ($layout) {
		case 'thumbnails-below':
			return self::build_gallery_thumbnails_below($images);
		case 'thumbnails-side':
			return self::build_gallery_thumbnails_side($images);
		default: // standard
			return self::build_gallery_standard($images);
		}
	}

	/**
	 * Build standard gallery layout.
	 *
	 * @since 0.1.2
	 * @param array $images Array of image URLs.
	 * @return string HTML for standard gallery.
	 */
	public static function build_gallery_standard($images) {
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
	 * Build gallery with thumbnails below.
	 *
	 * @since 0.1.2
	 * @param array $images Array of image URLs.
	 * @return string HTML for gallery with thumbnails below.
	 */
	public static function build_gallery_thumbnails_below($images) {
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
	 * Build gallery with thumbnails on the side.
	 *
	 * @since 0.1.2
	 * @param array $images Array of image URLs.
	 * @return string HTML for gallery with thumbnails on the side.
	 */
	public static function build_gallery_thumbnails_side($images) {
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

	/**
	 * Build add to cart form.
	 *
	 * @since 0.1.2
	 * @return string HTML for add to cart form.
	 */
	public static function build_add_to_cart_block() {
		ob_start();
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
	<?php echo  __('Add to Cart', 'peaches'); ?>
		</button>
			</div>
			</div>
<?php
		return ob_get_clean();
	}
}
