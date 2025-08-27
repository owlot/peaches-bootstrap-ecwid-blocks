<?php
/**
 * Custom Ecwid sitemap URL modification
 *
 * This class handles replacing Ecwid's default sitemap URLs with custom product detail page URLs
 * by using Ecwid's own API to get the original URLs and then replacing them with custom ones.
 *
 * @package Peaches_Bootstrap_Ecwid_Blocks
 * @since 0.4.6
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Peaches_Ecwid_Sitemap_Manager {

	/**
	 * Cache of Ecwid URLs mapped to custom URLs
	 *
	 * @var array
	 */
	private $url_mapping_cache = array();

	/**
	 * Original error reporting level
	 *
	 * @var int
	 */
	private $original_error_reporting = null;

	/**
	 * Initialize the sitemap manager
	 *
	 * @since 0.4.6
	 */
	public function __construct() {
		add_action('init', array($this, 'init_sitemap_filters'), 20); // Later priority to run after Ecwid
	}

	/**
	 * Initialize sitemap-related filters and hooks
	 *
	 * @since 0.4.6
	 */
	public function init_sitemap_filters() {
		// Remove Ecwid's sitemap provider registration - try multiple hook approaches
		remove_action('init', array('Ec_Store_Sitemap_Provider', 'init'));

		// Hook late on init to unregister after Ecwid has registered their provider
		add_action('init', array($this, 'unregister_ecwid_sitemap_provider'), 999);

		// Exclude ec-product post type from standard WordPress posts sitemap
		add_filter('wp_sitemaps_post_types', array($this, 'exclude_ec_product_from_posts_sitemap'), 10, 1);

		// Build URL mapping cache when WordPress loads (not during sitemap generation)
		add_action('wp_loaded', array($this, 'build_ecwid_url_mapping'));

		// Suppress PHP warnings during sitemap generation
		add_action('wp_sitemaps_init', array($this, 'suppress_sitemap_warnings'), 1);
	}

	/**
	 * Exclude ec-product post type from WordPress posts sitemap
	 *
	 * Since we have our own ecproduct sitemap provider with correct URLs,
	 * we don't want ec-product posts appearing in the standard posts sitemap.
	 *
	 * @since 0.4.6
	 * @param array $post_types Array of post types to include in sitemap.
	 * @return array Modified array without ec-product.
	 */
	public function exclude_ec_product_from_posts_sitemap($post_types) {
		// Remove ec-product from the posts sitemap
		if (isset($post_types['ec-product'])) {
			unset($post_types['ec-product']);
			$this->log_info('Excluded ec-product post type from WordPress posts sitemap');
		}

		return $post_types;
	}

	/**
	 * Suppress warnings during sitemap generation to prevent XML corruption
	 *
	 * @since 0.4.6
	 */
	public function suppress_sitemap_warnings() {
		if ($this->is_sitemap_request()) {
			$this->log_info('Suppressing warnings');
			// Temporarily suppress warnings to prevent XML corruption
			$this->original_error_reporting = error_reporting();
			error_reporting(E_ERROR | E_PARSE);
		}
	}

	/**
	 * Unregister Ecwid's sitemap provider to prevent duplicate/incorrect URLs
	 *
	 * @since 0.4.6
	 */
	public function unregister_ecwid_sitemap_provider() {
		// Check if WordPress sitemaps are available (WP 5.5+)
		if (!function_exists('wp_sitemaps_get_server')) {
			$this->log_info('WordPress sitemaps not available in this version (wp_sitemaps_get_server missing)');
			return;
		}

		// Get the sitemaps server instance
		$sitemaps = wp_sitemaps_get_server();

		if (!$sitemaps || !isset($sitemaps->registry)) {
			$this->log_info('WordPress sitemap server or registry not available');
			return;
		}

		// Get the registry and check if providers method exists
		$registry = $sitemaps->registry;

		if (!$registry || !method_exists($registry, 'get_providers')) {
			$this->log_info('WordPress sitemap registry or get_providers method not available');
			return;
		}

		// Check if Ecwid's provider is registered
		$providers = $registry->get_providers();

		if (isset($providers['ecstore'])) {
			$this->log_info('Found Ecwid ecstore sitemap provider, removing it');

			// Use reflection to access the private providers property
			try {
				$reflection = new ReflectionClass($registry);
				$providers_property = $reflection->getProperty('providers');
				$providers_property->setAccessible(true);
				$current_providers = $providers_property->getValue($registry);

				// Remove the ecstore provider
				unset($current_providers['ecstore']);
				$providers_property->setValue($registry, $current_providers);

				$this->log_info('Successfully removed Ecwid ecstore sitemap provider using reflection');
			} catch (Exception $e) {
				$this->log_info('Failed to remove ecstore provider via reflection: ' . $e->getMessage());
			}
		} else {
			$this->log_info('Ecwid ecstore sitemap provider not found in registry');
		}
	}

	/**
	 * Build mapping of Ecwid URLs to custom URLs by getting original Ecwid URLs
	 *
	 * @since 0.4.6
	 */
	public function build_ecwid_url_mapping() {
		// Don't populate cache during sitemap generation to avoid conflicts
		if ($this->is_sitemap_request()) {
			return;
		}

		try {
			// Get your plugin instance and managers
			$plugin = Peaches_Ecwid_Blocks::get_instance();
			$product_manager = $plugin->get_product_manager();

			if (!$product_manager) {
				$this->log_info('Product manager not available');
				return;
			}

			// Get all available languages for multilingual sites
			$languages = Peaches_Ecwid_Utilities::get_available_languages();

			// Get products directly from Ecwid API (not your processed version)
			if (class_exists('Ecwid_Api_V3')) {
				$ecwid_api_v3 = new Ecwid_Api_V3();
				$products_response = $ecwid_api_v3->get_products(array(
					'enabled' => true,
					'limit' => 100
				));

				if ($products_response && isset($products_response->items)) {
					foreach ($products_response->items as $product) {
						// Get Ecwid's original URL (hash-based format)
						$ecwid_url = isset($product->url) ? $product->url : '';

						if ($ecwid_url && isset($product->autogeneratedSlug)) {
							// Generate custom URLs for each language
							foreach ($languages as $lang_code => $language_data) {
								$custom_url = $product_manager->build_product_url($product, $lang_code);

								if ($custom_url && $ecwid_url !== $custom_url) {
									// Create language-specific cache key if multilingual
									$cache_key = count($languages) > 1 ? $ecwid_url . '_' . $lang_code : $ecwid_url;
									$this->url_mapping_cache[$cache_key] = $custom_url;

									// Also handle variations with/without trailing slash and hash
									$variations = array(
										rtrim($ecwid_url, '/'),
										$ecwid_url . '/',
										str_replace('#!/', '/', $ecwid_url), // Convert hash to path
										rtrim(str_replace('#!/', '/', $ecwid_url), '/'),
									);

									foreach ($variations as $variant) {
										if ($variant && $variant !== $ecwid_url) {
											$variant_cache_key = count($languages) > 1 ? $variant . '_' . $lang_code : $variant;
											$this->url_mapping_cache[$variant_cache_key] = $custom_url;
										}
									}
								}
							}
						}
					}

					$this->log_info('Built URL mapping cache with ' . count($this->url_mapping_cache) . ' entries for ' . count($languages) . ' languages', $languages);
				}
			}
		} catch (Exception $e) {
			$this->log_info('Error building URL mapping cache: ' . $e->getMessage());
		}
	}

	/**
	 * Check if current request is for sitemap generation
	 *
	 * @since 0.4.6
	 * @return bool True if this is a sitemap request.
	 */
	private function is_sitemap_request() {
		global $wp;

		// Check if this is a sitemap request
		return (
			isset($wp->query_vars['sitemap']) ||
			isset($_GET['sitemap']) ||
			strpos($_SERVER['REQUEST_URI'], 'sitemap') !== false ||
			strpos($_SERVER['REQUEST_URI'], 'wp-sitemap') !== false
		);
	}

	/**
	 * Log informational messages.
	 *
	 * @since 0.4.6
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_info($message, $context = array()) {
		if (class_exists('Peaches_Ecwid_Utilities') && Peaches_Ecwid_Utilities::is_debug_mode()) {
			Peaches_Ecwid_Utilities::log_error('[INFO] [Sitemap Manager] ' . $message, $context);
		}
	}

	/**
	 * Log error messages.
	 *
	 * @since 0.4.6
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_error($message, $context = array()) {
		if (class_exists('Peaches_Ecwid_Utilities')) {
			Peaches_Ecwid_Utilities::log_error('[Sitemap Manager] ' . $message, $context);
		} else {
			// Fallback logging if utilities class is not available
			$this->log_error('[Peaches Ecwid] [Sitemap Manager] ' . $message . (empty($context) ? '' : ' - Context: ' . wp_json_encode($context)));
		}
	}
}

/**
 * Custom sitemap provider for Ecwid products
 *
 * @since 0.4.6
 */
