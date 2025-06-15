<?php
/**
 * Peaches Ecwid Product Settings Manager
 *
 * Handles the settings page for Ecwid products with consistent card-based styling
 * across all tabs, matching the media tags design pattern.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Peaches_Ecwid_Product_Settings
 *
 * Manages settings for Ecwid products with tabbed interface
 * and integration with the shared Peaches menu system.
 *
 * @since 0.2.0
 */
class Peaches_Ecwid_Product_Settings {

	/**
	 * Settings page slug
	 *
	 * @since 0.2.0
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'peaches-ecwid-product-settings';

	/**
	 * Peaches main menu slug
	 *
	 * @since 0.2.0
	 *
	 * @var string
	 */
	const MAIN_MENU_SLUG = 'peaches-settings';

	/**
	 * Instance of this class
	 *
	 * @since 0.2.0
	 *
	 * @var Peaches_Ecwid_Product_Settings|null
	 */
	private static $instance = null;

	/**
	 * Media Tags Manager instance
	 *
	 * @since 0.2.0
	 *
	 * @var Peaches_Media_Tags_Manager|null
	 */
	private $media_tags_manager = null;

	/**
	 * Get singleton instance
	 *
	 * @since 0.2.0
	 *
	 * @return Peaches_Ecwid_Product_Settings The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * Initializes the settings manager with admin hooks and menu integration.
	 *
	 * @since 0.2.0
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Initialize Media Tags Manager for tab integration
		if ( class_exists( 'Peaches_Media_Tags_Manager' ) ) {
			$this->media_tags_manager = new Peaches_Media_Tags_Manager();
		}
	}

	/**
	 * Add admin menu items
	 *
	 * Creates the main Peaches menu if it doesn't exist, then adds the
	 * Ecwid product settings submenu with proper capability checks.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		// Add our submenu
		add_submenu_page(
			self::MAIN_MENU_SLUG,
			__( 'Ecwid Product Settings', 'peaches' ),
			__( 'Ecwid Products', 'peaches' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_products_page' )
		);
	}

	/**
	 * Render products page
	 *
	 * Displays the Ecwid product settings page with tabbed interface
	 * for managing different aspects of product configuration.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_products_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'product_settings';

		// Show success message if item was saved
		if (isset($_GET['saved'])) {
			$saved_post_id = absint($_GET['saved']);
			$saved_post = get_post($saved_post_id);

			if ($saved_post) {
				$post_type_labels = array(
					'product_settings' => __('Product Configuration', 'peaches'),
					'product_ingredient' => __('Product Ingredient', 'peaches'),
				);

				$label = isset($post_type_labels[$saved_post->post_type])
					? $post_type_labels[$saved_post->post_type]
					: __('Name', 'peaches');

				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<strong><?php echo esc_html($label); ?></strong>
						"<?php echo esc_html($saved_post->post_title); ?>"
						<?php _e('has been saved successfully!', 'peaches'); ?>
						<a href="<?php echo esc_url(get_edit_post_link($saved_post_id)); ?>" class="button button-small">
							<?php _e('Edit Again', 'peaches'); ?>
						</a>
					</p>
				</div>
				<?php
			}
		}

		// Get counts for tab badges
		$product_settings_count = wp_count_posts( 'product_settings' )->publish;
		$ingredients_library_count = wp_count_posts( 'product_ingredient' )->publish;
		$product_lines_count = wp_count_terms( array( 'taxonomy' => 'product_line', 'hide_empty' => false ) );

		// Get media tags count
		$media_tags_count = 0;
		if ( $this->media_tags_manager ) {
			$all_tags = $this->media_tags_manager->get_all_tags();
			$media_tags_count = count( $all_tags );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ecwid Product Management', 'peaches' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Manage product settings, ingredients, lines, media tags, and media for your Ecwid store.', 'peaches' ); ?></p>

			<!-- Bootstrap Nav Tabs -->
			<ul class="nav nav-tabs my-3" id="productManagementTabs" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link <?php echo $active_tab === 'product_settings' ? 'active' : ''; ?>"
							id="product-settings-tab"
							data-bs-toggle="tab"
							data-bs-target="#product-settings"
							type="button"
							role="tab"
							aria-controls="product-settings"
							aria-selected="<?php echo $active_tab === 'product_settings' ? 'true' : 'false'; ?>">
						<?php esc_html_e( 'Product Configuration', 'peaches' ); ?>
						<?php if ( $product_settings_count > 0 ): ?>
							<span class="badge bg-secondary ms-1"><?php echo esc_html( $product_settings_count ); ?></span>
						<?php endif; ?>
					</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link <?php echo $active_tab === 'ingredients_library' ? 'active' : ''; ?>"
							id="ingredients-library-tab"
							data-bs-toggle="tab"
							data-bs-target="#ingredients-library"
							type="button"
							role="tab"
							aria-controls="ingredients-library"
							aria-selected="<?php echo $active_tab === 'ingredients_library' ? 'true' : 'false'; ?>">
						<?php esc_html_e( 'Ingredients Library', 'peaches' ); ?>
						<?php if ( $ingredients_library_count > 0 ): ?>
							<span class="badge bg-secondary ms-1"><?php echo esc_html( $ingredients_library_count ); ?></span>
						<?php endif; ?>
					</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link <?php echo $active_tab === 'media_tags' ? 'active' : ''; ?>"
							id="media-tags-tab"
							data-bs-toggle="tab"
							data-bs-target="#media-tags"
							type="button"
							role="tab"
							aria-controls="media-tags"
							aria-selected="<?php echo $active_tab === 'media_tags' ? 'true' : 'false'; ?>">
						<?php esc_html_e( 'Media Tags', 'peaches' ); ?>
						<?php if ( $media_tags_count > 0 ): ?>
							<span class="badge bg-secondary ms-1"><?php echo esc_html( $media_tags_count ); ?></span>
						<?php endif; ?>
					</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link <?php echo $active_tab === 'product_lines' ? 'active' : ''; ?>"
							id="product-lines-tab"
							data-bs-toggle="tab"
							data-bs-target="#product-lines"
							type="button"
							role="tab"
							aria-controls="product-lines"
							aria-selected="<?php echo $active_tab === 'product_lines' ? 'true' : 'false'; ?>">
						<?php esc_html_e( 'Product Lines', 'peaches' ); ?>
						<?php if ( $product_lines_count > 0 ): ?>
							<span class="badge bg-secondary ms-1"><?php echo esc_html( $product_lines_count ); ?></span>
						<?php endif; ?>
					</button>
				</li>
			</ul>

			<!-- Bootstrap Tab Content -->
			<div class="tab-content" id="productManagementTabContent">
				<div class="tab-pane fade <?php echo $active_tab === 'product_settings' ? 'show active' : ''; ?>"
					 id="product-settings"
					 role="tabpanel"
					 aria-labelledby="product-settings-tab">
					<?php $this->render_product_settings_tab(); ?>
				</div>

				<div class="tab-pane fade <?php echo $active_tab === 'ingredients_library' ? 'show active' : ''; ?>"
					 id="ingredients-library"
					 role="tabpanel"
					 aria-labelledby="ingredients-library-tab">
					<?php $this->render_ingredients_library_tab(); ?>
				</div>

				<div class="tab-pane fade <?php echo $active_tab === 'media_tags' ? 'show active' : ''; ?>"
					 id="media-tags"
					 role="tabpanel"
					 aria-labelledby="media-tags-tab">
					<?php $this->render_media_tags_tab(); ?>
				</div>

				<div class="tab-pane fade <?php echo $active_tab === 'product_lines' ? 'show active' : ''; ?>"
					 id="product-lines"
					 role="tabpanel"
					 aria-labelledby="product-lines-tab">
					<?php $this->render_product_lines_tab(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Product Settings tab
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_product_settings_tab() {
		$product_settings_count = wp_count_posts( 'product_settings' )->publish;
		?>
		<div class="d-flex justify-content-between align-items-start mb-4">
			<div>
				<h3 class="mb-2"><?php esc_html_e( 'Product Configuration', 'peaches' ); ?></h3>
				<p class="text-muted"><?php esc_html_e( 'Configure individual products with ingredients, named media, product lines, and tags. Each product configuration links to a specific Ecwid product via ID or SKU.', 'peaches' ); ?></p>
			</div>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product_settings' ) ); ?>"
			   class="btn btn-primary text-nowrap">
				<i class="dashicons dashicons-plus-alt2"></i>
				<?php esc_html_e( 'Add New Product Configuration', 'peaches' ); ?>
			</a>
		</div>

		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="card-title mb-0"><?php esc_html_e( 'Product Configurations', 'peaches' ); ?></h5>
						<span class="badge bg-secondary"><?php echo $product_settings_count; ?> <?php esc_html_e( 'configurations', 'peaches' ); ?></span>
					</div>
					<div class="card-body">
						<?php $this->render_embedded_post_list( 'product_settings' ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Ingredients Library tab
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_ingredients_library_tab() {
		$ingredients_library_count = wp_count_posts( 'product_ingredient' )->publish;
		?>
		<div class="d-flex justify-content-between align-items-start mb-4">
			<div>
				<h3 class="mb-2"><?php esc_html_e( 'Ingredients Library', 'peaches' ); ?></h3>
				<p class="text-muted"><?php esc_html_e( 'Create and manage reusable ingredients that can be used across multiple products. Supports multilingual translations and rich descriptions.', 'peaches' ); ?></p>
			</div>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product_ingredient' ) ); ?>"
			   class="btn btn-primary text-nowrap">
				<i class="dashicons dashicons-plus-alt2"></i>
				<?php esc_html_e( 'Add New Product Ingredient', 'peaches' ); ?>
			</a>
		</div>

		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="card-title mb-0"><?php esc_html_e( 'Ingredients Library', 'peaches' ); ?></h5>
						<span class="badge bg-secondary"><?php echo $ingredients_library_count; ?> <?php esc_html_e( 'ingredients', 'peaches' ); ?></span>
					</div>
					<div class="card-body">
						<?php $this->render_embedded_post_list( 'product_ingredient' ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Media Tags tab
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_media_tags_tab() {
		if ( ! $this->media_tags_manager ) {
			?>
			<div class="alert alert-warning">
				<h4><?php esc_html_e( 'Media Tags Manager Not Available', 'peaches' ); ?></h4>
				<p><?php esc_html_e( 'The Media Tags Manager class is not loaded. Please check your plugin installation.', 'peaches' ); ?></p>
			</div>
			<?php
			return;
		}

		// Call the media tags manager's render method directly
		$this->media_tags_manager->render_admin_page_content();
	}

	/**
	 * Render Product Lines tab
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_product_lines_tab() {
		$product_lines_count = wp_count_terms( array( 'taxonomy' => 'product_line', 'hide_empty' => false ) );
		?>
		<div class="d-flex justify-content-between align-items-start mb-4">
			<div>
				<h3 class="mb-2"><?php esc_html_e( 'Product Lines', 'peaches' ); ?></h3>
				<p class="text-muted"><?php esc_html_e( 'Organize products into lines like fragrance collections, color schemes, or design series. Lines can have their own media, descriptions, and be targeted in Gutenberg blocks for dynamic content display.', 'peaches' ); ?></p>
			</div>
			<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_line' ) ); ?>"
			   class="btn btn-primary text-nowrap">
				<i class="dashicons dashicons-plus-alt2"></i>
				<?php esc_html_e( 'Add New Product Line', 'peaches' ); ?>
			</a>
		</div>

		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="card-title mb-0"><?php esc_html_e( 'Product Lines', 'peaches' ); ?></h5>
						<span class="badge bg-secondary"><?php echo $product_lines_count; ?> <?php esc_html_e( 'lines', 'peaches' ); ?></span>
					</div>
					<div class="card-body">
						<?php $this->render_embedded_taxonomy_list( 'product_line' ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render embedded post list table (matching media tags style)
	 *
	 * @since 0.2.0
	 *
	 * @param string $post_type Post type to display
	 *
	 * @return void
	 */
	private function render_embedded_post_list( $post_type ) {
		// Get the posts
		$args = array(
			'post_type' => $post_type,
			'posts_per_page' => 20,
			'post_status' => array( 'publish', 'draft', 'private' ),
			'orderby' => 'date',
			'order' => 'DESC'
		);

		$posts = get_posts( $args );
		$total_posts = wp_count_posts( $post_type );
		$total_count = $total_posts->publish + $total_posts->draft + $total_posts->private;

		if ( empty( $posts ) ) {
			?>
			<div class="text-center py-5">
				<i class="dashicons dashicons-inbox" style="font-size: 64px; color: #dee2e6; margin-bottom: 16px;"></i>
				<h4 class="text-muted"><?php esc_html_e( 'No items found', 'peaches' ); ?></h4>
				<p class="text-muted mb-4"><?php esc_html_e( 'Get started by creating your first item.', 'peaches' ); ?></p>
				<?php
				$post_type_object = get_post_type_object( $post_type );
				$add_new_url = admin_url( 'post-new.php?post_type=' . $post_type );
				?>
				<a href="<?php echo esc_url( $add_new_url ); ?>" class="btn btn-primary">
					<i class="dashicons dashicons-plus-alt2"></i>
					<?php printf( esc_html__( 'Create First %s', 'peaches' ), esc_html( $post_type_object->labels->singular_name ) ); ?>
				</a>
			</div>
			<?php
			return;
		}

		?>
		<div class="table-responsive">
			<table class="table table-hover align-middle">
				<thead class="table-light">
					<tr>
						<th scope="col"><?php esc_html_e( 'Name', 'peaches' ); ?></th>
						<?php if ( $post_type === 'product_settings' ): ?>
							<th scope="col" class="text-center"><?php esc_html_e( 'SKU', 'peaches' ); ?></th>
							<th scope="col" class="text-center"><?php esc_html_e( 'Ecwid ID', 'peaches' ); ?></th>
							<th scope="col" class="text-center"><?php esc_html_e( 'Components', 'peaches' ); ?></th>
						<?php elseif ( $post_type === 'product_ingredient' ): ?>
							<th scope="col"><?php esc_html_e( 'Description', 'peaches' ); ?></th>
						<?php endif; ?>
						<th scope="col" class="text-center"><?php esc_html_e( 'Date', 'peaches' ); ?></th>
						<th scope="col" class="text-end"><?php esc_html_e( 'Actions', 'peaches' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts as $post ): ?>
						<tr>
							<td>
								<div class="d-flex align-items-center">
									<div>
										<h6 class="mb-1"><?php echo esc_html( $post->post_title ?: __( '(no title)', 'peaches' ) ); ?></h6>
									</div>
								</div>
							</td>

							<?php if ( $post_type === 'product_settings' ): ?>
								<td class="text-center">
									<?php
									$product_sku = get_post_meta($post->ID, '_ecwid_product_sku', true);
									if ($product_sku) {
										echo '<span class="badge bg-primary">' . esc_html($product_sku) . '</span>';
									} else {
										echo '<span class="text-muted">—</span>';
									}
									?>
								</td>
								<td class="text-center">
									<?php
									$product_id = get_post_meta( $post->ID, '_ecwid_product_id', true );
									if ($product_id) {
										echo '<span class="badge bg-primary">' . esc_html($product_id) . '</span>';
									} else {
										echo '<span class="text-muted">—</span>';
									}
									?>
								</td>
								<td class="text-center">
									<div class="d-flex justify-content-center gap-1">
										<?php
										$ingredients = get_post_meta( $post->ID, '_product_ingredients', true );
										$ingredients_count = is_array( $ingredients ) ? count( $ingredients ) : 0;
										echo '<span class="badge bg-success" title="Ingredients">' . $ingredients_count . ' I</span>';

										$media = get_post_meta( $post->ID, '_product_media', true );
										$media_count = is_array( $media ) ? count( $media ) : 0;
										echo '<span class="badge bg-info" title="Media">' . $media_count . ' M</span>';

										$lines = wp_get_object_terms( $post->ID, 'product_line' );
										$lines_count = is_array( $lines ) ? count( $lines ) : 0;
										echo '<span class="badge bg-warning text-dark" title="Lines">' . $lines_count . ' L</span>';

										$tags = wp_get_object_terms( $post->ID, 'post_tag' );
										$tags_count = is_array( $tags ) ? count( $tags ) : 0;
										echo '<span class="badge bg-secondary" title="Tags">' . $tags_count . ' T</span>';
										?>
									</div>
								</td>
							<?php elseif ( $post_type === 'product_ingredient' ): ?>
								<td>
									<?php
									$description = get_post_meta( $post->ID, '_ingredient_description', true );
									if ( $description ) {
										echo '<span class="text-muted">' . wp_trim_words( $description, 15 ) . '</span>';
									} else {
										echo '<em class="text-muted">' . esc_html__( 'No description', 'peaches' ) . '</em>';
									}
									?>
								</td>
							<?php endif; ?>

							<td class="text-center">
								<small class="text-muted"><?php echo esc_html( get_the_date( 'M j, Y', $post ) ); ?></small>
							</td>

							<td class="text-end">
								<div class="btn-group btn-group-sm" role="group">
									<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) ); ?>"
									   class="btn btn-outline-primary"
									   data-bs-toggle="tooltip"
									   title="<?php esc_attr_e( 'Edit item', 'peaches' ); ?>">
										<i class="dashicons dashicons-edit"></i>
										<span class="visually-hidden"><?php esc_html_e( 'Edit', 'peaches' ); ?></span>
									</a>
									<?php if ( current_user_can( 'delete_post', $post->ID ) ): ?>
										<a href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"
										   class="btn btn-outline-danger"
										   data-bs-toggle="tooltip"
										   title="<?php esc_attr_e( 'Delete item', 'peaches' ); ?>">
											<i class="dashicons dashicons-trash"></i>
											<span class="visually-hidden"><?php esc_html_e( 'Delete', 'peaches' ); ?></span>
										</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $total_count > 20 ): ?>
			<div class="d-flex justify-content-between align-items-center mt-3 p-2 bg-light rounded">
				<span class="text-muted small">
					<?php printf( __( '%s more items available', 'peaches' ), number_format_i18n( $total_count - 20 ) ); ?>
				</span>
				<?php $post_type_object = get_post_type_object( $post_type ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $post_type ) ); ?>"
				   class="btn btn-outline-primary btn-sm">
					<i class="dashicons dashicons-external"></i>
					<?php printf( esc_html__( 'Manage All %s', 'peaches' ), esc_html( $post_type_object->labels->name ) ); ?>
				</a>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render embedded taxonomy list table (matching media tags style)
	 *
	 * @since 0.2.0
	 *
	 * @param string $taxonomy Taxonomy to display
	 *
	 * @return void
	 */
	private function render_embedded_taxonomy_list( $taxonomy ) {
		// Get the terms
		$terms = get_terms( array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC',
			'number' => 20
		) );

		$total_count = wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			?>
			<div class="text-center py-5">
				<i class="dashicons dashicons-tag" style="font-size: 64px; color: #dee2e6; margin-bottom: 16px;"></i>
				<h4 class="text-muted"><?php esc_html_e( 'No product lines found', 'peaches' ); ?></h4>
				<p class="text-muted mb-4"><?php esc_html_e( 'Create your first product line to organize your products.', 'peaches' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy ) ); ?>" class="btn btn-primary">
					<i class="dashicons dashicons-plus-alt2"></i>
					<?php esc_html_e( 'Create First Product Line', 'peaches' ); ?>
				</a>
			</div>
			<?php
			return;
		}

		?>
		<div class="table-responsive">
			<table class="table table-hover align-middle">
				<thead class="table-light">
					<tr>
						<th scope="col"><?php esc_html_e( 'Type', 'peaches' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Name', 'peaches' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Tag name', 'peaches' ); ?></th>
						<th scope="col" class="text-center"><?php esc_html_e( 'Media', 'peaches' ); ?></th>
						<th scope="col" class="text-center"><?php esc_html_e( 'Products', 'peaches' ); ?></th>
						<th scope="col" class="text-end"><?php esc_html_e( 'Actions', 'peaches' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $terms as $term ): ?>
						<tr>
							<td class="align-items-center flex-shrink-1">
								<?php
								$line_type = get_term_meta( $term->term_id, 'line_type', true );
								if ($line_type) {
									echo '<span class="badge bg-secondary">' . esc_html($line_type) . '</span>';
								} else {
									echo '<span class="text-muted">—</span>';
								}
								?>
							</td>

							<td>
								<div class="d-flex align-items-center">
									<div>
										<h6 class="mb-1"><?php echo esc_html( $term->name ); ?></h6>
									</div>
								</div>
							</td>

							<td>
								<div class="d-flex align-items-center">
									<div class="me-3">
										<code class="bg-light px-2 py-1 rounded text-dark"><?php echo esc_html( $term->slug ); ?></code>
									</div>
								</div>
							</td>

							<td class="text-center">
								<?php
								$line_media = get_term_meta( $term->term_id, 'line_media', true );
								if ( is_array( $line_media ) && count( $line_media ) > 0 ) {
									echo '<div class="d-flex align-items-center justify-content-center gap-2">';
									echo '<span class="badge bg-info">' . count( $line_media ) . '</span>';
									if ( isset( $line_media[0]['attachment_id'] ) ) {
										echo wp_get_attachment_image( $line_media[0]['attachment_id'], array( 32, 32 ), false, array('class' => 'rounded') );
									}
									echo '</div>';
								} else {
									echo '<span class="badge bg-light text-dark">0</span>';
								}
								?>
							</td>

							<td class="text-center">
								<?php
								if ($term->count > 0) {
									echo '<span class="badge bg-success">' . esc_html( $term->count ) . '</span>';
								} else {
									echo '<span class="badge bg-light text-dark">0</span>';
								}
								?>
							</td>

							<td class="text-end">
								<div class="btn-group btn-group-sm" role="group">
									<a href="<?php echo esc_url( admin_url( 'edit-tags.php?action=edit&taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id ) ); ?>"
									   class="btn btn-outline-primary"
									   data-bs-toggle="tooltip"
									   title="<?php esc_attr_e( 'Edit line', 'peaches' ); ?>">
										<i class="dashicons dashicons-edit"></i>
										<span class="visually-hidden"><?php esc_html_e( 'Edit', 'peaches' ); ?></span>
									</a>
									<?php if ( current_user_can( 'delete_term', $term->term_id ) ): ?>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit-tags.php?action=delete&taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id ), 'delete-tag_' . $term->term_id ) ); ?>"
										   class="btn btn-outline-danger"
										   data-bs-toggle="tooltip"
										   title="<?php esc_attr_e( 'Delete line', 'peaches' ); ?>">
											<i class="dashicons dashicons-trash"></i>
											<span class="visually-hidden"><?php esc_html_e( 'Delete', 'peaches' ); ?></span>
										</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $total_count > 20 ): ?>
			<div class="d-flex justify-content-between align-items-center mt-3 p-2 bg-light rounded">
				<span class="text-muted small">
					<?php printf( __( '%s more items available', 'peaches' ), number_format_i18n( $total_count - count($terms) ) ); ?>
				</span>
				<?php $taxonomy_object = get_taxonomy( $taxonomy ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy ) ); ?>"
				   class="btn btn-outline-primary btn-sm">
					<i class="dashicons dashicons-external"></i>
					<?php printf( esc_html__( 'Manage All %s', 'peaches' ), esc_html( $taxonomy_object->labels->name ) ); ?>
				</a>
			</div>
		<?php endif; ?>
		<?php
	}

