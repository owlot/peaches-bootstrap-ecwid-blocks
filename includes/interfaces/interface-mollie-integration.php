<?php
/**
 * Mollie Integration Classes
 *
 * Different integration approaches for various Mollie WordPress plugins.
 *
 * @package PeachesEcwidBlocks
 * @since   0.4.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Base Mollie Integration Interface
 *
 * Defines the contract for all Mollie integration classes.
 *
 * @since 0.4.0
 */
interface Peaches_Mollie_Integration_Interface {

	/**
	 * Create a subscription
	 *
	 * @param int   $product_id    Ecwid product ID
	 * @param array $plan          Subscription plan data
	 * @param array $customer_data Customer information
	 *
	 * @return array Subscription data with checkout URL
	 *
	 * @throws Exception If subscription creation fails
	 */
	public function create_subscription($product_id, $plan, $customer_data);

	/**
	 * Handle webhook notifications from Mollie
	 *
	 * @return void
	 *
	 * @throws Exception If webhook processing fails
	 */
	public function handle_webhook();

	/**
	 * Get subscription by ID
	 *
	 * @param string $subscription_id Mollie subscription ID
	 *
	 * @return array|null Subscription data or null if not found
	 */
	public function get_subscription($subscription_id);

	/**
	 * Cancel subscription
	 *
	 * @param string $subscription_id Mollie subscription ID
	 *
	 * @return bool True if cancelled successfully
	 */
	public function cancel_subscription($subscription_id);

	/**
	 * Check if integration is properly configured
	 *
	 * @return bool True if configured
	 */
	public function is_configured();
}
