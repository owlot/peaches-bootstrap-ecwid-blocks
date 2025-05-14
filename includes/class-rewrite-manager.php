<?php
/**
 * Rewrite Manager class
 *
 * Handles URL rewriting for product pages.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

// Include the interface
require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-rewrite-manager.php';

/**
 * Class Peaches_Rewrite_Manager
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
class Peaches_Rewrite_Manager implements Peaches_Rewrite_Manager_Interface {
	/**
	 * Ecwid API instance.
	 *
	 * @since  0.1.2
	 * @access private
	 * @var    Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Constructor.
	 *
	 * @since 0.1.2
	 * @param Peaches_Ecwid_API_Interface $ecwid_api Ecwid API instance.
	 */
	public function __construct($ecwid_api) {
		$this->ecwid_api = $ecwid_api;

		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 0.1.2
	 */
	private function init_hooks() {
		add_action('init', array($this, 'add_ecwid_rewrite_rules'), 999);
		add_action('template_redirect', array($this, 'product_template_redirect'), 1);
		add_action('init', array($this, 'check_rewrite_rules'), 1000);
		add_action('wp_head', array($this, 'add_product_og_tags'));
		add_action('wp_footer', array($this, 'set_ecwid_config'), 1000);
	}

	/**
	 * Add Ecwid product rewrite rules with configurable shop paths.
	 *
	 * Creates rewrite rules for each configured language using the shop paths
	 * defined in the multilingual settings.
	 *
	 * @since 0.1.2
	 *
	 * @return void
	 */
	public function add_ecwid_rewrite_rules() {
		add_rewrite_tag( '%ecwid_product_slug%', '([^&]+)' );

		// Get the ID of the product template page
		$template_page = get_page_by_path( 'product-detail' );

		if ( ! $template_page ) {
			$this->register_product_template();
			$template_page = get_page_by_path( 'product-detail' );
		}

		if ( ! $template_page ) {
			error_log( 'Peaches Ecwid: Could not create or find product-detail template page' );
			return;
		}

		$template_page_id = $template_page->ID;

		// Check if we have multilingual configuration
		if ( $this->is_multilingual_site() ) {
			$this->add_multilingual_rewrite_rules( $template_page_id );
		} else {
			$this->add_single_language_rewrite_rules( $template_page_id );
		}
	}

	/**
	 * Add rewrite rules for multilingual sites.
	 *
	 * @since 0.1.2
	 *
	 * @param int $template_page_id Template page ID.
	 *
	 * @return void
	 */
	private function add_multilingual_rewrite_rules( $template_page_id ) {
		$languages = $this->get_available_languages();

		if ( empty( $languages ) ) {
			$this->add_single_language_rewrite_rules( $template_page_id );
			return;
		}

		foreach ( $languages as $lang_code => $language_data ) {
			$shop_path = $this->get_shop_path_for_language( $lang_code );

			if ( empty( $shop_path ) ) {
				continue;
			}

			// For Polylang
			if ( function_exists( 'pll_languages_list' ) ) {
				// Default language (no prefix)
				if ( $language_data['is_default'] ) {
					add_rewrite_rule(
						'^' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
						'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang_code,
						'top'
					);
				}

				// Language with prefix
				add_rewrite_rule(
					'^' . preg_quote( $lang_code, '^' ) . '/' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
					'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang_code,
					'top'
				);
			}
			// For WPML
			elseif ( function_exists( 'icl_get_languages' ) ) {
				$default_lang = apply_filters( 'wpml_default_language', null );

				// Default language (no prefix)
				if ( $lang_code === $default_lang ) {
					add_rewrite_rule(
						'^' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
						'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang_code,
						'top'
					);
				}

				// Language with prefix
				add_rewrite_rule(
					'^' . preg_quote( $lang_code, '^' ) . '/' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
					'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang_code,
					'top'
				);
			}
		}
	}

	/**
	 * Add rewrite rules for single language sites.
	 *
	 * @since 0.1.2
	 *
	 * @param int $template_page_id Template page ID.
	 *
	 * @return void
	 */
	private function add_single_language_rewrite_rules( $template_page_id ) {
		// Get the shop path from utility function (which will use Ecwid settings or default)
		$shop_path = Peaches_Ecwid_Utilities::get_shop_path( true, false ); // Include parents, no trailing slash

		if ( empty( $shop_path ) ) {
			$shop_path = 'shop'; // Ultimate fallback
		}

		// Add rewrite rule for single language
		add_rewrite_rule(
			'^' . preg_quote( $shop_path, '^' ) . '/([^/]+)/?$',
			'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]',
			'top'
		);
	}

	/**
	 * Get available languages from multilingual plugins.
	 *
	 * @since 0.1.2
	 *
	 * @return array Array of languages with codes as keys.
	 */
	private function get_available_languages() {
		$languages = array();

		// Polylang support
		if ( function_exists( 'pll_languages_list' ) && function_exists( 'pll_default_language' ) ) {
			$lang_codes = pll_languages_list( array( 'fields' => 'slug' ) );
			$default_lang = pll_default_language( 'slug' );

			foreach ( $lang_codes as $code ) {
				$languages[ $code ] = array(
					'code' => $code,
					'is_default' => ( $code === $default_lang ),
				);
			}
		}
		// WPML support
		elseif ( function_exists( 'icl_get_languages' ) ) {
			$wpml_languages = icl_get_languages( 'skip_missing=0' );
			$default_lang = apply_filters( 'wpml_default_language', null );

			foreach ( $wpml_languages as $code => $lang ) {
				$languages[ $code ] = array(
					'code' => $code,
					'is_default' => ( $code === $default_lang ),
				);
			}
		}

		return $languages;
	}

	/**
	 * Get shop path for specific language.
	 *
	 * @since 0.1.2
	 *
	 * @param string $language_code Language code.
	 *
	 * @return string Shop path for the language.
	 */
	private function get_shop_path_for_language( $language_code ) {
		// Try to get from multilingual settings first
		if ( class_exists( 'Peaches_Multilingual_Settings' ) ) {
			$settings_manager = Peaches_Multilingual_Settings::get_instance();
			return $settings_manager->get_shop_path_for_language( $language_code );
		}

		// Fallback to utility function
		return Peaches_Ecwid_Utilities::get_shop_path( true, false, $language_code );
	}

	/**
	 * Check if the site is configured for multiple languages.
	 *
	 * @since 0.1.2
	 *
	 * @return bool True if multilingual plugin is active and configured.
	 */
	private function is_multilingual_site() {
		// Check for Polylang
		if ( function_exists( 'pll_languages_list' ) ) {
			$languages = pll_languages_list();
			return is_array( $languages ) && count( $languages ) > 1;
		}

		// Check for WPML
		if ( function_exists( 'icl_get_languages' ) ) {
			$languages = icl_get_languages( 'skip_missing=0' );
			return is_array( $languages ) && count( $languages ) > 1;
		}

		return false;
	}

	/**
	 * Handle template redirect for product pages.
	 *
	 * @since 0.1.2
	 */
	public function product_template_redirect() {
		// Check if this is a product URL
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug) {
			// Set a global variable so our template can access the slug
			global $peaches_product_slug;
			$peaches_product_slug = $product_slug;

			// Get the product detail page
			$template_page = get_page_by_path('product-detail');
			if ($template_page) {
				// If Polylang is active, get the translated page
				if (function_exists('pll_get_post')) {
					$current_lang = pll_current_language();
					$translated_page = pll_get_post($template_page->ID, $current_lang);
					if ($translated_page) {
						$template_page = get_post($translated_page);
					}
				}
				// If WPML is active, get the translated page
				elseif (function_exists('icl_object_id')) {
					$current_lang = ICL_LANGUAGE_CODE;
					$translated_page_id = icl_object_id($template_page->ID, 'page', false, $current_lang);
					if ($translated_page_id) {
						$template_page = get_post($translated_page_id);
					}
				}

				// Force WordPress to use our template
				global $wp_query;
				$wp_query->queried_object = $template_page;
				$wp_query->queried_object_id = $template_page->ID;
				$wp_query->is_page = true;
				$wp_query->is_single = false;
				$wp_query->is_home = false;
				$wp_query->is_archive = false;
				$wp_query->is_category = false;
				$wp_query->is_404 = false;
				$wp_query->post = $template_page;
				$wp_query->posts = array($template_page);

				// Prevent Ecwid from taking over
				remove_all_actions('template_redirect', 1);
			}
		}
	}

	/**
	 * Register a page template for product details.
	 *
	 * @since 0.1.2
	 */
	public function register_product_template() {
		// Check if the product detail page exists
		$page_exists = get_page_by_path('product-detail');

		if (!$page_exists) {
			// Create the product detail page
			$page_data = array(
				'post_title'    => 'Product Detail',
				'post_name'     => 'product-detail',
				'post_status'   => 'publish',
				'post_type'     => 'page',
				'post_content'  => '<!-- wp:peaches/ecwid-product-detail /-->',
			);

			wp_insert_post($page_data);
		}
	}

	/**
	 * Add Open Graph tags for product pages.
	 *
	 * @since 0.1.2
	 */
	public function add_product_og_tags() {
		$product_slug = get_query_var('ecwid_product_slug');

		if ($product_slug) {
			$product_id = $this->ecwid_api->get_product_id_from_slug($product_slug);

			if ($product_id) {
				$product = $this->ecwid_api->get_product_by_id($product_id);

				if ($product) {
?>
	<meta property="og:title" content="<?php echo esc_attr($product->name); ?>" />
	<meta property="og:description" content="<?php echo esc_attr(wp_strip_all_tags($product->description)); ?>" />
	<?php if (!empty($product->thumbnailUrl)): ?>
	<meta property="og:image" content="<?php echo esc_url($product->thumbnailUrl); ?>" />
	<?php endif; ?>
<meta property="og:type" content="product" />
<?php
				}
			}
		}
	}

	/**
	 * Set additional Ecwid configuration.
	 *
	 * @since 0.1.2
	 */
	public function set_ecwid_config() {
?>
	<script type="text/javascript">
		window.ec = window.ec || Object();
		window.ec.config = window.ec.config || Object();
		window.ec.config.disable_ajax_navigation = true;
		</script>
<?php
	}

	/**
	 * Force rewrite rules update when needed.
	 *
	 * @since 0.1.2
	 */
	public function check_rewrite_rules() {
		// Check if we need to update rewrite rules
		$activated = get_option('peaches_ecwid_activated', 0);
		$flushed = get_option('peaches_ecwid_flushed', 0);

		if ($activated > $flushed) {
			flush_rewrite_rules(false);
			update_option('peaches_ecwid_flushed', time());
		}
	}
}
