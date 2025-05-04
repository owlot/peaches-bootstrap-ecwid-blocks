<?php
/**
 * Custom Post Type for Product Ingredients
 *
 * This file defines the custom post type and admin UI for managing
 * product ingredients that will be used in the ingredients block.
 */

class Peaches_Product_Ingredients {
    /**
     * Constructor - Register actions and filters
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_product_ingredients', array($this, 'save_meta_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('manage_product_ingredients_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_product_ingredients_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
    }

    /**
     * Register the Product Ingredients post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Product Ingredients', 'Post type general name', 'ecwid-shopping-cart'),
            'singular_name'         => _x('Product Ingredients', 'Post type singular name', 'ecwid-shopping-cart'),
            'menu_name'             => _x('Product Ingredients', 'Admin Menu text', 'ecwid-shopping-cart'),
            'name_admin_bar'        => _x('Product Ingredients', 'Add New on Toolbar', 'ecwid-shopping-cart'),
            'add_new'               => __('Add New', 'ecwid-shopping-cart'),
            'add_new_item'          => __('Add New Product Ingredients', 'ecwid-shopping-cart'),
            'new_item'              => __('New Product Ingredients', 'ecwid-shopping-cart'),
            'edit_item'             => __('Edit Product Ingredients', 'ecwid-shopping-cart'),
            'view_item'             => __('View Product Ingredients', 'ecwid-shopping-cart'),
            'all_items'             => __('All Product Ingredients', 'ecwid-shopping-cart'),
            'search_items'          => __('Search Product Ingredients', 'ecwid-shopping-cart'),
            'not_found'             => __('No ingredients found.', 'ecwid-shopping-cart'),
            'not_found_in_trash'    => __('No ingredients found in Trash.', 'ecwid-shopping-cart'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-list-view',
            'supports'           => array('title'),
            'show_in_rest'       => true,
        );

        register_post_type('product_ingredients', $args);
    }

    /**
     * Add meta boxes to the Product Ingredients post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'product_ingredients_meta',
            __('Product Ingredients', 'ecwid-shopping-cart'),
            array($this, 'render_meta_box'),
            'product_ingredients',
            'normal',
            'high'
        );

        add_meta_box(
            'product_reference_meta',
            __('Ecwid Product Reference', 'ecwid-shopping-cart'),
            array($this, 'render_product_reference_meta_box'),
            'product_ingredients',
            'side'
        );
    }

    /**
     * Render the Product Reference meta box
     */
    public function render_product_reference_meta_box($post) {
        $ecwid_product_id = get_post_meta($post->ID, '_ecwid_product_id', true);
        $ecwid_product_sku = get_post_meta($post->ID, '_ecwid_product_sku', true);

        // Nonce field for security
        wp_nonce_field('save_product_reference', 'product_reference_nonce');

        ?>
        <p>
            <label for="ecwid_product_id"><?php _e('Ecwid Product ID:', 'ecwid-shopping-cart'); ?></label>
            <input type="text" id="ecwid_product_id" name="ecwid_product_id" value="<?php echo esc_attr($ecwid_product_id); ?>" class="widefat">
        </p>
        <p>
            <label for="ecwid_product_sku"><?php _e('Ecwid Product SKU:', 'ecwid-shopping-cart'); ?></label>
            <input type="text" id="ecwid_product_sku" name="ecwid_product_sku" value="<?php echo esc_attr($ecwid_product_sku); ?>" class="widefat">
        </p>
        <p class="description">
            <?php _e('Enter either the Ecwid Product ID or SKU to link these ingredients to a specific product.', 'ecwid-shopping-cart'); ?>
        </p>
        <?php

        // If we have a product ID, try to show product details
        if (!empty($ecwid_product_id) && function_exists('Ecwid_Product::get_by_id')) {
            $product = Ecwid_Product::get_by_id($ecwid_product_id);
            if ($product) {
                echo '<div class="ecwid-product-info">';
                echo '<p><strong>' . __('Linked Product:', 'ecwid-shopping-cart') . '</strong> ' . esc_html($product->name) . '</p>';
                if (!empty($product->thumbnailUrl)) {
                    echo '<img src="' . esc_url($product->thumbnailUrl) . '" style="max-width:100%;" alt="' . esc_attr($product->name) . '">';
                }
                echo '</div>';
            }
        }
    }

