<?php
/**
 * Product Settings Manager class (formerly Ingredients Manager)
 *
 * Handles operations related to product settings including ingredients, media, and product lines.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

// Include the interface
require_once PEACHES_ECWID_INCLUDES_DIR . 'interfaces/interface-ingredients-manager.php';

/**
 * Class Peaches_Product_Settings_Manager
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Product_Settings_Manager implements Peaches_Ingredients_Manager_Interface {
	/**
	 * Ecwid API instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Product Lines Manager instance.
	 *
	 * @since  0.2.0
	 * @access private
	 * @var    Peaches_Product_Lines_Manager
	 */
	private $lines_manager;

	/**
	 * Constructor.
	 *
	 * @since 0.2.0
	 * @param Peaches_Ecwid_API_Interface $ecwid_api Ecwid API instance.
	 */
	public function __construct($ecwid_api) {
		$this->ecwid_api = $ecwid_api;

		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Set the lines manager instance.
	 *
	 * @since 0.2.0
	 * @param Peaches_Product_Lines_Manager $lines_manager Lines manager instance.
	 */
	public function set_lines_manager($lines_manager) {
		$this->lines_manager = $lines_manager;
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 0.2.0
	 */
	private function init_hooks() {
		add_action('init', array($this, 'register_post_type'));
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		add_action('save_post_product_settings', array($this, 'save_meta_data'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('manage_product_settings_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
		add_action('wp_ajax_search_ecwid_products', array($this, 'ajax_search_products'));
		add_action('rest_api_init', array($this, 'register_api_routes'));
		add_action('save_post_product_settings', array($this, 'register_translation_strings'), 10, 1);

		add_filter('manage_product_settings_posts_columns', array($this, 'add_custom_columns'));
	}

	/**
	 * Register the Product Settings post type.
	 *
	 * @since 0.2.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x('Product Settings', 'Post type general name', 'peaches'),
			'singular_name'         => _x('Product Settings', 'Post type singular name', 'peaches'),
			'menu_name'             => _x('Product Settings', 'Admin Menu text', 'peaches'),
			'name_admin_bar'        => _x('Product Settings', 'Add New on Toolbar', 'peaches'),
			'add_new'               => __('Add New', 'peaches'),
			'add_new_item'          => __('Add New Product Settings', 'peaches'),
			'new_item'              => __('New Product Settings', 'peaches'),
			'edit_item'             => __('Edit Product Settings', 'peaches'),
			'view_item'             => __('View Product Settings', 'peaches'),
			'all_items'             => __('All Product Settings', 'peaches'),
			'search_items'          => __('Search Product Settings', 'peaches'),
			'not_found'             => __('No product settings found.', 'peaches'),
			'not_found_in_trash'    => __('No product settings found in Trash.', 'peaches'),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => true,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-admin-settings',
			'supports'           => array('title'),
			'show_in_rest'       => true,
		);

		register_post_type('product_settings', $args);
	}

	/**
	 * Add meta boxes to the Product Settings post type.
	 *
	 * @since 0.2.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'product_reference_meta',
			__('Ecwid Product Reference', 'peaches'),
			array($this, 'render_product_reference_meta_box'),
			'product_settings',
			'side'
		);

		add_meta_box(
			'product_tags_meta',
			__('Product Tags', 'peaches'),
			array($this, 'render_tags_meta_box'),
			'product_settings',
			'side'
		);

		add_meta_box(
			'product_ingredients_meta',
			__('Product Ingredients', 'peaches'),
			array($this, 'render_ingredients_meta_box'),
			'product_settings',
			'normal',
			'high'
		);

		add_meta_box(
			'product_lines_meta',
			__('Product Lines', 'peaches'),
			array($this, 'render_lines_meta_box'),
			'product_settings',
			'normal',
			'high'
		);

		add_meta_box(
			'product_media_meta',
			__('Named Product Media', 'peaches'),
			array($this, 'render_media_meta_box'),
			'product_settings',
			'normal',
			'high'
		);
	}

	/**
	 * Render the Product Reference meta box.
	 *
	 * @since 0.2.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_product_reference_meta_box($post) {
		$ecwid_product_id = get_post_meta($post->ID, '_ecwid_product_id', true);
		$ecwid_product_sku = get_post_meta($post->ID, '_ecwid_product_sku', true);

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
				<?php _e('Enter the product ID or SKU to link these settings to a specific product.', 'peaches'); ?>
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
 * Render the Named Product Media meta box.
 *
 * @since 0.2.0
 * @param WP_Post $post Current post object.
 */
public function render_media_meta_box($post) {
	// Get current product media
	$product_media = get_post_meta($post->ID, '_product_media', true);
	if (!is_array($product_media)) {
		$product_media = array();
	}

	// Get all available media tags
	$media_tags_manager = new Peaches_Media_Tags_Manager();
	$available_tags = $media_tags_manager->get_all_tags();

	// Convert product media to tag-based array for easier handling
	$media_by_tag = array();
	foreach ($product_media as $media_item) {
		if (isset($media_item['tag_name'])) {
			$media_by_tag[$media_item['tag_name']] = $media_item;
		}
	}

	wp_nonce_field('save_product_media', 'product_media_nonce');
	?>
	<div class="product-media-meta-box">
		<div class="d-flex justify-content-between align-items-start mb-3">
			<div>
				<p class="description mb-2">
					<?php _e('Assign media files to predefined tags for this product. Each tag can have one media item.', 'peaches'); ?>
				</p>
				<?php if (empty($available_tags)): ?>
					<div class="alert alert-warning">
						<strong><?php _e('No media tags available.', 'peaches'); ?></strong>
						<?php _e('Please create some media tags first.', 'peaches'); ?>
						<a href="<?php echo admin_url('admin.php?page=peaches-media-tags'); ?>" class="btn btn-sm btn-outline-primary ms-2">
							<?php _e('Manage Media Tags', 'peaches'); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
			<?php if (!empty($available_tags)): ?>
				<a href="<?php echo admin_url('admin.php?page=peaches-media-tags'); ?>" class="btn btn-sm btn-outline-secondary">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php _e('Manage Tags', 'peaches'); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if (!empty($available_tags)): ?>
			<div id="product-media-container">
				<?php
				// Group tags by category for better organization
				$tags_by_category = array();
				foreach ($available_tags as $tag_key => $tag_data) {
					$category = isset($tag_data['category']) ? $tag_data['category'] : 'other';
					$tags_by_category[$category][] = array('key' => $tag_key, 'data' => $tag_data);
				}

				// Define category order and labels
				$category_info = array(
					'primary' => array('label' => __('Primary Images', 'peaches'), 'color' => 'primary'),
					'gallery' => array('label' => __('Gallery Images', 'peaches'), 'color' => 'success'),
					'secondary' => array('label' => __('Secondary Images', 'peaches'), 'color' => 'secondary'),
					'reference' => array('label' => __('Reference Materials', 'peaches'), 'color' => 'info'),
					'media' => array('label' => __('Rich Media', 'peaches'), 'color' => 'warning'),
					'other' => array('label' => __('Other', 'peaches'), 'color' => 'secondary')
				);

				foreach ($category_info as $category => $info):
					if (empty($tags_by_category[$category])) continue;
					?>
					<div class="card mb-3">
						<div class="card-header">
							<h6 class="card-title mb-0">
								<span class="badge bg-<?php echo esc_attr($info['color']); ?> me-2"><?php echo esc_html($info['label']); ?></span>
								<small class="text-muted">(<?php echo count($tags_by_category[$category]); ?> <?php _e('tags', 'peaches'); ?>)</small>
							</h6>
						</div>
						<div class="card-body">
							<div class="row g-3">
								<?php foreach ($tags_by_category[$category] as $tag): ?>
									<?php
									$tag_key = $tag['key'];
									$tag_data = $tag['data'];
									$current_media = isset($media_by_tag[$tag_key]) ? $media_by_tag[$tag_key] : null;
									$attachment_id = $current_media ? $current_media['attachment_id'] : '';
									?>
									<div class="col-md-6 col-lg-4">
										<?php $this->render_media_tag_item($tag_key, $tag_data, $attachment_id, $post->ID); ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render an individual media tag item.
 *
 * @since 0.2.0
 * @param string $tag_key       Tag key
 * @param array  $tag_data      Tag data
 * @param string $attachment_id Current attachment ID
 * @param int    $post_id       Current post ID for Ecwid fallback
 */
private function render_media_tag_item($tag_key, $tag_data, $attachment_id = '', $post_id = 0) {
	$has_media = !empty($attachment_id);
	$fallback_used = false;

	// Check for hero image fallback
	if (!$has_media && $tag_key === 'hero_image' && $post_id) {
		$ecwid_product_id = get_post_meta($post_id, '_ecwid_product_id', true);
		if ($ecwid_product_id) {
			$product = $this->ecwid_api->get_product_by_id($ecwid_product_id);
			if ($product && !empty($product->thumbnailUrl)) {
				$fallback_image_url = $product->thumbnailUrl;
				$fallback_used = true;
			}
		}
	}
	?>
	<div class="media-tag-item border rounded p-3 h-100" data-tag-key="<?php echo esc_attr($tag_key); ?>">
		<div class="text-center mb-3">
			<div class="media-preview bg-light rounded" style="height: 120px; display: flex; align-items: center; justify-content: center;">
				<?php if ($has_media): ?>
					<?php echo wp_get_attachment_image($attachment_id, 'thumbnail', false, array('class' => 'img-fluid rounded', 'style' => 'max-height: 100px;')); ?>
				<?php elseif ($fallback_used): ?>
					<img src="<?php echo esc_url($fallback_image_url); ?>" class="img-fluid rounded" style="max-height: 100px;" alt="<?php _e('Fallback from Ecwid', 'peaches'); ?>">
					<div class="position-absolute bottom-0 end-0 p-1">
						<small class="badge bg-info"><?php _e('From Ecwid', 'peaches'); ?></small>
					</div>
				<?php else: ?>
					<i class="fas fa-image fa-2x text-muted"></i>
				<?php endif; ?>
			</div>
		</div>

		<div class="text-center">
			<h6 class="mb-1"><?php echo esc_html($tag_data['label']); ?></h6>
			<small class="text-muted d-block mb-2">
				<code><?php echo esc_html($tag_key); ?></code>
			</small>
			<?php if (!empty($tag_data['description'])): ?>
				<small class="text-muted d-block mb-3"><?php echo esc_html($tag_data['description']); ?></small>
			<?php endif; ?>

			<input type="hidden"
				   name="product_media[<?php echo esc_attr($tag_key); ?>][tag_name]"
				   value="<?php echo esc_attr($tag_key); ?>">
			<input type="hidden"
				   name="product_media[<?php echo esc_attr($tag_key); ?>][attachment_id]"
				   value="<?php echo esc_attr($attachment_id); ?>"
				   class="media-attachment-id">

			<div class="btn-group-vertical w-100">
				<button type="button" class="btn btn-<?php echo $has_media ? 'outline-secondary' : 'secondary'; ?> btn-sm select-media-button">
					<span class="dashicons dashicons-<?php echo $has_media ? 'update' : 'plus-alt2'; ?>"></span>
					<?php echo $has_media ? __('Change', 'peaches') : __('Select', 'peaches'); ?>
				</button>
				<?php if ($has_media): ?>
					<button type="button" class="btn btn-outline-danger btn-sm remove-media-button">
						<span class="dashicons dashicons-trash"></span>
						<?php _e('Remove', 'peaches'); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

	/**
	 * Render the Product Lines meta box.
	 *
	 * @since 0.2.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_lines_meta_box($post) {
		$selected_lines = wp_get_object_terms($post->ID, 'product_line', array('fields' => 'ids'));
		if (is_wp_error($selected_lines)) {
			$selected_lines = array();
		}

		// Get all available lines
		$all_lines = array();
		if ($this->lines_manager) {
			$all_lines = $this->lines_manager->get_all_lines();
		}

		wp_nonce_field('save_product_lines', 'product_lines_nonce');
?>
		<div class="product-lines-meta-box">
			<?php if (empty($all_lines)): ?>
				<p><?php _e('No product lines available.', 'peaches'); ?>
				<a href="<?php echo admin_url('edit-tags.php?taxonomy=product_line'); ?>" target="_blank">
					<?php _e('Create a product line', 'peaches'); ?>
				</a></p>
			<?php else: ?>
				<p class="description">
					<?php _e('Select which product lines this product belongs to. Products can belong to multiple lines.', 'peaches'); ?>
				</p>

				<div class="lines-checklist" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
					<?php foreach ($all_lines as $line): ?>
						<label style="display: block; margin-bottom: 8px; cursor: pointer;">
							<input type="checkbox"
								   name="product_lines[]"
								   value="<?php echo esc_attr($line->term_id); ?>"
								   <?php checked(in_array($line->term_id, $selected_lines)); ?>>
							<?php echo esc_html($line->name); ?>
							<?php
							$line_type = get_term_meta($line->term_id, 'line_type', true);
							if ($line_type):
							?>
								<span class="description" style="color: #666;"> - <?php echo esc_html($line_type); ?></span>
							<?php endif; ?>
						</label><br>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
<?php
	}

	/**
	 * Render the Product Tags meta box.
	 *
	 * @since 0.2.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_tags_meta_box($post) {
		$tags = wp_get_object_terms($post->ID, 'post_tag');
		$tag_names = array();
		if (!is_wp_error($tags)) {
			$tag_names = wp_list_pluck($tags, 'name');
		}

		wp_nonce_field('save_product_tags', 'product_tags_nonce');
?>
		<div class="product-tags-meta-box">
			<p class="description">
				<?php _e('Add tags to describe product properties like "waterproof", "organic", "limited-edition". These can be used for filtering and search.', 'peaches'); ?>
			</p>
			<input type="text"
				   name="product_tags"
				   value="<?php echo esc_attr(implode(', ', $tag_names)); ?>"
				   class="widefat"
				   placeholder="<?php esc_attr_e('Enter tags separated by commas', 'peaches'); ?>">
			<p class="description">
				<?php _e('Separate tags with commas. Existing tags will be suggested as you type.', 'peaches'); ?>
			</p>
		</div>
<?php
	}

	/**
	 * Render the Ingredients meta box with simple table layout.
	 *
	 * @since 0.2.0
	 * @param WP_Post $post Current post object.
	 */
	public function render_ingredients_meta_box($post) {
		// Retrieve existing ingredient data
		$ingredients = get_post_meta($post->ID, '_product_ingredients', true);
		if (!is_array($ingredients)) {
			$ingredients = array();
		}

		wp_nonce_field('save_ingredients_meta', 'ingredients_nonce');
?>
		<div class="ingredients-meta-box">
			<div class="d-flex justify-content-between align-items-start mb-3">
				<div>
					<p class="description mb-2">
						<?php _e('Select ingredients from your ingredients library for this product. Each ingredient will be displayed on the product page.', 'peaches'); ?>
					</p>
				</div>
				<div>
					<a href="<?php echo esc_url(admin_url('post-new.php?post_type=product_ingredient')); ?>"
					   class="btn btn-sm btn-outline-primary"
					   target="_blank">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php _e('Create New Ingredient', 'peaches'); ?>
					</a>
				</div>
			</div>

			<div class="ingredients-list">
				<div id="ingredients-container">
					<?php if (empty($ingredients)): ?>
						<div class="alert alert-info">
							<p class="mb-2"><?php _e('No ingredients selected yet.', 'peaches'); ?></p>
							<button type="button" id="add-ingredient" class="btn btn-secondary btn-sm">
								<i class="fas fa-plus me-1"></i>
								<?php _e('Add First Ingredient', 'peaches'); ?>
							</button>
						</div>
					<?php else: ?>
						<div class="table-responsive">
							<table class="table table-striped table-hover table-light">
								<thead>
									<tr>
										<th scope="col" class="fw-semibold"><?php _e('Ingredient', 'peaches'); ?></th>
										<th scope="col" class="fw-semibold"><?php _e('Description', 'peaches'); ?></th>
										<th scope="col" class="fw-semibold text-center" style="width: 100px;"><?php _e('Actions', 'peaches'); ?></th>
									</tr>
								</thead>
								<tbody id="ingredients-table-body">
									<?php foreach ($ingredients as $index => $ingredient): ?>
										<?php $this->render_ingredient_table_row($ingredient, $index); ?>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>

						<div class="mt-3">
							<button type="button" id="add-ingredient" class="btn btn-secondary">
								<span class="dashicons dashicons-plus-alt2"></span>
								<?php _e('Add Another Ingredient', 'peaches'); ?>
							</button>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
<?php
	}

	/**
	 * Render an individual ingredient table row.
	 *
	 * @since 0.2.0
	 * @param array $ingredient Ingredient data.
	 * @param int   $index      Item index.
	 */
	private function render_ingredient_table_row($ingredient, $index) {
		$library_id = isset($ingredient['library_id']) ? $ingredient['library_id'] : '';

		// Get ingredient data for display
		$display_name = __('Select an ingredient...', 'peaches');
		$description = '';

		if ($library_id) {
			$ingredient_post = get_post($library_id);
			if ($ingredient_post) {
				$display_name = $ingredient_post->post_title;
				$description = get_post_meta($ingredient_post->ID, '_ingredient_description', true);
			}
		}
?>
		<tr class="ingredient-row" data-index="<?php echo esc_attr($index); ?>">
			<td>
				<select name="product_ingredient_id[]"
						class="form-select library-ingredient-select"
						required>
					<option value=""><?php _e('Select an ingredient...', 'peaches'); ?></option>
<?php
		$product_ingredients = get_posts(array(
			'post_type' => 'product_ingredient',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC'
		));

		if (empty($product_ingredients)) {
			echo '<option value="" disabled>' . __('No ingredients available - create one first', 'peaches') . '</option>';
		} else {
			foreach ($product_ingredients as $pi) {
				printf('<option value="%d" %s>%s</option>',
					$pi->ID,
					selected($library_id, $pi->ID, false),
					esc_html($pi->post_title)
				);
			}
		}
?>
				</select>
			</td>
			<td>
				<div class="ingredient-description text-muted small">
					<?php echo $description ? wp_kses_post($description) : __('Select an ingredient to see its description', 'peaches'); ?>
				</div>
			</td>
			<td class="text-center">
				<button type="button" class="btn btn-sm btn-outline-danger remove-ingredient" title="<?php esc_attr_e('Remove ingredient', 'peaches'); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</td>
		</tr>
<?php
	}

	/**
	 * Save the meta box data.
	 *
	 * @since 0.2.0
	 * @param int $post_id The post ID.
	 */
	public function save_meta_data($post_id) {
		// Check nonces
		if (!isset($_POST['product_reference_nonce']) || !wp_verify_nonce($_POST['product_reference_nonce'], 'save_product_reference')) {
			return;
		}

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check permissions
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Save product reference data
		if (isset($_POST['ecwid_product_id'])) {
			update_post_meta($post_id, '_ecwid_product_id', sanitize_text_field($_POST['ecwid_product_id']));
		}
		if (isset($_POST['ecwid_product_sku'])) {
			update_post_meta($post_id, '_ecwid_product_sku', sanitize_text_field($_POST['ecwid_product_sku']));
		}

		// Save ingredients
		if (isset($_POST['ingredients_nonce']) && wp_verify_nonce($_POST['ingredients_nonce'], 'save_ingredients_meta')) {
			$ingredients = array();

			if (isset($_POST['product_ingredient_id']) && is_array($_POST['product_ingredient_id'])) {
				$library_ids = $_POST['product_ingredient_id'];

				foreach ($library_ids as $library_id) {
					$library_id = intval($library_id);
					if ($library_id > 0) {
						// Verify the ingredient exists
						$ingredient_post = get_post($library_id);
						if ($ingredient_post && $ingredient_post->post_type === 'product_ingredient') {
							$ingredients[] = array(
								'type' => 'library',
								'library_id' => $library_id,
							);
						}
					}
				}
			}

			update_post_meta($post_id, '_product_ingredients', $ingredients);
		}

		// Save product media with predefined tags
		if (isset($_POST['product_media_nonce']) && wp_verify_nonce($_POST['product_media_nonce'], 'save_product_media')) {
			$product_media = array();

			if (isset($_POST['product_media']) && is_array($_POST['product_media'])) {
				foreach ($_POST['product_media'] as $tag_key => $media_data) {
					if (!empty($media_data['attachment_id']) && !empty($media_data['tag_name'])) {
						$attachment_id = absint($media_data['attachment_id']);
						$tag_name = sanitize_text_field($media_data['tag_name']);

						$product_media[] = array(
							'tag_name' => $tag_name,
							'attachment_id' => $attachment_id
						);

						// Mark attachment as product media
						update_post_meta($attachment_id, '_peaches_product_media', true);
						update_post_meta($attachment_id, '_peaches_product_media_tag', $tag_name);
					}
				}
			}

			update_post_meta($post_id, '_product_media', $product_media);
		}

		// Save product lines
		if (isset($_POST['product_lines_nonce']) && wp_verify_nonce($_POST['product_lines_nonce'], 'save_product_lines')) {
			$selected_lines = array();

			if (isset($_POST['product_lines']) && is_array($_POST['product_lines'])) {
				$selected_lines = array_map('absint', $_POST['product_lines']);
			}

			wp_set_object_terms($post_id, $selected_lines, 'product_line');
		}

		// Save product tags
		if (isset($_POST['product_tags_nonce']) && wp_verify_nonce($_POST['product_tags_nonce'], 'save_product_tags')) {
			$tags_input = '';

			if (isset($_POST['product_tags'])) {
				$tags_input = sanitize_text_field($_POST['product_tags']);
			}

			// Convert comma-separated tags to array
			$tags = array_map('trim', explode(',', $tags_input));
			$tags = array_filter($tags); // Remove empty values

			wp_set_object_terms($post_id, $tags, 'post_tag');
		}
	}

	/**
	 * Helper method to get product media by tag
	 *
	 * @since 0.2.0
	 * @param int    $product_id Product ID
	 * @param string $tag_key    Media tag key
	 *
	 * @return string|null Attachment ID or null if not found
	 */
	public function get_product_media_by_tag($product_id, $tag_key) {
		$product_media = get_post_meta($product_id, '_product_media', true);

		if (!is_array($product_media)) {
			return null;
		}

		foreach ($product_media as $media_item) {
			if (isset($media_item['tag_name']) && $media_item['tag_name'] === $tag_key) {
				return $media_item['attachment_id'];
			}
		}

		return null;
	}

	/**
	 * Helper method to get all product media organized by tag
	 *
	 * @since 0.2.0
	 * @param int $product_id Product ID
	 *
	 * @return array Array of media organized by tag key
	 */
	public function get_product_media_by_tags($product_id) {
		$product_media = get_post_meta($product_id, '_product_media', true);
		$media_by_tag = array();

		if (is_array($product_media)) {
			foreach ($product_media as $media_item) {
				if (isset($media_item['tag_name'])) {
					$media_by_tag[$media_item['tag_name']] = $media_item['attachment_id'];
				}
			}
		}

		return $media_by_tag;
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 0.2.0
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_scripts($hook) {
		global $post;

		if (($hook == 'post.php' || $hook == 'post-new.php') && $post && $post->post_type === 'product_settings') {
			wp_enqueue_media();
			wp_enqueue_script('jquery-ui-sortable');

			wp_enqueue_script(
				'product-settings-admin',
				PEACHES_ECWID_ASSETS_URL . 'js/admin-product-settings.js',
				array('jquery', 'jquery-ui-sortable'),
				PEACHES_ECWID_VERSION,
				true
			);

			wp_enqueue_style(
				'product-settings-admin',
				PEACHES_ECWID_ASSETS_URL . 'css/admin-product-settings.css',
				array(),
				PEACHES_ECWID_VERSION
			);

			// Get product ingredients for JavaScript
			$product_ingredients = get_posts(array(
				'post_type' => 'product_ingredient',
				'posts_per_page' => -1,
				'orderby' => 'title',
				'order' => 'ASC'
			));

			$product_ingredients_array = array();
			foreach ($product_ingredients as $pi) {
				$description = get_post_meta($pi->ID, '_ingredient_description', true);
				$product_ingredients_array[] = array(
					'id' => $pi->ID,
					'title' => $pi->post_title,
					'description' => $description
				);
			}

			wp_localize_script(
				'product-settings-admin',
				'ProductSettingsParams',
				array(
					'newIngredientText' => __('New Ingredient', 'peaches'),
					'newMediaText' => __('New Media', 'peaches'),
					'chooseProductText' => __('Choose Product', 'peaches'),
					'changeProductText' => __('Change Product', 'peaches'),
					'searchNonce' => wp_create_nonce('search_ecwid_products'),
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'productIngredients' => $product_ingredients_array,
					'newIngredientUrl' => admin_url('post-new.php?post_type=product_ingredient'),
					'selectMediaTitle' => __('Select Product Media', 'peaches'),
					'selectMediaButton' => __('Use this media', 'peaches'),
				)
			);
		}
	}

	// Keep all existing methods for API compatibility
	public function add_custom_columns($columns) {
		$new_columns = array();

		if (isset($columns['title'])) {
			$new_columns['title'] = $columns['title'];
		}

		$new_columns['product_id'] = __('Product ID', 'peaches');
		$new_columns['product_sku'] = __('Product SKU', 'peaches');
		$new_columns['ingredients_count'] = __('Ingredients', 'peaches');
		$new_columns['media_count'] = __('Media', 'peaches');
		$new_columns['lines_count'] = __('Lines', 'peaches');
		$new_columns['tags_count'] = __('Tags', 'peaches');

		foreach ($columns as $key => $value) {
			if ($key !== 'title') {
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

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

			case 'media_count':
				$media = get_post_meta($post_id, '_product_media', true);
				echo is_array($media) ? count($media) : '0';
				break;

			case 'lines_count':
				$lines = wp_get_object_terms($post_id, 'product_line');
				echo is_array($lines) ? count($lines) : '0';
				break;

			case 'tags_count':
				$tags = wp_get_object_terms($post_id, 'post_tag');
				echo is_array($tags) ? count($tags) : '0';
				break;
		}
	}

	// Keep all existing interface methods for backward compatibility
	public function ajax_search_products() {
		check_ajax_referer('search_ecwid_products', 'nonce');

		$query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

		if (empty($query)) {
			wp_send_json_error(array('message' => __('Search query is required', 'peaches')));
		}

		$products = $this->ecwid_api->search_products($query);
		wp_send_json_success(array('products' => $products));
	}

	public function register_api_routes() {
		register_rest_route('peaches/v1', '/product-ingredients/(?P<id>\d+)', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_product_ingredients_api'),
			'permission_callback' => '__return_true',
			'args' => array(
				'id' => array(
					'validate_callback' => function($param, $request, $key) {
						return is_numeric($param);
					}
				),
			),
		));
	}
public function get_product_ingredients_api($request) {
		$product_id = $request['id'];
		$raw_ingredients = $this->get_product_ingredients($product_id);

		if (empty($raw_ingredients)) {
			return new WP_REST_Response(array(
				'status' => 404,
				'message' => __('No ingredients found for this product', 'peaches'),
				'ingredients' => array()
			), 404);
		}

		$current_lang = Peaches_Ecwid_Utilities::get_current_language();
		$processed_ingredients = array();

		foreach ($raw_ingredients as $ingredient) {
			// All ingredients are now from the library
			if (isset($ingredient['type']) && $ingredient['type'] === 'library' && isset($ingredient['library_id'])) {
				$library_post = get_post($ingredient['library_id']);

				if ($library_post) {
					$name_key = $current_lang && $current_lang !== 'en' ?
						'_ingredient_name_' . $current_lang : null;

					$name = $name_key ? get_post_meta($library_post->ID, $name_key, true) : '';

					if (empty($name)) {
						$name = $library_post->post_title;
					}

					$description_key = $current_lang && $current_lang !== 'en' ?
						'_ingredient_description_' . $current_lang :
						'_ingredient_description';

					$description = get_post_meta($library_post->ID, $description_key, true);

					if (empty($description) && $current_lang && $current_lang !== 'en') {
						$description = get_post_meta($library_post->ID, '_ingredient_description', true);
					}

					$processed_ingredients[] = array(
						'name' => $name,
						'description' => $description
					);
				}
			}
		}

		return new WP_REST_Response(array(
			'status' => 200,
			'ingredients' => $processed_ingredients,
			'language' => $current_lang
		), 200);
	}

	public function register_translation_strings($post_id) {
		$ingredients = get_post_meta($post_id, '_product_ingredients', true);

		if (is_array($ingredients)) {
			foreach ($ingredients as $ingredient) {
				// All ingredients are now from the library
				if (isset($ingredient['type']) && $ingredient['type'] === 'library' && isset($ingredient['library_id'])) {
					$library_post = get_post($ingredient['library_id']);
					if ($library_post) {
						$name = $library_post->post_title;
						$description = get_post_meta($library_post->ID, '_ingredient_description', true);

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
				}
			}
		}
	}

	public function get_product_ingredients($product_id) {
		$args = array(
			'post_type' => 'product_settings',
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

		if (!$query->have_posts()) {
			$product = $this->ecwid_api->get_product_by_id($product_id);

			if ($product && !empty($product->sku)) {
				$args = array(
					'post_type' => 'product_settings',
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

	// Getter for lines manager
	public function get_lines_manager() {
		return $this->lines_manager;
	}
}
