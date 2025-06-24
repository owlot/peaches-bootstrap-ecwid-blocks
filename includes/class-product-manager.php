<?php
/**
 * Product Manager class
 *
 * Handles operations related to Ecwid products.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

// Include the interface
require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-product-manager.php';

/**
 * Class Peaches_Product_Manager
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
class Peaches_Product_Manager implements Peaches_Product_Manager_Interface {
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
	public function __construct($ecwid_api) {
		$this->ecwid_api = $ecwid_api;

		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 0.1.2
	 */
	private function init_hooks() {
		add_action('init', array($this, 'register_blocks'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'), 5);

		$this->init_ajax_handlers();
	}

	/**
	 * Initialize AJAX handlers for product data.
	 *
	 * @since 0.1.2
	 */
	public function init_ajax_handlers() {
		// AJAX handlers
		add_action('wp_ajax_get_ecwid_product_descriptions', array($this, 'ajax_get_product_descriptions'));
		add_action('wp_ajax_nopriv_get_ecwid_product_descriptions', array($this, 'ajax_get_product_descriptions'));
	}

	/**
	 * Register block types related to products.
	 *
	 * @since 0.1.2
	 */
	public function register_blocks() {
		// Register block metadata collection
		wp_register_block_metadata_collection(
			PEACHES_ECWID_PLUGIN_DIR . 'dist',
			PEACHES_ECWID_PLUGIN_DIR . 'dist/blocks-manifest.php'
		);

		// Register all blocks
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-category');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-detail/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-description/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-field/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-add-to-cart/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-images/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-ingredients/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-gallery-image/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-related-products/');
	}

	/**
	 * Enqueue scripts and styles for the admin.
	 *
	 * @since 0.1.2
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_scripts($hook) {
		// Get the current screen
		$screen = get_current_screen();

		// Only on block editor
		if ($screen && $screen->is_block_editor) {
			// Enqueue wp-api-fetch for REST API calls
			wp_enqueue_script('wp-api-fetch');

			// Enqueue wp-api for REST API functionality
			wp_enqueue_script('wp-api');

			// Create REST API nonce
			$rest_nonce = wp_create_nonce('wp_rest');

			// Provide REST API settings for blocks
			wp_add_inline_script(
				'wp-api-fetch',
				'window.wpApiSettings = window.wpApiSettings || {};
				window.wpApiSettings.root = "' . esc_url_raw(rest_url()) . '";
				window.wpApiSettings.nonce = "' . $rest_nonce . '";

				// Initialize wp.apiFetch with the settings
				if (window.wp && window.wp.apiFetch) {
					window.wp.apiFetch.use(window.wp.apiFetch.createNonceMiddleware("' . $rest_nonce . '"));
					window.wp.apiFetch.use(window.wp.apiFetch.createRootURLMiddleware("' . esc_url_raw(rest_url()) . '"));
				}

				console.log("REST API settings initialized:", window.wpApiSettings);',
				'after'
			);

			// Keep legacy settings for backward compatibility (product popup, etc.)
			wp_add_inline_script(
				'wp-blocks',
				'window.EcwidGutenbergParams = window.EcwidGutenbergParams || {};
				window.EcwidGutenbergParams.restUrl = "' . esc_url_raw(rest_url('peaches/v1/')) . '";
				window.EcwidGutenbergParams.restNonce = "' . $rest_nonce . '";
				window.EcwidGutenbergParams.chooseProduct = "' . __('Choose Product', 'peaches') . '";
				window.EcwidGutenbergParams.products = {};

				console.log("Ecwid REST params initialized");',
				'before'
			);
		}
	}

	/**
	 * Enqueue scripts and styles for the frontend.
	 *
	 * @since 0.1.2
	 */
	public function enqueue_frontend_scripts() {
		// Only on frontend
		if (is_admin()) return;

		// Ensure required scripts are enqueued
		wp_enqueue_script('wp-interactivity');

		// Get the shop path segment using the utility function
		$shop_path_segment = Peaches_Ecwid_Utilities::get_shop_path(true, true); // Include parents, with trailing slash

		// Get the store page ID
		$store_page_id = get_option('ecwid_store_page_id');

		// Get the correct store URL
		$store_url = $store_page_id ? get_permalink($store_page_id) : home_url();

		// Provide REST API settings for frontend blocks
		wp_add_inline_script(
			'wp-interactivity',
			'window.EcwidSettings = window.EcwidSettings || {};
			window.EcwidSettings.restUrl = "' . esc_url_raw(rest_url('peaches/v1/')) . '";
			window.EcwidSettings.restRoot = "' . esc_url_raw(rest_url()) . '";
			window.EcwidSettings.storePageId = "' . $store_page_id . '";
			window.EcwidSettings.storeUrl = "' . esc_url_raw($store_url) . '";
			window.EcwidSettings.shopPathSegment = "' . esc_js($shop_path_segment) . '";

			// Keep legacy AJAX settings for backward compatibility (if needed by other parts)
			window.EcwidSettings.ajaxUrl = "' . admin_url('admin-ajax.php') . '";
			window.EcwidSettings.ajaxNonce = "' . wp_create_nonce('get_ecwid_product_data') . '";

			console.log("Frontend REST API settings loaded:", window.EcwidSettings);',
			'before'
		);
	}

	/**
	 * Build a complete product URL.
	 *
	 * @since 0.1.2
	 * @param object $product The product object.
	 * @param string $lang    The language code.
	 * @return string         The complete product URL.
	 */
	public function build_product_url($product, $lang = '') {
		if (!$product || !isset($product->autogeneratedSlug)) {
			return '';
		}

		// If no specific language is provided, get current language
		if (empty($lang)) {
			$lang = Peaches_Ecwid_Utilities::get_current_language();
		}

		// Get the default language
		$default_lang = '';
		if (function_exists('pll_default_language')) {
			$default_lang = pll_default_language('slug');
		} elseif (defined('ICL_LANGUAGE_CODE') && class_exists('SitePress')) {
			global $sitepress;
			if ($sitepress) {
				$default_lang = $sitepress->get_default_language();
			}
		}

		// Get the base URL - this will be different depending on the language
		$base_url = home_url();

		// For non-default languages, get language-specific home URL
		if (!empty($lang) && $lang !== $default_lang) {
			// For Polylang
			if (function_exists('pll_home_url')) {
				$base_url = pll_home_url($lang);
			}
			// For WPML
			elseif (defined('ICL_LANGUAGE_CODE') && class_exists('SitePress')) {
				$languages = apply_filters('wpml_active_languages', null);
				if (isset($languages[$lang]['url'])) {
					$base_url = $languages[$lang]['url'];
				}
			}
			// Fallback - manually add language prefix
			else {
				$base_url = home_url($lang);
			}
		}

		// Ensure base URL ends with slash
		if (!preg_match('/\/$/', $base_url)) {
			$base_url .= '/';
		}

		// Get the shop path for the specified language
		$shop_path = Peaches_Ecwid_Utilities::get_shop_path(true, true, $lang);

		// Build the complete URL
		$url = $base_url . $shop_path . $product->autogeneratedSlug . '/';

		return $url;
	}

	/**
	 * Generate breadcrumb navigation for product detail page.
	 *
	 * @since 0.1.2
	 * @param object $product The product object.
	 * @return string HTML for breadcrumbs.
	 */
	public function generate_breadcrumbs($product) {
		if (!$product) {
			return '';
		}

		// Get store page URL - this should be the main page where your store is embedded
		$store_page_id = get_option('ecwid_store_page_id');
		$store_url = $store_page_id ? get_permalink($store_page_id) : home_url();
		$store_name = __('Shop', 'peaches');

		// Check if product has category info
		$category_name = '';
		$category_url = '';
		if (isset($product->categoryIds) && !empty($product->categoryIds) && is_array($product->categoryIds)) {
			$category_id = $product->categoryIds[0]; // Use first category

			// Try to get category info
			$api = new Ecwid_Api_V3();
			$category = $api->get_category($category_id);

			if ($category) {
				$category_name = $category->name;
				$category_url = $category->url;
			}
		}

		// Build breadcrumb HTML
		ob_start();
?>
	<nav aria-label="breadcrumb">
	<ol class="breadcrumb">
	<!-- Home link -->
	<li class="breadcrumb-item"><a href="<?php echo esc_url(home_url()); ?>"><?php echo __('Home', 'peaches'); ?></a></li>

	<!-- Store link -->
	<li class="breadcrumb-item"><a href="<?php echo esc_url($store_url); ?>"><?php echo esc_html($store_name); ?></a></li>

	<!-- Category link (if available) -->
	<?php if ($category_name && $category_url): ?>
	<li class="breadcrumb-item"><a href="<?php echo esc_url($category_url); ?>"><?php echo esc_html($category_name); ?></a></li>
	<?php endif; ?>

		<!-- Current product -->
			<li class="breadcrumb-item active" aria-current="page"><?php echo esc_html($product->name); ?></li>
			</ol>
			</nav>
<?php
		return ob_get_clean();
	}
}
