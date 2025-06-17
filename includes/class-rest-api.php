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
	 * Ecwid API instance.
	 *
	 * @since 0.2.6
	 * @var Peaches_Ecwid_API
	 */
	private $ecwid_api;

	/**
	 * Constructor.
	 *
	 * @since 0.2.6
	 *
	 * @param Peaches_Product_Settings_Manager $product_settings_manager Product Settings Manager instance.
	 * @param Peaches_Media_Tags_Manager       $media_tags_manager       Media Tags Manager instance.
	 * @param Peaches_Ecwid_API                $ecwid_api                Ecwid API instance.
	 */
	public function __construct($product_settings_manager, $media_tags_manager, $ecwid_api = null) {
		$this->product_settings_manager = $product_settings_manager;
		$this->media_tags_manager = $media_tags_manager;
		$this->ecwid_api = $ecwid_api;

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
				),
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
	 * Get product ingredients.
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

			// Get Ecwid API instance to check if product exists
			$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
			$ecwid_api = $ecwid_blocks->get_ecwid_api();

			// Verify product exists in Ecwid
			$product = $ecwid_api->get_product_by_id($product_id);
			if (!$product) {
				return new WP_Error(
					'product_not_found',
					__('Product not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get product ingredients
			$ingredients = $this->product_settings_manager->get_product_ingredients($product_id);

			// Process ingredients for API response
			$processed_ingredients = array();
			foreach ($ingredients as $ingredient) {
				if (isset($ingredient['library_id'])) {
					$ingredient_post = get_post($ingredient['library_id']);
					if ($ingredient_post && $ingredient_post->post_type === 'product_ingredient') {
						$processed_ingredients[] = array(
							'id'          => $ingredient['library_id'],
							'name'        => $ingredient_post->post_title,
							'description' => get_post_meta($ingredient_post->ID, '_ingredient_description', true),
						);
					}
				}
			}

			return new WP_REST_Response(
				array(
					'success'     => true,
					'product_id'  => (int) $product_id,
					'ingredients' => $processed_ingredients,
					'count'       => count($processed_ingredients),
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

			// Get Ecwid API instance to check if product exists
			$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
			$ecwid_api = $ecwid_blocks->get_ecwid_api();

			// Verify product exists in Ecwid
			$product = $ecwid_api->get_product_by_id($product_id);
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

			// Get Ecwid API instance to check if product exists
			$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
			$ecwid_api = $ecwid_blocks->get_ecwid_api();

			// Verify product exists in Ecwid
			$product = $ecwid_api->get_product_by_id($product_id);
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
	 * Get product media by tag (consolidating existing functionality).
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

			// Validate inputs
			if (!$product_id || !is_numeric($product_id)) {
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

			// Get Ecwid API instance to check if product exists
			$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
			$ecwid_api = $ecwid_blocks->get_ecwid_api();

			// Verify product exists in Ecwid
			$product = $ecwid_api->get_product_by_id($product_id);
			if (!$product) {
				return new WP_Error(
					'product_not_found',
					__('Product not found.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get product post ID
			$post_id = $ecwid_api->get_product_post_id($product_id);
			if (!$post_id) {
				return new WP_Error(
					'no_product_settings',
					__('No product settings found for this product.', 'peaches'),
					array('status' => 404)
				);
			}

			// Get product media manager
			$product_media_manager = $ecwid_blocks->get_product_media_manager();
			if (!$product_media_manager) {
				return new WP_Error(
					'media_manager_unavailable',
					__('Product media manager is not available.', 'peaches'),
					array('status' => 503)
				);
			}

			// Get media by tag
			$media_data = $product_media_manager->get_product_media_by_tag($post_id, $tag_key);

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
	 * Get all media tags (consolidating existing functionality).
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
					'key'                => $tag_key,
					'label'              => $tag_data['name'] ?? $tag_key, // Use 'label' instead of 'name' for block compatibility
					'name'               => $tag_data['name'] ?? $tag_key,
					'description'        => $tag_data['description'] ?? '',
					'category'           => $tag_data['category'] ?? 'other',
					'expectedMediaType'  => $tag_data['expected_type'] ?? 'image', // Primary field for block compatibility
					'expected_media_type'=> $tag_data['expected_type'] ?? 'image', // Fallback field
					'expected_type'      => $tag_data['expected_type'] ?? 'image', // Keep original
					'mediaType'          => $tag_data['expected_type'] ?? 'image', // Another fallback
					'required'           => $tag_data['required'] ?? false,
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

			// Get Ecwid API instance if not injected
			if (!$this->ecwid_api) {
				$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
				$this->ecwid_api = $ecwid_blocks->get_ecwid_api();
			}

			if (!$this->ecwid_api) {
				return new WP_Error(
					'api_unavailable',
					__('Ecwid API is not available.', 'peaches'),
					array('status' => 500)
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

			// Get Ecwid API instance if not injected
			if (!$this->ecwid_api) {
				$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
				$this->ecwid_api = $ecwid_blocks->get_ecwid_api();
			}

			if (!$this->ecwid_api) {
				return new WP_Error(
					'api_unavailable',
					__('Ecwid API is not available.', 'peaches'),
					array('status' => 500)
				);
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

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $product,
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

			// Get Ecwid API instance if not injected
			if (!$this->ecwid_api) {
				$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
				$this->ecwid_api = $ecwid_blocks->get_ecwid_api();
			}

			if (!$this->ecwid_api) {
				return new WP_Error(
					'api_unavailable',
					__('Ecwid API is not available.', 'peaches'),
					array('status' => 500)
				);
			}

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
