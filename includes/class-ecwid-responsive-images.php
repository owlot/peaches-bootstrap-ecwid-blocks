<?php
/**
 * Ecwid Responsive Images Class - Simplified
 *
 * Handles responsive images for legacy Ecwid content and dynamic images.
 * Gallery blocks are now handled server-side with proper responsive support.
 *
 * @package PeachesBootstrapEcwidBlocks
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Ecwid_Responsive_Images
 *
 * Simplified responsive images handling for Ecwid content.
 *
 * @package PeachesBootstrapEcwidBlocks
 * @since   1.0.0
 */
class Peaches_Ecwid_Responsive_Images {

	/**
	 * Singleton instance of the class.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Peaches_Ecwid_Responsive_Images|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @return Peaches_Ecwid_Responsive_Images The singleton instance
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent external instantiation.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Process legacy Ecwid images in content (but not our gallery blocks)
		add_filter('the_content', array($this, 'process_content_images'), 20);

		// Enqueue assets when needed
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
	}

	/**
	 * Process Ecwid images in post content (excluding gallery blocks)
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content
	 *
	 * @return string Modified content
	 */
	public function process_content_images($content) {
		if (empty($content) || is_admin()) {
			return $content;
		}

		// Only process if content has img tags
		if (strpos($content, '<img') === false) {
			return $content;
		}

		$pattern = '/<img([^>]+)>/i';

		return preg_replace_callback($pattern, function($matches) {
			$img_tag = $matches[0];
			$img_attributes = $matches[1];

			// Skip images that are already responsive or in gallery blocks
			if (strpos($img_attributes, 'srcset=') !== false ||
			    strpos($img_attributes, 'peaches-responsive-img') !== false ||
			    strpos($img_attributes, 'lightbox-image-original') !== false) {
				return $img_tag;
			}

			// Extract src attribute
			if (!preg_match('/src=["\']([^"\']+)["\']/', $img_attributes, $src_matches)) {
				return $img_tag;
			}

			$src = $src_matches[1];

			// Only process Ecwid images
			if (!Peaches_Ecwid_Image_Utilities::is_ecwid_image($src)) {
				return $img_tag;
			}

			return $this->add_responsive_attributes($img_tag, $src);
		}, $content);
	}

	/**
	 * Add responsive attributes to Ecwid image tag
	 *
	 * @since 1.0.0
	 *
	 * @param string $img_tag Original img tag
	 * @param string $src     Image source URL
	 *
	 * @return string Modified img tag
	 */
	private function add_responsive_attributes($img_tag, $src) {
		$srcset = Peaches_Ecwid_Image_Utilities::generate_ecwid_srcset($src);
		$sizes = Peaches_Ecwid_Image_Utilities::generate_bootstrap_sizes_attribute('default');

		if (empty($srcset)) {
			return $img_tag;
		}

		$modified_tag = $img_tag;

		// Add srcset
		$modified_tag = str_replace('<img', '<img srcset="' . esc_attr($srcset) . '"', $modified_tag);

		// Add sizes
		$modified_tag = str_replace('<img', '<img sizes="' . esc_attr($sizes) . '"', $modified_tag);

		// Add responsive classes
		if (preg_match('/class=["\']([^"\']*)["\']/', $modified_tag, $class_matches)) {
			$existing_class = $class_matches[1];
			$new_class = trim($existing_class . ' peaches-responsive-img peaches-ecwid-img');
			$modified_tag = str_replace(
				'class="' . $existing_class . '"',
				'class="' . esc_attr($new_class) . '"',
				$modified_tag
			);
		} else {
			$modified_tag = str_replace('<img', '<img class="peaches-responsive-img peaches-ecwid-img"', $modified_tag);
		}

		// Add data attributes
		$modified_tag = str_replace('<img', '<img data-responsive-type="ecwid"', $modified_tag);

		// Add loading attribute if not present
		if (!preg_match('/loading=/', $modified_tag)) {
			$modified_tag = str_replace('<img', '<img loading="lazy"', $modified_tag);
		}

		// Add decoding attribute if not present
		if (!preg_match('/decoding=/', $modified_tag)) {
			$modified_tag = str_replace('<img', '<img decoding="async"', $modified_tag);
		}

		// Add store ID if detectable
		$store_id = Peaches_Ecwid_Image_Utilities::get_ecwid_store_id($src);
		if ($store_id && !preg_match('/data-ecwid-store-id=/', $modified_tag)) {
			$modified_tag = str_replace('<img', '<img data-ecwid-store-id="' . esc_attr($store_id) . '"', $modified_tag);
		}

		return $modified_tag;
	}

	/**
	 * Enqueue assets when needed
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if (is_admin() || !$this->should_enqueue_assets()) {
			return;
		}

		// Enqueue CSS (minimal styling for responsive images)
		wp_enqueue_style(
			'peaches-ecwid-responsive-images',
			PEACHES_ECWID_PLUGIN_URL . 'assets/css/ecwid-responsive-images.css',
			array(),
			PEACHES_ECWID_VERSION
		);

		// Enqueue JavaScript (minimal enhancement for responsive images)
		wp_enqueue_script(
			'peaches-ecwid-responsive-images',
			PEACHES_ECWID_PLUGIN_URL . 'assets/js/ecwid-responsive-images.js',
			array(),
			PEACHES_ECWID_VERSION,
			true
		);
	}

	/**
	 * Check if assets should be enqueued
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if assets should be enqueued
	 */
	private function should_enqueue_assets() {
		global $post;

		// Check if current post content contains Ecwid images
		if ($post && !empty($post->post_content)) {
			// Simple check for Ecwid image domains
			$ecwid_patterns = array(
				'images-cdn.ecwid.com',
				'd2j6dbq0eux0bg.cloudfront.net',
				'app.ecwid.com'
			);

			foreach ($ecwid_patterns as $pattern) {
				if (strpos($post->post_content, $pattern) !== false) {
					return true;
				}
			}
		}

		// Always enqueue on archive pages where excerpts might contain Ecwid images
		if (is_home() || is_archive() || is_search()) {
			return true;
		}

		return false;
	}

	/**
	 * Get responsive image HTML for Ecwid image (public API)
	 *
	 * @since 1.0.0
	 *
	 * @param string $image_url Ecwid image URL
	 * @param array  $args      Arguments for image generation
	 *
	 * @return string Complete img HTML tag
	 */
	public function get_responsive_image($image_url, $args = array()) {
		return Peaches_Ecwid_Image_Utilities::get_responsive_image_html($image_url, $args);
	}

	/**
	 * Get responsive image attributes for Ecwid image (public API)
	 *
	 * @since 1.0.0
	 *
	 * @param string $image_url Ecwid image URL
	 * @param array  $args      Arguments for image generation
	 *
	 * @return array Image attributes array
	 */
	public function get_responsive_image_attributes($image_url, $args = array()) {
		return Peaches_Ecwid_Image_Utilities::get_responsive_image_attributes($image_url, $args);
	}
}
