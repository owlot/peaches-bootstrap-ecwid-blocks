<?php
/**
 * Product Media Manager class
 *
 * Handles media management for product settings including WordPress uploads,
 * external URLs, and Ecwid media selection.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.1
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Product_Media_Manager
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.1
 */
class Peaches_Product_Media_Manager {

	/**
	 * Ecwid API instance.
	 *
	 * @var Peaches_Ecwid_API_Interface
	 */
	private $ecwid_api;

	/**
	 * Media Tags Manager instance.
	 *
	 * @var Peaches_Media_Tags_Manager
	 */
	private $media_tags_manager;

	/**
	 * Constructor.
	 *
	 * @param Peaches_Ecwid_API_Interface $ecwid_api
	 * @param Peaches_Media_Tags_Manager  $media_tags_manager
	 */
	public function __construct($ecwid_api, $media_tags_manager) {
		$this->ecwid_api = $ecwid_api;
		$this->media_tags_manager = $media_tags_manager;
	}

	/**
	 * Render media tag item with multiple input modes.
	 *
	 * @param string $tag_key       Tag key
	 * @param array  $tag_data      Tag data
	 * @param mixed  $current_media Current media data
	 * @param int    $post_id       Current post ID for Ecwid fallback
	 */
	public function render_media_tag_item($tag_key, $tag_data, $current_media = null, $post_id = 0) {
		// Determine current media type and values
		$media_type = 'none';
		$attachment_id = '';
		$media_url = '';
		$ecwid_position = '';
		$has_media = false;
		$fallback_used = false;

		if (is_array($current_media)) {
			if (!empty($current_media['attachment_id'])) {
				$media_type = 'upload';
				$attachment_id = $current_media['attachment_id'];
				$has_media = true;
			} elseif (!empty($current_media['media_url'])) {
				$media_type = 'url';
				$media_url = $current_media['media_url'];
				$has_media = true;
			} elseif (!empty($current_media['ecwid_position'])) {
				$media_type = 'ecwid';
				$ecwid_position = $current_media['ecwid_position'];
				$has_media = true;
			}
		} elseif (!empty($current_media)) {
			// Legacy format - attachment ID only
			$media_type = 'upload';
			$attachment_id = $current_media;
			$has_media = true;
		}

		// Check for hero image fallback from Ecwid
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
			<div class="text-center mb-3 d-flex justify-content-center">
				<div class="media-preview bg-light rounded position-relative" style="flex: 0 0 150px; text-align: center;">
					<?php if ($has_media): ?>
						<?php $this->render_media_preview($media_type, $attachment_id, $media_url, $ecwid_position, $post_id); ?>
					<?php elseif ($fallback_used): ?>
						<img src="<?php echo esc_url($fallback_image_url); ?>" class="img-fluid rounded" alt="<?php _e('Fallback from Ecwid', 'peaches'); ?>">
						<div class="position-absolute bottom-0 end-0 p-1">
							<small class="badge bg-info"><?php _e('Fallback Ecwid', 'peaches'); ?></small>
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

				<!-- Media Type Selection -->
				<div class="media-type-selection mb-3">
					<div class="btn-group-vertical w-100" role="group" aria-label="<?php esc_attr_e('Media type selection', 'peaches'); ?>">
						<input type="radio" class="btn-check media-type-radio"
							   name="media_type_<?php echo esc_attr($tag_key); ?>"
							   id="upload_<?php echo esc_attr($tag_key); ?>"
							   value="upload"
							   <?php checked($media_type, 'upload'); ?>>
						<label class="btn btn-outline-secondary btn-sm" for="upload_<?php echo esc_attr($tag_key); ?>">
							<i class="dashicons dashicons-upload"></i>
							<?php _e('Upload File', 'peaches'); ?>
						</label>

						<input type="radio" class="btn-check media-type-radio"
							   name="media_type_<?php echo esc_attr($tag_key); ?>"
							   id="url_<?php echo esc_attr($tag_key); ?>"
							   value="url"
							   <?php checked($media_type, 'url'); ?>>
						<label class="btn btn-outline-secondary btn-sm" for="url_<?php echo esc_attr($tag_key); ?>">
							<i class="dashicons dashicons-admin-links"></i>
							<?php _e('External URL', 'peaches'); ?>
						</label>

						<input type="radio" class="btn-check media-type-radio"
							   name="media_type_<?php echo esc_attr($tag_key); ?>"
							   id="ecwid_<?php echo esc_attr($tag_key); ?>"
							   value="ecwid"
							   <?php checked($media_type, 'ecwid'); ?>>
						<label class="btn btn-outline-secondary btn-sm" for="ecwid_<?php echo esc_attr($tag_key); ?>">
							<i class="dashicons dashicons-store"></i>
							<?php _e('Ecwid Media', 'peaches'); ?>
						</label>
					</div>
				</div>

				<!-- Hidden inputs for form data -->
				<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][tag_name]" value="<?php echo esc_attr($tag_key); ?>">
				<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][media_type]" value="<?php echo esc_attr($media_type); ?>" class="media-type-value">
				<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][attachment_id]" value="<?php echo esc_attr($attachment_id); ?>" class="media-attachment-id">
				<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][media_url]" value="<?php echo esc_attr($media_url); ?>" class="media-url-value">
				<input type="hidden" name="product_media[<?php echo esc_attr($tag_key); ?>][ecwid_position]" value="<?php echo esc_attr($ecwid_position); ?>" class="media-ecwid-position">

				<!-- Upload File Controls -->
				<div class="media-upload-controls" style="display: <?php echo $media_type === 'upload' ? 'block' : 'none'; ?>;">
					<div class="btn-group-vertical w-100">
						<button type="button" class="btn btn-<?php echo $has_media && $media_type === 'upload' ? 'outline-secondary' : 'secondary'; ?> btn-sm select-media-button">
							<span class="dashicons dashicons-<?php echo $has_media && $media_type === 'upload' ? 'update' : 'plus-alt2'; ?>"></span>
							<?php echo $has_media && $media_type === 'upload' ? __('Change', 'peaches') : __('Select', 'peaches'); ?>
						</button>
						<?php if ($has_media && $media_type === 'upload'): ?>
							<button type="button" class="btn btn-outline-danger btn-sm remove-media-button">
								<span class="dashicons dashicons-trash"></span>
								<?php _e('Remove', 'peaches'); ?>
							</button>
						<?php endif; ?>
					</div>
				</div>

				<!-- URL Input Controls -->
				<div class="media-url-controls" style="display: <?php echo $media_type === 'url' ? 'block' : 'none'; ?>;">
					<div class="input-group input-group-sm mb-2">
						<input type="url" class="form-control media-url-input"
							   placeholder="<?php esc_attr_e('https://example.com/image.jpg', 'peaches'); ?>"
							   value="<?php echo esc_attr($media_url); ?>">
						<button type="button" class="btn btn-outline-secondary preview-url-button" title="<?php esc_attr_e('Preview URL', 'peaches'); ?>">
							<i class="dashicons dashicons-visibility"></i>
						</button>
					</div>
					<?php if ($has_media && $media_type === 'url'): ?>
						<button type="button" class="btn btn-outline-danger btn-sm w-100 clear-url-button">
							<span class="dashicons dashicons-trash"></span>
							<?php _e('Clear URL', 'peaches'); ?>
						</button>
					<?php endif; ?>
				</div>

				<!-- Ecwid Media Controls -->
				<div class="media-ecwid-controls" style="display: <?php echo $media_type === 'ecwid' ? 'block' : 'none'; ?>;">
					<div class="mb-2">
						<select class="form-select form-select-sm ecwid-position-select">
							<option value=""><?php _e('Select image position...', 'peaches'); ?></option>
							<option value="0" <?php selected($ecwid_position, '0'); ?>><?php _e('Main image (position 1)', 'peaches'); ?></option>
						</select>
					</div>
					<button type="button" class="btn btn-outline-primary btn-sm w-100 load-ecwid-media-button">
						<span class="dashicons dashicons-update"></span>
						<?php _e('Load Product Images', 'peaches'); ?>
					</button>
					<?php if ($has_media && $media_type === 'ecwid'): ?>
						<button type="button" class="btn btn-outline-danger btn-sm w-100 mt-1 clear-ecwid-button">
							<span class="dashicons dashicons-trash"></span>
							<?php _e('Clear Selection', 'peaches'); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render media preview based on type.
	 *
	 * @param string $media_type     Media type (upload, url, ecwid)
	 * @param string $attachment_id  WordPress attachment ID
	 * @param string $media_url      External media URL
	 * @param string $ecwid_position Ecwid image position
	 * @param int    $post_id        Post ID for Ecwid data
	 */
	private function render_media_preview($media_type, $attachment_id, $media_url, $ecwid_position, $post_id) {
		switch ($media_type) {
			case 'upload':
				if ($attachment_id) {
					echo wp_get_attachment_image($attachment_id, 'thumbnail', false, array(
						'class' => 'img-fluid rounded',
					));
					echo '<div class="position-absolute top-0 start-0 p-1">';
					echo '<small class="badge bg-success">' . __('WP Media', 'peaches') . '</small>';
					echo '</div>';
				}
				break;

			case 'url':
				if ($media_url) {
					// Check if it's a video URL
					if ($this->is_video_url($media_url)) {
						echo '<div class="video-preview-placeholder bg-dark text-white d-flex align-items-center justify-content-center rounded" style="width: 100px; height: 100px;">';
						echo '<i class="fas fa-play fa-2x"></i>';
						echo '</div>';
					} else {
						echo '<img src="' . esc_url($media_url) . '" class="img-fluid rounded" alt="' . esc_attr__('External media', 'peaches') . '" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
						echo '<div class="error-placeholder bg-warning text-dark d-flex align-items-center justify-content-center rounded" style="width: 100px; height: 100px; display: none;">';
						echo '<i class="fas fa-exclamation-triangle"></i>';
						echo '</div>';
					}
					echo '<div class="position-absolute top-0 start-0 p-1">';
					echo '<small class="badge bg-info">' . __('External', 'peaches') . '</small>';
					echo '</div>';
				}
				break;

			case 'ecwid':
				if ($ecwid_position !== '' && $post_id) {
					$ecwid_product_id = get_post_meta($post_id, '_ecwid_product_id', true);
					if ($ecwid_product_id) {
						$product = $this->ecwid_api->get_product_by_id($ecwid_product_id);
						if ($product) {
							$image_url = $this->get_ecwid_image_by_position($product, $ecwid_position);
							if ($image_url) {
								echo '<img src="' . esc_url($image_url) . '" class="img-fluid rounded" alt="' . esc_attr__('Ecwid media', 'peaches') . '">';
								echo '<div class="position-absolute top-0 start-0 p-1">';
								echo '<small class="badge bg-warning text-dark">' . __('Ecwid', 'peaches') . '</small>';
								echo '</div>';
							}
						}
					}
				}
				break;
		}
	}

	/**
	 * Check if URL is a video URL.
	 *
	 * @param string $url URL to check
	 *
	 * @return bool True if video URL
	 */
	private function is_video_url($url) {
		$video_patterns = array(
			'/youtube\.com\/watch\?v=/',
			'/youtu\.be\//',
			'/vimeo\.com\//',
			'/wistia\.com\//',
			'/\.mp4$/i',
			'/\.webm$/i',
			'/\.ogg$/i',
			'/\.mov$/i'
		);

		foreach ($video_patterns as $pattern) {
			if (preg_match($pattern, $url)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get Ecwid image by position.
	 *
	 * @param object $product  Ecwid product object
	 * @param string $position Image position
	 *
	 * @return string|null Image URL or null
	 */
	private function get_ecwid_image_by_position($product, $position) {
		$position = intval($position);

		if ($position === 0 && !empty($product->thumbnailUrl)) {
			return $product->thumbnailUrl;
		}

		if (!empty($product->galleryImages) && is_array($product->galleryImages)) {
			// Position 1+ refers to gallery images (0-indexed)
			$gallery_index = $position - 1;
			if (isset($product->galleryImages[$gallery_index])) {
				return $product->galleryImages[$gallery_index]->url;
			}
		}

		return null;
	}

	/**
	 * Save product media data.
	 *
	 * @param int   $post_id     Post ID
	 * @param array $media_data  Media data from form
	 */
	public function save_product_media($post_id, $media_data) {
		$product_media = array();

		foreach ($media_data as $tag_key => $media_item) {
			if (empty($media_item['tag_name']) || empty($media_item['media_type'])) {
				continue;
			}

			$media_type = sanitize_text_field($media_item['media_type']);
			$tag_name = sanitize_text_field($media_item['tag_name']);

			$media_entry = array(
				'tag_name' => $tag_name,
				'media_type' => $media_type
			);

			switch ($media_type) {
				case 'upload':
					if (!empty($media_item['attachment_id'])) {
						$attachment_id = absint($media_item['attachment_id']);
						$media_entry['attachment_id'] = $attachment_id;

						// Mark attachment as product media
						update_post_meta($attachment_id, '_peaches_product_media', true);
						update_post_meta($attachment_id, '_peaches_product_media_tag', $tag_name);
					}
					break;

				case 'url':
					if (!empty($media_item['media_url'])) {
						$media_url = esc_url_raw($media_item['media_url']);
						if ($media_url) {
							$media_entry['media_url'] = $media_url;
						}
					}
					break;

				case 'ecwid':
					if (isset($media_item['ecwid_position']) && $media_item['ecwid_position'] !== '') {
						$ecwid_position = absint($media_item['ecwid_position']);
						$media_entry['ecwid_position'] = $ecwid_position;
					}
					break;
			}

			// Only add if we have valid media data
			if (count($media_entry) > 2) { // More than just tag_name and media_type
				$product_media[] = $media_entry;
			}
		}

		update_post_meta($post_id, '_product_media', $product_media);
	}

	/**
	 * Get product media by tag with enhanced data.
	 *
	 * @param int    $post_id Post ID
	 * @param string $tag_key Media tag key
	 *
	 * @return array|null Media data or null if not found
	 */
	public function get_product_media_by_tag($post_id, $tag_key) {
		$product_media = get_post_meta($post_id, '_product_media', true);

		if (!is_array($product_media)) {
			return null;
		}

		foreach ($product_media as $media_item) {
			if (isset($media_item['tag_name']) && $media_item['tag_name'] === $tag_key) {
				return $media_item;
			}
		}

		return null;
	}
}
