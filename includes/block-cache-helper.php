<?php
/**
 * Block HTML Caching Helper Functions
 *
 * Provides reusable functions for implementing block HTML caching
 * across all Ecwid blocks with render.php files.
 *
 * @package PeachesEcwidBlocks
 * @since   0.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if we're in an editor context where caching should be disabled
 *
 * @since  0.5.0
 * @return bool True if in editor context, false otherwise.
 */
function peaches_ecwid_is_editor_context() {
	// Check for block editor REST API requests (check this FIRST, before is_admin).
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		if ( false !== strpos( $request_uri, '/wp/v2/' ) ) {
			return true;
		}
	}

	// Check if we're in admin area.
	if ( ! is_admin() ) {
		return false;
	}

	// Check for block editor page.
	global $pagenow;
	if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
		return true;
	}

	// Check for admin-ajax requests from block editor.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$action = $_REQUEST['action'] ?? '';
		// Common block editor ajax actions.
		$editor_actions = array(
			'heartbeat',
			'parse-embed',
			'wp_link_ajax',
			'search-users',
			'ajax-tag-search',
		);
		if ( in_array( $action, $editor_actions, true ) ) {
			return true;
		}
	}

	// Check for preview requests.
	if ( is_preview() ) {
		return true;
	}

	return false;
}

/**
 * Check for cached block HTML and start output buffering if needed
 *
 * @since  0.5.0
 * @param  string $block_type         The block type identifier (e.g., 'ecwid-product-detail').
 * @param  array  $attributes         Block attributes.
 * @param  string $content            Block content.
 * @param  array  $additional_factors Additional factors that affect rendering.
 * @return array|false Array with cache_manager and cache_factors, or false if cached HTML was served.
 */
function peaches_ecwid_start_block_cache( $block_type, $attributes, $content, $additional_factors = array() ) {
	// Skip caching in editor contexts to avoid stale content and cache pollution.
	if ( peaches_ecwid_is_editor_context() ) {
		return false;
	}

	// Get the cache manager from Bootstrap Blocks.
	$bootstrap_blocks = Peaches_Bootstrap_Blocks::get_instance();
	if ( ! $bootstrap_blocks ) {
		return false;
	}

	$cache_manager = $bootstrap_blocks->get_cache_manager();
	if ( ! $cache_manager ) {
		return false;
	}

	// Create cache key based on all variables that affect the output.
	$current_language = function_exists( 'peaches_get_render_language' ) ? peaches_get_render_language() : '';
	$cache_factors    = array_merge(
		array(
			'attributes'       => $attributes,
			'content'          => $content,
			'current_language' => $current_language,
			'query_vars'       => array(
				'ecwid_product_slug' => get_query_var( 'ecwid_product_slug', '' ),
				'ecwid_category_id'  => get_query_var( 'ecwid_category_id', '' ),
				'page'               => get_query_var( 'page', 1 ),
			),
		),
		$additional_factors
	);

	// Check for cached HTML.
	$cached_html = $cache_manager->get_cached_block_html( $block_type, $cache_factors );

	if ( false !== $cached_html ) {
		// Return cached HTML directly.
		echo $cached_html;
		return false; // Signal that cached content was served.
	}

	// Start output buffering to capture the rendered HTML.
	ob_start();

	return array(
		'cache_manager' => $cache_manager,
		'cache_factors' => $cache_factors,
	);
}

/**
 * Product-aware cache function for Ecwid product blocks
 *
 * Creates cache keys based on the actual product ID being rendered,
 * avoiding unnecessary cache variations for the same product content.
 *
 * @since  0.5.0
 * @param  string $block_type         The block type identifier (e.g., 'ecwid-product-description').
 * @param  array  $attributes         Block attributes.
 * @param  string $content            Block content.
 * @param  array  $additional_factors Additional factors that affect rendering.
 * @return array|false Array with cache_manager and cache_factors, or false if cached HTML was served.
 */
