<?php
/**
 * REST API Handler class
 *
 * Consolidates all REST API endpoints for the Peaches Ecwid Blocks plugin.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.5
 */

if ( ! defined( 'ABSPATH' ) ) {
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
	 * @param Peaches_Product_Settings_Manager  $product_settings_manager  Product settings manager
	 * @param Peaches_Media_Tags_Manager        $media_tags_manager        Media tags manager
	 * @param Peaches_Product_Media_Manager     $product_media_manager     Product media manager
	 * @param Peaches_Ecwid_API                 $ecwid_api                 Ecwid API
	 * @param Peaches_Product_Manager           $product_manager           Product manager
	 * @param Peaches_Product_Lines_Manager     $product_lines_manager     Product lines manager
	 */
	public function __construct(
		$product_settings_manager,
		$media_tags_manager,
		$product_media_manager,
		$ecwid_api,
		$product_manager,
		$product_lines_manager
	) {
		$this->product_settings_manager = $product_settings_manager;
		$this->media_tags_manager = $media_tags_manager;
		$this->product_media_manager = $product_media_manager;
		$this->ecwid_api = $ecwid_api;
		$this->product_manager = $product_manager;
		$this->product_lines_manager = $product_lines_manager;

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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'rest_authentication_errors', array( $this, 'allow_public_endpoints' ) );
	}

	/**
	 * Allow public access to our specific endpoints even if REST API is restricted globally.
	 *
	 * @since 0.2.5
	 *
	 * @param WP_Error|null|true $result Error from another authentication handler, null if we should handle it, or true if no problems.
	 *
	 * @return WP_Error|null|true Modified result.
	 */
	public function allow_public_endpoints( $result ) {
		// If there's already an error and it's not a 401 authentication error, pass it through.
		if ( is_wp_error( $result ) && 'rest_forbidden' !== $result->get_error_code() ) {
			return $result;
		}

		// Get the current request.
		$current_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';

		// Check if this is one of our public endpoints.
		if ( 0 === strpos( $current_route, '/' . self::NAMESPACE . '/' ) ) {
			// Allow access to our endpoints.
			return true;
		}

		// For all other endpoints, maintain the existing restriction.
		return $result;
	}

	/**
	 * Register all REST API routes.
	 *
	 * @since 0.2.5
	 *
	 * @return void
	 */
	public function register_routes() {
		$this->log_info('Starting to register REST API routes');

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

		// Product lines endpoint
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

		// All line types endpoint
		register_rest_route(
			self::NAMESPACE,
			'/line-types',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_line_types'),
				'permission_callback' => array($this, 'check_public_permissions'),
			)
		);

		// Product descriptions endpoint
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

		// Category products endpoint (generic - includes featured products when category_id = 0)
		register_rest_route(
			self::NAMESPACE,
			'/category-products/(?P<category_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_category_products'),
				'permission_callback' => array($this, 'check_public_permissions'),
				'args'                => array(
					'category_id' => array(
						'description' => __('Category ID. Use 0 for featured products (Store Front Page).', 'peaches'),
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 0,
					),
					'limit'       => array(
						'description' => __('Number of products to return.', 'peaches'),
						'type'        => 'integer',
						'required'    => false,
						'default'     => 20,
						'minimum'     => 1,
						'maximum'     => 100,
					),
					'offset'      => array(
						'description' => __('Number of products to skip for pagination.', 'peaches'),
						'type'        => 'integer',
						'required'    => false,
						'default'     => 0,
						'minimum'     => 0,
					),
					'sort_by'     => array(
						'description' => __('Sort products by field.', 'peaches'),
						'type'        => 'string',
						'required'    => false,
						'default'     => 'name',
						'enum'        => array('name', 'price', 'created', 'updated'),
					),
					'sort_order'  => array(
						'description' => __('Sort order.', 'peaches'),
						'type'        => 'string',
						'required'    => false,
						'default'     => 'asc',
						'enum'        => array('asc', 'desc'),
					),
					'enabled'     => array(
						'description' => __('Filter by enabled status.', 'peaches'),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => true,
					),
					'in_stock'    => array(
						'description' => __('Filter by stock status.', 'peaches'),
						'type'        => 'boolean',
						'required'    => false,
					),
					'return_ids_only' => array(
						'description' => __('Return only product IDs instead of full product data.', 'peaches'),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => false,
					),
				),
			)
		);

		$this->log_info('Completed registering all REST API routes');
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
			$this->log_error('Error getting product lines', array('error' => $e->getMessage()));

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
			$this->log_error('Error in get_product_line_media', array('error' => $e->getMessage()));

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
			$this->log_error('Error getting line types', array('error' => $e->getMessage()));

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
			$this->log_error('Error getting product ingredients', array('error' => $e->getMessage()));
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

			// Get current language for translations
			$current_language = Peaches_Ecwid_Utilities::get_current_language();

			// Get product descriptions with translations
			$descriptions = $this->product_settings_manager->get_product_descriptions_with_translations($product_id, $current_language);

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
			$this->log_error('Error getting product descriptions', array('error' => $e->getMessage()));
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
			// Get current language for translations
			$current_language = Peaches_Ecwid_Utilities::get_current_language();

			$description = $this->product_settings_manager->get_product_description_by_type($product_id, $type, $current_language);

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
			$this->log_error('Error getting product description by type', array('error' => $e->getMessage()));
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

			// Use the unified method from product media manager
			$media_data = $this->product_media_manager->get_product_media_data($product_id, $tag_key, 'large', false);

			if (!$media_data) {
				return new WP_Error(
					'media_not_found',
					sprintf(__('No media found for tag "%s" on this product.', 'peaches'), $tag_key),
					array('status' => 404)
				);
			}

			return new WP_REST_Response(
				array(
					'success'    => true,
					'product_id' => (int) $product_id,
					'tag_key'    => $tag_key,
					'data'       => $media_data,
					'fallback'   => isset($media_data['is_fallback']) ? $media_data['is_fallback'] : false,
				),
				200
			);

		} catch (Exception $e) {
			$this->log_error('Error getting product media by tag', array('error' => $e->getMessage()));
			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
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
			$this->log_error('Error getting media tags', array('error' => $e->getMessage()));
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
			$this->log_error('Error getting description types', array('error' => $e->getMessage()));
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
			$this->log_error('Error getting related products', array('error' => $e->getMessage()));

			return new WP_Error(
				'server_error',
				__('Internal server error.', 'peaches'),
				array('status' => 500)
			);
		}
	}

	/**
	 * Get products from a specific category.
	 *
	 * @since 0.3.4
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_category_products($request) {
		$this->log_info('get_category_products method called');

		try {
			$category_id      = $request->get_param('category_id');
			$limit            = $request->get_param('limit');
			$offset           = $request->get_param('offset');
			$sort_by          = $request->get_param('sort_by');
			$sort_order       = $request->get_param('sort_order');
			$enabled          = $request->get_param('enabled');
			$in_stock         = $request->get_param('in_stock');
			$return_ids_only  = $request->get_param('return_ids_only');

			$this->log_info('Request parameters', array(
				'category_id' => $category_id,
				'limit' => $limit,
				'offset' => $offset,
				'sort_by' => $sort_by,
				'sort_order' => $sort_order,
				'enabled' => $enabled,
				'in_stock' => $in_stock,
				'return_ids_only' => $return_ids_only
			));

			// Validate category_id
			if (!is_numeric($category_id) || $category_id < 0) {
				$this->log_error('Invalid category_id provided', array('category_id' => $category_id));
				return new WP_Error(
					'invalid_category_id',
					__('Invalid category ID provided.', 'peaches'),
					array('status' => 400)
				);
			}

			// Build search options for Ecwid API
			$search_options = array(
				'category'  => (int) $category_id,
				'limit'     => $limit,
				'offset'    => $offset,
				'enabled'   => $enabled,
			);

			// Convert sort parameters to Ecwid format
			if ($sort_by && $sort_order) {
				$sort_by_upper = strtoupper($sort_by);
				$sort_order_upper = strtoupper($sort_order);

				switch ($sort_by_upper) {
					case 'NAME':
						$search_options['sortBy'] = ($sort_order_upper === 'DESC') ? 'NAME_DESC' : 'NAME_ASC';
						break;
					case 'PRICE':
						$search_options['sortBy'] = ($sort_order_upper === 'DESC') ? 'PRICE_DESC' : 'PRICE_ASC';
						break;
					case 'CREATED':
						$search_options['sortBy'] = ($sort_order_upper === 'DESC') ? 'ADDED_TIME_DESC' : 'ADDED_TIME_ASC';
						break;
					case 'UPDATED':
						$search_options['sortBy'] = ($sort_order_upper === 'DESC') ? 'UPDATED_TIME_DESC' : 'UPDATED_TIME_ASC';
						break;
					default:
						// Don't add sortBy parameter, let Ecwid use default
						break;
				}
			}

			// Add stock filter if specified
			if ($in_stock !== null) {
				$search_options['inStock'] = $in_stock;
			}

			$this->log_info('Search options for Ecwid API', $search_options);

			// Check if ecwid_api exists
			if (!$this->ecwid_api) {
				$this->log_error('ecwid_api instance is null');
				return new WP_Error(
					'api_not_available',
					__('Ecwid API not available.', 'peaches'),
					array('status' => 500)
				);
			}

			// Check if search_products method exists
			if (!method_exists($this->ecwid_api, 'search_products')) {
				$this->log_error('search_products method does not exist on ecwid_api');
				return new WP_Error(
					'method_not_available',
					__('Search products method not available.', 'peaches'),
					array('status' => 500)
				);
			}

			// Get products from the specified category
			$products = $this->ecwid_api->search_products('', $search_options);

			$this->log_info('search_products returned', array(
				'type' => gettype($products),
				'count' => is_array($products) ? count($products) : 'N/A',
				'is_empty' => empty($products),
				'first_few_products' => is_array($products) ? array_slice($products, 0, 3) : 'Not array'
			));

			if (empty($products)) {
				$category_name = $category_id === 0 ? 'Store Front Page (Featured Products)' : 'Category ' . $category_id;

				$this->log_info('No products found in category', array('category_name' => $category_name));

				return new WP_Error(
					'no_products_found',
					sprintf(__('No products found in %s.', 'peaches'), $category_name),
					array('status' => 404)
				);
			}

			// Prepare response data
			$response_data = array(
				'success'     => true,
				'category_id' => (int) $category_id,
				'count'       => count($products),
				'limit'       => $limit,
				'offset'      => $offset,
				'sort_by'     => $sort_by,
				'sort_order'  => $sort_order,
			);

			// Add category context
			if ($category_id === 0) {
				$response_data['category_type'] = 'featured';
				$response_data['category_name'] = 'Store Front Page';
				$response_data['description'] = 'Featured products displayed on the store\'s front page';
			} else {
				$response_data['category_type'] = 'standard';
				$response_data['category_name'] = 'Category ' . $category_id;
			}

			// Return either product IDs or full product data
			if ($return_ids_only) {
				$product_ids = array();
				foreach ($products as $product) {
					if (isset($product['id'])) {
						$product_ids[] = (int) $product['id'];
					}
				}
				$response_data['product_ids'] = $product_ids;
				$this->log_info('Returning product IDs only', array('ids' => $product_ids));
			} else {
				$response_data['products'] = $products;
				$this->log_info('Returning full product data', array('count' => count($products)));
			}

			$this->log_info('Successful response prepared', array(
				'category_id' => $response_data['category_id'],
				'count' => $response_data['count'],
				'category_type' => $response_data['category_type']
			));

			return new WP_REST_Response($response_data, 200);

		} catch (Exception $e) {
			$this->log_error('Exception in get_category_products', array(
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			));

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
			$this->log_error('Error getting product data', array('error' => $e->getMessage()));

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
			$this->log_error('Error getting categories', array('error' => $e->getMessage()));

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

	/**
	 * Log informational messages.
	 *
	 * @since 0.3.4
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_info( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) && Peaches_Ecwid_Utilities::is_debug_mode() ) {
			Peaches_Ecwid_Utilities::log_error( '[INFO] [REST API] ' . $message, $context );
		}
	}

	/**
	 * Log error messages.
	 *
	 * @since 0.3.4
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_error($message, $context = array()) {
		if (class_exists('Peaches_Ecwid_Utilities')) {
			Peaches_Ecwid_Utilities::log_error('[REST API] ' . $message, $context);
		} else {
			// Fallback logging if utilities class is not available
			error_log('[Peaches Ecwid] [REST API] ' . $message . (empty($context) ? '' : ' - Context: ' . wp_json_encode($context)));
		}
	}
}
