<?php
/**
 * Ecwid Product Debug Tool
 *
 * DEPRECATED: This class is deprecated in favor of the unified Peaches_Debug_Manager.
 * Use the Debug Tools page under Peaches > Ecwid Blocks > Debug instead.
 *
 * Admin utility for inspecting Ecwid product object properties.
 * Helps developers understand what data is available from the Ecwid API.
 *
 * @package    PeachesEcwidBlocks
 * @since      0.6.2
 * @deprecated 0.7.0 Use Peaches_Debug_Manager instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Ecwid_Product_Debug
 *
 * Provides admin interface and shortcode for debugging Ecwid product objects.
 *
 * @package    PeachesEcwidBlocks
 * @since      0.6.2
 * @deprecated 0.7.0 Use Peaches_Debug_Manager instead.
 */
class Peaches_Ecwid_Product_Debug {

	/**
	 * Ecwid API instance.
	 *
	 * @since  0.6.2
	 * @access private
	 * @var    Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Admin page slug.
	 *
	 * @since 0.6.2
	 * @var   string
	 */
	const PAGE_SLUG = 'debug-ecwid-product';

	/**
	 * Shortcode name.
	 *
	 * @since 0.6.2
	 * @var   string
	 */
	const SHORTCODE = 'debug_ecwid_product';

