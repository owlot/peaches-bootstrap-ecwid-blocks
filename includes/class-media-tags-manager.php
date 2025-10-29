<?php
/**
 * Media Tags Manager class
 *
 * Manages predefined media tags that can be used across products.
 * Each tag represents a media type (hero_image, size_chart etc.)
 * and products can assign one media item per tag.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Media_Tags_Manager
 *
 * Implements media tags management with proper error handling and validation.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Media_Tags_Manager implements Peaches_Media_Tags_Manager_Interface {

	/**
	 * Option name for storing media tags
	 *
	 * @since 0.2.0
	 * @var string
	 */
	const TAGS_OPTION = 'peaches_ecwid_media_tags';

	/**
	 * Available media types
	 *
	 * @since 0.2.0
	 * @var array
	 */
	const MEDIA_TYPES = array(
		'image'    => 'Image',
		'video'    => 'Video',
		'audio'    => 'Audio',
		'document' => 'Document',
	);

	/**
	 * Default media tags with expected media types
	 *
	 * @since 0.2.0
	 * @var array
	 */
	const DEFAULT_TAGS = array(
		'hero_image' => array(
			'name'               => 'Hero Image',
			'label'              => 'Hero Image',
			'description'        => 'Main product showcase image',
			'category'           => 'primary',
			'expectedMediaType'  => 'image',
			'required'           => false,
		),
		'ingredients_image' => array(
			'name'               => 'Ingredients Image',
			'label'              => 'Ingredients Image',
			'description'        => 'Image showing product ingredients or composition',
			'category'           => 'reference',
			'expectedMediaType'  => 'image',
			'required'           => false,
		),
		'size_chart' => array(
			'name'               => 'Size Chart',
			'label'              => 'Size Chart',
			'description'        => 'Product sizing information and measurements',
			'category'           => 'reference',
			'expectedMediaType'  => 'image',
			'required'           => false,
		),
		'demo_video' => array(
			'name'               => 'Demo Video',
			'label'              => 'Demo Video',
			'description'        => 'Product demonstration or usage video',
			'category'           => 'media',
			'expectedMediaType'  => 'video',
			'required'           => false,
		),
		'user_manual' => array(
			'name'               => 'User Manual',
			'label'              => 'User Manual',
			'description'        => 'Downloadable user manual or instructions',
			'category'           => 'reference',
			'expectedMediaType'  => 'document',
			'required'           => false,
		),
	);

	/**
	 * Tags cache to avoid repeated database queries.
	 *
	 * @since 0.2.0
	 * @var array|null
	 */
	private $tags_cache = null;

	/**
	 * Constructor.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		$this->init_hooks();
		$this->maybe_initialize_default_tags();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_add_media_tag', array( $this, 'ajax_add_media_tag' ) );
		add_action( 'wp_ajax_delete_media_tag', array( $this, 'ajax_delete_media_tag' ) );
		add_action( 'wp_ajax_update_media_tag', array( $this, 'ajax_update_media_tag' ) );
	}

	/**
	 * Maybe initialize default tags on first run.
	 *
	 * @since  0.2.0
	 * @return void
	 */
	private function maybe_initialize_default_tags() {
		try {
			$existing_tags = get_option( self::TAGS_OPTION, array() );

			if ( empty( $existing_tags ) ) {
				update_option( self::TAGS_OPTION, self::DEFAULT_TAGS );
				$this->log_info( 'Default media tags initialized' );
			} else {
				// Update existing tags to use new field name.
				$updated = false;
				foreach ( $existing_tags as $key => $tag_data ) {
					// Migrate old field name to new field name.
					if ( isset( $tag_data['expected_media_type'] ) && ! isset( $tag_data['expectedMediaType'] ) ) {
						$existing_tags[ $key ]['expectedMediaType'] = $tag_data['expected_media_type'];
						unset( $existing_tags[ $key ]['expected_media_type'] ); // Remove old field.
						$updated = true;
					}
					// If neither field exists, add default
					if (!isset($tag_data['expectedMediaType'])) {
						$existing_tags[$key]['expectedMediaType'] = $this->guess_media_type_from_tag($key, $tag_data);
						$updated = true;
					}
				}

				if ($updated) {
					update_option(self::TAGS_OPTION, $existing_tags);
					$this->log_info('Migrated existing media tags to use expectedMediaType field');
				}
			}
		} catch (Exception $e) {
			$this->log_error('Error initializing default tags', array(
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			));
		}
	}

	/**
	 * Guess media type from existing tag data.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key  Tag key.
	 * @param array  $tag_data Tag data.
	 *
	 * @return string Media type.
	 */
	private function guess_media_type_from_tag($tag_key, $tag_data) {
		$key_lower = strtolower($tag_key);
		$label_lower = strtolower($tag_data['label'] ?? '');
		$desc_lower = strtolower($tag_data['description'] ?? '');

		// Check for video keywords
		if (strpos($key_lower, 'video') !== false ||
			strpos($label_lower, 'video') !== false ||
			strpos($desc_lower, 'video') !== false) {
			return 'video';
		}

		// Check for audio keywords
		if (strpos($key_lower, 'audio') !== false ||
			strpos($label_lower, 'audio') !== false ||
			strpos($desc_lower, 'audio') !== false ||
			strpos($key_lower, 'sound') !== false) {
			return 'audio';
		}

		// Check for document keywords
		if (strpos($key_lower, 'manual') !== false ||
			strpos($key_lower, 'document') !== false ||
			strpos($key_lower, 'pdf') !== false ||
			strpos($label_lower, 'manual') !== false ||
			strpos($label_lower, 'document') !== false ||
			strpos($desc_lower, 'manual') !== false) {
			return 'document';
		}

		// Default to image
		return 'image';
	}

	/**
	 * Get available media types.
	 *
	 * @since 0.2.0
	 *
	 * @return array Media types array.
	 */
	public function get_media_types() {
		return self::MEDIA_TYPES;
	}

	/**
	 * Get all media tags with caching.
	 *
	 * @since 0.2.0
	 *
	 * @param bool $force_refresh Force refresh of cache.
	 *
	 * @return array Array of media tags.
	 */
	public function get_all_tags($force_refresh = false) {
		if ($force_refresh || $this->tags_cache === null) {
			try {
				$this->tags_cache = get_option(self::TAGS_OPTION, array());

				// Validate tags structure
				if (!is_array($this->tags_cache)) {
					$this->log_error('Invalid tags structure found, resetting to defaults');
					$this->tags_cache = self::DEFAULT_TAGS;
					update_option(self::TAGS_OPTION, $this->tags_cache);
				}
			} catch (Exception $e) {
				$this->log_error('Error getting all tags', array(
					'error' => $e->getMessage()
				));
				$this->tags_cache = self::DEFAULT_TAGS;
			}
		}

		return $this->tags_cache;
	}

	/**
	 * Get tags by category.
	 *
	 * @since 0.2.0
	 *
	 * @param string $category Category to filter by.
	 *
	 * @return array Array of filtered tags.
	 *
	 * @throws InvalidArgumentException If category is invalid.
	 */
	public function get_tags_by_category($category) {
		if (!is_string($category) || empty($category)) {
			throw new InvalidArgumentException('Category must be a non-empty string');
		}

		$all_tags = $this->get_all_tags();

		return array_filter($all_tags, function($tag_data) use ($category) {
			return isset($tag_data['category']) && $tag_data['category'] === $category;
		});
	}

	/**
	 * Get tags by expected media type.
	 *
	 * @since 0.2.0
	 *
	 * @param string $media_type Media type to filter by.
	 *
	 * @return array Array of filtered tags.
	 *
	 * @throws InvalidArgumentException If media type is invalid.
	 */
	public function get_tags_by_media_type($media_type) {
		if (!array_key_exists($media_type, self::MEDIA_TYPES)) {
			throw new InvalidArgumentException('Invalid media type: ' . $media_type);
		}

		$all_tags = $this->get_all_tags();

		return array_filter($all_tags, function($tag_data) use ($media_type) {
			return isset($tag_data['expectedMediaType']) && $tag_data['expectedMediaType'] === $media_type;
		});
	}

	/**
	 * Get tag data by key.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key Tag key.
	 *
	 * @return array|null Tag data or null if not found.
	 *
	 * @throws InvalidArgumentException If tag key is invalid.
	 */
	public function get_tag($tag_key) {
		if (!is_string($tag_key) || empty($tag_key)) {
			throw new InvalidArgumentException('Tag key must be a non-empty string');
		}

		$tags = $this->get_all_tags();
		return isset($tags[$tag_key]) ? $tags[$tag_key] : null;
	}

	/**
	 * Get expected media type for a tag.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key Tag key.
	 *
	 * @return string|null Expected media type or null if not found.
	 */
	public function get_tag_expected_media_type($tag_key) {
		$tag_data = $this->get_tag($tag_key);
		return $tag_data ? ($tag_data['expectedMediaType'] ?? null) : null;
	}

	/**
	 * Check if tag exists.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key Tag key.
	 *
	 * @return bool True if tag exists.
	 */
	public function tag_exists($tag_key) {
		if (!is_string($tag_key) || empty($tag_key)) {
			return false;
		}

		$tags = $this->get_all_tags();
		return isset($tags[$tag_key]);
	}

	/**
	 * Validate media type against tag expectations.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key    Tag key.
	 * @param string $media_url  Media URL to check.
	 * @param string $mime_type  Optional mime type.
	 *
	 * @return array Validation result with 'valid' boolean and 'message'.
	 */
	public function validate_media_for_tag($tag_key, $media_url, $mime_type = '') {
		$expected_type = $this->get_tag_expected_media_type($tag_key);

		if (!$expected_type) {
			return array(
				'valid' => false,
				'message' => __('Tag not found.', 'peaches')
			);
		}

		$actual_type = Peaches_Ecwid_Utilities::get_media_type($media_url, $mime_type);

		if ($actual_type === $expected_type) {
			return array(
				'valid' => true,
				'message' => __('Media type matches tag expectations.', 'peaches')
			);
		}

		$expected_label = self::MEDIA_TYPES[$expected_type];
		$actual_label = isset(self::MEDIA_TYPES[$actual_type]) ? self::MEDIA_TYPES[$actual_type] : $actual_type;

		return array(
			'valid' => false,
			'message' => sprintf(
				__('Expected %s but got %s. This may not display correctly.', 'peaches'),
				$expected_label,
				$actual_label
			)
		);
	}

	/**
	 * Add a new media tag.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key              Tag key.
	 * @param string $tag_label            Tag label.
	 * @param string $tag_description      Tag description.
	 * @param string $tag_category         Tag category.
	 * @param string $tag_expected_media_type Expected media type.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function add_tag($tag_key, $tag_label, $tag_description = '', $tag_category = 'primary', $tag_expected_media_type = 'image') {
		// Validate inputs
		$validation = $this->validate_tag_data($tag_key, $tag_label, $tag_expected_media_type);
		if (is_wp_error($validation)) {
			return $validation;
		}

		// Check if tag already exists
		if ($this->tag_exists($tag_key)) {
			return new WP_Error('tag_exists', __('A tag with this key already exists.', 'peaches'));
		}

		try {
			$tags = $this->get_all_tags();
			$tags[$tag_key] = array(
				'name'               => sanitize_text_field($tag_label),
				'label'              => sanitize_text_field($tag_label),
				'description'        => sanitize_textarea_field($tag_description),
				'category'           => sanitize_text_field($tag_category),
				'expectedMediaType'  => sanitize_text_field($tag_expected_media_type)
			);

			$result = update_option(self::TAGS_OPTION, $tags);

			if ($result) {
				$this->tags_cache = $tags; // Update cache
				$this->log_info('Media tag added successfully', array('tag_key' => $tag_key));
				return true;
			} else {
				return new WP_Error('save_failed', __('Failed to save tag.', 'peaches'));
			}
		} catch (Exception $e) {
			$this->log_error('Error adding media tag', array(
				'tag_key' => $tag_key,
				'error' => $e->getMessage()
			));
			return new WP_Error('add_error', __('Error adding tag.', 'peaches'));
		}
	}

	/**
	 * Update an existing media tag.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key              Tag key.
	 * @param string $tag_label            Tag label.
	 * @param string $tag_description      Tag description.
	 * @param string $tag_category         Tag category.
	 * @param string $tag_expected_media_type Expected media type.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function update_tag($tag_key, $tag_label, $tag_description = '', $tag_category = 'primary', $tag_expected_media_type = 'image') {
		// Validate inputs
		$validation = $this->validate_tag_data($tag_key, $tag_label, $tag_expected_media_type);
		if (is_wp_error($validation)) {
			return $validation;
		}

		// Check if tag exists
		if (!$this->tag_exists($tag_key)) {
			return new WP_Error('tag_not_found', __('Tag not found.', 'peaches'));
		}

		try {
			$tags = $this->get_all_tags();
			$tags[$tag_key] = array(
				'name'               => sanitize_text_field($tag_label),
				'label'              => sanitize_text_field($tag_label),
				'description'        => sanitize_textarea_field($tag_description),
				'category'           => sanitize_text_field($tag_category),
				'expectedMediaType'  => sanitize_text_field($tag_expected_media_type) // FIXED
			);

			$result = update_option(self::TAGS_OPTION, $tags);

			if ($result) {
				$this->tags_cache = $tags; // Update cache
				$this->log_info('Media tag updated successfully', array('tag_key' => $tag_key));
				return true;
			} else {
				return new WP_Error('save_failed', __('Failed to update tag.', 'peaches'));
			}
		} catch (Exception $e) {
			$this->log_error('Error updating media tag', array(
				'tag_key' => $tag_key,
				'error' => $e->getMessage()
			));
			return new WP_Error('update_error', __('Error updating tag.', 'peaches'));
		}
	}

	/**
	 * Delete a media tag.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key Tag key.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_tag($tag_key) {
		if (!is_string($tag_key) || empty($tag_key)) {
			return new WP_Error('invalid_tag_key', __('Invalid tag key provided.', 'peaches'));
		}

		// Prevent deletion of default tags
		if (array_key_exists($tag_key, self::DEFAULT_TAGS)) {
			return new WP_Error('cannot_delete_default', __('Default tags cannot be deleted.', 'peaches'));
		}

		// Check if tag exists
		if (!$this->tag_exists($tag_key)) {
			return new WP_Error('tag_not_found', __('Tag not found.', 'peaches'));
		}

		try {
			$tags = $this->get_all_tags();
			unset($tags[$tag_key]);

			$result = update_option(self::TAGS_OPTION, $tags);

			if ($result) {
				$this->tags_cache = $tags; // Update cache
				$this->log_info('Media tag deleted successfully', array('tag_key' => $tag_key));
				return true;
			} else {
				return new WP_Error('delete_failed', __('Failed to delete tag.', 'peaches'));
			}
		} catch (Exception $e) {
			$this->log_error('Error deleting media tag', array(
				'tag_key' => $tag_key,
				'error' => $e->getMessage()
			));
			return new WP_Error('delete_error', __('Error deleting tag.', 'peaches'));
		}
	}

	/**
	 * Validate tag data.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key              Tag key.
	 * @param string $tag_label            Tag label.
	 * @param string $tag_expected_media_type Expected media type.
	 *
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_tag_data($tag_key, $tag_label, $tag_expected_media_type) {
		if (empty($tag_key) || empty($tag_label) || empty($tag_expected_media_type)) {
			return new WP_Error('missing_required', __('Tag key, label, and expected media type are required.', 'peaches'));
		}

		// Validate tag key format
		if (!preg_match('/^[a-z0-9_]+$/', $tag_key)) {
			return new WP_Error('invalid_key_format', __('Tag key can only contain lowercase letters, numbers, and underscores.', 'peaches'));
		}

		// Validate expected media type
		if (!array_key_exists($tag_expected_media_type, self::MEDIA_TYPES)) {
			return new WP_Error('invalid_media_type', __('Invalid media type selected.', 'peaches'));
		}

		return true;
	}

	/**
	 * Clear tags cache.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function clear_cache() {
		$this->tags_cache = null;
	}

	/**
	 * Enqueue scripts specifically for the tab context.
	 *
	 * @since 0.2.0
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_tab_scripts($hook) {
		try {
			wp_enqueue_script(
				'peaches-media-tags-admin',
				PEACHES_ECWID_ASSETS_URL . 'js/admin-media-tags.js',
				array('jquery'),
				PEACHES_ECWID_VERSION,
				true
			);

			wp_localize_script('peaches-media-tags-admin', 'MediaTagsParams', array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('media_tags_nonce'),
				'mediaTypes' => self::MEDIA_TYPES,
				'strings' => array(
					'confirmDelete' => __('Are you sure you want to delete this tag? This action cannot be undone.', 'peaches'),
					'editTag' => __('Edit Tag', 'peaches'),
					'updateTag' => __('Update Tag', 'peaches'),
					'addTag' => __('Add Tag', 'peaches'),
					'autoGenerated' => __('Auto-generated from label', 'peaches'),
					'manualOverride' => __('Manual override active', 'peaches'),
				)
			));
		} catch (Exception $e) {
			$this->log_error('Error enqueuing tab scripts', array(
				'hook' => $hook,
				'error' => $e->getMessage()
			));
		}
	}

	/**
	 * Render admin page content for tab integration.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_admin_page_content() {
		try {
			$tags = $this->get_all_tags();
			$this->render_page_header($tags);
			$this->render_notices();
			$this->render_tags_table($tags);
			$this->render_reference_card();
			$this->render_modals();
		} catch (Exception $e) {
			$this->log_error('Error rendering admin page content', array(
				'error' => $e->getMessage()
			));
			echo '<div class="notice notice-error"><p>' . esc_html__('Error loading media tags interface.', 'peaches') . '</p></div>';
		}
	}

	/**
	 * Render page header with controls.
	 *
	 * @since 0.2.0
	 *
	 * @param array $tags All tags.
	 *
	 * @return void
	 */
	private function render_page_header($tags) {
		?>
		<div class="d-flex justify-content-between align-items-start mb-4">
			<div>
				<h3 class="mb-2"><?php esc_html_e('Media Tags Management', 'peaches'); ?></h3>
				<p class="text-muted"><?php esc_html_e('Manage predefined media tags that can be used across all products. Each tag has an expected media type to ensure consistent content.', 'peaches'); ?></p>
			</div>
			<button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#addTagModal">
				<i class="dashicons dashicons-plus-alt2"></i>
				<?php esc_html_e('Add New Tag', 'peaches'); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render notices.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_notices() {
		if (isset($_GET['message'])) {
			$message = sanitize_text_field($_GET['message']);
			$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';

			$alert_class = $type === 'error' ? 'alert-danger' : 'alert-success';
			?>
			<div class="alert <?php echo esc_attr($alert_class); ?> alert-dismissible fade show">
				<?php echo esc_html(urldecode($message)); ?>
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php esc_attr_e('Close', 'peaches'); ?>"></button>
			</div>
			<?php
		}
	}

	/**
	 * Render tags table.
	 *
	 * @since 0.2.0
	 *
	 * @param array $tags All tags.
	 *
	 * @return void
	 */
	private function render_tags_table($tags) {
		?>
		<div class="row mt-4">
			<div class="col-12">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="card-title mb-0"><?php esc_html_e('Media Tags', 'peaches'); ?></h5>
						<span class="badge bg-secondary"><?php echo count($tags); ?> <?php esc_html_e('tags', 'peaches'); ?></span>
					</div>
					<div class="card-body">
						<?php if (empty($tags)): ?>
							<div class="text-center py-5">
								<i class="dashicons dashicons-tag" style="font-size: 64px; color: #dee2e6; margin-bottom: 16px;"></i>
								<h4 class="text-muted"><?php esc_html_e('No media tags found', 'peaches'); ?></h4>
								<p class="text-muted mb-4"><?php esc_html_e('Create your first media tag to get started organizing your product media.', 'peaches'); ?></p>
								<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">
									<i class="dashicons dashicons-plus-alt2"></i>
									<?php esc_html_e('Create First Tag', 'peaches'); ?>
								</button>
							</div>
						<?php else: ?>
							<div class="table-responsive">
								<table class="table table-hover align-middle">
									<thead class="table-light">
										<tr>
											<th scope="col" class="border-0"><?php esc_html_e('Name', 'peaches'); ?></th>
											<th scope="col" class="border-0"><?php esc_html_e('Tag', 'peaches'); ?></th>
											<th scope="col" class="border-0"><?php esc_html_e('Media Type', 'peaches'); ?></th>
											<th scope="col" class="border-0"><?php esc_html_e('Category', 'peaches'); ?></th>
											<th scope="col" class="border-0"><?php esc_html_e('Description', 'peaches'); ?></th>
											<th scope="col" class="border-0 text-end"><?php esc_html_e('Actions', 'peaches'); ?></th>
										</tr>
									</thead>
									<tbody id="media-tags-list">
										<?php foreach ($tags as $tag_key => $tag_data): ?>
											<?php $this->render_tag_row($tag_key, $tag_data); ?>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a tag row.
	 *
	 * @since 0.2.0
	 *
	 * @param string $tag_key  Tag key.
	 * @param array  $tag_data Tag data.
	 *
	 * @return void
	 */
	private function render_tag_row($tag_key, $tag_data) {
		$category_badges = array(
			'primary' => 'bg-primary',
			'secondary' => 'bg-secondary',
			'reference' => 'bg-info',
			'media' => 'bg-warning text-dark'
		);

		$media_type_badges = array(
			'image' => 'bg-success',
			'video' => 'bg-danger',
			'audio' => 'bg-warning text-dark',
			'document' => 'bg-info'
		);

		$badge_class = isset($category_badges[$tag_data['category']]) ? $category_badges[$tag_data['category']] : 'bg-secondary';
		$media_type_badge = isset($media_type_badges[$tag_data['expectedMediaType']]) ? $media_type_badges[$tag_data['expectedMediaType']] : 'bg-secondary';
		$is_default = array_key_exists($tag_key, self::DEFAULT_TAGS);
		$media_type_label = isset(self::MEDIA_TYPES[$tag_data['expectedMediaType']]) ? self::MEDIA_TYPES[$tag_data['expectedMediaType']] : 'Unknown';
		?>
		<tr class="media-tag-row" data-tag-key="<?php echo esc_attr($tag_key); ?>">
			<td>
				<div class="d-flex align-items-center">
					<div>
						<h6 class="mb-1"><?php echo esc_html($tag_data['label']); ?></h6>
					</div>
				</div>
			</td>
			<td>
				<code class="bg-light px-2 py-1 rounded text-dark text-nowrap"><?php echo esc_html($tag_key); ?></code>
				<div>
				<?php if ($is_default): ?>
					<small class="text-muted hstack mt-1">
						<i class="dashicons dashicons-lock"></i>
						<?php esc_html_e('Default tag', 'peaches'); ?>
					</small>
				<?php endif; ?>
				</div>
			</td>
			<td>
				<span class="badge <?php echo esc_attr($media_type_badge); ?>">
					<?php echo esc_html($media_type_label); ?>
				</span>
			</td>
			<td>
				<span class="badge <?php echo esc_attr($badge_class); ?>">
					<?php echo esc_html(ucfirst($tag_data['category'])); ?>
				</span>
			</td>
			<td>
				<?php if (!empty($tag_data['description'])): ?>
					<span class="text-muted"><?php echo esc_html($tag_data['description']); ?></span>
				<?php else: ?>
					<em class="text-muted"><?php esc_html_e('No description', 'peaches'); ?></em>
				<?php endif; ?>
			</td>
			<td class="text-end">
				<div class="btn-group btn-group-sm" role="group">
					<button type="button"
							class="btn btn-outline-primary edit-tag-btn"
							data-tag-key="<?php echo esc_attr($tag_key); ?>"
							data-tag-label="<?php echo esc_attr($tag_data['label']); ?>"
							data-tag-description="<?php echo esc_attr($tag_data['description']); ?>"
							data-tag-category="<?php echo esc_attr($tag_data['category']); ?>"
							data-tag-expected-media-type="<?php echo esc_attr($tag_data['expectedMediaType']); ?>"
							data-bs-toggle="tooltip"
							title="<?php esc_attr_e('Edit tag', 'peaches'); ?>">
						<i class="dashicons dashicons-edit"></i>
						<span class="visually-hidden"><?php esc_html_e('Edit', 'peaches'); ?></span>
					</button>
					<?php if (!$is_default): ?>
						<button type="button"
								class="btn btn-outline-danger delete-tag-btn"
								data-tag-key="<?php echo esc_attr($tag_key); ?>"
								data-tag-label="<?php echo esc_attr($tag_data['label']); ?>"
								data-bs-toggle="tooltip"
								title="<?php esc_attr_e('Delete tag', 'peaches'); ?>">
							<i class="dashicons dashicons-trash"></i>
							<span class="visually-hidden"><?php esc_html_e('Delete', 'peaches'); ?></span>
						</button>
					<?php else: ?>
						<button type="button"
								class="btn btn-outline-secondary"
								disabled
								data-bs-toggle="tooltip"
								title="<?php esc_attr_e('Default tags cannot be deleted', 'peaches'); ?>">
							<i class="dashicons dashicons-lock"></i>
							<span class="visually-hidden"><?php esc_html_e('Protected', 'peaches'); ?></span>
						</button>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render reference card.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_reference_card() {
		?>
		<div class="card mt-4">
			<div class="card-header">
				<h6 class="card-title mb-0">
					<i class="dashicons dashicons-info"></i>
					<?php esc_html_e('Media Types & Categories Reference', 'peaches'); ?>
				</h6>
			</div>
			<div class="card-body">
				<div class="row g-3 mb-4">
					<div class="col-md-6">
						<h6 class="fw-bold"><?php esc_html_e('Media Types', 'peaches'); ?></h6>
						<div class="d-flex flex-column gap-2">
							<div class="d-flex align-items-center">
								<span class="badge bg-success me-2">Image</span>
								<small class="text-muted"><?php esc_html_e('Photos, graphics, charts', 'peaches'); ?></small>
							</div>
							<div class="d-flex align-items-center">
								<span class="badge bg-danger me-2">Video</span>
								<small class="text-muted"><?php esc_html_e('Product demos, tutorials', 'peaches'); ?></small>
							</div>
							<div class="d-flex align-items-center">
								<span class="badge bg-warning text-dark me-2">Audio</span>
								<small class="text-muted"><?php esc_html_e('Sounds, music, voice clips', 'peaches'); ?></small>
							</div>
							<div class="d-flex align-items-center">
								<span class="badge bg-info me-2">Document</span>
								<small class="text-muted"><?php esc_html_e('PDFs, manuals, guides', 'peaches'); ?></small>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<h6 class="fw-bold"><?php esc_html_e('Categories', 'peaches'); ?></h6>
						<div class="d-flex flex-column gap-2">
							<div class="d-flex align-items-center">
								<span class="badge bg-primary me-2">Primary</span>
								<small class="text-muted"><?php esc_html_e('Main product content', 'peaches'); ?></small>
							</div>
							<div class="d-flex align-items-center">
								<span class="badge bg-secondary me-2">Secondary</span>
								<small class="text-muted"><?php esc_html_e('Additional views', 'peaches'); ?></small>
							</div>
							<div class="d-flex align-items-center">
								<span class="badge bg-info me-2">Reference</span>
								<small class="text-muted"><?php esc_html_e('Charts, guides, specs', 'peaches'); ?></small>
							</div>
							<div class="d-flex align-items-center">
								<span class="badge bg-warning text-dark me-2">Media</span>
								<small class="text-muted"><?php esc_html_e('Rich content, demos', 'peaches'); ?></small>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render modal dialogs.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_modals() {
		?>
		<!-- Add Tag Modal -->
		<div class="modal fade" id="addTagModal" tabindex="-1" aria-labelledby="addTagModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="addTagModalLabel">
							<i class="dashicons dashicons-plus-alt2"></i>
							<?php esc_html_e('Add New Media Tag', 'peaches'); ?>
						</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<form id="add-media-tag-form">
						<div class="modal-body">
							<?php wp_nonce_field('add_media_tag', 'media_tag_nonce'); ?>

							<div class="mb-3">
								<label for="tag_label" class="form-label"><?php esc_html_e('Display Label', 'peaches'); ?> <span class="text-danger">*</span></label>
								<input type="text"
									   class="form-control"
									   id="tag_label"
									   name="tag_label"
									   placeholder="Hero Image"
									   required>
								<div class="form-text"><?php esc_html_e('Human-readable name shown in the admin interface.', 'peaches'); ?></div>
							</div>

							<div class="mb-3">
								<label for="tag_key" class="form-label"><?php esc_html_e('Tag Key', 'peaches'); ?> <span class="text-danger">*</span></label>
								<input type="text"
									   class="form-control"
									   id="tag_key"
									   name="tag_key"
									   pattern="[a-z0-9_]+"
									   placeholder="hero_image"
									   required>
								<div class="form-text"><?php esc_html_e('Auto-generated from label. Lowercase letters, numbers, and underscores only.', 'peaches'); ?></div>
								<div class="invalid-feedback"></div>
							</div>

							<div class="mb-3">
								<label for="tag_expected_media_type" class="form-label"><?php esc_html_e('Expected Media Type', 'peaches'); ?> <span class="text-danger">*</span></label>
								<select class="form-select" id="tag_expected_media_type" name="tag_expected_media_type" required>
									<option value=""><?php esc_html_e('Select media type...', 'peaches'); ?></option>
									<?php foreach (self::MEDIA_TYPES as $type_key => $type_label): ?>
										<option value="<?php echo esc_attr($type_key); ?>"><?php echo esc_html($type_label); ?></option>
									<?php endforeach; ?>
								</select>
								<div class="form-text"><?php esc_html_e('What type of media should be used with this tag.', 'peaches'); ?></div>
							</div>

							<div class="mb-3">
								<label for="tag_description" class="form-label"><?php esc_html_e('Description', 'peaches'); ?></label>
								<textarea class="form-control"
										  id="tag_description"
										  name="tag_description"
										  rows="3"
										  placeholder="<?php esc_attr_e('Brief description of when to use this tag...', 'peaches'); ?>"></textarea>
								<div class="form-text"><?php esc_html_e('Optional description to help users understand when to use this tag.', 'peaches'); ?></div>
							</div>

							<div class="mb-3">
								<label for="tag_category" class="form-label"><?php esc_html_e('Category', 'peaches'); ?></label>
								<select class="form-select" id="tag_category" name="tag_category">
									<option value="primary"><?php esc_html_e('Primary - Main product content', 'peaches'); ?></option>
									<option value="secondary"><?php esc_html_e('Secondary - Additional product views', 'peaches'); ?></option>
									<option value="reference"><?php esc_html_e('Reference - Charts, guides, specifications', 'peaches'); ?></option>
									<option value="media"><?php esc_html_e('Media - Rich content and demonstrations', 'peaches'); ?></option>
								</select>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
								<?php esc_html_e('Cancel', 'peaches'); ?>
							</button>
							<button type="submit" class="btn btn-primary">
								<i class="dashicons dashicons-plus-alt2"></i>
								<?php esc_html_e('Add Tag', 'peaches'); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Edit Tag Modal -->
		<div class="modal fade" id="editTagModal" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="editTagModalLabel">
							<i class="dashicons dashicons-edit"></i>
							<?php esc_html_e('Edit Media Tag', 'peaches'); ?>
						</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<form id="edit-media-tag-form">
						<div class="modal-body">
							<?php wp_nonce_field('edit_media_tag', 'edit_media_tag_nonce'); ?>
							<input type="hidden" id="edit_tag_key" name="edit_tag_key" value="">

							<div class="mb-3">
								<label for="edit_tag_label" class="form-label"><?php esc_html_e('Display Label', 'peaches'); ?> <span class="text-danger">*</span></label>
								<input type="text"
									   class="form-control"
									   id="edit_tag_label"
									   name="edit_tag_label"
									   required>
							</div>

							<div class="mb-3">
								<label for="edit_tag_expected_media_type" class="form-label"><?php esc_html_e('Expected Media Type', 'peaches'); ?> <span class="text-danger">*</span></label>
								<select class="form-select" id="edit_tag_expected_media_type" name="edit_tag_expected_media_type" required>
									<?php foreach (self::MEDIA_TYPES as $type_key => $type_label): ?>
										<option value="<?php echo esc_attr($type_key); ?>"><?php echo esc_html($type_label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="mb-3">
								<label for="edit_tag_description" class="form-label"><?php esc_html_e('Description', 'peaches'); ?></label>
								<textarea class="form-control"
										  id="edit_tag_description"
										  name="edit_tag_description"
										  rows="3"></textarea>
							</div>

							<div class="mb-3">
								<label for="edit_tag_category" class="form-label"><?php esc_html_e('Category', 'peaches'); ?></label>
								<select class="form-select" id="edit_tag_category" name="edit_tag_category">
									<option value="primary"><?php esc_html_e('Primary - Main product content', 'peaches'); ?></option>
									<option value="secondary"><?php esc_html_e('Secondary - Additional product views', 'peaches'); ?></option>
									<option value="reference"><?php esc_html_e('Reference - Charts, guides, specifications', 'peaches'); ?></option>
									<option value="media"><?php esc_html_e('Media - Rich content and demonstrations', 'peaches'); ?></option>
								</select>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
								<?php esc_html_e('Cancel', 'peaches'); ?>
							</button>
							<button type="submit" class="btn btn-primary">
								<i class="dashicons dashicons-saved"></i>
								<?php esc_html_e('Update Tag', 'peaches'); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Add media tag.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function ajax_add_media_tag() {
		try {
			check_ajax_referer('media_tags_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				wp_send_json_error(__('Insufficient permissions.', 'peaches'));
			}

			$tag_key = sanitize_key($_POST['tag_key']);
			$tag_label = sanitize_text_field($_POST['tag_label']);
			$tag_description = sanitize_textarea_field($_POST['tag_description']);
			$tag_category = sanitize_text_field($_POST['tag_category']);
			$tag_expected_media_type = sanitize_text_field($_POST['tag_expected_media_type']);

			$result = $this->add_tag($tag_key, $tag_label, $tag_description, $tag_category, $tag_expected_media_type);

			if (is_wp_error($result)) {
				wp_send_json_error($result->get_error_message());
			}

			wp_send_json_success(array(
				'message' => __('Tag added successfully.', 'peaches'),
				'tag_key' => $tag_key,
				'tag_data' => $this->get_tag($tag_key)
			));
		} catch (Exception $e) {
			$this->log_error('AJAX add media tag error', array(
				'error' => $e->getMessage()
			));
			wp_send_json_error(__('An error occurred while adding the tag.', 'peaches'));
		}
	}

	/**
	 * AJAX: Update media tag.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function ajax_update_media_tag() {
		try {
			check_ajax_referer('media_tags_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				wp_send_json_error(__('Insufficient permissions.', 'peaches'));
			}

			$tag_key = sanitize_key($_POST['tag_key']);
			$tag_label = sanitize_text_field($_POST['tag_label']);
			$tag_description = sanitize_textarea_field($_POST['tag_description']);
			$tag_category = sanitize_text_field($_POST['tag_category']);
			$tag_expected_media_type = sanitize_text_field($_POST['tag_expected_media_type']);

			$result = $this->update_tag($tag_key, $tag_label, $tag_description, $tag_category, $tag_expected_media_type);

			if (is_wp_error($result)) {
				wp_send_json_error($result->get_error_message());
			}

			wp_send_json_success(array(
				'message' => __('Tag updated successfully.', 'peaches'),
				'tag_data' => $this->get_tag($tag_key)
			));
		} catch (Exception $e) {
			$this->log_error('AJAX update media tag error', array(
				'error' => $e->getMessage()
			));
			wp_send_json_error(__('An error occurred while updating the tag.', 'peaches'));
		}
	}

	/**
	 * AJAX: Delete media tag.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function ajax_delete_media_tag() {
		try {
			check_ajax_referer('media_tags_nonce', 'nonce');

			if (!current_user_can('manage_options')) {
				wp_send_json_error(__('Insufficient permissions.', 'peaches'));
			}

			$tag_key = sanitize_key($_POST['tag_key']);
			$result = $this->delete_tag($tag_key);

			if (is_wp_error($result)) {
				wp_send_json_error($result->get_error_message());
			}

			wp_send_json_success(array(
				'message' => __('Tag deleted successfully.', 'peaches')
			));
		} catch (Exception $e) {
			$this->log_error('AJAX delete media tag error', array(
				'error' => $e->getMessage()
			));
			wp_send_json_error(__('An error occurred while deleting the tag.', 'peaches'));
		}
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
			Peaches_Ecwid_Utilities::log_error('[INFO] [Media Tags Manager] ' . $message, $context);
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
		Peaches_Ecwid_Utilities::log_error('[Media Tags Manager] ' . $message, $context);
	}
}
