<?php
/**
 * Main plugin class
 *
 * Handles the initialization and coordination of all plugin components.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

// Include the interface
require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-ecwid-blocks.php';

/**
 * Class Peaches_Ecwid_Blocks
 *
 * Main plugin class implementing the singleton pattern.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
class Peaches_Ecwid_Blocks implements Peaches_Ecwid_Blocks_Interface {
	/**
	 * Singleton instance of the class.
	 *
	 * @since  0.1.2
	 * @access private
	 * @var    Peaches_Ecwid_Blocks|null
	 */
	private static $instance = null;

	/**
	 * Ecwid API instance.
	 *
	 * @since  0.1.2
	 * @access private
	 * @var    Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Rewrite Manager instance.
	 *
	 * @since  0.1.2
	 * @access private
	 * @var    Peaches_Rewrite_Manager_Interface
	 */
	private $rewrite_manager;

	/**
	 * Product Manager instance.
	 *
	 * @since  0.1.2
	 * @access private
	 * @var    Peaches_Product_Manager_Interface
	 */
	private $product_manager;

	/**
	 * Ingredients Manager instance.
	 *
	 * @since  0.1.2
	 * @access private
	 * @var    Peaches_Ingredients_Manager_Interface
	 */
	private $ingredients_manager;

	/**
	 * Master Ingredients Manager instance.
	 *
	 * @since  0.1.2
	 * @access private
	 * @var    Peaches_Master_Ingredients_Manager
	 */
	private $master_ingredients_manager;

	/**
	 * Get singleton instance of the class.
	 *
	 * @since 0.1.2
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
	 * @since 0.1.2
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->initialize_components();
		$this->init_hooks();
	}

	/**
	 * Load plugin dependencies.
	 *
	 * Includes required interface and class files.
	 *
	 * @since 0.1.2
	 */
	private function load_dependencies() {
		// Load interfaces
		require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-ecwid-api.php';
		require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-rewrite-manager.php';
		require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-product-manager.php';
		require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-ingredients-manager.php';

		// Load classes
		require_once PEACHES_ECWID_INCLUDES_DIR . 'class-utilities.php';
		require_once PEACHES_ECWID_INCLUDES_DIR . 'class-ecwid-api.php';
		require_once PEACHES_ECWID_INCLUDES_DIR . 'class-rewrite-manager.php';
		require_once PEACHES_ECWID_INCLUDES_DIR . 'class-product-manager.php';
		require_once PEACHES_ECWID_INCLUDES_DIR . 'class-ingredients-manager.php';
		require_once PEACHES_ECWID_INCLUDES_DIR . 'class-master-ingredients-manager.php';
	}

	/**
	 * Initialize plugin components.
	 *
	 * Creates instances of required classes and injects dependencies.
	 *
	 * @since 0.1.2
	 */
	private function initialize_components() {
		// Initialize API first as it's needed by other components
		$this->ecwid_api = new Peaches_Ecwid_API();

		// Initialize other components with dependencies
		$this->rewrite_manager = new Peaches_Rewrite_Manager($this->ecwid_api);
		$this->product_manager = new Peaches_Product_Manager($this->ecwid_api);
		$this->ingredients_manager = new Peaches_Ingredients_Manager($this->ecwid_api);
		$this->master_ingredients_manager = new Peaches_Master_Ingredients_Manager();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 0.1.2
	 */
	private function init_hooks() {
		// Plugin core hooks
		add_action('plugins_loaded', array($this, 'load_textdomain'));
		add_action('admin_init', array($this, 'check_ecwid_plugin'));
		add_filter('plugin_action_links_' . plugin_basename(PEACHES_ECWID_PATH . 'peaches-bootstrap-ecwid-blocks.php'), array($this, 'add_settings_link'));
	}

	/**
	 * Plugin activation hook.
	 *
	 * @since 0.1.2
	 */
	public function activate() {
		// Create the product detail page if needed
		$this->rewrite_manager->register_product_template();

		// Flush rewrite rules
		flush_rewrite_rules(true);

		// Store activation timestamp for cache busting
		update_option('peaches_ecwid_activated', time());
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @since 0.1.2
	 */
	public function deactivate() {
		// Flush rewrite rules to remove our custom ones
		flush_rewrite_rules(true);
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @since 0.1.2
	 */
	public function load_textdomain() {
		load_plugin_textdomain('peaches', false, dirname(plugin_basename(PEACHES_ECWID_PATH)) . '/languages');
	}

	/**
	 * Check if Ecwid plugin is active.
	 *
	 * @since 0.1.2
	 */
	public function check_ecwid_plugin() {
		if (!class_exists('Ecwid_Store_Page') && !class_exists('EcwidPlatform')) {
			add_action('admin_notices', function() {
?>
	<div class="notice notice-error">
	<p><?php _e('Peaches Ecwid Custom Product Pages requires the Ecwid Ecommerce Shopping Cart plugin to be installed and activated.', 'peaches'); ?></p>
	</div>
<?php
			});
			return false;
		}
		return true;
	}

	/**
	 * Add settings link to plugin page.
	 *
	 * @since 0.1.2
	 * @param array $links Current plugin links.
	 * @return array Modified plugin links.
	 */
	public function add_settings_link($links) {
		$settings_link = '<a href="' . admin_url('admin.php?page=ec-store') . '">' . __('Ecwid Settings', 'peaches') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Get Ecwid API instance.
	 *
	 * @since 0.1.2
	 * @return Peaches_Ecwid_API_Interface The Ecwid API instance.
	 */
	public function get_ecwid_api() {
		return $this->ecwid_api;
	}

	/**
	 * Get Rewrite Manager instance.
	 *
	 * @since 0.1.2
	 * @return Peaches_Rewrite_Manager_Interface The Rewrite Manager instance.
	 */
	public function get_rewrite_manager() {
		return $this->rewrite_manager;
	}

	/**
	 * Get Product Manager instance.
	 *
	 * @since 0.1.2
	 * @return Peaches_Product_Manager_Interface The Product Manager instance.
	 */
	public function get_product_manager() {
		return $this->product_manager;
	}

	/**
	 * Get Ingredients Manager instance.
	 *
	 * @since 0.1.2
	 * @return Peaches_Ingredients_Manager_Interface The Ingredients Manager instance.
	 */
	public function get_ingredients_manager() {
		return $this->ingredients_manager;
	}

	/**
	 * Get Master Ingredients Manager instance.
	 *
	 * @since 0.1.2
	 * @return Peaches_Master_Ingredients_Manager The Master Ingredients Manager instance.
	 */
	public function get_master_ingredients_manager() {
		return $this->master_ingredients_manager;
	}
}
