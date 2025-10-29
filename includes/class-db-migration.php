<?php
/**
 * Database Migration class
 *
 * Handles database migrations for plugin updates.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Ecwid_DB_Migration
 *
 * Manages database schema migrations for plugin updates.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Ecwid_DB_Migration {
	/**
	 * Current database version option name
	 *
	 * @since 0.2.0
	 * @var string
	 */
	const DB_VERSION_OPTION = 'peaches_ecwid_db_version';

	/**
	 * Current database version
	 *
	 * @since 0.2.0
	 * @var string
	 */
	const CURRENT_DB_VERSION = '0.2.2';

	/**
	 * Run migrations if needed
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public static function maybe_migrate() {
		$current_version = get_option( self::DB_VERSION_OPTION, '0.0.0' );

		if ( version_compare( $current_version, self::CURRENT_DB_VERSION, '<' ) ) {
			self::run_migrations( $current_version );
			update_option( self::DB_VERSION_OPTION, self::CURRENT_DB_VERSION );
		}
	}

	/**
	 * Run all necessary migrations
	 *
	 * @since 0.2.0
	 *
	 * @param string $from_version Version to migrate from.
	 *
	 * @return void
	 */
	private static function run_migrations( $from_version ) {
		// Migration from 0.1.x to 0.2.0 - rename post type
		if ( version_compare( $from_version, '0.2.0', '<' ) ) {
			self::migrate_to_0_2_0();
		}

		// Migration from 0.2.0 to 0.2.1 - remove product groups
		if ( version_compare( $from_version, '0.2.1', '<' ) ) {
			self::migrate_to_0_2_1();
		}

		// Migration from 0.2.1 to 0.2.2 - rename master ingredients to product ingredients
		if ( version_compare( $from_version, '0.2.2', '<' ) ) {
			self::migrate_to_0_2_2();
		}
	}

	/**
	 * Migration to version 0.2.0
	 * Renames product_ingredients post type to product_settings
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private static function migrate_to_0_2_0() {
		global $wpdb;

		// Log migration start
		self::log_info( 'Starting migration to 0.2.0 - renaming product_ingredients to product_settings' );

		// Update posts table
		$updated_posts = $wpdb->update(
			$wpdb->posts,
			array( 'post_type' => 'product_settings' ),
			array( 'post_type' => 'product_ingredients' ),
			array( '%s' ),
			array( '%s' )
		);

		// Update user meta for screen options, etc. - but be very careful with capabilities
		$safe_meta_updates = array(
			'manageedit-product_ingredientscolumnshidden' => 'manageedit-product_settingscolumnshidden',
			'edit_product_ingredients_per_page'           => 'edit_product_settings_per_page',
			'closedpostboxes_product_ingredients'         => 'closedpostboxes_product_settings',
			'metaboxhidden_product_ingredients'           => 'metaboxhidden_product_settings',
		);

		foreach ( $safe_meta_updates as $old_key => $new_key ) {
			$wpdb->update(
				$wpdb->usermeta,
				array( 'meta_key' => $new_key ),
				array( 'meta_key' => $old_key ),
				array( '%s' ),
				array( '%s' )
			);
		}

		// Update any option names that might reference the old post type
		$wpdb->update(
			$wpdb->options,
			array( 'option_name' => 'product_settings_rewrite_rules' ),
			array( 'option_name' => 'product_ingredients_rewrite_rules' ),
			array( '%s' ),
			array( '%s' )
		);

		// Clear any caches
		wp_cache_flush();

		// Force rewrite rules flush
		delete_option( 'rewrite_rules' );

		self::log_info( "Migration to 0.2.0 completed safely. Updated {$updated_posts} posts from product_ingredients to product_settings" );
	}

	/**
	 * Migration to version 0.2.1
	 * Removes product groups post type and migrates to taxonomies
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private static function migrate_to_0_2_1() {
		global $wpdb;

		self::log_info( 'Starting migration to 0.2.1 - removing product groups and setting up product lines' );

		// First, migrate any existing product group assignments to product lines
		self::migrate_groups_to_lines();

		// Remove product_groups posts
		$deleted_posts = $wpdb->delete(
			$wpdb->posts,
			array( 'post_type' => 'product_groups' ),
			array( '%s' )
		);

		// Remove product_groups post meta
		$deleted_meta = $wpdb->query(
			$wpdb->prepare(
				"DELETE pm FROM {$wpdb->postmeta} pm
				 LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				 WHERE p.post_type = %s OR p.ID IS NULL",
				'product_groups'
			)
		);

		// Clean up user meta related to product_groups
		$group_meta_updates = array(
			'manageedit-product_groupscolumnshidden',
			'edit_product_groups_per_page',
			'closedpostboxes_product_groups',
			'metaboxhidden_product_groups',
		);

		foreach ( $group_meta_updates as $meta_key ) {
			$wpdb->delete(
				$wpdb->usermeta,
				array( 'meta_key' => $meta_key ),
				array( '%s' )
			);
		}

		// Remove product_groups related options
		delete_option( 'product_groups_rewrite_rules' );

		// Update product_settings meta to remove _product_groups references
		$wpdb->delete(
			$wpdb->postmeta,
			array( 'meta_key' => '_product_groups' ),
			array( '%s' )
		);

		// Clear any caches
		wp_cache_flush();

		// Force rewrite rules flush
		delete_option( 'rewrite_rules' );

		self::log_info( "Migration to 0.2.1 completed. Removed {$deleted_posts} product group posts and cleaned up meta data" );
	}

	/**
	 * Migrate existing product groups to product lines taxonomy
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private static function migrate_groups_to_lines() {
		// Get all product_groups posts
		$groups = get_posts(
			array(
				'post_type'      => 'product_groups',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		if ( empty( $groups ) ) {
			self::log_info( 'No product groups found to migrate' );
			return;
		}

		self::log_info( 'Migrating ' . count( $groups ) . ' product groups to product lines' );

		foreach ( $groups as $group ) {
			// Create product line term
			$term_data = wp_insert_term(
				$group->post_title,
				'product_line',
				array(
					'description' => $group->post_content,
					'slug'        => $group->post_name,
				)
			);

			if ( is_wp_error( $term_data ) ) {
				self::log_error( 'Failed to create product line for group: ' . $group->post_title, array(
					'error' => $term_data->get_error_message(),
				) );
				continue;
			}

			$term_id = $term_data['term_id'];

			// Migrate group meta to term meta
			$group_attributes = get_post_meta( $group->ID, '_product_group_attributes', true );
			$group_media      = get_post_meta( $group->ID, '_product_group_media', true );

			// Extract type from attributes
			$line_type        = '';
			$other_attributes = array();

			if ( is_array( $group_attributes ) ) {
				foreach ( $group_attributes as $attr ) {
					if ( isset( $attr['key'] ) && $attr['key'] === 'type' ) {
						$line_type = $attr['value'];
					} else {
						$other_attributes[] = $attr;
					}
				}
			}

			// Save term meta
			if ( $line_type ) {
				update_term_meta( $term_id, 'line_type', $line_type );
			}

			if ( $group->post_content ) {
				update_term_meta( $term_id, 'line_description', $group->post_content );
			}

			// Migrate media - convert single media to array format
			if ( $group_media ) {
				$line_media = array(
					array(
						'tag'           => 'main_image',
						'tag_id'        => 0, // Will be created when term is saved
						'attachment_id' => $group_media,
					),
				);
				update_term_meta( $term_id, 'line_media', $line_media );

				// Mark attachment as line media
				update_post_meta( $group_media, '_peaches_line_media', true );
				update_post_meta( $group_media, '_peaches_line_media_tag', 'main_image' );
			}

			// Find product_settings that referenced this group and update them
			$product_settings = get_posts(
				array(
					'post_type'      => 'product_settings',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => '_product_groups',
							'value'   => $group->ID,
							'compare' => 'LIKE',
						),
					),
				)
			);

			foreach ( $product_settings as $product_setting ) {
				// Add the new product line term to this product
				$existing_lines = wp_get_object_terms( $product_setting->ID, 'product_line', array( 'fields' => 'ids' ) );
				if ( is_wp_error( $existing_lines ) ) {
					$existing_lines = array();
				}

				$existing_lines[] = $term_id;
				wp_set_object_terms( $product_setting->ID, $existing_lines, 'product_line' );

				self::log_info( 'Assigned product line ' . $group->post_title . ' to product setting ' . $product_setting->post_title );
			}

			self::log_info( 'Successfully migrated group "' . $group->post_title . '" to product line' );
		}
	}

	/**
	 * Migration to version 0.2.2
	 * Renames master_ingredient post type to product_ingredient
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private static function migrate_to_0_2_2() {
		global $wpdb;

		// Log migration start
		self::log_info( 'Starting migration to 0.2.2 - renaming master_ingredient to product_ingredient' );

		// Update posts table
		$updated_posts = $wpdb->update(
			$wpdb->posts,
			array( 'post_type' => 'product_ingredient' ),
			array( 'post_type' => 'master_ingredient' ),
			array( '%s' ),
			array( '%s' )
		);

		// Update user meta for screen options, etc.
		$meta_updates = array(
			'manageedit-master_ingredientcolumnshidden' => 'manageedit-product_ingredientcolumnshidden',
			'edit_master_ingredient_per_page'           => 'edit_product_ingredient_per_page',
			'closedpostboxes_master_ingredient'         => 'closedpostboxes_product_ingredient',
			'metaboxhidden_master_ingredient'           => 'metaboxhidden_product_ingredient',
		);

		foreach ( $meta_updates as $old_key => $new_key ) {
			$wpdb->update(
				$wpdb->usermeta,
				array( 'meta_key' => $new_key ),
				array( 'meta_key' => $old_key ),
				array( '%s' ),
				array( '%s' )
			);
		}

		// Update any option names that might reference the old post type
		$wpdb->update(
			$wpdb->options,
			array( 'option_name' => 'product_ingredient_rewrite_rules' ),
			array( 'option_name' => 'master_ingredient_rewrite_rules' ),
			array( '%s' ),
			array( '%s' )
		);

		// Update product_settings meta that references master ingredients
		// Change master_ingredient_id fields to library_ingredient_id to be more descriptive
		$ingredient_references = $wpdb->get_results(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta}
			 WHERE meta_key = '_product_ingredients'"
		);

		foreach ( $ingredient_references as $reference ) {
			$ingredients_data = maybe_unserialize( $reference->meta_value );

			if ( is_array( $ingredients_data ) ) {
				$updated = false;
				foreach ( $ingredients_data as &$ingredient ) {
					if ( isset( $ingredient['master_id'] ) ) {
						// Rename master_id to library_id for clarity
						$ingredient['library_id'] = $ingredient['master_id'];
						unset( $ingredient['master_id'] );
						$updated = true;
					}
					if ( isset( $ingredient['type'] ) && $ingredient['type'] === 'master' ) {
						// Rename type from 'master' to 'library'
						$ingredient['type'] = 'library';
						$updated = true;
					}
				}

				if ( $updated ) {
					update_post_meta( $reference->post_id, '_product_ingredients', $ingredients_data );
				}
			}
		}

		// Clear any caches
		wp_cache_flush();

		// Force rewrite rules flush
		delete_option( 'rewrite_rules' );

		self::log_info( "Migration to 0.2.2 completed. Updated {$updated_posts} posts from master_ingredient to product_ingredient" );
	}

	/**
	 * Check if migration is needed
	 *
	 * @since 0.2.0
	 *
	 * @return bool True if migration is needed.
	 */
	public static function needs_migration() {
		$current_version = get_option( self::DB_VERSION_OPTION, '0.0.0' );
		return version_compare( $current_version, self::CURRENT_DB_VERSION, '<' );
	}

	/**
	 * Get current database version
	 *
	 * @since 0.2.0
	 *
	 * @return string Current database version.
	 */
	public static function get_current_version() {
		return get_option( self::DB_VERSION_OPTION, '0.0.0' );
	}

	/**
	 * Log informational messages.
	 *
	 * Only logs when debug mode is enabled.
	 *
	 * @since 0.2.0
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private static function log_info( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) && Peaches_Ecwid_Utilities::is_debug_mode() ) {
			Peaches_Ecwid_Utilities::log_error( '[INFO] [DB Migration] ' . $message, $context );
		}
	}

	/**
	 * Log error messages.
	 *
	 * Always logs, regardless of debug mode.
	 *
	 * @since 0.2.0
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	private static function log_error( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) ) {
			Peaches_Ecwid_Utilities::log_error( '[DB Migration] ' . $message, $context );
		} else {
			// Fallback logging if utilities class is not available
			error_log( '[Peaches Ecwid] [DB Migration] ' . $message . ( empty( $context ) ? '' : ' - Context: ' . wp_json_encode( $context ) ) );
		}
	}
}
