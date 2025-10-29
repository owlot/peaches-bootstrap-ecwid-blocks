<?php
/**
 * Peaches Ecwid Settings Manager
 *
 * Handles the settings page for Ecwid blocks and integrates
 * with the shared Peaches admin menu structure.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Peaches_Ecwid_Settings
 *
 * Manages settings for Ecwid blocks with proper validation
 * and integration with the shared Peaches menu system.
 *
 * @since 0.2.0
 */
class Peaches_Ecwid_Settings {

	/**
	 * Settings option name
	 *
	 * @since 0.2.0
	 *
	 * @var string
	 */
	const OPTION_NAME = 'peaches_ecwid_settings';

	/**
	 * Settings page slug
	 *
	 * @since 0.2.0
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'peaches-ecwid-settings';

	/**
	 * Peaches main menu slug
	 *
	 * @since 0.2.0
	 *
	 * @var string
	 */
	const MAIN_MENU_SLUG = 'peaches-settings';

	/**
	 * Instance of this class
	 *
	 * @since 0.2.0
	 *
	 * @var Peaches_Ecwid_Settings|null
	 */
	private static $instance = null;

	/**
	 * Settings Manager instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Ecwid_Product_Settings
	 */
	private $product_settings_manager;

	/**
	 * Get singleton instance
	 *
	 * @since 0.2.0
	 *
	 * @return Peaches_Ecwid_Settings The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * Initializes the settings manager with admin hooks and menu integration.
	 *
	 * @since 0.2.0
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_cache_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		$this->product_settings_manager = Peaches_Ecwid_Product_Settings::get_instance();
	}

	/**
	 * Add admin menu items
	 *
	 * Creates the main Peaches menu if it doesn't exist, then adds the
	 * Ecwid blocks settings submenu with proper capability checks.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		// Check if we need Ecwid integration
		if ( ! $this->is_ecwid_available() ) {
			return;
		}

		// Check if main Peaches menu exists
		global $admin_page_hooks;
		$main_menu_exists = isset( $admin_page_hooks[ self::MAIN_MENU_SLUG ] );

		if ( ! $main_menu_exists ) {
			// Create the main Peaches menu
			add_menu_page(
				__( 'Peaches Settings', 'peaches' ),
				__( 'Peaches', 'peaches' ),
				'manage_options',
				self::MAIN_MENU_SLUG,
				array( $this, 'render_main_page' ),
				'dashicons-admin-settings',
				58
			);
		}

		// Add our submenu
		add_submenu_page(
			self::MAIN_MENU_SLUG,
			__( 'Ecwid Blocks Settings', 'peaches' ),
			__( 'Ecwid Blocks', 'peaches' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 *
	 * Registers WordPress settings with proper sanitization callbacks
	 * and creates settings sections and fields for different tabs.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( ! $this->is_ecwid_available() ) {
			return;
		}

		register_setting(
			self::OPTION_NAME,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// General Settings Section
		add_settings_section(
			'general_settings',
			__( 'General Settings', 'peaches' ),
			array( $this, 'render_general_section_description' ),
			self::PAGE_SLUG . '_general'
		);

		// Template Settings Section
		add_settings_section(
			'template_settings',
			__( 'Template Settings', 'peaches' ),
			array( $this, 'render_template_section_description' ),
			self::PAGE_SLUG . '_templates'
		);

		// Debug Settings Section
		add_settings_section(
			'debug_settings',
			__( 'Debug Settings', 'peaches' ),
			array( $this, 'render_debug_section_description' ),
			self::PAGE_SLUG . '_debug'
		);

		// Add fields for each section
		$this->add_general_fields();
		$this->add_template_fields();
		$this->add_debug_fields();
	}

	/**
	 * Add general settings fields
	 *
	 * Creates settings fields for general Ecwid blocks configuration
	 * including API settings and caching options.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function add_general_fields() {
		add_settings_field(
			'cache_duration',
			__( 'Cache Duration (minutes)', 'peaches' ),
			array( $this, 'render_cache_duration_field' ),
			self::PAGE_SLUG . '_general',
			'general_settings'
		);

		add_settings_field(
			'enable_redis',
			__( 'Redis Caching', 'peaches' ),
			array( $this, 'render_redis_field' ),
			self::PAGE_SLUG . '_general',
			'general_settings'
		);


		add_settings_field(
			'cache_management',
			__( 'Cache Management', 'peaches' ),
			array( $this, 'render_cache_management_field' ),
			self::PAGE_SLUG . '_general',
			'general_settings'
		);

	}

	/**
	 * Add template settings fields
	 *
	 * Creates settings fields for template configuration
	 * including product detail page settings.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function add_template_fields() {
		add_settings_field(
			'product_template_page',
			__( 'Product Template Page', 'peaches' ),
			array( $this, 'render_template_page_field' ),
			self::PAGE_SLUG . '_templates',
			'template_settings'
		);

		add_settings_field(
			'enable_breadcrumbs',
			__( 'Enable Breadcrumbs', 'peaches' ),
			array( $this, 'render_breadcrumbs_field' ),
			self::PAGE_SLUG . '_templates',
			'template_settings'
		);
	}

	/**
	 * Add debug settings fields
	 *
	 * Creates settings fields for debug configuration
	 * including debug mode and debug tools access.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	private function add_debug_fields() {
		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'peaches' ),
			array( $this, 'render_debug_mode_field' ),
			self::PAGE_SLUG . '_debug',
			'debug_settings'
		);

		add_settings_field(
			'debug_tools',
			__( 'Debug Tools', 'peaches' ),
			array( $this, 'render_debug_tools_field' ),
			self::PAGE_SLUG . '_debug',
			'debug_settings'
		);
	}

	/**
	 * Render main page
	 *
	 * Processes cache-related actions like clearing cache.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function handle_cache_actions() {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['page'] ) || $_GET['page'] !== self::PAGE_SLUG ) {
			return;
		}

		$action = sanitize_key( $_GET['action'] );

		if ( $action === 'clear_cache' ) {
			// Verify nonce
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'clear_ecwid_cache' ) ) {
				wp_die( __( 'Security check failed.', 'peaches' ) );
			}

			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'peaches' ) );
			}

			// Clear the cache
			$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
			$ecwid_api = $ecwid_blocks->get_ecwid_api();
			$ecwid_api->clear_cache();

			// Redirect with success message
			$redirect_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=general&cache-cleared=1' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Render main page (if we created the main menu)
	 *
	 * Displays the main Peaches settings page with links to available
	 * plugin settings pages and welcome information.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_main_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Peaches Settings', 'peaches' ); ?></h1>
			<p><?php esc_html_e( 'Welcome to Peaches plugin settings. Use the menu on the left to configure individual plugins.', 'peaches' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render settings page
	 *
	 * Displays the Ecwid blocks settings page with tabbed interface
	 * and validation messages, or shows Ecwid requirement notice.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! $this->is_ecwid_available() ) {
			$this->render_ecwid_not_available();
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ecwid Blocks Settings', 'peaches' ); ?></h1>

			<?php $this->render_notices(); ?>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=general' ) ); ?>"
				   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'General', 'peaches' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=blocks' ) ); ?>"
				   class="nav-tab <?php echo $active_tab === 'blocks' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Blocks', 'peaches' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=templates' ) ); ?>"
				   class="nav-tab <?php echo $active_tab === 'templates' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Templates', 'peaches' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=debug' ) ); ?>"
				   class="nav-tab <?php echo $active_tab === 'debug' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Debug', 'peaches' ); ?>
				</a>
			</h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_NAME );

				switch ( $active_tab ) {
					case 'blocks':
						do_settings_sections( self::PAGE_SLUG . '_blocks' );
						break;
					case 'templates':
						do_settings_sections( self::PAGE_SLUG . '_templates' );
						break;
					case 'debug':
						do_settings_sections( self::PAGE_SLUG . '_debug' );
						break;
					default:
						do_settings_sections( self::PAGE_SLUG . '_general' );
						break;
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render general section description
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_general_section_description() {
		?>
		<p><?php esc_html_e( 'Configure general settings for Ecwid blocks including caching and debugging options.', 'peaches' ); ?></p>
		<?php
	}

	/**
	 * Render template section description
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_template_section_description() {
		?>
		<p><?php esc_html_e( 'Configure template settings for product detail pages and other Ecwid templates.', 'peaches' ); ?></p>
		<?php
	}

	/**
	 * Render debug section description
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function render_debug_section_description() {
		?>
		<p><?php esc_html_e( 'Configure debug settings and access advanced debugging tools for troubleshooting Ecwid blocks.', 'peaches' ); ?></p>
		<?php
	}

	/**
	 * Render cache duration field
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_cache_duration_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 60;
		?>
		<input type="number"
			   id="cache_duration"
			   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[cache_duration]"
			   value="<?php echo esc_attr( $value ); ?>"
			   min="1"
			   max="1440"
			   class="small-text" />
		<p class="description"><?php esc_html_e( 'How long to cache Ecwid API responses (1-1440 minutes).', 'peaches' ); ?></p>
		<?php
	}

	/**
	 * Render redis field
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_redis_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['enable_redis'] ) ? $settings['enable_redis'] : false;

		// Check if the parent Peaches Bootstrap Blocks plugin is active
		$redis_service = $this->get_shared_cache_service();
		$redis_configured = $redis_service && $redis_service->is_redis_available();
		?>
		<div class="redis-settings">
			<label>
				<input type="checkbox"
					   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_redis]"
					   value="1"
					   <?php checked( $value ); ?>
					   <?php disabled( ! $redis_service ); ?> />
				<?php esc_html_e( 'Use Redis for Ecwid caching', 'peaches' ); ?>
			</label>

			<?php if ( ! $redis_service ) : ?>
				<p class="description" style="color: #d63638;">
					<strong><?php esc_html_e( 'Peaches Bootstrap Blocks plugin required.', 'peaches' ); ?></strong>
					<?php esc_html_e( 'Install and activate Peaches Bootstrap Blocks to enable Redis caching.', 'peaches' ); ?>
				</p>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'Redis configuration is managed by Peaches Bootstrap Blocks plugin.', 'peaches' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=peaches-redis-settings' ) ); ?>">
						<?php esc_html_e( 'Configure Redis settings', 'peaches' ); ?>
					</a>
				</p>

				<?php if ( $redis_configured ) : ?>
					<div class="redis-status" style="margin-top: 10px; padding: 10px; background: #d1edff; border-left: 4px solid #0073aa;">
						<strong><?php esc_html_e( 'Redis Connected', 'peaches' ); ?></strong><br>
						<?php esc_html_e( 'Ecwid data will be cached using Redis for improved performance.', 'peaches' ); ?>
					</div>
				<?php elseif ( $value ) : ?>
					<div class="redis-status" style="margin-top: 10px; padding: 10px; background: #fff2cc; border-left: 4px solid #dba617;">
						<strong><?php esc_html_e( 'Redis Not Connected', 'peaches' ); ?></strong><br>
						<?php esc_html_e( 'Using WordPress transients as fallback. Configure Redis in main settings for better performance.', 'peaches' ); ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}


	/**
	 * Render cache management field
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_cache_management_field() {
		$cache_info = $this->get_cache_info();

		// Get detailed cache statistics
		$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
		$ecwid_api = $ecwid_blocks ? $ecwid_blocks->get_ecwid_api() : null;
		$detailed_stats = $ecwid_api ? $ecwid_api->get_cache_stats() : null;
		?>
		<div class="cache-management">
			<div class="cache-stats" style="margin-bottom: 15px;">
				<strong><?php esc_html_e( 'Cache Status:', 'peaches' ); ?></strong> <?php echo esc_html( $cache_info['type'] ); ?><br>

				<?php if ( $detailed_stats ) : ?>
					<strong><?php esc_html_e( 'API Cache Details:', 'peaches' ); ?></strong><br>
					<div style="margin-left: 15px; font-size: 13px;">
						<?php esc_html_e( 'Products:', 'peaches' ); ?> <?php echo esc_html( number_format( $detailed_stats['products'] ) ); ?> |
						<?php esc_html_e( 'Categories:', 'peaches' ); ?> <?php echo esc_html( number_format( $detailed_stats['categories'] ) ); ?> |
						<?php esc_html_e( 'Searches:', 'peaches' ); ?> <?php echo esc_html( number_format( $detailed_stats['searches'] ) ); ?><br>
						<?php esc_html_e( 'Descriptions:', 'peaches' ); ?> <?php echo esc_html( number_format( $detailed_stats['descriptions'] ) ); ?> |
						<?php esc_html_e( 'Slugs:', 'peaches' ); ?> <?php echo esc_html( number_format( $detailed_stats['slugs'] ) ); ?> |
						<?php esc_html_e( 'Other:', 'peaches' ); ?> <?php echo esc_html( number_format( $detailed_stats['other'] ) ); ?><br>
						<strong><?php esc_html_e( 'Total:', 'peaches' ); ?> <?php echo esc_html( number_format( $detailed_stats['total'] ) ); ?></strong>
					</div>
				<?php else : ?>
					<strong><?php esc_html_e( 'Cached Items:', 'peaches' ); ?></strong> <?php echo esc_html( number_format( $cache_info['count'] ) ); ?>
				<?php endif; ?>

				<?php if ( $cache_info['redis_available'] && $cache_info['memory_usage'] > 0 ) : ?>
					<br><strong><?php esc_html_e( 'Memory Usage:', 'peaches' ); ?></strong> <?php echo esc_html( $this->format_bytes( $cache_info['memory_usage'] ) ); ?>
				<?php endif; ?>
			</div>

			<p>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=clear_cache&tab=general' ), 'clear_ecwid_cache' ) ); ?>"
				   class="button button-secondary"
				   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all cached data? This will temporarily slow down your site until the cache is rebuilt.', 'peaches' ); ?>')">
					<?php esc_html_e( 'Clear API Cache', 'peaches' ); ?>
				</a>
			</p>
			<p class="description">
				<strong><?php esc_html_e( 'Note:', 'peaches' ); ?></strong> <?php esc_html_e( 'This clears only API result cache. To clear block HTML cache, visit', 'peaches' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=peaches-cache-settings' ) ); ?>"><?php esc_html_e( 'Peaches Cache Settings', 'peaches' ); ?></a>.
			</p>
		</div>
		<?php
	}

	/**
	 * Render debug mode field
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_debug_mode_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['debug_mode'] ) ? $settings['debug_mode'] : false;
		?>
		<label>
			<input type="checkbox"
				   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[debug_mode]"
				   value="1"
				   <?php checked( $value ); ?> />
			<?php esc_html_e( 'Enable debug logging for Ecwid blocks', 'peaches' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Logs API calls and block rendering information to help with troubleshooting.', 'peaches' ); ?>
			<?php if ( $value ) : ?>
				<br>
				<strong style="color: #d63638;"><?php esc_html_e( '⚠ Debug mode is currently active', 'peaches' ); ?></strong>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Render debug tools field
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function render_debug_tools_field() {
		?>
		<div class="debug-tools-links">
			<p><?php esc_html_e( 'Access advanced debugging tools for inspecting products and system information.', 'peaches' ); ?></p>

			<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=peaches-debug' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Open Debug Tools', 'peaches' ); ?>
				</a>
				<p class="description">
					<?php esc_html_e( 'Debug tools include: Product Inspector and System Information.', 'peaches' ); ?>
					<br>
					<?php
					printf(
						/* translators: %s: Link to Multilingual Settings page */
						esc_html__( 'For rewrite rules and URL testing, visit %s.', 'peaches' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=peaches-multilingual-settings' ) ) . '">' . esc_html__( 'Multilingual Settings', 'peaches' ) . '</a>'
					);
					?>
				</p>
			<?php else : ?>
				<p class="description" style="color: #d63638;">
					<strong><?php esc_html_e( 'Debug tools are only available when WP_DEBUG is enabled.', 'peaches' ); ?></strong><br>
					<?php esc_html_e( 'Add the following to your wp-config.php file:', 'peaches' ); ?>
				</p>
				<code style="display: block; padding: 10px; background: #f0f0f1; margin: 10px 0;">
					define( 'WP_DEBUG', true );<br>
					define( 'WP_DEBUG_LOG', true );<br>
					define( 'WP_DEBUG_DISPLAY', false );
				</code>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render template page field
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_template_page_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['product_template_page'] ) ? $settings['product_template_page'] : '';

		$pages = get_pages();
		?>
		<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[product_template_page]">
			<option value=""><?php esc_html_e( 'Auto-generate', 'peaches' ); ?></option>
			<?php foreach ( $pages as $page ) : ?>
				<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $value, $page->ID ); ?>>
					<?php echo esc_html( $page->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Page to use as template for product detail pages. Leave empty to auto-generate.', 'peaches' ); ?></p>
		<?php
	}

	/**
	 * Render breadcrumbs field
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_breadcrumbs_field() {
		$settings = $this->get_settings();
		$value = isset( $settings['enable_breadcrumbs'] ) ? $settings['enable_breadcrumbs'] : true;
		?>
		<label>
			<input type="checkbox"
				   name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_breadcrumbs]"
				   value="1"
				   <?php checked( $value ); ?> />
			<?php esc_html_e( 'Show breadcrumb navigation on product pages', 'peaches' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Displays navigation breadcrumbs to help users understand their location in your store.', 'peaches' ); ?></p>
		<?php
	}

	/**
	 * Render notices
	 *
	 * Displays success messages, warnings, and validation notifications
	 * based on settings updates and validation results.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_notices() {
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Settings saved successfully!', 'peaches' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['cache-cleared'] ) && $_GET['cache-cleared'] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Cache cleared successfully!', 'peaches' ); ?></p>
			</div>
			<?php
		}

		// Show debug mode notice if enabled
		$settings = $this->get_settings();
		if ( $settings['debug_mode'] ) {
			?>
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Debug Mode Active', 'peaches' ); ?></strong> -
					<?php esc_html_e( 'Ecwid API calls and block operations are being logged.', 'peaches' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=peaches-ecwid-settings&tab=general&view_logs=1' ) ); ?>">
						<?php esc_html_e( 'View recent logs', 'peaches' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Render Ecwid not available message
	 *
	 * Displays an error notice when Ecwid Shopping Cart plugin is not
	 * installed or activated, with installation link.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_ecwid_not_available() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ecwid Blocks Settings', 'peaches' ); ?></h1>

			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Ecwid Shopping Cart Required', 'peaches' ); ?></strong>
				</p>
				<p>
					<?php esc_html_e( 'This settings page requires the Ecwid Shopping Cart plugin to be installed and activated.', 'peaches' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=ecwid&tab=search&type=term' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Install Ecwid Shopping Cart', 'peaches' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Sanitize settings
	 *
	 * Validates and sanitizes all settings input with proper error handling
	 * and triggers any necessary actions after successful save.
	 *
	 * @since 0.2.0
	 *
	 * @param array $input Raw input data from form submission.
	 *
	 * @return array Sanitized settings data.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize cache duration
		if ( isset( $input['cache_duration'] ) ) {
			$cache_duration = absint( $input['cache_duration'] );
			$sanitized['cache_duration'] = max( 1, min( 1440, $cache_duration ) );
		}

		// Sanitize checkboxes
		$checkboxes = array( 'debug_mode', 'enable_lazy_loading', 'enable_breadcrumbs', 'enable_redis' );
		foreach ( $checkboxes as $checkbox ) {
			$sanitized[ $checkbox ] = isset( $input[ $checkbox ] ) && $input[ $checkbox ];
		}

		// Sanitize select fields
		if ( isset( $input['default_product_layout'] ) ) {
			$allowed_layouts = array( 'card', 'list', 'grid' );
			$sanitized['default_product_layout'] = in_array( $input['default_product_layout'], $allowed_layouts, true )
				? $input['default_product_layout']
				: 'card';
		}

		// Sanitize template page
		if ( isset( $input['product_template_page'] ) ) {
			$sanitized['product_template_page'] = absint( $input['product_template_page'] );
		}

		return $sanitized;
	}

	/**
	 * Get current settings
	 *
	 * Retrieves saved settings with default values for any missing options
	 * to ensure consistent behavior across all installations.
	 *
	 * @since 0.2.0
	 *
	 * @return array Current settings merged with defaults.
	 */
	public function get_settings() {
		$defaults = array(
			'cache_duration'           => 60,
			'debug_mode'              => false,
			'enable_redis'            => false,
			'default_product_layout'  => 'card',
			'enable_lazy_loading'     => true,
			'product_template_page'   => '',
			'enable_breadcrumbs'      => true,
		);

		return wp_parse_args( get_option( self::OPTION_NAME, array() ), $defaults );
	}

	/**
	 * Get cache information from API
	 *
	 * @since 0.2.0
	 *
	 * @return array Cache information
	 */
	private function get_cache_info() {
		$ecwid_blocks = Peaches_Ecwid_Blocks::get_instance();
		$ecwid_api = $ecwid_blocks->get_ecwid_api();

		if ( method_exists( $ecwid_api, 'get_cache_info' ) ) {
			return $ecwid_api->get_cache_info();
		}

		// Fallback for older API
		return array(
			'type' => 'WordPress Transients',
			'redis_available' => false,
			'count' => $this->get_cache_stats()['count'],
			'memory_usage' => 0,
		);
	}

	/**
	 * Format bytes into human readable format
	 *
	 * @since 0.2.0
	 *
	 * @param int $bytes Number of bytes
	 *
	 * @return string Formatted string
	 */
	private function format_bytes( $bytes ) {
		$units = array( 'B', 'KB', 'MB', 'GB' );
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );

		$bytes /= pow( 1024, $pow );

		return round( $bytes, 2 ) . ' ' . $units[ $pow ];
	}

	/**
	 * Check if Ecwid is available
	 *
	 * Verifies that Ecwid Shopping Cart plugin is installed and activated
	 * by checking for required plugin classes.
	 *
	 * @since 0.2.0
	 *
	 * @return bool True if Ecwid plugin is active and available.
	 */
	private function is_ecwid_available() {
		return class_exists( 'Ecwid_Store_Page' ) || class_exists( 'EcwidPlatform' );
	}

	/**
	 * Get cache statistics
	 *
	 * Returns information about the current cache state.
	 *
	 * @since 0.2.0
	 *
	 * @return array Cache statistics
	 */
	private function get_cache_stats() {
		global $wpdb;

		$cache_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_peaches_ecwid_%'
			)
		);

		return array(
			'count' => intval( $cache_count ),
		);
	}

	/**
	 * Get recent debug logs
	 *
	 * Retrieves recent debug log entries for display.
	 *
	 * @since 0.2.0
	 *
	 * @return array Recent log entries
	 */
	private function get_recent_logs() {
		// This is a simple implementation - in production you might want
		// to use a more sophisticated logging system
		$log_file = WP_CONTENT_DIR . '/debug.log';
		$logs = array();

		if ( file_exists( $log_file ) && is_readable( $log_file ) ) {
			$lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

			// Get last 50 lines
			$lines = array_slice( $lines, -50 );

			// Filter for our plugin logs
			foreach ( $lines as $line ) {
				if ( strpos( $line, '[Peaches Ecwid' ) !== false ) {
					$logs[] = $line;
				}
			}

			// Get last 20 relevant logs
			$logs = array_slice( $logs, -20 );
		}

		return $logs;
	}

	/**
	 * Enqueue admin scripts
	 *
	 * Loads JavaScript and CSS files for the admin settings page
	 * with proper version control and conditional loading.
	 *
	 * @since 0.2.0
	 *
	 * @param string $hook Current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, self::PAGE_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style( 'wp-admin' );

		// Add custom CSS for tabs and debug logs
		wp_add_inline_style( 'wp-admin', '
			.nav-tab-wrapper .nav-tab {
				text-decoration: none;
			}
			.nav-tab-wrapper .nav-tab:focus {
				box-shadow: 0 0 0 1px #007cba;
			}
			.debug-logs {
				background: #f1f1f1;
				border: 1px solid #ccc;
				padding: 10px;
				max-height: 300px;
				overflow-y: auto;
				font-family: monospace;
				font-size: 12px;
				white-space: pre-wrap;
			}
			.cache-management {
				background: #f9f9f9;
				border: 1px solid #ddd;
				padding: 15px;
				border-radius: 4px;
			}
		' );

		// Show debug logs if requested
		if ( isset( $_GET['view_logs'] ) && $_GET['view_logs'] == '1' ) {
			$logs = $this->get_recent_logs();

			wp_add_inline_script( 'wp-admin', '
				jQuery(document).ready(function($) {
					var logs = ' . json_encode( $logs ) . ';
					var logHtml = "<h3>' . esc_js( __( 'Recent Debug Logs', 'peaches' ) ) . '</h3>";
					logHtml += "<div class=\"debug-logs\">";
					if (logs.length > 0) {
						logHtml += logs.join("\\n");
					} else {
						logHtml += "' . esc_js( __( 'No recent logs found.', 'peaches' ) ) . '";
					}
					logHtml += "</div>";
					logHtml += "<p><a href=\"" + window.location.href.replace("&view_logs=1", "") + "\" class=\"button\">' . esc_js( __( 'Close Logs', 'peaches' ) ) . '</a></p>";

					$("form").after(logHtml);
				});
			' );
		}
	}

	/**
	 * Get shared Redis service from Peaches Bootstrap Blocks
	 *
	 * @since 0.2.0
	 *
	 * @return Peaches_Redis_Settings|null Redis service instance or null if not available
	 */
	private function get_shared_cache_service() {
		// Check if Peaches Bootstrap Blocks is active
		if ( ! class_exists( 'Peaches_Bootstrap_Blocks' ) ) {
			return null;
		}

		$bootstrap_blocks = Peaches_Bootstrap_Blocks::get_instance();
		if ( ! $bootstrap_blocks ) {
			return null;
		}

		return $bootstrap_blocks->get_cache_manager();
	}

	/**
	 * Log informational messages.
	 *
	 * Only logs when debug mode is enabled.
	 *
	 * @since 0.2.0
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private function log_info( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) && Peaches_Ecwid_Utilities::is_debug_mode() ) {
			Peaches_Ecwid_Utilities::log_error( '[INFO] [Ecwid Settings] ' . $message, $context );
		}
	}

	/**
	 * Log error messages.
	 *
	 * Always logs, regardless of debug mode.
	 *
	 * @since 0.2.0
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private function log_error( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) ) {
			Peaches_Ecwid_Utilities::log_error( '[Ecwid Settings] ' . $message, $context );
		} else {
			// Fallback logging if utilities class is not available
			error_log( '[Peaches Ecwid] [Ecwid Settings] ' . $message . ( empty( $context ) ? '' : ' - Context: ' . wp_json_encode( $context ) ) );
		}
	}
}