	/**
	 * Constructor.
	 *
	 * @since 0.6.2
	 * @param Peaches_Ecwid_API_Interface $ecwid_api Ecwid API instance.
	 */
	public function __construct( $ecwid_api ) {
		$this->ecwid_api = $ecwid_api;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since      0.6.2
	 * @deprecated 0.7.0
	 */
	private function init_hooks() {
		// Show deprecation notice.
		_deprecated_function( __CLASS__, '0.7.0', 'Peaches_Debug_Manager' );

		// Defer capability check until hooks actually run.
		add_action( 'admin_menu', array( $this, 'register_admin_page' ), 100 );
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
	}

	/**
	 * Register admin page under Peaches menu.
	 *
	 * @since      0.6.2
	 * @deprecated 0.7.0
	 */
	public function register_admin_page() {
		// Redirect to new debug tools page.
		add_submenu_page(
			'peaches-settings',
			__( 'Debug Ecwid Product (Legacy)', 'peaches' ),
			__( 'Debug Product (Legacy)', 'peaches' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @since      0.6.2
	 * @deprecated 0.7.0
	 */
	public function render_admin_page() {
		// Security check.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access Denied: You must be an administrator to access this tool.', 'peaches' ) );
		}

		// Show deprecation notice.
		?>
		<div class="wrap">
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Deprecation Notice:', 'peaches' ); ?></strong>
					<?php esc_html_e( 'This debug tool has been replaced by the new unified Debug Tools interface.', 'peaches' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=peaches-debug&tab=product' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
						<?php esc_html_e( 'Go to New Debug Tools', 'peaches' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
		return;

		$product       = null;
		$product_id    = null;
		$error_message = null;

		// Check if form was submitted
		if ( isset( $_POST['product_id'] ) && check_admin_referer( 'debug_ecwid_product' ) ) {
			$product_id = intval( $_POST['product_id'] );

			if ( $product_id > 0 ) {
				list( $product, $error_message ) = $this->get_product( $product_id );
			}
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( '🔍 Ecwid Product Object Inspector', 'peaches' ); ?></h1>

			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( '💡 Tip:', 'peaches' ); ?></strong>
					<?php
					printf(
						/* translators: %s: shortcode example */
						esc_html__( 'You can also use the shortcode on any page (admin-only): %s', 'peaches' ),
						'<code>[' . esc_html( self::SHORTCODE ) . ' id="YOUR_PRODUCT_ID"]</code>'
					);
					?>
				</p>
			</div>

			<div class="card" style="max-width: 800px; margin: 20px 0;">
				<h2><?php esc_html_e( 'Enter Product ID', 'peaches' ); ?></h2>
				<form method="post" action="">
					<?php wp_nonce_field( 'debug_ecwid_product' ); ?>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="product_id"><?php esc_html_e( 'Product ID', 'peaches' ); ?></label>
							</th>
							<td>
								<input type="number" name="product_id" id="product_id"
									   value="<?php echo esc_attr( $product_id ); ?>"
									   class="regular-text" required>
								<p class="description">
									<?php esc_html_e( 'Enter an Ecwid product ID (e.g., 780195892)', 'peaches' ); ?>
								</p>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button button-primary"
							   value="<?php esc_attr_e( 'Inspect Product', 'peaches' ); ?>">
					</p>
				</form>
			</div>

			<?php if ( $product_id ) : ?>
				<?php $this->render_debug_output( $product, $product_id, $error_message ); ?>
			<?php endif; ?>

			<div class="card" style="margin: 20px 0;">
				<h2><?php esc_html_e( '📚 Resources', 'peaches' ); ?></h2>
				<ul>
					<li>
						<a href="https://api-docs.ecwid.com/reference/products" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Ecwid API v3 - Products Documentation', 'peaches' ); ?>
						</a>
					</li>
					<li>
						<a href="https://api-docs.ecwid.com/reference/get-a-product" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Get Product Endpoint Documentation', 'peaches' ); ?>
						</a>
					</li>
					<li>
						<a href="https://support.ecwid.com/hc/en-us/articles/207100549-WordPress-plugin" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Ecwid WordPress Plugin Support', 'peaches' ); ?>
						</a>
					</li>
				</ul>
			</div>
		</div>

		<style>
			.card h2 { margin-top: 0; }
			.card h3 { margin-top: 20px; }
		</style>
		<?php
	}

	/**
	 * Handle shortcode rendering.
	 *
	 * @since 0.6.2
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_shortcode( $atts ) {
		// Double-check admin permissions for shortcode usage
		if ( ! current_user_can( 'manage_options' ) ) {
			return '<div class="notice notice-error"><p>' .
				   esc_html__( '⚠️ Insufficient permissions. You must be an administrator to use this tool.', 'peaches' ) .
				   '</p></div>';
		}

		$atts       = shortcode_atts( array( 'id' => 0 ), $atts );
		$product_id = intval( $atts['id'] );

		if ( $product_id <= 0 ) {
			return '<div class="notice notice-warning"><p>' .
				   sprintf(
					   /* translators: %s: shortcode example */
					   esc_html__( 'Usage: %s', 'peaches' ),
					   '[' . esc_html( self::SHORTCODE ) . ' id="YOUR_PRODUCT_ID"]'
				   ) .
				   '</p></div>';
		}

		list( $product, $error_message ) = $this->get_product( $product_id );

		ob_start();
		$this->render_debug_output( $product, $product_id, $error_message );
		return ob_get_clean();
	}

	/**
	 * Get product using Ecwid API.
	 *
	 * @since 0.6.2
	 * @param int $product_id Product ID.
	 * @return array Array containing product object and error message.
	 */
	private function get_product( $product_id ) {
		$product       = null;
		$error_message = null;

		try {
			if ( $this->ecwid_api ) {
				$product = $this->ecwid_api->get_product_by_id( $product_id );
				if ( ! $product ) {
					$error_message = sprintf(
						/* translators: %d: product ID */
						__( 'Product with ID %d not found or API returned no data.', 'peaches' ),
						$product_id
					);
				}
			} else {
				$error_message = __( 'Ecwid API is not available.', 'peaches' );
			}
		} catch ( Exception $e ) {
			$error_message = $e->getMessage();
		}

		return array( $product, $error_message );
	}

	/**
	 * Render debug output for a product.
	 *
	 * @since 0.6.2
	 * @param object|null $product      Product object.
	 * @param int         $product_id   Product ID.
	 * @param string|null $error_message Error message if any.
	 */
	private function render_debug_output( $product, $product_id, $error_message ) {
		if ( $product ) :
			?>
			<div class="card" style="max-width: 1200px; margin: 20px 0;">
				<p><strong><?php esc_html_e( 'Product ID:', 'peaches' ); ?></strong> <?php echo esc_html( $product_id ); ?></p>
				<p><strong><?php esc_html_e( 'Product Name:', 'peaches' ); ?></strong> <code><?php echo esc_html( $product->name ?? 'N/A' ); ?></code></p>
				<p><strong><?php esc_html_e( 'Data Source:', 'peaches' ); ?></strong> Peaches_Ecwid_API</p>
			</div>

			<div class="card" style="max-width: 1200px; margin: 20px 0;">
				<h2><?php esc_html_e( '🔑 All Available Properties', 'peaches' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 30%;"><?php esc_html_e( 'Property Name', 'peaches' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'Type', 'peaches' ); ?></th>
							<th style="width: 50%;"><?php esc_html_e( 'Value / Preview', 'peaches' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$properties = get_object_vars( $product );
						ksort( $properties );

						foreach ( $properties as $key => $value ) :
							$type    = gettype( $value );
							$preview = $this->format_value_preview( $value );
							?>
							<tr>
								<td><code><?php echo esc_html( $key ); ?></code></td>
								<td><?php echo esc_html( $type ); ?></td>
								<td><?php echo wp_kses_post( $preview ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="card" style="max-width: 1200px; margin: 20px 0;">
				<h2><?php esc_html_e( '📦 Key Schema Properties', 'peaches' ); ?></h2>
				<?php $this->render_key_properties_table( $product ); ?>
			</div>

			<div class="card" style="max-width: 1200px; margin: 20px 0;">
				<h2><?php esc_html_e( '🧪 Availability Detection Logic', 'peaches' ); ?></h2>
				<?php $this->render_availability_detection( $product ); ?>
			</div>

			<div class="card" style="max-width: 1200px; margin: 20px 0;">
				<h2><?php esc_html_e( '📄 Raw JSON Output', 'peaches' ); ?></h2>
				<textarea readonly style="width: 100%; min-height: 300px; font-family: monospace; font-size: 12px;"><?php echo esc_textarea( json_encode( $product, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea>
				<p>
					<button class="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.textContent='Copied!'; setTimeout(() => this.textContent='<?php esc_attr_e( 'Copy to Clipboard', 'peaches' ); ?>', 2000);">
						<?php esc_html_e( 'Copy to Clipboard', 'peaches' ); ?>
					</button>
				</p>
			</div>

		<?php else : ?>

			<div class="notice notice-error" style="margin: 20px 0;">
				<p><strong><?php esc_html_e( '❌ Error:', 'peaches' ); ?></strong></p>
				<p><?php echo esc_html( $error_message ?? __( 'Failed to fetch product data.', 'peaches' ) ); ?></p>
				<p><?php esc_html_e( 'Troubleshooting:', 'peaches' ); ?></p>
				<ul style="margin-left: 20px;">
					<li><?php esc_html_e( 'Verify the product ID is correct in your Ecwid admin', 'peaches' ); ?></li>
					<li><?php esc_html_e( 'Ensure Ecwid plugin is installed and configured', 'peaches' ); ?></li>
					<li><?php esc_html_e( 'Check that your Ecwid API credentials are valid', 'peaches' ); ?></li>
					<li><?php esc_html_e( 'Verify the product is published and visible', 'peaches' ); ?></li>
				</ul>
			</div>

		<?php
		endif;
	}

	/**
	 * Format value preview for display.
	 *
	 * @since 0.6.2
	 * @param mixed $value Value to format.
	 * @return string Formatted preview.
	 */
	private function format_value_preview( $value ) {
		$type = gettype( $value );

		switch ( $type ) {
			case 'boolean':
				return $value ? '<span style="color: green;">✓ true</span>' : '<span style="color: red;">✗ false</span>';

			case 'NULL':
				return '<em style="color: #999;">null</em>';

			case 'integer':
			case 'double':
				return '<code>' . esc_html( $value ) . '</code>';

			case 'string':
				if ( strlen( $value ) > 100 ) {
					return '<code>' . esc_html( substr( $value, 0, 100 ) ) . '...</code> <em>(' . strlen( $value ) . ' chars)</em>';
				}
				return '<code>' . esc_html( $value ) . '</code>';

			case 'array':
				$count   = count( $value );
				$preview = $count > 0 ? esc_html( json_encode( array_slice( $value, 0, 2 ) ) ) : '[]';
				return '<code>' . $preview . '</code> <em>(' . $count . ' items)</em>';

			case 'object':
				$vars  = get_object_vars( $value );
				$count = count( $vars );
				return '<code>{...}</code> <em>(' . $count . ' properties)</em>';

			default:
				return '<code>' . esc_html( print_r( $value, true ) ) . '</code>';
		}
	}

	/**
	 * Render key properties table.
	 *
	 * @since 0.6.2
	 * @param object $product Product object.
	 */
	private function render_key_properties_table( $product ) {
		$key_properties = array(
			array(
				'name'        => 'inStock',
				'current'     => $product->inStock ?? null,
				'type'        => 'boolean',
				'description' => __( 'Indicates if product is in stock', 'peaches' ),
			),
			array(
				'name'        => 'enabled',
				'current'     => $product->enabled ?? null,
				'type'        => 'boolean',
				'description' => __( 'Indicates if product is enabled/published', 'peaches' ),
			),
			array(
				'name'        => 'quantity',
				'current'     => $product->quantity ?? null,
				'type'        => 'integer',
				'description' => __( 'Current stock quantity', 'peaches' ),
			),
			array(
				'name'        => 'unlimited',
				'current'     => $product->unlimited ?? null,
				'type'        => 'boolean',
				'description' => __( 'Indicates if product has unlimited stock', 'peaches' ),
			),
		);

		?>
		<table class="wp-list-table widefat fixed striped">
			<?php foreach ( $key_properties as $prop ) : ?>
				<tr>
					<th style="width: 20%;"><code><?php echo esc_html( $prop['name'] ); ?></code></th>
					<td style="width: 80%;">
						<strong>
							<?php
							if ( $prop['current'] === null ) {
								echo '<em style="color: #999;">' . esc_html__( 'Not set', 'peaches' ) . '</em>';
							} elseif ( $prop['type'] === 'boolean' ) {
								echo $prop['current'] ? '<span style="color: green;">✓ true</span>' : '<span style="color: red;">✗ false</span>';
							} else {
								echo '<code>' . esc_html( $prop['current'] ) . '</code>';
							}
							?>
						</strong>
						<br>
						<span style="color: #666;"><?php echo esc_html( $prop['description'] ); ?></span>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Render availability detection information.
	 *
	 * @since 0.6.2
	 * @param object $product Product object.
	 */
	private function render_availability_detection( $product ) {
		$in_stock  = isset( $product->inStock ) ? $product->inStock : null;
		$enabled   = isset( $product->enabled ) ? $product->enabled : null;
		$quantity  = isset( $product->quantity ) ? $product->quantity : null;
		$unlimited = isset( $product->unlimited ) ? $product->unlimited : null;

		// Determine availability based on plugin logic
		$availability = 'https://schema.org/OutOfStock';
		$can_purchase = false;

		if ( $enabled && ( $in_stock || ( $quantity !== null && $quantity > 0 ) || $unlimited ) ) {
			$availability = 'https://schema.org/InStock';
			$can_purchase = true;
		} elseif ( $enabled && isset( $in_stock ) && ! $in_stock ) {
			// Check if it's a pre-order scenario
			$availability = 'https://schema.org/PreOrder';
			$can_purchase = true; // Assuming purchasable when out of stock
		}

		?>
		<div style="padding: 15px; background: #f8f9fa; border-left: 4px solid #0073aa;">
			<p>
				<strong><?php esc_html_e( 'Schema.org Availability:', 'peaches' ); ?></strong>
				<span style="padding: 3px 8px; background: <?php echo $can_purchase ? '#d4edda' : '#f8d7da'; ?>; border-radius: 3px;">
					<?php echo esc_html( str_replace( 'https://schema.org/', '', $availability ) ); ?>
				</span>
			</p>
			<p><strong><?php esc_html_e( 'Detection Logic:', 'peaches' ); ?></strong></p>
			<ul style="margin-left: 20px;">
				<li>
					<code>enabled</code>: <?php echo $enabled ? '<span style="color: green;">✓ true</span>' : '<span style="color: red;">✗ false</span>'; ?>
					<strong><?php echo $enabled ? '' : ' ' . esc_html__( '(Product disabled - will be OutOfStock)', 'peaches' ); ?></strong>
				</li>
				<li>
					<code>inStock</code>: <?php echo $in_stock ? '<span style="color: green;">✓ true</span>' : '<span style="color: red;">✗ false</span>'; ?>
				</li>
				<li>
					<code>quantity</code>: <code><?php echo esc_html( $quantity ?? 'null' ); ?></code>
				</li>
				<li>
					<code>unlimited</code>: <?php echo $unlimited ? '<span style="color: green;">✓ true</span>' : '<span style="color: red;">✗ false</span>'; ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Purchasable:', 'peaches' ); ?></strong>
					<?php echo $can_purchase ? '<span style="color: green;">✓ Yes</span>' : '<span style="color: red;">✗ No</span>'; ?>
				</li>
			</ul>
		</div>

		<h3><?php esc_html_e( '📖 How Availability is Determined', 'peaches' ); ?></h3>
		<pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><code><?php
echo esc_html( 'if ($enabled && ($inStock || $quantity > 0 || $unlimited)) {
    $availability = "InStock";
} elseif ($enabled && !$inStock) {
    $availability = "PreOrder"; // Out of stock but purchasable
} else {
    $availability = "OutOfStock";
}' );
		?></code></pre>
		<?php
	}

	/**
	 * Log informational messages.
	 *
	 * Only logs when debug mode is enabled.
	 *
	 * @since 0.6.2
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private function log_info( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) && Peaches_Ecwid_Utilities::is_debug_mode() ) {
			Peaches_Ecwid_Utilities::log_error( '[INFO] [Product Debug] ' . $message, $context );
		}
	}

	/**
	 * Log error messages.
	 *
	 * Always logs, regardless of debug mode.
	 *
	 * @since 0.6.2
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private function log_error( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) ) {
			Peaches_Ecwid_Utilities::log_error( '[Product Debug] ' . $message, $context );
		} else {
			// Fallback logging if utilities class is not available
			error_log( '[Peaches Ecwid] [Product Debug] ' . $message . ( empty( $context ) ? '' : ' - Context: ' . wp_json_encode( $context ) ) );
		}
	}
}