    /**
     * Render the Ingredients meta box
     */
    public function render_meta_box($post) {
        // Retrieve existing ingredient data
        $ingredients = get_post_meta($post->ID, '_product_ingredients', true);
        if (!is_array($ingredients)) {
            $ingredients = [];
        }

        // Nonce field for security
        wp_nonce_field('save_ingredients_meta', 'ingredients_nonce');
        ?>
        <div class="ingredients-meta-box">
            <div class="ingredients-list">
                <div id="ingredients-container">
                    <?php
                    if (empty($ingredients)) {
                        // Add one empty item if no ingredients exist yet
                        $this->render_ingredient_item(array('name' => '', 'description' => ''), 0);
                    } else {
                        foreach ($ingredients as $index => $ingredient) {
                            $this->render_ingredient_item($ingredient, $index);
                        }
                    }
                    ?>
                </div>
                <p>
                    <button type="button" id="add-ingredient" class="button button-primary">
                        <span class="dashicons dashicons-plus" style="margin-top: 3px;"></span>
                        <?php _e('Add New Ingredient', 'ecwid-shopping-cart'); ?>
                    </button>
                </p>
            </div>
        </div>

        <!-- Template for new ingredient items -->
        <script type="text/template" id="ingredient-template">
            <?php $this->render_ingredient_item(array('name' => '', 'description' => ''), '{{INDEX}}'); ?>
        </script>
        <?php
    }

