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

        // Add rewrite rule with high priority
        add_rewrite_rule(
            '^winkel/([^/]+)/?$',
            'index.php?page_id=' . $template_page_id . '&ecwid_product_slug=$matches[1]',
            'top'
        );
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
            // Force WordPress to use our template
            global $wp_query;
            $wp_query->is_page = true;
            $wp_query->is_single = false;
            $wp_query->is_home = false;
            $wp_query->is_archive = false;
            $wp_query->is_category = false;
            $wp_query->is_404 = false;
            $wp_query->post = $template_page;

            // Prevent Ecwid from taking over
            remove_all_actions('template_redirect', 1);
        }
    }
}
add_action('template_redirect', 'peaches_product_template_redirect', 1); // Higher priority than Ecwid

/**
 * Disable Ecwid's own product pages when on our custom product URLs
 */
function peaches_disable_ecwid_pages($is_activate) {
    // If this is our custom product URL, disable Ecwid's product page handling
    if (get_query_var('ecwid_product_slug')) {
        return false;
    }
    return $is_activate;
}
add_filter('ecwid_is_activate_html_catalog', 'peaches_disable_ecwid_pages', 20);

/**
 * Priority function to disable Ecwid's store page processing when on our URLs
 */
function peaches_prevent_ecwid_store_page_detect($result) {
    if (get_query_var('ecwid_product_slug')) {
        return false;
    }
    return $result;
}
add_filter('ecwid_is_store_page', 'peaches_prevent_ecwid_store_page_detect', 5);

/**
 * Intercept Ecwid's URL parsing to prevent it from handling our URLs
 */
function peaches_prevent_ecwid_parse_url($parse) {
    if (get_query_var('ecwid_product_slug')) {
        error_log("Custom product template triggered for slug: " . get_query_var('ecwid_product_slug'));
        return false;
    }
    return $parse;
}
add_filter('ecwid_parse_url', 'peaches_prevent_ecwid_parse_url', 5);

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
 * Hijack Ecwid's default URL handling for /winkel/{slug}
 */
add_action('parse_request', function ($wp) {
    if (preg_match('#^winkel/([^/]+)/?$#', $wp->request, $matches)) {
        $slug = sanitize_title($matches[1]);

        // Point to your product-detail page
        $page = get_page_by_path('product-detail');
        if ($page) {
            $wp->query_vars = [
                'page_id' => $page->ID,
                'ecwid_product_slug' => $slug,
            ];
            $wp->matched_rule = 'custom_winkel_override';
            $wp->matched_query = 'index.php?page_id=' . $page->ID . '&ecwid_product_slug=' . $slug;
        }
    }
});
add_filter('query_vars', function ($vars) {
    $vars[] = 'ecwid_product_slug';
    return $vars;
});

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
