<?php
/**
 * Plugin Name:       Peaches Boostrap Ecwid Blocks
 * Description:       Gutenberg blocks created for Ecwid Bootstrap themed components
 * Version:           0.3.4
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Peaches.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       peaches
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants
define('PEACHES_ECWID_VERSION', '0.3.4');
define('PEACHES_ECWID_PLUGIN_FILE', __FILE__);
define('PEACHES_ECWID_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PEACHES_ECWID_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PEACHES_ECWID_INCLUDES_DIR', PEACHES_ECWID_PLUGIN_DIR . 'includes/');
define('PEACHES_ECWID_ASSETS_URL', PEACHES_ECWID_PLUGIN_URL . 'assets/');
// Require the main plugin class
require_once PEACHES_ECWID_INCLUDES_DIR . 'class-ecwid-blocks.php';

// Initialize the plugin
Peaches_Ecwid_Blocks::get_instance();

/**
 * Plugin activation hook
 */
function peaches_bootstrap_ecwid_activate() {
	// Get the instance and register the activation hook
	$instance = Peaches_Ecwid_Blocks::get_instance();
	$instance->activate();
}
register_activation_hook(__FILE__, 'peaches_bootstrap_ecwid_activate');

/**
 * Plugin deactivation hook
 */
function peaches_bootstrap_ecwid_deactivate() {
	// Get the instance and register the deactivation hook
	$instance = Peaches_Ecwid_Blocks::get_instance();
	$instance->deactivate();
}
register_deactivation_hook(__FILE__, 'peaches_bootstrap_ecwid_deactivate');
