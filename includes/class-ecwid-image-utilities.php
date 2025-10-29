<?php
/**
 * Ecwid Image Utilities Class
 *
 * Shared utilities for Ecwid image processing across the plugin.
 * Consolidates image detection, type determination, and URL manipulation.
 *
 * @package PeachesBootstrapEcwidBlocks
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Ecwid_Image_Utilities
 *
 * Static utility class for Ecwid image operations.
 *
 * @package PeachesBootstrapEcwidBlocks
 * @since   1.0.0
 */
class Peaches_Ecwid_Image_Utilities {

	/**
	 * Ecwid domains for image detection
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private static $ecwid_domains = array(
		'images-cdn.ecwid.com',
		'd2j6dbq0eux0bg.cloudfront.net',
		'app.ecwid.com',
		'ecwid.com',
	);

	/**
	 * Check if the image URL is from Ecwid
	 *
	 * @since  1.0.0
	 * @param  string $url Image URL to check.
	 * @return bool True if Ecwid image.
	 */
	public static function is_ecwid_image( $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		$parsed_url = wp_parse_url( $url );
		if ( ! isset( $parsed_url['host'] ) ) {
			return false;
		}

		$host = strtolower( $parsed_url['host'] );

		foreach ( self::$ecwid_domains as $domain ) {
			if ( $host === $domain || false !== strpos( $host, $domain ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine media type from URL and optional MIME type
	 *
	 * @since  1.0.0
	 * @param  string $url       Media URL.
	 * @param  string $mime_type Optional MIME type.
	 * @return string Media type (image, video, audio, document).
	 */
	public static function determine_media_type( $url, $mime_type = '' ) {
		if ( ! empty( $mime_type ) ) {
			if ( 0 === strpos( $mime_type, 'image/' ) ) {
				return 'image';
			}
			if ( 0 === strpos( $mime_type, 'video/' ) ) {
				return 'video';
			}
			if ( 0 === strpos( $mime_type, 'audio/' ) ) {
				return 'audio';
			}
			return 'document';
		}

		// Fallback to URL extension.
		$extension = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );

		$image_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp' );
		$video_extensions = array( 'mp4', 'webm', 'ogg', 'avi', 'mov', 'wmv', 'flv' );
		$audio_extensions = array( 'mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a' );

		if ( in_array( $extension, $image_extensions, true ) ) {
			return 'image';
		}
		if ( in_array( $extension, $video_extensions, true ) ) {
			return 'video';
		}
		if ( in_array( $extension, $audio_extensions, true ) ) {
			return 'audio';
		}

		return 'document';
	}

	/**
	 * Get Ecwid store ID from image URL
	 *
	 * @since  1.0.0
	 * @param  string $image_url Ecwid image URL.
	 * @return string|false Store ID or false if not detectable.
	 */
	public static function get_ecwid_store_id( $image_url ) {
		if ( ! self::is_ecwid_image( $image_url ) ) {
			return false;
		}

		// Extract store ID from various Ecwid URL patterns.
		$patterns = array(
			'/\/(\d+)\//',        // Simple numeric ID in path.
			'/store-(\d+)/',      // store-12345 pattern.
			'/storeId[=:](\d+)/', // storeId=12345 or storeId:12345.
			'/[?&]store=(\d+)/',  // ?store=12345 parameter.
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $image_url, $matches ) ) {
				return $matches[1];
			}
		}

		return false;
	}

	/**
	 * Generate Ecwid responsive image srcset using actual API data
	 *
	 * Uses the real image sizes provided by Ecwid API instead of guessing URLs.
	 *
	 * @since  1.1.0
	 * @param  object $product  Ecwid product object with image data.
	 * @param  int    $position Image position (0 = main, 1+ = gallery).
	 * @param  string $context  Context for sizes optimization (hero, gallery, thumbnail, etc.).
	 * @return array|null Image data with srcset and sizes, or null if not found.
	 */
	public static function generate_ecwid_image_data( $product, $position = 0, $context = 'gallery' ) {
		if ( ! $product ) {
			return null;
		}

		$position   = intval( $position );
		$image_data = null;

		// Try new media.images structure first (preferred).
		if ( isset( $product->media ) && isset( $product->media->images ) && is_array( $product->media->images ) ) {
			if ( isset( $product->media->images[ $position ] ) ) {
				$image_data = $product->media->images[ $position ];
			}
		} else {
			// Fallback to legacy structure.
			if ( 0 === $position ) {
				// Main image - construct from main product image fields.
				$image_data = self::create_legacy_main_image_data( $product );
			} else {
				// Gallery image.
				$gallery_index = $position - 1;
				if ( isset( $product->galleryImages ) &&
					is_array( $product->galleryImages ) &&
					isset( $product->galleryImages[ $gallery_index ] ) ) {
					$image_data = $product->galleryImages[ $gallery_index ];
				}
			}
		}

		if ( ! $image_data ) {
			return null;
		}

		return self::process_ecwid_image_data( $image_data, $context );
	}

	/**
	 * Process Ecwid image data into srcset and sizes
	 *
	 * @since  1.1.0
	 * @param  object $image_data Ecwid image object.
	 * @param  string $context    Context for sizes optimization.
	 * @return array Processed image data.
	 */
	private static function process_ecwid_image_data( $image_data, $context = 'gallery' ) {
		$srcset_parts    = array();
		$available_sizes = array();

		// Map of Ecwid image size fields to their approximate widths.
		$size_mapping = array(
			'image160pxUrl'     => 160,
			'image400pxUrl'     => 400,
			'image800pxUrl'     => 800,
			'image1500pxUrl'    => 1500,
			'imageOriginalUrl'  => 2000, // Estimate, could be larger.
			// Legacy field mappings.
			'thumbnailUrl'      => 160,
			'smallThumbnailUrl' => 80,
			'hdThumbnailUrl'    => 400,
			'imageUrl'          => 800,
			'originalImageUrl'  => 2000,
		);

		// Check which sizes are actually available.
		foreach ( $size_mapping as $field => $width ) {
			if ( isset( $image_data->{$field} ) && ! empty( $image_data->{$field} ) ) {
				$srcset_parts[]              = $image_data->{$field} . ' ' . $width . 'w';
				$available_sizes[ $width ] = $image_data->{$field};
			}
		}

		// Sort by width.
		ksort( $available_sizes );

		// Get the best default image based on context.
		$default_url = self::get_best_default_image( $available_sizes, $context );

		return array(
			'url'             => $default_url,
			'srcset'          => implode( ', ', $srcset_parts ),
			'alt'             => isset( $image_data->alt ) ? $image_data->alt : '',
			'width'           => isset( $image_data->width ) ? $image_data->width : null,
			'height'          => isset( $image_data->height ) ? $image_data->height : null,
			'available_sizes' => $available_sizes,
		);
	}

	/**
	 * Create legacy main image data from product fields
	 *
	 * @since  1.1.0
	 * @param  object $product Ecwid product object.
	 * @return object|null Legacy image data object.
	 */
	private static function create_legacy_main_image_data( $product ) {
		$image_data = new stdClass();

		// Map legacy main image fields.
		if ( isset( $product->thumbnailUrl ) ) {
			$image_data->thumbnailUrl  = $product->thumbnailUrl;
			$image_data->image160pxUrl = $product->thumbnailUrl; // Usually 160px.
		}

		if ( isset( $product->smallThumbnailUrl ) ) {
			$image_data->smallThumbnailUrl = $product->smallThumbnailUrl;
		}

		if ( isset( $product->hdThumbnailUrl ) ) {
			$image_data->hdThumbnailUrl = $product->hdThumbnailUrl;
			$image_data->image400pxUrl  = $product->hdThumbnailUrl; // Usually 400px.
		}

		if ( isset( $product->imageUrl ) ) {
			$image_data->imageUrl      = $product->imageUrl;
			$image_data->image800pxUrl = $product->imageUrl; // Usually 800px.
		}

		if ( isset( $product->originalImageUrl ) ) {
			$image_data->originalImageUrl = $product->originalImageUrl;
			$image_data->imageOriginalUrl = $product->originalImageUrl;
		}

		// Check if we have at least one image.
		if ( isset( $image_data->thumbnailUrl ) || isset( $image_data->imageUrl ) ) {
			return $image_data;
		}

		return null;
	}

	/**
	 * Get the best default image URL based on context
	 *
	 * @since  1.1.0
	 * @param  array  $available_sizes Array of width => URL mappings.
	 * @param  string $context         Context (hero, gallery, thumbnail, etc.).
	 * @return string Best default image URL.
	 */
	private static function get_best_default_image( $available_sizes, $context ) {
		if ( empty( $available_sizes ) ) {
			return '';
		}

		// Define preferred widths for different contexts.
		$preferred_widths = array(
			'hero'      => 800,
			'gallery'   => 400,
			'thumbnail' => 160,
			'product'   => 400,
			'default'   => 400,
		);

		$target_width = isset( $preferred_widths[ $context ] ) ? $preferred_widths[ $context ] : $preferred_widths['default'];

		// Find the closest available size.
		$closest_width  = null;
		$min_difference = PHP_INT_MAX;

		foreach ( array_keys( $available_sizes ) as $width ) {
			$difference = abs( $width - $target_width );
			if ( $difference < $min_difference ) {
				$min_difference = $difference;
				$closest_width  = $width;
			}
		}

		return $available_sizes[ $closest_width ];
	}

	/**
	 * Updated generate_ecwid_srcset method that tries to use product data
	 *
	 * @since  1.1.0
	 * @param  object $product  Product data to use for real sizes.
	 * @param  int    $position Image position if product data is provided.
	 * @param  string $context  Context for image generation.
	 * @return string Srcset attribute value.
	 */
	public static function generate_ecwid_srcset( $product, $position = 0, $context = 'gallery' ) {
		$image_data = self::generate_ecwid_image_data( $product, $position, $context );
		return $image_data ? $image_data['srcset'] : '';
	}

	/**
	 * Generate Bootstrap-aware sizes attribute for images
	 *
	 * @since  1.0.0
	 * @param  string $context Context for sizes (gallery, thumbnail, hero, product).
	 * @return string Sizes attribute value.
	 */
	public static function generate_bootstrap_sizes_attribute( $context = 'default' ) {
		switch ( $context ) {
			case 'thumbnail':
				return '(max-width: 575px) 150px, ' .
					'(max-width: 767px) 150px, ' .
					'150px';

			case 'gallery':
				return '(max-width: 575px) 100vw, ' .
					'(max-width: 767px) 50vw, ' .
					'(max-width: 991px) 33vw, ' .
					'(max-width: 1199px) 25vw, ' .
					'(max-width: 1399px) 20vw, ' .
					'300px';

			case 'hero':
				return '(max-width: 575px) 100vw, ' .
					'(max-width: 767px) 100vw, ' .
					'(max-width: 991px) 75vw, ' .
					'(max-width: 1199px) 50vw, ' .
					'600px';

			case 'product':
				return '(max-width: 575px) 100vw, ' .
					'(max-width: 767px) 80vw, ' .
					'(max-width: 991px) 60vw, ' .
					'(max-width: 1199px) 40vw, ' .
					'500px';

			default:
				return '(max-width: 575px) 100vw, ' .
					'(max-width: 767px) 50vw, ' .
					'(max-width: 991px) 33vw, ' .
					'(max-width: 1199px) 25vw, ' .
					'(max-width: 1399px) 20vw, ' .
					'300px';
		}
	}

	/**
	 * Guess MIME type from URL extension
	 *
	 * @since  1.0.0
	 * @param  string $url Media URL.
	 * @return string Guessed MIME type.
	 */
	public static function guess_mime_type_from_url( $url ) {
		$extension = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );

		$mime_types = array(
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'webp' => 'image/webp',
			'svg'  => 'image/svg+xml',
			'bmp'  => 'image/bmp',
			'mp4'  => 'video/mp4',
			'webm' => 'video/webm',
			'ogg'  => 'video/ogg',
			'avi'  => 'video/x-msvideo',
			'mov'  => 'video/quicktime',
			'mp3'  => 'audio/mpeg',
			'wav'  => 'audio/wav',
			'aac'  => 'audio/aac',
			'flac' => 'audio/flac',
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);

		return isset( $mime_types[ $extension ] ) ? $mime_types[ $extension ] : 'application/octet-stream';
	}