class Peaches_Ecwid_Sitemap_Provider extends WP_Sitemaps_Provider {

	public function __construct() {
		$this->name        = 'ecproduct';
		$this->object_type = 'product';
	}

	public static function init() {
		$provider = new self();
		wp_register_sitemap_provider( 'ecproduct', $provider );
	}

	/**
	 * Get a URL list for a sitemap
	 *
	 * This method uses Ecwid's API to get products with their Ecwid URLs,
	 * then replaces them with custom URLs from your Product Manager.
	 *
	 * @since 0.4.6
	 * @param int    $page_num Page of results.
	 * @param string $subtype  Optional. Not applicable for Users but
	 *                         required for compatibility.
	 * @return array Array of URLs for a sitemap.
	 */
	public function get_url_list($page_num, $subtype = '') {
		$urls = array();

		try {
			// Get products from your Ecwid API with proper pagination
			$plugin = Peaches_Ecwid_Blocks::get_instance();
			$api = $plugin->get_ecwid_api();
			$product_manager = $plugin->get_product_manager();

			if (!$api || !$product_manager) {
				return $urls;
			}

			$limit = 100;
			$offset = ($page_num - 1) * $limit;

			$products = $api->get_all_products(array(
				'limit' => $limit,
				'offset' => $offset,
				'enabled' => true
			));

			if (!empty($products) && is_array($products)) {
				foreach ($products as $product_data) {
					// Convert array to object if needed
					$product = is_array($product_data) ? (object) $product_data : $product_data;

					// Skip if we don't have essential data
					if (!isset($product->id)) {
						continue;
					}

					// Get the full product object from API if we only have basic data
					if (!isset($product->autogeneratedSlug)) {
						try {
							$full_product = $api->get_product_by_id($product->id);
							if ($full_product && isset($full_product->autogeneratedSlug)) {
								$product = $full_product;
							} else {
								continue; // Skip if we can't get the full product
							}
						} catch (Exception $e) {
							continue;
						}
					}

					// Build custom URL using your existing method
					if (isset($product->autogeneratedSlug)) {
						try {
							$custom_url = $product_manager->build_product_url($product);

							if ($custom_url) {
								$sitemap_entry = array(
									'loc' => $custom_url
								);

								// Add last modified date if available
								if (isset($product->updated) && !empty($product->updated)) {
									$sitemap_entry['lastmod'] = gmdate(DATE_W3C, strtotime($product->updated));
								} elseif (isset($product->updateTimestamp)) {
									$sitemap_entry['lastmod'] = gmdate(DATE_W3C, $product->updateTimestamp);
								}

								// Add change frequency and priority for SEO
								$sitemap_entry['changefreq'] = 'weekly';
								$sitemap_entry['priority'] = '0.8';

								$urls[] = $sitemap_entry;
							}
						} catch (Exception $e) {
							// Skip this product if URL building fails
						}
					}
				}
			}
		} catch (Exception $e) {
			// Log error but don't break sitemap generation
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('Peaches Ecwid Sitemap Provider Error: ' . $e->getMessage());
			}
		}

