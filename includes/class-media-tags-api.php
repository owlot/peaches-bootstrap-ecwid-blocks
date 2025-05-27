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

		// Format for frontend consumption
		$formatted_tags = array();
		foreach ($tags as $tag_key => $tag_data) {
			$formatted_tags[] = array(
				'key' => $tag_key,
				'label' => $tag_data['label'],
				'description' => $tag_data['description'],
				'category' => $tag_data['category']
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

		// Format for frontend consumption
		$formatted_tags = array();
		foreach ($tags as $tag_key => $tag_data) {
			$formatted_tags[] = array(
				'key' => $tag_key,
				'label' => $tag_data['label'],
				'description' => $tag_data['description'],
				'category' => $tag_data['category']
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

		$media_by_tags = $this->product_settings_manager->get_product_media_by_tags($product_settings->ID);
		$available_tags = $this->media_tags_manager->get_all_tags();

		// Format response with full media data
		$formatted_media = array();
		foreach ($media_by_tags as $tag_key => $attachment_id) {
			$attachment = get_post($attachment_id);
			if ($attachment) {
				$formatted_media[$tag_key] = array(
					'attachment_id' => $attachment_id,
					'url' => wp_get_attachment_url($attachment_id),
					'title' => $attachment->post_title,
					'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
					'sizes' => $this->get_attachment_sizes($attachment_id),
					'tag_info' => isset($available_tags[$tag_key]) ? $available_tags[$tag_key] : null
				);
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

		$attachment_id = $this->product_settings_manager->get_product_media_by_tag($product_settings->ID, $tag_key);

		if (!$attachment_id) {
			return new WP_REST_Response(array(
				'success' => false,
				'error' => 'No media found for this tag',
				'product_id' => $product_id,
				'tag_key' => $tag_key
			), 404);
		}

		$attachment = get_post($attachment_id);
		if (!$attachment) {
			return new WP_REST_Response(array(
				'success' => false,
				'error' => 'Media file not found',
				'attachment_id' => $attachment_id
			), 404);
		}

		$tag_info = $this->media_tags_manager->get_tag($tag_key);

		$media_data = array(
			'attachment_id' => $attachment_id,
			'url' => wp_get_attachment_url($attachment_id),
			'title' => $attachment->post_title,
			'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
			'sizes' => $this->get_attachment_sizes($attachment_id),
			'tag_info' => $tag_info
		);

		return new WP_REST_Response(array(
			'success' => true,
			'product_id' => $product_id,
			'tag_key' => $tag_key,
			'data' => $media_data
		), 200);
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
	 * Get attachment sizes for an attachment.
	 *
	 * @param int $attachment_id
	 *
	 * @return array
	 */
	private function get_attachment_sizes($attachment_id) {
		$sizes = array();
		$metadata = wp_get_attachment_metadata($attachment_id);

		if (!$metadata || !isset($metadata['sizes'])) {
			return $sizes;
		}

		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		$base_path = dirname($metadata['file']);

		foreach ($metadata['sizes'] as $size_name => $size_data) {
			$sizes[$size_name] = array(
				'url' => $base_url . '/' . $base_path . '/' . $size_data['file'],
				'width' => $size_data['width'],
				'height' => $size_data['height']
			);
		}

		// Add full size
		$sizes['full'] = array(
			'url' => wp_get_attachment_url($attachment_id),
			'width' => $metadata['width'],
			'height' => $metadata['height']
		);

		return $sizes;
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

		// Find media for the specified tag
		foreach ($product_media as $media_item) {
			if (isset($media_item['tag_name']) && $media_item['tag_name'] === $tag_key) {
				$attachment_id = $media_item['attachment_id'];

				if ($size === 'full') {
					return wp_get_attachment_url($attachment_id);
				} else {
					$image = wp_get_attachment_image_src($attachment_id, $size);
					return $image ? $image[0] : null;
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

		// Find media for the specified tag
		foreach ($product_media as $media_item) {
			if (isset($media_item['tag_name']) && $media_item['tag_name'] === $tag_key) {
				$attachment_id = $media_item['attachment_id'];
				$attachment = get_post($attachment_id);

				if (!$attachment) {
					return null;
				}

				return array(
					'id' => $attachment_id,
					'url' => wp_get_attachment_url($attachment_id),
					'title' => $attachment->post_title,
					'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
					'caption' => $attachment->post_excerpt,
					'description' => $attachment->post_content,
					'sizes' => wp_get_attachment_metadata($attachment_id)
				);
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

		$url = $size === 'full' ? $media_data['url'] : wp_get_attachment_image_src($media_data['id'], $size)[0];
		$alt = $media_data['alt'] ?: $media_data['title'];

		// Build attributes string
		$attr_string = '';
		foreach ($attributes as $key => $value) {
			$attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
		}

		echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '"' . $attr_string . '>';
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
