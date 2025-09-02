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
	 * Redis connection instance
	 *
	 * @since 0.2.0
	 * @var Redis|null
	 */
	private $redis = null;

	/**
	 * Whether Redis is available and connected
	 *
	 * @since 0.2.0
	 * @var bool
	 */
	private $redis_available = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_redis();
	}

	/**
	 * Initialize Redis connection if available
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function init_redis() {
		$settings = $this->get_settings();

		// Only try Redis if enabled in settings and Redis extension is available
		if (!$settings['enable_redis'] || !extension_loaded('redis')) {
			$this->log_info('Redis not enabled or extension not available');
			return;
		}

		try {
			$this->redis = new Redis();

			$host = defined('WP_REDIS_HOST') ? WP_REDIS_HOST : '127.0.0.1';
			$port = defined('WP_REDIS_PORT') ? WP_REDIS_PORT : 6379;
			$timeout = defined('WP_REDIS_TIMEOUT') ? WP_REDIS_TIMEOUT : 1;
			$password = defined('WP_REDIS_PASSWORD') ? WP_REDIS_PASSWORD : null;
			$database = defined('WP_REDIS_DATABASE') ? WP_REDIS_DATABASE : 0;

			$connected = $this->redis->connect($host, $port, $timeout);

			if ($connected && $password) {
				$this->redis->auth($password);
			}

			if ($connected && $database) {
				$this->redis->select($database);
			}

			if ($connected) {
				// Test the connection
				$this->redis->ping();
				$this->redis_available = true;
				$this->log_info('Redis connection established: ' . $host . ':' . $port);
			}
		} catch (Exception $e) {
			$this->log_info('Redis connection failed: ' . $e->getMessage());
			$this->redis = null;
			$this->redis_available = false;
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
		$base_key = self::CACHE_GROUP . '_' . $type . '_' . md5(serialize($identifier));

		// Add site prefix for Redis to support multiple sites
		if ($this->redis_available) {
			$site_prefix = defined('WP_REDIS_PREFIX') ? WP_REDIS_PREFIX : get_current_blog_id();
			return $site_prefix . ':' . $base_key;
		}

		return $base_key;
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
		if ($this->redis_available) {
			try {
				$cached_data = $this->redis->get($cache_key);

				if ($cached_data !== false) {
					$this->log_info('Redis cache HIT for key: ' . $cache_key);
					return unserialize($cached_data);
				}

				$this->log_info('Redis cache MISS for key: ' . $cache_key);
				return false;
			} catch (Exception $e) {
				$this->log_info('Redis get error: ' . $e->getMessage());
				// Fall back to transients
				return $this->get_transient_data($cache_key);
			}
		}

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
		$cached_data = get_transient($cache_key);

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

		if ($this->redis_available) {
			try {
				$this->redis->setex($cache_key, $cache_duration, serialize($data));
				$this->log_info('Redis cached data for key: ' . $cache_key . ' (Duration: ' . $cache_duration . 's)');
				return;
			} catch (Exception $e) {
				$this->log_info('Redis set error: ' . $e->getMessage());
				// Fall back to transients
			}
		}

		set_transient($cache_key, $data, $cache_duration);
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
			}
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

		if ($this->redis_available) {
			try {
				// Get cache pattern for our keys
				$site_prefix = defined('WP_REDIS_PREFIX') ? WP_REDIS_PREFIX : get_current_blog_id();
				$pattern = $site_prefix . ':' . self::CACHE_GROUP . '_*';

				// Get all matching keys
				$keys = $this->redis->keys($pattern);

				if (!empty($keys)) {
					$deleted = $this->redis->del($keys);
					$this->log_info('Redis cache cleared: ' . $deleted . ' keys deleted');
				} else {
					$this->log_info('Redis cache: No keys found to delete');
				}

				return;
			} catch (Exception $e) {
				$this->log_info('Redis cache clear error: ' . $e->getMessage());
				// Fall back to clearing transients
			}
		}

		// Clear WordPress transients
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
	 * Get cache information and statistics
	 *
	 * @since 0.2.0
	 *
	 * @return array Cache information
	 */
	public function get_cache_info() {
		$info = array(
			'type' => $this->redis_available ? 'Redis' : 'WordPress Transients',
			'redis_available' => $this->redis_available,
			'count' => 0,
			'memory_usage' => 0,
		);

		if ($this->redis_available) {
			try {
				$site_prefix = defined('WP_REDIS_PREFIX') ? WP_REDIS_PREFIX : get_current_blog_id();
				$pattern = $site_prefix . ':' . self::CACHE_GROUP . '_*';
				$keys = $this->redis->keys($pattern);

				$info['count'] = count($keys);

				// Get memory usage for our keys
				if (!empty($keys)) {
					$memory = 0;
					foreach ($keys as $key) {
						$memory += $this->redis->memory('usage', $key);
					}
					$info['memory_usage'] = $memory;
				}

				// Get Redis connection info
				$redis_info = $this->redis->info();
				$info['redis_info'] = array(
					'version' => $redis_info['redis_version'] ?? 'unknown',
					'used_memory_human' => $redis_info['used_memory_human'] ?? 'unknown',
					'connected_clients' => $redis_info['connected_clients'] ?? 'unknown',
				);
			} catch (Exception $e) {
				$this->log_info('Error getting Redis cache info: ' . $e->getMessage());
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
			return true;
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
				return true;
			}
		}

		return false;
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
			return (int) $post_id;
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
				return (int) $post_id;
			}
		}

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
		$related_ids = array();

		if (!$product || empty($product->relatedProducts)) {
			return $related_ids;
		}

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
		if (class_exists('Peaches_Ecwid_Utilities') && Peaches_Ecwid_Utilities::is_debug_mode()) {
			Peaches_Ecwid_Utilities::log_error('[INFO] [ECWID API] ' . $message, $context);
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
