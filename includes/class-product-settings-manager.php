<?php
/**
 * Product Settings Manager class (formerly Ingredients Manager)
 *
 * Handles operations related to product settings including ingredients, media, and product lines.
 * Implements proper interfaces and error handling.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Product_Settings_Manager
 *
 * Manages product settings with comprehensive error handling and validation.
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
	 * @var    Peaches_Product_Lines_Manager_Interface
	 */
	private $lines_manager;

	/**
	 * Product Media Manager instance.
	 *
	 * @since  0.2.1
	 * @access private
	 * @var    Peaches_Product_Media_Manager_Interface
	 */
	private $media_manager;

	/**
	 * Cache for settings data.
	 *
	 * @since 0.2.0
	 * @access private
	 * @var array
	 */
	private $cache = array();

	/**
	 * Constructor.
	 *
	 * @since 0.2.0
	 *
	 * @param Peaches_Ecwid_API_Interface $ecwid_api Ecwid API instance.
	 *
	 * @throws InvalidArgumentException If ecwid_api is null.
	 */
	public function __construct($ecwid_api) {
		if (!$ecwid_api instanceof Peaches_Ecwid_API_Interface) {
			throw new InvalidArgumentException('Ecwid API instance is required');
		}

		$this->ecwid_api = $ecwid_api;
		$this->init_hooks();
	}

	/**
	 * Set the lines manager instance.
	 *
	 * @since 0.2.0
	 *
	 * @param Peaches_Product_Lines_Manager_Interface $lines_manager Lines manager instance.
	 *
	 * @return void
	 */
	public function set_lines_manager($lines_manager) {
		if ($lines_manager instanceof Peaches_Product_Lines_Manager_Interface) {
			$this->lines_manager = $lines_manager;
		}
	}

	/**
	 * Set the media manager instance.
	 *
	 * @since 0.2.1
	 *
	 * @param Peaches_Product_Media_Manager_Interface $media_manager Media manager instance.
	 *
	 * @return void
	 */
	public function set_media_manager($media_manager) {
		if ($media_manager instanceof Peaches_Product_Media_Manager_Interface) {
			$this->media_manager = $media_manager;
		}
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 0.2.0
	 *
	 * @return void
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
		add_action('wp_ajax_get_ecwid_product_media', array($this, 'ajax_get_ecwid_product_media'));

		add_filter('manage_product_settings_posts_columns', array($this, 'add_custom_columns'));
	}

	/**
	 * Register the Product Settings post type.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 *
	 * @throws Exception If post type registration fails.
	 */
	public function register_post_type() {
		try {
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

			$result = register_post_type('product_settings', $args);

			if (is_wp_error($result)) {
				throw new Exception('Failed to register product_settings post type: ' . $result->get_error_message());
			}

			$this->log_info('Product Settings post type registered successfully');
		} catch (Exception $e) {
			$this->log_error('Error registering post type', array(
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			));
			throw $e;
		}
	}

	/**
	 * Add meta boxes to the Product Settings post type.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		try {
			add_meta_box(
				'product_reference_meta',
				__('Ecwid Product Reference', 'peaches'),
				array($this, 'render_product_reference_meta_box'),
				'product_settings',
				'side',
				'high'
			);

			add_meta_box(
				'product_tags_meta',
				__('Product Tags', 'peaches'),
				array($this, 'render_tags_meta_box'),
				'product_settings',
				'side'
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
				'product_ingredients_meta',
				__('Product Ingredients', 'peaches'),
				array($this, 'render_ingredients_meta_box'),
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

			$this->log_info('Meta boxes added successfully');
		} catch (Exception $e) {
			$this->log_error('Error adding meta boxes', array(
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Render the Product Reference meta box.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $post Current post object.
	 *
	 * @return void
	 */
	public function render_product_reference_meta_box($post) {
		try {
			if (!$post instanceof WP_Post) {
				throw new InvalidArgumentException('Invalid post object provided');
			}

			$ecwid_product_id = get_post_meta($post->ID, '_ecwid_product_id', true);
			$ecwid_product_sku = get_post_meta($post->ID, '_ecwid_product_sku', true);

			wp_nonce_field('save_product_reference', 'product_reference_nonce');
			?>
			<div class="product-selector-container">
				<p>
					<label for="ecwid_product_id"><?php _e('Ecwid Product ID:', 'peaches'); ?></label>
					<input type="text"
						   id="ecwid_product_id"
						   name="ecwid_product_id"
						   value="<?php echo esc_attr($ecwid_product_id); ?>"
						   class="widefat"
						   pattern="[0-9]+"
						   title="<?php esc_attr_e('Enter a valid product ID (numbers only)', 'peaches'); ?>">
				</p>
				<p>
					<label for="ecwid_product_sku"><?php _e('Ecwid Product SKU:', 'peaches'); ?></label>
					<input type="text"
						   id="ecwid_product_sku"
						   name="ecwid_product_sku"
						   value="<?php echo esc_attr($ecwid_product_sku); ?>"
						   class="widefat"
						   maxlength="100">
				</p>
				<p class="description">
					<?php _e('Enter the product ID or SKU to link these settings to a specific product.', 'peaches'); ?>
				</p>

				<!-- Simple product search -->
				<div class="simple-product-search">
					<h4><?php _e('Search Products', 'peaches'); ?></h4>
					<input type="text"
						   id="product-search"
						   class="widefat"
						   placeholder="<?php esc_attr_e('Search for products...', 'peaches'); ?>"
						   autocomplete="off">
					<div id="product-search-results"
						 class="product-search-results"
						 style="border: 1px solid #ddd; margin-top: 10px; display: none; max-height: 300px; overflow-y: auto;"
						 role="listbox"
						 aria-label="<?php esc_attr_e('Product search results', 'peaches'); ?>">
					</div>
				</div>
			</div>

			<?php
			// If we have a product ID, try to show product details
			if (!empty($ecwid_product_id)) {
				$this->render_linked_product_info($ecwid_product_id);
			}
		} catch (Exception $e) {
			$this->log_error('Error rendering product reference meta box', array(
				'post_id' => $post->ID ?? 'unknown',
				'error' => $e->getMessage()
			));
			echo '<div class="notice notice-error"><p>' . esc_html__('Error loading product reference fields.', 'peaches') . '</p></div>';
		}
	}

	/**
	 * Render linked product information.
	 *
	 * @since 0.2.0
	 *
	 * @param int $product_id Ecwid product ID.
	 *
	 * @return void
	 */
	private function render_linked_product_info($product_id) {
		try {
			$product = $this->ecwid_api->get_product_by_id($product_id);

			if ($product) {
				echo '<div class="ecwid-product-info" style="margin-top: 20px; padding: 10px; background: #f5f5f5; border-radius: 3px;">';
				echo '<h4>' . __('Linked Product:', 'peaches') . '</h4>';
				echo '<p><strong>' . esc_html($product->name) . '</strong></p>';

				if (!empty($product->sku)) {
					echo '<p><em>' . sprintf(__('SKU: %s', 'peaches'), esc_html($product->sku)) . '</em></p>';
				}

				if (!empty($product->thumbnailUrl)) {
					echo '<img src="' . esc_url($product->thumbnailUrl) . '" style="max-width:200px; margin-top: 10px;" alt="' . esc_attr($product->name) . '" loading="lazy">';
				}
				echo '</div>';
			} else {
				echo '<div class="notice notice-warning inline"><p>' . __('Product not found in Ecwid store.', 'peaches') . '</p></div>';
			}
		} catch (Exception $e) {
			$this->log_error('Error rendering linked product info', array(
				'product_id' => $product_id,
				'error' => $e->getMessage()
			));
			echo '<div class="notice notice-error inline"><p>' . __('Error loading product information.', 'peaches') . '</p></div>';
		}
	}

	/**
	 * Render the Product Lines meta box.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $post Current post object.
	 *
	 * @return void
	 */
	public function render_lines_meta_box($post) {
		try {
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
				<div class="d-flex justify-content-between align-items-start mb-3">
					<div>
						<p class="description mb-2">
							<?php _e('Select which product lines this product belongs to. Products can belong to multiple lines.', 'peaches'); ?>
						</p>
					</div>
					<div>
						<a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=product_line')); ?>"
						   class="btn btn-sm btn-outline-primary"
						   target="_blank">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php _e('Create New Product Line', 'peaches'); ?>
						</a>
					</div>
				</div>
				<?php if (empty($all_lines)): ?>
					<p><?php _e('No product lines available.', 'peaches'); ?>
					<a href="<?php echo admin_url('edit-tags.php?taxonomy=product_line'); ?>" target="_blank">
						<?php _e('Create a product line', 'peaches'); ?>
					</a></p>
				<?php else: ?>
					<div class="hstack gap-2">
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
		} catch (Exception $e) {
			$this->log_error('Error rendering lines meta box', array(
				'post_id' => $post->ID ?? 'unknown',
				'error' => $e->getMessage()
			));
			echo '<div class="notice notice-error"><p>' . esc_html__('Error loading product lines.', 'peaches') . '</p></div>';
		}
	}

	/**
	 * Render the Product Tags meta box.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $post Current post object.
	 *
	 * @return void
	 */
	public function render_tags_meta_box($post) {
		try {
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
		} catch (Exception $e) {
			$this->log_error('Error rendering tags meta box', array(
				'post_id' => $post->ID ?? 'unknown',
				'error' => $e->getMessage()
			));
			echo '<div class="notice notice-error"><p>' . esc_html__('Error loading product tags.', 'peaches') . '</p></div>';
		}
	}

	/**
	 * Render the Ingredients meta box with simple table layout.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $post Current post object.
	 *
	 * @return void
	 */
	public function render_ingredients_meta_box($post) {
		try {
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
		} catch (Exception $e) {
			$this->log_error('Error rendering ingredients meta box', array(
				'post_id' => $post->ID ?? 'unknown',
				'error' => $e->getMessage()
			));
			echo '<div class="notice notice-error"><p>' . esc_html__('Error loading ingredients interface.', 'peaches') . '</p></div>';
		}
	}

	/**
	 * Render an individual ingredient table row.
	 *
	 * @since 0.2.0
	 *
	 * @param array $ingredient Ingredient data.
	 * @param int   $index      Item index.
	 *
	 * @return void
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
	 * Render the Named Product Media meta box with enhanced media types.
	 *
	 * @since 0.2.1
	 *
	 * @param WP_Post $post Current post object.
	 *
	 * @return void
	 */
	public function render_media_meta_box($post) {
		try {
			if (!$post instanceof WP_Post) {
				throw new InvalidArgumentException('Invalid post object provided');
			}

			// Initialize Media Manager if not already done
			if (!$this->media_manager) {
				$media_tags_manager = new Peaches_Media_Tags_Manager();
				$this->media_manager = new Peaches_Product_Media_Manager($this->ecwid_api, $media_tags_manager);
			}

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
							<?php _e('Assign media files, URLs, or Ecwid images to predefined tags for this product. Each tag can have one media item from any source.', 'peaches'); ?>
						</p>
						<?php if (empty($available_tags)): ?>
							<div class="alert alert-warning">
								<strong><?php _e('No media tags available.', 'peaches'); ?></strong>
								<?php _e('Please create some media tags first.', 'peaches'); ?>
								<a href="<?php echo esc_url(admin_url('admin.php?page=peaches-ecwid-product-settings&tab=media_tags')); ?>" class="btn btn-sm btn-outline-primary ms-2">
									<?php _e('Manage Media Tags', 'peaches'); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
					<?php if (!empty($available_tags)): ?>
						<a href="<?php echo esc_url(admin_url('admin.php?page=peaches-ecwid-product-settings&tab=media_tags')); ?>" class="btn btn-sm btn-outline-secondary">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php _e('Manage Tags', 'peaches'); ?>
						</a>
					<?php endif; ?>
				</div>

				<?php if (!empty($available_tags)): ?>
					<div id="product-media-container">
						<?php $this->render_media_tags_by_category($available_tags, $media_by_tag, $post->ID); ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		} catch (Exception $e) {
			$this->log_error('Error rendering media meta box', array(
				'post_id' => $post->ID ?? 'unknown',
				'error' => $e->getMessage()
			));
			echo '<div class="notice notice-error"><p>' . esc_html__('Error loading media management interface.', 'peaches') . '</p></div>';
		}
	}

	/**
	 * Render media tags organized by category.
	 *
	 * @since 0.2.1
	 *
	 * @param array $available_tags Available media tags.
	 * @param array $media_by_tag   Media organized by tag.
	 * @param int   $post_id        Post ID.
	 *
	 * @return void
	 */
	private function render_media_tags_by_category($available_tags, $media_by_tag, $post_id) {
		// Group tags by category for better organization
		$tags_by_category = array();
		foreach ($available_tags as $tag_key => $tag_data) {
			$category = $tag_data['category'] ?? 'other';
			$tags_by_category[$category][] = array('key' => $tag_key, 'data' => $tag_data);
		}

		// Define category order and labels
		$category_info = array(
			'primary'   => array('label' => __('Primary Images', 'peaches'), 'color' => 'primary'),
			'gallery'   => array('label' => __('Gallery Images', 'peaches'), 'color' => 'success'),
			'secondary' => array('label' => __('Secondary Images', 'peaches'), 'color' => 'secondary'),
			'reference' => array('label' => __('Reference Materials', 'peaches'), 'color' => 'info'),
			'media'     => array('label' => __('Rich Media', 'peaches'), 'color' => 'warning'),
			'other'     => array('label' => __('Other', 'peaches'), 'color' => 'secondary')
		);

		foreach ($category_info as $category => $info) {
			if (empty($tags_by_category[$category])) {
				continue;
			}
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
							$current_media = $media_by_tag[$tag_key] ?? null;
							?>
							<div class="col-md-6 col-lg-4">
								<?php $this->media_manager->render_media_tag_item($tag_key, $tag_data, $current_media, $post_id); ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Get product ingredients by product ID (Interface implementation).
	 *
	 * @since 0.2.0
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array Array of ingredients.
	 *
	 * @throws InvalidArgumentException If product ID is invalid.
	 */
	public function get_product_ingredients($product_id) {
		if (!is_numeric($product_id) || $product_id <= 0) {
			throw new InvalidArgumentException('Invalid product ID provided');
		}

		// Check cache first
		$cache_key = 'ingredients_' . $product_id;
		if (isset($this->cache[$cache_key])) {
			return $this->cache[$cache_key];
		}

		try {
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
				// Try fallback by SKU
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

			$ingredients = array();
			if ($query->have_posts()) {
				$query->the_post();
				$ingredients = get_post_meta(get_the_ID(), '_product_ingredients', true);
				wp_reset_postdata();

				if (!is_array($ingredients)) {
					$ingredients = array();
				}
			}

			// Cache the result
			$this->cache[$cache_key] = $ingredients;
			return $ingredients;

		} catch (Exception $e) {
			$this->log_error('Error getting product ingredients', array(
				'product_id' => $product_id,
				'error' => $e->getMessage()
			));
			return array();
		}
	}

	/**
	 * Save product ingredients (Interface implementation).
	 *
	 * @since 0.2.0
	 *
	 * @param int   $post_id     Post ID.
	 * @param array $ingredients Array of ingredients.
	 *
	 * @return bool Success status.
	 *
	 * @throws InvalidArgumentException If parameters are invalid.
	 */
	public function save_product_ingredients($post_id, $ingredients) {
		if (!is_numeric($post_id) || $post_id <= 0) {
			throw new InvalidArgumentException('Invalid post ID provided');
		}

		if (!is_array($ingredients)) {
			throw new InvalidArgumentException('Ingredients must be an array');
		}

		try {
			// Validate and sanitize ingredients
			$sanitized_ingredients = array();
			foreach ($ingredients as $ingredient) {
				if (is_array($ingredient) && isset($ingredient['library_id'])) {
					$library_id = absint($ingredient['library_id']);
					if ($library_id > 0) {
						// Verify the ingredient exists
						$ingredient_post = get_post($library_id);
						if ($ingredient_post && $ingredient_post->post_type === 'product_ingredient') {
							$sanitized_ingredients[] = array(
								'type' => 'library',
								'library_id' => $library_id,
							);
						}
					}
				}
			}

			$result = update_post_meta($post_id, '_product_ingredients', $sanitized_ingredients);

			// Clear cache
			$cache_key = 'ingredients_' . get_post_meta($post_id, '_ecwid_product_id', true);
			unset($this->cache[$cache_key]);

			$this->log_info('Product ingredients saved', array(
				'post_id' => $post_id,
				'count' => count($sanitized_ingredients)
			));

			return $result !== false;

		} catch (Exception $e) {
			$this->log_error('Error saving product ingredients', array(
				'post_id' => $post_id,
				'error' => $e->getMessage()
			));
			return false;
		}
	}

	/**
	 * Save the meta box data with enhanced validation and error handling.
	 *
	 * @since 0.2.1
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function save_meta_data($post_id) {
		try {
			// Validate post ID
			if (!is_numeric($post_id) || $post_id <= 0) {
				throw new InvalidArgumentException('Invalid post ID');
			}

			// Check if this is a valid save request
			if (!$this->validate_save_request($post_id)) {
				return;
			}

			// Save each section with individual error handling
			$this->save_product_reference($post_id);
			$this->save_ingredients_data($post_id);
			$this->save_media_data($post_id);
			$this->save_lines_data($post_id);
			$this->save_tags_data($post_id);

			$this->log_info('Product settings saved successfully', array('post_id' => $post_id));

		} catch (Exception $e) {
			$this->log_error('Error saving meta data', array(
				'post_id' => $post_id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			));
		}
	}

	/**
	 * Validate save request.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool True if valid save request.
	 */
	private function validate_save_request($post_id) {
		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return false;
		}

		// Check permissions
		if (!current_user_can('edit_post', $post_id)) {
			$this->log_error('User lacks permission to edit post', array('post_id' => $post_id));
			return false;
		}

		// Check if this is our post type
		$post = get_post($post_id);
		if (!$post || $post->post_type !== 'product_settings') {
			return false;
		}

		return true;
	}

	/**
	 * Save product reference data.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	private function save_product_reference($post_id) {
		if (!isset($_POST['product_reference_nonce']) ||
			!wp_verify_nonce($_POST['product_reference_nonce'], 'save_product_reference')) {
			return;
		}

		try {
			if (isset($_POST['ecwid_product_id'])) {
				$product_id = sanitize_text_field($_POST['ecwid_product_id']);
				if (!empty($product_id) && !is_numeric($product_id)) {
					throw new InvalidArgumentException('Product ID must be numeric');
				}
				update_post_meta($post_id, '_ecwid_product_id', $product_id);
			}

			if (isset($_POST['ecwid_product_sku'])) {
				$sku = sanitize_text_field($_POST['ecwid_product_sku']);
				if (strlen($sku) > 100) {
					throw new InvalidArgumentException('SKU is too long (max 100 characters)');
				}
				update_post_meta($post_id, '_ecwid_product_sku', $sku);
			}

		} catch (Exception $e) {
			$this->log_error('Error saving product reference', array(
				'post_id' => $post_id,
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Save ingredients data.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	private function save_ingredients_data($post_id) {
		if (!isset($_POST['ingredients_nonce']) ||
			!wp_verify_nonce($_POST['ingredients_nonce'], 'save_ingredients_meta')) {
			return;
		}

		try {
			$ingredients = array();

			if (isset($_POST['product_ingredient_id']) && is_array($_POST['product_ingredient_id'])) {
				$library_ids = $_POST['product_ingredient_id'];

				foreach ($library_ids as $library_id) {
					$library_id = absint($library_id);
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

			$this->save_product_ingredients($post_id, $ingredients);

		} catch (Exception $e) {
			$this->log_error('Error saving ingredients data', array(
				'post_id' => $post_id,
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Save media data.
	 *
	 * @since 0.2.1
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	private function save_media_data($post_id) {
		if (!isset($_POST['product_media_nonce']) ||
			!wp_verify_nonce($_POST['product_media_nonce'], 'save_product_media')) {
			return;
		}

		try {
			if (!$this->media_manager) {
				$media_tags_manager = new Peaches_Media_Tags_Manager();
				$this->media_manager = new Peaches_Product_Media_Manager($this->ecwid_api, $media_tags_manager);
			}

			if (isset($_POST['product_media']) && is_array($_POST['product_media'])) {
				$this->media_manager->save_product_media($post_id, $_POST['product_media']);
			}

		} catch (Exception $e) {
			$this->log_error('Error saving media data', array(
				'post_id' => $post_id,
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Save product lines data.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	private function save_lines_data($post_id) {
		if (!isset($_POST['product_lines_nonce']) ||
			!wp_verify_nonce($_POST['product_lines_nonce'], 'save_product_lines')) {
			return;
		}

		try {
			$selected_lines = array();

			if (isset($_POST['product_lines']) && is_array($_POST['product_lines'])) {
				$selected_lines = array_map('absint', $_POST['product_lines']);
				// Validate that all line IDs exist
				$selected_lines = array_filter($selected_lines, function($line_id) {
					return get_term($line_id, 'product_line') !== null;
				});
			}

			wp_set_object_terms($post_id, $selected_lines, 'product_line');

		} catch (Exception $e) {
			$this->log_error('Error saving lines data', array(
				'post_id' => $post_id,
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Save product tags data.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	private function save_tags_data($post_id) {
		if (!isset($_POST['product_tags_nonce']) ||
			!wp_verify_nonce($_POST['product_tags_nonce'], 'save_product_tags')) {
			return;
		}

		try {
			$tags_input = '';

			if (isset($_POST['product_tags'])) {
				$tags_input = sanitize_text_field($_POST['product_tags']);
			}

			// Convert comma-separated tags to array
			$tags = array_map('trim', explode(',', $tags_input));
			$tags = array_filter($tags); // Remove empty values
			$tags = array_slice($tags, 0, 50); // Limit to 50 tags

			wp_set_object_terms($post_id, $tags, 'post_tag');

		} catch (Exception $e) {
			$this->log_error('Error saving tags data', array(
				'post_id' => $post_id,
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Enqueue admin scripts and styles with enhanced error handling.
	 *
	 * @since 0.2.1
	 *
	 * @param string $hook Current admin page.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts($hook) {
		global $post;

		if (($hook !== 'post.php' && $hook !== 'post-new.php') ||
			!$post ||
			$post->post_type !== 'product_settings') {
			return;
		}

		try {
			wp_enqueue_media();
			wp_enqueue_script('jquery-ui-sortable');

			// Main product settings script
			wp_enqueue_script(
				'product-settings-admin',
				PEACHES_ECWID_ASSETS_URL . 'js/admin-product-settings.js',
				array('jquery', 'jquery-ui-sortable'),
				PEACHES_ECWID_VERSION,
				true
			);

			// Media management script
			wp_enqueue_script(
				'product-media-management',
				PEACHES_ECWID_ASSETS_URL . 'js/admin-product-media.js',
				array('jquery', 'media-upload'),
				PEACHES_ECWID_VERSION,
				true
			);

			// Styles
			wp_enqueue_style(
				'product-settings-admin',
				PEACHES_ECWID_ASSETS_URL . 'css/admin-product-settings.css',
				array(),
				PEACHES_ECWID_VERSION
			);

			wp_enqueue_style(
				'product-media-admin',
				PEACHES_ECWID_ASSETS_URL . 'css/admin-product-media.css',
				array(),
				PEACHES_ECWID_VERSION
			);

			// Prepare data for JavaScript
			$this->localize_scripts();

		} catch (Exception $e) {
			$this->log_error('Error enqueuing admin scripts', array(
				'hook' => $hook,
				'post_id' => $post->ID ?? 'unknown',
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Localize scripts with necessary data.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function localize_scripts() {
		try {
			// Get product ingredients for JavaScript
			$product_ingredients = get_posts(array(
				'post_type' => 'product_ingredient',
				'posts_per_page' => -1,
				'orderby' => 'title',
				'order' => 'ASC',
				'post_status' => 'publish'
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

			// Localize main script
			wp_localize_script(
				'product-settings-admin',
				'ProductSettingsParams',
				array(
					'newIngredientText' => __('New Ingredient', 'peaches'),
					'chooseProductText' => __('Choose Product', 'peaches'),
					'changeProductText' => __('Change Product', 'peaches'),
					'searchNonce' => wp_create_nonce('search_ecwid_products'),
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'productIngredients' => $product_ingredients_array,
					'newIngredientUrl' => admin_url('post-new.php?post_type=product_ingredient'),
				)
			);

			// Localize media management script
			wp_localize_script(
				'product-media-management',
				'ProductMediaParams',
				array(
					'selectMediaTitle' => __('Select Product Media', 'peaches'),
					'selectMediaButton' => __('Use this media', 'peaches'),
					'ajaxUrl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('product_media_nonce'),
				)
			);

		} catch (Exception $e) {
			$this->log_error('Error localizing scripts', array(
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Add custom columns with validation.
	 *
	 * @since 0.2.0
	 *
	 * @param array $columns Existing columns.
	 *
	 * @return array Modified columns.
	 */
	public function add_custom_columns($columns) {
		if (!is_array($columns)) {
			$this->log_error('Invalid columns array provided to add_custom_columns');
			return array();
		}

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

	/**
	 * Render custom columns with enhanced error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public function render_custom_columns($column, $post_id) {
		try {
			switch ($column) {
				case 'product_id':
					$product_id = get_post_meta($post_id, '_ecwid_product_id', true);
					echo $product_id ? esc_html($product_id) : '—';
					break;

				case 'product_sku':
					$product_sku = get_post_meta($post_id, '_ecwid_product_sku', true);
					echo $product_sku ? esc_html($product_sku) : '—';
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
					$lines = wp_get_object_terms($post_id, 'product_line', array('fields' => 'ids'));
					echo is_array($lines) ? count($lines) : '0';
					break;

				case 'tags_count':
					$tags = wp_get_object_terms($post_id, 'post_tag', array('fields' => 'ids'));
					echo is_array($tags) ? count($tags) : '0';
					break;
			}
		} catch (Exception $e) {
			$this->log_error('Error rendering custom column', array(
				'column' => $column,
				'post_id' => $post_id,
				'error' => $e->getMessage()
			));
			echo '—';
		}
	}

	/**
	 * AJAX handler to search products with enhanced validation.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function ajax_search_products() {
		try {
			check_ajax_referer('search_ecwid_products', 'nonce');

			$query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

			if (empty($query)) {
				wp_send_json_error(array('message' => __('Search query is required', 'peaches')));
			}

			if (strlen($query) < 2) {
				wp_send_json_error(array('message' => __('Search query must be at least 2 characters', 'peaches')));
			}

			$products = $this->ecwid_api->search_products($query);

			if (!is_array($products)) {
				wp_send_json_error(array('message' => __('Invalid search results', 'peaches')));
			}

			wp_send_json_success(array('products' => $products));

		} catch (Exception $e) {
			$this->log_error('AJAX search products error', array(
				'query' => $_POST['query'] ?? 'unknown',
				'error' => $e->getMessage()
			));
			wp_send_json_error(array('message' => __('Search failed', 'peaches')));
		}
	}

	/**
	 * AJAX handler to get Ecwid product media with validation.
	 *
	 * @since 0.2.1
	 *
	 * @return void
	 */
	public function ajax_get_ecwid_product_media() {
		try {
			// Verify nonce
			if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'product_media_nonce')) {
				wp_send_json_error(__('Security check failed', 'peaches'));
			}

			// Check permissions
			if (!current_user_can('edit_posts')) {
				wp_send_json_error(__('Insufficient permissions', 'peaches'));
			}

			$product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

			if (!$product_id) {
				wp_send_json_error(__('Product ID is required', 'peaches'));
			}

			$product = $this->ecwid_api->get_product_by_id($product_id);

			if (!$product) {
				wp_send_json_error(__('Product not found', 'peaches'));
			}

			$images = array();

			// Add main image
			if (!empty($product->thumbnailUrl)) {
				$images[] = array(
					'position' => 0,
					'label' => __('Main image (position 1)', 'peaches'),
					'url' => $product->thumbnailUrl
				);
			}

			// Add gallery images
			if (!empty($product->galleryImages) && is_array($product->galleryImages)) {
				foreach ($product->galleryImages as $index => $gallery_image) {
					$images[] = array(
						'position' => $index + 1,
						'label' => sprintf(__('Gallery image %d (position %d)', 'peaches'), $index + 1, $index + 2),
						'url' => $gallery_image->url
					);
				}
			}

			wp_send_json_success(array(
				'images' => $images,
				'product_name' => $product->name
			));

		} catch (Exception $e) {
			$this->log_error('AJAX get Ecwid product media error', array(
				'product_id' => $_POST['product_id'] ?? 'unknown',
				'error' => $e->getMessage()
			));
			wp_send_json_error(__('Failed to get product media', 'peaches'));
		}
	}

	/**
	 * Register API routes with enhanced validation.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register_api_routes() {
		try {
			register_rest_route('peaches/v1', '/product-ingredients/(?P<id>\d+)', array(
				'methods' => 'GET',
				'callback' => array($this, 'get_product_ingredients_api'),
				'permission_callback' => '__return_true',
				'args' => array(
					'id' => array(
						'validate_callback' => function($param, $request, $key) {
							return is_numeric($param) && $param > 0;
						},
						'sanitize_callback' => function($param, $request, $key) {
							return absint($param);
						}
					),
				),
			));
		} catch (Exception $e) {
			$this->log_error('Error registering API routes', array(
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * API endpoint to get product ingredients with language support.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response API response.
	 */
	public function get_product_ingredients_api($request) {
		try {
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
				if (isset($ingredient['type']) && $ingredient['type'] === 'library' && isset($ingredient['library_id'])) {
					$library_post = get_post($ingredient['library_id']);

					if ($library_post && $library_post->post_type === 'product_ingredient') {
						$ingredient_data = $this->get_ingredient_with_translations($library_post, $current_lang);
						if ($ingredient_data) {
							$processed_ingredients[] = $ingredient_data;
						}
					}
				}
			}

			return new WP_REST_Response(array(
				'status' => 200,
				'ingredients' => $processed_ingredients,
				'language' => $current_lang
			), 200);

		} catch (Exception $e) {
			$this->log_error('API get product ingredients error', array(
				'product_id' => $request['id'],
				'error' => $e->getMessage()
			));

			return new WP_REST_Response(array(
				'status' => 500,
				'message' => __('Internal server error', 'peaches'),
				'ingredients' => array()
			), 500);
		}
	}

	/**
	 * Get ingredient with translations.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $library_post Ingredient post.
	 * @param string  $current_lang Current language.
	 *
	 * @return array|null Ingredient data with translations.
	 */
	private function get_ingredient_with_translations($library_post, $current_lang) {
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

		return array(
			'name' => $name,
			'description' => $description
		);
	}

	/**
	 * Register translation strings for multilingual support.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function register_translation_strings($post_id) {
		try {
			$ingredients = get_post_meta($post_id, '_product_ingredients', true);

			if (is_array($ingredients)) {
				foreach ($ingredients as $ingredient) {
					if (isset($ingredient['type']) && $ingredient['type'] === 'library' && isset($ingredient['library_id'])) {
						$library_post = get_post($ingredient['library_id']);
						if ($library_post) {
							$this->register_ingredient_strings($library_post);
						}
					}
				}
			}
		} catch (Exception $e) {
			$this->log_error('Error registering translation strings', array(
				'post_id' => $post_id,
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Register individual ingredient strings for translation.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $library_post Ingredient post.
	 *
	 * @return void
	 */
	private function register_ingredient_strings($library_post) {
		$name = $library_post->post_title;
		$description = get_post_meta($library_post->ID, '_ingredient_description', true);

		if ($name) {
			// Polylang
			if (function_exists('pll_register_string')) {
				pll_register_string('ingredient_name_' . md5($name), $name, 'Ecwid Shopping Cart', false);
			}
			// WPML
			if (function_exists('wpml_register_single_string')) {
				wpml_register_single_string('ecwid-shopping-cart', 'ingredient_name_' . md5($name), $name);
			}
		}

		if ($description) {
			// Polylang
			if (function_exists('pll_register_string')) {
				pll_register_string('ingredient_desc_' . md5($description), $description, 'Ecwid Shopping Cart', false);
			}
			// WPML
			if (function_exists('wpml_register_single_string')) {
				wpml_register_single_string('ecwid-shopping-cart', 'ingredient_desc_' . md5($description), $description);
			}
		}
	}

	/**
	 * Get product media by tag with enhanced format support.
	 *
	 * @since 0.2.1
	 *
	 * @param int    $product_id Product ID.
	 * @param string $tag_key    Media tag key.
	 *
	 * @return array|null Media data or null if not found.
	 */
	public function get_product_media_by_tag($product_id, $tag_key) {
		if (!$this->media_manager) {
			$media_tags_manager = new Peaches_Media_Tags_Manager();
			$this->media_manager = new Peaches_Product_Media_Manager($this->ecwid_api, $media_tags_manager);
		}

		return $this->media_manager->get_product_media_by_tag($product_id, $tag_key);
	}

	/**
	 * Get all product media organized by tag with enhanced format support.
	 *
	 * @since 0.2.1
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array Array of media organized by tag key with enhanced data.
	 */
	public function get_product_media_by_tags($product_id) {
		$product_media = get_post_meta($product_id, '_product_media', true);
		$media_by_tag = array();

		if (is_array($product_media)) {
			foreach ($product_media as $media_item) {
				if (isset($media_item['tag_name'])) {
					$media_by_tag[$media_item['tag_name']] = $media_item;
				}
			}
		}

		return $media_by_tag;
	}

	/**
	 * Clear cache for specific keys.
	 *
	 * @since 0.2.0
	 *
	 * @param array $keys Cache keys to clear.
	 *
	 * @return void
	 */
	public function clear_cache($keys = array()) {
		if (empty($keys)) {
			$this->cache = array();
		} else {
			foreach ($keys as $key) {
				unset($this->cache[$key]);
			}
		}
	}

	/**
	 * Get lines manager instance.
	 *
	 * @since 0.2.0
	 *
	 * @return Peaches_Product_Lines_Manager_Interface|null
	 */
	public function get_lines_manager() {
		return $this->lines_manager;
	}

	/**
	 * Get Media Manager instance.
	 *
	 * @since 0.2.1
	 *
	 * @return Peaches_Product_Media_Manager_Interface|null
	 */
	public function get_media_manager() {
		return $this->media_manager;
	}

	/**
	 * Get Ecwid API instance.
	 *
	 * @since 0.2.0
	 *
	 * @return Peaches_Ecwid_API_Interface
	 */
	public function get_ecwid_api() {
		return $this->ecwid_api;
	}

	/**
	 * Log informational messages.
	 *
	 * @since 0.2.0
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_info($message, $context = array()) {
		if (Peaches_Ecwid_Utilities::is_debug_mode()) {
			Peaches_Ecwid_Utilities::log_error('[INFO] [Product Settings Manager] ' . $message, $context);
		}
	}

	/**
	 * Log error messages.
	 *
	 * @since 0.2.0
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_error($message, $context = array()) {
		Peaches_Ecwid_Utilities::log_error('[Product Settings Manager] ' . $message, $context);
	}
}
