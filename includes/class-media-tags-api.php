<?php
/**
 * Media Tags API for Gutenberg blocks
 *
 * Provides REST API endpoints and helper functions for Gutenberg blocks
 * to easily access media tags and product media.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Media_Tags_API
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Media_Tags_API {

	/**
	 * Media Tags Manager instance.
	 *
	 * @var Peaches_Media_Tags_Manager
	 */
	private $media_tags_manager;

	/**
	 * Product Settings Manager instance.
	 *
	 * @var Peaches_Product_Settings_Manager
	 */
	private $product_settings_manager;

	/**
	 * Constructor.
	 *
	 * @param Peaches_Media_Tags_Manager        $media_tags_manager
	 * @param Peaches_Product_Settings_Manager  $product_settings_manager
	 */
	public function __construct($media_tags_manager, $product_settings_manager) {
		$this->media_tags_manager = $media_tags_manager;
		$this->product_settings_manager = $product_settings_manager;

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action('rest_api_init', array($this, 'register_api_routes'));
		add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
	}

	/**
	 * Register REST API routes.
	 */
	public function register_api_routes() {
		// Get all available media tags
		register_rest_route('peaches/v1', '/media-tags', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_media_tags'),
			'permission_callback' => '__return_true',
		));

		// Get media tags by category
		register_rest_route('peaches/v1', '/media-tags/category/(?P<category>[a-zA-Z0-9_-]+)', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_media_tags_by_category'),
			'permission_callback' => '__return_true',
			'args' => array(
				'category' => array(
					'validate_callback' => function($param, $request, $key) {
						return is_string($param);
					}
				),
			),
		));

		// Get product media by tag
		register_rest_route('peaches/v1', '/product-media/(?P<product_id>\d+)', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_product_media'),
			'permission_callback' => '__return_true',
			'args' => array(
				'product_id' => array(
					'validate_callback' => function($param, $request, $key) {
						return is_numeric($param);
					}
				),
			),
		));

		// Get specific media for product and tag
		register_rest_route('peaches/v1', '/product-media/(?P<product_id>\d+)/tag/(?P<tag_key>[a-zA-Z0-9_-]+)', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_product_media_by_tag'),
			'permission_callback' => '__return_true',
			'args' => array(
				'product_id' => array(
					'validate_callback' => function($param, $request, $key) {
						return is_numeric($param);
					}
				),
				'tag_key' => array(
					'validate_callback' => function($param, $request, $key) {
						return is_string($param);
					}
				),
			),
		));
	}

	/**
	 * Get all media tags.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_media_tags($request) {
		$tags = $this->media_tags_manager->get_all_tags();

		// Format for frontend consumption - INCLUDE expected_media_type
		$formatted_tags = array();
		foreach ($tags as $tag_key => $tag_data) {
			$formatted_tags[] = array(
				'key' => $tag_key,
				'label' => $tag_data['label'],
				'description' => isset($tag_data['description']) ? $tag_data['description'] : '',
				'category' => $tag_data['category'],
				'expected_media_type' => isset($tag_data['expected_media_type']) ? $tag_data['expected_media_type'] : 'image' // Default fallback
			);
		}

		return new WP_REST_Response(array(
			'success' => true,
			'data' => $formatted_tags
		), 200);
	}

	/**
	 * Get media tags by category.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_media_tags_by_category($request) {
		$category = $request['category'];
		$tags = $this->media_tags_manager->get_tags_by_category($category);

		// Format for frontend consumption - INCLUDE expected_media_type
		$formatted_tags = array();
		foreach ($tags as $tag_key => $tag_data) {
			$formatted_tags[] = array(
				'key' => $tag_key,
				'label' => $tag_data['label'],
				'description' => isset($tag_data['description']) ? $tag_data['description'] : '',
				'category' => $tag_data['category'],
				'expected_media_type' => isset($tag_data['expected_media_type']) ? $tag_data['expected_media_type'] : 'image' // Default fallback
			);
		}

		return new WP_REST_Response(array(
			'success' => true,
			'data' => $formatted_tags,
			'category' => $category
		), 200);
	}

	/**
	 * Get all product media organized by tags.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_product_media($request) {
		$product_id = $request['product_id'];

		// Get product settings post by Ecwid product ID
		$product_settings = $this->get_product_settings_by_ecwid_id($product_id);

		if (!$product_settings) {
			return new WP_REST_Response(array(
				'success' => false,
				'error' => 'No product settings found for this product',
				'product_id' => $product_id
			), 404);
		}

		// Get all product media using the enhanced format
		$product_media = get_post_meta($product_settings->ID, '_product_media', true);
		$available_tags = $this->media_tags_manager->get_all_tags();

		// Format response with full media data
		$formatted_media = array();

		if (is_array($product_media)) {
			foreach ($product_media as $media_item) {
				if (!isset($media_item['tag_name'])) {
					continue;
				}

				$tag_name = $media_item['tag_name'];
				$processed_data = $this->process_enhanced_media_data($media_item, $product_settings->ID);

				if ($processed_data) {
					$processed_data['tag_info'] = isset($available_tags[$tag_name]) ? $available_tags[$tag_name] : null;
					$formatted_media[$tag_name] = $processed_data;
				}
			}
		}

		return new WP_REST_Response(array(
			'success' => true,
			'product_id' => $product_id,
			'data' => $formatted_media
		), 200);
	}

	/**
	 * Get specific media for product and tag.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_product_media_by_tag($request) {
		$product_id = $request['product_id'];
		$tag_key = $request['tag_key'];

		// Check if tag exists
		if (!$this->media_tags_manager->tag_exists($tag_key)) {
			return new WP_REST_Response(array(
				'success' => false,
				'error' => 'Media tag not found',
				'tag_key' => $tag_key
			), 404);
		}

		// Get product settings post by Ecwid product ID
		$product_settings = $this->get_product_settings_by_ecwid_id($product_id);

		if (!$product_settings) {
			return new WP_REST_Response(array(
				'success' => false,
				'error' => 'No product settings found for this product',
				'product_id' => $product_id
			), 404);
		}

		// Use the enhanced media manager to get media by tag
		$media_data = $this->product_settings_manager->get_product_media_by_tag($product_settings->ID, $tag_key);

		if (!$media_data) {
			return new WP_REST_Response(array(
				'success' => false,
				'error' => 'No media found for this tag',
				'product_id' => $product_id,
				'tag_key' => $tag_key
			), 404);
		}

		// Process the enhanced media format
		$response_data = $this->process_enhanced_media_data($media_data, $product_settings->ID);

		if (!$response_data) {
			return new WP_REST_Response(array(
				'success' => false,
				'error' => 'Media file not found or invalid',
				'product_id' => $product_id,
				'tag_key' => $tag_key
			), 404);
		}

		$tag_info = $this->media_tags_manager->get_tag($tag_key);
		$response_data['tag_info'] = $tag_info;

		return new WP_REST_Response(array(
			'success' => true,
			'product_id' => $product_id,
			'tag_key' => $tag_key,
			'data' => $response_data
		), 200);
	}

	/**
	 * Process enhanced media data format into API response format
	 *
	 * @param array $media_data Enhanced media data from Product Settings Manager
	 * @param int   $post_id    Product settings post ID
	 *
	 * @return array|null Processed media data or null if invalid
	 */
	private function process_enhanced_media_data($media_data, $post_id) {
		if (!is_array($media_data) || !isset($media_data['media_type'])) {
			return null;
		}

		$media_type = $media_data['media_type'];

		switch ($media_type) {
			case 'upload':
				if (!empty($media_data['attachment_id'])) {
					return $this->format_wordpress_media_response($media_data['attachment_id']);
				}
				break;

			case 'url':
				if (!empty($media_data['media_url'])) {
					return $this->format_url_media_response($media_data['media_url']);
				}
				break;

			case 'ecwid':
				if (isset($media_data['ecwid_position']) && $post_id) {
					return $this->format_ecwid_media_response($media_data['ecwid_position'], $post_id);
				}
				break;
		}

		return null;
	}

	/**
	 * Format WordPress media attachment response
	 *
	 * @param int $attachment_id WordPress attachment ID
	 *
	 * @return array|null Formatted response or null if invalid
	 */
	private function format_wordpress_media_response($attachment_id) {
		$attachment = get_post($attachment_id);

		if (!$attachment) {
			return null;
		}

		$mime_type = get_post_mime_type($attachment_id);
		$media_type = $this->determine_media_type_from_url(wp_get_attachment_url($attachment_id), $mime_type);

		return array(
			'attachment_id' => $attachment_id,
			'url' => wp_get_attachment_url($attachment_id),
			'title' => $attachment->post_title,
			'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
			'caption' => $attachment->post_excerpt,
			'description' => $attachment->post_content,
			'mime_type' => $mime_type,
			'type' => $media_type,
			'sizes' => $this->get_attachment_sizes($attachment_id),
			'source' => 'wordpress'
		);
	}

	/**
	 * Format external URL media response
	 *
	 * @param string $media_url External media URL
	 *
	 * @return array Formatted response
	 */
	private function format_url_media_response($media_url) {
		$media_type = $this->determine_media_type_from_url($media_url);

		return array(
			'url' => $media_url,
			'title' => basename(parse_url($media_url, PHP_URL_PATH)),
			'alt' => '',
			'caption' => '',
			'description' => '',
			'mime_type' => $this->guess_mime_type_from_url($media_url),
			'type' => $media_type,
			'sizes' => array(
				'full' => array(
					'url' => $media_url,
					'width' => 0,
					'height' => 0,
					'type' => $media_type
				)
			),
			'source' => 'external'
		);
	}

	/**
	 * Format Ecwid media response
	 *
	 * @param int $ecwid_position Ecwid image position
	 * @param int $post_id        Product settings post ID
	 *
	 * @return array|null Formatted response or null if invalid
	 */
	private function format_ecwid_media_response($ecwid_position, $post_id) {
		// Get Ecwid product ID from post meta
		$ecwid_product_id = get_post_meta($post_id, '_ecwid_product_id', true);

		if (!$ecwid_product_id) {
			return null;
		}

		// Get product from Ecwid API
		$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
		$ecwid_api = $ecwid_blocks->get_ecwid_api();
		$product = $ecwid_api->get_product_by_id($ecwid_product_id);

		if (!$product) {
			return null;
		}

		// Get image URL by position
		$image_url = $this->get_ecwid_image_by_position($product, $ecwid_position);

		if (!$image_url) {
			return null;
		}

		$media_type = $this->determine_media_type_from_url($image_url);

		return array(
			'url' => $image_url,
			'title' => $product->name . ' - Image ' . ($ecwid_position + 1),
			'alt' => $product->name,
			'caption' => '',
			'description' => '',
			'mime_type' => $this->guess_mime_type_from_url($image_url),
			'type' => $media_type,
			'sizes' => array(
				'full' => array(
					'url' => $image_url,
					'width' => 0,
					'height' => 0,
					'type' => $media_type
				)
			),
			'source' => 'ecwid',
			'ecwid_position' => $ecwid_position
		);
	}

	/**
	 * Get Ecwid image URL by position
	 *
	 * @param object $product  Ecwid product object
	 * @param int    $position Image position
	 *
	 * @return string|null Image URL or null
	 */
	private function get_ecwid_image_by_position($product, $position) {
		$position = intval($position);

		if ($position === 0 && !empty($product->thumbnailUrl)) {
			return $product->thumbnailUrl;
		}

		if (!empty($product->galleryImages) && is_array($product->galleryImages)) {
			// Position 1+ refers to gallery images (0-indexed)
			$gallery_index = $position - 1;
			if (isset($product->galleryImages[$gallery_index])) {
				return $product->galleryImages[$gallery_index]->url;
			}
		}

		return null;
	}

	/**
	 * Determine media type from URL and optional mime type
	 *
	 * @param string $url      Media URL
	 * @param string $mimeType Optional mime type
	 *
	 * @return string Media type ('video' or 'image')
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
	 * Guess mime type from URL
	 *
	 * @param string $url Media URL
	 *
	 * @return string Guessed mime type
	 */
	private function guess_mime_type_from_url($url) {
		$extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

		$mime_types = array(
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'svg' => 'image/svg+xml',
			'mp4' => 'video/mp4',
			'webm' => 'video/webm',
			'ogg' => 'video/ogg',
			'avi' => 'video/x-msvideo',
			'mov' => 'video/quicktime',
			'mp3' => 'audio/mpeg',
			'wav' => 'audio/wav',
			'aac' => 'audio/aac',
			'flac' => 'audio/flac',
			'pdf' => 'application/pdf',
			'doc' => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);

		return isset($mime_types[$extension]) ? $mime_types[$extension] : 'image/jpeg';
	}

	/**
	 * Gets the media sizes
	 *
	 * @param int $attachment_id
	 *
	 * @return array
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
				'url' => wp_get_attachment_url($attachment_id),
				'width' => isset($metadata['width']) ? $metadata['width'] : 0,
				'height' => isset($metadata['height']) ? $metadata['height'] : 0,
				'type' => 'video'
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
				'url' => $base_url . '/' . $base_path . '/' . $size_data['file'],
				'width' => $size_data['width'],
				'height' => $size_data['height'],
				'type' => 'image'
			);
		}

		// Add full size
		$sizes['full'] = array(
			'url' => wp_get_attachment_url($attachment_id),
			'width' => isset($metadata['width']) ? $metadata['width'] : 0,
			'height' => isset($metadata['height']) ? $metadata['height'] : 0,
			'type' => 'image'
		);

		return $sizes;
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function enqueue_block_editor_assets() {
		// Localize script with media tags data for block editor
		wp_localize_script('wp-blocks', 'PeachesMediaTags', array(
			'apiUrl' => rest_url('peaches/v1/'),
			'tags' => $this->media_tags_manager->get_all_tags(),
			'nonce' => wp_create_nonce('wp_rest')
		));
	}

	/**
	 * Get product settings post by Ecwid product ID.
	 *
	 * @param int $ecwid_product_id
	 *
	 * @return WP_Post|null
	 */
	private function get_product_settings_by_ecwid_id($ecwid_product_id) {
		$args = array(
			'post_type' => 'product_settings',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_ecwid_product_id',
					'value' => $ecwid_product_id,
					'compare' => '='
				)
			)
		);

		$query = new WP_Query($args);

		if ($query->have_posts()) {
			return $query->posts[0];
		}

		return null;
	}

	/**
	 * Helper function to get media URL by tag for templates.
	 *
	 * @param int    $product_id Ecwid product ID
	 * @param string $tag_key    Media tag key
	 * @param string $size       Image size (optional)
	 *
	 * @return string|null Media URL or null if not found
	 */
	public static function get_media_url($product_id, $tag_key, $size = 'full') {
		// Get product settings
		$args = array(
			'post_type' => 'product_settings',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_ecwid_product_id',
					'value' => $product_id,
					'compare' => '='
				)
			)
		);

		$query = new WP_Query($args);

		if (!$query->have_posts()) {
			return null;
		}

		$product_settings = $query->posts[0];
		$product_media = get_post_meta($product_settings->ID, '_product_media', true);

		if (!is_array($product_media)) {
			return null;
		}

		// Find media for the specified tag using enhanced format
		foreach ($product_media as $media_item) {
			if (isset($media_item['tag_name']) && $media_item['tag_name'] === $tag_key) {
				// Handle different media types
				switch ($media_item['media_type']) {
					case 'upload':
						if (!empty($media_item['attachment_id'])) {
							if ($size === 'full') {
								return wp_get_attachment_url($media_item['attachment_id']);
							} else {
								$image = wp_get_attachment_image_src($media_item['attachment_id'], $size);
								return $image ? $image[0] : null;
							}
						}
						break;

					case 'url':
						if (!empty($media_item['media_url'])) {
							return $media_item['media_url'];
						}
						break;

					case 'ecwid':
						if (isset($media_item['ecwid_position'])) {
							// Get Ecwid product and return image URL
							$ecwid_product_id = get_post_meta($product_settings->ID, '_ecwid_product_id', true);
							if ($ecwid_product_id) {
								$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
								$ecwid_api = $ecwid_blocks->get_ecwid_api();
								$product = $ecwid_api->get_product_by_id($ecwid_product_id);

								if ($product) {
									$api = new self(new Peaches_Media_Tags_Manager(), null);
									return $api->get_ecwid_image_by_position($product, $media_item['ecwid_position']);
								}
							}
						}
						break;
				}
			}
		}

		return null;
	}

	/**
	 * Helper function to get media data by tag for templates.
	 *
	 * @param int    $product_id Ecwid product ID
	 * @param string $tag_key    Media tag key
	 *
	 * @return array|null Media data array or null if not found
	 */
	public static function get_media_data($product_id, $tag_key) {
		// Get product settings
		$args = array(
			'post_type' => 'product_settings',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_ecwid_product_id',
					'value' => $product_id,
					'compare' => '='
				)
			)
		);

		$query = new WP_Query($args);

		if (!$query->have_posts()) {
			return null;
		}

		$product_settings = $query->posts[0];
		$product_media = get_post_meta($product_settings->ID, '_product_media', true);

		if (!is_array($product_media)) {
			return null;
		}

		// Find media for the specified tag using enhanced format
		foreach ($product_media as $media_item) {
			if (isset($media_item['tag_name']) && $media_item['tag_name'] === $tag_key) {
				// Return enhanced media data based on type
				switch ($media_item['media_type']) {
					case 'upload':
						if (!empty($media_item['attachment_id'])) {
							$attachment = get_post($media_item['attachment_id']);
							if ($attachment) {
								return array(
									'id' => $media_item['attachment_id'],
									'url' => wp_get_attachment_url($media_item['attachment_id']),
									'title' => $attachment->post_title,
									'alt' => get_post_meta($media_item['attachment_id'], '_wp_attachment_image_alt', true),
									'caption' => $attachment->post_excerpt,
									'description' => $attachment->post_content,
									'sizes' => wp_get_attachment_metadata($media_item['attachment_id']),
									'type' => 'wordpress',
									'media_type' => 'upload'
								);
							}
						}
						break;

					case 'url':
						if (!empty($media_item['media_url'])) {
							return array(
								'url' => $media_item['media_url'],
								'title' => basename(parse_url($media_item['media_url'], PHP_URL_PATH)),
								'alt' => '',
								'caption' => '',
								'description' => '',
								'type' => 'external',
								'media_type' => 'url'
							);
						}
						break;

					case 'ecwid':
						if (isset($media_item['ecwid_position'])) {
							$ecwid_product_id = get_post_meta($product_settings->ID, '_ecwid_product_id', true);
							if ($ecwid_product_id) {
								$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
								$ecwid_api = $ecwid_blocks->get_ecwid_api();
								$product = $ecwid_api->get_product_by_id($ecwid_product_id);

								if ($product) {
									$api = new self(new Peaches_Media_Tags_Manager(), null);
									$image_url = $api->get_ecwid_image_by_position($product, $media_item['ecwid_position']);

									if ($image_url) {
										return array(
											'url' => $image_url,
											'title' => $product->name . ' - Image ' . ($media_item['ecwid_position'] + 1),
											'alt' => $product->name,
											'caption' => '',
											'description' => '',
											'type' => 'ecwid',
											'media_type' => 'ecwid',
											'ecwid_position' => $media_item['ecwid_position']
										);
									}
								}
							}
						}
						break;
				}
			}
		}

		return null;
	}
}

