<?php
/**
 * REST API Handler class
 *
 * Consolidates all REST API endpoints for the Peaches Ecwid Blocks plugin.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.5
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_REST_API
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.5
 */
class Peaches_REST_API {
	/**
	 * API namespace.
	 *
	 * @since 0.2.5
	 * @var string
	 */
	const NAMESPACE = 'peaches/v1';

	/**
	 * Product Settings Manager instance.
	 *
	 * @since 0.2.5
	 * @var Peaches_Product_Settings_Manager
	 */
	private $product_settings_manager;

	/**
	 * Media Tags Manager instance.
	 *
	 * @since 0.2.5
	 * @var Peaches_Media_Tags_Manager
	 */
	private $media_tags_manager;

	/**
	 * Product Media Manager instance.
	 *
	 * @since  0.2.6
	 * @access private
	 * @var    Peaches_Product_Media_Manager
	 */
	private $product_media_manager;

	/**
	 * Ecwid API instance.
	 *
	 * @since 0.2.6
	 * @var Peaches_Ecwid_API
	 */
	private $ecwid_api;

	/**
	 * Product Manager instance.
	 *
	 * @since 0.2.7
	 * @var Peaches_Product_Manager
	 */
	private $product_manager;

	/**
	 *
	 * Product Lines Manager instance.
	 *
	 * @since 0.3.1
	 * @var Peaches_Product_Lines_Manager
	 */
	private $product_lines_manager;