		/**
		 * Filter the sitemap URLs for Ecwid products
		 *
		 * @since 0.4.6
		 * @param array $urls      Array of sitemap URLs.
		 * @param int   $page_num  Page number.
		 */
		return apply_filters('peaches_ecwid_sitemap_urls', $urls, $page_num);
	}
	/**
	 * Get the max number of pages available for the object type
	 *
	 * @since 0.4.6
	 * @param string $subtype Optional. Not applicable for Users but
	 *                        required for compatibility.
	 * @return int Total number of pages.
	 */
	public function get_max_num_pages($subtype = '') {
		try {
			// Get total product count from your Ecwid API
			$plugin = Peaches_Ecwid_Blocks::get_instance();
			$api = $plugin->get_ecwid_api();

			// Get all products and count them (this might be cached)
			$all_products = $api->get_all_products(array(
				'enabled' => true
			));

			$total_products = is_array($all_products) ? count($all_products) : 0;

			return max(1, (int) ceil($total_products / 100));
		} catch (Exception $e) {
			// Log error and return reasonable default
			if (defined('WP_DEBUG') && WP_DEBUG) {
				$this->log_error('Peaches Ecwid Sitemap Provider Error getting max pages: ' . $e->getMessage());
			}
			return 1;
		}
	}

	/**
	 * Log informational messages.
	 *
	 * @since 0.4.6
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_info($message, $context = array()) {
		if (class_exists('Peaches_Ecwid_Utilities') && Peaches_Ecwid_Utilities::is_debug_mode()) {
			Peaches_Ecwid_Utilities::log_error('[INFO] [Sitemap Provider] ' . $message, $context);
		}
	}

	/**
	 * Log error messages.
	 *
	 * @since 0.4.6
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_error($message, $context = array()) {
		if (class_exists('Peaches_Ecwid_Utilities')) {
			Peaches_Ecwid_Utilities::log_error('[Sitemap Provider] ' . $message, $context);
		} else {
			// Fallback logging if utilities class is not available
			$this->log_error('[Peaches Ecwid] [Sitemap Provider] ' . $message . (empty($context) ? '' : ' - Context: ' . wp_json_encode($context)));
		}
	}
}

add_filter('init', 'Peaches_Ecwid_Sitemap_Provider::init', 50);

// Initialize the sitemap manager
new Peaches_Ecwid_Sitemap_Manager();
