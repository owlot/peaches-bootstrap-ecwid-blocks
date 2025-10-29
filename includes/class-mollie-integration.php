<?php
/**
 * Direct Mollie API Integration
 *
 * Direct integration using Mollie PHP API client.
 *
 * @package PeachesEcwidBlocks
 * @since   0.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Mollie_Direct_Integration
 *
 * Implements direct Mollie API integration.
 *
 * @package PeachesEcwidBlocks
 * @since   0.4.0
 */
class Peaches_Mollie_Direct_Integration implements Peaches_Mollie_Integration_Interface {

	/**
	 * Mollie API client
	 *
	 * @since 0.4.0
	 * @var \Mollie\Api\MollieApiClient|null
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
		if ( class_exists( 'Mollie\\Api\\MollieApiClient' ) ) {
			$api_key = get_option( 'peaches_mollie_api_key' );

			if ( $api_key ) {
				try {
					$this->mollie_api = new \Mollie\Api\MollieApiClient();
					$this->mollie_api->setApiKey( $api_key );
					$this->log_info( 'Mollie API client initialized successfully' );
				} catch ( Exception $e ) {
					$this->log_error( 'Failed to initialize Mollie API client', array(
						'error' => $e->getMessage(),
					) );
				}
			}
		}
	}

	/**
	 * Create a subscription
	 *
	 * Direct API implementation
	 *
	 * @since 0.4.0
	 *
	 * @param int   $product_id    Product ID.
	 * @param array $plan          Subscription plan details.
	 * @param array $customer_data Customer data.
	 *
	 * @return mixed Subscription data or false on failure.
	 * @throws Exception When direct integration is not implemented.
	 */
	public function create_subscription( $product_id, $plan, $customer_data ) {
		// Direct implementation using Mollie API client
		// This would be similar to WC integration but using direct API calls
		$this->log_error( 'Attempted to use unimplemented direct Mollie API integration' );
		throw new Exception( __( 'Direct Mollie API integration not yet implemented', 'peaches' ) );
	}

	/**
	 * Handle webhook notifications
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 * @throws Exception When webhook handling is not implemented.
	 */
	public function handle_webhook() {
		// Direct webhook handling
		$this->log_error( 'Attempted to use unimplemented webhook handling' );
		throw new Exception( __( 'Direct Mollie webhook handling not yet implemented', 'peaches' ) );
	}

	/**
	 * Get subscription by ID
	 *
	 * @since 0.4.0
	 *
	 * @param string $subscription_id Subscription ID.
	 *
	 * @return mixed|null Subscription data or null if not found.
	 */
	public function get_subscription( $subscription_id ) {
		$this->log_info( 'Get subscription called (not implemented)', array(
			'subscription_id' => $subscription_id,
		) );
		return null;
	}

	/**
	 * Cancel subscription
	 *
	 * @since 0.4.0
	 *
	 * @param string $subscription_id Subscription ID.
	 *
	 * @return bool False (not implemented).
	 */
	public function cancel_subscription( $subscription_id ) {
		$this->log_info( 'Cancel subscription called (not implemented)', array(
			'subscription_id' => $subscription_id,
		) );
		return false;
	}

	/**
	 * Check if integration is properly configured
	 *
	 * @since 0.4.0
	 *
	 * @return bool True if configured, false otherwise.
	 */
	public function is_configured() {
		return $this->mollie_api !== null;
	}

	/**
	 * Log informational messages.
	 *
	 * Only logs when debug mode is enabled.
	 *
	 * @since 0.4.0
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private function log_info( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) && Peaches_Ecwid_Utilities::is_debug_mode() ) {
			Peaches_Ecwid_Utilities::log_error( '[INFO] [Mollie Integration] ' . $message, $context );
		}
	}

	/**
	 * Log error messages.
	 *
	 * Always logs, regardless of debug mode.
	 *
	 * @since 0.4.0
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private function log_error( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) ) {
			Peaches_Ecwid_Utilities::log_error( '[Mollie Integration] ' . $message, $context );
		} else {
			// Fallback logging if utilities class is not available
			error_log( '[Peaches Ecwid] [Mollie Integration] ' . $message . ( empty( $context ) ? '' : ' - Context: ' . wp_json_encode( $context ) ) );
		}
	}
}
