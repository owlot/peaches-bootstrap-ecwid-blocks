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
		$this->ensure_ecwid_compatibility();

        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_product_ingredients', array($this, 'save_meta_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('manage_product_ingredients_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
		add_action('wp_ajax_search_ecwid_products', array($this, 'ajax_search_products'));

        add_filter('manage_product_ingredients_posts_columns', array($this, 'add_custom_columns'));
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
		<div class="product-selector-container">
			<p>
				<label for="ecwid_product_id"><?php _e('Ecwid Product ID:', 'ecwid-shopping-cart'); ?></label>
				<input type="text" id="ecwid_product_id" name="ecwid_product_id" value="<?php echo esc_attr($ecwid_product_id); ?>" class="widefat">
			</p>
			<p>
				<label for="ecwid_product_sku"><?php _e('Ecwid Product SKU:', 'ecwid-shopping-cart'); ?></label>
				<input type="text" id="ecwid_product_sku" name="ecwid_product_sku" value="<?php echo esc_attr($ecwid_product_sku); ?>" class="widefat">
			</p>
			<p class="description">
				<?php _e('Enter the product ID or SKU to link these ingredients to a specific product.', 'ecwid-shopping-cart'); ?>
			</p>

			<!-- Simple product search -->
			<div class="simple-product-search">
				<h4><?php _e('Search Products', 'ecwid-shopping-cart'); ?></h4>
				<input type="text" id="product-search" class="widefat" placeholder="<?php esc_attr_e('Search for products...', 'ecwid-shopping-cart'); ?>">
				<div id="product-search-results" class="product-search-results" style="border: 1px solid #ddd; margin-top: 10px; display: none; max-height: 300px; overflow-y: auto;"></div>
			</div>
		</div>

		<?php
		// If we have a product ID, try to show product details
		if (!empty($ecwid_product_id) && function_exists('Ecwid_Product::get_by_id')) {
			$product = Ecwid_Product::get_by_id($ecwid_product_id);
			if ($product) {
				echo '<div class="ecwid-product-info" style="margin-top: 20px; padding: 10px; background: #f5f5f5; border-radius: 3px;">';
				echo '<h4>' . __('Linked Product:', 'ecwid-shopping-cart') . '</h4>';
				echo '<p><strong>' . esc_html($product->name) . '</strong></p>';
				if (!empty($product->thumbnailUrl)) {
					echo '<img src="' . esc_url($product->thumbnailUrl) . '" style="max-width:200px; margin-top: 10px;" alt="' . esc_attr($product->name) . '">';
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

	private function render_ingredient_item($ingredient, $index) {
		// Check if this is linked to a master ingredient
		$master_id = isset($ingredient['master_id']) ? $ingredient['master_id'] : '';
		$is_custom = !$master_id;
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
				<!-- Ingredient type selector -->
				<p>
					<label><?php _e('Ingredient Type:', 'ecwid-shopping-cart'); ?></label><br>
					<label>
						<input type="radio" name="ingredient_type[<?php echo esc_attr($index); ?>]"
							   value="master" <?php checked(!$is_custom); ?> class="ingredient-type-radio">
						<?php _e('From Library', 'ecwid-shopping-cart'); ?>
					</label>
					<label>
						<input type="radio" name="ingredient_type[<?php echo esc_attr($index); ?>]"
							   value="custom" <?php checked($is_custom); ?> class="ingredient-type-radio">
						<?php _e('Custom', 'ecwid-shopping-cart'); ?>
					</label>
				</p>

				<!-- Master ingredient selector -->
				<div class="master-ingredient-selector" style="<?php echo $is_custom ? 'display:none;' : ''; ?>">
					<p>
						<label for="master_ingredient_<?php echo esc_attr($index); ?>"><?php _e('Select Ingredient:', 'ecwid-shopping-cart'); ?></label>
						<select id="master_ingredient_<?php echo esc_attr($index); ?>"
								name="master_ingredient_id[]"
								class="widefat master-ingredient-select">
							<option value=""><?php _e('Select an ingredient...', 'ecwid-shopping-cart'); ?></option>
							<?php
							$master_ingredients = get_posts(array(
								'post_type' => 'master_ingredient',
								'posts_per_page' => -1,
								'orderby' => 'title',
								'order' => 'ASC'
							));
							foreach ($master_ingredients as $mi) {
								printf('<option value="%d" %s>%s</option>',
									$mi->ID,
									selected($master_id, $mi->ID, false),
									esc_html($mi->post_title)
								);
							}
							?>
						</select>
					</p>
				</div>

				<!-- Custom ingredient fields -->
				<div class="custom-ingredient-fields" style="<?php echo !$is_custom ? 'display:none;' : ''; ?>">
					<p>
						<label for="ingredient_name_<?php echo esc_attr($index); ?>"><?php _e('Ingredient Name:', 'ecwid-shopping-cart'); ?></label>
						<input type="text" id="ingredient_name_<?php echo esc_attr($index); ?>"
							   name="ingredient_name[]"
							   value="<?php echo esc_attr($ingredient['name']); ?>"
							   class="widefat ingredient-name-field">
					</p>
					<p>
						<label for="ingredient_description_<?php echo esc_attr($index); ?>"><?php _e('Description:', 'ecwid-shopping-cart'); ?></label>
						<textarea id="ingredient_description_<?php echo esc_attr($index); ?>"
								  name="ingredient_description[]"
								  rows="4"
								  class="widefat"><?php echo esc_textarea($ingredient['description']); ?></textarea>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta data
	 */
	public function save_meta_data($post_id) {
		// Check if our nonce is set
		if (!isset($_POST['ingredients_nonce'])) {
			return;
		}

		// Verify that the nonce is valid
		if (!wp_verify_nonce($_POST['ingredients_nonce'], 'save_ingredients_meta')) {
			return;
		}

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check the user's permissions
		if (isset($_POST['post_type']) && 'product_ingredients' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_id)) {
				return;
			}
		} else {
			if (!current_user_can('edit_post', $post_id)) {
				return;
			}
		}

		// Save product reference data
		if (isset($_POST['ecwid_product_id'])) {
			update_post_meta($post_id, '_ecwid_product_id', sanitize_text_field($_POST['ecwid_product_id']));
		}
		if (isset($_POST['ecwid_product_sku'])) {
			update_post_meta($post_id, '_ecwid_product_sku', sanitize_text_field($_POST['ecwid_product_sku']));
		}

		// Save ingredients
		$ingredients = [];

		if (isset($_POST['ingredient_type']) && is_array($_POST['ingredient_type'])) {
			$types = $_POST['ingredient_type'];
			$names = isset($_POST['ingredient_name']) ? $_POST['ingredient_name'] : array();
			$descriptions = isset($_POST['ingredient_description']) ? $_POST['ingredient_description'] : array();
			$master_ids = isset($_POST['master_ingredient_id']) ? $_POST['master_ingredient_id'] : array();

			foreach ($types as $index => $type) {
				if ($type === 'master' && !empty($master_ids[$index])) {
					$ingredients[] = [
						'type' => 'master',
						'master_id' => intval($master_ids[$index]),
					];
				} elseif ($type === 'custom' && !empty($names[$index])) {
					$ingredients[] = [
						'type' => 'custom',
						'name' => sanitize_text_field($names[$index]),
						'description' => wp_kses_post($descriptions[$index])
					];
				}
			}
		}

		update_post_meta($post_id, '_product_ingredients', $ingredients);
	}

	/**
	 * AJAX handler for product search
	 */
	public function ajax_search_products() {
		check_ajax_referer('search_ecwid_products', 'nonce');

		$query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

		if (empty($query)) {
			wp_send_json_error(array('message' => __('Search query is required', 'ecwid-shopping-cart')));
		}

		$products = array();

		if (class_exists('Ecwid_Api_V3')) {
			try {
				$api = new Ecwid_Api_V3();

				// Try multiple search strategies
				$search_params = array(
					'limit' => 10,
					'enabled' => true,
				);

				// First, search by product name
				$search_params['keyword'] = $query;
				$result = $api->get_products($search_params);

				$products = array();
				if ($result && isset($result->items)) {
					foreach ($result->items as $product) {
						$products[] = array(
							'id' => $product->id,
							'name' => $product->name,
							'sku' => $product->sku,
							'price' => isset($product->price) ? $product->price : null,
							'matchType' => 'name'
						);
					}
				}

				// If no results, try searching by SKU
				if (empty($products)) {
					unset($search_params['keyword']);
					$search_params['sku'] = $query;
					$result = $api->get_products($search_params);

					if ($result && isset($result->items)) {
						foreach ($result->items as $product) {
							$products[] = array(
								'id' => $product->id,
								'name' => $product->name,
								'sku' => $product->sku,
								'price' => isset($product->price) ? $product->price : null,
								'matchType' => 'sku'
							);
						}
					}
				}

			} catch (Exception $e) {
				wp_send_json_error(array('message' => $e->getMessage()));
			}
		}

		wp_send_json_success(array('products' => $products));
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
				PEACHES_ECWID_URL . 'assets/js/admin-product-ingredients.js',
				array('jquery', 'jquery-ui-sortable'),
				'1.0.0',
				true
			);

			wp_enqueue_style(
				'product-ingredients-admin',
				PEACHES_ECWID_URL . 'assets/css/admin-product-ingredients.css',
				array(),
				'1.0.0'
			);

			// Get master ingredients for JavaScript
			$master_ingredients = get_posts(array(
				'post_type' => 'master_ingredient',
				'posts_per_page' => -1,
				'orderby' => 'title',
				'order' => 'ASC'
			));

			$master_ingredients_array = array();
			foreach ($master_ingredients as $mi) {
				$master_ingredients_array[] = array(
					'id' => $mi->ID,
					'title' => $mi->post_title
				);
			}

			// Localize script with texts and data
			wp_localize_script(
				'product-ingredients-admin',
				'EcwidIngredientsParams',
				array(
					'newIngredientText' => __('New Ingredient', 'ecwid-shopping-cart'),
					'chooseProductText' => __('Choose Product', 'ecwid-shopping-cart'),
					'changeProductText' => __('Change Product', 'ecwid-shopping-cart'),
					'searchNonce' => wp_create_nonce('search_ecwid_products'),
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'masterIngredients' => $master_ingredients_array
				)
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

	/**
	 * Check if required Ecwid functions are available
	 */
	private function ensure_ecwid_compatibility() {
		// Simple fallback for get_ecwid_store_id
		if (!function_exists('get_ecwid_store_id')) {
			function get_ecwid_store_id() {
				// Try different ways to get the store ID
				if (defined('ECWID_STORE_ID')) {
					return ECWID_STORE_ID;
				}

				$store_id = get_option('ecwid_store_id');
				if ($store_id) {
					return $store_id;
				}

				return ''; // Return empty string as fallback
			}
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