/**
	 * Enqueue admin scripts and styles
	 *
	 * @since 0.2.0
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, self::PAGE_SLUG ) === false ) {
			return;
		}

		// Custom admin styles
		wp_enqueue_style(
			'peaches-product-settings-page',
			PEACHES_ECWID_ASSETS_URL . 'css/admin-product-settings-page.css',
			array(),
			PEACHES_ECWID_VERSION
		);

		// ALWAYS enqueue media tags scripts when on the product settings page
		// This ensures the script is available when switching tabs dynamically
		if ( $this->media_tags_manager ) {
			$this->media_tags_manager->enqueue_tab_scripts( $hook );
		}

		// Enqueue jQuery to ensure we have a script handle to attach to
		wp_enqueue_script('jquery');

		// Enhanced inline script for URL management and tab handling
		wp_add_inline_script('jquery', '
			document.addEventListener("DOMContentLoaded", function() {
				// Initialize the correct tab from URL parameter
				const urlParams = new URLSearchParams(window.location.search);
				const activeTab = urlParams.get("tab") || "product_settings";

				let targetId = "#product-settings";
				if (activeTab === "ingredients_library") {
					targetId = "#ingredients-library";
				} else if (activeTab === "media_tags") {
					targetId = "#media-tags";
				} else if (activeTab === "product_lines") {
					targetId = "#product-lines";
				}

				// Activate the correct tab on page load
				const tabButton = document.querySelector(`button[data-bs-target="${targetId}"]`);
				if (tabButton && typeof bootstrap !== "undefined" && bootstrap.Tab) {
					const tab = new bootstrap.Tab(tabButton);
					tab.show();
				}

				// Initialize tooltips for all tabs
				if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
					const tooltipTriggerList = document.querySelectorAll("[data-bs-toggle=\"tooltip\"]");
					tooltipTriggerList.forEach(function(tooltipTriggerEl) {
						new bootstrap.Tooltip(tooltipTriggerEl);
					});
				}

				// Handle browser back/forward buttons
				window.addEventListener("popstate", function(event) {
					const urlParams = new URLSearchParams(window.location.search);
					const tabFromUrl = urlParams.get("tab") || "product_settings";

					let targetId = "#product-settings";
					if (tabFromUrl === "ingredients_library") {
						targetId = "#ingredients-library";
					} else if (tabFromUrl === "media_tags") {
						targetId = "#media-tags";
					} else if (tabFromUrl === "product_lines") {
						targetId = "#product-lines";
					}

					const tabButton = document.querySelector(`button[data-bs-target="${targetId}"]`);
					if (tabButton && typeof bootstrap !== "undefined" && bootstrap.Tab) {
						const tab = new bootstrap.Tab(tabButton);
						tab.show();
					}
				});

				// Update URL when tab changes (create new history entry)
				document.querySelectorAll("#productManagementTabs button[data-bs-toggle=\"tab\"]").forEach(function(button) {
					button.addEventListener("shown.bs.tab", function(event) {
						const targetId = event.target.getAttribute("data-bs-target");
						let tabName = "product_settings";

						if (targetId === "#ingredients-library") {
							tabName = "ingredients_library";
						} else if (targetId === "#media-tags") {
							tabName = "media_tags";
						} else if (targetId === "#product-lines") {
							tabName = "product_lines";
						}

						const url = new URL(window.location);
						url.searchParams.set("tab", tabName);

						// Use pushState to create new history entries for proper back button behavior
						window.history.pushState({tab: tabName}, "", url);
					});
				});
			});
		');
	}
}
