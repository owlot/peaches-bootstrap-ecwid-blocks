<?php
/**
 * Consolidated Utilities class
 *
 * Provides utility functions used throughout the plugin with proper error handling and logging.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Ecwid_Utilities
 *
 * Centralized utility functions for the plugin.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Ecwid_Utilities {

	/**
	 * Plugin settings cache.
	 *
	 * @since 0.2.0
	 * @var array|null
	 */
	private static $settings_cache = null;

	/**
	 * Language detection cache.
	 *
	 * @since 0.2.0
	 * @var array
	 */
	private static $language_cache = array();

	/**
	 * Sanitize attributes with enhanced validation.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $attributes The attributes to sanitize.
	 *
	 * @return array The sanitized attributes.
	 *
	 * @throws InvalidArgumentException If attributes are not an array or string.
	 */
	public static function sanitize_attributes($attributes) {
		if (is_string($attributes)) {
			return array(sanitize_text_field($attributes));
		}

		if (!is_array($attributes)) {
			self::log_error('Invalid attributes type provided to sanitize_attributes', array(
				'type' => gettype($attributes),
				'value' => $attributes
			));
			return array();
		}

		$sanitized = array();
		foreach ($attributes as $key => $value) {
			$sanitized_key = sanitize_key($key);

			if (is_array($value)) {
				$sanitized[$sanitized_key] = self::sanitize_attributes($value);
			} elseif (is_numeric($value)) {
				$sanitized[$sanitized_key] = is_float($value) ? floatval($value) : absint($value);
			} elseif (is_bool($value)) {
				$sanitized[$sanitized_key] = (bool) $value;
			} else {
				$sanitized[$sanitized_key] = sanitize_text_field($value);
			}
		}

		return $sanitized;
	}

	/**
	 * Get current language with caching and fallback.
	 *
	 * @since 0.2.0
	 *
	 * @param bool $force_refresh Force refresh of cache.
	 *
	 * @return string The current language code.
	 */
	public static function get_current_language($force_refresh = false) {
		$cache_key = 'current_language';

		if (!$force_refresh && isset(self::$language_cache[$cache_key])) {
			return self::$language_cache[$cache_key];
		}

		$language = '';

		try {
			// Polylang support
			if (function_exists('pll_current_language')) {
				$language = pll_current_language();
			}
			// WPML support
			elseif (defined('ICL_LANGUAGE_CODE')) {
				$language = ICL_LANGUAGE_CODE;
			}
			// WordPress locale fallback
			else {
				$locale = get_locale();
				$language = substr($locale, 0, 2);
			}

			// Validate language code
			if (empty($language) || !preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $language)) {
				$language = 'en';
			}

		} catch (Exception $e) {
			self::log_error('Error detecting current language', array(
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			));
			$language = 'en';
		}

		self::$language_cache[$cache_key] = $language;
		return $language;
	}

	/**
	 * Get available languages from multilingual plugins.
	 *
	 * @since 0.2.0
	 *
	 * @param bool $force_refresh Force refresh of cache.
	 *
	 * @return array Array of languages with codes as keys.
	 */
	public static function get_available_languages($force_refresh = false) {
		$cache_key = 'available_languages';

		if (!$force_refresh && isset(self::$language_cache[$cache_key])) {
			return self::$language_cache[$cache_key];
		}

		$languages = array();

		try {
			// Polylang support
			if (function_exists('pll_languages_list') && function_exists('pll_default_language')) {
				$lang_codes = pll_languages_list(array('fields' => 'slug'));
				$default_lang = pll_default_language('slug');

				if (is_array($lang_codes)) {
					foreach ($lang_codes as $code) {
						$languages[$code] = array(
							'code' => $code,
							'is_default' => ($code === $default_lang),
							'name' => function_exists('pll_get_language') ? pll_get_language($code, 'name') : $code
						);
					}
				}
			}
			// WPML support
			elseif (function_exists('icl_get_languages')) {
				$wpml_languages = icl_get_languages('skip_missing=0');
				$default_lang = apply_filters('wpml_default_language', null);

				if (is_array($wpml_languages)) {
					foreach ($wpml_languages as $code => $lang) {
						$languages[$code] = array(
							'code' => $code,
							'is_default' => ($code === $default_lang),
							'name' => isset($lang['native_name']) ? $lang['native_name'] : $code
						);
					}
				}
			}
			// Fallback to English only
			else {
				$languages['en'] = array(
					'code' => 'en',
					'is_default' => true,
					'name' => 'English'
				);
			}

		} catch (Exception $e) {
			self::log_error('Error getting available languages', array(
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			));

			// Fallback
			$languages = array(
				'en' => array(
					'code' => 'en',
					'is_default' => true,
					'name' => 'English'
				)
			);
		}

		self::$language_cache[$cache_key] = $languages;
		return $languages;
	}

	/**
	 * Get translated post with enhanced error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param int         $post_id The post ID.
	 * @param string|null $lang    The language code.
	 *
	 * @return int The translated post ID.
	 *
	 * @throws InvalidArgumentException If post ID is invalid.
	 */
	public static function get_translated_post($post_id, $lang = null) {
		if (!is_numeric($post_id) || $post_id <= 0) {
			throw new InvalidArgumentException('Invalid post ID provided to get_translated_post');
		}

		$post_id = absint($post_id);

		if ($lang === null) {
			$lang = self::get_current_language();
		}

		try {
			// Polylang support
			if (function_exists('pll_get_post')) {
				$translated_id = pll_get_post($post_id, $lang);
				return $translated_id ? absint($translated_id) : $post_id;
			}
			// WPML support
			elseif (function_exists('icl_object_id')) {
				$translated_id = icl_object_id($post_id, 'page', false, $lang);
				return $translated_id ? absint($translated_id) : $post_id;
			}

		} catch (Exception $e) {
			self::log_error('Error getting translated post', array(
				'post_id' => $post_id,
				'language' => $lang,
				'error' => $e->getMessage()
			));
		}

		return $post_id;
	}

	/**
	 * Check if the site is configured for multiple languages.
	 *
	 * @since 0.2.0
	 *
	 * @return bool True if multilingual plugin is active and configured.
	 */
	public static function is_multilingual_site() {
		static $is_multilingual = null;

		if ($is_multilingual !== null) {
			return $is_multilingual;
		}

		try {
			// Check for Polylang
			if (function_exists('pll_languages_list')) {
				$languages = pll_languages_list();
				$is_multilingual = is_array($languages) && count($languages) > 1;
				return $is_multilingual;
			}

			// Check for WPML
			if (function_exists('icl_get_languages')) {
				$languages = icl_get_languages('skip_missing=0');
				$is_multilingual = is_array($languages) && count($languages) > 1;
				return $is_multilingual;
			}

		} catch (Exception $e) {
			self::log_error('Error checking if site is multilingual', array(
				'error' => $e->getMessage()
			));
		}

		$is_multilingual = false;
		return $is_multilingual;
	}

	/**
	 * Get the Ecwid shop page slug and path with multilingual support.
	 *
	 * @since 0.2.0
	 *
	 * @param bool        $include_parents     Whether to include parent page slugs in the path.
	 * @param bool        $with_trailing_slash Whether to add a trailing slash.
	 * @param string|null $lang                Language code to get the path for. Empty for current language.
	 *
	 * @return string The store page slug or full path.
	 *
	 * @throws InvalidArgumentException If language code format is invalid.
	 */
	public static function get_shop_path($include_parents = true, $with_trailing_slash = true, $lang = null) {
		if ($lang !== null && !empty($lang) && !preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $lang)) {
			throw new InvalidArgumentException('Invalid language code format provided to get_shop_path');
		}

		$shop_path = '';

		try {
			// Check for custom shop slug in Ecwid settings
			$custom_shop_slug = self::get_ecwid_shop_slug();

			// For single-language sites, use custom slug if available
			if (!empty($custom_shop_slug) && !self::is_multilingual_site()) {
				$shop_path = $custom_shop_slug;
			} else {
				// Get language-specific path
				if ($lang === null) {
					$lang = self::get_current_language();
				}

				// Try multilingual settings first
				$shop_path = self::get_multilingual_shop_path($lang);

				// Fallback to store page method
				if (empty($shop_path)) {
					$shop_path = self::get_store_page_path($include_parents, $lang);
				}

				// Ultimate fallback to language-specific defaults
				if (empty($shop_path)) {
					$shop_path = self::get_default_shop_path_for_language($lang);
				}
			}

			// Add trailing slash if requested
			if ($with_trailing_slash && !empty($shop_path)) {
				$shop_path = rtrim($shop_path, '/') . '/';
			}

		} catch (Exception $e) {
			self::log_error('Error getting shop path', array(
				'language' => $lang,
				'include_parents' => $include_parents,
				'error' => $e->getMessage()
			));

			// Fallback
			$shop_path = $with_trailing_slash ? 'shop/' : 'shop';
		}

		return $shop_path;
	}

	/**
	 * Get Ecwid shop slug from settings.
	 *
	 * @since 0.2.0
	 *
	 * @return string|null Ecwid shop slug or null if not set.
	 */
	private static function get_ecwid_shop_slug() {
		try {
			if (class_exists('Ecwid_Store_Page') && method_exists('Ecwid_Store_Page', 'get_store_url_prefix')) {
				return Ecwid_Store_Page::get_store_url_prefix();
			}

			return get_option('ecwid_store_url_prefix');

		} catch (Exception $e) {
			self::log_error('Error getting Ecwid shop slug', array(
				'error' => $e->getMessage()
			));
			return null;
		}
	}

	/**
	 * Get multilingual shop path from settings.
	 *
	 * @since 0.2.0
	 *
	 * @param string $lang Language code.
	 *
	 * @return string Shop path or empty string.
	 */
	private static function get_multilingual_shop_path($lang) {
		try {
			if (class_exists('Peaches_Multilingual_Settings')) {
				$settings_manager = Peaches_Multilingual_Settings::get_instance();
				return $settings_manager->get_shop_path_for_language($lang);
			}
		} catch (Exception $e) {
			self::log_error('Error getting multilingual shop path', array(
				'language' => $lang,
				'error' => $e->getMessage()
			));
		}

		return '';
	}

	/**
	 * Get store page path.
	 *
	 * @since 0.2.0
	 *
	 * @param bool   $include_parents Whether to include parent pages.
	 * @param string $lang           Language code.
	 *
	 * @return string Store page path.
	 */
	private static function get_store_page_path($include_parents, $lang) {
		try {
			$store_page_id = self::get_store_page_id();

			if (!$store_page_id) {
				return '';
			}

			// Get translated page ID
			$default_lang = self::get_default_language();
			if (!empty($lang) && $lang !== $default_lang) {
				$translated_id = self::get_translated_post($store_page_id, $lang);
				if ($translated_id) {
					$store_page_id = $translated_id;
				}
			}

			$store_page = get_post($store_page_id);
			if (!$store_page || !isset($store_page->post_name)) {
				return '';
			}

			$shop_path = $store_page->post_name;

			// Include parent pages if requested
			if ($include_parents && $store_page->post_parent != 0) {
				$parent_slugs = self::get_parent_page_slugs($store_page->post_parent);
				if (!empty($parent_slugs)) {
					$shop_path = implode('/', array_reverse($parent_slugs)) . '/' . $shop_path;
				}
			}

			return $shop_path;

		} catch (Exception $e) {
			self::log_error('Error getting store page path', array(
				'include_parents' => $include_parents,
				'language' => $lang,
				'error' => $e->getMessage()
			));
			return '';
		}
	}

	/**
	 * Get store page ID from Ecwid settings.
	 *
	 * @since 0.2.0
	 *
	 * @return int|null Store page ID or null if not found.
	 */
	private static function get_store_page_id() {
		try {
			if (defined('Ecwid_Store_Page::OPTION_MAIN_STORE_PAGE_ID')) {
				$store_page_id = get_option(Ecwid_Store_Page::OPTION_MAIN_STORE_PAGE_ID);
				if ($store_page_id) {
					return absint($store_page_id);
				}
			}

			$store_page_id = get_option('ecwid_store_page_id');
			return $store_page_id ? absint($store_page_id) : null;

		} catch (Exception $e) {
			self::log_error('Error getting store page ID', array(
				'error' => $e->getMessage()
			));
			return null;
		}
	}

	/**
	 * Get default language from multilingual plugins.
	 *
	 * @since 0.2.0
	 *
	 * @return string Default language code.
	 */
	private static function get_default_language() {
		static $default_language = null;

		if ($default_language !== null) {
			return $default_language;
		}

		try {
			if (function_exists('pll_default_language')) {
				$default_language = pll_default_language('slug');
			} elseif (defined('ICL_LANGUAGE_CODE') && class_exists('SitePress')) {
				global $sitepress;
				if ($sitepress && method_exists($sitepress, 'get_default_language')) {
					$default_language = $sitepress->get_default_language();
				}
			}

			if (empty($default_language)) {
				$default_language = 'en';
			}

		} catch (Exception $e) {
			self::log_error('Error getting default language', array(
				'error' => $e->getMessage()
			));
			$default_language = 'en';
		}

		return $default_language;
	}

	/**
	 * Get parent page slugs recursively.
	 *
	 * @since 0.2.0
	 *
	 * @param int $parent_id Parent page ID.
	 *
	 * @return array Array of parent slugs.
	 */
	private static function get_parent_page_slugs($parent_id) {
		$parent_slugs = array();
		$max_depth = 10; // Prevent infinite loops
		$current_depth = 0;

		while ($parent_id && $current_depth < $max_depth) {
			$parent = get_post($parent_id);
			if ($parent && isset($parent->post_name)) {
				$parent_slugs[] = $parent->post_name;
				$parent_id = $parent->post_parent;
				$current_depth++;
			} else {
				break;
			}
		}

		return $parent_slugs;
	}

	/**
	 * Get default shop path for a specific language.
	 *
	 * @since 0.2.0
	 *
	 * @param string $language_code Language code.
	 *
	 * @return string Default shop path for the language.
	 */
	private static function get_default_shop_path_for_language($language_code) {
		// For default language, try to get from Ecwid first
		$default_lang = self::get_default_language();
		if ($language_code === $default_lang) {
			$ecwid_path = self::get_ecwid_shop_slug();
			if (!empty($ecwid_path)) {
				return trim($ecwid_path, '/');
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

		return isset($defaults[$language_code]) ? $defaults[$language_code] : 'shop';
	}

	/**
	 * Build gallery HTML based on layout with enhanced error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param array  $images Array of image URLs.
	 * @param string $layout Gallery layout type.
	 *
	 * @return string HTML for gallery.
	 *
	 * @throws InvalidArgumentException If images is not an array.
	 */
	public static function build_gallery_html($images, $layout = 'standard') {
		if (!is_array($images)) {
			throw new InvalidArgumentException('Images parameter must be an array');
		}

		if (empty($images)) {
			return '';
		}

		// Sanitize layout parameter
		$allowed_layouts = array('standard', 'thumbnails-below', 'thumbnails-side');
		if (!in_array($layout, $allowed_layouts, true)) {
			self::log_error('Invalid gallery layout provided', array(
				'layout' => $layout,
				'allowed' => $allowed_layouts
			));
			$layout = 'standard';
		}

		try {
			switch ($layout) {
				case 'thumbnails-below':
					return self::build_gallery_thumbnails_below($images);
				case 'thumbnails-side':
					return self::build_gallery_thumbnails_side($images);
				default:
					return self::build_gallery_standard($images);
			}
		} catch (Exception $e) {
			self::log_error('Error building gallery HTML', array(
				'layout' => $layout,
				'image_count' => count($images),
				'error' => $e->getMessage()
			));
			return '';
		}
	}

	/**
	 * Build standard gallery layout.
	 *
	 * @since 0.2.0
	 *
	 * @param array $images Array of image URLs.
	 *
	 * @return string HTML for standard gallery.
	 */
	private static function build_gallery_standard($images) {
		$output = '<div class="product-gallery standard-gallery">';

		if (!empty($images)) {
			$main_image = esc_url($images[0]);
			$output .= '<div class="main-image mb-3">
				<img src="' . $main_image . '" class="img-fluid rounded" alt="' . esc_attr__('Product image', 'peaches') . '" loading="lazy">
				</div>';
		}

		$output .= '</div>';
		return $output;
	}

	/**
	 * Build gallery with thumbnails below.
	 *
	 * @since 0.2.0
	 *
	 * @param array $images Array of image URLs.
	 *
	 * @return string HTML for gallery with thumbnails below.
	 */
	private static function build_gallery_thumbnails_below($images) {
		if (empty($images)) {
			return '';
		}

		$main_image = esc_url($images[0]);

		$output = '<div class="product-gallery thumbnails-below-gallery">';
		$output .= '<div class="main-image mb-3">
			<img src="' . $main_image . '" class="img-fluid rounded main-gallery-image" alt="' . esc_attr__('Product image', 'peaches') . '" loading="lazy">
			</div>';

		if (count($images) > 1) {
			$output .= '<div class="thumbnails-row d-flex flex-wrap">';
			foreach ($images as $index => $image) {
				$image_url = esc_url($image);
				$active_class = ($index === 0) ? 'active' : '';
				$output .= '<div class="thumbnail-container p-1" style="width: 80px;">
					<img src="' . $image_url . '"
					class="img-thumbnail thumbnail-image ' . esc_attr($active_class) . '"
						alt="' . esc_attr__('Thumbnail', 'peaches') . '"
						data-full-image="' . $image_url . '"
						loading="lazy">
						</div>';
			}
			$output .= '</div>';

			$output .= self::get_gallery_javascript();
		}

		$output .= '</div>';
		return $output;
	}

	/**
	 * Build gallery with thumbnails on the side.
	 *
	 * @since 0.2.0
	 *
	 * @param array $images Array of image URLs.
	 *
	 * @return string HTML for gallery with thumbnails on the side.
	 */
	private static function build_gallery_thumbnails_side($images) {
		if (empty($images)) {
			return '';
		}

		$main_image = esc_url($images[0]);

		$output = '<div class="product-gallery thumbnails-side-gallery">';
		$output .= '<div class="row">';

		// Thumbnails on the left (only if we have more than one image)
		if (count($images) > 1) {
			$output .= '<div class="col-2 d-flex flex-column">';
			foreach ($images as $index => $image) {
				$image_url = esc_url($image);
				$active_class = ($index === 0) ? 'active' : '';
				$output .= '<div class="thumbnail-container mb-2">
					<img src="' . $image_url . '"
					class="img-thumbnail thumbnail-image ' . esc_attr($active_class) . '"
						alt="' . esc_attr__('Thumbnail', 'peaches') . '"
						data-full-image="' . $image_url . '"
						loading="lazy">
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
			<img src="' . $main_image . '" class="img-fluid rounded main-gallery-image" alt="' . esc_attr__('Product image', 'peaches') . '" loading="lazy">
			</div>';
		$output .= '</div>'; // End column

		$output .= '</div>'; // End row

		// Add JavaScript for thumbnail clicks (only if we have more than one image)
		if (count($images) > 1) {
			$output .= self::get_gallery_javascript();
		}

		$output .= '</div>'; // End gallery
		return $output;
	}

	/**
	 * Get gallery JavaScript for thumbnail interactions.
	 *
	 * @since 0.2.0
	 *
	 * @return string JavaScript code for gallery interactions.
	 */
	private static function get_gallery_javascript() {
		return '
		<script>
		document.addEventListener("DOMContentLoaded", function() {
			const thumbnails = document.querySelectorAll(".thumbnail-image");
			const mainImage = document.querySelector(".main-gallery-image");

			if (!mainImage) return;

			thumbnails.forEach(function(thumbnail) {
				thumbnail.addEventListener("click", function() {
					// Remove active class from all thumbnails
					thumbnails.forEach(thumb => thumb.classList.remove("active"));

					// Add active class to clicked thumbnail
					this.classList.add("active");

					// Update main image
					const fullImageUrl = this.getAttribute("data-full-image");
					if (fullImageUrl) {
						mainImage.src = fullImageUrl;
					}
				});
			});
		});
		</script>';
	}

	/**
	 * Get plugin settings with caching.
	 *
	 * @since 0.2.0
	 *
	 * @param bool $force_refresh Force refresh of cache.
	 *
	 * @return array Plugin settings.
	 */
	public static function get_plugin_settings($force_refresh = false) {
		if ($force_refresh || self::$settings_cache === null) {
			try {
				if (class_exists('Peaches_Ecwid_Settings')) {
					$settings_manager = Peaches_Ecwid_Settings::get_instance();
					self::$settings_cache = $settings_manager->get_settings();
				} else {
					self::$settings_cache = array(
						'cache_duration' => 60,
						'debug_mode' => false,
						'enable_redis' => false,
					);
				}
			} catch (Exception $e) {
				self::log_error('Error getting plugin settings', array(
					'error' => $e->getMessage()
				));
				self::$settings_cache = array();
			}
		}

		return self::$settings_cache;
	}

	/**
	 * Log error messages with context.
	 *
	 * @since 0.2.0
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	public static function log_error($message, $context = array()) {
		if (!is_string($message)) {
			$message = 'Non-string error message provided';
		}

		$log_message = '[Peaches Ecwid Utilities] ' . $message;

		if (!empty($context) && is_array($context)) {
			$log_message .= ' - Context: ' . wp_json_encode($context);
		}

		error_log($log_message);
	}

	/**
	 * Validate and sanitize URL.
	 *
	 * @since 0.2.0
	 *
	 * @param string $url     URL to validate.
	 * @param bool   $require_https Whether to require HTTPS.
	 *
	 * @return string|false Validated URL or false if invalid.
	 */
	public static function validate_url($url, $require_https = false) {
		if (!is_string($url) || empty($url)) {
			return false;
		}

		$url = esc_url_raw($url);

		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			return false;
		}

		if ($require_https && strpos($url, 'https://') !== 0) {
			return false;
		}

		return $url;
	}

	/**
	 * Format file size in human readable format.
	 *
	 * @since 0.2.0
	 *
	 * @param int $bytes     Number of bytes.
	 * @param int $precision Decimal precision.
	 *
	 * @return string Formatted file size.
	 */
	public static function format_file_size($bytes, $precision = 2) {
		$bytes = max($bytes, 0);
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

		for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
			$bytes /= 1024;
		}

		return round($bytes, $precision) . ' ' . $units[$i];
	}

	/**
	 * Clear all utility caches.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public static function clear_caches() {
		self::$settings_cache = null;
		self::$language_cache = array();
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @since 0.2.0
	 *
	 * @return bool True if debug mode is enabled.
	 */
	public static function is_debug_mode() {
		$settings = self::get_plugin_settings();
		return !empty($settings['debug_mode']);
	}

	/**
	 * Validate media type from file extension or MIME type.
	 *
	 * @since 0.2.0
	 *
	 * @param string $file_path_or_url File path or URL.
	 * @param string $mime_type        Optional MIME type.
	 *
	 * @return string Media type (image, video, audio, document).
	 */
	public static function get_media_type($file_path_or_url, $mime_type = '') {
		// Check MIME type first if provided
		if (!empty($mime_type)) {
			if (strpos($mime_type, 'image/') === 0) {
				return 'image';
			}
			if (strpos($mime_type, 'video/') === 0) {
				return 'video';
			}
			if (strpos($mime_type, 'audio/') === 0) {
				return 'audio';
			}
			if (strpos($mime_type, 'application/pdf') === 0 || strpos($mime_type, 'text/') === 0) {
				return 'document';
			}
		}

		// Parse file extension
		$extension = strtolower(pathinfo($file_path_or_url, PATHINFO_EXTENSION));

		$type_map = array(
			'image' => array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff'),
			'video' => array('mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'm4v', '3gp', 'mkv'),
			'audio' => array('mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma'),
			'document' => array('pdf', 'doc', 'docx', 'txt', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx')
		);

		foreach ($type_map as $type => $extensions) {
			if (in_array($extension, $extensions, true)) {
				return $type;
			}
		}

		// Check for video hosting patterns
		if (preg_match('/(?:youtube\.com|youtu\.be|vimeo\.com|wistia\.com)/i', $file_path_or_url)) {
			return 'video';
		}

		// Default to image
		return 'image';
	}
}