function peaches_ecwid_start_product_block_cache( $block_type, $attributes, $content, $additional_factors = array() ) {
	// Skip caching in editor contexts to avoid stale content and cache pollution.
	if ( peaches_ecwid_is_editor_context() ) {
		return false;
	}

	// Get the cache manager from Bootstrap Blocks.
	$bootstrap_blocks = Peaches_Bootstrap_Blocks::get_instance();
	if ( ! $bootstrap_blocks ) {
		return false;
	}

	$cache_manager = $bootstrap_blocks->get_cache_manager();
	if ( ! $cache_manager ) {
		return false;
	}

	// Determine the product ID for caching.
	$product_id = peaches_ecwid_get_effective_product_id( $attributes );

	// If no product ID, fall back to regular caching.
	if ( empty( $product_id ) ) {
		return peaches_ecwid_start_block_cache( $block_type, $attributes, $content, $additional_factors );
	}

	// Create cache key based on product ID and other relevant factors.
	$current_language = function_exists( 'peaches_get_render_language' ) ? peaches_get_render_language() : '';

	$cache_factors = array_merge(
		array(
			'product_id'       => $product_id,
			'attributes'       => $attributes,
			'content'          => $content,
			'current_language' => $current_language,
		),
		$additional_factors
	);

	// Check for cached HTML.
	$cached_html = $cache_manager->get_cached_block_html( $block_type, $cache_factors );

	if ( false !== $cached_html ) {
		// Return cached HTML directly.
		echo $cached_html;
		return false; // Signal that cached content was served.
	}

	// Start output buffering to capture the rendered HTML.
	ob_start();

	return array(
		'cache_manager' => $cache_manager,
		'cache_factors' => $cache_factors,
	);
}

/**
 * Category-aware cache function for Ecwid category blocks
 *
 * Creates cache keys based on the category ID being rendered,
 * avoiding cache variations from query_vars between editor and frontend.
 *
 * @since  0.5.0
 * @param  string $block_type         The block type identifier (e.g., 'ecwid-category-products').
 * @param  array  $attributes         Block attributes.
 * @param  string $content            Block content.
 * @param  array  $additional_factors Additional factors that affect rendering.
 * @return array|false Array with cache_manager and cache_factors, or false if cached HTML was served.
 */
function peaches_ecwid_start_category_block_cache( $block_type, $attributes, $content, $additional_factors = array() ) {
	// Skip caching in editor contexts to avoid stale content and cache pollution.
	if ( peaches_ecwid_is_editor_context() ) {
		return false;
	}

	// Get the cache manager from Bootstrap Blocks.
	$bootstrap_blocks = Peaches_Bootstrap_Blocks::get_instance();
	if ( ! $bootstrap_blocks ) {
		return false;
	}

	$cache_manager = $bootstrap_blocks->get_cache_manager();
	if ( ! $cache_manager ) {
		return false;
	}

	// Get category ID from attributes.
	$category_id = isset( $attributes['selectedCategoryId'] ) ? absint( $attributes['selectedCategoryId'] ) : 0;

	// If no category ID, fall back to regular caching.
	if ( empty( $category_id ) ) {
		return peaches_ecwid_start_block_cache( $block_type, $attributes, $content, $additional_factors );
	}

	// Create cache key based on category ID and other relevant factors.
	$current_language = function_exists( 'peaches_get_render_language' ) ? peaches_get_render_language() : '';

	$cache_factors = array_merge(
		array(
			'category_id'      => $category_id,
			'attributes'       => $attributes,
			'content'          => $content,
			'current_language' => $current_language,
		),
		$additional_factors
	);

	// Check for cached HTML.
	$cached_html = $cache_manager->get_cached_block_html( $block_type, $cache_factors );

	if ( false !== $cached_html ) {
		// Return cached HTML directly.
		echo $cached_html;
		return false; // Signal that cached content was served.
	}

	// Start output buffering to capture the rendered HTML.
	ob_start();

	return array(
		'cache_manager' => $cache_manager,
		'cache_factors' => $cache_factors,
	);
}

/**
 * Cache the rendered block HTML
 *
 * @since  0.5.0
 * @param  string $block_type    Block type identifier.
 * @param  object $cache_manager Cache manager instance.
 * @param  array  $cache_factors Cache factors array.
 * @param  int    $duration      Cache duration in seconds (default: 300 = 5 minutes).
 * @return void
 */
function peaches_ecwid_end_block_cache( $block_type, $cache_manager, $cache_factors, $duration = 300 ) {
	// Cache the rendered HTML if output buffering is active.
	if ( $cache_manager && ob_get_level() > 0 ) {
		$rendered_html = ob_get_clean();

		// Cache the HTML (Redis only - no transient fallback).
		$cache_manager->cache_block_html( $block_type, $cache_factors, $rendered_html, $duration );

		// Output the HTML.
		echo $rendered_html;
	}
}
