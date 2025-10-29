<?php
/**
 * Enhanced Navigation Manager
 *
 * Adds better navigation for product settings, lines, and ingredients
 * to redirect back to the unified Peaches Ecwid Products page.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Enhanced_Navigation
 *
 * Manages enhanced navigation for product-related admin pages.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Enhanced_Navigation {

	/**
	 * Constructor.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Add navigation notices
		add_action( 'admin_notices', array( $this, 'add_navigation_notice' ) );

		// Handle post save redirects - use a safer approach
		add_action( 'admin_init', array( $this, 'handle_redirect_on_save' ) );

		// Menu manipulation for taxonomies
		add_filter( 'parent_file', array( $this, 'set_parent_menu_active' ) );
		add_filter( 'submenu_file', array( $this, 'set_submenu_active' ) );
	}

	/**
	 * Handle redirects after saving posts.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function handle_redirect_on_save() {
		// Only process if we have the right parameters
		if ( ! isset( $_GET['post'] ) || ! isset( $_GET['action'] ) || $_GET['action'] !== 'edit' ) {
			return;
		}

		// Check if we just saved and should redirect
		if ( isset( $_GET['message'] ) && $_GET['message'] === '1' ) {
			$post_id = absint( $_GET['post'] );
			$post    = get_post( $post_id );

			if ( ! $post ) {
				return;
			}

			// Map post types to their tab in the unified page
			$post_type_tabs = array(
				'product_settings'   => 'product_settings',
				'product_ingredient' => 'product_ingredient',
			);

			// Check if this is one of our post types and if we came from the unified page
			if ( isset( $post_type_tabs[ $post->post_type ] ) ) {
				$tab = $post_type_tabs[ $post->post_type ];

				// Check if we have a referrer indicating we came from the unified page
				$referrer = wp_get_referer();
				if ( $referrer && strpos( $referrer, 'page=peaches-ecwid-product-settings' ) !== false ) {
					// Use JavaScript redirect to avoid header issues
					?>
					<script type="text/javascript">
						window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=peaches-ecwid-product-settings&tab=' . $tab . '&saved=' . $post_id ) ); ?>';
					</script>
					<?php
					exit;
				}
			}
		}
	}

	/**
	 * Set the parent menu as active for our taxonomies and post types.
	 *
	 * @since 0.2.0
	 *
	 * @param string $parent_file The parent file.
	 *
	 * @return string Modified parent file.
	 */
	public function set_parent_menu_active( $parent_file ) {
		global $current_screen;

		if ( ! $current_screen ) {
			return $parent_file;
		}

		// Handle product lines taxonomy
		if ( $this->is_product_line_page() ) {
			return 'peaches-settings';
		}

		// Handle our post types
		if ( isset( $current_screen->post_type ) && in_array( $current_screen->post_type, array( 'product_settings', 'product_ingredient' ), true ) ) {
			return 'peaches-settings';
		}

		return $parent_file;
	}

	/**
	 * Set the submenu as active for our taxonomies and post types.
	 *
	 * @since 0.2.0
	 *
	 * @param string $submenu_file The submenu file.
	 *
	 * @return string Modified submenu file.
	 */
	public function set_submenu_active( $submenu_file ) {
		global $current_screen;

		if ( ! $current_screen ) {
			return $submenu_file;
		}

		// Handle product lines taxonomy
		if ( $this->is_product_line_page() ) {
			return 'peaches-ecwid-product-settings';
		}

		// Handle our post types
		if ( isset( $current_screen->post_type ) && in_array( $current_screen->post_type, array( 'product_settings', 'product_ingredient' ), true ) ) {
			return 'peaches-ecwid-product-settings';
		}

		return $submenu_file;
	}

	/**
	 * Add navigation notice on relevant post type edit screens.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function add_navigation_notice() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		// Configuration for different content types
		$content_types = array(
			'product_settings'   => array(
				'tab'   => 'product_settings',
				'title' => __( 'Product Configuration', 'peaches' ),
			),
			'product_ingredient' => array(
				'tab'   => 'ingredients_library',
				'title' => __( 'Ingredients Library', 'peaches' ),
			),
		);

		// Handle taxonomies
		if ( $this->is_product_line_page() ) {
			$config = array(
				'tab'   => 'product_lines',
				'title' => __( 'Product Lines', 'peaches' ),
			);
			$this->render_navigation_notice( $config );
			return;
		}

		// Handle post types
		if ( isset( $content_types[ $screen->id ] ) ||
			( isset( $screen->post_type ) && isset( $content_types[ $screen->post_type ] ) ) ) {

			$post_type = isset( $content_types[ $screen->id ] ) ? $screen->id : $screen->post_type;
			$config    = $content_types[ $post_type ];
			$this->render_navigation_notice( $config );
		}
	}

	/**
	 * Render the navigation notice.
	 *
	 * @since 0.2.0
	 * @since 0.3.2 Fixed button text concatenation issue.
	 *
	 * @param array $config Navigation configuration with tab and title.
	 *
	 * @return void
	 */
	private function render_navigation_notice( $config ) {
		$main_page_url = admin_url( 'admin.php?page=peaches-ecwid-product-settings&tab=' . $config['tab'] );

		?>
		<a href="<?php echo esc_url( $main_page_url ); ?>" class="button button-primary">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
			<?php
			/* translators: %s: destination page title (e.g., "Product Settings", "Product Lines") */
			printf( esc_html__( 'Back to %s', 'peaches' ), esc_html( $config['title'] ) );
			?>
		</a>
		<?php
	}

	/**
	 * Check if we're on a product line related page.
	 *
	 * @since 0.2.0
	 *
	 * @return bool True if on product line page, false otherwise.
	 */
	private function is_product_line_page() {
		global $current_screen, $taxnow;

		if ( ! $current_screen ) {
			return false;
		}

		// Check for product_line taxonomy pages
		if ( isset( $current_screen->taxonomy ) && $current_screen->taxonomy === 'product_line' ) {
			return true;
		}

		// Check for edit-tags.php with product_line taxonomy
		if ( isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] === 'product_line' ) {
			return true;
		}

		// Check global taxonomy variable
		if ( $taxnow === 'product_line' ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the current post type context for navigation.
	 *
	 * @since 0.2.0
	 *
	 * @return string|null Current post type or null if not found.
	 */
	private function get_current_post_type_context() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return null;
		}

		// Handle different screen contexts
		if ( isset( $screen->post_type ) ) {
			return $screen->post_type;
		}

		if ( isset( $screen->id ) ) {
			return $screen->id;
		}

		return null;
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
			Peaches_Ecwid_Utilities::log_error( '[INFO] [Enhanced Navigation] ' . $message, $context );
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
			Peaches_Ecwid_Utilities::log_error( '[Enhanced Navigation] ' . $message, $context );
		} else {
			// Fallback logging if utilities class is not available
			error_log( '[Peaches Ecwid] [Enhanced Navigation] ' . $message . ( empty( $context ) ? '' : ' - Context: ' . wp_json_encode( $context ) ) );
		}
	}
}
