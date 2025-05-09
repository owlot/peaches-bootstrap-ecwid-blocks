<?php
/**
 * Ingredients Manager class
 *
 * Handles operations related to product ingredients.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

// Include the interface
require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-ingredients-manager.php';

/**
 * Class Peaches_Ingredients_Manager
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
class Peaches_Ingredients_Manager implements Peaches_Ingredients_Manager_Interface {
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
		add_action('init', array($this, 'register_post_type'));
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action('save_post_product_ingredients', array($this, 'save_meta_data'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('manage_product_ingredients_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
		add_action('wp_ajax_search_ecwid_products', array($this, 'ajax_search_products'));
		add_action('rest_api_init', array($this, 'register_api_routes'));
		add_action('save_post_product_ingredients', array($this, 'register_translation_strings'), 10, 1);

		add_filter('manage_product_ingredients_posts_columns', array($this, 'add_custom_columns'));
	}

	/**
	 * Register the Product Ingredients post type.
	 *
	 * @since 0.1.2
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x('Product Ingredients', 'Post type general name', 'peaches'),
			'singular_name'         => _x('Product Ingredients', 'Post type singular name', 'peaches'),
			'menu_name'             => _x('Product Ingredients', 'Admin Menu text', 'peaches'),
			'name_admin_bar'        => _x('Product Ingredients', 'Add New on Toolbar', 'peaches'),
			'add_new'               => __('Add New', 'peaches'),
			'add_new_item'          => __('Add New Product Ingredients', 'peaches'),
			'new_item'              => __('New Product Ingredients', 'peaches'),
			'edit_item'             => __('Edit Product Ingredients', 'peaches'),
			'view_item'             => __('View Product Ingredients', 'peaches'),
			'all_items'             => __('All Product Ingredients', 'peaches'),
			'search_items'          => __('Search Product Ingredients', 'peaches'),
			'not_found'             => __('No ingredients found.', 'peaches'),
			'not_found_in_trash'    => __('No ingredients found in Trash.', 'peaches'),
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
	 * Add meta boxes to the Product Ingredients post type.
	 *
	 * @since 0.1.2
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'product_ingredients_meta',
			__('Product Ingredients', 'peaches'),
			array($this, 'render_ingredients_meta_box'),
			'product_ingredients',
			'normal',
			'high'
		);

		add_meta_box(
			'product_reference_meta',
			__('Ecwid Product Reference', 'peaches'),
			array($this, 'render_product_reference_meta_box'),
			'product_ingredients',
			'side'
		);
	}

	/**
	 * Render the Product Reference meta box.
	 *
	 * @since 0.1.2
	 * @param WP_Post $post Current post object.
	 */
	public function render_product_reference_meta_box($post) {
		$ecwid_product_id = get_post_meta($post->ID, '_ecwid_product_id', true);
		$ecwid_product_sku = get_post_meta($post->ID, '_ecwid_product_sku', true);

		// Nonce field for security
		wp_nonce_field('save_product_reference', 'product_reference_nonce');

?>
	<div class="product-selector-container">
	<p>
	<label for="ecwid_product_id"><?php _e('Ecwid Product ID:', 'peaches'); ?></label>
	<input type="text" id="ecwid_product_id" name="ecwid_product_id" value="<?php echo esc_attr($ecwid_product_id); ?>" class="widefat">
	</p>
	<p>
	<label for="ecwid_product_sku"><?php _e('Ecwid Product SKU:', 'peaches'); ?></label>
	<input type="text" id="ecwid_product_sku" name="ecwid_product_sku" value="<?php echo esc_attr($ecwid_product_sku); ?>" class="widefat">
	</p>
	<p class="description">
	<?php _e('Enter the product ID or SKU to link these ingredients to a specific product.', 'peaches'); ?>
		</p>

			<!-- Simple product search -->
			<div class="simple-product-search">
			<h4><?php _e('Search Products', 'peaches'); ?></h4>
			<input type="text" id="product-search" class="widefat" placeholder="<?php esc_attr_e('Search for products...', 'peaches'); ?>">
			<div id="product-search-results" class="product-search-results" style="border: 1px solid #ddd; margin-top: 10px; display: none; max-height: 300px; overflow-y: auto;"></div>
			</div>
			</div>

<?php
		// If we have a product ID, try to show product details
		if (!empty($ecwid_product_id)) {
			$product = $this->ecwid_api->get_product_by_id($ecwid_product_id);
			if ($product) {
				echo '<div class="ecwid-product-info" style="margin-top: 20px; padding: 10px; background: #f5f5f5; border-radius: 3px;">';
				echo '<h4>' . __('Linked Product:', 'peaches') . '</h4>';
				echo '<p><strong>' . esc_html($product->name) . '</strong></p>';
				if (!empty($product->thumbnailUrl)) {
					echo '<img src="' . esc_url($product->thumbnailUrl) . '" style="max-width:200px; margin-top: 10px;" alt="' . esc_attr($product->name) . '">';
				}
				echo '</div>';
			}
		}
	}

	/**
	 * Render the Ingredients meta box.
	 *
	 * @since 0.1.2
	 * @param WP_Post $post Current post object.
	 */
	public function render_ingredients_meta_box($post) {
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
	<?php _e('Add New Ingredient', 'peaches'); ?>
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
	 * Render an individual ingredient item.
	 *
	 * @since 0.1.2
	 * @param array $ingredient Ingredient data.
	 * @param int   $index      Item index.
	 */
	private function render_ingredient_item($ingredient, $index) {
		// Check if this is linked to a master ingredient
		$master_id = isset($ingredient['master_id']) ? $ingredient['master_id'] : '';
		$is_custom = !$master_id;
?>
	<div class="ingredient-item postbox" data-index="<?php echo esc_attr($index); ?>">
	<div class="postbox-header">
	<h2 class="hndle ui-sortable-handle">
	<span><?php echo empty($ingredient['name']) ? __('New Ingredient', 'peaches') : esc_html($ingredient['name']); ?></span>
	</h2>
	<div class="handle-actions hide-if-no-js">
	<button type="button" class="handlediv button-link remove-ingredient" aria-expanded="true">
	<span class="screen-reader-text"><?php _e('Remove Ingredient', 'peaches'); ?></span>
	<span class="dashicons dashicons-trash"></span>
	</button>
	</div>
	</div>
	<div class="inside">
	<!-- Ingredient type selector -->
	<p>
	<label><?php _e('Ingredient Type:', 'peaches'); ?></label><br>
	<label>
	<input type="radio" name="ingredient_type[<?php echo esc_attr($index); ?>]"
	value="master" <?php checked(!$is_custom); ?> class="ingredient-type-radio">
	<?php _e('From Library', 'peaches'); ?>
		</label>
			<label>
			<input type="radio" name="ingredient_type[<?php echo esc_attr($index); ?>]"
			value="custom" <?php checked($is_custom); ?> class="ingredient-type-radio">
			<?php _e('Custom', 'peaches'); ?>
		</label>
			</p>

			<!-- Master ingredient selector -->
			<div class="master-ingredient-selector" style="<?php echo $is_custom ? 'display:none;' : ''; ?>">
			<p>
			<label for="master_ingredient_<?php echo esc_attr($index); ?>"><?php _e('Select Ingredient:', 'peaches'); ?></label>
			<select id="master_ingredient_<?php echo esc_attr($index); ?>"
			name="master_ingredient_id[]"
			class="widefat master-ingredient-select">
				<option value=""><?php _e('Select an ingredient...', 'peaches'); ?></option>
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
	<label for="ingredient_name_<?php echo esc_attr($index); ?>"><?php _e('Ingredient Name:', 'peaches'); ?></label>
	<input type="text" id="ingredient_name_<?php echo esc_attr($index); ?>"
	name="ingredient_name[]"
	value="<?php echo esc_attr($ingredient['name']); ?>"
	class="widefat ingredient-name-field">
		</p>
		<p>
		<label for="ingredient_description_<?php echo esc_attr($index); ?>"><?php _e('Description:', 'peaches'); ?></label>
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
	 * Save the meta box data.
	 *
	 * @since 0.1.2
	 * @param int $post_id The post ID.
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
	 * Enqueue admin scripts and styles.
	 *
	 * @since 0.1.2
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_scripts($hook) {
		global $post;

		// Only enqueue on the edit screen for our post type
		if (($hook == 'post.php' || $hook == 'post-new.php') && $post && $post->post_type === 'product_ingredients') {
			wp_enqueue_script(
				'product-ingredients-admin',
				PEACHES_ECWID_ASSETS_URL . 'js/admin-product-ingredients.js',
				array('jquery', 'jquery-ui-sortable'),
				PEACHES_ECWID_VERSION,
				true
			);

			wp_enqueue_style(
				'product-ingredients-admin',
				PEACHES_ECWID_ASSETS_URL . 'css/admin-product-ingredients.css',
				array(),
				PEACHES_ECWID_VERSION
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
					'newIngredientText' => __('New Ingredient', 'peaches'),
					'chooseProductText' => __('Choose Product', 'peaches'),
					'changeProductText' => __('Change Product', 'peaches'),
					'searchNonce' => wp_create_nonce('search_ecwid_products'),
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'masterIngredients' => $master_ingredients_array
				)
			);
		}
	}

	/**
	 * Add custom columns to the admin listing.
	 *
	 * @since 0.1.2
	 * @param array $columns Current columns.
	 * @return array Modified columns.
	 */
	public function add_custom_columns($columns) {
		$new_columns = array();

		// Insert title first
		if (isset($columns['title'])) {
			$new_columns['title'] = $columns['title'];
		}

		// Add our custom columns
		$new_columns['product_id'] = __('Product ID', 'peaches');
		$new_columns['product_sku'] = __('Product SKU', 'peaches');
		$new_columns['ingredients_count'] = __('Ingredients', 'peaches');

		// Add the remaining columns
		foreach ($columns as $key => $value) {
			if ($key !== 'title') {
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @since 0.1.2
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
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
	 * AJAX handler for product search.
	 *
	 * @since 0.1.2
	 */
	public function ajax_search_products() {
		check_ajax_referer('search_ecwid_products', 'nonce');

		$query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

		if (empty($query)) {
			wp_send_json_error(array('message' => __('Search query is required', 'peaches')));
		}

		$products = $this->ecwid_api->search_products($query);

		wp_send_json_success(array('products' => $products));
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 0.1.2
	 */
	public function register_api_routes() {
		register_rest_route('peaches/v1', '/product-ingredients/(?P<id>\d+)', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_product_ingredients_api'),
			'permission_callback' => '__return_true', // Public endpoint
			'args' => array(
				'id' => array(
					'validate_callback' => function($param, $request, $key) {
						return is_numeric($param);
					}
				),
			),
		));
	}

	/**
	 * Get ingredients for a specific product from the API.
	 *
	 * @since 0.1.2
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function get_product_ingredients_api($request) {
		$product_id = $request['id'];

		// Get ingredients using the existing helper function
		$raw_ingredients = $this->get_product_ingredients($product_id);

		// Check if we found ingredients
		if (empty($raw_ingredients)) {
			return new WP_REST_Response(array(
				'status' => 404,
				'message' => __('No ingredients found for this product', 'peaches'),
				'ingredients' => array()
			), 404);
		}

		// Use the unified language function
		$current_lang = Peaches_Ecwid_Utilities::get_current_language();

		// Process ingredients with multilingual support
		$processed_ingredients = array();
		foreach ($raw_ingredients as $ingredient) {
			if (isset($ingredient['type']) && $ingredient['type'] === 'master' && isset($ingredient['master_id'])) {
				// Get master ingredient data
				$master_post = get_post($ingredient['master_id']);

				if ($master_post) {
					// Get translated name
					$name_key = $current_lang && $current_lang !== 'en' ?
						'_ingredient_name_' . $current_lang :
null;

$name = $name_key ? get_post_meta($master_post->ID, $name_key, true) : '';

// Fallback to default name if translation not found
if (empty($name)) {
	$name = $master_post->post_title;
					}

					// Get translated description
					$description_key = $current_lang && $current_lang !== 'en' ?
						'_ingredient_description_' . $current_lang :
						'_ingredient_description';

					$description = get_post_meta($master_post->ID, $description_key, true);

					// Fallback to default language if translation not found
					if (empty($description) && $current_lang && $current_lang !== 'en') {
						$description = get_post_meta($master_post->ID, '_ingredient_description', true);
					}

					$processed_ingredients[] = array(
						'name' => $name,
						'description' => $description
					);
				}
			} else {
				// Handle custom ingredients (legacy support)
				$name = $ingredient['name'];
				$description = $ingredient['description'];

				$processed_ingredients[] = array(
					'name' => $name,
					'description' => $description
				);
			}
		}

		return new WP_REST_Response(array(
			'status' => 200,
			'ingredients' => $processed_ingredients,
			'language' => $current_lang
		), 200);
	}

	/**
	 * Get product ingredients by product ID or SKU.
	 *
	 * @since 0.1.2
	 * @param int|string $product_id The product ID or SKU.
	 * @return array Array of ingredients.
	 */
	public function get_product_ingredients($product_id) {
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
		if (!$query->have_posts()) {
			$product = $this->ecwid_api->get_product_by_id($product_id);

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

	/**
	 * Register strings for translation.
	 *
	 * @since 0.1.2
	 * @param int $post_id The post ID.
	 */
	public function register_translation_strings($post_id) {
		$ingredients = get_post_meta($post_id, '_product_ingredients', true);

		if (is_array($ingredients)) {
			foreach ($ingredients as $ingredient) {
				// Check if this is a master ingredient
				if (isset($ingredient['type']) && $ingredient['type'] === 'master' && isset($ingredient['master_id'])) {
					// Get master ingredient data
					$master_post = get_post($ingredient['master_id']);
					if ($master_post) {
						$name = $master_post->post_title;
						$description = get_post_meta($master_post->ID, '_ingredient_description', true);

						// Register for translation
						if ($name) {
							if (function_exists('pll_register_string')) {
								pll_register_string('ingredient_name_' . md5($name), $name, 'Ecwid Shopping Cart', false);
							}
							if (function_exists('wpml_register_single_string')) {
								wpml_register_single_string('ecwid-shopping-cart', 'ingredient_name_' . md5($name), $name);
							}
						}

						if ($description) {
							if (function_exists('pll_register_string')) {
								pll_register_string('ingredient_desc_' . md5($description), $description, 'Ecwid Shopping Cart', false);
							}
							if (function_exists('wpml_register_single_string')) {
								wpml_register_single_string('ecwid-shopping-cart', 'ingredient_desc_' . md5($description), $description);
							}
						}
					}
				} elseif (isset($ingredient['name']) && isset($ingredient['description'])) {
					// This is a custom ingredient with the old structure
					if ($ingredient['name']) {
						if (function_exists('pll_register_string')) {
							pll_register_string('ingredient_name_' . md5($ingredient['name']), $ingredient['name'], 'Ecwid Shopping Cart', false);
						}
						if (function_exists('wpml_register_single_string')) {
							wpml_register_single_string('ecwid-shopping-cart', 'ingredient_name_' . md5($ingredient['name']), $ingredient['name']);
						}
					}

					if ($ingredient['description']) {
						if (function_exists('pll_register_string')) {
							pll_register_string('ingredient_desc_' . md5($ingredient['description']), $ingredient['description'], 'Ecwid Shopping Cart', false);
						}
						if (function_exists('wpml_register_single_string')) {
							wpml_register_single_string('ecwid-shopping-cart', 'ingredient_desc_' . md5($ingredient['description']), $ingredient['description']);
						}
					}
				}
			}
		}
	}
}
