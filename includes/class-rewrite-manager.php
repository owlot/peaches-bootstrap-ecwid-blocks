<?php
/**
 * Rewrite Manager class
 *
 * Handles URL rewriting for product pages.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the interface
require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-rewrite-manager.php';

/**
 * Class Peaches_Rewrite_Manager
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
class Peaches_Rewrite_Manager implements Peaches_Rewrite_Manager_Interface {
	/**
	 * Ecwid API instance.
	 *
	 * @since  0.1.2
	 * @access private
	 * @var    Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Constructor.
	 *
	 * @since 0.1.2
	 * @param Peaches_Ecwid_API_Interface $ecwid_api Ecwid API instance.
	 */
	public function __construct( $ecwid_api ) {
		$this->ecwid_api = $ecwid_api;

		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since  0.1.2
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'add_ecwid_rewrite_rules' ), 999 );
		add_action( 'template_redirect', array( $this, 'product_template_redirect' ), 1 );
		add_action( 'template_redirect', array( $this, 'remove_default_canonical' ), 999 );
		add_action( 'init', array( $this, 'check_rewrite_rules' ), 1000 );
		add_action( 'wp_head', array( $this, 'add_product_og_tags' ) );
		add_action( 'wp_footer', array( $this, 'set_ecwid_config' ), 1000 );

		// Add hooks for dynamic page title and meta content.
		add_filter( 'document_title_parts', array( $this, 'filter_product_page_title' ), 10, 1 );
		add_filter( 'the_title', array( $this, 'filter_product_title' ), 10, 2 );
		add_filter( 'get_the_excerpt', array( $this, 'filter_product_excerpt' ), 10, 2 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'filter_product_featured_image' ), 10, 4 );
		add_filter( 'get_canonical_url', array( $this, 'filter_product_canonical_url' ), 10, 2 );
	}

	/**
	 * Add Ecwid product rewrite rules with configurable shop paths.
	 *
	 * Creates rewrite rules for each configured language using the shop paths
	 * defined in the multilingual settings.
	 *
	 * @since  0.1.2
	 * @return void
	 */
	public function add_ecwid_rewrite_rules() {
		add_rewrite_tag( '%ecwid_product_slug%', '([^&]+)' );

		// Get the template page ID from settings.
		$template_page_id = $this->get_template_page_id();

		if ( ! $template_page_id ) {
			$this->register_product_template();
			$template_page_id = $this->get_template_page_id();
		}

		if ( ! $template_page_id ) {
			$this->log_error( 'Could not create or find product template page' );
			return;
		}

		// Check if we have multilingual configuration.
		if ( $this->is_multilingual_site() ) {
			$this->add_multilingual_rewrite_rules( $template_page_id );
		} else {
			$this->add_single_language_rewrite_rules( $template_page_id );
		}
	}

	/**
	 * Add rewrite rules for multilingual sites.
	 *
	 * @since  0.1.2
	 * @param  int $template_page_id Template page ID.
	 * @return void
	 */
	private function add_multilingual_rewrite_rules( $template_page_id ) {
		$languages = $this->get_available_languages();

		if ( empty( $languages ) ) {
			$this->add_single_language_rewrite_rules( $template_page_id );
			return;
		}

		foreach ( $languages as $lang_code => $language_data ) {
			$shop_path = $this->get_shop_path_for_language( $lang_code );

			if ( empty( $shop_path ) ) {
				continue;
			}

			// For Polylang.
			if ( function_exists( 'pll_languages_list' ) ) {
				// Default language (no prefix).
				if ( $language_data['is_default'] ) {
					add_rewrite_rule(
						'^' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
						'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang_code,
						'top'
					);
				}

				// Language with prefix.
				add_rewrite_rule(
					'^' . preg_quote( $lang_code, '^' ) . '/' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
					'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang_code,
					'top'
				);
			} elseif ( function_exists( 'icl_get_languages' ) ) {
				// For WPML.
				$default_lang = apply_filters( 'wpml_default_language', null );

				// Default language (no prefix).
				if ( $lang_code === $default_lang ) {
					add_rewrite_rule(
						'^' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
						'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang_code,
						'top'
					);
				}

				// Language with prefix
				add_rewrite_rule(
					'^' . preg_quote( $lang_code, '^' ) . '/' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
					'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang_code,
					'top'
				);
			}
		}
	}

	/**
	 * Add rewrite rules for single language sites.
	 *
	 * @since 0.1.2
	 *
	 * @param int $template_page_id Template page ID.
	 *
	 * @return void
	 */
	private function add_single_language_rewrite_rules( $template_page_id ) {
		// Get the shop path from utility function (which will use Ecwid settings or default).
		$shop_path = Peaches_Ecwid_Utilities::get_shop_path( true, false ); // Include parents, no trailing slash

		if ( empty( $shop_path ) ) {
			$shop_path = 'shop'; // Ultimate fallback.
		}

		// Add rewrite rule for single language
		add_rewrite_rule(
			'^' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
			'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]',
			'top'
		);
	}

	/**
	 * Get available languages from multilingual plugins.
	 *
	 * @since 0.1.2
	 *
	 * @return array Array of languages with codes as keys.
	 */
	private function get_available_languages() {
		$languages = array();

		// Polylang support
		if ( function_exists( 'pll_languages_list' ) && function_exists( 'pll_default_language' ) ) {
			$lang_codes = pll_languages_list( array( 'fields' => 'slug' ) );
			$default_lang = pll_default_language( 'slug' );

			foreach ( $lang_codes as $code ) {
				$languages[ $code ] = array(
					'code' => $code,
					'is_default' => ( $code === $default_lang ),
				);
			}
		}
		// WPML support
		elseif ( function_exists( 'icl_get_languages' ) ) {
			$wpml_languages = icl_get_languages( 'skip_missing=0' );
			$default_lang = apply_filters( 'wpml_default_language', null );

			foreach ( $wpml_languages as $code => $lang ) {
				$languages[ $code ] = array(
					'code' => $code,
					'is_default' => ( $code === $default_lang ),
				);
			}
		}

		return $languages;
	}

	/**
	 * Get shop path for specific language.
	 *
	 * @since 0.1.2
	 *
	 * @param string $language_code Language code.
	 *
	 * @return string Shop path for the language.
	 */
	private function get_shop_path_for_language( $language_code ) {
		// Try to get from multilingual settings first
		if ( class_exists( 'Peaches_Multilingual_Settings' ) ) {
			$settings_manager = Peaches_Multilingual_Settings::get_instance();
			return $settings_manager->get_shop_path_for_language( $language_code );
		}

		// Fallback to utility function.
		return Peaches_Ecwid_Utilities::get_shop_path( true, false, $language_code );
	}

	/**
	 * Check if the site is configured for multiple languages.
	 *
	 * @since 0.1.2
	 *
	 * @return bool True if multilingual plugin is active and configured.
	 */
	private function is_multilingual_site() {
		// Check for Polylang
		if ( function_exists( 'pll_languages_list' ) ) {
			$languages = pll_languages_list();
			return is_array( $languages ) && count( $languages ) > 1;
		}

		// Check for WPML
		if ( function_exists( 'icl_get_languages' ) ) {
			$languages = icl_get_languages( 'skip_missing=0' );
			return is_array( $languages ) && count( $languages ) > 1;
		}

		return false;
	}

	/**
	 * Handle template redirect for product pages.
	 *
	 * @since 0.1.2
	 */
	public function product_template_redirect() {
		// Check if this is a product URL
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug) {
			// Set a global variable so our template can access the slug
			global $peaches_product_slug;
			$peaches_product_slug = $product_slug;

			// Get the product detail page from settings
			$template_page_id = $this->get_template_page_id();
			$template_page = $template_page_id ? get_post($template_page_id) : null;
			if ($template_page) {
				// If Polylang is active, get the translated page
				if (function_exists('pll_get_post')) {
					$current_lang = pll_current_language();
					$translated_page = pll_get_post($template_page->ID, $current_lang);
					if ($translated_page) {
						$template_page = get_post($translated_page);
					}
				}
				// If WPML is active, get the translated page
				elseif (function_exists('icl_object_id')) {
					$current_lang = ICL_LANGUAGE_CODE;
					$translated_page_id = icl_object_id($template_page->ID, 'page', false, $current_lang);
					if ($translated_page_id) {
						$template_page = get_post($translated_page_id);
					}
				}

				// Force WordPress to use our template
				global $wp_query;
				$wp_query->queried_object = $template_page;
				$wp_query->queried_object_id = $template_page->ID;
				$wp_query->is_page = true;
				$wp_query->is_single = false;
				$wp_query->is_home = false;
				$wp_query->is_archive = false;
				$wp_query->is_category = false;
				$wp_query->is_404 = false;
				$wp_query->post = $template_page;
				$wp_query->posts = array($template_page);

				// Prevent Ecwid from taking over
				remove_all_actions('template_redirect', 1);
			}
		}
	}

	/**
	 * Register a page template for product details.
	 *
	 * @since 0.1.2
	 */
	public function register_product_template() {
		// Check if the product detail page exists
		$page_exists = get_page_by_path('product-detail');

		if (!$page_exists) {
			// Create the product detail page
			$page_data = array(
				'post_title'    => 'Product Detail',
				'post_name'     => 'product-detail',
				'post_status'   => 'publish',
				'post_type'     => 'page',
				'post_content'  => '<!-- wp:peaches/ecwid-product-detail /-->',
			);

			wp_insert_post($page_data);
		}
	}

	/**
	 * Add Open Graph tags for product pages.
	 *
	 * @since 0.1.2
	 */
	public function add_product_og_tags() {
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug) {
			$product_id = $this->ecwid_api->get_product_id_from_slug($product_slug);

			if ($product_id) {
				$product = $this->ecwid_api->get_product_by_id($product_id);

				if ($product) {
					$product_description = wp_trim_words(wp_strip_all_tags($product->description), 30);
					// Get the actual current URL, not the template page permalink
					$product_url = home_url(add_query_arg(array(), $_SERVER['REQUEST_URI']));
?>
	<link rel="canonical" href="<?php echo esc_url($product_url); ?>" />
	<meta name="description" content="<?php echo esc_attr($product_description); ?>">
	<meta name="title" content="<?php echo esc_attr($product->name); ?>">

	<meta property="og:title" content="<?php echo esc_attr($product->name); ?>" />
	<meta property="og:description" content="<?php echo esc_attr($product_description); ?>" />
	<?php if (!empty($product->thumbnailUrl)): ?>
	<meta property="og:image" content="<?php echo esc_url($product->thumbnailUrl); ?>" />
	<?php endif; ?>
	<meta property="og:url" content="<?php echo esc_url($product_url); ?>" />
	<meta property="og:type" content="product" />
	<meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>">
	<meta property="og:locale" content="<?php echo esc_attr(get_locale()); ?>">

	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="<?php echo esc_attr($product->name); ?>">
	<meta name="twitter:description" content="<?php echo esc_attr($product_description); ?>">
	<?php if (!empty($product->thumbnailUrl)): ?>
	<meta name="twitter:image" content="<?php echo esc_url($product->thumbnailUrl); ?>">
	<?php endif; ?>

	<?php
					// Add Google Shopping Product Schema (JSON-LD)
					$this->add_product_schema_markup($product, $product_url);

					// Add Google Tag Manager data layer for product view
					$this->add_gtm_product_view($product);
				}
			}
		}
	}

	/**
	 * Add Google Shopping Product Schema markup (JSON-LD).
	 *
	 * Outputs structured data for Google Shopping and other search engines.
	 *
	 * @since 0.6.1
	 * @param object $product The product object from Ecwid API.
	 * @param string $product_url The canonical product URL.
	 */
	private function add_product_schema_markup($product, $product_url) {
		// Build the product schema
		$schema = array(
			'@context' => 'https://schema.org/',
			'@type' => 'Product',
			'name' => $product->name,
			'description' => wp_strip_all_tags($product->description),
			'url' => $product_url,
		);

		// Add images
		$images = array();
		if (!empty($product->thumbnailUrl)) {
			$images[] = $product->thumbnailUrl;
		}
		if (!empty($product->imageUrl)) {
			$images[] = $product->imageUrl;
		}
		if (!empty($product->galleryImages)) {
			foreach ($product->galleryImages as $gallery_image) {
				if (!empty($gallery_image->imageUrl)) {
					$images[] = $gallery_image->imageUrl;
				}
			}
		}
		if (!empty($images)) {
			$schema['image'] = array_unique($images);
		}

		// Add SKU if available
		if (!empty($product->sku)) {
			$schema['sku'] = $product->sku;
		}

		// Add brand if available in product options or attributes
		$brand = get_bloginfo('name'); // Default to site name
		if (!empty($product->attributes)) {
			foreach ($product->attributes as $attribute) {
				if (strtolower($attribute->name) === 'brand' || strtolower($attribute->name) === 'merk') {
					$brand = $attribute->value;
					break;
				}
			}
		}
		$schema['brand'] = array(
			'@type' => 'Brand',
			'name' => $brand
		);

		// Add offers (price and availability)
		$offer = array(
			'@type' => 'Offer',
			'url' => $product_url,
			'priceCurrency' => !empty($product->defaultDisplayedPriceFormatted) ?
				$this->extract_currency_code($product) : 'EUR',
			'price' => !empty($product->price) ? number_format($product->price, 2, '.', '') : '0.00',
			'availability' => $this->get_product_availability($product),
			'seller' => array(
				'@type' => 'Organization',
				'name' => get_bloginfo('name')
			)
		);

		// Add sale price if available
		if (!empty($product->compareToPrice) && $product->compareToPrice > $product->price) {
			$offer['priceValidUntil'] = date('Y-m-d', strtotime('+1 year'));
		}

		$schema['offers'] = $offer;

		// Add aggregate rating if available
		// Note: Ecwid doesn't provide ratings by default, but this can be extended
		// if you're using a review system
		if (!empty($product->rating)) {
			$schema['aggregateRating'] = array(
				'@type' => 'AggregateRating',
				'ratingValue' => $product->rating,
				'reviewCount' => !empty($product->reviewCount) ? $product->reviewCount : 1
			);
		}

		// Output the JSON-LD script
		?>
	<script type="application/ld+json">
	<?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
	</script>
		<?php
	}

	/**
	 * Extract currency code from product data.
	 *
	 * @since 0.6.1
	 * @param object $product The product object.
	 * @return string Currency code (default: EUR).
	 */
	private function extract_currency_code($product) {
		// Try to extract from formatted price string
		if (!empty($product->defaultDisplayedPriceFormatted)) {
			$formatted = $product->defaultDisplayedPriceFormatted;

			// Common currency symbols and codes
			$currencies = array(
				'€' => 'EUR',
				'$' => 'USD',
				'£' => 'GBP',
				'¥' => 'JPY',
				'CHF' => 'CHF',
				'EUR' => 'EUR',
				'USD' => 'USD',
				'GBP' => 'GBP',
			);

			foreach ($currencies as $symbol => $code) {
				if (strpos($formatted, $symbol) !== false) {
					return $code;
				}
			}
		}

		// Default to EUR for European shops
		return 'EUR';
	}

	/**
	 * Add Google Tag Manager data layer for product view event.
	 *
	 * Pushes product view data to the GTM data layer for enhanced e-commerce tracking.
	 *
	 * @since 0.6.1
	 * @param object $product The product object from Ecwid API.
	 */
	private function add_gtm_product_view($product) {
		$gtm_product = array(
			'event' => 'productView',
			'ecommerce' => array(
				'detail' => array(
					'products' => array(
						array(
							'id' => $product->id,
							'name' => $product->name,
							'price' => !empty($product->price) ? $product->price : 0,
							'brand' => get_bloginfo('name'),
							'category' => $this->get_category_name_safe($product),
							'variant' => !empty($product->sku) ? $product->sku : '',
						)
					)
				)
			)
		);

		?>
	<script>
	window.dataLayer = window.dataLayer || [];
	window.dataLayer.push(<?php echo wp_json_encode($gtm_product, JSON_UNESCAPED_SLASHES); ?>);
	</script>
		<?php
	}

	/**
	 * Get category name from product data safely.
	 *
	 * @since 0.6.1
	 * @param object $product The product object.
	 * @return string Category name or empty string.
	 */
	private function get_category_name_safe($product) {
		// Try to get category from product's categories array (if Ecwid provides it)
		if (!empty($product->categories) && is_array($product->categories)) {
			$first_category = reset($product->categories);
			if (!empty($first_category->name)) {
				return $first_category->name;
			}
		}

		// Fallback: try to extract from product object if category data is embedded
		if (!empty($product->categoryName)) {
			return $product->categoryName;
		}

		// If we have category IDs but no names, return empty string
		// Getting categories by ID would require additional API calls which could slow down the page
		return '';
	}

	/**
	 * Get product availability status for schema.org markup.
	 *
	 * Handles different availability scenarios including pre-order and backorder.
	 *
	 * @since 0.6.1
	 * @param object $product The product object from Ecwid API.
	 * @return string Schema.org availability URL.
	 */
	private function get_product_availability($product) {
		// Check if product is in stock
		$in_stock = !empty($product->inStock);

		// Check if unlimited quantity (always available)
		$unlimited = !empty($product->unlimited);

		// Check if out of stock purchases are allowed (pre-order/backorder)
		// Ecwid properties to check (in order of priority):
		// 1. warningLimit - if quantity is 0 but product enabled, it may be pre-order
		// 2. enabled - if product is enabled but not in stock
		// 3. showOnFrontpage - product visibility
		$available_for_purchase = false;

		// If product is enabled and not in stock, check if it's available for purchase
		if (isset($product->enabled) && $product->enabled) {
			// Product is enabled - check if purchases are allowed when out of stock
			if (!$in_stock && !$unlimited) {
				// Check quantity - if 0 or negative but enabled, likely pre-order
				$quantity = isset($product->quantity) ? intval($product->quantity) : 0;
				if ($quantity <= 0) {
					$available_for_purchase = true; // Enabled with 0 stock = pre-order allowed
				}
			}
		}

		// Optional debug logging (enable WP_DEBUG to see)
		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			$this->log_info('[Product Schema] Product availability', array(
				'product_id' => $product->id ?? 'unknown',
				'in_stock' => $in_stock,
				'unlimited' => $unlimited,
				'enabled' => isset($product->enabled) ? $product->enabled : null,
				'quantity' => isset($product->quantity) ? $product->quantity : null,
				'available_for_purchase' => $available_for_purchase
			));
		}

		// Determine availability
		if ($in_stock || $unlimited) {
			return 'https://schema.org/InStock';
		} elseif (!$in_stock && $available_for_purchase) {
			// Out of stock but enabled with 0 quantity = PreOrder
			return 'https://schema.org/PreOrder';
		} else {
			// Out of stock and cannot purchase (disabled)
			return 'https://schema.org/OutOfStock';
		}
	}

	/**
	 * Remove default WordPress canonical link for product pages.
	 *
	 * @since 0.6.0
	 */
	public function remove_default_canonical() {
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug) {
			// Remove the default WordPress canonical link action
			remove_action('wp_head', 'rel_canonical');
		}
	}

	/**
	 * Filter canonical URL for product pages.
	 *
	 * @since 0.6.0
	 * @param string $canonical_url The canonical URL.
	 * @param WP_Post $post The post object.
	 * @return string|bool Modified canonical URL or false to remove.
	 */
	public function filter_product_canonical_url($canonical_url, $post) {
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug) {
			// For product pages, use the current URL as canonical
			$current_url = home_url(add_query_arg(array(), $_SERVER['REQUEST_URI']));
			return $current_url;
		}

		return $canonical_url;
	}

	/**
	 * Filter the document title for product pages.
	 *
	 * @since 0.6.1
	 *
	 * @param array $title_parts The document title parts.
	 *
	 * @return array Modified title parts.
	 */
	public function filter_product_page_title($title_parts) {
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug) {
			$product_id = $this->ecwid_api->get_product_id_from_slug($product_slug);

			if ($product_id) {
				$product = $this->ecwid_api->get_product_by_id($product_id);

				if ($product && !empty($product->name)) {
					$title_parts['title'] = $product->name;
				}
			}
		}

		return $title_parts;
	}

	/**
	 * Filter the title for product pages.
	 *
	 * @since 0.6.1
	 *
	 * @param string $title The page title.
	 * @param int    $id    The post ID.
	 *
	 * @return string Modified title.
	 */
	public function filter_product_title($title, $id = null) {
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug && $this->is_product_template_page($id)) {
			$product_id = $this->ecwid_api->get_product_id_from_slug($product_slug);

			if ($product_id) {
				$product = $this->ecwid_api->get_product_by_id($product_id);

				if ($product && !empty($product->name)) {
					return $product->name;
				}
			}
		}

		return $title;
	}

	/**
	 * Filter the excerpt for product pages.
	 *
	 * @since 0.6.1
	 *
	 * @param string $excerpt The page excerpt.
	 * @param object $post    The post object.
	 *
	 * @return string Modified excerpt.
	 */
	public function filter_product_excerpt($excerpt, $post = null) {
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug && $this->is_product_template_page($post ? $post->ID : null)) {
			$product_id = $this->ecwid_api->get_product_id_from_slug($product_slug);

			if ($product_id) {
				// First try to get translated custom description
				$description_text = $this->get_translated_product_description($product_id);

				// Fallback to Ecwid product description if no custom description
				if (empty($description_text)) {
					$product = $this->ecwid_api->get_product_by_id($product_id);
					if ($product && !empty($product->description)) {
						$description_text = wp_strip_all_tags($product->description);
					}
				}

				if (!empty($description_text)) {
					return wp_trim_words($description_text, 30);
				}
			}
		}

		return $excerpt;
	}

	/**
	 * Filter the featured image for product pages.
	 *
	 * @since 0.6.1
	 *
	 * @param array|false  $image         Array of image data, or false if no image.
	 * @param int          $attachment_id Image attachment ID.
	 * @param string|array $size          Requested image size.
	 * @param bool         $icon          Whether the image should be treated as an icon.
	 *
	 * @return array|false Modified image data.
	 */
	public function filter_product_featured_image($image, $attachment_id, $size, $icon) {
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug && $this->is_featured_image_request($attachment_id)) {
			$product_id = $this->ecwid_api->get_product_id_from_slug($product_slug);

			if ($product_id) {
				$product = $this->ecwid_api->get_product_by_id($product_id);

				if ($product && !empty($product->thumbnailUrl)) {
					// Return product image data in WordPress format
					return array(
						$product->thumbnailUrl, // URL
						800, // Width (default)
						600, // Height (default)
						false // Whether it's a resized image
					);
				}
			}
		}

		return $image;
	}

	/**
	 * Check if the current image request is for the page's featured image.
	 *
	 * @since 0.6.1
	 *
	 * @param int $attachment_id The attachment ID being requested.
	 *
	 * @return bool True if this is a featured image request.
	 */
	private function is_featured_image_request($attachment_id) {
		// Get the template page ID
		$template_page_id = $this->get_template_page_id();

		if (!$template_page_id) {
			return false;
		}

		// Check if the attachment ID matches the page's featured image
		$page_thumbnail_id = get_post_thumbnail_id($template_page_id);

		return $attachment_id == $page_thumbnail_id;
	}

	/**
	 * Check if the current page is the product template page.
	 *
	 * @since 0.6.1
	 *
	 * @param int $page_id The page ID to check.
	 *
	 * @return bool True if it's the product template page.
	 */
	private function is_product_template_page($page_id = null) {
		if ($page_id === null) {
			$page_id = get_the_ID();
		}

		$template_page_id = $this->get_template_page_id();
		return $template_page_id && $page_id == $template_page_id;
	}

	/**
	 * Get the template page ID from settings.
	 *
	 * @since 0.6.1
	 *
	 * @return int|null Template page ID or null if not set.
	 */
	private function get_template_page_id() {
		// Get settings from the settings manager
		$settings_manager = Peaches_Ecwid_Settings::get_instance();
		$settings = $settings_manager->get_settings();

		$template_page_id = isset($settings['product_template_page']) ? absint($settings['product_template_page']) : 0;

		if ($template_page_id > 0) {
			// Verify the page still exists
			$page = get_post($template_page_id);
			if ($page && $page->post_type === 'page' && $page->post_status === 'publish') {
				return $template_page_id;
			}
		}

		// Fallback to auto-generated page if setting is empty or page doesn't exist
		$template_page = get_page_by_path('product-detail');
		return $template_page ? $template_page->ID : null;
	}

	/**
	 * Get translated product description for SEO purposes.
	 *
	 * @since 0.6.1
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return string Translated description text or empty string.
	 */
	private function get_translated_product_description($product_id) {
		// Get the main plugin instance
		$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
		if (!$ecwid_blocks) {
			return '';
		}

		// Get the product settings manager
		$product_settings_manager = $ecwid_blocks->get_product_settings_manager();
		if (!$product_settings_manager || !method_exists($product_settings_manager, 'get_product_descriptions_with_translations')) {
			return '';
		}

		try {
			// Get current language
			$current_language = Peaches_Ecwid_Utilities::get_current_language();

			// Get translated descriptions
			$descriptions = $product_settings_manager->get_product_descriptions_with_translations($product_id, $current_language);

			// Look for a suitable description to use for SEO
			// Priority: custom -> features -> usage -> ingredients
			$preferred_types = array('custom', 'features', 'usage', 'ingredients');

			foreach ($preferred_types as $type) {
				foreach ($descriptions as $description) {
					if ($description['type'] === $type && !empty($description['content'])) {
						// Strip HTML tags and return
						return wp_strip_all_tags($description['content']);
					}
				}
			}

			// If no preferred types found, use the first available description
			foreach ($descriptions as $description) {
				if (!empty($description['content'])) {
					return wp_strip_all_tags($description['content']);
				}
			}
		} catch (Exception $e) {
			// Log error but don't break the page
			$this->log_error('Error getting translated product description', array('error' => $e->getMessage()));
		}

		return '';
	}

	/**
	 * Set additional Ecwid configuration.
	 *
	 * @since 0.1.2
	 */
	public function set_ecwid_config() {
?>
	<script type="text/javascript">
		window.ec = window.ec || Object();
		window.ec.config = window.ec.config || Object();
		window.ec.config.disable_ajax_navigation = true;
		</script>
<?php
	}

	/**
	 * Force rewrite rules update when needed.
	 *
	 * @since 0.1.2
	 */
	public function check_rewrite_rules() {
		// Check if we need to update rewrite rules.
		$activated = get_option( 'peaches_ecwid_activated', 0 );
		$flushed   = get_option( 'peaches_ecwid_flushed', 0 );

		if ( $activated > $flushed ) {
			flush_rewrite_rules( false );
			update_option( 'peaches_ecwid_flushed', time() );
		}
	}

	/**
	 * Log informational messages.
	 *
	 * @since 0.7.0
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_info( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) && Peaches_Ecwid_Utilities::is_debug_mode() ) {
			Peaches_Ecwid_Utilities::log_error( '[INFO] [Rewrite Manager] ' . $message, $context );
		}
	}

	/**
	 * Log error messages.
	 *
	 * @since 0.7.0
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_error( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) ) {
			Peaches_Ecwid_Utilities::log_error( '[Rewrite Manager] ' . $message, $context );
		} else {
			// Fallback logging if utilities class is not available
			error_log( '[Peaches Ecwid] [Rewrite Manager] ' . $message . ( empty( $context ) ? '' : ' - Context: ' . wp_json_encode( $context ) ) );
		}
	}
}
