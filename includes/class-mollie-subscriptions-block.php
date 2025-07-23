<?php
/**
 * Mollie Subscription Block class
 *
 * Integrates Mollie subscription functionality with Ecwid products using Gutenberg blocks.
 *
 * @package PeachesEcwidBlocks
 * @since   0.4.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Mollie_Subscription_Block
 *
 * Manages Mollie subscription integration with WordPress and Ecwid.
 *
 * @package PeachesEcwidBlocks
 * @since   0.4.0
 */
class Peaches_Mollie_Subscription_Block {

	/**
	 * Mollie API integration instance.
	 *
	 * @since  0.4.0
	 * @access private
	 * @var    object
	 */
	private $mollie_integration;

	/**
	 * Ecwid API instance.
	 *
	 * @since  0.4.0
	 * @access private
	 * @var    Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Cache instance.
	 *
	 * @since  0.4.0
	 * @access private
	 * @var    Peaches_Cache_Interface
	 */
	private $cache;

	/**
	 * Constructor.
	 *
	 * @since 0.4.0
	 *
	 * @param Peaches_Ecwid_API_Interface $ecwid_api Ecwid API instance.
	 * @param Peaches_Cache_Interface     $cache     Cache instance.
	 */
	public function __construct($ecwid_api, $cache) {
		$this->ecwid_api = $ecwid_api;
		$this->cache = $cache;

		$this->init_hooks();
		$this->init_mollie_integration();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action('init', array($this, 'register_block_type'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// REST API endpoints
		add_action('rest_api_init', array($this, 'register_rest_routes'));

		// AJAX handlers
		add_action('wp_ajax_create_mollie_subscription', array($this, 'ajax_create_subscription'));
		add_action('wp_ajax_nopriv_create_mollie_subscription', array($this, 'ajax_create_subscription'));
		add_action('wp_ajax_get_subscription_plans', array($this, 'ajax_get_subscription_plans'));
		add_action('wp_ajax_nopriv_get_subscription_plans', array($this, 'ajax_get_subscription_plans'));

		// Mollie webhook handler
		add_action('init', array($this, 'handle_mollie_webhooks'));

		// Admin settings
		add_action('admin_init', array($this, 'register_settings'));
	}

	/**
	 * Initialize Mollie integration based on available options.
	 *
	 * Priority order: Direct API > Standalone Plugin > WooCommerce Plugin
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	private function init_mollie_integration() {
		// First priority: Direct Mollie API (recommended for Ecwid users)
		if (get_option('peaches_mollie_api_key') && class_exists('Mollie\\Api\\MollieApiClient')) {
			$this->mollie_integration = new Peaches_Mollie_Direct_Integration();
		}
		// Second priority: Standalone Mollie Payments plugin
		elseif (class_exists('Mollie\\WordPress\\Plugin')) {
			$this->mollie_integration = new Peaches_Mollie_Standalone_Integration();
		}
		// Third priority: WooCommerce Mollie plugin (not recommended for Ecwid-only sites)
		elseif (class_exists('Mollie_WC_Plugin')) {
			$this->mollie_integration = new Peaches_Mollie_WC_Integration();
			add_action('admin_notices', array($this, 'show_woocommerce_notice'));
		}
		else {
			add_action('admin_notices', array($this, 'show_mollie_setup_notice'));
		}
	}

	/**
	 * Register the Gutenberg block type.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function register_block_type() {
		if (!function_exists('register_block_type')) {
			return;
		}

		register_block_type(
			'peaches/mollie-subscription',
			array(
				'attributes'        => array(
					'selectedProductId' => array(
						'type'    => 'number',
						'default' => 0,
					),
					'subscriptionPlans' => array(
						'type'    => 'array',
						'default' => array(),
						'items'   => array(
							'type' => 'object',
						),
					),
					'showPricing' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'buttonText' => array(
						'type'    => 'string',
						'default' => __('Subscribe Now', 'peaches'),
					),
					'buttonStyle' => array(
						'type'    => 'string',
						'default' => 'btn-primary',
					),
					'showDescription' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'layoutStyle' => array(
						'type'    => 'string',
						'default' => 'cards',
					),
					'customCSS' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
				'viewScriptModule'  => plugins_url('build/mollie-subscription/view.js', PEACHES_ECWID_PLUGIN_FILE),
				'editorScript'      => 'peaches-mollie-subscription-editor',
				'editorStyle'       => 'peaches-mollie-subscription-editor',
				'style'             => 'peaches-mollie-subscription',
				'supports'          => array(
					'html'          => false,
					'layout'        => false,
					'interactivity' => true,
				),
			)
		);
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts() {
		if (is_admin()) {
			return;
		}

		// Ensure WordPress Interactivity API is loaded
		wp_enqueue_script('wp-interactivity');

		// Enqueue frontend styles
		wp_enqueue_style(
			'peaches-mollie-subscription',
			plugins_url('build/mollie-subscription/style-index.css', PEACHES_ECWID_PLUGIN_FILE),
			array(),
			PEACHES_ECWID_VERSION
		);

		// Localize script with settings for the interactivity API
		wp_add_inline_script(
			'wp-interactivity',
			'window.PeachesMollieSettings = ' . wp_json_encode(array(
				'ajaxUrl'   => admin_url('admin-ajax.php'),
				'nonce'     => wp_create_nonce('mollie_subscription_nonce'),
				'currency'  => get_option('peaches_mollie_currency', 'EUR'),
				'locale'    => get_locale(),
				'returnUrl' => home_url('/subscription-success/'),
				'cancelUrl' => home_url('/subscription-cancelled/'),
			)) . ';',
			'before'
		);
	}

	/**
	 * Render placeholder when block is not configured.
	 *
	 * @since 0.4.0
	 *
	 * @return string Placeholder HTML.
	 */
	private function render_placeholder() {
		return sprintf(
			'<div class="alert alert-info border-0 shadow-sm">
				<div class="d-flex align-items-center">
					<i class="fas fa-info-circle fs-4 text-info me-3"></i>
					<div>
						<h6 class="alert-heading mb-1">%s</h6>
						<p class="mb-0 small">%s</p>
					</div>
				</div>
			</div>',
			esc_html__('Mollie Subscription Block', 'peaches'),
			esc_html__('Please configure the block by selecting a product and adding subscription plans.', 'peaches')
		);
	}

	/**
	 * Render error message.
	 *
	 * @since 0.4.0
	 *
	 * @param string $message Error message.
	 *
	 * @return string Error HTML.
	 */
	private function render_error($message) {
		return sprintf(
			'<div class="alert alert-danger border-0 shadow-sm">
				<div class="d-flex align-items-center">
					<i class="fas fa-exclamation-triangle fs-4 text-danger me-3"></i>
					<div>
						<h6 class="alert-heading mb-1">%s</h6>
						<p class="mb-0 small">%s</p>
					</div>
				</div>
			</div>',
			esc_html__('Error', 'peaches'),
			esc_html($message)
		);
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts() {
		if (is_admin()) {
			return;
		}

		// Ensure WordPress Interactivity API is loaded
		wp_enqueue_script('wp-interactivity');

		// Enqueue our frontend interactivity script
		wp_enqueue_script(
			'peaches-mollie-subscription',
			plugins_url('build/mollie-subscription/view.js', PEACHES_ECWID_PLUGIN_FILE),
			array('wp-interactivity'),
			PEACHES_ECWID_VERSION,
			true
		);

		// Enqueue frontend styles
		wp_enqueue_style(
			'peaches-mollie-subscription',
			plugins_url('build/mollie-subscription/style-index.css', PEACHES_ECWID_PLUGIN_FILE),
			array(),
			PEACHES_ECWID_VERSION
		);

		// Localize script with settings
		wp_add_inline_script(
			'wp-interactivity',
			'window.PeachesMollieSettings = ' . wp_json_encode(array(
				'ajaxUrl'   => admin_url('admin-ajax.php'),
				'nonce'     => wp_create_nonce('mollie_subscription_nonce'),
				'currency'  => get_option('peaches_mollie_currency', 'EUR'),
				'locale'    => get_locale(),
				'returnUrl' => home_url('/subscription-success/'),
				'cancelUrl' => home_url('/subscription-cancelled/'),
			)) . ';',
			'before'
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 0.4.0
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts($hook) {
		$screen = get_current_screen();

		if (!$screen || !$screen->is_block_editor) {
			return;
		}

		// Enqueue block editor script
		wp_enqueue_script(
			'peaches-mollie-subscription-editor',
			plugins_url('build/mollie-subscription/index.js', PEACHES_ECWID_PLUGIN_FILE),
			array(
				'wp-blocks',
				'wp-element',
				'wp-editor',
				'wp-components',
				'wp-i18n',
				'wp-api-fetch',
			),
			PEACHES_ECWID_VERSION,
			true
		);

		// Enqueue editor styles
		wp_enqueue_style(
			'peaches-mollie-subscription-editor',
			plugins_url('build/mollie-subscription/index.css', PEACHES_ECWID_PLUGIN_FILE),
			array('wp-edit-blocks'),
			PEACHES_ECWID_VERSION
		);

		// Provide settings for the editor
		wp_add_inline_script(
			'peaches-mollie-subscription-editor',
			'window.PeachesMollieEditor = ' . wp_json_encode(array(
				'restUrl'      => rest_url('peaches/v1/'),
				'nonce'        => wp_create_nonce('wp_rest'),
				'currencies'   => $this->get_supported_currencies(),
				'intervals'    => $this->get_supported_intervals(),
				'mollieActive' => $this->mollie_integration !== null,
			)) . ';',
			'before'
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'peaches/v1',
			'/subscription-plans/(?P<product_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'rest_get_subscription_plans'),
				'permission_callback' => array($this, 'rest_permission_check'),
				'args'                => array(
					'product_id' => array(
						'required'          => true,
						'validate_callback' => function($param) {
							return is_numeric($param);
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'peaches/v1',
			'/create-subscription',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'rest_create_subscription'),
				'permission_callback' => '__return_true',
				'args'                => array(
					'product_id' => array(
						'required'          => true,
						'validate_callback' => function($param) {
							return is_numeric($param);
						},
						'sanitize_callback' => 'absint',
					),
					'plan'       => array(
						'required'          => true,
						'validate_callback' => function($param) {
							return is_array($param);
						},
					),
					'customer'   => array(
						'required'          => true,
						'validate_callback' => function($param) {
							return is_array($param) && isset($param['email']);
						},
					),
				),
			)
		);
	}

	/**
	 * REST API permission check.
	 *
	 * @since 0.4.0
	 *
	 * @return bool True if user has permission.
	 */
	public function rest_permission_check() {
		return current_user_can('edit_posts');
	}

	/**
	 * AJAX handler to create Mollie subscription.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function ajax_create_subscription() {
		check_ajax_referer('mollie_subscription_nonce', 'nonce');

		if (!$this->mollie_integration) {
			wp_send_json_error(__('Mollie integration not available', 'peaches'));
		}

		$product_id = absint($_POST['product_id']);
		$plan = $this->sanitize_subscription_plan($_POST['plan']);
		$customer = $this->sanitize_customer_data($_POST['customer']);

		try {
			$subscription = $this->mollie_integration->create_subscription($product_id, $plan, $customer);

			if ($subscription && isset($subscription['checkout_url'])) {
				wp_send_json_success(array(
					'checkout_url' => $subscription['checkout_url'],
					'subscription_id' => $subscription['id'],
				));
			} else {
				wp_send_json_error(__('Failed to create subscription', 'peaches'));
			}
		} catch (Exception $e) {
			error_log('Mollie subscription error: ' . $e->getMessage());
			wp_send_json_error(__('An error occurred while creating the subscription', 'peaches'));
		}
	}

	/**
	 * AJAX handler to get subscription plans for a product.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function ajax_get_subscription_plans() {
		check_ajax_referer('mollie_subscription_nonce', 'nonce');

		$product_id = absint($_POST['product_id']);
		$plans = get_post_meta($product_id, '_mollie_subscription_plans', true);

		if (!$plans) {
			$plans = array();
		}

		wp_send_json_success($plans);
	}

	/**
	 * Handle Mollie webhooks.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function handle_mollie_webhooks() {
		if (!isset($_GET['peaches_mollie_webhook'])) {
			return;
		}

		if (!$this->mollie_integration) {
			status_header(503);
			exit('Mollie integration not available');
		}

		try {
			$this->mollie_integration->handle_webhook();
		} catch (Exception $e) {
			error_log('Mollie webhook error: ' . $e->getMessage());
			status_header(500);
			exit('Webhook processing failed');
		}

		status_header(200);
		exit('OK');
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'peaches_mollie_settings',
			'peaches_mollie_currency',
			array(
				'sanitize_callback' => array($this, 'sanitize_currency'),
			)
		);

		register_setting(
			'peaches_mollie_settings',
			'peaches_mollie_webhook_url',
			array(
				'sanitize_callback' => 'esc_url_raw',
			)
		);
	}

	/**
	 * Show admin notice about WooCommerce being unnecessary for Ecwid users.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function show_woocommerce_notice() {
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e('Mollie Subscription Notice:', 'peaches'); ?></strong>
				<?php esc_html_e('You\'re using WooCommerce Mollie with Ecwid. For better performance and cleaner integration, consider using:', 'peaches'); ?>
			</p>
			<ul>
				<li><strong><?php esc_html_e('Direct API Integration:', 'peaches'); ?></strong>
					<?php esc_html_e('Configure your Mollie API key in Peaches > Ecwid Blocks > Mollie Settings', 'peaches'); ?></li>
				<li><strong><?php esc_html_e('Standalone Plugin:', 'peaches'); ?></strong>
					<a href="https://wordpress.org/plugins/mollie-payments/" target="_blank">
						<?php esc_html_e('Mollie Payments for WordPress', 'peaches'); ?>
					</a></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Show admin notice with setup recommendations for Ecwid users.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function show_mollie_setup_notice() {
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e('Mollie Subscription Setup Required', 'peaches'); ?></strong>
			</p>
			<p><?php esc_html_e('To use Mollie subscriptions with your Ecwid store, choose one of these options:', 'peaches'); ?></p>

			<h4><?php esc_html_e('🎯 Recommended for Ecwid Users:', 'peaches'); ?></h4>
			<ol>
				<li>
					<strong><?php esc_html_e('Direct API Integration (Best Performance)', 'peaches'); ?></strong><br>
					• <?php esc_html_e('Install Mollie PHP SDK via Composer:', 'peaches'); ?> <code>composer require mollie/mollie-api-php</code><br>
					• <?php esc_html_e('Get your API key from', 'peaches'); ?> <a href="https://my.mollie.com/dashboard/developers/api-keys" target="_blank"><?php esc_html_e('Mollie Dashboard', 'peaches'); ?></a><br>
					• <?php esc_html_e('Configure in Peaches > Ecwid Blocks > Mollie Settings', 'peaches'); ?>
				</li>
				<li>
					<strong><?php esc_html_e('Standalone Plugin (Easier Setup)', 'peaches'); ?></strong><br>
					• <?php esc_html_e('Install', 'peaches'); ?>
					<a href="<?php echo admin_url('plugin-install.php?s=mollie+payments&tab=search&type=term'); ?>">
						<?php esc_html_e('Mollie Payments for WordPress', 'peaches'); ?>
					</a>
				</li>
			</ol>

			<h4><?php esc_html_e('❌ Not Recommended:', 'peaches'); ?></h4>
			<p>
				<strong><?php esc_html_e('WooCommerce + Mollie WooCommerce plugin', 'peaches'); ?></strong> -
				<?php esc_html_e('Adds unnecessary complexity and potential conflicts with Ecwid', 'peaches'); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Get supported currencies.
	 *
	 * @since 0.4.0
	 *
	 * @return array Supported currencies.
	 */
	private function get_supported_currencies() {
		return array(
			'EUR' => 'Euro',
			'USD' => 'US Dollar',
			'GBP' => 'British Pound',
			'CHF' => 'Swiss Franc',
			'SEK' => 'Swedish Krona',
			'NOK' => 'Norwegian Krone',
			'DKK' => 'Danish Krone',
			'PLN' => 'Polish Złoty',
		);
	}

	/**
	 * Get supported subscription intervals.
	 *
	 * @since 0.4.0
	 *
	 * @return array Supported intervals.
	 */
	private function get_supported_intervals() {
		return array(
			'1 day'     => __('Daily', 'peaches'),
			'7 days'    => __('Weekly', 'peaches'),
			'14 days'   => __('Bi-weekly', 'peaches'),
			'1 month'   => __('Monthly', 'peaches'),
			'3 months'  => __('Quarterly', 'peaches'),
			'6 months'  => __('Semi-annually', 'peaches'),
			'12 months' => __('Annually', 'peaches'),
		);
	}

	/**
	 * Sanitize subscription plan data.
	 *
	 * @since 0.4.0
	 *
	 * @param array $plan Raw plan data.
	 *
	 * @return array Sanitized plan data.
	 */
	private function sanitize_subscription_plan($plan) {
		return array(
			'name'        => sanitize_text_field($plan['name']),
			'amount'      => floatval($plan['amount']),
			'currency'    => sanitize_text_field($plan['currency']),
			'interval'    => sanitize_text_field($plan['interval']),
			'description' => sanitize_textarea_field($plan['description']),
		);
	}

	/**
	 * Sanitize customer data.
	 *
	 * @since 0.4.0
	 *
	 * @param array $customer Raw customer data.
	 *
	 * @return array Sanitized customer data.
	 */
	private function sanitize_customer_data($customer) {
		return array(
			'email'      => sanitize_email($customer['email']),
			'first_name' => sanitize_text_field($customer['first_name']),
			'last_name'  => sanitize_text_field($customer['last_name']),
			'phone'      => sanitize_text_field($customer['phone']),
		);
	}

	/**
	 * Sanitize currency code.
	 *
	 * @since 0.4.0
	 *
	 * @param string $currency Currency code.
	 *
	 * @return string Sanitized currency code.
	 */
	public function sanitize_currency($currency) {
		$supported = $this->get_supported_currencies();
		return array_key_exists($currency, $supported) ? $currency : 'EUR';
	}
}
