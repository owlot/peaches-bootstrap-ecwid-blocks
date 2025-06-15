<?php
/**
 * Product Settings Manager class
 *
 * Manages product settings post type and related functionality.
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
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Product_Settings_Manager {
	/**
	 * Ecwid API instance.
	 *
	 * @var Peaches_Ecwid_API
	 */
	private $ecwid_api;

	/**
	 * Product Lines Manager instance.
	 *
	 * @var Peaches_Product_Lines_Manager
	 */
	private $lines_manager;

	/**
	 * Product Media Manager instance.
	 *
	 * @var Peaches_Product_Media_Manager
	 */
	private $media_manager;

	/**
	 * Simple cache for data operations.
	 *
	 * @var array
	 */
	private $cache = array();

	/**
	 * Constructor.
	 *
	 * @param Peaches_Ecwid_API_Interface   $ecwid_api
	 * @param Peaches_Product_Lines_Manager $lines_manager
	 * @param Peaches_Product_Media_Manager $media_manager
	 */
	public function __construct($ecwid_api, $lines_manager, $media_manager) {
		if (!$ecwid_api instanceof Peaches_Ecwid_API_Interface) {
			throw new InvalidArgumentException('Ecwid API instance is required');
		}
		$this->ecwid_api = $ecwid_api;

		if (!$lines_manager instanceof Peaches_Product_Lines_Manager_Interface) {
			throw new InvalidArgumentException('Product Lines Manager instance is required');
		}
		$this->lines_manager = $lines_manager;

		if (!$media_manager instanceof Peaches_Product_Media_Manager_Interface) {
			throw new InvalidArgumentException('Product Media Manager instance is required');
		}
		$this->media_manager = $media_manager;

		$this->init_hooks();
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
	}

	/**
	 * Register Product Settings post type.
	 *
	 * @since 0.2.0
	 *
	 * @throws Exception If post type registration fails.
	 */
	public function register_post_type() {
		try {
			$labels = array(
				'name'                  => _x('Product Settings', 'Post type general name', 'peaches'),
				'singular_name'         => _x('Product Setting', 'Post type singular name', 'peaches'),
				'menu_name'             => _x('Product Settings', 'Admin Menu text', 'peaches'),
				'name_admin_bar'        => _x('Product Setting', 'Add New on Toolbar', 'peaches'),
				'add_new'               => __('Add New', 'peaches'),
				'add_new_item'          => __('Add New Product Setting', 'peaches'),
				'new_item'              => __('New Product Setting', 'peaches'),
				'edit_item'             => __('Edit Product Setting', 'peaches'),
				'view_item'             => __('View Product Setting', 'peaches'),
				'all_items'             => __('All Product Settings', 'peaches'),
				'search_items'          => __('Search Product Settings', 'peaches'),
				'parent_item_colon'     => __('Parent Product Settings:', 'peaches'),
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
				<div class="d-flex justify-content-between align-items-start mb-3 gap-2">
					<div>
						<p class="description mb-2">
							<?php _e('Select which product lines this product belongs to. Products can belong to multiple lines.', 'peaches'); ?>
						</p>
					</div>
					<div>
						<a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=product_line')); ?>"
						   class="btn btn-sm btn-outline-primary text-nowrap"
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
				<div class="d-flex justify-content-between align-items-start mb-3 gap-2">
					<div>
						<p class="description mb-2">
							<?php _e('Select ingredients from your ingredients library for this product. Each ingredient will be displayed on the product page.', 'peaches'); ?>
						</p>
					</div>
					<div>
						<a href="<?php echo esc_url(admin_url('post-new.php?post_type=product_ingredient')); ?>"
						   class="btn btn-sm btn-outline-primary text-nowrap"
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
								<button type="button" class="btn btn-primary btn-sm add-ingredient"><?php _e('Add First Ingredient', 'peaches'); ?></button>
							</div>
						<?php else: ?>
							<table class="table table-bordered ingredients-table">
								<thead>
									<tr>
										<th><?php _e('Ingredient', 'peaches'); ?></th>
										<th><?php _e('Description', 'peaches'); ?></th>
										<th width="80"><?php _e('Actions', 'peaches'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($ingredients as $index => $ingredient): ?>
										<?php $this->render_ingredient_table_row($ingredient, $index); ?>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div class="ingredients-actions mt-3">
						<button type="button" class="btn btn-primary add-ingredient">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php _e('Add Ingredient', 'peaches'); ?>
						</button>
					</div>
				</div>

				<!-- Hidden template for new ingredient rows -->
				<template id="ingredient-row-template">
					<tr class="ingredient-row" data-index="{{INDEX}}">
						<td>
							<select name="product_ingredient_id[]" class="form-select library-ingredient-select" required>
								<option value=""><?php _e('Select an ingredient...', 'peaches'); ?></option>
								<?php
								$product_ingredients = get_posts(array(
									'post_type' => 'product_ingredient',
									'posts_per_page' => -1,
									'orderby' => 'title',
									'order' => 'ASC'
								));

								foreach ($product_ingredients as $pi) {
									printf('<option value="%d">%s</option>', $pi->ID, esc_html($pi->post_title));
								}
								?>
							</select>
						</td>
						<td>
							<div class="ingredient-description text-muted small">
								<?php _e('Select an ingredient to see its description', 'peaches'); ?>
							</div>
						</td>
						<td class="text-center">
							<button type="button" class="btn btn-sm btn-outline-danger remove-ingredient" title="<?php esc_attr_e('Remove ingredient', 'peaches'); ?>">
								<span class="dashicons dashicons-trash"></span>
							</button>
						</td>
					</tr>
				</template>
			</div>

			<script type="text/javascript">
			jQuery(document).ready(function($) {
				let ingredientIndex = <?php echo count($ingredients); ?>;

				// Add ingredient functionality
				$(document).on('click', '.add-ingredient', function() {
					let template = $('#ingredient-row-template').html();
					template = template.replace(/{{INDEX}}/g, ingredientIndex);

					if ($('.ingredients-table tbody').length === 0) {
						// Create table if it doesn't exist
						$('#ingredients-container').html(`
							<table class="table table-bordered ingredients-table">
								<thead>
									<tr>
										<th><?php _e('Ingredient', 'peaches'); ?></th>
										<th><?php _e('Description', 'peaches'); ?></th>
										<th width="80"><?php _e('Actions', 'peaches'); ?></th>
									</tr>
								</thead>
								<tbody></tbody>
							</table>
						`);
					}

					$('.ingredients-table tbody').append(template);
					ingredientIndex++;
				});

				// Remove ingredient functionality
				$(document).on('click', '.remove-ingredient', function() {
					$(this).closest('tr').remove();

					// If no ingredients left, show the empty state
					if ($('.ingredients-table tbody tr').length === 0) {
						$('#ingredients-container').html(`
							<div class="alert alert-info">
								<p class="mb-2"><?php _e('No ingredients selected yet.', 'peaches'); ?></p>
								<button type="button" class="btn btn-primary btn-sm add-ingredient"><?php _e('Add First Ingredient', 'peaches'); ?></button>
							</div>
						`);
					}
				});

				// Update description when ingredient changes
				$(document).on('change', '.library-ingredient-select', function() {
					const ingredientId = $(this).val();
					const descriptionDiv = $(this).closest('tr').find('.ingredient-description');

					if (ingredientId) {
						// AJAX call to get ingredient description
						$.post(ajaxurl, {
							action: 'get_ingredient_description',
							ingredient_id: ingredientId,
							nonce: '<?php echo wp_create_nonce('get_ingredient_description'); ?>'
						}, function(response) {
							if (response.success) {
								descriptionDiv.html(response.data.description || '<?php _e('No description available', 'peaches'); ?>');
							} else {
								descriptionDiv.html('<?php _e('Error loading description', 'peaches'); ?>');
							}
						});
					} else {
						descriptionDiv.html('<?php _e('Select an ingredient to see its description', 'peaches'); ?>');
					}
				});
			});
			</script>
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
				<div class="d-flex justify-content-between align-items-start mb-3 gap-2">
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
						<a href="<?php echo esc_url(admin_url('admin.php?page=peaches-ecwid-product-settings&tab=media_tags')); ?>" class="btn btn-sm btn-outline-secondary text-nowrap">
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
							<div class="col-md-6 col-xl-4">
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
	 * Save product ingredients with enhanced validation.
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
	 * Get product ingredients by post ID.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array Array of ingredients.
	 */
	public function get_product_ingredients($post_id) {
		if (!is_numeric($post_id) || $post_id <= 0) {
			return array();
		}

		$cache_key = 'ingredients_' . $post_id;
		if (isset($this->cache[$cache_key])) {
			return $this->cache[$cache_key];
		}

		$ingredients = get_post_meta($post_id, '_product_ingredients', true);
		if (!is_array($ingredients)) {
			$ingredients = array();
		}

		// Cache the result
		$this->cache[$cache_key] = $ingredients;

		return $ingredients;
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
		try {
			$screen = get_current_screen();

			if (!$screen || $screen->post_type !== 'product_settings') {
				return;
			}

			// Add AJAX handlers for ingredient descriptions
			add_action('wp_ajax_get_ingredient_description', array($this, 'ajax_get_ingredient_description'));

		} catch (Exception $e) {
			$this->log_error('Error enqueuing admin scripts', array(
				'hook' => $hook,
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * AJAX handler to get ingredient description.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function ajax_get_ingredient_description() {
		try {
			// Check nonce
			if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_ingredient_description')) {
				wp_send_json_error('Invalid nonce');
				return;
			}

			$ingredient_id = isset($_POST['ingredient_id']) ? absint($_POST['ingredient_id']) : 0;

			if (!$ingredient_id) {
				wp_send_json_error('Invalid ingredient ID');
				return;
			}

			$ingredient_post = get_post($ingredient_id);

			if (!$ingredient_post || $ingredient_post->post_type !== 'product_ingredient') {
				wp_send_json_error('Ingredient not found');
				return;
			}

			$description = get_post_meta($ingredient_id, '_ingredient_description', true);

			wp_send_json_success(array(
				'description' => $description ? wp_kses_post($description) : ''
			));

		} catch (Exception $e) {
			$this->log_error('Error in AJAX get ingredient description', array(
				'error' => $e->getMessage()
			));
			wp_send_json_error('Server error');
		}
	}

	/**
	 * Log informational message.
	 *
	 * @since 0.2.0
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_info($message, $context = array()) {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('Peaches Product Settings Manager: ' . $message . ' ' . wp_json_encode($context));
		}
	}

	/**
	 * Log error message.
	 *
	 * @since 0.2.0
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_error($message, $context = array()) {
		error_log('Peaches Product Settings Manager ERROR: ' . $message . ' ' . wp_json_encode($context));
	}
}
