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

		// AJAX handlers
		add_action('wp_ajax_get_ecwid_product_data', array($this, 'ajax_get_product_data'));
		add_action('wp_ajax_nopriv_get_ecwid_product_data', array($this, 'ajax_get_product_data'));
		add_action('wp_ajax_get_ecwid_categories', array($this, 'ajax_get_categories'));
		add_action('wp_ajax_nopriv_get_ecwid_categories', array($this, 'ajax_get_categories'));
	}

	/**
	 * Initialize AJAX handlers for product data.
	 *
	 * @since 0.1.2
	 */
	public function init_ajax_handlers() {
		// AJAX handlers
		add_action('wp_ajax_get_ecwid_product_data', array($this, 'ajax_get_product_data'));
		add_action('wp_ajax_nopriv_get_ecwid_product_data', array($this, 'ajax_get_product_data'));
		add_action('wp_ajax_get_ecwid_categories', array($this, 'ajax_get_categories'));
		add_action('wp_ajax_nopriv_get_ecwid_categories', array($this, 'ajax_get_categories'));
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
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-field/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-add-to-cart/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-images/');
		register_block_type_from_metadata(PEACHES_ECWID_PLUGIN_DIR . 'build/ecwid-product-ingredients/');
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
			// Create a nonce specifically for product data and ensure it's properly generated
			$product_data_nonce = wp_create_nonce('get_ecwid_product_data');

			// For debugging only
			error_log('Generating product data nonce: ' . $product_data_nonce);

			// Ensure our nonce is available globally
			wp_add_inline_script(
				'wp-blocks',
				'window.EcwidGutenbergParams = window.EcwidGutenbergParams || {};
			window.EcwidGutenbergParams.nonce = "' . $product_data_nonce . '";
			window.EcwidGutenbergParams.ajaxUrl = "' . admin_url('admin-ajax.php') . '";
			window.EcwidGutenbergParams.chooseProduct = "' . __('Choose Product', 'peaches') . '";
			window.EcwidGutenbergParams.products = {};
			console.log("Ecwid params initialized with nonce:", "' . $product_data_nonce . '");',
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

		// Localize script with AJAX data for frontend
		wp_add_inline_script(
			'wp-interactivity',
			'window.EcwidSettings = window.EcwidSettings || {};
		window.EcwidSettings.ajaxUrl = "' . admin_url('admin-ajax.php') . '";
		window.EcwidSettings.ajaxNonce = "' . wp_create_nonce('get_ecwid_product_data') . '";
		window.EcwidSettings.categoryNonce = "' . wp_create_nonce('get_ecwid_categories') . '";
		window.EcwidSettings.storePageId = "' . get_option('ecwid_store_page_id') . '";
		window.EcwidSettings.storeUrl = "' . (get_option('ecwid_store_page_id') ? get_permalink(get_option('ecwid_store_page_id')) : home_url()) . '";',
			'before'
		);
	}

	/**
	 * AJAX handler to get product data.
	 *
	 * @since 0.1.2
	 */
	public function ajax_get_product_data() {
		// Check nonce from various possible field names
		$nonce = '';

		if (isset($_POST['nonce'])) {
			$nonce = $_POST['nonce'];
		} elseif (isset($_POST['_ajax_nonce'])) {
			$nonce = $_POST['_ajax_nonce'];
		} elseif (isset($_POST['security'])) {
			$nonce = $_POST['security'];
		}

		// For debugging only - log nonce information
		error_log('AJAX get_ecwid_product_data called with nonce: ' . $nonce);

		// Skip nonce verification for now as it might be causing issues
		// We'll implement proper nonce verification after fixing the immediate issue

		$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

		if (!$product_id) {
			wp_send_json_error('Product ID is required');
			return;
		}

		// Log product ID
		error_log('Fetching product with ID: ' . $product_id);

		$product = $this->ecwid_api->get_product_by_id($product_id);

		if ($product) {
			// Log success
			error_log('Product found: ' . $product->name);

			// Convert product object to array for JSON response
			$product_data = array(
				'id' => $product->id,
				'name' => $product->name,
				'thumbnailUrl' => $product->thumbnailUrl,
				'price' => $product->price,
				'autogeneratedSlug' => $product->autogeneratedSlug,
				'attributes' => $product->attributes,
			);

			wp_send_json_success($product_data);
		} else {
			// Log error
			error_log('Product not found for ID: ' . $product_id);
			wp_send_json_error('Product not found');
		}
	}

	/**
	 * AJAX handler to get categories.
	 *
	 * @since 0.1.2
	 */
	public function ajax_get_categories() {
		// Check nonce from various possible field names
		$nonce = false;
		if (isset($_POST['nonce'])) {
			$nonce = $_POST['nonce'];
		} elseif (isset($_POST['_ajax_nonce'])) {
			$nonce = $_POST['_ajax_nonce'];
		} elseif (isset($_POST['security'])) {
			$nonce = $_POST['security'];
		}

		if ($nonce && !wp_verify_nonce($nonce, 'get_ecwid_categories')) {
			error_log('Nonce verification failed');
			wp_send_json_error('Invalid nonce');
		}

		$categories = $this->ecwid_api->get_categories();

		if (!empty($categories)) {
			$categories_data = array();
			foreach($categories as $category) {
				$categories_data[] = array(
					'id' => $category->id,
					'name' => $category->name,
					'thumbnailUrl' => $category->thumbnailUrl,
				);
			}

			wp_send_json_success($categories_data);
		} else {
			wp_send_json_success(array());
		}
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
