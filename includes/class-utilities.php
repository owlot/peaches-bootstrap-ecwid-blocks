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
	 * Get the Ecwid shop page slug and path with multilingual support.
	 *
	 * This retrieves the store page slug and full path, taking into account
	 * parent pages if the store is on a nested page, current language, and
	 * user-configured language-specific shop paths.
	 *
	 * @since 0.1.2
	 *
	 * @param bool   $include_parents     Whether to include parent page slugs in the path.
	 * @param bool   $with_trailing_slash Whether to add a trailing slash.
	 * @param string $lang                Language code to get the path for. Empty for current language.
	 *
	 * @return string The store page slug or full path.
	 */
	public static function get_shop_path( $include_parents = true, $with_trailing_slash = true, $lang = '' ) {
		$shop_path = '';

		// First, check if there's a custom shop slug set in Ecwid settings
		$custom_shop_slug = '';

		// Try to get it from Ecwid settings if they're available
		if ( class_exists( 'Ecwid_Store_Page' ) && method_exists( 'Ecwid_Store_Page', 'get_store_url_prefix' ) ) {
			$custom_shop_slug = Ecwid_Store_Page::get_store_url_prefix();
		} else {
			// Fallback: check options directly
			$custom_shop_slug = get_option( 'ecwid_store_url_prefix' );
		}

		// If we found a custom slug in Ecwid settings, use it for single-language sites
		if ( ! empty( $custom_shop_slug ) && ! self::is_multilingual_site() ) {
			$shop_path = $custom_shop_slug;

			// Add trailing slash if requested
			if ( $with_trailing_slash ) {
				$shop_path .= '/';
			}

			return $shop_path;
		}

		// If no specific language is provided, get current language
		if ( empty( $lang ) ) {
			$lang = self::get_current_language();
		}

		// Get the default language
		$default_lang = '';
		if ( function_exists( 'pll_default_language' ) ) {
			$default_lang = pll_default_language( 'slug' );
		} elseif ( defined( 'ICL_LANGUAGE_CODE' ) && class_exists( 'SitePress' ) ) {
			global $sitepress;
			if ( $sitepress ) {
				$default_lang = $sitepress->get_default_language();
			}
		}

		// Try to get from multilingual settings first
		if ( class_exists( 'Peaches_Multilingual_Settings' ) ) {
			$settings_manager = Peaches_Multilingual_Settings::get_instance();
			$configured_path = $settings_manager->get_shop_path_for_language( $lang );

			if ( ! empty( $configured_path ) ) {
				$shop_path = $configured_path;
			}
		}

		// If no configured path found, fall back to previous logic
		if ( empty( $shop_path ) ) {
			// Get the store page ID - using the Ecwid constant if available
			$store_page_id = null;

			if ( defined( 'Ecwid_Store_Page::OPTION_MAIN_STORE_PAGE_ID' ) ) {
				$store_page_id = get_option( Ecwid_Store_Page::OPTION_MAIN_STORE_PAGE_ID );
			}

			// Fallback to our own stored option if Ecwid constant isn't available
			if ( ! $store_page_id ) {
				$store_page_id = get_option( 'ecwid_store_page_id' );
			}

			$has_translation = false;

			if ( $store_page_id ) {
				// Get the translated store page ID if we have a language
				if ( ! empty( $lang ) && $lang !== $default_lang ) {
					$translated_id = self::get_translated_post( $store_page_id, $lang );
					if ( $translated_id ) {
						$store_page_id = $translated_id;
						$has_translation = true;
					}
				}

				// Get the store page object
				$store_page = get_post( $store_page_id );

				if ( $store_page && isset( $store_page->post_name ) ) {
					// Use the page slug as the shop path
					$shop_path = $store_page->post_name;

					// If we should include parents and the page is nested, get the full path
					if ( $include_parents && $store_page->post_parent != 0 ) {
						$parent_slugs = array();
						$parent_id = $store_page->post_parent;

						while ( $parent_id ) {
							$parent = get_post( $parent_id );
							if ( $parent ) {
								$parent_slugs[] = $parent->post_name;
								$parent_id = $parent->post_parent;
							} else {
								$parent_id = 0;
							}
						}

						// Reverse the array to get the correct order
						$parent_slugs = array_reverse( $parent_slugs );

						// Prepend parent slugs to the shop path
						if ( ! empty( $parent_slugs ) ) {
							$shop_path = implode( '/', $parent_slugs ) . '/' . $shop_path;
						}
					}
				}
			}

			// If we still don't have a shop path, use language-specific defaults
			if ( empty( $shop_path ) ) {
				$shop_path = self::get_default_shop_path_for_language( $lang, $lang === $default_lang );
			}
		}

		// Add trailing slash if requested
		if ( $with_trailing_slash && ! empty( $shop_path ) ) {
			$shop_path .= '/';
		}

		return $shop_path;
	}

	/**
	 * Get default shop path for a specific language.
	 *
	 * @since 0.1.2
	 *
	 * @param string $language_code Language code.
	 * @param bool   $is_default    Whether this is the default language.
	 *
	 * @return string Default shop path for the language.
	 */
	private static function get_default_shop_path_for_language( $language_code, $is_default = false ) {
		// For default language, try to get from Ecwid first
		if ( $is_default ) {
			$ecwid_path = self::get_ecwid_shop_path();
			if ( ! empty( $ecwid_path ) ) {
				return trim( $ecwid_path, '/' );
			}
		}

		// Language-specific defaults
		$defaults = array(
			'en' => 'shop',
			'nl' => 'winkel',
			'fr' => 'boutique',
			'de' => 'geschaeft',
			'es' => 'tienda',
			'it' => 'negozio',
			'pt' => 'loja',
			'ru' => 'magazin',
			'zh' => 'shop',
			'ja' => 'shop',
		);

		return isset( $defaults[ $language_code ] ) ? $defaults[ $language_code ] : 'shop';
	}

	/**
	 * Get Ecwid shop path from settings.
	 *
	 * @since 0.1.2
	 *
	 * @return string|null Ecwid shop path or null if not set.
	 */
	private static function get_ecwid_shop_path() {
		// Try to get from Ecwid settings
		if ( class_exists( 'Ecwid_Store_Page' ) && method_exists( 'Ecwid_Store_Page', 'get_store_url_prefix' ) ) {
			return Ecwid_Store_Page::get_store_url_prefix();
		}

		// Fallback to option
		return get_option( 'ecwid_store_url_prefix' );
	}

	/**
	 * Check if the site is configured for multiple languages.
	 *
	 * @since 0.1.2
	 *
	 * @return bool True if multilingual plugin is active and configured.
	 */
	private static function is_multilingual_site() {
		// Check for Polylang
		if ( function_exists( 'pll_languages_list' ) ) {
			$languages = pll_languages_list();
			return is_array( $languages ) && count( $languages ) > 1;
		}

		// Check for WPML
		if ( function_exists( 'icl_get_languages' ) ) {
			$languages = icl_get_languages( 'skip_missing=0' );
			return is_array( $languages ) && count( $languages ) > 1;
		}

		return false;
	}
}
