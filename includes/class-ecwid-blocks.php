<?php
/**
 * Main plugin class with error handling
 *
 * Handles the initialization and coordination of all plugin components.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Ecwid_Blocks
 *
 * Main plugin class implementing the singleton pattern.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Ecwid_Blocks {
	/**
	 * Singleton instance of the class.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Ecwid_Blocks|null
	 */
	private static $instance = null;

	/**
	 * Block registration instance.
	 *
	 * @since  0.3.0
	 * @access private
	 * @var    Peaches_Block_Registration_Interface
	 */
	private $block_registration;

	/**
	 * Ecwid API instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Rewrite Manager instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Rewrite_Manager_Interface
	 */
	private $rewrite_manager;

	/**
	 * Product Manager instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Product_Manager_Interface
	 */
	private $product_manager;

	/**
	 * Product Settings Manager instance (formerly Ingredients Manager).
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Ingredients_Manager_Interface
	 */
	private $product_settings_manager;

	/**
	 * Product Lines Manager instance (replaces Product Groups).
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Product_Lines_Manager
	 */
	private $product_lines_manager;

	/**
	 * Product Media Manager instance.
	 *
	 * @since  0.2.1
	 * @access private
	 * @var    Peaches_Product_Media_Manager
	 */
	private $product_media_manager;

	/**
	 * Ingredients Manager instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Ingredients_Library_Manager
	 */
	private $ingredients_library_manager;

	/**
	 * Settings Manager instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Ecwid_Settings
	 */
	private $settings_manager;

	/**
	 * Block patterns instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Ecwid_Block_Patterns_Interface
	 */
	private $block_patterns;

	/**
	 * Enhanced Navigation instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Enhanced_Navigation
	 */
	private $enhanced_navigation;

	/**
	 * Media Tags Manager instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Media_Tags_Manager
	 */
	private $media_tags_manager;

	/**
	 * REST API instance.
	 *
	 * @since  0.2.5
	 * @access private
	 * @var    Peaches_REST_API
	 */
	private $rest_api;

	/**
	 * Get singleton instance of the class.
	 *
	 * @since 0.2.0
	 * @return Peaches_Ecwid_Blocks The singleton instance.
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent external instantiation.
	 *
	 * Initializes the plugin by defining constants, loading dependencies,
	 * and initializing components.
	 *
	 * @since 0.2.0
	 */
	private function __construct() {
		try {
			$this->load_dependencies();
			$this->maybe_migrate_database();
			$this->initialize_components();
			$this->init_hooks();

			delete_transient('peaches_ecwid_all_products_6ed855dcb1ec6aec9d8ea36c935c6225');
		} catch (Exception $e) {
			error_log('Peaches Ecwid Blocks initialization error: ' . $e->getMessage());
			add_action('admin_notices', array($this, 'show_initialization_error'));
		}
	}

	/**
	 * Show initialization error notice.
	 */
	public function show_initialization_error() {
?>
		<div class="notice notice-error">
			<p><?php _e('Peaches Ecwid Blocks failed to initialize properly. Please check the error log for details.', 'peaches'); ?></p>
		</div>
<?php
	}

	/**
	 * Load plugin dependencies.
	 *
	 * Includes required interface and class files.
	 *
	 * @since 0.2.0
	 */
	private function load_dependencies() {
		// Load interfaces first
		$interfaces = array(
			'interfaces/interface-block-registration.php',
			'interfaces/interface-ecwid-api.php',
			'interfaces/interface-rewrite-manager.php',
			'interfaces/interface-product-manager.php',
			'interfaces/interface-block-patterns.php',
			'interfaces/interface-media-tags-manager.php',
			'interfaces/interface-product-media-manager.php',
			'interfaces/interface-product-lines-manager.php',
			'interfaces/interface-enhanced-navigation.php'
		);

		foreach ($interfaces as $interface) {
			$file_path = PEACHES_ECWID_INCLUDES_DIR . $interface;
			if (file_exists($file_path)) {
				require_once $file_path;
			} else {
				error_log('Missing interface file: ' . $file_path);
			}
		}

		// Load classes
		$classes = array(
			'class-utilities.php',
			'class-block-registration.php',
			'class-ecwid-api.php',
			'class-ecwid-sitemap-manager.php',
			'class-rewrite-manager.php',
			'class-enhanced-navigation.php',
			'class-product-manager.php',
			'class-ingredients-library-manager.php',
			'class-ecwid-settings.php',
			'class-ecwid-product-settings.php',
			'class-block-patterns.php',
			'class-db-migration.php',
			'class-product-lines-manager.php',
			'class-product-settings-manager.php',
			'class-product-media-manager.php',
			'class-media-tags-manager.php',
			'class-ecwid-image-utilities.php',
			'class-rest-api.php',
		);

		foreach ($classes as $class_file) {
			$file_path = PEACHES_ECWID_INCLUDES_DIR . $class_file;
			if (file_exists($file_path)) {
				require_once $file_path;
			} else {
				error_log('Missing class file: ' . $file_path);
			}
		}

		// Include template functions
		require_once PEACHES_ECWID_INCLUDES_DIR . 'template-functions.php';
	}

	/**
	 * Run database migration if needed.
	 *
	 * @since 0.2.0
	 */
	private function maybe_migrate_database() {
		if (class_exists('Peaches_Ecwid_DB_Migration')) {
			Peaches_Ecwid_DB_Migration::maybe_migrate();
		}
	}

	/**
	 * Initialize plugin components.
	 *
	 * Creates instances of required classes and injects dependencies.
	 *
	 * @since 0.2.0
	 */
	private function initialize_components() {
		// Initialize API first as it's needed by other components
		if (class_exists('Peaches_Ecwid_API')) {
			$this->ecwid_api = new Peaches_Ecwid_API();
		}

		// Initialize settings manager early so other components can access it
		if (class_exists('Peaches_Ecwid_Settings')) {
			$this->settings_manager = Peaches_Ecwid_Settings::get_instance();
		}

		// Initialize media tags manager
		if (class_exists('Peaches_Media_Tags_Manager')) {
			$this->media_tags_manager = new Peaches_Media_Tags_Manager();
		}

		// Initialize product media manager
		if (class_exists('Peaches_Product_Media_Manager') && $this->ecwid_api && $this->media_tags_manager) {
			$this->product_media_manager = new Peaches_Product_Media_Manager($this->ecwid_api, $this->media_tags_manager);
		}

		// Initialize product lines manager
		if (class_exists('Peaches_Product_Lines_Manager')) {
			$this->product_lines_manager = new Peaches_Product_Lines_Manager();
		}

		if (class_exists('Peaches_Ingredients_Library_Manager')) {
			$this->ingredients_library_manager = new Peaches_Ingredients_Library_Manager();
		}

		// Initialize product settings manager
		if (class_exists('Peaches_Product_Settings_Manager') && $this->ecwid_api) {
			$this->product_settings_manager = new Peaches_Product_Settings_Manager($this->ecwid_api, $this->product_lines_manager, $this->product_media_manager);
		}

		// Initialize other components with dependencies
		if (class_exists('Peaches_Rewrite_Manager') && $this->ecwid_api) {
			$this->rewrite_manager = new Peaches_Rewrite_Manager($this->ecwid_api);
		}

		if (class_exists('Peaches_Product_Manager') && $this->ecwid_api) {
			$this->product_manager = new Peaches_Product_Manager($this->ecwid_api);
		}

		// Initialize enhanced navigation if available
		if (class_exists('Peaches_Enhanced_Navigation')) {
			$this->enhanced_navigation = new Peaches_Enhanced_Navigation();
		}

		// Initialize patterns last and only if we're not in an AJAX request
		if (!wp_doing_ajax() && class_exists('Peaches_Ecwid_Block_Patterns')) {
			$this->block_patterns = new Peaches_Ecwid_Block_Patterns();
		}

		// Initialize REST API
		if (class_exists('Peaches_REST_API') &&
		    $this->product_settings_manager &&
		    $this->media_tags_manager &&
		    $this->product_media_manager &&
		    $this->ecwid_api &&
		    $this->product_manager &&
		    $this->product_lines_manager) {
			$this->rest_api = new Peaches_REST_API(
				$this->product_settings_manager,
				$this->media_tags_manager,
				$this->product_media_manager,
				$this->ecwid_api,
				$this->product_manager,
				$this->product_lines_manager
			);
		}

		// Initialize block registration
		if (class_exists('Peaches_Ecwid_Block_Registration')) {
			$this->block_registration = new Peaches_Ecwid_Block_Registration();
		}

		$this->init_mollie_subscription();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 0.2.0
	 */
	private function init_hooks() {
		// Load text domain on init hook instead of immediately to avoid the "too early" error
		add_action('init', array($this, 'load_textdomain'), 0);

		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// Add AJAX handlers
		add_action('wp_ajax_search_ecwid_products', array($this, 'ajax_search_products'));
		add_action('wp_ajax_get_ecwid_product_data', array($this, 'ajax_get_product_data'));
		add_action('wp_ajax_mark_product_media', array($this, 'ajax_mark_product_media'));

		// Plugin activation and deactivation
		add_action('activate_plugin', array($this, 'activate'));
		add_action('deactivate_plugin', array($this, 'deactivate'));
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @since 0.3.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'peaches-bootstrap-ecwid-blocks',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 0.2.0
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

		// Localize script with AJAX data for frontend
		wp_add_inline_script(
			'wp-interactivity',
			'window.EcwidSettings = window.EcwidSettings || {};
			window.EcwidSettings.ajaxUrl = "' . admin_url('admin-ajax.php') . '";
			window.EcwidSettings.ajaxNonce = "' . wp_create_nonce('get_ecwid_product_data') . '";
			window.EcwidSettings.categoryNonce = "' . wp_create_nonce('get_ecwid_categories') . '";
			window.EcwidSettings.storePageId = "' . $store_page_id . '";
			window.EcwidSettings.storeUrl = "' . $store_url . '";
			window.EcwidSettings.shopPath = "' . esc_js($shop_path_segment) . '";',
			'before'
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 0.2.0
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_scripts($hook) {
		// Get the current screen
		$screen = get_current_screen();

		// Only on block editor
		if ($screen && $screen->is_block_editor) {
			// Create a nonce specifically for product data and ensure it's properly generated
			$product_data_nonce = wp_create_nonce('get_ecwid_product_data');

			// Ensure our nonce is available globally
			wp_add_inline_script(
				'wp-blocks',
				'window.EcwidGutenbergParams = window.EcwidGutenbergParams || {};
			window.EcwidGutenbergParams.nonce = "' . $product_data_nonce . '";
			window.EcwidGutenbergParams.ajaxUrl = "' . admin_url('admin-ajax.php') . '";
			window.EcwidGutenbergParams.chooseProduct = "' . __('Choose Product', 'peaches') . '";
			window.EcwidGutenbergParams.products = {};',
				'before'
			);
		}
	}

	/**
	 * AJAX handler to search products.
	 *
	 * @since 0.2.0
	 */
	public function ajax_search_products() {
		check_ajax_referer('search_ecwid_products', 'nonce');

		$search_term = sanitize_text_field($_POST['search_term']);
		$products = $this->ecwid_api->search_products($search_term);

		wp_send_json_success($products);
	}

	/**
	 * AJAX handler to get product data.
	 *
	 * @since 0.2.0
	 */
	public function ajax_get_product_data() {
		check_ajax_referer('get_ecwid_product_data', 'nonce');

		$product_id = absint($_POST['product_id']);
		$product = $this->ecwid_api->get_product($product_id);

		if ($product) {
			wp_send_json_success($product);
		} else {
			wp_send_json_error('Product not found');
		}
	}

	/**
	 * AJAX handler to mark product media.
	 *
	 * @since 0.2.0
	 */
	public function ajax_mark_product_media() {
		check_ajax_referer('search_ecwid_products', 'nonce');

		$attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;

		if ($attachment_id) {
			update_post_meta($attachment_id, '_peaches_product_media', true);
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Initialize Mollie subscription functionality
	 *
	 * @since 0.4.0
	 */
	private function init_mollie_subscription() {
		// Check if any Mollie plugin is available
		if ($this->is_mollie_available()) {
			require_once PEACHES_ECWID_INCLUDES_DIR . 'class-mollie-subscription-block.php';

			new Peaches_Mollie_Subscription_Block($this->ecwid_api, $this->cache);
		}
	}

	/**
	 * Check if Mollie integration is available
	 *
	 * @since 0.4.0
	 *
	 * @return bool True if Mollie plugin is available
	 */
	private function is_mollie_available() {
			   class_exists('Mollie\\Api\\MollieApiClient');
	}

	/**
	 * Plugin activation handler.
	 *
	 * @since 0.2.0
	 */
	public function activate() {
		// Flush rewrite rules on activation
		if ($this->rewrite_manager) {
			flush_rewrite_rules();
		}

		$this->create_mollie_tables();
	}

	/**
	 * Plugin deactivation handler.
	 *
	 * @since 0.2.0
	 */
	public function deactivate() {
		// Flush rewrite rules on deactivation
		flush_rewrite_rules();
	}

	// Getter methods with null checks

	public function get_ecwid_api() {
		return $this->ecwid_api;
	}

	public function get_rewrite_manager() {
		return $this->rewrite_manager;
	}

	public function get_product_manager() {
		return $this->product_manager;
	}

	public function get_product_settings_manager() {
		return $this->product_settings_manager;
	}

	public function get_product_lines_manager() {
		return $this->product_lines_manager;
	}

	public function get_product_media_manager() {
		return $this->product_media_manager;
	}

	public function get_ingredients_library_manager() {
		return $this->ingredients_library_manager;
	}

	public function get_settings_manager() {
		return $this->settings_manager;
	}

	public function get_enhanced_navigation() {
		return $this->enhanced_navigation;
	}

	// Backward compatibility methods

	public function get_ingredients_manager() {
		return $this->product_settings_manager;
	}

	public function get_product_group_manager() {
		// Redirect to lines manager for backward compatibility
		return $this->product_lines_manager;
	}

	/**
	 * Get Media Tags Manager instance.
	 *
	 * @return Peaches_Media_Tags_Manager|null
	 */
	public function get_media_tags_manager() {
		return $this->media_tags_manager;
	}

	/**
	 * Get REST API instance.
	 *
	 * @since 0.2.5
	 * @return Peaches_REST_API|null
	 */
	public function get_rest_api() {
		return $this->rest_api;
	}

	/**
	 * Create Mollie subscriptions table on activation
	 *
	 * @since 0.4.0
	 */
	private function create_mollie_tables() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'peaches_mollie_subscriptions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			subscription_id varchar(255) NOT NULL,
			product_id bigint(20) unsigned NOT NULL,
			customer_email varchar(255) NOT NULL,
			plan_data longtext NOT NULL,
			customer_data longtext NOT NULL,
			status varchar(50) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY subscription_id (subscription_id),
			KEY product_id (product_id),
			KEY customer_email (customer_email),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}
