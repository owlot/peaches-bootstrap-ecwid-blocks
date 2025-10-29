<?php
/**
 * Product Media Manager class
 *
 * Handles media management for product settings including WordPress uploads,
 * external URLs, and Ecwid media selection with comprehensive error handling.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Product_Media_Manager
 *
 * Manages product media with comprehensive error handling and validation.
 * Implements proper interface and WordPress coding standards.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.1
 */
class Peaches_Product_Media_Manager implements Peaches_Product_Media_Manager_Interface {

	/**
	 * Ecwid API instance.
	 *
	 * @since  0.2.1
	 * @access private
	 * @var    Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Media Tags Manager instance.
	 *
	 * @since  0.2.1
	 * @access private
	 * @var    Peaches_Media_Tags_Manager
	 */
	private $media_tags_manager;

	/**
	 * Cache for media data.
	 *
	 * @since  0.2.1
	 * @access private
	 * @var    array
	 */
	private $cache = array();

	/**
	 * Supported media types.
	 *
	 * @since  0.2.1
	 * @access private
	 * @var    array
	 */
	private $supported_media_types = array( 'upload', 'url', 'ecwid' );

	/**
	 * Constructor.
	 *
	 * @since 0.2.1
	 *
	 * @param Peaches_Ecwid_API_Interface $ecwid_api          Ecwid API instance.
	 * @param Peaches_Media_Tags_Manager  $media_tags_manager Media tags manager instance.
	 *
	 * @throws InvalidArgumentException If required parameters are invalid.
	 */
	public function __construct( $ecwid_api, $media_tags_manager ) {
		if ( ! $ecwid_api instanceof Peaches_Ecwid_API_Interface ) {
			throw new InvalidArgumentException( 'Ecwid API instance is required' );
		}

		if ( ! $media_tags_manager instanceof Peaches_Media_Tags_Manager ) {
			throw new InvalidArgumentException( 'Media Tags Manager instance is required' );
		}

		$this->ecwid_api          = $ecwid_api;
		$this->media_tags_manager = $media_tags_manager;

		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since  0.2.1
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_preview_media_url', array( $this, 'ajax_preview_media_url' ) );
		add_action( 'wp_ajax_load_ecwid_media', array( $this, 'ajax_load_ecwid_media' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Save product media data (Interface implementation).
	 *
	 * Saves product media with validation and error handling.
	 *
	 * @since  0.2.1
	 * @param  int   $post_id    Post ID.
	 * @param  array $media_data Media data from form.
	 * @return void
	 * @throws InvalidArgumentException If parameters are invalid.
	 */
	public function save_product_media( $post_id, $media_data ) {
		if ( ! is_numeric( $post_id ) || $post_id <= 0 ) {
			throw new InvalidArgumentException( 'Invalid post ID provided' );
		}

		if ( ! is_array( $media_data ) ) {
			$this->log_error(
				'Invalid media data provided',
				array(
					'post_id'   => $post_id,
					'data_type' => gettype( $media_data ),
				)
			);
			return;
		}

		$post_id = absint( $post_id );

		try {
			$product_media   = array();
			$processed_count = 0;
			$errors          = array();

			foreach ( $media_data as $tag_key => $media_item ) {
				try {
					$processed_item = $this->process_media_item( $tag_key, $media_item, $post_id );
					if ( $processed_item ) {
						$product_media[] = $processed_item;
						$processed_count++;
					}
				} catch ( Exception $e ) {
					$errors[] = array(
						'tag_key' => $tag_key,
						'error'   => $e->getMessage(),
					);
					$this->log_error(
						'Error processing media item',
						array(
							'post_id' => $post_id,
							'tag_key' => $tag_key,
							'error'   => $e->getMessage(),
						)
					);
				}
			}

			$this->log_info( 'Calling update post meta _product_media', $product_media );
			// Save the processed media data.
			$result = update_post_meta( $post_id, '_product_media', $product_media );

			if ( false === $result ) {
				$this->log_error(
					'Failed to save product media meta',
					array(
						'post_id' => $post_id,
					)
				);
			} else {
				$this->log_info(
					'Successfully saved product media',
					array(
						'post_id'         => $post_id,
						'processed_count' => $processed_count,
						'error_count'     => count( $errors ),
					)
				);

				// Clear cache for this post.
				unset( $this->cache[ 'media_' . $post_id ] );
			}

			if ( ! empty( $errors ) ) {
				$this->log_error(
					'Errors occurred during media processing',
					array(
						'post_id' => $post_id,
						'errors'  => $errors,
					)
				);
			}

		} catch ( Exception $e ) {
			$this->log_error(
				'Exception saving product media',
				array(
					'post_id' => $post_id,
					'error'   => $e->getMessage(),
					'trace'   => $e->getTraceAsString(),
				)
			);
		}
	}

	/**
	 * Get product media by tag with enhanced data (Interface implementation).
	 *
	 * Retrieves media data for a specific tag with caching and validation.
	 *
	 * @since  0.2.1
	 * @param  int    $post_id Post ID.
	 * @param  string $tag_key Media tag key.
	 *
	 * @return array|null Media data or null if not found.
	 *
	 * @throws InvalidArgumentException If parameters are invalid.
	 */
	public function get_product_media_by_tag($post_id, $tag_key) {
		if (!is_numeric($post_id) || $post_id <= 0) {
			throw new InvalidArgumentException('Invalid post ID provided');
		}

		if (empty($tag_key) || !is_string($tag_key)) {
			throw new InvalidArgumentException('Invalid tag key provided');
		}

		$post_id = absint($post_id);
		$tag_key = sanitize_key($tag_key);

		try {
			// Check cache first
			$cache_key = 'media_' . $post_id;
			if (!isset($this->cache[$cache_key])) {
				$product_media = get_post_meta($post_id, '_product_media', true);
				$this->cache[$cache_key] = is_array($product_media) ? $product_media : array();
			}

			$product_media = $this->cache[$cache_key];

			foreach ($product_media as $media_item) {
				if (isset($media_item['tag_name']) && $media_item['tag_name'] === $tag_key) {
					// Validate the media item before returning
					$validated_item = $this->validate_media_item($media_item);

					$this->log_info('Retrieved product media by tag', array(
						'post_id'    => $post_id,
						'tag_key'    => $tag_key,
						'media_type' => $validated_item['media_type'] ?? 'unknown',
					));

					return $validated_item;
				}
			}

			$this->log_info('Product media not found for tag', array(
				'post_id' => $post_id,
				'tag_key' => $tag_key,
			));

			return null;

		} catch (Exception $e) {
			$this->log_error('Exception retrieving product media by tag', array(
				'post_id' => $post_id,
				'tag_key' => $tag_key,
				'error'   => $e->getMessage(),
				'trace'   => $e->getTraceAsString(),
			));
			return null;
		}
	}

	/**
	 * Get complete product media data by tag.
	 *
	 * Returns full media information including URLs, metadata, and fallbacks.
	 * This method unifies the logic used by both REST API and template functions.
	 *
	 * @since 0.4.3
	 *
	 * @param int    $product_id  Ecwid product ID.
	 * @param string $tag_key     Media tag key.
	 * @param string $size        Image size (thumbnail, medium, large, full).
	 * @param bool   $fallback    Whether to use fallback images.
	 *
	 * @return array|null Complete media data or null if not found.
	 */
	public function get_product_media_data($product_id, $tag_key, $size = 'large', $fallback = true) {
		if (empty($product_id) || empty($tag_key)) {
			return null;
		}

		try {
			// Get product object first (same as REST API approach)
			$product = $this->ecwid_api->get_product_by_id($product_id);
			if (!$product) {
				$this->log_error('Product not found in Ecwid', array(
					'product_id' => $product_id,
				));
				return null;
			}

			// Get product post ID
			$post_id = $this->ecwid_api->get_product_post_id($product_id);
			$raw_media_data = null;
			$is_fallback = false;

			if ($post_id) {
				// Try to get raw media data by tag
				$raw_media_data = $this->get_product_media_by_tag($post_id, $tag_key);
			}

			if (!$raw_media_data) {
				$this->log_info('No custom media found, checking fallback', array(
					'product_id' => $product_id,
					'tag_key' => $tag_key,
					'fallback_enabled' => $fallback
				));

				// No custom media found, try fallback to Ecwid images if enabled
				if ($fallback) {
					// Pass null to use enhanced fallback with responsive srcset
					return $this->create_fallback_media_data($product, null, $tag_key);
				}
				return null;
			}

			// Process the raw media data
			$processed_data = $this->process_media_data_complete($raw_media_data, $product, $size);

			if ($processed_data) {
				return $processed_data;
			}

			// If processing failed, try fallback
			if ($fallback) {
				// Pass null to use enhanced fallback with responsive srcset
				return $this->create_fallback_media_data($product, null, $tag_key);
			}

			return null;

		} catch (Exception $e) {
			$this->log_error('Error getting product media data', array(
				'product_id' => $product_id,
				'tag_key' => $tag_key,
				'size' => $size,
				'error' => $e->getMessage()
			));

			// Try fallback on error
			if ($fallback && isset($product)) {
				// Pass null to use enhanced fallback with responsive srcset
				return $this->create_fallback_media_data($product, null, $tag_key);
			}

			return null;
		}
	}

	/**
	 * Process media data to get complete information.
	 *
	 * @since 0.4.3
	 *
	 * @param array  $media_data Raw media data from get_product_media_by_tag.
	 * @param object $product    Ecwid product object.
	 * @param string $size       Image size for WordPress attachments.
	 *
	 * @return array|null Complete media data or null if processing failed.
	 */
	private function process_media_data_complete($media_data, $product, $size = 'large') {
		if (!is_array($media_data) || !isset($media_data['media_type'])) {
			return null;
		}

		$media_type = $media_data['media_type'];

		switch ($media_type) {
			case 'upload':
				if (!empty($media_data['attachment_id'])) {
					return $this->format_wordpress_media_data($media_data['attachment_id'], $size);
				}
				break;

			case 'url':
				if (!empty($media_data['media_url'])) {
					return $this->format_url_media_data($media_data['media_url']);
				}
				break;

			case 'ecwid':
				if (isset($media_data['ecwid_position'])) {
					return $this->format_ecwid_media_data($media_data['ecwid_position'], $product, $size);
				}
				break;
		}

		return null;
	}

	/**
	 * Format Ecwid media data with size selection support.
	 *
	 * @since 0.4.3
	 *
	 * @param int    $ecwid_position Ecwid image position.
	 * @param object $product        Ecwid product object.
	 * @param string $size           Preferred size (thumbnail, medium, large, full).
	 *
	 * @return array|null Formatted data or null if invalid.
	 */
	private function format_ecwid_media_data($ecwid_position, $product, $size = 'large') {
		// Use the improved utility method to get complete image data
		$image_data = Peaches_Ecwid_Image_Utilities::generate_ecwid_image_data(
			$product,
			$ecwid_position,
			'gallery'
		);

		if (!$image_data) {
			return null;
		}

		// Safely extract product name as string
		$product_name = isset($product->name) && is_scalar($product->name) ? (string)$product->name : 'Product';

		$media_type = $this->determine_media_type_from_url($image_data['url']);

		// Build the sizes array using actual Ecwid image sizes
		$sizes = array();
		if (!empty($image_data['available_sizes'])) {
			foreach ($image_data['available_sizes'] as $width => $url) {
				// Map width to approximate size names
				$size_name = $this->get_size_name_from_width($width);
				$sizes[$size_name] = array(
					'url'    => $url,
					'width'  => $width,
					'height' => 0, // Ecwid doesn't always provide height
					'type'   => $media_type
				);
			}
		}

		// Add full size as the largest available
		if (!empty($image_data['available_sizes'])) {
			$max_width = max(array_keys($image_data['available_sizes']));
			$sizes['full'] = array(
				'url'    => $image_data['available_sizes'][$max_width],
				'width'  => $max_width,
				'height' => isset($image_data['height']) && is_numeric($image_data['height']) ? (int)$image_data['height'] : 0,
				'type'   => $media_type
			);
		}

		// Safely extract alt text
		$alt_text = '';
		if (isset($image_data['alt']) && is_scalar($image_data['alt'])) {
			$alt_text = (string)$image_data['alt'];
		} else {
			$alt_text = $product_name;
		}

		// Select the appropriate URL based on requested size
		$selected_url = $this->get_ecwid_url_for_size($image_data, $sizes, $size);

		return array(
			'url'            => $selected_url, // Use size-specific URL
			'title'          => $product_name . ' - Image ' . ($ecwid_position + 1),
			'alt'            => $alt_text,
			'caption'        => '',
			'description'    => '',
			'mime_type'      => $this->guess_mime_type_from_url($selected_url),
			'type'           => $media_type,
			'source'         => 'ecwid',
			'ecwid_position' => $ecwid_position,
			'sizes'          => $sizes,
			'srcset'         => isset($image_data['srcset']) && is_scalar($image_data['srcset']) ? (string)$image_data['srcset'] : '',
			'responsive'     => true,
		);
	}

	/**
	 * Get Ecwid URL for specific size.
	 *
	 * @since 0.4.3
	 *
	 * @param array  $image_data Ecwid image data.
	 * @param array  $sizes      Available sizes array.
	 * @param string $size       Requested size.
	 *
	 * @return string Selected URL.
	 */
	private function get_ecwid_url_for_size($image_data, $sizes, $size) {
		// Direct size mapping
		if (isset($sizes[$size]['url'])) {
			return $sizes[$size]['url'];
		}

		// Size fallback logic
		switch ($size) {
			case 'full':
			case 'original':
				// Get the largest available image
				if (!empty($image_data['available_sizes'])) {
					$max_width = max(array_keys($image_data['available_sizes']));
					return $image_data['available_sizes'][$max_width];
				}
				break;

			case 'large':
				// Prefer 800px or largest available
				if (isset($sizes['large']['url'])) {
					return $sizes['large']['url'];
				}
				if (!empty($image_data['available_sizes'])) {
					$max_width = max(array_keys($image_data['available_sizes']));
					return $image_data['available_sizes'][$max_width];
				}
				break;

			case 'medium':
				// Prefer 400px or fallback
				if (isset($sizes['medium']['url'])) {
					return $sizes['medium']['url'];
				}
				if (isset($sizes['large']['url'])) {
					return $sizes['large']['url'];
				}
				break;

			case 'thumbnail':
				// Prefer 160px or smallest available
				if (isset($sizes['thumbnail']['url'])) {
					return $sizes['thumbnail']['url'];
				}
				if (!empty($image_data['available_sizes'])) {
					$min_width = min(array_keys($image_data['available_sizes']));
					return $image_data['available_sizes'][$min_width];
				}
				break;
		}

		// Final fallback to src from image_data
		return $image_data['src'];
	}

	/**
	 * Format WordPress media attachment data.
	 *
	 * @since 0.4.3
	 *
	 * @param int    $attachment_id WordPress attachment ID.
	 * @param string $size          Image size.
	 *
	 * @return array|null Formatted data or null if invalid.
	 */
	private function format_wordpress_media_data($attachment_id, $size = 'large') {
		$attachment = get_post($attachment_id);
		if (!$attachment) {
			return null;
		}

		$mime_type = get_post_mime_type($attachment_id);
		$media_type = $this->determine_media_type_from_mime($mime_type);

		// FIXED: Use appropriate URL function based on media type
		if ($media_type === 'image') {
			// For images, use wp_get_attachment_image_url() which supports size parameters
			$url = wp_get_attachment_image_url($attachment_id, $size);
		} else {
			// For videos, audio, and documents, use wp_get_attachment_url() which always works
			$url = wp_get_attachment_url($attachment_id);
		}

		if (!$url) {
			return null;
		}

		return array(
			'url'           => $url,
			'title'         => $attachment->post_title,
			'alt'           => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
			'caption'       => $attachment->post_excerpt,
			'description'   => $attachment->post_content,
			'mime_type'     => $mime_type,
			'type'          => $media_type,
			'source'        => 'wordpress',
			'attachment_id' => $attachment_id,
			'sizes'         => $this->get_attachment_sizes($attachment_id),
		);
	}

	/**
	 * Format external URL media data.
	 *
	 * @since 0.4.3
	 *
	 * @param string $media_url External media URL.
	 *
	 * @return array Formatted data.
	 */
	private function format_url_media_data($media_url) {
		$media_type = $this->determine_media_type_from_url($media_url);

		return array(
			'url'         => $media_url,
			'title'       => basename(parse_url($media_url, PHP_URL_PATH)),
			'alt'         => '',
			'caption'     => '',
			'description' => '',
			'mime_type'   => $this->guess_mime_type_from_url($media_url),
			'type'        => $media_type,
			'source'      => 'external',
			'sizes'       => array(
				'full' => array(
					'url'    => $media_url,
					'width'  => 0,
					'height' => 0,
					'type'   => $media_type
				)
			),
		);
	}

	/**
	 * Map image width to WordPress size name
	 *
	 * @since 0.4.3
	 *
	 * @param int $width Image width in pixels.
	 *
	 * @return string Size name.
	 */
	private function get_size_name_from_width($width) {
		if ($width <= 160) {
			return 'thumbnail';
		} elseif ($width <= 400) {
			return 'medium';
		} elseif ($width <= 800) {
			return 'large';
		} elseif ($width <= 1500) {
			return 'extra-large';
		} else {
			return 'original';
		}
	}

	/**
	 * Create fallback media data for Ecwid images.
	 *
	 * @since 0.4.3
	 *
	 * @param object $product     Ecwid product object.
	 * @param string $fallback_url Fallback image URL.
	 * @param string $tag_key     Original tag key requested.
	 *
	 * @return array Fallback media data.
	 */
	private function create_fallback_media_data($product, $fallback_url = null, $tag_key = '') {
		// Safely extract product name as string
		$product_name = isset($product->name) && is_scalar($product->name) ? (string)$product->name : 'Product';

		// If no specific URL provided, get the best available image (position 0)
		if (!$fallback_url) {
			$image_data = Peaches_Ecwid_Image_Utilities::generate_ecwid_image_data(
				$product,
				0, // Main image
				'gallery'
			);

			if ($image_data) {
				$fallback_url = $image_data['src'];

				// Return enhanced fallback with responsive data
				$media_type = $this->determine_media_type_from_url($fallback_url);

				$sizes = array();
				if (!empty($image_data['available_sizes'])) {
					foreach ($image_data['available_sizes'] as $width => $url) {
						$size_name = $this->get_size_name_from_width($width);
						$sizes[$size_name] = array(
							'url'    => $url,
							'width'  => $width,
							'height' => 0,
							'type'   => $media_type
						);
					}
				}

				// Safely extract alt text
				$alt_text = '';
				if (isset($image_data['alt']) && is_scalar($image_data['alt'])) {
					$alt_text = (string)$image_data['alt'];
				} else {
					$alt_text = $product_name;
				}

				return array(
					'url'         => $fallback_url,
					'title'       => $product_name . (!empty($tag_key) ? ' - ' . $tag_key : '') . ' (fallback)',
					'alt'         => $alt_text,
					'caption'     => '',
					'description' => '',
					'mime_type'   => $this->guess_mime_type_from_url($fallback_url),
					'type'        => $media_type,
					'source'      => 'ecwid_fallback',
					'is_fallback' => true,
					'sizes'       => $sizes,
					'srcset'      => isset($image_data['srcset']) && is_scalar($image_data['srcset']) ? (string)$image_data['srcset'] : '',
					'responsive'  => true,
				);
			}
		}

		// Basic fallback if no enhanced data available
		$media_type = $this->determine_media_type_from_url($fallback_url);

		return array(
			'url'         => $fallback_url,
			'title'       => $product_name . (!empty($tag_key) ? ' - ' . $tag_key : '') . ' (fallback)',
			'alt'         => $product_name,
			'caption'     => '',
			'description' => '',
			'mime_type'   => $this->guess_mime_type_from_url($fallback_url),
			'type'        => $media_type,
			'source'      => 'ecwid_fallback',
			'is_fallback' => true,
			'sizes'       => array(
				'full' => array(
					'url'    => $fallback_url,
					'width'  => 0,
					'height' => 0,
					'type'   => $media_type
				)
			),
			'responsive'  => false,
		);
	}

	/**
	 * Updated get_product_media_url method - now uses the complete method.
	 *
	 * @since 0.2.7
	 *
	 * @param int    $product_id  Ecwid product ID.
	 * @param string $tag_key     Media tag key.
	 * @param string $size        Image size (thumbnail, medium, large, full).
	 * @param bool   $fallback    Whether to use fallback images.
	 *
	 * @return string|null Media URL or null if not found.
	 */
	public function get_product_media_url($product_id, $tag_key, $size = 'large', $fallback = true) {
		$media_data = $this->get_product_media_data($product_id, $tag_key, $size, $fallback);

		return $media_data ? $media_data['url'] : null;
	}

	/**
	 * Determine media type from URL and optional mime type.
	 *
	 * @since 0.4.3
	 *
	 * @param string $url      Media URL.
	 * @param string $mimeType Optional mime type.
	 *
	 * @return string Media type.
	 */
	private function determine_media_type_from_url($url, $mimeType = '') {
		// Check mime type first if provided
		if ($mimeType) {
			if (strpos($mimeType, 'video/') === 0) {
				return 'video';
			}
			if (strpos($mimeType, 'image/') === 0) {
				return 'image';
			}
			if (strpos($mimeType, 'audio/') === 0) {
				return 'audio';
			}
			if (strpos($mimeType, 'application/pdf') === 0 || strpos($mimeType, 'text/') === 0) {
				return 'document';
			}
		}

		if (!$url) {
			return 'image';
		}

		// Parse URL to get pathname without query parameters
		$parsed_url = parse_url($url);
		$pathname = isset($parsed_url['path']) ? $parsed_url['path'] : $url;

		// Extract file extension from pathname
		$extension = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));

		// Video extensions
		$video_extensions = array('mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'm4v', '3gp', 'mkv');
		if (in_array($extension, $video_extensions)) {
			return 'video';
		}

		// Audio extensions
		$audio_extensions = array('mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma');
		if (in_array($extension, $audio_extensions)) {
			return 'audio';
		}

		// Document extensions
		$document_extensions = array('pdf', 'doc', 'docx', 'txt', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx');
		if (in_array($extension, $document_extensions)) {
			return 'document';
		}

		// Check for common video hosting patterns
		if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false ||
			strpos($url, 'vimeo.com') !== false || strpos($url, 'wistia.com') !== false ||
			strpos($url, '/videos/') !== false || strpos($url, '/video/') !== false) {
			return 'video';
		}

		// Default to image
		return 'image';
	}

	/**
	 * Determine media type from mime type only.
	 *
	 * @since 0.4.3
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return string Media type.
	 */
	private function determine_media_type_from_mime($mime_type) {
		if (empty($mime_type)) {
			return 'image';
		}

		if (strpos($mime_type, 'video/') === 0) {
			return 'video';
		}
		if (strpos($mime_type, 'image/') === 0) {
			return 'image';
		}
		if (strpos($mime_type, 'audio/') === 0) {
			return 'audio';
		}
		if (strpos($mime_type, 'application/pdf') === 0 || strpos($mime_type, 'text/') === 0) {
			return 'document';
		}

		return 'image';
	}

	/**
	 * Guess mime type from URL.
	 *
	 * @since 0.4.3
	 *
	 * @param string $url Media URL.
	 *
	 * @return string Guessed mime type.
	 */
	private function guess_mime_type_from_url($url) {
		$extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

		$mime_types = array(
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'webp' => 'image/webp',
			'svg'  => 'image/svg+xml',
			'bmp'  => 'image/bmp',
			'mp4'  => 'video/mp4',
			'webm' => 'video/webm',
			'ogg'  => 'video/ogg',
			'avi'  => 'video/x-msvideo',
			'mov'  => 'video/quicktime',
			'mp3'  => 'audio/mpeg',
			'wav'  => 'audio/wav',
			'aac'  => 'audio/aac',
			'flac' => 'audio/flac',
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);

		return isset($mime_types[$extension]) ? $mime_types[$extension] : 'image/jpeg';
	}

	/**
	 * Get attachment sizes for WordPress media.
	 *
	 * @since 0.4.3
	 *
	 * @param int $attachment_id WordPress attachment ID.
	 *
	 * @return array Array of available sizes.
	 */
	private function get_attachment_sizes($attachment_id) {
		$sizes = array();
		$metadata = wp_get_attachment_metadata($attachment_id);

		if (!$metadata) {
			return $sizes;
		}

		// For videos, we might not have traditional image sizes
		$mime_type = get_post_mime_type($attachment_id);
		if (strpos($mime_type, 'video/') === 0) {
			// For videos, return basic info
			$sizes['full'] = array(
				'url'    => wp_get_attachment_url($attachment_id),
				'width'  => isset($metadata['width']) ? $metadata['width'] : 0,
				'height' => isset($metadata['height']) ? $metadata['height'] : 0,
				'type'   => 'video'
			);

			return $sizes;
		}

		// For images, process normal image sizes
		if (!isset($metadata['sizes'])) {
			return $sizes;
		}

		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		$base_path = dirname($metadata['file']);

		foreach ($metadata['sizes'] as $size_name => $size_data) {
			$sizes[$size_name] = array(
				'url'    => $base_url . '/' . $base_path . '/' . $size_data['file'],
				'width'  => $size_data['width'],
				'height' => $size_data['height'],
				'type'   => 'image'
			);
		}

		// Add full size
		$sizes['full'] = array(
			'url'    => wp_get_attachment_url($attachment_id),
			'width'  => isset($metadata['width']) ? $metadata['width'] : 0,
			'height' => isset($metadata['height']) ? $metadata['height'] : 0,
			'type'   => 'image'
		);

		return $sizes;
	}

	/**
	 * Render media tag item with multiple input modes (Interface implementation).
	 *
	 * Renders the complete media management interface for a specific tag.
	 *
	 * @since 0.2.1
	 *
	 * @param string $tag_key       Tag key.
	 * @param array  $tag_data      Tag data.
	 * @param mixed  $current_media Current media data.
	 * @param int    $post_id       Current post ID for Ecwid fallback.
	 *
	 * @return void
	 */
	public function render_media_tag_item($tag_key, $tag_data, $current_media = null, $post_id = 0) {
		if (empty($tag_key) || !is_array($tag_data)) {
			$this->log_error('Invalid parameters for render_media_tag_item', array(
				'tag_key'   => $tag_key,
				'tag_data'  => $tag_data,
			));
			return;
		}

		try {
			// Determine current media type and values
			$media_state = $this->parse_current_media($current_media, $tag_key, $post_id);

			$this->render_media_tag_interface($tag_key, $tag_data, $media_state, $post_id);

		} catch (Exception $e) {
			$this->log_error('Exception rendering media tag item', array(
				'tag_key' => $tag_key,
				'post_id' => $post_id,
				'error'   => $e->getMessage(),
			));

			// Render error fallback
			$this->render_error_fallback($tag_key, $tag_data);
		}
	}

	/**
	 * Process individual media item during save operation.
	 *
	 * @since 0.2.1
	 *
	 * @param string $tag_key    Tag key.
	 * @param array  $media_item Media item data.
	 * @param int    $post_id    Post ID.
	 *
	 * @return array|null Processed media item or null if invalid.
	 *
	 * @throws Exception If processing fails.
	 */
	private function process_media_item($tag_key, $media_item, $post_id) {
		$this->log_info('Process media item', $media_item);

		if (empty($media_item['tag_name']) || empty($media_item['media_type'])) {
			return null;
		}

		$media_type = sanitize_text_field($media_item['media_type']);
		$tag_name = sanitize_text_field($media_item['tag_name']);

		if (!in_array($media_type, $this->supported_media_types, true)) {
			throw new Exception('Unsupported media type: ' . $media_type);
		}

		$media_entry = array(
			'tag_name'   => $tag_name,
			'media_type' => $media_type,
		);

		switch ($media_type) {
			case 'upload':
				$processed = $this->process_upload_media($media_item, $tag_name);
				break;

			case 'url':
				$processed = $this->process_url_media($media_item);
				break;

			case 'ecwid':
				$processed = $this->process_ecwid_media($media_item, $post_id);
				break;

			default:
				throw new Exception('Invalid media type: ' . $media_type);
		}

		if ($processed) {
			$media_entry = array_merge($media_entry, $processed);
		}

		// Only return if we have valid media data
		return count($media_entry) > 2 ? $media_entry : null;
	}

	/**
	 * Process upload media type.
	 *
	 * @since 0.2.1
	 *
	 * @param array  $media_item Media item data.
	 * @param string $tag_name   Tag name.
	 *
	 * @return array|null Processed data or null.
	 */
	private function process_upload_media($media_item, $tag_name) {
		if (empty($media_item['attachment_id'])) {
			return null;
		}

		$attachment_id = absint($media_item['attachment_id']);

		// Verify attachment exists
		if (!get_post($attachment_id)) {
			$this->log_error('Invalid attachment ID', array(
				'attachment_id' => $attachment_id,
				'tag_name'      => $tag_name,
			));
			return null;
		}

		// Mark attachment as product media
		update_post_meta($attachment_id, '_peaches_product_media', true);
		update_post_meta($attachment_id, '_peaches_product_media_tag', $tag_name);

		return array('attachment_id' => $attachment_id);
	}

	/**
	 * Process URL media type.
	 *
	 * @since 0.2.1
	 *
	 * @param array $media_item Media item data.
	 *
	 * @return array|null Processed data or null.
	 */
	private function process_url_media($media_item) {
		if (empty($media_item['media_url'])) {
			return null;
		}

		$media_url = esc_url_raw($media_item['media_url']);

		if (!$media_url || !filter_var($media_url, FILTER_VALIDATE_URL)) {
			$this->log_error('Invalid media URL provided', array(
				'url' => $media_item['media_url'],
			));
			return null;
		}

		return array('media_url' => $media_url);
	}

	/**
	 * Process Ecwid media type.
	 *
	 * @since 0.2.1
	 *
	 * @param array $media_item Media item data.
	 * @param int   $post_id    Post ID.
	 *
	 * @return array|null Processed data or null.
	 */
	private function process_ecwid_media($media_item, $post_id) {
		if (!isset($media_item['ecwid_position']) || $media_item['ecwid_position'] === '') {
			return null;
		}

		$ecwid_position = absint($media_item['ecwid_position']);

		// DEBUG: Log the position being processed
		$this->log_info('Processing Ecwid media for position: ' . $ecwid_position, array(
			'post_id' => $post_id,
			'original_position' => $media_item['ecwid_position']
		));

		// Validate that we can access the Ecwid product
		$ecwid_product_id = get_post_meta($post_id, '_ecwid_product_id', true);
		if (!$ecwid_product_id) {
			$this->log_error('No Ecwid product ID found for post', array(
				'post_id' => $post_id,
			));
			return null;
		}

		try {
			$product = $this->ecwid_api->get_product_by_id($ecwid_product_id);
			if (!$product) {
				$this->log_error('Ecwid product not found', array(
					'ecwid_product_id' => $ecwid_product_id,
					'post_id'          => $post_id,
				));
				return null;
			}

			// DEBUG: Log product image data
			$this->log_info('Ecwid product image data', array(
				'ecwid_product_id' => $ecwid_product_id,
				'thumbnailUrl' => isset($product->thumbnailUrl) ? $product->thumbnailUrl : 'NOT SET',
				'galleryImages_count' => isset($product->galleryImages) ? count($product->galleryImages) : 0,
				'position_requested' => $ecwid_position
			));

			// Validate that the position exists
			$image_url = $this->get_ecwid_image_by_position($product, $ecwid_position);
			if (!$image_url) {
				$this->log_error('Ecwid image position not found', array(
					'ecwid_product_id' => $ecwid_product_id,
					'position'         => $ecwid_position,
					'product_thumbnail' => isset($product->thumbnailUrl) ? 'EXISTS' : 'MISSING',
					'gallery_count'     => isset($product->galleryImages) ? count($product->galleryImages) : 0,
				));
				return null;
			}

			// DEBUG: Log successful validation
			$this->log_info('Ecwid media validation successful', array(
				'position' => $ecwid_position,
				'image_url' => $image_url
			));

		} catch (Exception $e) {
			$this->log_error('Error validating Ecwid media', array(
				'ecwid_product_id' => $ecwid_product_id,
				'position'         => $ecwid_position,
				'error'            => $e->getMessage(),
			));
			return null;
		}

		return array('ecwid_position' => (string) $ecwid_position);
	}

	/**
	 * Parse current media data into standardized format.
	 *
	 * @since 0.2.1
	 *
	 * @param mixed  $current_media Current media data.
	 * @param string $tag_key       Tag key.
	 * @param int    $post_id       Post ID.
	 *
	 * @return array Parsed media state.
	 */
	private function parse_current_media($current_media, $tag_key, $post_id) {
		$state = array(
			'media_type'      => 'none',
			'attachment_id'   => '',
			'media_url'       => '',
			'ecwid_position'  => '',
			'has_media'       => false,
			'fallback_used'   => false,
			'fallback_url'    => '',
		);

		$this->log_info('Parse current media', $current_media);

		try {
			if (is_array($current_media)) {
				if (!empty($current_media['attachment_id'])) {
					$state['media_type'] = 'upload';
					$state['attachment_id'] = $current_media['attachment_id'];
					$state['has_media'] = true;
				} elseif (!empty($current_media['media_url'])) {
					$state['media_type'] = 'url';
					$state['media_url'] = $current_media['media_url'];
					$state['has_media'] = true;
				} elseif (isset($current_media['ecwid_position']) && $current_media['ecwid_position'] !== '') {
					$state['media_type'] = 'ecwid';
					$state['ecwid_position'] = $current_media['ecwid_position']; // Keep as string
					$state['has_media'] = true;
				}
			} elseif (!empty($current_media)) {
				// Legacy format - attachment ID only
				$state['media_type'] = 'upload';
				$state['attachment_id'] = $current_media;
				$state['has_media'] = true;
			}

			if (!$state['has_media'] && $tag_key === 'hero_image' && $post_id) {
				// Get the Ecwid product ID from the post meta
				$ecwid_product_id = get_post_meta($post_id, '_ecwid_product_id', true);
				if ($ecwid_product_id) {
					$fallback_url = $this->get_ecwid_fallback_image($ecwid_product_id);
					if ($fallback_url) {
						$state['fallback_used'] = true;
						$state['fallback_url'] = $fallback_url;
					}
				}
			}
		} catch (Exception $e) {
			$this->log_error('Error parsing current media', array(
				'tag_key'       => $tag_key,
				'post_id'       => $post_id,
				'current_media' => $current_media,
				'error'         => $e->getMessage(),
			));
		}

		return $state;
	}

	/**
	 * Render the complete media tag interface.
	 *
	 * @since 0.2.1
	 *
	 * @param string $tag_key     Tag key.
	 * @param array  $tag_data    Tag data.
	 * @param array  $media_state Media state.
	 * @param int    $post_id     Post ID.
	 *
	 * @return void
	 */
	private function render_media_tag_interface($tag_key, $tag_data, $media_state, $post_id) {
		?>
		<div class="media-tag-item border rounded p-3 h-100" data-tag-key="<?php echo esc_attr($tag_key); ?>">
			<div class="text-center mb-3 d-flex justify-content-center">
				<div class="media-preview bg-light rounded position-relative" style="flex: 0 0 150px; text-align: center;">
					<?php $this->render_media_preview_section($media_state, $post_id); ?>
				</div>
			</div>

			<div class="text-center">
				<h6 class="mb-1"><?php echo esc_html($tag_data['label']); ?></h6>
				<small class="text-muted d-block mb-2">
					<code><?php echo esc_html($tag_key); ?></code>
				</small>
				<?php if (!empty($tag_data['description'])): ?>
					<small class="text-muted d-block mb-3"><?php echo esc_html($tag_data['description']); ?></small>
				<?php endif; ?>

				<?php $this->render_media_type_selection($tag_key, $media_state); ?>
				<?php $this->render_hidden_inputs($tag_key, $media_state); ?>
				<?php $this->render_media_controls($tag_key, $media_state); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render media preview section.
	 *
	 * @since 0.2.1
	 *
	 * @param array $media_state Media state.
	 * @param int   $post_id     Post ID.
	 *
	 * @return void
	 */
	private function render_media_preview_section($media_state, $post_id) {
		if ($media_state['has_media']) {
			$this->render_media_preview(
				$media_state['media_type'],
				$media_state['attachment_id'],
				$media_state['media_url'],
				$media_state['ecwid_position'],
				$post_id
			);
		} elseif ($media_state['fallback_used']) {
			?>
			<img src="<?php echo esc_url($media_state['fallback_url']); ?>" class="img-thumbnail" alt="<?php _e('Fallback from Ecwid', 'peaches'); ?>">
			<div class="position-absolute bottom-0 end-0 p-1">
				<small class="badge bg-info"><?php _e('Fallback Ecwid', 'peaches'); ?></small>
			</div>
			<?php
		} else {
			?>
			<i class="fas fa-image fa-2x text-muted"></i>
			<?php
		}
	}

	/**
	 * Render media type selection buttons.
	 *
	 * @since 0.2.1
	 *
	 * @param string $tag_key     Tag key.
	 * @param array  $media_state Media state.
	 *
	 * @return void
	 */
	private function render_media_type_selection($tag_key, $media_state) {
		?>
		<!-- Media Type Selection -->
		<div class="media-type-selection mb-3">
			<div class="btn-group-vertical w-100" role="group" aria-label="<?php esc_attr_e('Media type selection', 'peaches'); ?>">
				<input type="radio" class="btn-check media-type-radio"
					   name="media_type_<?php echo esc_attr($tag_key); ?>"
					   id="upload_<?php echo esc_attr($tag_key); ?>"
					   value="upload"
					   <?php checked($media_state['media_type'], 'upload'); ?>>
				<label class="btn btn-outline-secondary btn-sm" for="upload_<?php echo esc_attr($tag_key); ?>">
					<i class="dashicons dashicons-upload"></i>
					<?php _e('Upload File', 'peaches'); ?>
				</label>

				<input type="radio" class="btn-check media-type-radio"
					   name="media_type_<?php echo esc_attr($tag_key); ?>"
					   id="url_<?php echo esc_attr($tag_key); ?>"
					   value="url"
					   <?php checked($media_state['media_type'], 'url'); ?>>
				<label class="btn btn-outline-secondary btn-sm" for="url_<?php echo esc_attr($tag_key); ?>">
					<i class="dashicons dashicons-admin-links"></i>
					<?php _e('External URL', 'peaches'); ?>
				</label>

				<input type="radio" class="btn-check media-type-radio"
					   name="media_type_<?php echo esc_attr($tag_key); ?>"
					   id="ecwid_<?php echo esc_attr($tag_key); ?>"
					   value="ecwid"
					   <?php checked($media_state['media_type'], 'ecwid'); ?>>
				<label class="btn btn-outline-secondary btn-sm" for="ecwid_<?php echo esc_attr($tag_key); ?>">
					<i class="dashicons dashicons-store"></i>
					<?php _e('Ecwid Media', 'peaches'); ?>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * Render hidden form inputs.
	 *
	 * @since 0.2.1
	 *
	 * @param string $tag_key     Tag key.
	 * @param array  $media_state Media state.
	 *
	 * @return void
	 */
	private function render_hidden_inputs($tag_key, $media_state) {
		?>
		<!-- Hidden inputs for form data -->
		<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][tag_name]" value="<?php echo esc_attr($tag_key); ?>">
		<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][media_type]" value="<?php echo esc_attr($media_state['media_type']); ?>" class="media-type-value">
		<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][attachment_id]" value="<?php echo esc_attr($media_state['attachment_id']); ?>" class="media-attachment-id">
		<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][media_url]" value="<?php echo esc_attr($media_state['media_url']); ?>" class="media-url-value">
		<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][ecwid_position]" value="<?php echo esc_attr($media_state['ecwid_position']); ?>" class="media-ecwid-position">
		<?php
	}

	/**
	 * Render media control sections.
	 *
	 * @since 0.2.1
	 *
	 * @param string $tag_key     Tag key.
	 * @param array  $media_state Media state.
	 *
	 * @return void
	 */
	private function render_media_controls($tag_key, $media_state) {
		$this->render_upload_controls($media_state);
		$this->render_url_controls($media_state);
		$this->render_ecwid_controls($media_state);
	}

	/**
	 * Render upload file controls.
	 *
	 * @since 0.2.1
	 *
	 * @param array $media_state Media state.
	 *
	 * @return void
	 */
	private function render_upload_controls($media_state) {
		$has_upload_media = $media_state['has_media'] && $media_state['media_type'] === 'upload';
		?>
		<!-- Upload File Controls -->
		<div class="media-upload-controls" style="display: <?php echo $media_state['media_type'] === 'upload' ? 'block' : 'none'; ?>;">
			<div class="btn-group-vertical w-100">
				<button type="button" class="btn btn-<?php echo $has_upload_media ? 'outline-secondary' : 'secondary'; ?> btn-sm select-media-button">
					<span class="dashicons dashicons-<?php echo $has_upload_media ? 'update' : 'plus-alt2'; ?>"></span>
				</button>
				<?php if ($has_upload_media): ?>
					<button type="button" class="btn btn-outline-danger btn-sm remove-media-button">
						<span class="dashicons dashicons-trash"></span>
						<?php _e('Remove', 'peaches'); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render URL input controls.
	 *
	 * @since 0.2.1
	 *
	 * @param array $media_state Media state.
	 *
	 * @return void
	 */
	private function render_url_controls($media_state) {
		$has_url_media = $media_state['has_media'] && $media_state['media_type'] === 'url';
		?>
		<!-- URL Input Controls -->
		<div class="media-url-controls" style="display: <?php echo $media_state['media_type'] === 'url' ? 'block' : 'none'; ?>;">
			<div class="input-group input-group-sm mb-2">
				<input type="url" class="form-control media-url-input"
					   placeholder="<?php esc_attr_e('https://example.com/image.jpg', 'peaches'); ?>"
					   value="<?php echo esc_attr($media_state['media_url']); ?>">
				<button type="button" class="btn btn-outline-secondary preview-url-button" title="<?php esc_attr_e('Preview URL', 'peaches'); ?>">
					<i class="dashicons dashicons-visibility"></i>
				</button>
			</div>
			<?php if ($has_url_media): ?>
				<button type="button" class="btn btn-outline-danger btn-sm w-100 clear-url-button">
					<span class="dashicons dashicons-trash"></span>
					<?php _e('Clear URL', 'peaches'); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Ecwid media controls.
	 *
	 * @since 0.2.1
	 *
	 * @param array $media_state Media state.
	 *
	 * @return void
	 */
	private function render_ecwid_controls($media_state) {
		$has_ecwid_media = $media_state['has_media'] && $media_state['media_type'] === 'ecwid';
		?>
		<!-- Ecwid Media Controls -->
		<div class="media-ecwid-controls" style="display: <?php echo $media_state['media_type'] === 'ecwid' ? 'block' : 'none'; ?>;">
			<div class="mb-2">
				<select class="form-select form-select-sm ecwid-position-select">
					<option value=""><?php _e('Select image position...', 'peaches'); ?></option>
					<option value="0" <?php selected($media_state['ecwid_position'], '0'); ?>><?php _e('Main image (position 1)', 'peaches'); ?></option>
				</select>
			</div>
			<!-- Loading indicator for auto-loading images -->
			<div class="ecwid-images-loading" style="display: none;">
				<div class="d-flex align-items-center">
					<div class="spinner-border spinner-border-sm me-2" role="status">
						<span class="visually-hidden"><?php _e('Loading...', 'peaches'); ?></span>
					</div>
					<span class="text-muted"><?php _e('Loading product images...', 'peaches'); ?></span>
				</div>
			</div>
			<?php if ($has_ecwid_media): ?>
				<button type="button" class="btn btn-outline-danger btn-sm w-100 mt-1 clear-ecwid-button">
					<span class="dashicons dashicons-trash"></span>
					<?php _e('Clear Selection', 'peaches'); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render media preview based on type.
	 *
	 * @since 0.2.1
	 *
	 * @param string $media_type     Media type (upload, url, ecwid).
	 * @param string $attachment_id  WordPress attachment ID.
	 * @param string $media_url      External media URL.
	 * @param string $ecwid_position Ecwid image position.
	 * @param int    $post_id        Post ID for Ecwid data.
	 *
	 * @return void
	 */
	private function render_media_preview($media_type, $attachment_id, $media_url, $ecwid_position, $post_id) {
		try {
			switch ($media_type) {
				case 'upload':
					$this->render_upload_preview($attachment_id);
					break;

				case 'url':
					$this->render_url_preview($media_url);
					break;

				case 'ecwid':
					$this->render_ecwid_preview($ecwid_position, $post_id);
					break;

				default:
					$this->log_error('Unknown media type for preview', array(
						'media_type' => $media_type,
					));
					break;
			}
		} catch (Exception $e) {
			$this->log_error('Error rendering media preview', array(
				'media_type' => $media_type,
				'post_id'    => $post_id,
				'error'      => $e->getMessage(),
			));
		}
	}

	/**
	 * Render upload media preview.
	 *
	 * @since 0.2.1
	 *
	 * @param string $attachment_id Attachment ID.
	 *
	 * @return void
	 */
	private function render_upload_preview($attachment_id) {
		if ($attachment_id && get_post($attachment_id)) {
			echo wp_get_attachment_image($attachment_id, 'thumbnail', false, array(
				'class' => 'img-thumbnail',
			));
			echo '<div class="position-absolute top-0 start-0 p-1">';
			echo '<small class="badge bg-success">' . __('WP Media', 'peaches') . '</small>';
			echo '</div>';
		}
	}

	/**
	 * Render URL media preview.
	 *
	 * @since 0.2.1
	 *
	 * @param string $media_url Media URL.
	 *
	 * @return void
	 */
	private function render_url_preview($media_url) {
		if ($media_url) {
			// Check if it's a video URL
			if ($this->is_video_url($media_url)) {
				echo '<div class="video-preview-placeholder bg-dark text-white d-flex align-items-center justify-content-center rounded" style="width: 100px; height: 100px;">';
				echo '<i class="fas fa-play fa-2x"></i>';
				echo '</div>';
			} else {
				echo '<img src="' . esc_url($media_url) . '" class="img-thumbnail" alt="' . esc_attr__('External media', 'peaches') . '" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
				echo '<div class="error-placeholder bg-warning text-dark d-flex align-items-center justify-content-center rounded" style="width: 100px; height: 100px; display: none;">';
				echo '<i class="fas fa-exclamation-triangle"></i>';
				echo '</div>';
			}
			echo '<div class="position-absolute top-0 start-0 p-1">';
			echo '<small class="badge bg-info">' . __('External', 'peaches') . '</small>';
			echo '</div>';
		}
	}

	/**
	 * Render Ecwid media preview.
	 *
	 * @since 0.2.1
	 *
	 * @param string $ecwid_position Ecwid position.
	 * @param int    $post_id        Post ID.
	 *
	 * @return void
	 */
	private function render_ecwid_preview($ecwid_position, $post_id) {
		if ($ecwid_position !== '' && $post_id) {
			try {
				$ecwid_product_id = get_post_meta($post_id, '_ecwid_product_id', true);
				if ($ecwid_product_id) {
					$product = $this->ecwid_api->get_product_by_id($ecwid_product_id);
					if ($product) {
						$image_url = $this->get_ecwid_image_by_position($product, $ecwid_position);
						if ($image_url) {
							echo '<img src="' . esc_url($image_url) . '" class="img-thumbnail" alt="' . esc_attr__('Ecwid media', 'peaches') . '">';
							echo '<div class="position-absolute top-0 start-0 p-1">';
							echo '<small class="badge bg-warning text-dark">' . __('Ecwid', 'peaches') . '</small>';
							echo '</div>';
						}
					}
				}
			} catch (Exception $e) {
				$this->log_error('Error rendering Ecwid preview', array(
					'ecwid_position' => $ecwid_position,
					'post_id'        => $post_id,
					'error'          => $e->getMessage(),
				));
			}
		}
	}

	/**
	 * Render error fallback interface.
	 *
	 * @since 0.2.1
	 *
	 * @param string $tag_key  Tag key.
	 * @param array  $tag_data Tag data.
	 *
	 * @return void
	 */
	private function render_error_fallback($tag_key, $tag_data) {
		?>
		<div class="media-tag-item border rounded p-3 h-100 bg-light" data-tag-key="<?php echo esc_attr($tag_key); ?>">
			<div class="text-center text-muted">
				<i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
				<h6 class="mb-1"><?php echo esc_html($tag_data['label'] ?? $tag_key); ?></h6>
				<small><?php _e('Error loading media interface', 'peaches'); ?></small>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if URL is a video URL.
	 *
	 * @since 0.2.1
	 *
	 * @param string $url URL to check.
	 *
	 * @return bool True if video URL.
	 */
	private function is_video_url($url) {
		$video_patterns = array(
			'/youtube\.com\/watch\?v=/',
			'/youtu\.be\//',
			'/vimeo\.com\//',
			'/wistia\.com\//',
			'/\.mp4$/i',
			'/\.webm$/i',
			'/\.ogg$/i',
			'/\.mov$/i',
		);

		foreach ($video_patterns as $pattern) {
			if (preg_match($pattern, $url)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get Ecwid image by position.
	 *
	 * @since 0.2.1
	 *
	 * @param object $product    Ecwid product object.
	 * @param int    $position   Image position.
	 * @param bool   $full_data  Whether to return full responsive data.
	 *
	 * @return string|array|null Image URL, full data array, or null.
	 */
	private function get_ecwid_image_by_position($product, $position, $full_data = false) {
		if ($full_data) {
			// Return complete responsive image data
			return Peaches_Ecwid_Image_Utilities::generate_ecwid_image_data(
				$product,
				$position,
				'gallery'
			);
		}

		// Original behavior - just return URL
		$position = intval($position);

		if ($position === 0 && !empty($product->thumbnailUrl)) {
			return $product->thumbnailUrl;
		}

		if (!empty($product->galleryImages) && is_array($product->galleryImages)) {
			$gallery_index = $position - 1;
			if (isset($product->galleryImages[$gallery_index])) {
				return $product->galleryImages[$gallery_index]->url;
			}
		}

		return null;
	}

	/**
	 * Get Ecwid fallback image for hero image tag.
	 *
	 * @since 0.2.1
	 * @since 0.2.7 Refactored to use generic get_ecwid_image function.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string|null Fallback image URL or null.
	 */
	private function get_ecwid_fallback_image($post_id) {
		// For hero image fallback, use position 0 (main/thumbnail image)
		return $this->get_ecwid_image_by_position($post_id, 0);
	}

	/**
	 * Validate media item data.
	 *
	 * @since 0.2.1
	 *
	 * @param array $media_item Media item to validate.
	 *
	 * @return array Validated media item.
	 */
	private function validate_media_item($media_item) {
		if (!is_array($media_item)) {
			return array();
		}

		$validated = array();

		// Validate required fields
		if (isset($media_item['tag_name'])) {
			$validated['tag_name'] = sanitize_text_field($media_item['tag_name']);
		}

		if (isset($media_item['media_type']) &&
			in_array($media_item['media_type'], $this->supported_media_types, true)) {
			$validated['media_type'] = $media_item['media_type'];
		}

		// Validate type-specific fields
		switch ($validated['media_type'] ?? '') {
			case 'upload':
				if (isset($media_item['attachment_id']) && is_numeric($media_item['attachment_id'])) {
					$attachment_id = absint($media_item['attachment_id']);
					if (get_post($attachment_id)) {
						$validated['attachment_id'] = $attachment_id;
					}
				}
				break;

			case 'url':
				if (isset($media_item['media_url'])) {
					$url = esc_url_raw($media_item['media_url']);
					if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
						$validated['media_url'] = $url;
					}
				}
				break;

			case 'ecwid':
				if (isset($media_item['ecwid_position']) && is_numeric($media_item['ecwid_position'])) {
					$validated['ecwid_position'] = absint($media_item['ecwid_position']);
				}
				break;
		}

		return $validated;
	}

	/**
	 * AJAX handler for previewing media URLs.
	 *
	 * @since 0.2.1
	 *
	 * @return void
	 */
	public function ajax_preview_media_url() {
		check_ajax_referer('product_media_nonce', 'nonce');

		if (!current_user_can('edit_posts')) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';

		if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
			wp_send_json_error('Invalid URL provided');
			return;
		}

		try {
			// Perform a simple HEAD request to validate the URL
			$response = wp_remote_head($url, array(
				'timeout' => 5,
				'redirection' => 3,
			));

			if (is_wp_error($response)) {
				wp_send_json_error('Unable to access URL: ' . $response->get_error_message());
				return;
			}

			$response_code = wp_remote_retrieve_response_code($response);
			if ($response_code !== 200) {
				wp_send_json_error('URL returned status code: ' . $response_code);
				return;
			}

			$content_type = wp_remote_retrieve_header($response, 'content-type');
			$is_image = strpos($content_type, 'image/') === 0;

			wp_send_json_success(array(
				'valid'        => true,
				'content_type' => $content_type,
				'is_image'     => $is_image,
				'is_video'     => $this->is_video_url($url),
			));

		} catch (Exception $e) {
			$this->log_error('Error validating media URL', array(
				'url'   => $url,
				'error' => $e->getMessage(),
			));
			wp_send_json_error('Error validating URL');
		}
	}

	/**
	 * AJAX handler for loading Ecwid media.
	 *
	 * @since 0.2.1
	 *
	 * @return void
	 */
	public function ajax_load_ecwid_media() {
		check_ajax_referer('product_media_nonce', 'nonce');

		if (!current_user_can('edit_posts')) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

		if (!$post_id) {
			wp_send_json_error('Invalid post ID');
			return;
		}

		try {
			$ecwid_product_id = get_post_meta($post_id, '_ecwid_product_id', true);
			if (!$ecwid_product_id) {
				wp_send_json_error('No Ecwid product ID found');
				return;
			}

			$product = $this->ecwid_api->get_product_by_id($ecwid_product_id);
			if (!$product) {
				wp_send_json_error('Ecwid product not found');
				return;
			}

			$images = array();

			// Add main image
			if (!empty($product->thumbnailUrl)) {
				$images[] = array(
					'position' => 0,
					'url'      => $product->thumbnailUrl,
					'label'    => __('Main image (position 1)', 'peaches'),
				);
			}

			// Add gallery images
			if (!empty($product->galleryImages) && is_array($product->galleryImages)) {
				foreach ($product->galleryImages as $index => $gallery_image) {
					$images[] = array(
						'position' => $index + 1,
						'url'      => $gallery_image->url,
						'label'    => sprintf(__('Gallery image %d', 'peaches'), $index + 2),
					);
				}
			}

			wp_send_json_success(array(
				'images' => $images,
				'count'  => count($images),
			));

		} catch (Exception $e) {
			$this->log_error('Error loading Ecwid media', array(
				'post_id' => $post_id,
				'error'   => $e->getMessage(),
			));
			wp_send_json_error('Error loading Ecwid media');
		}
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 0.2.1
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts() {
		// Only enqueue if needed
		if (!is_singular() || !has_shortcode(get_post()->post_content, 'peaches_product_media')) {
			return;
		}

		wp_enqueue_style(
			'peaches-product-media',
			PEACHES_ECWID_ASSETS_URL . 'css/product-media.css',
			array(),
			PEACHES_ECWID_VERSION
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 0.2.1
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts($hook) {
		if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
			return;
		}

		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'product_settings') {
			return;
		}

		try {
			wp_enqueue_media();

			wp_enqueue_script(
				'peaches-product-media-admin',
				PEACHES_ECWID_ASSETS_URL . 'js/admin-product-media.js',
				array('jquery', 'media-upload'),
				PEACHES_ECWID_VERSION,
				true
			);

			// Get post ID for auto-loading functionality
			$post_id = 0;
			if (isset($_GET['post'])) {
				$post_id = absint($_GET['post']);
			}

			wp_localize_script('peaches-product-media-admin', 'ProductMediaParams', array(
				'nonce'               => wp_create_nonce('product_media_nonce'),
				'selectMediaTitle'    => __('Select Product Media', 'peaches'),
				'selectMediaButton'   => __('Use this media', 'peaches'),
				'confirmRemove'       => __('Are you sure you want to remove this media?', 'peaches'),
				'loadingText'         => __('Loading...', 'peaches'),
				'errorText'           => __('Error occurred', 'peaches'),
				'pleaseEnterUrl'      => __('Please enter a URL first', 'peaches'),
				'urlValidationFailed' => __('URL validation failed:', 'peaches'),
				'errorValidatingUrl'  => __('Error validating URL', 'peaches'),
				'ajaxUrl'             => admin_url('admin-ajax.php'),
				'postId'              => $post_id,
				'autoLoadImages'      => true, // Flag to enable auto-loading
			));

			wp_enqueue_style(
				'peaches-product-media-admin',
				PEACHES_ECWID_ASSETS_URL . 'css/admin-product-media.css',
				array(),
				PEACHES_ECWID_VERSION
			);

		} catch (Exception $e) {
			$this->log_error('Failed to enqueue admin scripts', array(
				'hook'  => $hook,
				'error' => $e->getMessage(),
			));
		}
	}

	/**
	 * Get all product media for a post.
	 *
	 * Retrieves all media associated with a product settings post.
	 *
	 * @since 0.2.1
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array Array of media items.
	 *
	 * @throws InvalidArgumentException If post ID is invalid.
	 */
	public function get_all_product_media($post_id) {
		if (!is_numeric($post_id) || $post_id <= 0) {
			throw new InvalidArgumentException('Invalid post ID provided');
		}

		$post_id = absint($post_id);

		try {
			// Check cache first
			$cache_key = 'media_' . $post_id;
			if (!isset($this->cache[$cache_key])) {
				$product_media = get_post_meta($post_id, '_product_media', true);
				$this->cache[$cache_key] = is_array($product_media) ? $product_media : array();
			}

			$product_media = $this->cache[$cache_key];

			// Validate all media items
			$validated_media = array();
			foreach ($product_media as $media_item) {
				$validated_item = $this->validate_media_item($media_item);
				if (!empty($validated_item)) {
					$validated_media[] = $validated_item;
				}
			}

			$this->log_info('Retrieved all product media', array(
				'post_id'      => $post_id,
				'total_count'  => count($product_media),
				'valid_count'  => count($validated_media),
			));

			return $validated_media;

		} catch (Exception $e) {
			$this->log_error('Exception retrieving all product media', array(
				'post_id' => $post_id,
				'error'   => $e->getMessage(),
				'trace'   => $e->getTraceAsString(),
			));
			return array();
		}
	}

	/**
	 * Delete product media by tag.
	 *
	 * Removes a specific media item by tag key.
	 *
	 * @since 0.2.1
	 *
	 * @param int    $post_id Post ID.
	 * @param string $tag_key Tag key to remove.
	 *
	 * @return bool True on success, false on failure.
	 *
	 * @throws InvalidArgumentException If parameters are invalid.
	 */
	public function delete_product_media_by_tag($post_id, $tag_key) {
		if (!is_numeric($post_id) || $post_id <= 0) {
			throw new InvalidArgumentException('Invalid post ID provided');
		}

		if (empty($tag_key) || !is_string($tag_key)) {
			throw new InvalidArgumentException('Invalid tag key provided');
		}

		$post_id = absint($post_id);
		$tag_key = sanitize_key($tag_key);

		try {
			$product_media = get_post_meta($post_id, '_product_media', true);
			if (!is_array($product_media)) {
				return true; // Nothing to delete
			}

			$updated_media = array();
			$deleted_item = null;

			foreach ($product_media as $media_item) {
				if (isset($media_item['tag_name']) && $media_item['tag_name'] === $tag_key) {
					$deleted_item = $media_item;
					continue; // Skip this item (delete it)
				}
				$updated_media[] = $media_item;
			}

			if ($deleted_item === null) {
				$this->log_info('No media found to delete for tag', array(
					'post_id' => $post_id,
					'tag_key' => $tag_key,
				));
				return true; // Nothing was found to delete
			}

			// Clean up attachment meta if it was a WordPress upload
			if (isset($deleted_item['attachment_id'])) {
				delete_post_meta($deleted_item['attachment_id'], '_peaches_product_media');
				delete_post_meta($deleted_item['attachment_id'], '_peaches_product_media_tag');
			}

			$result = update_post_meta($post_id, '_product_media', $updated_media);

			if ($result !== false) {
				// Clear cache
				unset($this->cache['media_' . $post_id]);

				$this->log_info('Successfully deleted product media by tag', array(
					'post_id'    => $post_id,
					'tag_key'    => $tag_key,
					'media_type' => $deleted_item['media_type'] ?? 'unknown',
				));
			}

			return $result !== false;

		} catch (Exception $e) {
			$this->log_error('Exception deleting product media by tag', array(
				'post_id' => $post_id,
				'tag_key' => $tag_key,
				'error'   => $e->getMessage(),
				'trace'   => $e->getTraceAsString(),
			));
			return false;
		}
	}

	/**
	 * Clear media cache for a specific post.
	 *
	 * @since 0.2.1
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function clear_media_cache($post_id = null) {
		if ($post_id) {
			unset($this->cache['media_' . absint($post_id)]);
		} else {
			$this->cache = array();
		}

		$this->log_info('Cleared media cache', array(
			'post_id' => $post_id,
		));
	}

	/**
	 * Log informational messages.
	 *
	 * @since 0.2.1
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_info($message, $context = array()) {
		if (class_exists('Peaches_Ecwid_Utilities') && Peaches_Ecwid_Utilities::is_debug_mode()) {
			Peaches_Ecwid_Utilities::log_error('[INFO] [Product Media Manager] ' . $message, $context);
		}
	}

	/**
	 * Log error messages.
	 *
	 * @since 0.2.1
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_error($message, $context = array()) {
		if (class_exists('Peaches_Ecwid_Utilities')) {
			Peaches_Ecwid_Utilities::log_error('[Product Media Manager] ' . $message, $context);
		} else {
			// Fallback logging if utilities class is not available
			error_log('[Peaches Ecwid] [Product Media Manager] ' . $message . (empty($context) ? '' : ' - Context: ' . wp_json_encode($context)));
		}
	}
}
