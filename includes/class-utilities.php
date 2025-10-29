<?php
/**
 * Consolidated Utilities class
 *
 * Provides utility functions used throughout the plugin with proper error handling and logging.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Ecwid_Utilities
 *
 * Centralized utility functions for the plugin.
 *
 * @package PeachesEcwidBlocks
 * @since   0.2.0
 */
class Peaches_Ecwid_Utilities {

	/**
	 * Plugin settings cache.
	 *
	 * @since 0.2.0
	 * @var array|null
	 */
	private static $settings_cache = null;

	/**
	 * Language detection cache.
	 *
	 * @since 0.2.0
	 * @var array
	 */
	private static $language_cache = array();

	/**
	 * Check if peaches-multilingual plugin is available and active.
	 *
	 * @since 0.5.0
	 *
	 * @return bool True if peaches-multilingual plugin is available.
	 */
	public static function is_peaches_multilingual_available() {
		return function_exists( 'peaches_get_render_language' ) ||
		       function_exists( 'peaches_current_language' ) ||
		       class_exists( 'Peaches_Multilingual_Integration' );
	}

	/**
	 * Sanitize attributes with enhanced validation.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $attributes The attributes to sanitize.
	 *
	 * @return array The sanitized attributes.
	 *
	 * @throws InvalidArgumentException If attributes are not an array or string.
	 */
	public static function sanitize_attributes( $attributes ) {
		if ( is_string( $attributes ) ) {
			return array( sanitize_text_field( $attributes ) );
		}

		if ( ! is_array( $attributes ) ) {
			self::log_error( 'Invalid attributes type provided to sanitize_attributes', array(
				'type'  => gettype( $attributes ),
				'value' => $attributes,
			) );
			return array();
		}

		$sanitized = array();
		foreach ( $attributes as $key => $value ) {
			$sanitized_key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $sanitized_key ] = self::sanitize_attributes( $value );
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $sanitized_key ] = is_float( $value ) ? floatval( $value ) : absint( $value );
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $sanitized_key ] = (bool) $value;
			} else {
				$sanitized[ $sanitized_key ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Get current language with caching and fallback.
	 *
	 * @since 0.2.0
	 *
	 * @param bool $force_refresh Force refresh of cache.
	 *
	 * @return string The current language code.
	 */
	public static function get_current_language( $force_refresh = false ) {
		$cache_key = 'current_language';

		if ( ! $force_refresh && isset( self::$language_cache[ $cache_key ] ) ) {
			return self::$language_cache[ $cache_key ];
		}

		$language = '';

		try {
			// 1. First priority: peaches-multilingual plugin
			if ( self::is_peaches_multilingual_available() ) {
				if ( function_exists( 'peaches_get_render_language' ) ) {
					$language = peaches_get_render_language();
				} elseif ( function_exists( 'peaches_current_language' ) ) {
					$language = peaches_current_language();
				}
			}

			// 2. Second priority: Direct Polylang support
			if ( empty( $language ) && function_exists( 'pll_current_language' ) ) {
				$language = pll_current_language();
			}

			// 3. Third priority: Direct WPML support
			if ( empty( $language ) && defined( 'ICL_LANGUAGE_CODE' ) ) {
				$language = ICL_LANGUAGE_CODE;
			}

			// 4. WordPress locale fallback
			if ( empty( $language ) ) {
				$locale   = get_locale();
				$language = substr( $locale, 0, 2 );
			}

			// Validate language code
			if ( empty( $language ) || ! preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', $language ) ) {
				$language = 'en';
			}

		} catch ( Exception $e ) {
			self::log_error( 'Error detecting current language', array(
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			) );
			$language = 'en';
		}

		self::$language_cache[ $cache_key ] = $language;
		return $language;
	}

	/**
	 * Get available languages from multilingual plugins.
	 *
	 * @since 0.2.0
	 *
	 * @param bool $force_refresh Force refresh of cache.
	 *
	 * @return array Array of languages with codes as keys.
	 */
	public static function get_available_languages( $force_refresh = false ) {
		$cache_key = 'available_languages';

		if ( ! $force_refresh && isset( self::$language_cache[ $cache_key ] ) ) {
			return self::$language_cache[ $cache_key ];
		}

		$languages = array();

		try {
			// 1. First priority: peaches-multilingual plugin
			if ( self::is_peaches_multilingual_available() && function_exists( 'peaches_available_languages' ) ) {
				$lang_codes   = peaches_available_languages();
				$default_lang = function_exists( 'peaches_default_language' ) ? peaches_default_language() : '';

				if ( is_array( $lang_codes ) && ! empty( $lang_codes ) ) {
					foreach ( $lang_codes as $code ) {
						$languages[ $code ] = array(
							'code'       => $code,
							'is_default' => ( $code === $default_lang ),
							'name'       => $code, // peaches-multilingual might provide names in the future
						);
					}
				}
			}

			// 2. Second priority: Direct Polylang support
			if ( empty( $languages ) && function_exists( 'pll_languages_list' ) && function_exists( 'pll_default_language' ) ) {
				$lang_codes   = pll_languages_list( array( 'fields' => 'slug' ) );
				$default_lang = pll_default_language( 'slug' );

				if ( is_array( $lang_codes ) ) {
					foreach ( $lang_codes as $code ) {
						$languages[ $code ] = array(
							'code'       => $code,
							'is_default' => ( $code === $default_lang ),
							'name'       => function_exists( 'pll_the_languages' ) ? pll_the_languages( array( 'raw' => 1 ) )[ $code ]['name'] : $code,
						);
					}
				}
			}

			// 3. Third priority: Direct WPML support
			if ( empty( $languages ) && function_exists( 'icl_get_languages' ) ) {
				$wpml_languages = icl_get_languages( 'skip_missing=0' );

				if ( is_array( $wpml_languages ) ) {
					foreach ( $wpml_languages as $code => $lang_data ) {
						$languages[ $code ] = array(
							'code'       => $code,
							'is_default' => ! empty( $lang_data['default_locale'] ),
							'name'       => ! empty( $lang_data['native_name'] ) ? $lang_data['native_name'] : $code,
						);
					}
				}
			}

			// 4. Fallback: Single language site
			if ( empty( $languages ) ) {
				$locale        = get_locale();
				$language_code = substr( $locale, 0, 2 );

				$languages[ $language_code ] = array(
					'code'       => $language_code,
					'is_default' => true,
					'name'       => $language_code,
				);
			}

		} catch ( Exception $e ) {
			self::log_error( 'Error getting available languages', array(
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			) );

			// Return default language on error
			$languages = array(
				'en' => array(
					'code'       => 'en',
					'is_default' => true,
					'name'       => 'en',
				),
			);
		}

		self::$language_cache[ $cache_key ] = $languages;
		return $languages;
	}

	/**
	 * Get translated post ID for current language.
	 *
	 * @since 0.2.0
	 *
	 * @param int    $post_id     Original post ID.
	 * @param string $language    Optional. Target language code.
	 * @param string $post_type   Optional. Post type for validation.
	 *
	 * @return int Translated post ID or original if translation not found.
	 */
	public static function get_translated_post( $post_id, $language = '', $post_type = 'page' ) {
		if ( empty( $language ) ) {
			$language = self::get_current_language();
		}

		$translated_id = $post_id;

		try {
			// Try peaches-multilingual first
			if ( self::is_peaches_multilingual_available() && function_exists( 'peaches_get_translation' ) ) {
				$translated_id = peaches_get_translation( $post_id, $language );
			} elseif ( function_exists( 'pll_get_post' ) ) {
				// Direct Polylang support
				$translated = pll_get_post( $post_id, $language );
				if ( $translated ) {
					$translated_id = $translated;
				}
			} elseif ( function_exists( 'icl_object_id' ) ) {
				// Direct WPML support
				$translated = icl_object_id( $post_id, $post_type, false, $language );
				if ( $translated ) {
					$translated_id = $translated;
				}
			}
		} catch ( Exception $e ) {
			self::log_error( 'Error getting translated post', array(
				'post_id'  => $post_id,
				'language' => $language,
				'error'    => $e->getMessage(),
			) );
		}

		return absint( $translated_id );
	}

	/**
	 * Check if site is multilingual.
	 *
	 * @since 0.2.0
	 *
	 * @return bool True if multilingual plugin is active.
	 */
	public static function is_multilingual_site() {
		static $is_multilingual = null;

		if ( null !== $is_multilingual ) {
			return $is_multilingual;
		}

		$is_multilingual = false;

		try {
			// Check peaches-multilingual first
			if ( self::is_peaches_multilingual_available() ) {
				$is_multilingual = true;
			} elseif ( function_exists( 'pll_languages_list' ) || function_exists( 'icl_get_languages' ) ) {
				// Check for direct Polylang or WPML
				$is_multilingual = true;
			}
		} catch ( Exception $e ) {
			self::log_error( 'Error checking multilingual status', array(
				'error' => $e->getMessage(),
			) );
		}

		return $is_multilingual;
	}

	/**
	 * Get the shop path for current language.
	 *
	 * Returns the correct shop path based on current language, with fallback to default.
	 *
	 * @since  0.2.0
	 * @param  bool        $include_parents      Optional. Include parent page slugs. Default true.
	 * @param  bool        $with_trailing_slash  Optional. Add trailing slash. Default true.
	 * @param  string|null $lang                 Optional. Language code to get path for. Default null (current language).
	 * @return string Shop path.
	 * @throws InvalidArgumentException If language code format is invalid.
	 */
	public static function get_shop_path( $include_parents = true, $with_trailing_slash = true, $lang = null ) {
		// Validate language code format if provided.
		if ( null !== $lang && ! empty( $lang ) && ! preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', $lang ) ) {
			throw new InvalidArgumentException( 'Invalid language code format provided to get_shop_path' );
		}

		$shop_path = '';

		try {
			// Check for custom shop slug in Ecwid settings.
			$custom_shop_slug = self::get_ecwid_shop_slug();

			// For single-language sites, use custom slug if available.
			if ( ! empty( $custom_shop_slug ) && ! self::is_multilingual_site() ) {
				$shop_path = $custom_shop_slug;
			} else {
				// Get language-specific path.
				if ( null === $lang ) {
					$lang = self::get_current_language();
				}

				// Try multilingual settings first.
				if ( class_exists( 'Peaches_Multilingual_Settings' ) ) {
					$settings_manager = Peaches_Multilingual_Settings::get_instance();
					$shop_path        = $settings_manager->get_shop_path_for_language( $lang );
				}

				// Fallback to store page if no multilingual settings.
				if ( empty( $shop_path ) ) {
					$store_page_id = self::get_store_page_id();

					if ( $store_page_id ) {
						// Get translated page if multilingual.
						if ( self::is_multilingual_site() ) {
							$translated_id = self::get_translated_post( $store_page_id, $lang, 'page' );
							$post          = get_post( $translated_id );
						} else {
							$post = get_post( $store_page_id );
						}

						if ( $post && 'publish' === $post->post_status ) {
							// Build the full path including parent slugs if requested.
							if ( $include_parents ) {
								$parent_slugs = self::get_parent_page_slugs( $post->ID );
								$shop_path    = implode( '/', array_merge( $parent_slugs, array( $post->post_name ) ) );
							} else {
								$shop_path = $post->post_name;
							}
						}
					}
				}
			}

			// Fallback to Ecwid default.
			if ( empty( $shop_path ) ) {
				$shop_path = $custom_shop_slug ? $custom_shop_slug : 'shop';
			}

			// Add trailing slash if requested.
			if ( $with_trailing_slash && ! empty( $shop_path ) ) {
				$shop_path = rtrim( $shop_path, '/' ) . '/';
			}

		} catch ( Exception $e ) {
			self::log_error(
				'Error getting shop path',
				array(
					'language' => $lang,
					'error'    => $e->getMessage(),
				)
			);
			$shop_path = 'shop';
			if ( $with_trailing_slash ) {
				$shop_path .= '/';
			}
		}

		return $shop_path;
	}

	/**
	 * Get Ecwid's configured shop slug.
	 *
	 * @since 0.2.0
	 *
	 * @return string Shop slug from Ecwid settings.
	 */
	public static function get_ecwid_shop_slug() {
		if ( function_exists( 'get_ecwid_store_page_data' ) ) {
			$page_data = get_ecwid_store_page_data();
			if ( ! empty( $page_data['page_id'] ) ) {
				$post = get_post( $page_data['page_id'] );
				if ( $post ) {
					return $post->post_name;
				}
			}
		}

		return 'shop';
	}

	/**
	 * Get shop path with multilingual support.
	 *
	 * @since 0.2.0
	 *
	 * @param string $language Language code.
	 *
	 * @return string Shop path.
	 */
	public static function get_multilingual_shop_path( $language = '' ) {
		return self::get_shop_path( $language );
	}

	/**
	 * Get store page path from Ecwid configuration.
	 *
	 * @since 0.2.0
	 *
	 * @param string $language Optional. Language code.
	 *
	 * @return string Store page path or empty string.
	 */
	public static function get_store_page_path( $language = '' ) {
		if ( empty( $language ) ) {
			$language = self::get_current_language();
		}

		$cache_key = 'store_page_path_' . $language;

		if ( isset( self::$language_cache[ $cache_key ] ) ) {
			return self::$language_cache[ $cache_key ];
		}

		$path          = '';
		$store_page_id = self::get_store_page_id();

		if ( $store_page_id ) {
			if ( self::is_multilingual_site() ) {
				$store_page_id = self::get_translated_post( $store_page_id, $language, 'page' );
			}

			$post = get_post( $store_page_id );
			if ( $post && 'publish' === $post->post_status ) {
				$parent_slugs = self::get_parent_page_slugs( $post->ID );
				$path         = implode( '/', array_merge( $parent_slugs, array( $post->post_name ) ) );
			}
		}

		self::$language_cache[ $cache_key ] = $path;
		return $path;
	}

	/**
	 * Get Ecwid store page ID.
	 *
	 * @since 0.2.0
	 *
	 * @return int|null Store page ID or null if not found.
	 */
	public static function get_store_page_id() {
		if ( function_exists( 'get_ecwid_store_page_data' ) ) {
			$page_data = get_ecwid_store_page_data();
			if ( ! empty( $page_data['page_id'] ) ) {
				return absint( $page_data['page_id'] );
			}
		}

		return null;
	}

	/**
	 * Get default language code.
	 *
	 * @since 0.2.0
	 *
	 * @return string Default language code.
	 */
	public static function get_default_language() {
		$cache_key = 'default_language';

		if ( isset( self::$language_cache[ $cache_key ] ) ) {
			return self::$language_cache[ $cache_key ];
		}

		$default_language = '';

		try {
			// Try peaches-multilingual first
			if ( self::is_peaches_multilingual_available() && function_exists( 'peaches_default_language' ) ) {
				$default_language = peaches_default_language();
			} elseif ( function_exists( 'pll_default_language' ) ) {
				// Direct Polylang support
				$default_language = pll_default_language();
			} elseif ( defined( 'ICL_LANGUAGE_CODE' ) ) {
				// Direct WPML support
				$default_language = ICL_LANGUAGE_CODE;
			}

			// WordPress locale fallback
			if ( empty( $default_language ) ) {
				$locale           = get_locale();
				$default_language = substr( $locale, 0, 2 );
			}

		} catch ( Exception $e ) {
			self::log_error( 'Error getting default language', array(
				'error' => $e->getMessage(),
			) );
			$default_language = 'en';
		}

		self::$language_cache[ $cache_key ] = $default_language;
		return $default_language;
	}

	/**
	 * Get parent page slugs for building full paths.
	 *
	 * @since 0.2.0
	 *
	 * @param int $page_id Page ID to get parents for.
	 *
	 * @return array Array of parent slugs.
	 */
	public static function get_parent_page_slugs( $page_id ) {
		$slugs     = array();
		$ancestors = get_post_ancestors( $page_id );

		if ( ! empty( $ancestors ) ) {
			$ancestors = array_reverse( $ancestors );
			foreach ( $ancestors as $ancestor_id ) {
				$post = get_post( $ancestor_id );
				if ( $post ) {
					$slugs[] = $post->post_name;
				}
			}
		}

		return $slugs;
	}

	/**
	 * Get default shop path for a given language.
	 *
	 * @since 0.2.0
	 *
	 * @param string $language Language code.
	 *
	 * @return string Shop path.
	 */
	public static function get_default_shop_path_for_language( $language ) {
		$cache_key = 'default_shop_path_' . $language;

		if ( isset( self::$language_cache[ $cache_key ] ) ) {
			return self::$language_cache[ $cache_key ];
		}

		$shop_path = self::get_shop_path( $language );

		if ( empty( $shop_path ) ) {
			$shop_path = 'shop';
		}

		self::$language_cache[ $cache_key ] = $shop_path;
		return $shop_path;
	}

	/**
	 * Build gallery HTML with proper error handling.
	 *
	 * @since 0.2.0
	 *
	 * @param array  $images Gallery images.
	 * @param string $layout Gallery layout type.
	 * @param string $main_image_url Main image URL.
	 *
	 * @return string Gallery HTML markup.
	 */
	public static function build_gallery_html( $images, $layout = 'standard', $main_image_url = '' ) {
		if ( empty( $images ) || ! is_array( $images ) ) {
			return '';
		}

		try {
			switch ( $layout ) {
				case 'thumbnails-below':
					return self::build_gallery_thumbnails_below( $images, $main_image_url );

				case 'thumbnails-side':
					return self::build_gallery_thumbnails_side( $images, $main_image_url );

				case 'standard':
				default:
					return self::build_gallery_standard( $images );
			}
		} catch ( Exception $e ) {
			self::log_error( 'Error building gallery HTML', array(
				'layout' => $layout,
				'error'  => $e->getMessage(),
			) );
			return '';
		}
	}

	/**
	 * Build standard gallery layout.
	 *
	 * @since 0.2.0
	 *
	 * @param array $images Gallery images.
	 *
	 * @return string Gallery HTML.
	 */
	private static function build_gallery_standard( $images ) {
		$html = '<div class="ecwid-gallery ecwid-gallery-standard">';
		foreach ( $images as $image ) {
			$html .= sprintf( '<img src="%s" alt="%s" loading="lazy">', esc_url( $image['url'] ), esc_attr( $image['alt'] ?? '' ) );
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * Build gallery with thumbnails below main image.
	 *
	 * @since 0.2.0
	 *
	 * @param array  $images Gallery images.
	 * @param string $main_image_url Main image URL.
	 *
	 * @return string Gallery HTML.
	 */
	private static function build_gallery_thumbnails_below( $images, $main_image_url ) {
		$html  = '<div class="ecwid-gallery ecwid-gallery-thumbnails-below">';
		$html .= '<div class="ecwid-gallery-main">';
		$html .= sprintf( '<img src="%s" alt="" loading="lazy">', esc_url( $main_image_url ) );
		$html .= '</div>';
		$html .= '<div class="ecwid-gallery-thumbnails">';

		foreach ( $images as $image ) {
			$html .= sprintf(
				'<img src="%s" alt="%s" class="ecwid-gallery-thumbnail" loading="lazy">',
				esc_url( $image['url'] ),
				esc_attr( $image['alt'] ?? '' )
			);
		}

		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Build gallery with thumbnails on the side.
	 *
	 * @since 0.2.0
	 *
	 * @param array  $images Gallery images.
	 * @param string $main_image_url Main image URL.
	 *
	 * @return string Gallery HTML.
	 */
	private static function build_gallery_thumbnails_side( $images, $main_image_url ) {
		$html  = '<div class="ecwid-gallery ecwid-gallery-thumbnails-side">';
		$html .= '<div class="ecwid-gallery-thumbnails">';

		foreach ( $images as $image ) {
			$html .= sprintf(
				'<img src="%s" alt="%s" class="ecwid-gallery-thumbnail" loading="lazy">',
				esc_url( $image['url'] ),
				esc_attr( $image['alt'] ?? '' )
			);
		}

		$html .= '</div>';
		$html .= '<div class="ecwid-gallery-main">';
		$html .= sprintf( '<img src="%s" alt="" loading="lazy">', esc_url( $main_image_url ) );
		$html .= '</div>';
		$html .= '</div>';

		// Add JavaScript for gallery interaction
		$html .= self::get_gallery_javascript();

		return $html;
	}

	/**
	 * Get gallery interaction JavaScript.
	 *
	 * @since 0.2.0
	 *
	 * @return string JavaScript code.
	 */
	private static function get_gallery_javascript() {
		return '<script>
		document.addEventListener("DOMContentLoaded", function() {
			const thumbnails = document.querySelectorAll(".ecwid-gallery-thumbnail");
			const mainImage = document.querySelector(".ecwid-gallery-main img");

			if (thumbnails && mainImage) {
				thumbnails.forEach(function(thumb) {
					thumb.addEventListener("click", function() {
						mainImage.src = this.src;
					});
				});
			}
		});
		</script>';
	}

	/**
	 * Get plugin settings with caching.
	 *
	 * @since 0.2.0
	 *
	 * @param bool $force_refresh Force refresh of cache.
	 *
	 * @return array Plugin settings.
	 */
	public static function get_plugin_settings( $force_refresh = false ) {
		if ( ! $force_refresh && null !== self::$settings_cache ) {
			return self::$settings_cache;
		}

		$settings = get_option( 'peaches_ecwid_blocks_settings', array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		self::$settings_cache = $settings;
		return $settings;
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
	public static function log_error( $message, $context = array() ) {
		if ( ! is_string( $message ) ) {
			$message = 'Non-string error message provided';
		}

		$log_message = '[Peaches Ecwid Blocks] ' . $message;

		if ( ! empty( $context ) && is_array( $context ) ) {
			$log_message .= ' - Context: ' . wp_json_encode( $context );
		}

		error_log( $log_message );
	}

	/**
	 * Validate and sanitize URL.
	 *
	 * @since 0.2.0
	 *
	 * @param string $url           URL to validate.
	 * @param bool   $require_https Whether to require HTTPS.
	 *
	 * @return string|false Validated URL or false if invalid.
	 */
	public static function validate_url( $url, $require_https = false ) {
		if ( ! is_string( $url ) || empty( $url ) ) {
			return false;
		}

		$url = esc_url_raw( $url );

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		if ( $require_https && strpos( $url, 'https://' ) !== 0 ) {
			return false;
		}

		return $url;
	}

	/**
	 * Format file size in human readable format.
	 *
	 * @since 0.2.0
	 *
	 * @param int $bytes     Number of bytes.
	 * @param int $precision Decimal precision.
	 *
	 * @return string Formatted file size.
	 */
	public static function format_file_size( $bytes, $precision = 2 ) {
		$bytes = max( $bytes, 0 );
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB' );

		for ( $i = 0; $bytes > 1024 && $i < count( $units ) - 1; $i++ ) {
			$bytes /= 1024;
		}

		return round( $bytes, $precision ) . ' ' . $units[ $i ];
	}

	/**
	 * Clear all utility caches.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public static function clear_caches() {
		self::$settings_cache = null;
		self::$language_cache = array();
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @since 0.2.0
	 *
	 * @return bool True if debug mode is enabled.
	 */
	public static function is_debug_mode() {
		$settings = self::get_plugin_settings();
		return ! empty( $settings['debug_mode'] );
	}

	/**
	 * Validate media type from file extension or MIME type.
	 *
	 * @since 0.2.0
	 *
	 * @param string $file_path_or_url File path or URL.
	 * @param string $mime_type        Optional MIME type.
	 *
	 * @return string Media type (image, video, audio, document).
	 */
	public static function get_media_type( $file_path_or_url, $mime_type = '' ) {
		// Check MIME type first if provided
		if ( ! empty( $mime_type ) ) {
			if ( strpos( $mime_type, 'image/' ) === 0 ) {
				return 'image';
			}
			if ( strpos( $mime_type, 'video/' ) === 0 ) {
				return 'video';
			}
			if ( strpos( $mime_type, 'audio/' ) === 0 ) {
				return 'audio';
			}
			if ( strpos( $mime_type, 'application/pdf' ) === 0 || strpos( $mime_type, 'text/' ) === 0 ) {
				return 'document';
			}
		}

		// Parse file extension
		$extension = strtolower( pathinfo( $file_path_or_url, PATHINFO_EXTENSION ) );

		$type_map = array(
			'image'    => array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff' ),
			'video'    => array( 'mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv', 'm4v', '3gp', 'mkv' ),
			'audio'    => array( 'mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma' ),
			'document' => array( 'pdf', 'doc', 'docx', 'txt', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx' ),
		);

		foreach ( $type_map as $type => $extensions ) {
			if ( in_array( $extension, $extensions, true ) ) {
				return $type;
			}
		}

		// Check for video hosting patterns
		if ( preg_match( '/(?:youtube\.com|youtu\.be|vimeo\.com|wistia\.com)/i', $file_path_or_url ) ) {
			return 'video';
		}

		// Default to image
		return 'image';
	}
}
