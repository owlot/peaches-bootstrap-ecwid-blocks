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

// Include the interface
require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-ecwid-blocks.php';

/**
 * Class Peaches_Ecwid_Blocks
 *
 * Main plugin class implementing the singleton pattern.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Ecwid_Blocks implements Peaches_Ecwid_Blocks_Interface {
	/**
	 * Singleton instance of the class.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Ecwid_Blocks|null
	 */
	private static $instance = null;

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
	 * Media Tags API instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Media_Tags_API
	 */
	private $media_tags_api;

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
			'interfaces/interface-ecwid-api.php',
			'interfaces/interface-rewrite-manager.php',
			'interfaces/interface-product-manager.php',
			'interfaces/interface-ingredients-manager.php',
			'interfaces/interface-block-patterns.php'
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
			'class-ecwid-api.php',
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
			'class-ingredients-manager.php',
			'class-media-tags-manager.php',
			'class-media-tags-api.php',
		);

		foreach ($classes as $class_file) {
			$file_path = PEACHES_ECWID_INCLUDES_DIR . $class_file;
			if (file_exists($file_path)) {
				require_once $class_file;
			}
		}
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

		// Initialize product lines manager (replaces groups)
		if (class_exists('Peaches_Product_Lines_Manager')) {
			$this->product_lines_manager = new Peaches_Product_Lines_Manager();
		}

		// Initialize product media manager
		if (class_exists('Peaches_Product_Media_Manager') && $this->ecwid_api && $this->media_tags_manager) {
			$this->product_media_manager = new Peaches_Product_Media_Manager($this->ecwid_api, $this->media_tags_manager);
		}

		// Initialize other components with dependencies
		if (class_exists('Peaches_Rewrite_Manager') && $this->ecwid_api) {
			$this->rewrite_manager = new Peaches_Rewrite_Manager($this->ecwid_api);
		}

		if (class_exists('Peaches_Product_Manager') && $this->ecwid_api) {
			$this->product_manager = new Peaches_Product_Manager($this->ecwid_api);
		}

		// Initialize product settings manager (new) or ingredients manager (fallback)
		if (class_exists('Peaches_Product_Settings_Manager') && $this->ecwid_api) {
			$this->product_settings_manager = new Peaches_Product_Settings_Manager($this->ecwid_api);
			if ($this->product_lines_manager && method_exists($this->product_settings_manager, 'set_lines_manager')) {
				$this->product_settings_manager->set_lines_manager($this->product_lines_manager);
			}
		} elseif (class_exists('Peaches_Ingredients_Manager') && $this->ecwid_api) {
			// Fallback to old ingredients manager
			$this->product_settings_manager = new Peaches_Ingredients_Manager($this->ecwid_api);
		}

		if (class_exists('Peaches_Ingredients_Library_Manager')) {
			$this->ingredients_library_manager = new Peaches_Ingredients_Library_Manager();
		}

		// Initialize enhanced navigation if available
		if (class_exists('Peaches_Enhanced_Navigation')) {
			$this->enhanced_navigation = new Peaches_Enhanced_Navigation();
		}

		// Initialize patterns last and only if we're not in an AJAX request
		if (!wp_doing_ajax() && class_exists('Peaches_Ecwid_Block_Patterns')) {
			$this->block_patterns = new Peaches_Ecwid_Block_Patterns();
		}

		// Initialize media tags manager
		if (class_exists('Peaches_Media_Tags_Manager')) {
			$this->media_tags_manager = new Peaches_Media_Tags_Manager();
		}

		// Initialize media tags API (requires media tags manager and product settings manager)
		if (class_exists('Peaches_Media_Tags_API') && $this->media_tags_manager && $this->product_settings_manager) {
			$this->media_tags_api = new Peaches_Media_Tags_API($this->media_tags_manager, $this->product_settings_manager);
		}
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 0.2.0
	 */
	private function init_hooks() {
		// Plugin core hooks
		add_action('plugins_loaded', array($this, 'load_textdomain'));
		add_action('admin_init', array($this, 'check_ecwid_plugin'));
		add_filter('plugin_action_links_' . plugin_basename(PEACHES_ECWID_PLUGIN_DIR . 'peaches-bootstrap-ecwid-blocks.php'), array($this, 'add_settings_link'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// Only add media hooks if we have the new functionality
		if (method_exists($this, 'filter_media_library')) {
			add_action('pre_get_posts', array($this, 'filter_media_library'));
			add_filter('attachment_fields_to_edit', array($this, 'add_product_media_field'), 10, 2);
			add_filter('attachment_fields_to_save', array($this, 'save_product_media_field'), 10, 2);
			add_action('wp_ajax_mark_product_media', array($this, 'ajax_mark_product_media'));
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * Loads necessary CSS for the admin pages.
	 *
	 * @since 0.2.0
	 */
	public function enqueue_admin_scripts() {
		// Enqueue editor-specific styles
		wp_enqueue_style(
			'peaches-bootstrap-ecwid-admin',
			PEACHES_ECWID_PLUGIN_URL . 'assets/css/admin.css',
			array(),
		);
	}

	/**
	 * Plugin activation hook.
	 *
	 * @since 0.2.0
	 */
	public function activate() {
		// Run database migration
		$this->maybe_migrate_database();

		// Create the product detail page if needed
		if ($this->rewrite_manager) {
			$this->rewrite_manager->register_product_template();
		}

		// Flush rewrite rules
		flush_rewrite_rules(true);

		// Store activation timestamp for cache busting
		update_option('peaches_ecwid_activated', time());
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @since 0.2.0
	 */
	public function deactivate() {
		// Flush rewrite rules to remove our custom ones
		flush_rewrite_rules(true);
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @since 0.2.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain('peaches', false, dirname(plugin_basename(PEACHES_ECWID_PLUGIN_DIR)) . '/languages');
	}

	/**
	 * Check if Ecwid plugin is active.
	 *
	 * @since 0.2.0
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
	 * @since 0.2.0
	 * @param array $links Current plugin links.
	 * @return array Modified plugin links.
	 */
	public function add_settings_link($links) {
		$settings_link = '<a href="' . admin_url('admin.php?page=' . Peaches_Ecwid_Settings::PAGE_SLUG) . '">' . __('Settings', 'peaches') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Filter media library to show product media.
	 *
	 * @since 0.2.0
	 * @param WP_Query $query The query object.
	 */
	public function filter_media_library($query) {
		if (!is_admin() || !$query->is_main_query()) {
			return;
		}

		// Check if we're in the media library and filtering for product media
		if (isset($_GET['product_media_filter']) && $_GET['product_media_filter'] === '1') {
			$query->set('meta_query', array(
				array(
					'key' => '_peaches_product_media',
					'value' => true,
					'compare' => '='
				)
			));
		}
	}

	/**
	 * Add product media field to attachment edit screen.
	 *
	 * @since 0.2.0
	 * @param array   $form_fields Form fields array.
	 * @param WP_Post $post        Attachment post object.
	 * @return array Modified form fields.
	 */
	public function add_product_media_field($form_fields, $post) {
		$is_product_media = get_post_meta($post->ID, '_peaches_product_media', true);
		$is_line_media = get_post_meta($post->ID, '_peaches_line_media', true);
		$media_tag = get_post_meta($post->ID, '_peaches_product_media_tag', true);
		$line_media_tag = get_post_meta($post->ID, '_peaches_line_media_tag', true);

		$form_fields['peaches_product_media'] = array(
			'label' => __('Product Media', 'peaches'),
			'input' => 'html',
			'html' => '
				<label>
					<input type="checkbox" name="attachments[' . $post->ID . '][peaches_product_media]" value="1" ' . checked($is_product_media, true, false) . ' />
					' . __('This is product media', 'peaches') . '
				</label>
				<br><br>
				<label for="attachments[' . $post->ID . '][peaches_product_media_tag]">' . __('Product Media Tag:', 'peaches') . '</label>
				<input type="text" name="attachments[' . $post->ID . '][peaches_product_media_tag]" value="' . esc_attr($media_tag) . '" class="widefat" />
				<div class="description">' . __('Tag name for targeting this media in blocks.', 'peaches') . '</div>
				<br><br>
				<label>
					<input type="checkbox" name="attachments[' . $post->ID . '][peaches_line_media]" value="1" ' . checked($is_line_media, true, false) . ' />
					' . __('This is line media', 'peaches') . '
				</label>
				<br><br>
				<label for="attachments[' . $post->ID . '][peaches_line_media_tag]">' . __('Line Media Tag:', 'peaches') . '</label>
				<input type="text" name="attachments[' . $post->ID . '][peaches_line_media_tag]" value="' . esc_attr($line_media_tag) . '" class="widefat" />
				<div class="description">' . __('Tag name for targeting this line media in blocks.', 'peaches') . '</div>
			'
		);

		return $form_fields;
	}

	/**
	 * Save product media field.
	 *
	 * @since 0.2.0
	 * @param array $post       Post data array.
	 * @param array $attachment Attachment data array.
	 * @return array Modified post data.
	 */
	public function save_product_media_field($post, $attachment) {
		// Save product media
		if (isset($attachment['peaches_product_media'])) {
			update_post_meta($post['ID'], '_peaches_product_media', true);
		} else {
			delete_post_meta($post['ID'], '_peaches_product_media');
		}

		if (isset($attachment['peaches_product_media_tag'])) {
			update_post_meta($post['ID'], '_peaches_product_media_tag', sanitize_text_field($attachment['peaches_product_media_tag']));
		}

		// Save line media
		if (isset($attachment['peaches_line_media'])) {
			update_post_meta($post['ID'], '_peaches_line_media', true);
		} else {
			delete_post_meta($post['ID'], '_peaches_line_media');
		}

		if (isset($attachment['peaches_line_media_tag'])) {
			update_post_meta($post['ID'], '_peaches_line_media_tag', sanitize_text_field($attachment['peaches_line_media_tag']));
		}

		return $post;
	}

	/**
	 * AJAX handler to mark attachment as product media.
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
	 * Get Media Tags API instance.
	 *
	 * @return Peaches_Media_Tags_API|null
	 */
	public function get_media_tags_api() {
		return $this->media_tags_api;
	}
}
