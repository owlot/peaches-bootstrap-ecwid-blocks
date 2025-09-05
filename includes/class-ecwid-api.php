<?php
/**
 * Ecwid API class
 *
 * Handles interaction with the Ecwid API with caching (Redis/Transients) and debug support.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

// Include the interface
require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-ecwid-api.php';

/**
 * Class Peaches_Ecwid_API
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
class Peaches_Ecwid_API implements Peaches_Ecwid_API_Interface {
	/**
	 * Cache group for Ecwid data
	 *
	 * @since 0.1.2
	 * @var string
	 */
	const CACHE_GROUP = 'peaches_ecwid';

	/**
	 * Shared Cache service instance
	 *
	 * @since 0.2.0
	 * @var Peaches_Cache_Manager|null
	 */
	private $cache_service = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Delay cache service initialization to ensure Bootstrap Blocks is ready
		add_action('init', array($this, 'delayed_init_cache_service'), 15);
	}

	/**
	 * Delayed initialization of cache service (called on 'init' hook)
	 *
	 * @since 0.6.1
	 *
	 * @return void
	 */
	public function delayed_init_cache_service() {
		$this->init_cache_service();
	}

	/**
	 * Initialize Redis service from shared Peaches Bootstrap Blocks
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function init_cache_service() {
		// Check if Peaches Bootstrap Blocks is active and get the shared Cache service
		if ( ! class_exists( 'Peaches_Bootstrap_Blocks' ) ) {
			$this->log_info('Peaches Bootstrap Blocks not available - using WordPress transients');
			return;
		}

		$bootstrap_blocks = Peaches_Bootstrap_Blocks::get_instance();
		if ( ! $bootstrap_blocks ) {
			$this->log_info('Failed to get Peaches Bootstrap Blocks instance');
			return;
		}

		$this->cache_service = $bootstrap_blocks->get_cache_manager();
		if ( $this->cache_service ) {
			$this->log_info('Shared Cache service initialized from Peaches Bootstrap Blocks');
		} else {
			$this->log_info('Shared Cache service not available - using WordPress transients');
		}
	}

	/**
	 * Ensure cache service is available (lazy initialization)
	 *
	 * @since 0.6.1
	 *
	 * @return void
	 */
	private function ensure_cache_service_available() {
		if ( ! $this->cache_service ) {
			$this->init_cache_service();
		}
	}

	/**
	 * Get plugin settings
	 *
	 * @since 0.1.2
	 *
	 * @return array Plugin settings
	 */
	private function get_settings() {
		if (class_exists('Peaches_Ecwid_Settings')) {
			$settings_manager = Peaches_Ecwid_Settings::get_instance();
			return $settings_manager->get_settings();
		}

		// Fallback defaults
		return array(
			'cache_duration' => 60,
			'debug_mode' => false,
			'enable_redis' => false,
		);
	}

	/**
	 * Get cache key for API data
	 *
	 * @since 0.1.2
	 *
	 * @param string $type     Cache type (product, category, etc.)
	 * @param mixed  $identifier Unique identifier for the data
	 *
	 * @return string Cache key
	 */
	private function get_cache_key($type, $identifier) {
		return $type . '_' . md5(serialize($identifier));
	}

	/**
	 * Get cached data
	 *
	 * @since 0.1.2
	 *
	 * @param string $cache_key Cache key
	 *
	 * @return mixed|false Cached data or false if not found
	 */
	private function get_cached_data($cache_key) {
		$settings = $this->get_settings();


		// Ensure cache service is available (lazy init if needed)
		$this->ensure_cache_service_available();

		// Only use shared Cache service if enabled in local settings and service is available
		if ($settings['enable_redis'] && $this->cache_service) {
			$cached_data = $this->cache_service->get_cache($cache_key, self::CACHE_GROUP);

			if ($cached_data !== false) {
				$this->log_info('Shared cache HIT for key: ' . $cache_key);
				return $cached_data;
			}

			$this->log_info('Shared cache MISS for key: ' . $cache_key);
			return false;
		}

		// Fallback to WordPress transients
		return $this->get_transient_data($cache_key);
	}

	/**
	 * Get data from WordPress transients
	 *
	 * @since 0.2.0
	 *
	 * @param string $cache_key Cache key
	 *
	 * @return mixed|false Cached data or false if not found
	 */
	private function get_transient_data($cache_key) {
		$transient_key = self::CACHE_GROUP . '_' . $cache_key;
		$cached_data = get_transient($transient_key);

		if ($cached_data !== false) {
			$this->log_info('Transient cache HIT for key: ' . $cache_key);
			return $cached_data;
		}

		$this->log_info('Transient cache MISS for key: ' . $cache_key);
		return false;
	}

	/**
	 * Set cached data
	 *
	 * @since 0.1.2
	 *
	 * @param string $cache_key Cache key
	 * @param mixed  $data      Data to cache
	 *
	 * @return void
	 */
	private function set_cached_data($cache_key, $data) {
		$settings = $this->get_settings();
		$cache_duration = $settings['cache_duration'] * MINUTE_IN_SECONDS;

		// Ensure cache service is available (lazy init if needed)
		$this->ensure_cache_service_available();


		// Only use shared Cache service if enabled in local settings and service is available
		if ($settings['enable_redis'] && $this->cache_service) {
			$success = $this->cache_service->set_cache($cache_key, $data, $cache_duration, self::CACHE_GROUP);

			if ($success) {
				$this->log_info('Shared cache set successful for key: ' . $cache_key . ' (Duration: ' . $cache_duration . 's)');
				return;
			}

			$this->log_info('Shared cache set failed for key: ' . $cache_key);
		}

		// Fallback to WordPress transients
		set_transient(self::CACHE_GROUP . '_' . $cache_key, $data, $cache_duration);
		$this->log_info('Transient cached data for key: ' . $cache_key . ' (Duration: ' . $cache_duration . 's)');
	}

	/**
	 * Get product data by product ID.
	 *
	 * @since 0.1.2
	 * @param int $product_id The product ID.
	 * @return object|null The product data or null if not found.
	 */
	public function get_product_by_id($product_id) {
		$this->log_info('Getting product with ID: ' . $product_id);

		// Check cache first
		$cache_key = $this->get_cache_key('product', $product_id);
		$cached_product = $this->get_cached_data($cache_key);

		if ($cached_product !== false) {
			$this->log_info('Returning cached product: ' . $cached_product->name);
			return $cached_product;
		}

		$product = null;

		if (function_exists('Ecwid_Product::get_by_id')) {
			try {
				$product = Ecwid_Product::get_by_id($product_id);

				if ($product) {
					$this->log_info('Product found via Ecwid_Product::get_by_id - ' . $product->name);
				} else {
					$this->log_info('Product not found via Ecwid_Product::get_by_id for ID: ' . $product_id);
				}
			} catch (Exception $e) {
				$this->log_info('Error with Ecwid_Product::get_by_id - ' . $e->getMessage());
			}
		} else {
			$this->log_info('Ecwid_Product::get_by_id function not available');

			// Try alternative method using Ecwid API if available
			if (class_exists('Ecwid_Api_V3')) {
				try {
					$api = new Ecwid_Api_V3();
					$product = $api->get_product($product_id);

					if ($product) {
						$this->log_info('Product found via Ecwid_Api_V3 - ' . $product->name);
					} else {
						$this->log_info('Product not found via Ecwid_Api_V3 for ID: ' . $product_id);
					}
				} catch (Exception $e) {
					$this->log_info('Error with Ecwid_Api_V3 - ' . $e->getMessage());
				}
			}
		}

		// Cache the result (even if null) to prevent repeated API calls
		if ($product !== null) {
			$this->set_cached_data($cache_key, $product);
		} else {
			// Cache null results for a shorter duration to allow for retries
			$short_cache_key = $cache_key . '_null';
			set_transient($short_cache_key, null, 5 * MINUTE_IN_SECONDS);
		}

		return $product;
	}

	/**
	 * Get product ID from slug with caching.
	 *
	 * @since 0.1.2
	 * @param string $slug The product slug.
	 * @return int The product ID or 0 if not found.
	 */
	public function get_product_id_from_slug($slug) {
		if (!$slug) {
			return 0;
		}

		$this->log_info('Getting product ID for slug: ' . $slug);

		// Check cache first
		$cache_key = $this->get_cache_key('slug', $slug);
		$cached_id = $this->get_cached_data($cache_key);

		if ($cached_id !== false) {
			$this->log_info('Returning cached product ID: ' . $cached_id);
			return intval($cached_id);
		}

		$ecwid_store_id = EcwidPlatform::get_store_id();
		$slug_api_url = "https://app.ecwid.com/storefront/api/v1/{$ecwid_store_id}/catalog/slug";

		$this->log_info('Making API request to: ' . $slug_api_url);

		$slug_response = wp_remote_post($slug_api_url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => json_encode(array(
				'slug' => $slug
			))
		));

		if (is_wp_error($slug_response)) {
			$this->log_info('API request failed: ' . $slug_response->get_error_message());
			return 0;
		}

		$response_code = wp_remote_retrieve_response_code($slug_response);
		$this->log_info('API response code: ' . $response_code);

		if ($response_code === 200) {
			$slug_data = json_decode(wp_remote_retrieve_body($slug_response), true);
			$this->log_info('API response data', $slug_data);

			// Check if we found a valid product
			if (!empty($slug_data) && $slug_data['type'] === 'product' && !empty($slug_data['entityId'])) {
				$product_id = $slug_data['entityId'];

				// Cache the result
				$this->set_cached_data($cache_key, $product_id);

				$this->log_info('Found product ID: ' . $product_id);
				return $product_id;
			} else {
			}
		} else {
		}

		// Cache null results for a shorter duration
		set_transient($cache_key . '_null', 0, 5 * MINUTE_IN_SECONDS);
		$this->log_info('No product found for slug: ' . $slug);

		return 0;
	}

	/**
	 * Search for products in Ecwid store.
	 *
	 * @since 0.1.2
	 *
	 * @param string $query The search query.
	 * @param array  $options Optional search parameters.
	 * @return array Array of matching products.
	 */
	public function search_products($query, $options = array()) {
		$this->log_info('Searching products with query: ' . $query, $options);

		// Check cache first
		$cache_key = $this->get_cache_key('search', array('query' => $query, 'options' => $options));
		$cached_results = $this->get_cached_data($cache_key);

		if ($cached_results !== false) {
			$this->log_info('Returning cached search results (' . count($cached_results) . ' products)');
			return $cached_results;
		}

		$products = array();

		if (class_exists('Ecwid_Api_V3')) {
			try {
				$api = new Ecwid_Api_V3();

				// Merge with default search parameters
				$search_params = array_merge(array(
					'limit' => 10,
					'enabled' => true,
				), $options);

				// Add keyword only if query is provided
				if (!empty($query)) {
					$search_params['keyword'] = $query;
				}

				$this->log_info('Final search params for Ecwid API', $search_params);

				$result = $api->search_products($search_params);

				$this->log_info('Ecwid API response', array(
					'has_result' => !empty($result),
					'has_items' => isset($result->items),
					'total' => isset($result->total) ? $result->total : 'no total',
					'count' => isset($result->items) ? count($result->items) : 'no items'
				));

				if ($result && isset($result->items)) {
					foreach ($result->items as $product) {
						$products[] = array(
							'id' => $product->id,
							'name' => $product->name,
							'sku' => $product->sku,
							'price' => isset($product->price) ? $product->price : null,
							'categoryIds' => isset($product->categoryIds) ? $product->categoryIds : array(),
							'matchType' => !empty($query) ? 'keyword' : 'category'
						);
					}
					$this->log_info('Found ' . count($products) . ' products');
				}

				// Only try SKU search if we had a keyword query but no results
				if (empty($products) && !empty($query)) {
					$this->log_info('No results for keyword, trying SKU search');

					// Remove keyword and add SKU for second attempt
					unset($search_params['keyword']);
					$search_params['sku'] = $query;

					$result = $api->get_products($search_params);

					if ($result && isset($result->items)) {
						foreach ($result->items as $product) {
							$products[] = array(
								'id' => $product->id,
								'name' => $product->name,
								'sku' => $product->sku,
								'price' => isset($product->price) ? $product->price : null,
								'categoryIds' => isset($product->categoryIds) ? $product->categoryIds : array(),
								'matchType' => 'sku'
							);
						}
						$this->log_info('Found ' . count($products) . ' products by SKU search');
					}
				}
			} catch (Exception $e) {
				$this->log_info('Error during product search: ' . $e->getMessage());
			}
		}

		// Cache the results
		$this->set_cached_data($cache_key, $products);
		$this->log_info('Search completed, returning ' . count($products) . ' products');

		return $products;
	}

	/**
	 * Get categories from Ecwid store.
	 *
	 * @since 0.1.2
	 * @param array $options Optional parameters.
	 * @return array Array of categories.
	 */
	public function get_categories($options = array()) {
		$this->log_info('Getting categories', $options);

		// Check cache first
		$cache_key = $this->get_cache_key('categories', $options);
		$cached_categories = $this->get_cached_data($cache_key);

		if ($cached_categories !== false) {
			$this->log_info('Returning cached categories (' . count($cached_categories) . ' categories)');
			return $cached_categories;
		}

		$categories = array();

		if (class_exists('Ecwid_Api_V3')) {
			try {
				$api = new Ecwid_Api_V3();
				$categories_result = $api->get_categories($options);

				if ($categories_result && isset($categories_result->items)) {
					$categories = $categories_result->items;
					$this->log_info('Found ' . count($categories) . ' categories');
				}
			} catch (Exception $e) {
				$this->log_info('Error getting categories: ' . $e->getMessage());
			}
		}

		// Cache the results
		$this->set_cached_data($cache_key, $categories);

		return $categories;
	}

	/**
	 * Clear all cached data
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function clear_cache() {
		$this->log_info('Clearing all Ecwid cache');

		$settings = $this->get_settings();

		// Use shared Cache service if enabled and available
		if ($settings['enable_redis'] && $this->cache_service) {
			$success = $this->cache_service->clear_cache_group(self::CACHE_GROUP);

			if ($success) {
				$this->log_info('Shared Redis service: Ecwid cache cleared');
				return;
			}

			$this->log_info('Shared Redis service cache clear failed');
		}

		// Fallback: Clear WordPress transients
		global $wpdb;

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_' . self::CACHE_GROUP . '_%'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_timeout_' . self::CACHE_GROUP . '_%'
			)
		);

		$this->log_info('Transient cache cleared: ' . $deleted . ' transients deleted');
	}

	/**
	 * Get detailed cache statistics by type
	 *
	 * @since 0.6.1
	 *
	 * @return array Cache statistics by type
	 */
	public function get_cache_stats() {
		$stats = array(
			'products' => 0,
			'searches' => 0,
			'categories' => 0,
			'slugs' => 0,
			'descriptions' => 0,
			'other' => 0,
			'total' => 0
		);

		if ( $this->cache_service && $this->cache_service->is_redis_available() ) {
			try {
				// Try to get Redis connection (works for direct Redis)
				$redis = $this->cache_service->get_redis();
				
				if ( $redis ) {
					// Direct Redis connection - use key patterns
					$site_prefix = defined( 'WP_REDIS_PREFIX' ) ? WP_REDIS_PREFIX : get_current_blog_id();
					
					$key_patterns = array(
						'products' => self::CACHE_GROUP . ":peaches:{$site_prefix}:product_*",
						'searches' => self::CACHE_GROUP . ":peaches:{$site_prefix}:search_*",
						'categories' => self::CACHE_GROUP . ":peaches:{$site_prefix}:categories_*",
						'slugs' => self::CACHE_GROUP . ":peaches:{$site_prefix}:slug_*",
						'descriptions' => self::CACHE_GROUP . ":peaches:{$site_prefix}:product_description_*",
					);
					
					foreach ( $key_patterns as $type => $pattern ) {
						$keys = $redis->keys( $pattern );
						$stats[$type] = count( $keys );
					}
					
					$all_pattern = self::CACHE_GROUP . ":peaches:{$site_prefix}:*";
					$all_keys = $redis->keys( $all_pattern );
					$stats['total'] = count( $all_keys );
					$stats['other'] = $stats['total'] - array_sum( array_slice( $stats, 0, -2 ) );
				} else {
					// Object Cache plugin - use group stats from cache manager
					if ( $this->cache_service && method_exists( $this->cache_service, 'get_cache_stats_by_group' ) ) {
						$group_stats = $this->cache_service->get_cache_stats_by_group();
						$ecwid_stats = isset( $group_stats[self::CACHE_GROUP] ) ? $group_stats[self::CACHE_GROUP] : 0;
						
						// For Object Cache, we can't easily separate by cache key type,
						// so show all as "other" for now
						$stats['total'] = $ecwid_stats;
						$stats['other'] = $ecwid_stats;
					}
				}
			} catch ( Exception $e ) {
				$this->log_info( 'Failed to get detailed cache stats: ' . $e->getMessage() );
			}
		}

		return $stats;
	}

	/**
	 * Get cache information and statistics
	 *
	 * @since 0.2.0
	 *
	 * @return array Cache information
	 */
	public function get_cache_info() {
		$settings = $this->get_settings();
		$redis_enabled = $settings['enable_redis'] && $this->cache_service;
		$redis_available = $redis_enabled && $this->cache_service->is_redis_available();

		$info = array(
			'type' => $redis_available ? 'Shared Redis Service' : 'WordPress Transients',
			'redis_available' => $redis_available,
			'count' => 0,
			'memory_usage' => 0,
		);

		if ($redis_available) {
			// Get cache info from shared Cache service
			//
			$shared_info = $this->cache_service->get_cache_info();

			// Copy relevant information
			if (isset($shared_info['redis_info'])) {
				$info['redis_info'] = $shared_info['redis_info'];
			}

			$info['memory_usage'] = $shared_info['memory_usage'] ?? 0;

			// Count our specific cache keys
			try {
				$redis = $this->cache_service->get_redis();
				if ($redis) {
					$site_prefix = defined('WP_REDIS_PREFIX') ? WP_REDIS_PREFIX : get_current_blog_id();
					$pattern = self::CACHE_GROUP . ":peaches:{$site_prefix}:*";
					$keys = $redis->keys($pattern);
					$info['count'] = count($keys);
				}
			} catch (Exception $e) {
				$this->log_info('Error counting Ecwid cache keys: ' . $e->getMessage());
			}
		} else {
			// Get transient count
			global $wpdb;
			$info['count'] = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
					'_transient_' . self::CACHE_GROUP . '_%'
				)
			);
		}

		return $info;
	}

	/**
	 * Get product description by type with caching support
	 *
	 * @since 0.4.7
	 *
	 * @param int    $product_id       Product ID
	 * @param string $description_type Description type (usage, care, etc.)
	 * @param string $language         Optional language code
	 *
	 * @return array|null Description data with title and content, or null if not found
	 */
	public function get_product_description_by_type($product_id, $description_type, $language = 'en') {
		if (empty($product_id) || empty($description_type)) {
			return null;
		}

		// Create cache key
		$cache_key = sprintf(
			'product_description_%d_%s_%s',
			$product_id,
			$description_type,
			$language
		);

		// Try to get from cache first
		$cached_data = $this->get_cached_data($cache_key);
		if ($cached_data !== false) {
			return $cached_data;
		}

		try {
			// Get product settings manager
			$settings_manager = Peaches_Ecwid_Blocks::get_instance()->get_product_settings_manager();
			if (!$settings_manager) {
				$this->log_error('Product settings manager not available');
				return null;
			}

			// Get description from product settings
			$description_data = $settings_manager->get_product_description_by_type(
				$product_id,
				$description_type,
				$language
			);

			if (!$description_data) {
				// Cache negative result for shorter time (use transients directly for short cache)
				set_transient($cache_key . '_null', null, 5 * MINUTE_IN_SECONDS);
				return null;
			}

			// Ensure we have the expected structure
			$formatted_data = [
				'title' => isset($description_data['title']) ? $description_data['title'] : '',
				'content' => isset($description_data['content']) ? $description_data['content'] : '',
				'type' => $description_type,
			];

			// Cache the result
			$this->set_cached_data($cache_key, $formatted_data);

			return $formatted_data;

		} catch (Exception $e) {
			$this->log_error('Error fetching product description', [
				'product_id' => $product_id,
				'description_type' => $description_type,
				'language' => $language,
				'error' => $e->getMessage(),
			]);

			// Cache error result for short time (use transients directly for short cache)
			set_transient($cache_key . '_error', null, 2 * MINUTE_IN_SECONDS);
			return null;
		}
	}

	/**
	 * Get all products from Ecwid store with pagination, search, and sorting.
	 *
	 * @since 0.2.3
	 *
	 * @param array $options Search and pagination parameters
	 *
	 * @return array Array containing products and pagination info
	 */
	public function get_all_products($options = array()) {
		$this->log_info('Getting all products with options', $options);

		// Check cache first
		$cache_key = $this->get_cache_key('all_products', $options);
		$cached_products = $this->get_cached_data($cache_key);

		if ($cached_products !== false) {
			$this->log_info('Returning cached products (' . count($cached_products) . ' products)');
			return $cached_products;
		}

		$products = array();

		if (class_exists('Ecwid_Api_V3')) {
			try {
				$api = new Ecwid_Api_V3();

				// Clean up the options to only include parameters the API actually supports
				$api_params = array();

				// Only include supported parameters
				if (isset($options['limit'])) {
					$api_params['limit'] = min(100, max(1, intval($options['limit'])));
				} else {
					$api_params['limit'] = 100;
				}

				if (isset($options['offset'])) {
					$api_params['offset'] = max(0, intval($options['offset']));
				} else {
					$api_params['offset'] = 0;
				}

				if (isset($options['enabled'])) {
					$api_params['enabled'] = $options['enabled'];
				} else {
					$api_params['enabled'] = true; // Only get enabled products by default
				}

				if (isset($options['category'])) {
					$api_params['category'] = intval($options['category']);
				}

				if (isset($options['keyword'])) {
					$api_params['keyword'] = sanitize_text_field($options['keyword']);
				}

				if (isset($options['sku'])) {
					$api_params['sku'] = sanitize_text_field($options['sku']);
				}

				if (isset($options['inStock'])) {
					$api_params['inStock'] = $options['inStock'];
				}

				// Handle sorting carefully - the API is picky about these
				if (isset($options['sortBy']) && !empty($options['sortBy'])) {
					$allowed_sorts = array('NAME', 'PRICE', 'UPDATED_TIME', 'CREATED_TIME', 'NAME_DESC', 'PRICE_DESC', 'UPDATED_TIME_DESC', 'CREATED_TIME_DESC');

					// Convert sortBy + sortOrder to single sortBy value that API expects
					$sort_by = strtoupper($options['sortBy']);
					$sort_order = isset($options['sortOrder']) ? strtoupper($options['sortOrder']) : 'ASC';

					if ($sort_by === 'NAME') {
						$api_params['sortBy'] = ($sort_order === 'DESC') ? 'NAME_DESC' : 'NAME';
					} elseif ($sort_by === 'PRICE') {
						$api_params['sortBy'] = ($sort_order === 'DESC') ? 'PRICE_DESC' : 'PRICE';
					} elseif ($sort_by === 'UPDATED_TIME') {
						$api_params['sortBy'] = ($sort_order === 'DESC') ? 'UPDATED_TIME_DESC' : 'UPDATED_TIME';
					} elseif ($sort_by === 'CREATED_TIME') {
						$api_params['sortBy'] = ($sort_order === 'DESC') ? 'CREATED_TIME_DESC' : 'CREATED_TIME';
					} else {
						// If invalid sort, just omit it and let Ecwid use default
						$this->log_info('Invalid sortBy value, using default: ' . $sort_by);
					}
				}

				$this->log_info('API parameters after cleanup', $api_params);

				$all_products = array();
				$has_more = true;
				$current_offset = $api_params['offset'];
				$safety_counter = 0;

				// Fetch all products with pagination
				while ($has_more && $safety_counter < 100) { // Safety limit to prevent infinite loops
					$api_params['offset'] = $current_offset;
					$this->log_info('Fetching products with offset: ' . $current_offset);

					$result = $api->get_products($api_params);
					$this->log_info('API result type: ' . gettype($result));

					if ($result && is_object($result) && isset($result->items) && is_array($result->items)) {
						$this->log_info('Received ' . count($result->items) . ' products from API');

						foreach ($result->items as $product) {
							$products[] = array(
								'id' => $product->id,
								'name' => $product->name,
								'sku' => isset($product->sku) ? $product->sku : '',
								'price' => isset($product->price) ? $product->price : 0,
								'enabled' => isset($product->enabled) ? $product->enabled : true,
								'url' => isset($product->url) ? $product->url : '',
								'imageUrl' => isset($product->imageUrl) ? $product->imageUrl : '',
								'thumbnailUrl' => isset($product->thumbnailUrl) ? $product->thumbnailUrl : '',
								'description' => isset($product->description) ? $product->description : '',
								'inStock' => isset($product->inStock) ? $product->inStock : false,
								'weight' => isset($product->weight) ? $product->weight : 0,
								'created' => isset($product->created) ? $product->created : '',
								'updated' => isset($product->updated) ? $product->updated : ''
							);
						}

						// Check if there are more products
						if (isset($result->total) && isset($result->count) && isset($result->offset)) {
							$current_offset += $result->count;
							$has_more = ($current_offset < $result->total) && (count($result->items) > 0);
							$this->log_info('Pagination info - offset: ' . $current_offset . ', total: ' . $result->total . ', has_more: ' . ($has_more ? 'yes' : 'no'));
						} else {
							$has_more = false;
						}
					} else {
						$this->log_info('No valid result or items in API response');

						// If this is the first attempt and we have sorting, try without sorting
						if ($current_offset === $api_params['offset'] && isset($api_params['sortBy'])) {
							$this->log_info('Retrying without sortBy parameter');
							unset($api_params['sortBy']);
							$safety_counter++; // Increment counter but try again
							continue;
						}

						$has_more = false;
					}

					$safety_counter++;
				}

				$this->log_info('Total products fetched: ' . count($products));

			} catch (Exception $e) {
				$this->log_info('Error fetching all products: ' . $e->getMessage());
				return array(); // Return empty array on error, don't cache
			}
		} else {
			$this->log_info('Ecwid_Api_V3 class not available');
			return array();
		}

		// Only cache successful results
		if (!empty($products)) {
			$cache_expiration = isset($options['cache_expiration']) ? $options['cache_expiration'] : (6 * HOUR_IN_SECONDS);
			$this->set_cached_data($cache_key, $products, $cache_expiration);
			$this->log_info('Products cached successfully');
		} else {
			$this->log_info('No products to cache - not caching empty result');
		}

		$this->log_info('get_all_products completed, returning ' . count($products) . ' products');

		return $products;
	}

	/**
	 * Check if a WordPress post exists for the given Ecwid product.
	 *
	 * @since 0.2.3
	 *
	 * @param int    $product_id  Ecwid product ID
	 * @param string $product_sku Ecwid product SKU
	 *
	 * @return bool True if post exists, false otherwise
	 */
	private function product_has_post( $product_id, $product_sku = '' ) {
		// Use the cached get_product_post_id method instead of duplicating database queries
		$post_id = $this->get_product_post_id( $product_id, $product_sku );
		return $post_id !== null;
	}

	/**
	 * Get WordPress post ID for the given Ecwid product.
	 *
	 * @since 0.2.3
	 *
	 * @param int    $product_id  Ecwid product ID
	 * @param string $product_sku Ecwid product SKU
	 *
	 * @return int|null Post ID or null if not found
	 */
	public function get_product_post_id( $product_id, $product_sku = '' ) {
		// Create cache key based on product ID and SKU
		$cache_key = $this->get_cache_key('product_post_id', $product_id . '_' . $product_sku);
		$cached_post_id = $this->get_cached_data($cache_key);

		if ($cached_post_id !== false) {
			$this->log_info('Returning cached post ID: ' . $cached_post_id . ' for product: ' . $product_id);
			return $cached_post_id ? (int) $cached_post_id : null;
		}

		global $wpdb;

		// Check by product ID first
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_ecwid_product_id'
				AND meta_value = %s
				AND post_id IN (
					SELECT ID FROM {$wpdb->posts}
					WHERE post_type = 'product_settings'
					AND post_status IN ('publish', 'draft')
				)",
				$product_id
			)
		);

		if ( $post_id ) {
			$result = (int) $post_id;
			$this->set_cached_data($cache_key, $result);
			$this->log_info('Found and cached post ID: ' . $result . ' for product: ' . $product_id);
			return $result;
		}

		// Check by SKU if available
		if ( ! empty( $product_sku ) ) {
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta}
					WHERE meta_key = '_ecwid_product_sku'
					AND meta_value = %s
					AND post_id IN (
						SELECT ID FROM {$wpdb->posts}
						WHERE post_type = 'product_settings'
						AND post_status IN ('publish', 'draft')
					)",
					$product_sku
				)
			);

			if ( $post_id ) {
				$result = (int) $post_id;
				$this->set_cached_data($cache_key, $result);
				$this->log_info('Found and cached post ID by SKU: ' . $result . ' for product: ' . $product_id);
				return $result;
			}
		}

		// Cache null result to prevent repeated database queries
		$this->set_cached_data($cache_key, null);
		$this->log_info('No post ID found for product: ' . $product_id . ', cached null result');
		return null;
	}

	/**
	 * Get related product IDs from a product object.
	 *
	 * Extracts related product IDs from Ecwid product data using both
	 * explicit product IDs and category-based related products.
	 *
	 * @since 0.2.6
	 *
	 * @param object $product Product object from Ecwid API.
	 *
	 * @return array Array of related product IDs.
	 */
	public function get_related_product_ids($product) {
		if (!$product || empty($product->relatedProducts)) {
			return array();
		}

		// Check cache first using product ID and related products structure hash
		$cache_identifier = $product->id . '_' . md5(serialize($product->relatedProducts));
		$cache_key = $this->get_cache_key('related_products', $cache_identifier);
		$cached_related_ids = $this->get_cached_data($cache_key);

		if ($cached_related_ids !== false) {
			$this->log_info('Returning cached related product IDs for product: ' . $product->id);
			return $cached_related_ids;
		}

		$related_ids = array();

		// Method 1: Get related products by explicit product IDs
		if (isset($product->relatedProducts->productIds) &&
			is_array($product->relatedProducts->productIds)) {

			$related_ids = array_merge($related_ids, $product->relatedProducts->productIds);
		}

		// Method 2: Get related products by category
		if (isset($product->relatedProducts->relatedCategory) &&
			$product->relatedProducts->relatedCategory->enabled) {

			$related_category = $product->relatedProducts->relatedCategory;
			$category_related_ids = $this->get_related_products_by_category(
				$product,
				$related_category
			);

			$related_ids = array_merge($related_ids, $category_related_ids);
		}

		// Remove duplicates and the current product ID
		$related_ids = array_unique($related_ids);
		$related_ids = array_filter($related_ids, function($id) use ($product) {
			return $id != $product->id;
		});

		// Convert to integers and reindex array
		$related_ids = array_values(array_map('intval', $related_ids));

		// Cache the result
		$this->set_cached_data($cache_key, $related_ids);
		$this->log_info('Cached related product IDs for product: ' . $product->id . ' (' . count($related_ids) . ' related products)');

		return $related_ids;
	}

	/**
	 * Get related products by category.
	 *
	 * @since 0.2.6
	 *
	 * @param object $product         Current product object.
	 * @param object $related_category Related category configuration.
	 *
	 * @return array Array of related product IDs.
	 */
	private function get_related_products_by_category($product, $related_category) {
		$related_ids = array();

		try {
			// Determine which category to search in
			$category_id = null;

			if (isset($related_category->categoryId)) {
				$category_id = $related_category->categoryId;
			} elseif (isset($product->categoryIds) && !empty($product->categoryIds)) {
				// Use the first category of the current product
				$category_id = $product->categoryIds[0];
			}

			if (!$category_id) {
				return $related_ids;
			}

			// Set up search parameters
			$search_params = array(
				'category' => $category_id,
				'enabled' => true,
				'limit' => isset($related_category->productCount) ?
					$related_category->productCount + 1 : 5, // Get one extra to exclude current
			);

			// Search for products in the category
			$category_products = $this->search_products('', $search_params);

			if (!empty($category_products)) {
				foreach ($category_products as $cat_product) {
					if (isset($cat_product['id']) && $cat_product['id'] != $product->id) {
						$related_ids[] = $cat_product['id'];
					}
				}
			}

			// Limit to the requested count
			if (isset($related_category->productCount)) {
				$related_ids = array_slice($related_ids, 0, $related_category->productCount);
			}

		} catch (Exception $e) {
			$this->log_error('Error fetching related products by category: ' . $e->getMessage());
		}

		return $related_ids;
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
	private function log_info($message, $context = array()) {
		$settings = $this->get_settings();

		// Use shared Bootstrap utilities with Ecwid's debug setting
		if (class_exists('Peaches_Utilities')) {
			Peaches_Utilities::log_error('[INFO] [ECWID API] ' . $message, $context, '[Peaches Ecwid Blocks]', $settings['debug_mode']);
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
			Peaches_Ecwid_Utilities::log_error('[ECWID API] ' . $message, $context);
		} else {
			// Fallback logging if utilities class is not available
			$this->log_error('[Peaches Ecwid] [ECWID API] ' . $message . (empty($context) ? '' : ' - Context: ' . wp_json_encode($context)));
		}
	}
}
