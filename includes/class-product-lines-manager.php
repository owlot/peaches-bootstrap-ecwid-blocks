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
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Product_Lines_Manager {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
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
	 */
	public function register_taxonomies() {
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

		register_taxonomy('product_line', 'product_settings', array(
			'hierarchical'      => true, // For future flexibility
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => false, // We'll integrate with unified interface
			'show_admin_column' => false, // We'll handle this in our interface
			'query_var'         => true,
			'public'            => true,
			'publicly_queryable' => true,
			'show_in_rest'      => true,
			'rewrite'           => false, // We'll handle rewrite rules manually
		));

		// Register Product Media Tags taxonomy
		$media_labels = array(
			'name'              => _x('Product Media Tags', 'taxonomy general name', 'peaches'),
			'singular_name'     => _x('Product Media Tag', 'taxonomy singular name', 'peaches'),
			'search_items'      => __('Search Media Tags', 'peaches'),
			'all_items'         => __('All Media Tags', 'peaches'),
			'edit_item'         => __('Edit Media Tag', 'peaches'),
			'update_item'       => __('Update Media Tag', 'peaches'),
			'add_new_item'      => __('Add New Media Tag', 'peaches'),
			'new_item_name'     => __('New Media Tag Name', 'peaches'),
			'menu_name'         => __('Product Media Tags', 'peaches'),
		);

		register_taxonomy('product_media_tag', 'product_settings', array(
			'hierarchical'      => false,
			'labels'            => $media_labels,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => false,
			'query_var'         => true,
			'public'            => false,
			'show_in_rest'      => true,
		));

		// Register Line Media Tags taxonomy
		$line_media_labels = array(
			'name'              => _x('Line Media Tags', 'taxonomy general name', 'peaches'),
			'singular_name'     => _x('Line Media Tag', 'taxonomy singular name', 'peaches'),
			'search_items'      => __('Search Line Media Tags', 'peaches'),
			'all_items'         => __('All Line Media Tags', 'peaches'),
			'edit_item'         => __('Edit Line Media Tag', 'peaches'),
			'update_item'       => __('Update Line Media Tag', 'peaches'),
			'add_new_item'      => __('Add New Line Media Tag', 'peaches'),
			'new_item_name'     => __('New Line Media Tag Name', 'peaches'),
			'menu_name'         => __('Line Media Tags', 'peaches'),
		);

		register_taxonomy('line_media_tag', null, array(
			'hierarchical'      => false,
			'labels'            => $line_media_labels,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_admin_column' => false,
			'query_var'         => true,
			'public'            => false,
			'show_in_rest'      => true,
		));

		// Register regular WordPress tags for product_settings
		register_taxonomy_for_object_type('post_tag', 'product_settings');
	}

	/**
	 * Add term meta fields for new terms.
	 */
	public function add_term_meta_fields() {
		wp_nonce_field('save_product_line_meta', 'product_line_meta_nonce');
		?>
		<div class="form-field">
			<label for="line_type"><?php _e('Line Type', 'peaches'); ?></label>
			<input type="text" name="line_type" id="line_type" value="" list="line_type_suggestions">
			<datalist id="line_type_suggestions">
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
			<?php
			wp_editor('', 'line_description', array(
				'textarea_name' => 'line_description',
				'media_buttons' => false,
				'textarea_rows' => 5,
				'teeny' => true,
			));
			?>
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
	 */
	public function edit_term_meta_fields($term) {
		$line_type = get_term_meta($term->term_id, 'line_type', true);
		$line_description = get_term_meta($term->term_id, 'line_description', true);
		$line_media = get_term_meta($term->term_id, 'line_media', true);

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
	}

	/**
	 * Render an individual media item.
	 */
	private function render_media_item($media, $index) {
		$tag = isset($media['tag']) ? $media['tag'] : '';
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
				'taxonomy' => 'line_media_tag',
				'hide_empty' => false,
				'fields' => 'names'
			));
			foreach ($existing_tags as $existing_tag) {
				echo '<option value="' . esc_attr($existing_tag) . '">';
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
	 */
	public function save_term_meta($term_id) {
		if (!isset($_POST['product_line_meta_nonce']) || !wp_verify_nonce($_POST['product_line_meta_nonce'], 'save_product_line_meta')) {
			return;
		}

		if (!current_user_can('manage_categories')) {
			return;
		}

		// Save line type
		if (isset($_POST['line_type'])) {
			update_term_meta($term_id, 'line_type', sanitize_text_field($_POST['line_type']));
		}

		// Save line description
		if (isset($_POST['line_description'])) {
			update_term_meta($term_id, 'line_description', wp_kses_post($_POST['line_description']));
		}

		// Save line media
		if (isset($_POST['line_media']) && is_array($_POST['line_media'])) {
			$line_media = array();

			foreach ($_POST['line_media'] as $media) {
				if (!empty($media['tag']) && !empty($media['attachment_id'])) {
					$attachment_id = absint($media['attachment_id']);
					$tag_name = sanitize_text_field($media['tag']);

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
						'tag' => $tag_name,
						'tag_id' => $tag_term_id,
						'attachment_id' => $attachment_id
					);

					// Mark attachment as line media
					update_post_meta($attachment_id, '_peaches_line_media', true);
					update_post_meta($attachment_id, '_peaches_line_media_tag', $tag_name);
				}
			}

			update_term_meta($term_id, 'line_media', $line_media);
		}
	}

	/**
	 * Add custom columns to term list.
	 */
	public function add_term_columns($columns) {
		$new_columns = array();

		if (isset($columns['cb'])) {
			$new_columns['cb'] = $columns['cb'];
		}
		if (isset($columns['name'])) {
			$new_columns['name'] = $columns['name'];
		}

		$new_columns['line_type'] = __('Type', 'peaches');
		$new_columns['media_count'] = __('Media', 'peaches');
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
	 */
	private function get_existing_line_types() {
		global $wpdb;

		$types = $wpdb->get_col(
			"SELECT DISTINCT meta_value
			 FROM {$wpdb->termmeta}
			 WHERE meta_key = 'line_type'
			 AND meta_value != ''"
		);

		return array_filter($types);
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function enqueue_admin_scripts($hook) {
		if (strpos($hook, 'edit-tags.php') !== false && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'product_line') {
			wp_enqueue_media();

			wp_enqueue_script(
				'product-lines-admin',
				PEACHES_ECWID_ASSETS_URL . 'js/admin-product-lines.js',
				array('jquery', 'media-upload'),
				PEACHES_ECWID_VERSION,
				true
			);

			wp_localize_script('product-lines-admin', 'ProductLinesParams', array(
				'selectMediaTitle' => __('Select Line Media', 'peaches'),
				'selectMediaButton' => __('Use this media', 'peaches'),
			));
		}
	}

	/**
	 * Get all product lines.
	 */
	public function get_all_lines() {
		return get_terms(array(
			'taxonomy' => 'product_line',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC'
		));
	}

	/**
	 * Get line media by line ID.
	 */
	public function get_line_media($line_id) {
		$media = get_term_meta($line_id, 'line_media', true);
		return is_array($media) ? $media : array();
	}

	/**
	 * Get lines for a specific product.
	 */
	public function get_product_lines($product_id) {
		// Get product_settings post that matches this product ID
		$args = array(
			'post_type' => 'product_settings',
			'meta_query' => array(
				array(
					'key' => '_ecwid_product_id',
					'value' => $product_id,
					'compare' => '='
				)
			),
			'posts_per_page' => 1
		);

		$query = new WP_Query($args);
		if ($query->have_posts()) {
			$product_settings = $query->posts[0];
			return wp_get_object_terms($product_settings->ID, 'product_line', array('fields' => 'ids'));
		}

		return array();
	}

	/**
	 * Setup menu manipulation for product lines pages
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
	 * Set the parent menu as active when viewing product lines
	 */
	public function set_parent_menu_active($parent_file) {
		global $current_screen;

		if ($this->is_product_line_page()) {
			return 'peaches-settings';
		}

		return $parent_file;
	}

	/**
	 * Set the submenu as active when viewing product lines
	 */
	public function set_submenu_active($submenu_file) {
		global $current_screen;

		if ($this->is_product_line_page()) {
			return 'peaches-ecwid-product-settings';
		}

		return $submenu_file;
	}

	/**
	 * Add menu styling for better visual consistency
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
	 * Add breadcrumb navigation to product lines pages
	 */
	public function add_breadcrumb_navigation() {
		if (!$this->is_product_line_page()) {
			return;
		}

		$main_page_url = admin_url('admin.php?page=peaches-ecwid-product-settings&tab=product_lines');
		$page_title = $this->get_current_page_title();

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
				<div style="flex-shrink: 0;">
					<a href="<?php echo esc_url($main_page_url); ?>" class="button button-primary">
						<span class="dashicons dashicons-arrow-left-alt2" style="margin-top: 3px;"></span>
						<?php _e('Back to Ecwid Products', 'peaches'); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if we're on a product line related page
	 */
	private function is_product_line_page() {
		global $current_screen, $taxnow;

		if (!$current_screen) {
			return false;
		}

		// Check for product_line taxonomy pages
		if (isset($current_screen->taxonomy) && $current_screen->taxonomy === 'product_line') {
			return true;
		}

		// Check for edit-tags.php with product_line taxonomy
		if (isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'product_line') {
			return true;
		}

		// Check global taxonomy variable
		if ($taxnow === 'product_line') {
			return true;
		}

		return false;
	}

	/**
	 * Get the current page title for breadcrumbs
	 */
	private function get_current_page_title() {
		global $current_screen;

		if (!$current_screen) {
			return '';
		}

		// Determine page context
		if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['tag_ID'])) {
			// Editing a specific term
			$term_id = absint($_GET['tag_ID']);
			$term = get_term($term_id, 'product_line');
			if ($term && !is_wp_error($term)) {
				return sprintf(__('Edit "%s"', 'peaches'), $term->name);
			}
		} elseif ($current_screen->action === 'add') {
			// Adding new term
			return __('Add New Product Line', 'peaches');
		} elseif (strpos($current_screen->id, 'edit-product_line') !== false) {
			// List view
			return __('All Product Lines', 'peaches');
		}

		return '';
	}
}
