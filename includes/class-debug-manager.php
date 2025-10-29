<?php
/**
 * Debug Manager class
 *
 * Provides product inspection
 *
 * @package PeachesEcwidBlocks
 * @since   0.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Debug_Manager
 *
 * Manages all debug tools and admin interfaces for the plugin.
 *
 * @package PeachesEcwidBlocks
 * @since   0.7.0
 */
class Peaches_Debug_Manager {

	/**
	 * Ecwid API instance.
	 *
	 * @since  0.7.0
	 * @access private
	 * @var    Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Admin page slug.
	 *
	 * @since 0.7.0
	 * @var   string
	 */
	const PAGE_SLUG = 'peaches-debug';

	/**
	 * Constructor.
	 *
	 * @since 0.7.0
	 * @param Peaches_Ecwid_API_Interface $ecwid_api Ecwid API instance.
	 */
	public function __construct( $ecwid_api ) {
		$this->ecwid_api = $ecwid_api;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since  0.7.0
	 * @return void
	 */
	private function init_hooks() {
		// Only load debug tools if WP_DEBUG is enabled or user is administrator.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_action( 'admin_menu', array( $this, 'register_admin_page' ), 100 );
		}
	}

	/**
	 * Register debug admin page under Peaches menu.
	 *
	 * @since  0.7.0
	 * @return void
	 */
	public function register_admin_page() {
		add_submenu_page(
			'peaches-settings',
			__( 'Debug Tools', 'peaches' ),
			__( 'Debug', 'peaches' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page.
	 *
	 * @since  0.7.0
	 * @return void
	 */
	public function render_admin_page() {
		// Security check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'peaches' ) );
		}

		// Handle actions.
		$this->handle_actions();

		// Get active tab.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'product';

		// Get debug mode status.
		$debug_mode_enabled = false;
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) ) {
			$debug_mode_enabled = Peaches_Ecwid_Utilities::is_debug_mode();
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ecwid Blocks Debug Tools', 'peaches' ); ?></h1>

			<!-- Breadcrumb Navigation -->
			<p class="description" style="margin-bottom: 15px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=peaches-ecwid-settings' ) ); ?>">
					<?php esc_html_e( 'Ecwid Settings', 'peaches' ); ?>
				</a> &raquo;
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=peaches-ecwid-settings&tab=debug' ) ); ?>">
					<?php esc_html_e( 'Debug Tab', 'peaches' ); ?>
				</a> &raquo;
				<strong><?php esc_html_e( 'Debug Tools', 'peaches' ); ?></strong>
			</p>

			<?php if ( $debug_mode_enabled ) : ?>
				<div class="notice notice-success">
					<p>
						<strong><?php esc_html_e( '✓ Debug Mode Active', 'peaches' ); ?></strong> -
						<?php esc_html_e( 'Detailed logging is enabled. Check your debug.log file for API calls and block operations.', 'peaches' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=peaches-ecwid-settings&tab=debug' ) ); ?>" class="button button-small" style="margin-left: 10px;">
							<?php esc_html_e( 'Debug Settings', 'peaches' ); ?>
						</a>
					</p>
				</div>
			<?php else : ?>
				<div class="notice notice-warning">
					<p>
						<strong><?php esc_html_e( '⚠ Debug Mode Inactive', 'peaches' ); ?></strong> -
						<?php esc_html_e( 'Enable debug mode to see detailed logs of API calls and block operations.', 'peaches' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=peaches-ecwid-settings&tab=debug' ) ); ?>" class="button button-small" style="margin-left: 10px;">
							<?php esc_html_e( 'Enable Debug Mode', 'peaches' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<div class="notice notice-info">
				<p>
					<?php esc_html_e( 'These tools help troubleshoot Ecwid blocks, rewrite rules, and product data.', 'peaches' ); ?>
				</p>
			</div>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=product' ) ); ?>"
				   class="nav-tab <?php echo 'product' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Product Inspector', 'peaches' ); ?>
				</a>
			</h2>

			<?php if ( 'product' === $active_tab ) : ?>
				<?php $this->render_product_tab(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle debug actions.
	 *
	 * @since  0.7.0
	 * @return void
	 */
	private function handle_actions() {
		if ( ! isset( $_GET['action'] ) ) {
			return;
		}

		$action = sanitize_key( $_GET['action'] );

		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'peaches_debug_' . $action ) ) {
			wp_die( esc_html__( 'Security check failed.', 'peaches' ) );
		}

		// No actions currently handled in Debug Manager
		// Rewrite rule management moved to Multilingual Settings page
	}

	/**
	 * Render product inspector tab.
	 *
	 * @since  0.7.0
	 * @return void
	 */
	private function render_product_tab() {
		$product     = null;
		$product_id  = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
		$error       = '';

		if ( $product_id ) {
			try {
				$product = $this->ecwid_api->get_product_by_id( $product_id );
				if ( ! $product ) {
					$error = sprintf(
						/* translators: %d: product ID */
						__( 'Product with ID %d not found.', 'peaches' ),
						$product_id
					);
				}
			} catch ( Exception $e ) {
				$error = $e->getMessage();
			}
		}
		?>

		<div class="card mt-4">
			<div class="card-body">
				<h2 class="h4 mb-3 border-bottom pb-2"><?php esc_html_e( 'Product Inspector', 'peaches' ); ?></h2>

				<p><?php esc_html_e( 'Inspect Ecwid product data directly from the API.', 'peaches' ); ?></p>

				<form method="get" action="" class="mb-4">
					<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
					<input type="hidden" name="tab" value="product">
					<div class="mb-3">
						<label for="product_id" class="form-label">
							<strong><?php esc_html_e( 'Product ID:', 'peaches' ); ?></strong>
						</label>
						<input type="number"
						       id="product_id"
						       name="product_id"
						       class="form-control"
						       value="<?php echo esc_attr( $product_id ); ?>"
						       min="1"
						       style="max-width: 300px;">
					</div>
					<?php submit_button( __( 'Inspect Product', 'peaches' ), 'primary', 'submit', false ); ?>
				</form>

				<?php if ( $error ) : ?>
					<div class="alert alert-warning">
						<strong><?php esc_html_e( 'Error:', 'peaches' ); ?></strong>
						<?php echo esc_html( $error ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $product ) : ?>
					<h3 class="h5 mt-4 mb-3"><?php esc_html_e( 'Product Summary', 'peaches' ); ?></h3>

					<table class="table table-bordered">
						<thead>
							<tr>
								<th style="width: 30%;"><?php esc_html_e( 'Property', 'peaches' ); ?></th>
								<th><?php esc_html_e( 'Value', 'peaches' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'ID', 'peaches' ); ?></strong></td>
								<td><?php echo esc_html( $product->id ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Name', 'peaches' ); ?></strong></td>
								<td><?php echo esc_html( $product->name ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'SKU', 'peaches' ); ?></strong></td>
								<td><?php echo isset( $product->sku ) ? esc_html( $product->sku ) : '—'; ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Price', 'peaches' ); ?></strong></td>
								<td><?php echo isset( $product->price ) ? esc_html( $product->price ) : '—'; ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Slug', 'peaches' ); ?></strong></td>
								<td><?php echo isset( $product->autogeneratedSlug ) ? esc_html( $product->autogeneratedSlug ) : '—'; ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'URL', 'peaches' ); ?></strong></td>
								<td>
									<?php
									if ( isset( $product->url ) ) {
										echo '<a href="' . esc_url( $product->url ) . '" target="_blank">' . esc_html( $product->url ) . '</a>';
									} else {
										echo '—';
									}
									?>
								</td>
							</tr>
						</tbody>
					</table>

					<h3 class="h5 mt-4 mb-3"><?php esc_html_e( 'Full Object Data', 'peaches' ); ?></h3>
					<pre style="background: #f6f7f7; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; max-height: 600px;"><?php echo esc_html( print_r( $product, true ) ); ?></pre>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Add admin notice.
	 *
	 * @since  0.7.0
	 * @param  string $message Notice message.
	 * @param  string $type    Notice type (success, error, warning, info).
	 * @return void
	 */
	private function add_admin_notice( $message, $type = 'info' ) {
		add_action(
			'admin_notices',
			function() use ( $message, $type ) {
				printf(
					'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
					esc_attr( $type ),
					esc_html( $message )
				);
			}
		);
	}

	/**
	 * Log informational messages.
	 *
	 * @since  0.7.0
	 * @param  string $message Log message.
	 * @param  array  $context Additional context data.
	 * @return void
	 */
	private function log_info( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) && Peaches_Ecwid_Utilities::is_debug_mode() ) {
			Peaches_Ecwid_Utilities::log_error( '[INFO] [Debug Manager] ' . $message, $context );
		}
	}

	/**
	 * Log error messages.
	 *
	 * @since  0.7.0
	 * @param  string $message Error message.
	 * @param  array  $context Additional context data.
	 * @return void
	 */
	private function log_error( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) ) {
			Peaches_Ecwid_Utilities::log_error( '[Debug Manager] ' . $message, $context );
		}
	}
}
