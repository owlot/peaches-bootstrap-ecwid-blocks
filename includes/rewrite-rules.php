<?php

require_once plugin_dir_path(__FILE__) . '/utils.php';

/**
 * Function to add Ecwid product rewrite rules
 */
function peaches_add_ecwid_rewrite_rules() {
    add_rewrite_tag('%ecwid_product_slug%', '([^&]+)');

    // Get the ID of the product template page
    $template_page = get_page_by_path('product-detail');

    if ($template_page) {
        $template_page_id = $template_page->ID;

        // Add rewrite rule for default URLs
        add_rewrite_rule(
            '^winkel/([^/]+)/?$',
            'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]',
            'top'
        );

        // Add support for Polylang language prefixes
        if (function_exists('pll_languages_list')) {
            $languages = pll_languages_list();
            foreach ($languages as $lang) {
                add_rewrite_rule(
                    '^' . $lang . '/winkel/([^/]+)/?$',
                    'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang,
                    'top'
                );
            }
        }

        // Add support for WPML language prefixes
        if (defined('ICL_LANGUAGE_CODE') && class_exists('SitePress')) {
            global $sitepress;
            if ($sitepress && method_exists($sitepress, 'get_active_languages')) {
                $languages = $sitepress->get_active_languages();
                foreach ($languages as $lang_code => $lang) {
                    add_rewrite_rule(
                        '^' . $lang_code . '/winkel/([^/]+)/?$',
                        'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]&lang=' . $lang_code,
                        'top'
                    );
                }
            }
        }
    } else {
        peaches_register_product_template();
    }
}
add_action('init', 'peaches_add_ecwid_rewrite_rules', 1);

/**
 * Register a page template for product details
 */
function peaches_register_product_template() {
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
 * Handle template redirect to use our template for product pages
 */
function peaches_product_template_redirect() {
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
add_action('template_redirect', 'peaches_product_template_redirect', 1);

/**
 * Manually flush rewrite rules on plugin activation
 */
function peaches_flush_rewrite_rules() {
	error_log("Custom product template triggered for slug");
    flush_rewrite_rules(true); // Force hard flush
    peaches_add_ecwid_rewrite_rules();
}
register_activation_hook(__FILE__, 'peaches_flush_rewrite_rules');

/**
 * Add Open Graph tags for product pages
 */
function peaches_product_og_tags() {
    $product_slug = get_query_var('ecwid_product_slug');

    if ($product_slug) {
        $product_id = peaches_get_product_id_from_slug($product_slug);

        if ($product_id) {
            $product = Ecwid_Product::get_by_id($product_id);

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
add_action('wp_head', 'peaches_product_og_tags');

/**
 * Set additional Ecwid configuration
 */
function peaches_disable_ecwid_ajax() {
	?>
	<script type="text/javascript">
		window.ec = window.ec || Object();
		window.ec.config = window.ec.config || Object();
		window.ec.config.disable_ajax_navigation = true;
	</script>
	<?php
}
add_action('wp_footer', 'peaches_disable_ecwid_ajax', 1000);
