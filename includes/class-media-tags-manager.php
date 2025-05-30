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

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Media_Tags_Manager
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Media_Tags_Manager {

	/**
	 * Option name for storing media tags
	 */
	const TAGS_OPTION = 'peaches_ecwid_media_tags';

	/**
	 * Available media types
	 */
	const MEDIA_TYPES = array(
		'image' => 'Image',
		'video' => 'Video',
		'audio' => 'Audio',
		'document' => 'Document'
	);

	/**
	 * Default media tags with expected media types
	 */
	const DEFAULT_TAGS = array(
		'hero_image' => array(
			'label' => 'Hero Image',
			'description' => 'Main featured image for the product',
			'category' => 'primary',
			'expected_media_type' => 'image'
		),
		'size_chart' => array(
			'label' => 'Size Chart',
			'description' => 'Product sizing information',
			'category' => 'reference',
			'expected_media_type' => 'image'
		),
		'product_video' => array(
			'label' => 'Product Video',
			'description' => 'Video demonstrating the product',
			'category' => 'media',
			'expected_media_type' => 'video'
		),
		'ingredients_image' => array(
			'label' => 'Ingredients Image',
			'description' => 'Visual ingredients list or nutrition facts',
			'category' => 'reference',
			'expected_media_type' => 'image'
		),
		'packaging_image' => array(
			'label' => 'Packaging Image',
			'description' => 'Product packaging or box image',
			'category' => 'secondary',
			'expected_media_type' => 'image'
		),
		'product_audio' => array(
			'label' => 'Product Audio',
			'description' => 'Audio content related to the product',
			'category' => 'media',
			'expected_media_type' => 'audio'
		),
		'instruction_manual' => array(
			'label' => 'Instruction Manual',
			'description' => 'Product instruction manual or guide',
			'category' => 'reference',
			'expected_media_type' => 'document'
		)
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		$this->maybe_initialize_default_tags();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action('admin_init', array($this, 'handle_form_submission'));
		add_action('wp_ajax_add_media_tag', array($this, 'ajax_add_media_tag'));
		add_action('wp_ajax_delete_media_tag', array($this, 'ajax_delete_media_tag'));
		add_action('wp_ajax_update_media_tag', array($this, 'ajax_update_media_tag'));
	}

	/**
	 * Maybe initialize default tags on first run.
	 */
	private function maybe_initialize_default_tags() {
		$existing_tags = get_option(self::TAGS_OPTION, array());

		if (empty($existing_tags)) {
			update_option(self::TAGS_OPTION, self::DEFAULT_TAGS);
		} else {
			// Update existing tags with missing expected_media_type field
			$updated = false;
			foreach ($existing_tags as $key => $tag_data) {
				if (!isset($tag_data['expected_media_type'])) {
					// Set default based on tag content or fallback to image
					$existing_tags[$key]['expected_media_type'] = $this->guess_media_type_from_tag($key, $tag_data);
					$updated = true;
				}
			}

			if ($updated) {
				update_option(self::TAGS_OPTION, $existing_tags);
			}
		}
	}

	/**
	 * Guess media type from existing tag data
	 *
	 * @param string $tag_key  Tag key
	 * @param array  $tag_data Tag data
	 *
	 * @return string Media type
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
	 * Get available media types
	 *
	 * @return array Media types array
	 */
	public function get_media_types() {
		return self::MEDIA_TYPES;
	}

	/**
	 * Enqueue scripts specifically for the tab context
	 *
	 * @since 0.2.0
	 *
	 * @param string $hook Current admin page hook
	 *
	 * @return void
	 */
	public function enqueue_tab_scripts($hook) {
		// Custom script only - no custom CSS needed with Bootstrap
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
	}

	/**
	 * Render admin page content for tab integration
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_admin_page_content() {
		$tags = $this->get_all_tags();
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

		<?php $this->render_notices(); ?>

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

				<!-- Media Types Reference Card -->
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
										<small class="text-muted"><?php esc_html_e('Photo\'s, graphics, charts', 'peaches'); ?></small>
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
			</div>
		</div>

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
	 * Render a tag row.
	 *
	 * @param string $tag_key  Tag key
	 * @param array  $tag_data Tag data
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
		$media_type_badge = isset($media_type_badges[$tag_data['expected_media_type']]) ? $media_type_badges[$tag_data['expected_media_type']] : 'bg-secondary';
		$is_default = array_key_exists($tag_key, self::DEFAULT_TAGS);
		$media_type_label = isset(self::MEDIA_TYPES[$tag_data['expected_media_type']]) ? self::MEDIA_TYPES[$tag_data['expected_media_type']] : 'Unknown';
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
							data-tag-expected-media-type="<?php echo esc_attr($tag_data['expected_media_type']); ?>"
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
	 * Render notices.
	 */
	private function render_notices() {
		if (isset($_GET['message'])) {
			$message = sanitize_text_field($_GET['message']);
			$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';

			$alert_class = $type === 'error' ? 'alert-danger' : 'alert-success';
			?>
			<div class="alert <?php echo esc_attr($alert_class); ?> alert-dismissible fade show">
				<?php echo esc_html(urldecode($message)); ?>
				<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
			</div>
			<?php
		}
	}

	/**
	 * Handle form submission.
	 */
	public function handle_form_submission() {
		// This will be handled via AJAX, but keeping for fallback
	}

	/**
	 * AJAX: Add media tag.
	 */
	public function ajax_add_media_tag() {
		check_ajax_referer('media_tags_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(__('Insufficient permissions.', 'peaches'));
		}

		$tag_key = sanitize_key($_POST['tag_key']);
		$tag_label = sanitize_text_field($_POST['tag_label']);
		$tag_description = sanitize_textarea_field($_POST['tag_description']);
		$tag_category = sanitize_text_field($_POST['tag_category']);
		$tag_expected_media_type = sanitize_text_field($_POST['tag_expected_media_type']);

		if (empty($tag_key) || empty($tag_label) || empty($tag_expected_media_type)) {
			wp_send_json_error(__('Tag key, label, and expected media type are required.', 'peaches'));
		}

		// Validate tag key format
		if (!preg_match('/^[a-z0-9_]+$/', $tag_key)) {
			wp_send_json_error(__('Tag key can only contain lowercase letters, numbers, and underscores.', 'peaches'));
		}

		// Validate expected media type
		if (!array_key_exists($tag_expected_media_type, self::MEDIA_TYPES)) {
			wp_send_json_error(__('Invalid media type selected.', 'peaches'));
		}

		$tags = $this->get_all_tags();

		if (isset($tags[$tag_key])) {
			wp_send_json_error(__('A tag with this key already exists.', 'peaches'));
		}

		$tags[$tag_key] = array(
			'label' => $tag_label,
			'description' => $tag_description,
			'category' => $tag_category,
			'expected_media_type' => $tag_expected_media_type
		);

		update_option(self::TAGS_OPTION, $tags);

		wp_send_json_success(array(
			'message' => __('Tag added successfully.', 'peaches'),
			'tag_key' => $tag_key,
			'tag_data' => $tags[$tag_key]
		));
	}

	/**
	 * AJAX: Delete media tag.
	 */
	public function ajax_delete_media_tag() {
		check_ajax_referer('media_tags_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(__('Insufficient permissions.', 'peaches'));
		}

		$tag_key = sanitize_key($_POST['tag_key']);

		if (empty($tag_key)) {
			wp_send_json_error(__('Tag key is required.', 'peaches'));
		}

		// Prevent deletion of default tags
		if (array_key_exists($tag_key, self::DEFAULT_TAGS)) {
			wp_send_json_error(__('Default tags cannot be deleted.', 'peaches'));
		}

		$tags = $this->get_all_tags();

		if (!isset($tags[$tag_key])) {
			wp_send_json_error(__('Tag not found.', 'peaches'));
		}

		unset($tags[$tag_key]);
		update_option(self::TAGS_OPTION, $tags);

		wp_send_json_success(array(
			'message' => __('Tag deleted successfully.', 'peaches')
		));
	}

	/**
	 * AJAX: Update media tag.
	 */
	public function ajax_update_media_tag() {
		check_ajax_referer('media_tags_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(__('Insufficient permissions.', 'peaches'));
		}

		$tag_key = sanitize_key($_POST['tag_key']);
		$tag_label = sanitize_text_field($_POST['tag_label']);
		$tag_description = sanitize_textarea_field($_POST['tag_description']);
		$tag_category = sanitize_text_field($_POST['tag_category']);
		$tag_expected_media_type = sanitize_text_field($_POST['tag_expected_media_type']);

		if (empty($tag_key) || empty($tag_label) || empty($tag_expected_media_type)) {
			wp_send_json_error(__('Tag key, label, and expected media type are required.', 'peaches'));
		}

		// Validate expected media type
		if (!array_key_exists($tag_expected_media_type, self::MEDIA_TYPES)) {
			wp_send_json_error(__('Invalid media type selected.', 'peaches'));
		}

		$tags = $this->get_all_tags();

		if (!isset($tags[$tag_key])) {
			wp_send_json_error(__('Tag not found.', 'peaches'));
		}

		$tags[$tag_key] = array(
			'label' => $tag_label,
			'description' => $tag_description,
			'category' => $tag_category,
			'expected_media_type' => $tag_expected_media_type
		);

		update_option(self::TAGS_OPTION, $tags);

		wp_send_json_success(array(
			'message' => __('Tag updated successfully.', 'peaches'),
			'tag_data' => $tags[$tag_key]
		));
	}

	/**
	 * Get all media tags.
	 *
	 * @return array Array of media tags
	 */
	public function get_all_tags() {
		return get_option(self::TAGS_OPTION, array());
	}

	/**
	 * Get tags by category.
	 *
	 * @param string $category Category to filter by
	 *
	 * @return array Array of filtered tags
	 */
	public function get_tags_by_category($category) {
		$all_tags = $this->get_all_tags();
		return array_filter($all_tags, function($tag_data) use ($category) {
			return isset($tag_data['category']) && $tag_data['category'] === $category;
		});
	}

	/**
	 * Get tags by expected media type.
	 *
	 * @param string $media_type Media type to filter by
	 *
	 * @return array Array of filtered tags
	 */
	public function get_tags_by_media_type($media_type) {
		$all_tags = $this->get_all_tags();
		return array_filter($all_tags, function($tag_data) use ($media_type) {
			return isset($tag_data['expected_media_type']) && $tag_data['expected_media_type'] === $media_type;
		});
	}

	/**
	 * Get tag data by key.
	 *
	 * @param string $tag_key Tag key
	 *
	 * @return array|null Tag data or null if not found
	 */
	public function get_tag($tag_key) {
		$tags = $this->get_all_tags();
		return isset($tags[$tag_key]) ? $tags[$tag_key] : null;
	}

	/**
	 * Get expected media type for a tag.
	 *
	 * @param string $tag_key Tag key
	 *
	 * @return string|null Expected media type or null if not found
	 */
	public function get_tag_expected_media_type($tag_key) {
		$tag_data = $this->get_tag($tag_key);
		return $tag_data ? $tag_data['expected_media_type'] : null;
	}

	/**
	 * Check if tag exists.
	 *
	 * @param string $tag_key Tag key
	 *
	 * @return bool True if tag exists
	 */
	public function tag_exists($tag_key) {
		$tags = $this->get_all_tags();
		return isset($tags[$tag_key]);
	}

	/**
	 * Validate media type against tag expectations.
	 *
	 * @param string $tag_key    Tag key
	 * @param string $media_url  Media URL to check
	 * @param string $mime_type  Optional mime type
	 *
	 * @return array Validation result with 'valid' boolean and 'message'
	 */
	public function validate_media_for_tag($tag_key, $media_url, $mime_type = '') {
		$expected_type = $this->get_tag_expected_media_type($tag_key);

		if (!$expected_type) {
			return array(
				'valid' => false,
				'message' => __('Tag not found.', 'peaches')
			);
		}

		$actual_type = $this->determine_media_type_from_url($media_url, $mime_type);

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
	 * Determine media type from URL and mime type.
	 *
	 * @param string $url       Media URL
	 * @param string $mime_type Optional mime type
	 *
	 * @return string Media type (image, video, audio, document)
	 */
	private function determine_media_type_from_url($url, $mime_type = '') {
		// Check mime type first if provided
		if ($mime_type) {
			if (strpos($mime_type, 'image/') === 0) {
				return 'image';
			}
			if (strpos($mime_type, 'video/') === 0) {
				return 'video';
			}
			if (strpos($mime_type, 'audio/') === 0) {
				return 'audio';
			}
			if (strpos($mime_type, 'application/pdf') === 0 || strpos($mime_type, 'text/') === 0) {
				return 'document';
			}
		}

		if (!$url) {
			return 'image'; // Default fallback
		}

		// Parse URL to get file extension
		$parsed_url = parse_url($url);
		$pathname = isset($parsed_url['path']) ? $parsed_url['path'] : $url;
		$extension = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));

		// Image extensions
		$image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff');
		if (in_array($extension, $image_extensions)) {
			return 'image';
		}

		// Video extensions
		$video_extensions = array('mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'm4v', '3gp', 'mkv');
		if (in_array($extension, $video_extensions)) {
			return 'video';
		}

		// Audio extensions
		$audio_extensions = array('mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma');
		if (in_array($extension, $audio_extensions)) {
			return 'audio';
		}

		// Document extensions
		$document_extensions = array('pdf', 'doc', 'docx', 'txt', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx');
		if (in_array($extension, $document_extensions)) {
			return 'document';
		}

		// Check for video hosting patterns
		if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false ||
			strpos($url, 'vimeo.com') !== false || strpos($url, 'wistia.com') !== false) {
			return 'video';
		}

		// Default to image
		return 'image';
	}
}
