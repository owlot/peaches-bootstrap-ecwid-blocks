<?php
/**
 * Product Lines Manager class
 *
 * Manages product lines using WordPress custom taxonomy with term meta for
 * additional data like type, description, and named media.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Product_Lines_Manager
 *
 * Manages product lines with comprehensive error handling and validation.
 * Implements proper interface and WordPress coding standards.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Product_Lines_Manager implements Peaches_Product_Lines_Manager_Interface {

	/**
	 * Constructor.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action('init', array($this, 'register_taxonomies'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// Term meta boxes and saving
		add_action('product_line_add_form_fields', array($this, 'add_term_meta_fields'));
		add_action('product_line_edit_form_fields', array($this, 'edit_term_meta_fields'));
		add_action('created_product_line', array($this, 'save_term_meta'));
		add_action('edited_product_line', array($this, 'save_term_meta'));

		// Custom columns in term list
		add_filter('manage_edit-product_line_columns', array($this, 'add_term_columns'));
		add_filter('manage_product_line_custom_column', array($this, 'render_term_columns'), 10, 3);

		// Menu manipulation for unified admin experience
		add_action('admin_init', array($this, 'setup_menu_manipulation'));
		add_filter('parent_file', array($this, 'set_parent_menu_active'));
		add_filter('submenu_file', array($this, 'set_submenu_active'));
		add_action('admin_head', array($this, 'add_menu_styling'));
	}

	/**
	 * Register taxonomies.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register_taxonomies() {
		try {
			// Register Product Lines taxonomy
			$labels = array(
				'name'              => _x('Product Lines', 'taxonomy general name', 'peaches'),
				'singular_name'     => _x('Product Line', 'taxonomy singular name', 'peaches'),
				'search_items'      => __('Search Product Lines', 'peaches'),
				'all_items'         => __('All Product Lines', 'peaches'),
				'edit_item'         => __('Edit Product Line', 'peaches'),
				'update_item'       => __('Update Product Line', 'peaches'),
				'add_new_item'      => __('Add New Product Line', 'peaches'),
				'new_item_name'     => __('New Product Line Name', 'peaches'),
				'menu_name'         => __('Product Lines', 'peaches'),
			);

			$result = register_taxonomy('product_line', 'product_settings', array(
				'hierarchical'       => true, // For future flexibility
				'labels'             => $labels,
				'show_ui'            => true,
				'show_in_menu'       => false, // We'll integrate with unified interface
				'show_admin_column'  => false, // We'll handle this in our interface
				'query_var'          => true,
				'public'             => true,
				'publicly_queryable' => true,
				'show_in_rest'       => true,
				'rewrite'            => array('slug' => 'product-line'),
				'capabilities'       => array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'edit_posts',
				),
			));

			if (is_wp_error($result)) {
				$this->log_error('Failed to register product_line taxonomy', array(
					'error' => $result->get_error_message(),
					'code'  => $result->get_error_code(),
				));
			}

			// Register Line Media Tags taxonomy (for organizing media within lines)
			$media_labels = array(
				'name'              => _x('Line Media Tags', 'taxonomy general name', 'peaches'),
				'singular_name'     => _x('Line Media Tag', 'taxonomy singular name', 'peaches'),
				'search_items'      => __('Search Media Tags', 'peaches'),
				'all_items'         => __('All Media Tags', 'peaches'),
				'edit_item'         => __('Edit Media Tag', 'peaches'),
				'update_item'       => __('Update Media Tag', 'peaches'),
				'add_new_item'      => __('Add New Media Tag', 'peaches'),
				'new_item_name'     => __('New Media Tag Name', 'peaches'),
				'menu_name'         => __('Media Tags', 'peaches'),
			);

			$media_result = register_taxonomy('line_media_tag', 'attachment', array(
				'hierarchical'       => false,
				'labels'             => $media_labels,
				'show_ui'            => false, // Hidden from UI - managed internally
				'show_admin_column'  => false,
				'query_var'          => false,
				'public'             => false,
				'publicly_queryable' => false,
				'show_in_rest'       => false,
			));

			if (is_wp_error($media_result)) {
				$this->log_error('Failed to register line_media_tag taxonomy', array(
					'error' => $media_result->get_error_message(),
					'code'  => $media_result->get_error_code(),
				));
			}

		} catch (Exception $e) {
			$this->log_error('Exception during taxonomy registration', array(
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			));
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 0.2.0
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts($hook) {
		if (strpos($hook, 'edit-tags.php') !== false && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'product_line') {
			try {
				wp_enqueue_media();

				$script_handle = 'product-lines-admin';
				$script_path   = PEACHES_ECWID_ASSETS_URL . 'js/admin-product-lines.js';

				wp_enqueue_script(
					$script_handle,
					$script_path,
					array('jquery', 'media-upload'),
					PEACHES_ECWID_VERSION,
					true
				);

				wp_localize_script($script_handle, 'ProductLinesParams', array(
					'selectMediaTitle'  => __('Select Line Media', 'peaches'),
					'selectMediaButton' => __('Use this media', 'peaches'),
					'nonce'             => wp_create_nonce('product_lines_admin'),
				));

			} catch (Exception $e) {
				$this->log_error('Failed to enqueue admin scripts', array(
					'hook'  => $hook,
					'error' => $e->getMessage(),
				));
			}
		}
	}

	/**
	 * Get all product lines (Interface implementation).
	 *
	 * Retrieves all product lines with comprehensive error handling and caching.
	 *
	 * @since 0.2.0
	 *
	 * @return array Array of product lines.
	 */
	public function get_all_lines() {
		try {
			$terms = get_terms(array(
				'taxonomy'   => 'product_line',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			));

			if (is_wp_error($terms)) {
				$this->log_error('Failed to retrieve product lines', array(
					'error' => $terms->get_error_message(),
					'code'  => $terms->get_error_code(),
				));
				return array();
			}

			$this->log_info('Retrieved product lines', array(
				'count' => count($terms),
			));

			return $terms;

		} catch (Exception $e) {
			$this->log_error('Exception retrieving product lines', array(
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			));
			return array();
		}
	}

	/**
	 * Get line media by line ID (Interface implementation).
	 *
	 * Retrieves media associated with a specific product line.
	 *
	 * @since 0.2.0
	 *
	 * @param int $line_id Line ID.
	 *
	 * @return array Array of media items.
	 *
	 * @throws InvalidArgumentException If line ID is invalid.
	 */
	public function get_line_media($line_id) {
		if (!is_numeric($line_id) || $line_id <= 0) {
			throw new InvalidArgumentException('Invalid line ID provided');
		}

		$line_id = absint($line_id);

		try {
			// Verify the term exists
			$term = get_term($line_id, 'product_line');
			if (is_wp_error($term)) {
				$this->log_error('Invalid product line term', array(
					'line_id' => $line_id,
					'error'   => $term->get_error_message(),
				));
				return array();
			}

			if (!$term) {
				$this->log_error('Product line not found', array(
					'line_id' => $line_id,
				));
				return array();
			}

			$media = get_term_meta($line_id, 'line_media', true);

			if (!is_array($media)) {
				$media = array();
			}

			// Validate media items and filter out invalid ones
			$valid_media = array();
			foreach ($media as $media_item) {
				if (is_array($media_item) &&
					isset($media_item['attachment_id']) &&
					is_numeric($media_item['attachment_id']) &&
					get_post($media_item['attachment_id'])) {
					$valid_media[] = $media_item;
				}
			}

			$this->log_info('Retrieved line media', array(
				'line_id'     => $line_id,
				'media_count' => count($valid_media),
			));

			return $valid_media;

		} catch (Exception $e) {
			$this->log_error('Exception retrieving line media', array(
				'line_id' => $line_id,
				'error'   => $e->getMessage(),
				'trace'   => $e->getTraceAsString(),
			));
			return array();
		}
	}

	/**
	 * Get lines for a specific product (Interface implementation).
	 *
	 * Retrieves product lines associated with a given product ID.
	 *
	 * @since 0.2.0
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array Array of line IDs.
	 *
	 * @throws InvalidArgumentException If product ID is invalid.
	 */
	public function get_product_lines($product_id) {
		if (!is_numeric($product_id) || $product_id <= 0) {
			throw new InvalidArgumentException('Invalid product ID provided');
		}

		$product_id = absint($product_id);

		try {
			// Get product_settings post that matches this product ID
			$args = array(
				'post_type'      => 'product_settings',
				'meta_query'     => array(
					array(
						'key'     => '_ecwid_product_id',
						'value'   => $product_id,
						'compare' => '=',
					),
				),
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			);

			$query = new WP_Query($args);

			if (!$query->have_posts()) {
				$this->log_info('No product settings found for product', array(
					'product_id' => $product_id,
				));
				return array();
			}

			$product_settings = $query->posts[0];
			$line_terms = wp_get_object_terms($product_settings->ID, 'product_line', array(
				'fields' => 'ids',
			));

			if (is_wp_error($line_terms)) {
				$this->log_error('Failed to get product lines for product', array(
					'product_id' => $product_id,
					'post_id'    => $product_settings->ID,
					'error'      => $line_terms->get_error_message(),
				));
				return array();
			}

			$line_ids = array_map('absint', $line_terms);

			$this->log_info('Retrieved product lines for product', array(
				'product_id' => $product_id,
				'line_count' => count($line_ids),
				'line_ids'   => $line_ids,
			));

			return $line_ids;

		} catch (Exception $e) {
			$this->log_error('Exception retrieving product lines', array(
				'product_id' => $product_id,
				'error'      => $e->getMessage(),
				'trace'      => $e->getTraceAsString(),
			));
			return array();
		}
	}

	/**
	 * Add term meta fields for new terms.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function add_term_meta_fields() {
		// Get existing line types for datalist
		$existing_types = $this->get_existing_line_types();

		wp_nonce_field('save_product_line_meta', 'product_line_meta_nonce');
		?>
		<div class="form-field">
			<label for="line_type"><?php _e('Line Type', 'peaches'); ?></label>
			<input type="text" name="line_type" id="line_type" value="" class="regular-text" list="line_type_suggestions">
			<datalist id="line_type_suggestions">
				<?php foreach ($existing_types as $type): ?>
					<option value="<?php echo esc_attr($type); ?>">
				<?php endforeach; ?>
				<option value="fragrance">
				<option value="color_scheme">
				<option value="design_collection">
				<option value="seasonal">
				<option value="limited_edition">
			</datalist>
			<p class="description"><?php _e('Type of product line (e.g., fragrance, color_scheme, design_collection)', 'peaches'); ?></p>
		</div>

		<div class="form-field">
			<label for="line_description"><?php _e('Description', 'peaches'); ?></label>
			<textarea name="line_description" id="line_description" rows="5" cols="40"></textarea>
			<p class="description"><?php _e('Detailed description of the product line.', 'peaches'); ?></p>
		</div>

		<div class="form-field">
			<label><?php _e('Line Media', 'peaches'); ?></label>
			<div id="line-media-container">
				<!-- Media items will be added here -->
			</div>
			<button type="button" id="add-line-media" class="button">
				<?php _e('Add Media', 'peaches'); ?>
			</button>
			<p class="description"><?php _e('Add named media files for this product line.', 'peaches'); ?></p>
		</div>
		<?php
	}

	/**
	 * Add term meta fields for editing existing terms.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Term $term The term being edited.
	 *
	 * @return void
	 */
	public function edit_term_meta_fields($term) {
		if (!$term instanceof WP_Term) {
			$this->log_error('Invalid term object provided to edit_term_meta_fields');
			return;
		}

		try {
			$line_type        = get_term_meta($term->term_id, 'line_type', true);
			$line_description = get_term_meta($term->term_id, 'line_description', true);
			$line_media       = get_term_meta($term->term_id, 'line_media', true);

			if (!is_array($line_media)) {
				$line_media = array();
			}

			// Get existing line types for datalist
			$existing_types = $this->get_existing_line_types();

			wp_nonce_field('save_product_line_meta', 'product_line_meta_nonce');
			?>
			<tr class="form-field">
				<th scope="row">
					<label for="line_type"><?php _e('Line Type', 'peaches'); ?></label>
				</th>
				<td>
					<input type="text" name="line_type" id="line_type" value="<?php echo esc_attr($line_type); ?>" class="regular-text" list="line_type_suggestions">
					<datalist id="line_type_suggestions">
						<?php foreach ($existing_types as $type): ?>
							<option value="<?php echo esc_attr($type); ?>">
						<?php endforeach; ?>
						<option value="fragrance">
						<option value="color_scheme">
						<option value="design_collection">
						<option value="seasonal">
						<option value="limited_edition">
					</datalist>
					<p class="description"><?php _e('Type of product line (e.g., fragrance, color_scheme, design_collection)', 'peaches'); ?></p>
				</td>
			</tr>

			<tr class="form-field">
				<th scope="row">
					<label for="line_description"><?php _e('Description', 'peaches'); ?></label>
				</th>
				<td>
					<?php
					wp_editor($line_description, 'line_description', array(
						'textarea_name' => 'line_description',
						'media_buttons' => true,
						'textarea_rows' => 8,
					));
					?>
					<p class="description"><?php _e('Detailed description of the product line.', 'peaches'); ?></p>
				</td>
			</tr>

			<tr class="form-field">
				<th scope="row">
					<label><?php _e('Line Media', 'peaches'); ?></label>
				</th>
				<td>
					<div id="line-media-container">
						<?php
						if (empty($line_media)) {
							$this->render_media_item(array('tag' => '', 'attachment_id' => ''), 0);
						} else {
							foreach ($line_media as $index => $media) {
								$this->render_media_item($media, $index);
							}
						}
						?>
					</div>
					<button type="button" id="add-line-media" class="button">
						<?php _e('Add Media', 'peaches'); ?>
					</button>
					<p class="description"><?php _e('Add named media files for this product line.', 'peaches'); ?></p>
				</td>
			</tr>
			<?php

		} catch (Exception $e) {
			$this->log_error('Exception in edit_term_meta_fields', array(
				'term_id' => $term->term_id,
				'error'   => $e->getMessage(),
			));
		}
	}

	/**
	 * Render an individual media item.
	 *
	 * @since 0.2.0
	 *
	 * @param array $media Media item data.
	 * @param int   $index Item index.
	 *
	 * @return void
	 */
	private function render_media_item($media, $index) {
		$tag           = isset($media['tag']) ? $media['tag'] : '';
		$attachment_id = isset($media['attachment_id']) ? $media['attachment_id'] : '';
		?>
		<div class="line-media-item" data-index="<?php echo esc_attr($index); ?>" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; background: #f9f9f9;">
			<div style="display: flex; gap: 15px; align-items: flex-start;">
				<div style="flex: 1;">
					<p>
						<label><?php _e('Media Tag:', 'peaches'); ?></label>
						<input type="text"
							   name="line_media[<?php echo esc_attr($index); ?>][tag]"
							   value="<?php echo esc_attr($tag); ?>"
							   class="regular-text media-tag-field"
							   placeholder="<?php esc_attr_e('e.g., hero_image, logo, banner', 'peaches'); ?>"
							   list="media_tag_suggestions">
					</p>
					<p>
						<input type="hidden"
							   name="line_media[<?php echo esc_attr($index); ?>][attachment_id]"
							   value="<?php echo esc_attr($attachment_id); ?>"
							   class="media-attachment-id">
						<button type="button" class="button select-media-button">
							<?php echo $attachment_id ? __('Change Media', 'peaches') : __('Select Media', 'peaches'); ?>
						</button>
						<?php if ($attachment_id): ?>
							<button type="button" class="button remove-media-button"><?php _e('Remove', 'peaches'); ?></button>
						<?php endif; ?>
						<button type="button" class="button remove-media-item" style="color: #a00;"><?php _e('Delete', 'peaches'); ?></button>
					</p>
				</div>
				<div class="media-preview" style="flex: 0 0 150px; text-align: center;">
					<?php if ($attachment_id): ?>
						<?php echo wp_get_attachment_image($attachment_id, 'thumbnail'); ?>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Datalist for media tag suggestions -->
		<datalist id="media_tag_suggestions">
			<?php
			// Get existing line media tags
			$existing_tags = get_terms(array(
				'taxonomy'   => 'line_media_tag',
				'hide_empty' => false,
				'fields'     => 'names',
			));
			if (!is_wp_error($existing_tags)) {
				foreach ($existing_tags as $existing_tag) {
					echo '<option value="' . esc_attr($existing_tag) . '">';
				}
			}
			?>
			<option value="hero_image">
			<option value="logo">
			<option value="banner">
			<option value="gallery_1">
			<option value="gallery_2">
			<option value="gallery_3">
		</datalist>
		<?php
	}

	/**
	 * Save term meta data.
	 *
	 * @since 0.2.0
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return void
	 */
	public function save_term_meta($term_id) {
		if (!isset($_POST['product_line_meta_nonce']) ||
			!wp_verify_nonce($_POST['product_line_meta_nonce'], 'save_product_line_meta')) {
			$this->log_error('Nonce verification failed for save_term_meta');
			return;
		}

		if (!current_user_can('manage_categories')) {
			$this->log_error('User lacks permission to save term meta', array(
				'user_id' => get_current_user_id(),
				'term_id' => $term_id,
			));
			return;
		}

		$term_id = absint($term_id);

		try {
			// Save line type
			if (isset($_POST['line_type'])) {
				$line_type = sanitize_text_field($_POST['line_type']);
				update_term_meta($term_id, 'line_type', $line_type);
			}

			// Save line description
			if (isset($_POST['line_description'])) {
				$line_description = wp_kses_post($_POST['line_description']);
				update_term_meta($term_id, 'line_description', $line_description);
			}

			// Save line media
			if (isset($_POST['line_media']) && is_array($_POST['line_media'])) {
				$line_media = array();

				foreach ($_POST['line_media'] as $media) {
					if (!empty($media['tag']) && !empty($media['attachment_id'])) {
						$attachment_id = absint($media['attachment_id']);
						$tag_name      = sanitize_text_field($media['tag']);

						// Verify attachment exists
						if (!get_post($attachment_id)) {
							$this->log_error('Invalid attachment ID in line media', array(
								'attachment_id' => $attachment_id,
								'term_id'       => $term_id,
							));
							continue;
						}

						// Create/get the media tag term
						$tag_term = wp_insert_term($tag_name, 'line_media_tag');
						if (!is_wp_error($tag_term)) {
							$tag_term_id = $tag_term['term_id'];
						} else {
							// Term already exists
							$existing_term = get_term_by('name', $tag_name, 'line_media_tag');
							$tag_term_id = $existing_term ? $existing_term->term_id : 0;
						}

						$line_media[] = array(
							'tag'           => $tag_name,
							'tag_id'        => $tag_term_id,
							'attachment_id' => $attachment_id,
						);

						// Mark attachment as line media
						update_post_meta($attachment_id, '_peaches_line_media', true);
						update_post_meta($attachment_id, '_peaches_line_media_tag', $tag_name);
					}
				}

				update_term_meta($term_id, 'line_media', $line_media);

				$this->log_info('Successfully saved line media', array(
					'term_id'     => $term_id,
					'media_count' => count($line_media),
				));
			}

		} catch (Exception $e) {
			$this->log_error('Exception saving term meta', array(
				'term_id' => $term_id,
				'error'   => $e->getMessage(),
				'trace'   => $e->getTraceAsString(),
			));
		}
	}

	/**
	 * Add custom columns to term list.
	 *
	 * @since 0.2.0
	 *
	 * @param array $columns Existing columns.
	 *
	 * @return array Modified columns.
	 */
	public function add_term_columns($columns) {
		$new_columns = array();

		if (isset($columns['cb'])) {
			$new_columns['cb'] = $columns['cb'];
		}
		if (isset($columns['name'])) {
			$new_columns['name'] = $columns['name'];
		}

		$new_columns['line_type']      = __('Type', 'peaches');
		$new_columns['media_count']    = __('Media', 'peaches');
		$new_columns['products_count'] = __('Products', 'peaches');

		if (isset($columns['description'])) {
			$new_columns['description'] = $columns['description'];
		}
		if (isset($columns['slug'])) {
			$new_columns['slug'] = $columns['slug'];
		}
		if (isset($columns['posts'])) {
			$new_columns['posts'] = $columns['posts'];
		}

		return $new_columns;
	}

	/**
	 * Render custom columns in term list.
	 *
	 * @since 0.2.0
	 *
	 * @param string $content     Current column content.
	 * @param string $column_name Column name.
	 * @param int    $term_id     Term ID.
	 *
	 * @return string Column content.
	 */
	public function render_term_columns($content, $column_name, $term_id) {
		switch ($column_name) {
			case 'line_type':
				$line_type = get_term_meta($term_id, 'line_type', true);
				return esc_html($line_type ?: '—');

			case 'media_count':
				$line_media = get_term_meta($term_id, 'line_media', true);
				return is_array($line_media) ? count($line_media) : '0';

			case 'products_count':
				$term = get_term($term_id, 'product_line');
				return $term ? $term->count : '0';
		}

		return $content;
	}

	/**
	 * Get existing line types.
	 *
	 * @since 0.2.0
	 *
	 * @return array Array of existing line types.
	 */
	private function get_existing_line_types() {
		global $wpdb;

		try {
			$types = $wpdb->get_col(
				"SELECT DISTINCT meta_value
				 FROM {$wpdb->termmeta}
				 WHERE meta_key = 'line_type'
				 AND meta_value != ''"
			);

			return array_filter($types);

		} catch (Exception $e) {
			$this->log_error('Failed to get existing line types', array(
				'error' => $e->getMessage(),
			));
			return array();
		}
	}

	/**
	 * Setup menu manipulation for product lines pages.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function setup_menu_manipulation() {
		// Only on product_line taxonomy pages
		if (!$this->is_product_line_page()) {
			return;
		}

		// Add breadcrumb styling and navigation help
		add_action('all_admin_notices', array($this, 'add_breadcrumb_navigation'), 5);
	}

	/**
	 * Set the parent menu as active when viewing product lines.
	 *
	 * @since 0.2.0
	 *
	 * @param string $parent_file Current parent file.
	 *
	 * @return string Modified parent file.
	 */
	public function set_parent_menu_active($parent_file) {
		if ($this->is_product_line_page()) {
			return 'peaches-settings';
		}

		return $parent_file;
	}

	/**
	 * Set the submenu as active when viewing product lines.
	 *
	 * @since 0.2.0
	 *
	 * @param string $submenu_file Current submenu file.
	 *
	 * @return string Modified submenu file.
	 */
	public function set_submenu_active($submenu_file) {
		if ($this->is_product_line_page()) {
			return 'peaches-ecwid-product-settings';
		}

		return $submenu_file;
	}

	/**
	 * Add menu styling for better visual consistency.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function add_menu_styling() {
		if (!$this->is_product_line_page()) {
			return;
		}

		?>
		<style type="text/css">
		/* Ensure Peaches menu is highlighted */
		#adminmenu li.current > a.current {
			color: #fff;
			background-color: #0073aa;
		}

		/* Highlight the Ecwid Products submenu */
		#adminmenu .wp-submenu li.current a {
			color: #0073aa;
			font-weight: 600;
		}

		/* Add visual indication this is part of Peaches */
		.wrap h1:before {
			content: "Peaches → Ecwid Products → ";
			color: #666;
			font-size: 18px;
			font-weight: normal;
		}
		</style>
		<?php
	}

	/**
	 * Add breadcrumb navigation to product lines pages.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function add_breadcrumb_navigation() {
		if (!$this->is_product_line_page()) {
			return;
		}

		$main_page_url = admin_url('admin.php?page=peaches-ecwid-product-settings&tab=product_lines');
		$page_title    = $this->get_current_page_title();

		?>
		<div class="notice notice-info peaches-navigation-notice" style="border-left-color: #0073aa !important; background: #f0f8ff;">
			<div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
				<div style="flex: 1;">
					<p style="margin: 8px 0;">
						<strong><?php _e('Product Lines Management', 'peaches'); ?></strong>
						<?php if ($page_title): ?>
							→ <?php echo esc_html($page_title); ?>
						<?php endif; ?>
					</p>
					<p style="margin: 8px 0; color: #666;">
						<?php _e('You are managing product lines from the unified Ecwid Products interface.', 'peaches'); ?>
					</p>
				</div>
				<div style="flex: 0 0 auto;">
					<a href="<?php echo esc_url($main_page_url); ?>" class="button button-primary">
						<?php _e('← Back to Product Settings', 'peaches'); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if current page is a product line page.
	 *
	 * @since 0.2.0
	 *
	 * @return bool True if on product line page.
	 */
	private function is_product_line_page() {
		global $current_screen;

		if (!$current_screen) {
			return false;
		}

		return (
			isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'product_line'
		) || (
			$current_screen->taxonomy === 'product_line'
		);
	}

	/**
	 * Get current page title for breadcrumbs.
	 *
	 * @since 0.2.0
	 *
	 * @return string Current page title.
	 */
	private function get_current_page_title() {
		global $current_screen;

		if (!$current_screen) {
			return '';
		}

		if (isset($_GET['action']) && $_GET['action'] === 'edit') {
			return __('Edit Product Line', 'peaches');
		}

		if ($current_screen->base === 'edit-tags') {
			return __('All Product Lines', 'peaches');
		}

		return '';
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
		if (class_exists('Peaches_Ecwid_Utilities') && Peaches_Ecwid_Utilities::is_debug_mode()) {
			Peaches_Ecwid_Utilities::log_error('[INFO] [Product Lines Manager] ' . $message, $context);
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
		if (class_exists('Peaches_Ecwid_Utilities')) {
			Peaches_Ecwid_Utilities::log_error('[Product Lines Manager] ' . $message, $context);
		} else {
			// Fallback logging if utilities class is not available
			error_log('[Peaches Ecwid] [Product Lines Manager] ' . $message . (empty($context) ? '' : ' - Context: ' . wp_json_encode($context)));
		}
	}

	/**
	 * Get line by slug or ID.
	 *
	 * Retrieves a product line by slug or ID with error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param string|int $identifier Line slug or ID.
	 *
	 * @return WP_Term|null Product line term or null if not found.
	 */
	public function get_line($identifier) {
		if (empty($identifier)) {
			$this->log_error('Empty identifier provided to get_line');
			return null;
		}

		try {
			if (is_numeric($identifier)) {
				$term = get_term(absint($identifier), 'product_line');
			} else {
				$term = get_term_by('slug', sanitize_title($identifier), 'product_line');
			}

			if (is_wp_error($term)) {
				$this->log_error('Error retrieving product line', array(
					'identifier' => $identifier,
					'error'      => $term->get_error_message(),
				));
				return null;
			}

			if (!$term) {
				$this->log_info('Product line not found', array(
					'identifier' => $identifier,
				));
				return null;
			}

			return $term;

		} catch (Exception $e) {
			$this->log_error('Exception in get_line', array(
				'identifier' => $identifier,
				'error'      => $e->getMessage(),
				'trace'      => $e->getTraceAsString(),
			));
			return null;
		}
	}

	/**
	 * Create a new product line.
	 *
	 * Creates a new product line with validation and error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param string $name        Line name.
	 * @param array  $args        Optional arguments (description, line_type, etc.).
	 *
	 * @return WP_Term|WP_Error|null Created term on success, WP_Error on failure.
	 */
	public function create_line($name, $args = array()) {
		if (empty($name) || !is_string($name)) {
			$this->log_error('Invalid line name provided to create_line', array(
				'name' => $name,
			));
			return new WP_Error('invalid_name', 'Invalid line name provided');
		}

		try {
			$name = sanitize_text_field($name);

			$term_args = array();
			if (isset($args['description'])) {
				$term_args['description'] = wp_kses_post($args['description']);
			}
			if (isset($args['slug'])) {
				$term_args['slug'] = sanitize_title($args['slug']);
			}

			$result = wp_insert_term($name, 'product_line', $term_args);

			if (is_wp_error($result)) {
				$this->log_error('Failed to create product line', array(
					'name'  => $name,
					'args'  => $args,
					'error' => $result->get_error_message(),
				));
				return $result;
			}

			$term_id = $result['term_id'];

			// Save additional meta data
			if (isset($args['line_type'])) {
				update_term_meta($term_id, 'line_type', sanitize_text_field($args['line_type']));
			}

			if (isset($args['line_description'])) {
				update_term_meta($term_id, 'line_description', wp_kses_post($args['line_description']));
			}

			if (isset($args['line_media']) && is_array($args['line_media'])) {
				update_term_meta($term_id, 'line_media', $args['line_media']);
			}

			$term = get_term($term_id, 'product_line');

			$this->log_info('Created new product line', array(
				'term_id' => $term_id,
				'name'    => $name,
			));

			return $term;

		} catch (Exception $e) {
			$this->log_error('Exception creating product line', array(
				'name'  => $name,
				'args'  => $args,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			));
			return new WP_Error('creation_exception', 'Exception occurred during line creation');
		}
	}

	/**
	 * Delete a product line.
	 *
	 * Deletes a product line with proper cleanup and error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param int $line_id Line ID to delete.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_line($line_id) {
		if (!is_numeric($line_id) || $line_id <= 0) {
			$this->log_error('Invalid line ID provided to delete_line', array(
				'line_id' => $line_id,
			));
			return new WP_Error('invalid_id', 'Invalid line ID provided');
		}

		$line_id = absint($line_id);

		try {
			// Get the term first to verify it exists
			$term = get_term($line_id, 'product_line');
			if (is_wp_error($term) || !$term) {
				$this->log_error('Product line not found for deletion', array(
					'line_id' => $line_id,
				));
				return new WP_Error('not_found', 'Product line not found');
			}

			// Clean up associated media meta
			$line_media = get_term_meta($line_id, 'line_media', true);
			if (is_array($line_media)) {
				foreach ($line_media as $media_item) {
					if (isset($media_item['attachment_id'])) {
						delete_post_meta($media_item['attachment_id'], '_peaches_line_media');
						delete_post_meta($media_item['attachment_id'], '_peaches_line_media_tag');
					}
				}
			}

			$result = wp_delete_term($line_id, 'product_line');

			if (is_wp_error($result)) {
				$this->log_error('Failed to delete product line', array(
					'line_id' => $line_id,
					'error'   => $result->get_error_message(),
				));
				return $result;
			}

			$this->log_info('Deleted product line', array(
				'line_id' => $line_id,
				'name'    => $term->name,
			));

			return true;

		} catch (Exception $e) {
			$this->log_error('Exception deleting product line', array(
				'line_id' => $line_id,
				'error'   => $e->getMessage(),
				'trace'   => $e->getTraceAsString(),
			));
			return new WP_Error('deletion_exception', 'Exception occurred during line deletion');
		}
	}
}