    /**
     * Render a single ingredient item form
     */
    private function render_ingredient_item($ingredient, $index) {
        ?>
        <div class="ingredient-item postbox" data-index="<?php echo esc_attr($index); ?>">
            <div class="postbox-header">
                <h2 class="hndle ui-sortable-handle">
                    <span><?php echo empty($ingredient['name']) ? __('New Ingredient', 'ecwid-shopping-cart') : esc_html($ingredient['name']); ?></span>
                </h2>
                <div class="handle-actions hide-if-no-js">
                    <button type="button" class="handlediv button-link remove-ingredient" aria-expanded="true">
                        <span class="screen-reader-text"><?php _e('Remove Ingredient', 'ecwid-shopping-cart'); ?></span>
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <div class="inside">
                <p>
                    <label for="ingredient_name_<?php echo esc_attr($index); ?>"><?php _e('Ingredient Name:', 'ecwid-shopping-cart'); ?></label>
                    <input type="text" id="ingredient_name_<?php echo esc_attr($index); ?>" name="ingredient_name[]" value="<?php echo esc_attr($ingredient['name']); ?>" class="widefat ingredient-name-field">
                </p>
                <p>
                    <label for="ingredient_description_<?php echo esc_attr($index); ?>"><?php _e('Description:', 'ecwid-shopping-cart'); ?></label>
                    <textarea id="ingredient_description_<?php echo esc_attr($index); ?>" name="ingredient_description[]" rows="4" class="widefat"><?php echo esc_textarea($ingredient['description']); ?></textarea>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Save the meta box data
     */
    public function save_meta_data($post_id) {
        // Check if our nonce is set for ingredients
        if (!isset($_POST['ingredients_nonce']) || !wp_verify_nonce($_POST['ingredients_nonce'], 'save_ingredients_meta')) {
            return;
        }

        // Check if our nonce is set for product reference
        if (!isset($_POST['product_reference_nonce']) || !wp_verify_nonce($_POST['product_reference_nonce'], 'save_product_reference')) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save Ecwid product ID and SKU
        if (isset($_POST['ecwid_product_id'])) {
            update_post_meta($post_id, '_ecwid_product_id', sanitize_text_field($_POST['ecwid_product_id']));
        }

        if (isset($_POST['ecwid_product_sku'])) {
            update_post_meta($post_id, '_ecwid_product_sku', sanitize_text_field($_POST['ecwid_product_sku']));
        }

        // Save ingredients
        $ingredients = [];

        if (isset($_POST['ingredient_name']) && is_array($_POST['ingredient_name'])) {
            $count = count($_POST['ingredient_name']);

            for ($i = 0; $i < $count; $i++) {
                if (!empty($_POST['ingredient_name'][$i])) {
                    $ingredients[] = [
                        'name' => sanitize_text_field($_POST['ingredient_name'][$i]),
                        'description' => wp_kses_post($_POST['ingredient_description'][$i])
                    ];
                }
            }
        }

        update_post_meta($post_id, '_product_ingredients', $ingredients);
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post;

        // Only enqueue on the edit screen for our post type
        if (($hook == 'post.php' || $hook == 'post-new.php') && $post && $post->post_type === 'product_ingredients') {
            wp_enqueue_script(
                'product-ingredients-admin',
                plugin_dir_url(__FILE__) . 'assets/js/admin-product-ingredients.js',
                array('jquery', 'jquery-ui-sortable'),
                '1.0.0',
                true
            );

            wp_enqueue_style(
                'product-ingredients-admin',
                plugin_dir_url(__FILE__) . 'assets/css/admin-product-ingredients.css',
                array(),
                '1.0.0'
            );
        }
    }

    /**
     * Add custom columns to the admin listing
     */
    public function add_custom_columns($columns) {
        $new_columns = array();

        // Insert title first
        if (isset($columns['title'])) {
            $new_columns['title'] = $columns['title'];
        }

        // Add our custom columns
        $new_columns['product_id'] = __('Product ID', 'ecwid-shopping-cart');
        $new_columns['product_sku'] = __('Product SKU', 'ecwid-shopping-cart');
        $new_columns['ingredients_count'] = __('Ingredients', 'ecwid-shopping-cart');

        // Add the remaining columns
        foreach ($columns as $key => $value) {
            if ($key !== 'title') {
                $new_columns[$key] = $value;
            }
        }

        return $new_columns;
    }

    /**
     * Render custom column content
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'product_id':
                $product_id = get_post_meta($post_id, '_ecwid_product_id', true);
                echo esc_html($product_id);
                break;

            case 'product_sku':
                $product_sku = get_post_meta($post_id, '_ecwid_product_sku', true);
                echo esc_html($product_sku);
                break;

            case 'ingredients_count':
                $ingredients = get_post_meta($post_id, '_product_ingredients', true);
                echo is_array($ingredients) ? count($ingredients) : '0';
                break;
        }
    }
}

// Initialize the class
$peaches_product_ingredients = new Peaches_Product_Ingredients();

/**
 * Helper function to get product ingredients by product ID or SKU
 *
 * @param int|string $product_id The Ecwid product ID
 * @return array Array of ingredients
 */
function peaches_get_product_ingredients($product_id) {
    // First try to find by product ID
    $args = array(
        'post_type' => 'product_ingredients',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => '_ecwid_product_id',
                'value' => $product_id,
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);

    // If not found by ID, try by SKU if we have access to the product data
    if (!$query->have_posts() && function_exists('Ecwid_Product::get_by_id')) {
        $product = Ecwid_Product::get_by_id($product_id);

        if ($product && !empty($product->sku)) {
            $args = array(
                'post_type' => 'product_ingredients',
                'posts_per_page' => 1,
                'meta_query' => array(
                    array(
                        'key' => '_ecwid_product_sku',
                        'value' => $product->sku,
                        'compare' => '='
                    )
                )
            );

            $query = new WP_Query($args);
        }
    }

    if ($query->have_posts()) {
        $query->the_post();
        $ingredients = get_post_meta(get_the_ID(), '_product_ingredients', true);
        wp_reset_postdata();
        return is_array($ingredients) ? $ingredients : array();
    }

    return array();
}
