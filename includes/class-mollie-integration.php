<?php
/**
 * Direct Mollie API Integration
 *
 * Direct integration using Mollie PHP API client.
 *
 * @since 0.4.0
 */
class Peaches_Mollie_Direct_Integration implements Peaches_Mollie_Integration_Interface {

	/**
	 * Mollie API client
	 *
	 * @var \Mollie\Api\MollieApiClient
	 */
	private $mollie_api;

	/**
	 * Constructor
	 *
	 * @since 0.4.0
	 */
	public function __construct() {
		$this->init_direct_integration();
	}

	/**
	 * Initialize direct API integration
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	private function init_direct_integration() {
		if (class_exists('Mollie\\Api\\MollieApiClient')) {
			$api_key = get_option('peaches_mollie_api_key');

			if ($api_key) {
				$this->mollie_api = new \Mollie\Api\MollieApiClient();
				$this->mollie_api->setApiKey($api_key);
			}
		}
	}

	/**
	 * Create a subscription
	 *
	 * Direct API implementation
	 *
	 * @since 0.4.0
	 */
	public function create_subscription($product_id, $plan, $customer_data) {
		// Direct implementation using Mollie API client
		// This would be similar to WC integration but using direct API calls
		throw new Exception(__('Direct Mollie API integration not yet implemented', 'peaches'));
	}

	/**
	 * Handle webhook notifications
	 *
	 * @since 0.4.0
	 */
	public function handle_webhook() {
		// Direct webhook handling
		throw new Exception(__('Direct Mollie webhook handling not yet implemented', 'peaches'));
	}

	/**
	 * Get subscription by ID
	 *
	 * @since 0.4.0
	 */
	public function get_subscription($subscription_id) {
		return null;
	}

	/**
	 * Cancel subscription
	 *
	 * @since 0.4.0
	 */
	public function cancel_subscription($subscription_id) {
		return false;
	}

	/**
	 * Check if integration is properly configured
	 *
	 * @since 0.4.0
	 */
	public function is_configured() {
		return $this->mollie_api !== null;
	}
}