// Template helper functions for easy use in themes
if (!function_exists('peaches_get_product_media_url')) {
	/**
	 * Template function to get product media URL by tag.
	 *
	 * @param int    $product_id Ecwid product ID
	 * @param string $tag_key    Media tag key
	 * @param string $size       Image size (optional)
	 *
	 * @return string|null Media URL or null if not found
	 */
	function peaches_get_product_media_url($product_id, $tag_key, $size = 'full') {
		return Peaches_Media_Tags_API::get_media_url($product_id, $tag_key, $size);
	}
}

if (!function_exists('peaches_get_product_media_data')) {
	/**
	 * Template function to get product media data by tag.
	 *
	 * @param int    $product_id Ecwid product ID
	 * @param string $tag_key    Media tag key
	 *
	 * @return array|null Media data array or null if not found
	 */
	function peaches_get_product_media_data($product_id, $tag_key) {
		return Peaches_Media_Tags_API::get_media_data($product_id, $tag_key);
	}
}

if (!function_exists('peaches_the_product_media')) {
	/**
	 * Template function to display product media by tag.
	 *
	 * @param int    $product_id   Ecwid product ID
	 * @param string $tag_key      Media tag key
	 * @param string $size         Image size (optional)
	 * @param array  $attributes   Additional HTML attributes (optional)
	 */
	function peaches_the_product_media($product_id, $tag_key, $size = 'full', $attributes = array()) {
		$media_data = peaches_get_product_media_data($product_id, $tag_key);

		if (!$media_data) {
			return;
		}

		$url = isset($media_data['sizes']) && $size !== 'full' && isset($media_data['sizes'][$size])
			? $media_data['sizes'][$size]['url']
			: $media_data['url'];
		$alt = $media_data['alt'] ?: $media_data['title'];

		// Build attributes string
		$attr_string = '';
		foreach ($attributes as $key => $value) {
			$attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
		}

		// Check if it's a video
		$media_type = isset($media_data['media_type']) ? $media_data['media_type'] : 'image';
		$is_video = (strpos($media_data['url'], '.mp4') !== false) ||
					(strpos($media_data['url'], '.webm') !== false) ||
					(strpos($media_data['url'], '.ogg') !== false);

		if ($is_video) {
			echo '<video src="' . esc_url($url) . '" controls' . $attr_string . '></video>';
		} else {
			echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '"' . $attr_string . '>';
		}
	}
}

if (!function_exists('peaches_get_available_media_tags')) {
	/**
	 * Template function to get all available media tags.
	 *
	 * @return array Array of available media tags
	 */
	function peaches_get_available_media_tags() {
		$media_tags_manager = new Peaches_Media_Tags_Manager();
		return $media_tags_manager->get_all_tags();
	}
}