	/**
	 * Constructor.
	 *
	 * @since 0.2.6
	 *
	 * @param Peaches_Product_Settings_Manager $product_settings_manager Product Settings Manager instance.
	 * @param Peaches_Media_Tags_Manager       $media_tags_manager       Media Tags Manager instance.
	 * @param Peaches_Product_Media_Manager    $product_media_manager    Product Media Manager instance.
	 * @param Peaches_Ecwid_API                $ecwid_api                Ecwid API instance.
	 * @param Peaches_Product_Manager          $product_manager          Product manager instance.
	 * @param Peaches_Product_Lines_Manager    $product_lines_manager    Product lines manager instance.
	 */
	public function __construct($product_settings_manager, $media_tags_manager, $product_media_manager, $ecwid_api, $product_manager, $product_lines_manager) {
		$this->product_settings_manager = $product_settings_manager;
		$this->media_tags_manager       = $media_tags_manager;
		$this->product_media_manager    = $product_media_manager;
		$this->ecwid_api                = $ecwid_api;
		$this->product_manager          = $product_manager;
		$this->product_lines_manager    = $product_lines_manager;

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 0.2.5
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action('rest_api_init', array($this, 'register_routes'));
	}
/**
	 * Register all REST API routes.
	 *
	 * @since 0.2.5
	 *
	 * @return void
	 */
	public function register_routes() {
		// Product ingredients endpoint
		register_rest_route(
			self::NAMESPACE,
			'/product-ingredients/(?P<product_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_product_ingredients'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'product_id' => array(
						'description' => __('Ecwid product ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					),
					'lang'       => array(
						'description' => __('Language code for multilingual sites.', 'peaches'),
						'type'        => 'string',
						'required'    => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Product lines endpoint (NEW)
		register_rest_route(
			self::NAMESPACE,
			'/product-lines/(?P<product_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_product_lines'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'product_id' => array(
						'description' => __('Ecwid product ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					),
					'lang'       => array(
						'description' => __('Language code for multilingual sites.', 'peaches'),
						'type'        => 'string',
						'required'    => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Product lines by type endpoint
		register_rest_route(
			self::NAMESPACE,
			'/product-lines/(?P<product_id>\d+)/type/(?P<line_type>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_product_lines'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'product_id' => array(
						'description' => __('Ecwid product ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					),
					'line_type'  => array(
						'description' => __('Filter by line type.', 'peaches'),
						'type'        => 'string',
						'required'    => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'lang'       => array(
						'description' => __('Language code for multilingual sites.', 'peaches'),
						'type'        => 'string',
						'required'    => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Product line media endpoint
		register_rest_route(
			self::NAMESPACE,
			'/product-lines/(?P<line_id>\d+)/media',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_product_line_media'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'line_id' => array(
						'description' => __('Product line ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					),
				),
			)
		);

		// All line types endpoint (NEW)
		register_rest_route(
			self::NAMESPACE,
			'/line-types',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_line_types'),
				'permission_callback' => array($this, 'check_public_permissions'),
			)
		);

		// Product descriptions endpoint (NEW)
		register_rest_route(
			self::NAMESPACE,
			'/product-descriptions/(?P<product_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_product_descriptions'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'product_id' => array(
						'description' => __('Ecwid product ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					),
					'type'       => array(
						'description' => __('Filter by description type.', 'peaches'),
						'type'        => 'string',
						'required'    => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Product descriptions by type endpoint
		register_rest_route(
			self::NAMESPACE,
			'/product-descriptions/(?P<product_id>\d+)/type/(?P<type>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_product_description_by_type'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'product_id' => array(
						'description' => __('Ecwid product ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					),
					'type'       => array(
						'description' => __('Description type.', 'peaches'),
						'type'        => 'string',
						'required'    => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Product media by tag endpoint (consolidating existing functionality)
		register_rest_route(
			self::NAMESPACE,
			'/product-media/(?P<product_id>\d+)/tag/(?P<tag_key>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_product_media_by_tag'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'product_id' => array(
						'description' => __('Ecwid product ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					),
					'tag_key'    => array(
						'description' => __('Media tag key.', 'peaches'),
						'type'        => 'string',
						'required'    => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Media tags endpoint (consolidating existing functionality)
		register_rest_route(
			self::NAMESPACE,
			'/media-tags',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_media_tags'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'category' => array(
						'description' => __('Filter by category.', 'peaches'),
						'type'        => 'string',
						'required'    => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Description types endpoint
		register_rest_route(
			self::NAMESPACE,
			'/description-types',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_description_types'),
				'permission_callback' => array($this, 'check_public_permissions'),
			)
		);

		// Related products endpoint
		register_rest_route(
			self::NAMESPACE,
			'/related-products/(?P<product_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_related_products'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'product_id' => array(
						'description' => __('Ecwid product ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					),
					'limit'      => array(
						'description' => __('Number of related products to return.', 'peaches'),
						'type'        => 'integer',
						'required'    => false,
						'default'     => 4,
						'minimum'     => 1,
						'maximum'     => 20,
					),
				),
			)
		);

		// Product data endpoint (replaces AJAX get_ecwid_product_data)
		register_rest_route(
			self::NAMESPACE,
			'/products/(?P<product_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_product_data'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'product_id' => array(
						'description' => __('Ecwid product ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
					),
				),
			)
		);

		// Categories endpoint (replaces AJAX get_ecwid_categories)
		register_rest_route(
			self::NAMESPACE,
			'/categories',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_categories'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'parent' => array(
						'description' => __('Parent category ID.', 'peaches'),
						'type'        => 'integer',
						'required'    => false,
						'minimum'     => 0,
					),
					'enabled' => array(
						'description' => __('Filter enabled categories only.', 'peaches'),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => true,
					),
				),
			)
		);
	}

	/**
	 * Get product lines for a specific product.
	 *
	 * @since 0.3.1
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_product_lines($request) {
		try {
			$product_id = $request->get_param('product_id');
			$line_type = $request->get_param('line_type');

			if (!$product_id || !is_numeric($product_id)) {
				return new WP_Error(
					'invalid_product_id',
					__('Invalid product ID provided.', 'peaches'),
					array('status' => 400)
				);
			}

			// Verify product exists in Ecwid
			$product = $this->ecwid_api->get_product_by_id($product_id);
			if (!$product) {
				return new WP_Error(
					'product_not_found',
					__('Product not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get product lines using the product manager
			$line_ids = $this->product_lines_manager->get_product_lines($product_id);

			if (empty($line_ids)) {
				return new WP_REST_Response(
					array(
						'success' => true,
						'data'    => array(),
						'count'   => 0,
						'message' => __('No product lines found for this product.', 'peaches'),
					),
					200
				);
			}

			// Get full line data
			$lines = array();
			foreach ($line_ids as $line_id) {
				$term = get_term($line_id, 'product_line');
				if (!is_wp_error($term) && $term) {
					$line_type_meta = get_term_meta($line_id, 'line_type', true);
					$line_description = get_term_meta($line_id, 'line_description', true);

					// Filter by line type if specified
					if ($line_type && $line_type_meta !== $line_type) {
						continue;
					}

					$lines[] = array(
						'id'          => $term->term_id,
						'name'        => $term->name,
						'slug'        => $term->slug,
						'description' => $term->description,
						'line_type'   => $line_type_meta ?: '',
						'line_description' => $line_description ?: '',
						'count'       => $term->count,
					);
				}
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $lines,
					'count'   => count($lines),
					'filter'  => array(
						'product_id' => $product_id,
						'line_type'  => $line_type,
					),
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting product lines: ' . $e->getMessage());

			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get media for a specific product line.
	 *
	 * @since 0.3.2
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_product_line_media($request) {
		try {
			$line_id = $request->get_param('line_id');

			if (!$line_id || !is_numeric($line_id)) {
				return new WP_Error(
					'invalid_line_id',
					__('Invalid line ID provided.', 'peaches'),
					array('status' => 400)
				);
			}

			// Verify line exists
			$term = get_term($line_id, 'product_line');
			if (is_wp_error($term) || !$term) {
				return new WP_Error(
					'line_not_found',
					__('Product line not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get line media using the product lines manager
			$media = $this->product_lines_manager->get_line_media($line_id);

			// Enhance media data with WordPress attachment info
			$enhanced_media = array();
			foreach ($media as $media_item) {
				if (isset($media_item['attachment_id']) && $media_item['attachment_id']) {
					$attachment_id = $media_item['attachment_id'];
					$attachment = get_post($attachment_id);

					if ($attachment) {
						$enhanced_item = array(
							'tag' => $media_item['tag'],
							'attachment_id' => $attachment_id,
							'url' => wp_get_attachment_url($attachment_id),
							'thumbnail_url' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
							'medium_url' => wp_get_attachment_image_url($attachment_id, 'medium'),
							'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
							'title' => $attachment->post_title,
							'mime_type' => $attachment->post_mime_type,
					);

						// Add size information if it's an image
						if (strpos($attachment->post_mime_type, 'image') === 0) {
							$metadata = wp_get_attachment_metadata($attachment_id);
							if ($metadata) {
								$enhanced_item['width'] = $metadata['width'] ?? 0;
								$enhanced_item['height'] = $metadata['height'] ?? 0;
								$enhanced_item['sizes'] = $metadata['sizes'] ?? array();
							}
						}

						$enhanced_media[] = $enhanced_item;
					}
				}
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $enhanced_media,
					'count'   => count($enhanced_media),
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches Ecwid: Error in get_product_line_media: ' . $e->getMessage());

			return new WP_Error(
				'media_fetch_error',
				__('Error fetching line media.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get all available line types.
	 *
	 * @since 0.3.1
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_line_types($request) {
		try {
			// Get all product lines
			$terms = get_terms(array(
				'taxonomy'   => 'product_line',
				'hide_empty' => false,
				'fields'     => 'ids',
			));

			if (is_wp_error($terms)) {
				return new WP_Error(
					'taxonomy_error',
					__('Error retrieving product lines.', 'peaches'),
					array('status' => 500)
				);
			}

			$line_types = array();
			foreach ($terms as $term_id) {
				$line_type = get_term_meta($term_id, 'line_type', true);
				if (!empty($line_type) && !in_array($line_type, $line_types)) {
					$line_types[] = $line_type;
				}
			}

			// Add common default types if they don't exist
			$default_types = array(
				'fragrance',
				'color_scheme',
				'design_collection',
				'seasonal',
				'limited_edition'
			);

			foreach ($default_types as $default_type) {
				if (!in_array($default_type, $line_types)) {
					$line_types[] = $default_type;
				}
			}

			// Sort alphabetically
			sort($line_types);

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $line_types,
					'count'   => count($line_types),
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting line types: ' . $e->getMessage());

			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get product ingredients with multilingual support.
	 *
	 * @since 0.2.5
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_product_ingredients($request) {
		try {
			$product_id = $request->get_param('product_id');

			if (!$product_id || !is_numeric($product_id)) {
				return new WP_Error(
					'invalid_product_id',
					__('Invalid product ID provided.', 'peaches'),
					array('status' => 400)
				);
			}

			// Verify product exists in Ecwid
			$product = $this->ecwid_api->get_product_by_id($product_id);
			if (!$product) {
				return new WP_Error(
					'product_not_found',
					__('Product not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get current language
			$current_language = $this->normalize_language_code($this->get_current_language_code());

			// Get product post ID from Ecwid product ID
			$post_id = $this->ecwid_api->get_product_post_id($product_id);
			if (!$post_id) {
				return new WP_REST_Response(
					array(
						'success'     => true,
						'product_id'  => (int) $product_id,
						'ingredients' => array(), // No product settings = no ingredients
						'count'       => 0,
						'language'    => $current_language,
					),
					200
				);
			}

			// Get product ingredients using the WordPress post ID (not Ecwid product ID)
			$ingredients = $this->product_settings_manager->get_product_ingredients($post_id);

			// Process ingredients for API response with multilingual support
			$processed_ingredients = array();
			foreach ($ingredients as $ingredient) {
				if (isset($ingredient['library_id'])) {
					$ingredient_post = get_post($ingredient['library_id']);
					if ($ingredient_post && $ingredient_post->post_type === 'product_ingredient') {

						// Get ingredient data with translation support
						$ingredient_data = $this->get_ingredient_with_translation($ingredient_post, $current_language);

						if ($ingredient_data) {
							$processed_ingredients[] = $ingredient_data;
						}
					}
				}
			}

			return new WP_REST_Response(
				array(
					'success'     => true,
					'product_id'  => (int) $product_id,
					'ingredients' => $processed_ingredients,
					'count'       => count($processed_ingredients),
					'language'    => $current_language,
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting product ingredients: ' . $e->getMessage());
			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get current language code with peaches-multilingual plugin support.
	 *
	 * @since 0.2.5
	 *
	 * @return string Current language code.
	 */
	private function get_current_language_code() {
		// Check for editor language override in request headers
		$editor_language = $this->get_editor_language_from_request();
		if (!empty($editor_language)) {
			return $editor_language;
		}

		// Check for peaches-multilingual integration classes
		if (class_exists('Peaches_Multilingual_Integration')) {
			$integration = Peaches_Multilingual_Integration::get_instance();
			if (method_exists($integration, 'get_current_language')) {
				return $integration->get_current_language();
			}
		}

		// Use our existing utility function as fallback
		return Peaches_Ecwid_Utilities::get_current_language();
	}

	/**
	 * Normalize language code to match ingredient storage format.
	 *
	 * Converts codes like 'nl_NL', 'en-US', 'fr_FR' to 'nl', 'en', 'fr'
	 * to match the format used in ingredient meta field names.
	 *
	 * @since 0.2.5
	 *
	 * @param string $language_code Raw language code.
	 *
	 * @return string Normalized language code (2 characters).
	 */
	private function normalize_language_code($language_code) {
		if (empty($language_code)) {
			return 'en';
		}

		// Convert to lowercase and extract just the language part
		$language_code = strtolower($language_code);

		// Handle formats like 'nl_NL', 'en-US', 'fr-FR'
		if (strpos($language_code, '_') !== false) {
			$parts = explode('_', $language_code);
			return $parts[0];
		}

		if (strpos($language_code, '-') !== false) {
			$parts = explode('-', $language_code);
			return $parts[0];
		}

		// Already normalized or simple format
		return $language_code;
	}

	/**
	 * Get editor language from request headers or parameters.
	 *
	 * This allows the block editor to specify which language it's editing,
	 * which may be different from the frontend language.
	 *
	 * @since 0.2.5
	 *
	 * @return string|null Editor language code or null.
	 */
	private function get_editor_language_from_request() {
		// Check for editor language header (from block editor)
		$headers = getallheaders();
		if (is_array($headers)) {
			foreach ($headers as $key => $value) {
				if (strtolower($key) === 'x-peaches-language') {
					$lang = sanitize_text_field($value);
					if (preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $lang)) {
						return $lang;
					}
				}
			}
		}

		// Check for standard lang parameter
		if (isset($_GET['lang']) && !empty($_GET['lang'])) {
			$lang = sanitize_text_field($_GET['lang']);
			if (preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $lang)) {
				return $lang;
			}
		}

		return null;
	}

	/**
	 * Get ingredient data with translation support.
	 *
	 * @since 0.2.5
	 *
	 * @param WP_Post $ingredient_post Ingredient post object.
	 * @param string  $language        Target language code.
	 *
	 * @return array|null Ingredient data with translation or null if invalid.
	 */
	private function get_ingredient_with_translation($ingredient_post, $language) {
		if (!$ingredient_post || $ingredient_post->post_type !== 'product_ingredient') {
			return null;
		}

		// Start with default (English) values
		$name = $ingredient_post->post_title;
		$description = get_post_meta($ingredient_post->ID, '_ingredient_description', true);

		// Get translations if language is not English
		if (!empty($language) && $language !== 'en') {
			$translated_name = get_post_meta($ingredient_post->ID, '_ingredient_name_' . $language, true);
			$translated_description = get_post_meta($ingredient_post->ID, '_ingredient_description_' . $language, true);

			// Use translated values if available
			if (!empty($translated_name)) {
				$name = $translated_name;
			}

			if (!empty($translated_description)) {
				$description = $translated_description;
			}
		}

		return array(
			'id'          => $ingredient_post->ID,
			'name'        => $name,
			'description' => $description,
			'language'    => $language,
			'has_translation' => (!empty($language) && $language !== 'en' &&
			                     (!empty(get_post_meta($ingredient_post->ID, '_ingredient_name_' . $language, true)) ||
			                      !empty(get_post_meta($ingredient_post->ID, '_ingredient_description_' . $language, true)))),
		);
	}

	/**
	 * Get product descriptions.
	 *
	 * @since 0.2.5
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_product_descriptions($request) {
		try {
			$product_id = $request->get_param('product_id');
			$type_filter = $request->get_param('type');

			if (!$product_id || !is_numeric($product_id)) {
				return new WP_Error(
					'invalid_product_id',
					__('Invalid product ID provided.', 'peaches'),
					array('status' => 400)
				);
			}

			// Verify product exists in Ecwid
			$product = $this->ecwid_api->get_product_by_id($product_id);
			if (!$product) {
				return new WP_Error(
					'product_not_found',
					__('Product not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get product descriptions
			$descriptions = $this->product_settings_manager->get_product_descriptions($product_id);

			// Filter by type if specified
			if (!empty($type_filter)) {
				$descriptions = array_filter($descriptions, function($description) use ($type_filter) {
					return isset($description['type']) && $description['type'] === $type_filter;
				});
				// Reset array keys after filtering
				$descriptions = array_values($descriptions);
			}

			return new WP_REST_Response(
				array(
					'success'      => true,
					'product_id'   => (int) $product_id,
					'descriptions' => $descriptions,
					'count'        => count($descriptions),
					'filter'       => $type_filter,
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting product descriptions: ' . $e->getMessage());
			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get product description by type.
	 *
	 * @since 0.2.5
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_product_description_by_type($request) {
		try {
			$product_id = $request->get_param('product_id');
			$type = $request->get_param('type');

			if (!$product_id || !is_numeric($product_id)) {
				return new WP_Error(
					'invalid_product_id',
					__('Invalid product ID provided.', 'peaches'),
					array('status' => 400)
				);
			}

			if (empty($type)) {
				return new WP_Error(
					'invalid_type',
					__('Description type is required.', 'peaches'),
					array('status' => 400)
				);
			}

			// Verify product exists in Ecwid
			$product = $this->ecwid_api->get_product_by_id($product_id);
			if (!$product) {
				return new WP_Error(
					'product_not_found',
					__('Product not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get specific description by type
			$description = $this->product_settings_manager->get_product_description_by_type($product_id, $type);

			if (!$description) {
				return new WP_Error(
					'description_not_found',
					sprintf(__('Description of type "%s" not found for this product.', 'peaches'), $type),
					array('status' => 404)
				);
			}

			return new WP_REST_Response(
				array(
					'success'     => true,
					'product_id'  => (int) $product_id,
					'type'        => $type,
					'description' => $description,
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting product description by type: ' . $e->getMessage());
			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get product media by tag with full processing.
	 *
	 * @since 0.2.5
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_product_media_by_tag($request) {
		try {
			$product_id = $request->get_param('product_id');
			$tag_key = $request->get_param('tag_key');

			// Validate parameters
			if (empty($product_id) || !is_numeric($product_id)) {
				return new WP_Error(
					'invalid_product_id',
					__('Invalid product ID provided.', 'peaches'),
					array('status' => 400)
				);
			}

			if (empty($tag_key)) {
				return new WP_Error(
					'invalid_tag_key',
					__('Media tag key is required.', 'peaches'),
					array('status' => 400)
				);
			}

			// Check if tag exists
			if (!$this->media_tags_manager->tag_exists($tag_key)) {
				return new WP_Error(
					'tag_not_found',
					sprintf(__('Media tag "%s" does not exist.', 'peaches'), $tag_key),
					array('status' => 404)
				);
			}

			// Verify product exists in Ecwid
			$product = $this->ecwid_api->get_product_by_id($product_id);
			if (!$product) {
				return new WP_Error(
					'product_not_found',
					__('Product not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get product post ID
			$post_id = $this->ecwid_api->get_product_post_id($product_id);
			if (!$post_id) {
				return new WP_Error(
					'no_product_settings',
					__('No product settings found for this product.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get raw media data by tag
			$raw_media_data = $this->product_media_manager->get_product_media_by_tag($post_id, $tag_key);

			if (!$raw_media_data) {
				return new WP_Error(
					'media_not_found',
					sprintf(__('No media found for tag "%s" on this product.', 'peaches'), $tag_key),
					array('status' => 404)
				);
			}

			// Process the raw media data into full response format
			$processed_media = $this->process_media_data($raw_media_data, $post_id, $product);

			if (!$processed_media) {
				return new WP_Error(
					'media_processing_failed',
					__('Failed to process media data.', 'peaches'),
					array('status' => 500)
				);
			}

			return new WP_REST_Response(
				array(
					'success'    => true,
					'product_id' => (int) $product_id,
					'tag_key'    => $tag_key,
					'data'       => $processed_media,
					'fallback'   => false,
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting product media by tag: ' . $e->getMessage());
			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Process raw media data into API response format.
	 *
	 * @since 0.2.5
	 *
	 * @param array  $media_data Raw media data from product media manager.
	 * @param int    $post_id    Product settings post ID.
	 * @param object $product    Ecwid product object.
	 *
	 * @return array|null Processed media data or null if processing failed.
	 */
	private function process_media_data($media_data, $post_id, $product) {
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
				if (isset($media_data['ecwid_position'])) {
					return $this->format_ecwid_media_response($media_data['ecwid_position'], $product);
				}
				break;
		}

		return null;
	}

	/**
	 * Format WordPress media attachment response.
	 *
	 * @since 0.2.5
	 *
	 * @param int $attachment_id WordPress attachment ID.
	 *
	 * @return array|null Formatted response or null if invalid.
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
			'url'           => wp_get_attachment_url($attachment_id),
			'title'         => $attachment->post_title,
			'alt'           => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
			'caption'       => $attachment->post_excerpt,
			'description'   => $attachment->post_content,
			'mime_type'     => $mime_type,
			'type'          => $media_type,
			'sizes'         => $this->get_attachment_sizes($attachment_id),
			'source'        => 'wordpress'
		);
	}

	/**
	 * Format external URL media response.
	 *
	 * @since 0.2.5
	 *
	 * @param string $media_url External media URL.
	 *
	 * @return array Formatted response.
	 */
	private function format_url_media_response($media_url) {
		$media_type = $this->determine_media_type_from_url($media_url);

		return array(
			'url'         => $media_url,
			'title'       => basename(parse_url($media_url, PHP_URL_PATH)),
			'alt'         => '',
			'caption'     => '',
			'description' => '',
			'mime_type'   => $this->guess_mime_type_from_url($media_url),
			'type'        => $media_type,
			'sizes'       => array(
				'full' => array(
					'url'    => $media_url,
					'width'  => 0,
					'height' => 0,
					'type'   => $media_type
				)
			),
			'source'      => 'external'
		);
	}

	/**
	 * Format Ecwid media response.
	 *
	 * @since 0.2.5
	 *
	 * @param int    $ecwid_position Ecwid image position.
	 * @param object $product        Ecwid product object.
	 *
	 * @return array|null Formatted response or null if invalid.
	 */
	private function format_ecwid_media_response($ecwid_position, $product) {
		// Get image URL by position
		$image_url = $this->get_ecwid_image_by_position($product, $ecwid_position);

		if (!$image_url) {
			return null;
		}

		$media_type = $this->determine_media_type_from_url($image_url);

		return array(
			'url'            => $image_url,
			'title'          => $product->name . ' - Image ' . ($ecwid_position + 1),
			'alt'            => $product->name,
			'caption'        => '',
			'description'    => '',
			'mime_type'      => $this->guess_mime_type_from_url($image_url),
			'type'           => $media_type,
			'sizes'          => array(
				'full' => array(
					'url'    => $image_url,
					'width'  => 0,
					'height' => 0,
					'type'   => $media_type
				)
			),
			'source'         => 'ecwid',
			'ecwid_position' => $ecwid_position
		);
	}

	/**
	 * Get Ecwid image URL by position.
	 *
	 * @since 0.2.5
	 *
	 * @param object $product  Ecwid product object.
	 * @param int    $position Image position.
	 *
	 * @return string|null Image URL or null.
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
	 * Determine media type from URL and optional mime type.
	 *
	 * @since 0.2.5
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
	 * Guess mime type from URL.
	 *
	 * @since 0.2.5
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
	 * @since 0.2.5
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
	 * Get all media tags
	 *
	 * @since 0.2.5
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_media_tags($request) {
		try {
			$category_filter = $request->get_param('category');

			// Get all tags from media tags manager
			if ($category_filter) {
				$tags = $this->media_tags_manager->get_tags_by_category($category_filter);
			} else {
				$tags = $this->media_tags_manager->get_all_tags();
			}

			// Format tags for API response
			$formatted_tags = array();
			foreach ($tags as $tag_key => $tag_data) {
				$formatted_tags[] = array(
					'key'               => $tag_key,
					'label'             => $tag_data['name'] ?? $tag_data['label'] ?? $tag_key,
					'name'              => $tag_data['name'] ?? $tag_data['label'] ?? $tag_key,
					'description'       => $tag_data['description'] ?? '',
					'category'          => $tag_data['category'] ?? 'other',
					'expectedMediaType' => $tag_data['expectedMediaType'] ?? 'image',
					'required'          => $tag_data['required'] ?? false,
				);
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $formatted_tags,
					'count'   => count($formatted_tags),
					'filter'  => $category_filter,
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting media tags: ' . $e->getMessage());
			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get available description types.
	 *
	 * @since 0.2.5
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function get_description_types($request) {
		try {
			$description_types = $this->product_settings_manager->get_description_types();

			return new WP_REST_Response(
				array(
					'success' => true,
					'types'   => $description_types,
					'count'   => count($description_types),
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting description types: ' . $e->getMessage());
			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get related products for a specific product.
	 *
	 * @since 0.2.6
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_related_products($request) {
		try {
			$product_id = $request->get_param('product_id');

			if (!$product_id || !is_numeric($product_id)) {
				return new WP_Error(
					'invalid_product_id',
					__('Invalid product ID provided.', 'peaches'),
					array('status' => 400)
				);
			}

			// Get the main product
			$product = $this->ecwid_api->get_product_by_id($product_id);

			if (!$product) {
				return new WP_Error(
					'product_not_found',
					__('Product not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get related product IDs
			$related_ids = $this->ecwid_api->get_related_product_ids($product);

			if (empty($related_ids)) {
				return new WP_Error(
					'no_related_products',
					__('No related products found.', 'peaches'),
					array('status' => 404)
				);
			}

			return new WP_REST_Response(
				array(
					'success'        => true,
					'product_id'     => (int) $product_id,
					'related_products' => $related_ids,
					'count'          => count($related_ids),
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting related products: ' . $e->getMessage());

			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get product data for a specific product.
	 *
	 * @since 0.2.6
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_product_data($request) {
		try {
			$product_id = $request->get_param('product_id');

			if (!$product_id || !is_numeric($product_id)) {
				return new WP_Error(
					'invalid_product_id',
					__('Invalid product ID provided.', 'peaches'),
					array('status' => 400)
				);
			}

			// Get language from request if available, otherwise use current language
			$lang = $request->get_param('lang') ? sanitize_text_field($request->get_param('lang')) : '';

			// If no language specified in request, get current language
			if (empty($lang)) {
				$lang = Peaches_Ecwid_Utilities::get_current_language();
			}

			// Set the language for the current request if provided and different from current
			if (!empty($lang)) {
				// Polylang support
				if (function_exists('pll_set_language')) {
					pll_set_language($lang);
				}
				// WPML support
				elseif (defined('ICL_LANGUAGE_CODE') && class_exists('SitePress')) {
					global $sitepress;
					if ($sitepress) {
						$sitepress->switch_lang($lang);
					}
				}
			}

			// Get the product data
			$product = $this->ecwid_api->get_product_by_id($product_id);

			if (!$product) {
				return new WP_Error(
					'product_not_found',
					__('Product not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Convert the entire product object to array for JSON response
			$product_data = (array) $product;

			// Generate product URL
			$product_url = $this->product_manager->build_product_url($product, $lang);

			// Add our custom URL field
			$product_data['url'] = $product_url;
			if (!isset($product_data['description'])) {
				$product_data['description'] = '';
			}
			if (!isset($product_data['galleryImages'])) {
				$product_data['galleryImages'] = array();
			}
			if (!isset($product_data['media'])) {
				$product_data['media'] = null;
			}
			if (!isset($product_data['inStock'])) {
				$product_data['inStock'] = true; // Default assumption
			}
			if (!isset($product_data['compareToPrice'])) {
				$product_data['compareToPrice'] = null;
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $product_data,
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting product data: ' . $e->getMessage());

			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get categories from Ecwid store.
	 *
	 * @since 0.2.6
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_categories($request) {
		try {
			$parent = $request->get_param('parent');
			$enabled_only = $request->get_param('enabled');

			// Prepare options for API call
			$options = array();

			if (!is_null($parent)) {
				$options['parent'] = $parent;
			}

			if ($enabled_only) {
				$options['enabled'] = true;
			}

			// Get categories from API
			$categories = $this->ecwid_api->get_categories($options);

			return new WP_REST_Response(
				array(
					'success'    => true,
					'data'       => $categories,
					'count'      => count($categories),
					'options'    => $options,
				),
				200
			);

		} catch (Exception $e) {
			error_log('Peaches API: Error getting categories: ' . $e->getMessage());

			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Check permissions for public endpoints.
	 *
	 * @since 0.2.5
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool True if access is allowed.
	 */
	public function check_public_permissions($request) {
		// These endpoints are public for frontend blocks
		return true;
	}

	/**
	 * Sanitize product ID parameter.
	 *
	 * @since 0.2.5
	 *
	 * @param mixed $value   Parameter value.
	 * @param mixed $request Request object.
	 * @param mixed $param   Parameter key.
	 *
	 * @return int Sanitized product ID.
	 */
	public function sanitize_product_id($value, $request, $param) {
		return absint($value);
	}

	/**
	 * Validate product ID parameter.
	 *
	 * @since 0.2.5
	 *
	 * @param mixed $value   Parameter value.
	 * @param mixed $request Request object.
	 * @param mixed $param   Parameter key.
	 *
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_product_id($value, $request, $param) {
		if (!is_numeric($value) || $value < 1) {
			return new WP_Error(
				'rest_invalid_param',
				sprintf(__('%s must be a positive integer.', 'peaches'), $param),
				array('status' => 400)
			);
		}

		return true;
	}
}
