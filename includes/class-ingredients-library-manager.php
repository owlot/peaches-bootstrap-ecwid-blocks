<?php
/**
 * Ingredients Library Manager class
 *
 * Manages the central repository of ingredients that can be reused across products.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Ingredients_Library_Manager
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
class Peaches_Ingredients_Library_Manager {
	/**
	 * Constructor.
	 */
	public function __construct() {
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
		add_action('save_post_product_ingredient', array($this, 'save_meta_data'));

		// Add columns to admin list
		add_filter('manage_product_ingredient_posts_columns', array($this, 'add_custom_columns'));
		add_action('manage_product_ingredient_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);

		// Note: We no longer add the admin_menu hook here since the settings class handles menu organization
	}

	/**
	 * Register Product Ingredient post type.
	 *
	 * @since 0.1.2
	 */
	public function register_post_type() {
		$labels = array(
			'name' => _x('Ingredients Library', 'Post type general name', 'peaches'),
			'singular_name' => _x('Product Ingredient', 'Post type singular name', 'peaches'),
			'menu_name' => _x('Ingredients Library', 'Admin Menu text', 'peaches'),
			'name_admin_bar' => _x('Product Ingredient', 'Add New on Toolbar', 'peaches'),
			'add_new' => __('Add New', 'peaches'),
			'add_new_item' => __('Add New Product Ingredient', 'peaches'),
			'new_item' => __('New Product Ingredient', 'peaches'),
			'edit_item' => __('Edit Product Ingredient', 'peaches'),
			'view_item' => __('View Product Ingredient', 'peaches'),
			'all_items' => __('All Ingredients', 'peaches'),
			'search_items' => __('Search Ingredients', 'peaches'),
			'not_found' => __('No ingredients found.', 'peaches'),
			'not_found_in_trash' => __('No ingredients found in Trash.', 'peaches'),
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => false, // Changed from string to false - menu will be handled by settings class
			'query_var' => true,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => null,
			'menu_icon' => 'dashicons-carrot',
			'supports' => array('title'),
			'show_in_rest' => true,
		);

		register_post_type('product_ingredient', $args);
	}

	/**
	 * Add meta boxes.
	 *
	 * @since 0.1.2
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'ingredient_details_meta',
			__('Ingredient Details', 'peaches'),
			array($this, 'render_meta_box'),
			'product_ingredient',
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @since 0.1.2
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box($post) {
		// Get current description
		$description = get_post_meta($post->ID, '_ingredient_description', true);

		// Get available languages
		$languages = $this->get_available_languages();

		wp_nonce_field('save_product_ingredient', 'product_ingredient_nonce');
?>
	<div class="product-ingredient-meta">
		<div class="row mb-3">
			<div class="col-12">
				<label for="post_title" class="form-label"><?php _e('Name (English):', 'peaches'); ?></label>
				<input type="text" name="post_title" size="30" value="<?php echo esc_attr($post->post_title); ?>" id="title" spellcheck="true" autocomplete="off" class="form-control">
			</div>
		</div>

		<div class="row mb-3">
			<div class="col-12">
				<label for="ingredient_description" class="form-label"><?php _e('Description (English):', 'peaches'); ?></label>
				<textarea id="ingredient_description" name="ingredient_description" rows="4" class="form-control"><?php echo esc_textarea($description); ?></textarea>
			</div>
		</div>

		<?php if (!empty($languages) && count($languages) > 1): ?>
		<div class="translations-section">
			<h5 class="mb-3"><?php _e('Translations', 'peaches'); ?></h5>
			<?php foreach ($languages as $lang_code => $lang_name): ?>
			<?php if ($lang_code !== 'en'): // Skip default language ?>
			<div class="card mb-3">
				<div class="card-header">
					<h6 class="card-title mb-0"><?php printf(__('%s Translation', 'peaches'), $lang_name); ?></h6>
				</div>
				<div class="card-body">
					<div class="row mb-3">
						<div class="col-12">
							<label for="ingredient_name_<?php echo esc_attr($lang_code); ?>" class="form-label">
								<?php printf(__('Name (%s):', 'peaches'), $lang_name); ?>
							</label>
							<input type="text" id="ingredient_name_<?php echo esc_attr($lang_code); ?>"
								name="ingredient_name_<?php echo esc_attr($lang_code); ?>"
								value="<?php echo esc_attr(get_post_meta($post->ID, '_ingredient_name_' . $lang_code, true)); ?>"
								class="form-control">
						</div>
					</div>
					<div class="row">
						<div class="col-12">
							<label for="ingredient_description_<?php echo esc_attr($lang_code); ?>" class="form-label">
								<?php printf(__('Description (%s):', 'peaches'), $lang_name); ?>
							</label>
							<textarea id="ingredient_description_<?php echo esc_attr($lang_code); ?>"
								name="ingredient_description_<?php echo esc_attr($lang_code); ?>"
								rows="4"
								class="form-control"><?php
								echo esc_textarea(get_post_meta($post->ID, '_ingredient_description_' . $lang_code, true));
							?></textarea>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
<?php
	}

	/**
	 * Save meta data.
	 *
	 * @since 0.1.2
	 * @param int $post_id The post ID.
	 */
	public function save_meta_data($post_id) {
		// Check nonce
		if (!isset($_POST['product_ingredient_nonce']) || !wp_verify_nonce($_POST['product_ingredient_nonce'], 'save_product_ingredient')) {
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

		// Save description
		if (isset($_POST['ingredient_description'])) {
			update_post_meta($post_id, '_ingredient_description', wp_kses_post($_POST['ingredient_description']));
		}

		// Save translations
		$languages = $this->get_available_languages();
		foreach ($languages as $lang_code => $lang_name) {
			if ($lang_code !== 'en') {
				// Save description translations
				if (isset($_POST['ingredient_description_' . $lang_code])) {
					update_post_meta($post_id, '_ingredient_description_' . $lang_code, wp_kses_post($_POST['ingredient_description_' . $lang_code]));
				}
				// Save title translations
				if (isset($_POST['ingredient_name_' . $lang_code])) {
					update_post_meta($post_id, '_ingredient_name_' . $lang_code, sanitize_text_field($_POST['ingredient_name_' . $lang_code]));
				}
			}
		}
	}

	/**
	 * Get available languages.
	 *
	 * @since 0.1.2
	 * @return array List of available languages.
	 */
	private function get_available_languages() {
		$languages = array();

		// Polylang support
		if (function_exists('pll_languages_list')) {
			$lang_codes = pll_languages_list();

			if (is_array($lang_codes)) {
				foreach ($lang_codes as $code) {
					// Only get language details if the function exists
					if (function_exists('pll_get_language')) {
						$lang = pll_get_language($code);
						if (is_array($lang) && isset($lang['name'])) {
							$languages[$code] = $lang['name'];
						} else {
							// Fallback to language code if we can't get the name
							$languages[$code] = $code;
						}
					} else {
						// Fallback to just the code if function doesn't exist
						$languages[$code] = $code;
					}
				}
			}
		}
		// WPML support
		elseif (defined('ICL_LANGUAGE_CODE') && class_exists('SitePress')) {
			global $sitepress;
			if ($sitepress && method_exists($sitepress, 'get_active_languages')) {
				$active_languages = $sitepress->get_active_languages();
				if (is_array($active_languages)) {
					foreach ($active_languages as $code => $lang) {
						$languages[$code] = isset($lang['native_name']) ? $lang['native_name'] : $code;
					}
				}
			}
		}

		// Default to English only if no multilingual plugin is active or if no languages found
		if (empty($languages)) {
			$languages['en'] = 'English';
		}

		return $languages;
	}

	/**
	 * Add custom columns.
	 *
	 * @since 0.1.2
	 * @param array $columns Current columns.
	 * @return array Modified columns.
	 */
	public function add_custom_columns($columns) {
		$new_columns = array();

		// Keep title
		if (isset($columns['title'])) {
			$new_columns['title'] = $columns['title'];
		}

		// Add description preview
		$new_columns['description'] = __('Description', 'peaches');

		// Add remaining columns
		foreach ($columns as $key => $value) {
			if ($key !== 'title') {
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom columns.
	 *
	 * @since 0.1.2
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_custom_columns($column, $post_id) {
		switch ($column) {
		case 'description':
			$description = get_post_meta($post_id, '_ingredient_description', true);
			echo wp_trim_words($description, 15);
			break;
		}
	}

	/**
	 * Get all ingredients from the library.
	 *
	 * @since 0.1.2
	 * @return array Array of ingredient posts.
	 */
	public function get_all_ingredients() {
		return get_posts(array(
			'post_type' => 'product_ingredient',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'post_status' => 'publish'
		));
	}

	/**
	 * Get ingredient by ID.
	 *
	 * @since 0.1.2
	 * @param int $ingredient_id Ingredient ID.
	 * @return WP_Post|null Ingredient post or null if not found.
	 */
	public function get_ingredient($ingredient_id) {
		$ingredient = get_post($ingredient_id);

		if ($ingredient && $ingredient->post_type === 'product_ingredient') {
			return $ingredient;
		}

		return null;
	}

	/**
	 * Get ingredient with translations.
	 *
	 * @since 0.1.2
	 * @param int    $ingredient_id Ingredient ID.
	 * @param string $lang          Language code (optional).
	 * @return array Ingredient data with translations.
	 */
	public function get_ingredient_with_translations($ingredient_id, $lang = '') {
		$ingredient = $this->get_ingredient($ingredient_id);

		if (!$ingredient) {
			return null;
		}

		if (empty($lang)) {
			$lang = Peaches_Ecwid_Utilities::get_current_language();
		}

		$data = array(
			'id' => $ingredient->ID,
			'name' => $ingredient->post_title,
			'description' => get_post_meta($ingredient->ID, '_ingredient_description', true)
		);

		// Get translations if available
		if (!empty($lang) && $lang !== 'en') {
			$translated_name = get_post_meta($ingredient->ID, '_ingredient_name_' . $lang, true);
			$translated_description = get_post_meta($ingredient->ID, '_ingredient_description_' . $lang, true);

			if (!empty($translated_name)) {
				$data['name'] = $translated_name;
			}

			if (!empty($translated_description)) {
				$data['description'] = $translated_description;
			}
		}

		return $data;
	}
}
