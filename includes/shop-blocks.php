<?php
/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */

require_once plugin_dir_path(__FILE__) . '/../src/ecwid-product/render.php';
require_once plugin_dir_path(__FILE__) . '/../src/ecwid-category/render.php';

function peaches_bootstrap_ecwid_create_blocks_init() {
	wp_register_block_metadata_collection(
		__DIR__ . '/../dist',
		__DIR__ . '/../dist/blocks-manifest.php'
	);

	wp_register_script('ecwid-product', get_template_directory_uri().'/build/ecwid-product/index.js',
        array('wp-blocks', 'wp-element'), time() );

	register_block_type( __DIR__ . '/../build/ecwid-product', [
		'render_callback' => 'peaches_ecwid_product_render',
		'attributes' => [
			'classes'  => [ 'type' => 'string' ],
			'id'  => [ 'type' => 'integer' ],
			'xs'  => [ 'type' => 'object' ],
			'sm'  => [ 'type' => 'object' ],
			'md'  => [ 'type' => 'object' ],
			'lg'  => [ 'type' => 'object' ],
			'xl'  => [ 'type' => 'object' ],
			'xxl' => [ 'type' => 'object' ],
		]
	]);
	register_block_type( __DIR__ . '/../build/ecwid-category', [
		'render_callback' => 'peaches_ecwid_category_render',
		'attributes' => [
			'className' => [ 'type' => 'string', 'default' => '' ],
			'classes'  => [ 'type' => 'string', 'default' => 'row row-cols-4' ],
			'xs'  => [ 'type' => 'object', 'default' => ['rowCols' => 4] ],
			'sm'  => [ 'type' => 'object' ],
			'md'  => [ 'type' => 'object' ],
			'lg'  => [ 'type' => 'object' ],
			'xl'  => [ 'type' => 'object' ],
			'xxl' => [ 'type' => 'object' ],
		]
	]);

	register_block_type_from_metadata( __DIR__ . '/../build/ecwid-product-detail/' );
	register_block_type_from_metadata( __DIR__ . '/../build/ecwid-product-add-to-cart/' );
	register_block_type_from_metadata( __DIR__ . '/../build/ecwid-product-images/' );
}
add_action( 'init', 'peaches_bootstrap_ecwid_create_blocks_init' );