	/**
	 * Get responsive image attributes for Ecwid image
	 *
	 * @since  1.0.0
	 * @param  object $product  Product data.
	 * @param  int    $position Image position.
	 * @param  array  $args     Arguments for image generation.
	 * @return array Image attributes array.
	 */
	public static function get_responsive_image_attributes( $product, $position = 0, $args = array() ) {
		$defaults = array(
			'context' => 'gallery',
			'alt'     => '',
			'class'   => '',
			'loading' => 'lazy',
		);

		$args = wp_parse_args( $args, $defaults );

		$image_data = self::generate_ecwid_image_data( $product, $position, $args['context'] );

		if ( ! $image_data ) {
			return array();
		}

		$attributes = array(
			'src'      => $image_data['url'],
			'alt'      => ! empty( $args['alt'] ) ? esc_attr( $args['alt'] ) : esc_attr( $image_data['alt'] ),
			'loading'  => $args['loading'],
			'decoding' => 'async',
		);

		// Add responsive attributes.
		if ( ! empty( $image_data['srcset'] ) ) {
			$attributes['srcset'] = $image_data['srcset'];
			$attributes['sizes']  = self::generate_bootstrap_sizes_attribute( $args['context'] );
		}

		// Add width and height if available.
		if ( ! empty( $image_data['width'] ) ) {
			$attributes['width'] = $image_data['width'];
		}
		if ( ! empty( $image_data['height'] ) ) {
			$attributes['height'] = $image_data['height'];
		}

		// Add responsive class.
		$class = 'peaches-responsive-img peaches-ecwid-img';
		if ( ! empty( $args['class'] ) ) {
			$class .= ' ' . $args['class'];
		}
		$attributes['class'] = $class;

		// Add responsive type.
		$attributes['data-responsive-type'] = 'ecwid';

		// Add store ID if detectable.
		if ( ! empty( $image_data['url'] ) ) {
			$store_id = self::get_ecwid_store_id( $image_data['url'] );
			if ( $store_id ) {
				$attributes['data-ecwid-store-id'] = $store_id;
			}
		}

		return $attributes;
	}

	/**
	 * Generate complete responsive image HTML for Ecwid image
	 *
	 * @since  1.0.0
	 * @param  object $product  Product data.
	 * @param  int    $position Image position.
	 * @param  array  $args     Arguments for image generation.
	 * @return string Complete img HTML tag.
	 */
	public static function get_responsive_image_html( $product, $position = 0, $args = array() ) {
		$attributes = self::get_responsive_image_attributes( $product, $position, $args );

		if ( empty( $attributes ) ) {
			return '';
		}

		$html = '<img';
		foreach ( $attributes as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		$html .= ' />';

		return $html;
	}
}
