<?php
// includes/master-ingredients.php

/**
 * Master Ingredients Custom Post Type
 * Central management of ingredients that can be reused across products
 */
class Peaches_Master_Ingredients {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_master_ingredient', array($this, 'save_meta_data'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add columns to admin list
        add_filter('manage_master_ingredient_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_master_ingredient_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
    }

    /**
     * Register Master Ingredient post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => _x('Master Ingredients', 'Post type general name', 'ecwid-shopping-cart'),
            'singular_name' => _x('Master Ingredient', 'Post type singular name', 'ecwid-shopping-cart'),
            'menu_name' => _x('Master Ingredients', 'Admin Menu text', 'ecwid-shopping-cart'),
            'name_admin_bar' => _x('Master Ingredient', 'Add New on Toolbar', 'ecwid-shopping-cart'),
            'add_new' => __('Add New', 'ecwid-shopping-cart'),
            'add_new_item' => __('Add New Master Ingredient', 'ecwid-shopping-cart'),
            'new_item' => __('New Master Ingredient', 'ecwid-shopping-cart'),
            'edit_item' => __('Edit Master Ingredient', 'ecwid-shopping-cart'),
            'view_item' => __('View Master Ingredient', 'ecwid-shopping-cart'),
            'all_items' => __('All Master Ingredients', 'ecwid-shopping-cart'),
            'search_items' => __('Search Master Ingredients', 'ecwid-shopping-cart'),
            'not_found' => __('No ingredients found.', 'ecwid-shopping-cart'),
            'not_found_in_trash' => __('No ingredients found in Trash.', 'ecwid-shopping-cart'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=product_ingredients',
            'query_var' => true,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-admin-page',
            'supports' => array('title'),
            'show_in_rest' => true,
        );

        register_post_type('master_ingredient', $args);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'master_ingredient_meta',
            __('Ingredient Details', 'ecwid-shopping-cart'),
            array($this, 'render_meta_box'),
            'master_ingredient',
            'normal',
            'high'
        );
    }

	/**
	 * Render meta box
	 */
	public function render_meta_box($post) {
		// Get current description
		$description = get_post_meta($post->ID, '_ingredient_description', true);

		// Get available languages
		$languages = $this->get_available_languages();

		wp_nonce_field('save_master_ingredient', 'master_ingredient_nonce');
		?>
		<div class="master-ingredient-meta">
			<p>
				<label for="post_title"><?php _e('Name (English):', 'ecwid-shopping-cart'); ?></label>
				<input type="text" name="post_title" size="30" value="<?php echo esc_attr($post->post_title); ?>" id="title" spellcheck="true" autocomplete="off" class="widefat">
			</p>

			<p>
				<label for="ingredient_description"><?php _e('Description (English):', 'ecwid-shopping-cart'); ?></label>
				<textarea id="ingredient_description" name="ingredient_description" rows="4" class="widefat"><?php echo esc_textarea($description); ?></textarea>
			</p>

			<?php if (!empty($languages) && count($languages) > 1): ?>
				<h3><?php _e('Translations', 'ecwid-shopping-cart'); ?></h3>
				<?php foreach ($languages as $lang_code => $lang_name): ?>
					<?php if ($lang_code !== 'en'): // Skip default language ?>
						<div class="ingredient-translation-fields">
							<h4><?php printf(__('%s Translation', 'ecwid-shopping-cart'), $lang_name); ?></h4>
							<p>
								<label for="ingredient_name_<?php echo esc_attr($lang_code); ?>">
									<?php printf(__('Name (%s):', 'ecwid-shopping-cart'), $lang_name); ?>
								</label>
								<input type="text" id="ingredient_name_<?php echo esc_attr($lang_code); ?>"
									   name="ingredient_name_<?php echo esc_attr($lang_code); ?>"
									   value="<?php echo esc_attr(get_post_meta($post->ID, '_ingredient_name_' . $lang_code, true)); ?>"
									   class="widefat">
							</p>
							<p>
								<label for="ingredient_description_<?php echo esc_attr($lang_code); ?>">
									<?php printf(__('Description (%s):', 'ecwid-shopping-cart'), $lang_name); ?>
								</label>
								<textarea id="ingredient_description_<?php echo esc_attr($lang_code); ?>"
										  name="ingredient_description_<?php echo esc_attr($lang_code); ?>"
										  rows="4"
										  class="widefat"><?php
									echo esc_textarea(get_post_meta($post->ID, '_ingredient_description_' . $lang_code, true));
								?></textarea>
							</p>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

    /**
     * Save meta data
     */
	public function save_meta_data($post_id) {
		// Check nonce
		if (!isset($_POST['master_ingredient_nonce']) || !wp_verify_nonce($_POST['master_ingredient_nonce'], 'save_master_ingredient')) {
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
     * Get available languages
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
     * Add custom columns
     */
    public function add_custom_columns($columns) {
        $new_columns = array();

        // Keep title
        if (isset($columns['title'])) {
            $new_columns['title'] = $columns['title'];
        }

        // Add description preview
        $new_columns['description'] = __('Description', 'ecwid-shopping-cart');

        // Add remaining columns
        foreach ($columns as $key => $value) {
            if ($key !== 'title') {
                $new_columns[$key] = $value;
            }
        }

        return $new_columns;
    }

    /**
     * Render custom columns
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
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=product_ingredients',
            __('Ingredients Library', 'ecwid-shopping-cart'),
            __('Ingredients Library', 'ecwid-shopping-cart'),
            'manage_options',
            'master-ingredients',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page
     */
	public function render_settings_page() {
		// Get all master ingredients
		$args = array(
			'post_type' => 'master_ingredient',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC'
		);
		$ingredients = get_posts($args);

		// Get current language
		$current_lang = '';
		if (function_exists('pll_current_language')) {
			$current_lang = pll_current_language();
		} elseif (defined('ICL_LANGUAGE_CODE')) {
			$current_lang = ICL_LANGUAGE_CODE;
		}

		?>
		<div class="wrap">
			<h1><?php _e('Ingredients Library', 'ecwid-shopping-cart'); ?></h1>

			<p><?php _e('Manage your master ingredients library. These ingredients can be reused across different products.', 'ecwid-shopping-cart'); ?></p>

			<p>
				<a href="<?php echo admin_url('post-new.php?post_type=master_ingredient'); ?>" class="button button-primary">
					<?php _e('Add New Ingredient', 'ecwid-shopping-cart'); ?>
				</a>
			</p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e('Name', 'ecwid-shopping-cart'); ?></th>
						<th><?php _e('Description', 'ecwid-shopping-cart'); ?></th>
						<th><?php _e('Actions', 'ecwid-shopping-cart'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($ingredients)): ?>
						<?php foreach ($ingredients as $ingredient): ?>
							<?php
							// Get translated name
							$name = $ingredient->post_title;
							if ($current_lang && $current_lang !== 'en') {
								$translated_name = get_post_meta($ingredient->ID, '_ingredient_name_' . $current_lang, true);
								if (!empty($translated_name)) {
									$name = $translated_name;
								}
							}

							// Get translated description
							$description_key = $current_lang && $current_lang !== 'en' ?
								'_ingredient_description_' . $current_lang :
								'_ingredient_description';

							$description = get_post_meta($ingredient->ID, $description_key, true);
							?>
							<tr>
								<td><strong><?php echo esc_html($name); ?></strong></td>
								<td><?php echo wp_trim_words($description, 20); ?></td>
								<td>
									<a href="<?php echo get_edit_post_link($ingredient->ID); ?>" class="button button-small">
										<?php _e('Edit', 'ecwid-shopping-cart'); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr>
							<td colspan="3">
								<?php _e('No ingredients found. Add your first ingredient!', 'ecwid-shopping-cart'); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Add any necessary JavaScript or CSS here
    }
}

// Initialize the class
new Peaches_Master_Ingredients();
