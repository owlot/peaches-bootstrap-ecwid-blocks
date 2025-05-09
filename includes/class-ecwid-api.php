<?php
/**
 * Ecwid API class
 *
 * Handles interaction with the Ecwid API.
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
	 * Constructor.
	 */
	public function __construct() {
		// Ensure Ecwid compatibility
		$this->ensure_ecwid_compatibility();
	}

	/**
	 * Get product data by product ID.
	 *
	 * @since 0.1.2
	 * @param int $product_id The product ID.
	 * @return object|null The product data or null if not found.
	 */
	public function get_product_by_id($product_id) {
		// For debugging
		error_log('Ecwid API: Getting product with ID: ' . $product_id);

		if (function_exists('Ecwid_Product::get_by_id')) {
			try {
				$product = Ecwid_Product::get_by_id($product_id);

				if ($product) {
					error_log('Ecwid API: Product found - ' . $product->name);
					return $product;
				} else {
					error_log('Ecwid API: Product not found for ID: ' . $product_id);
				}
			} catch (Exception $e) {
				error_log('Ecwid API: Error getting product - ' . $e->getMessage());
			}
		} else {
			error_log('Ecwid API: Ecwid_Product::get_by_id function not available');

			// Try alternative method using Ecwid API if available
			if (class_exists('Ecwid_Api_V3')) {
				try {
					$api = new Ecwid_Api_V3();
					$product = $api->get_product($product_id);

					if ($product) {
						error_log('Ecwid API V3: Product found - ' . $product->name);
						return $product;
					} else {
						error_log('Ecwid API V3: Product not found for ID: ' . $product_id);
					}
				} catch (Exception $e) {
					error_log('Ecwid API V3: Error getting product - ' . $e->getMessage());
				}
			}
		}

		return null;
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

		$ecwid_store_id = $this->get_store_id();
		$slug_api_url = "https://app.ecwid.com/storefront/api/v1/{$ecwid_store_id}/catalog/slug";

		$slug_response = wp_remote_post($slug_api_url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => json_encode(array(
				'slug' => $slug
			))
		));

		if (!is_wp_error($slug_response) && wp_remote_retrieve_response_code($slug_response) === 200) {
			$slug_data = json_decode(wp_remote_retrieve_body($slug_response), true);

			// Check if we found a valid product
			if (!empty($slug_data) && $slug_data['type'] === 'product' && !empty($slug_data['entityId'])) {
				// Get the product ID
				return $slug_data['entityId'];
			}
		}

		return 0; // No product found
	}

	/**
	 * Search for products in Ecwid store.
	 *
	 * @since 0.1.2
	 * @param string $query The search query.
	 * @param array  $options Optional search parameters.
	 * @return array Array of matching products.
	 */
	public function search_products($query, $options = array()) {
		$products = array();

		if (class_exists('Ecwid_Api_V3')) {
			try {
				$api = new Ecwid_Api_V3();

				// Merge with default search parameters
				$search_params = array_merge(array(
					'limit' => 10,
					'enabled' => true,
				), $options);

				// First, search by product name
				$search_params['keyword'] = $query;
				$result = $api->get_products($search_params);

				if ($result && isset($result->items)) {
					foreach ($result->items as $product) {
						$products[] = array(
							'id' => $product->id,
							'name' => $product->name,
							'sku' => $product->sku,
							'price' => isset($product->price) ? $product->price : null,
							'matchType' => 'name'
						);
					}
				}

				// If no results, try searching by SKU
				if (empty($products)) {
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
								'matchType' => 'sku'
							);
						}
					}
				}
			} catch (Exception $e) {
				// Log error
				error_log('Ecwid API search error: ' . $e->getMessage());
			}
		}

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
		$categories = array();

		if (class_exists('Ecwid_Api_V3')) {
			try {
				$api = new Ecwid_Api_V3();
				$categories_result = $api->get_categories($options);

				if ($categories_result && isset($categories_result->items)) {
					$categories = $categories_result->items;
				}
			} catch (Exception $e) {
				// Log error
				error_log('Ecwid API categories error: ' . $e->getMessage());
			}
		}

		return $categories;
	}

	/**
	 * Get related products for a specific product.
	 *
	 * @since 0.1.2
	 * @param object $product The product object.
	 * @return string HTML for related products or empty string.
	 */
	public function get_related_products($product) {
		if (!$product || empty($product->relatedProducts)) {
			return '';
		}

		$api = new Ecwid_Api_V3();
		$related_products = [];

		if(isset($product->relatedProducts->productIds) && is_array($product->relatedProducts->productIds)) {
			$related_products = $api->get_products([
				'productId' => join(',', $product->relatedProducts->productIds)
			]);

			if (!$related_products || !isset($related_products->items) || count($related_products->items) <= 1) {
				return '';
			}
		}

		if(isset($product->relatedProducts->relatedCategory) && $product->relatedProducts->relatedCategory->enabled) {
			$related_category = $product->relatedProducts->relatedCategory;

			$filter = [
				'visibleInStorefront' => true,
				'enabled' => true,
				'limit' => $related_category->productCount + 1 // Get one extra to exclude current product
			];

			if(isset($related_category->categoryId)) {
				$filter['category'] = $related_category->categoryId;
			}

			$related_products = $api->get_products($filter);

			if (!$related_products || !isset($related_products->items) || count($related_products->items) <= 1) {
				return '';
			}

			// Filter out current product
			$filtered_products = array_filter($related_products->items, function($item) use ($product) {
				return $item->id != $product->id;
			});

			// Get only the needed number of products
			$related_products = array_slice($filtered_products, 0, $related_category->productCount);

			// If after filtering we have no products, return empty
			if (!$related_products || !isset($related_products->items) || count($related_products->items) <= 1) {
				return '';
			}
		}

		// Build HTML output (this could be moved to a template)
		ob_start();
?>
	<div class="related-products my-5">
	<h3><?php echo __('Related Products', 'peaches'); ?></h3>
	<div class="row row-cols-2 row-cols-md-4 g-4">
<?php
		// Loop through related products
		foreach ($related_products->items as $related) {
			// This should be replaced with a proper rendering function
			echo '<div class="col">';
			echo '<div class="card h-100 border-0">';
			echo '<div class="ratio ratio-1x1">';
			echo '<img src="' . esc_url($related->thumbnailUrl) . '" class="card-img-top" alt="' . esc_attr($related->name) . '">';
			echo '</div>';
			echo '<div class="card-body p-2 p-md-3">';
			echo '<h5 class="card-title">' . esc_html($related->name) . '</h5>';
			echo '</div>';
			echo '<div class="card-footer border-0">';
			echo '<div class="card-text fw-bold">€ ' . number_format($related->price, 2, ',', '.') . '</div>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
				}
?>
	</div>
	</div>
<?php
		return ob_get_clean();
	}

	/**
	 * Get Ecwid store ID.
	 *
	 * @since 0.1.2
	 * @return int The store ID.
	 */
	public function get_store_id() {
		if (function_exists('EcwidPlatform::get_store_id')) {
			return EcwidPlatform::get_store_id();
		} elseif (defined('ECWID_STORE_ID')) {
			return ECWID_STORE_ID;
		} else {
			return get_option('ecwid_store_id', 0);
		}
	}

	/**
	 * Ensure compatibility with Ecwid.
	 *
	 * @since 0.1.2
	 */
	private function ensure_ecwid_compatibility() {
		// If get_ecwid_store_id doesn't exist, create a fallback
		if (!function_exists('get_ecwid_store_id')) {
			function get_ecwid_store_id() {
				// Try different ways to get the store ID
				if (defined('ECWID_STORE_ID')) {
					return ECWID_STORE_ID;
				}

				$store_id = get_option('ecwid_store_id');
				if ($store_id) {
					return $store_id;
				}

				return ''; // Return empty string as fallback
			}
		}
	}
}
