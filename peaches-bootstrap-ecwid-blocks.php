<?php
/**
 * Plugin Name:       Peaches Boostrap Ecwid Blocks
 * Description:       Gutenberg blocks created for Ecwid Bootstrap themed components
 * Version:           0.1.2
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
define('PEACHES_ECWID_VERSION', '0.1.2');
define('PEACHES_ECWID_PATH', plugin_dir_path(__FILE__));
define('PEACHES_ECWID_URL', plugin_dir_url(__FILE__));

// Load text domain for translations
add_action('plugins_loaded', 'peaches_load_textdomain');
function peaches_load_textdomain() {
    load_plugin_textdomain('peaches', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Add language detection early for both Polylang and WPML
add_action('init', 'peaches_set_language', 0);
function peaches_set_language() {
    // Polylang support
    if (function_exists('pll_current_language')) {
        add_filter('parse_request', function($wp) {
            if (preg_match('#^winkel/([^/]+)/?$#', $wp->request)) {
                $curlang = pll_current_language();
                if ($curlang) {
                    // The correct way to set language in Polylang
                    add_filter('pll_preferred_language', function() use ($curlang) {
                        return $curlang;
                    });

                    // Set the language for Polylang (this is the correct approach)
                    if (function_exists('pll_set_language')) {
                        pll_set_language($curlang);
                    }
                }
            }
            return $wp;
        }, 1);
    }
    // WPML support
    elseif (defined('ICL_LANGUAGE_CODE') && class_exists('SitePress')) {
        global $sitepress;
        if ($sitepress) {
            add_filter('parse_request', function($wp) {
                if (preg_match('#^winkel/([^/]+)/?$#', $wp->request)) {
                    if (defined('ICL_LANGUAGE_CODE')) {
                        $sitepress->switch_lang(ICL_LANGUAGE_CODE);
                    }
                }
                return $wp;
            }, 1);
        }
    }
}

// Include required files
require_once PEACHES_ECWID_PATH . 'includes/rewrite-rules.php';
require_once PEACHES_ECWID_PATH . 'includes/shop-blocks.php';
require_once PEACHES_ECWID_PATH . 'includes/ingredients-post-type.php';
require_once PEACHES_ECWID_PATH . 'includes/master-ingredients-post-type.php';
require_once PEACHES_ECWID_PATH . 'includes/ingredients-api.php';

/**
 * Plugin activation hook
 */
function peaches_bootstrap_ecwid_activate() {
    // Create the product detail page if needed
    peaches_register_product_template();

    // Flush rewrite rules
    flush_rewrite_rules(true);

    // Store activation timestamp for cache busting
    update_option('peaches_ecwid_activated', time());
}
register_activation_hook(__FILE__, 'peaches_bootstrap_ecwid_activate');

/**
 * Plugin deactivation hook
 */
function peaches_bootstrap_ecwid_deactivate() {
    // Flush rewrite rules to remove our custom ones
    flush_rewrite_rules(true);
}
register_deactivation_hook(__FILE__, 'peaches_bootstrap_ecwid_deactivate');

/**
 * Check if Ecwid plugin is active
 */
function peaches_check_ecwid_plugin() {
    if (!class_exists('Ecwid_Store_Page') && !class_exists('EcwidPlatform')) {
        add_action('admin_notices', 'peaches_missing_ecwid_notice');
    }
}
add_action('admin_init', 'peaches_check_ecwid_plugin');

/**
 * Admin notice for missing Ecwid plugin
 */
function peaches_missing_ecwid_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Peaches Ecwid Custom Product Pages requires the Ecwid Ecommerce Shopping Cart plugin to be installed and activated.', 'peaches-ecwid'); ?></p>
    </div>
    <?php
}

/**
 * Force rewrite rules update when needed
 */
function peaches_init_check() {
    // Check if we need to update rewrite rules
    $activated = get_option('peaches_ecwid_activated', 0);
    $flushed = get_option('peaches_ecwid_flushed', 0);

    if ($activated > $flushed) {
        flush_rewrite_rules(false);
        update_option('peaches_ecwid_flushed', time());
    }
}
add_action('init', 'peaches_init_check', 5);

/**
 * Add settings link to plugin page
 */
function peaches_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ec-store') . '">' . __('Ecwid Settings', 'peaches-ecwid') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'peaches_add_settings_link');
