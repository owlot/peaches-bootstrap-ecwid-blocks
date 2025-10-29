<?php
/**
 * Handles block registration with modern WordPress methods and multilingual support
 *
 * This class uses the recommended WordPress block registration methods while
 * adding multilingual capabilities for supported blocks.
 *
 * @package PeachesBootstrapEcwidBlocks
 * @since   0.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Peaches_Ecwid_Block_Registration
 *
 * Implements modern block registration with multilingual integration.
 *
 * @package    PeachesBootstrapEcwidBlocks
 * @since      0.3.0
 * @implements Peaches_Ecwid_Block_Registration_Interface
 */
class Peaches_Ecwid_Block_Registration implements Peaches_Ecwid_Block_Registration_Interface {

	/**
	 * List of block types to register.
	 *
	 * @since  0.3.0
	 * @access private
	 * @var    array
	 */
	private $block_types = array(
		'ecwid-category',
		'ecwid-category-products',
		'ecwid-product',
		'ecwid-product-add-to-cart',
		'ecwid-product-description',
		'ecwid-product-detail',
		'ecwid-product-field',
		'ecwid-product-gallery-image',
		'ecwid-product-images',
		'ecwid-product-ingredients',
		'ecwid-product-related-products',
		'mollie-subscription',
	);

	/**
	 * Cache for registered block types to avoid duplicate registrations
	 *
	 * @since  0.4.5
	 * @access private
	 * @var    array
	 */
	private $registered_blocks = array();

	/**
	 * Multilingual configuration for blocks that support translations.
	 *
	 * @since  0.3.3
	 * @access private
	 * @var    array
	 */
	private $multilingual_blocks = array(
		'ecwid-product'             => array(
			'buttonText' => array(
				'method'   => 'button_content',
				'selector' => 'button.add-to-cart',
				'label'    => 'Button Text',
			),
		),
		'ecwid-product-add-to-cart' => array(
			'buttonText'     => array(
				'method'   => 'button_content',
				'selector' => 'button.add-to-cart',
				'label'    => 'Button Text',
			),
			'outOfStockText' => array(
				'method'   => 'button_content',
				'selector' => 'button.add-to-cart',
				'label'    => 'Out of Stock Text',
			),
		),
	);

	/**
	 * Constructor.
	 *
	 * Hooks into WordPress init action to register blocks and multilingual support.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_blocks' ), 20 );
		add_action( 'peaches_multilingual_init', array( $this, 'register_multilingual_blocks' ), 10 );
		add_filter( 'peaches_multilingual_js_config', array( $this, 'add_js_multilingual_config' ) );
		add_filter( 'render_block', array( $this, 'apply_block_translations' ), 10, 2 );
	}

	/**
	 * Register all block types.
	 *
	 * Uses wp_register_block_metadata_collection and register_block_type_from_metadata
	 * for optimal performance and automatic asset handling.
	 *
	 * @since  0.3.3
	 * @return void
	 */
	public function register_blocks() {
		// Performance monitoring start.
		$start_time = microtime( true );

		try {
			// Register block metadata collection for better performance.
			$this->register_block_metadata_collection();

			// Register individual block types.
			$this->register_individual_blocks();

			// Log performance if debugging is enabled.
			$execution_time = ( microtime( true ) - $start_time ) * 1000; // Convert to milliseconds.
			$this->log_info(
				sprintf(
					'Registered %d blocks in %.2fms',
					count( $this->block_types ),
					$execution_time
				)
			);

		} catch ( Exception $e ) {
			// Log error but don't break the site.
			$this->log_error( 'Error registering blocks - ' . $e->getMessage() );
		}
	}

	/**
	 * Register block metadata collection for improved performance
	 *
	 * @since  0.4.5
	 * @return void
	 * @throws Exception If metadata collection registration fails.
	 */
	private function register_block_metadata_collection() {
		$manifest_path = PEACHES_ECWID_PLUGIN_DIR . 'dist/blocks-manifest.php';
		$dist_path     = PEACHES_ECWID_PLUGIN_DIR . 'dist';

		// Verify paths exist before attempting registration.
		if ( ! file_exists( $dist_path ) ) {
			throw new Exception( 'Distribution directory not found: ' . $dist_path );
		}

		if ( ! file_exists( $manifest_path ) ) {
			throw new Exception( 'Manifest file not found: ' . $manifest_path );
		}

		// Register the metadata collection.
		if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
			wp_register_block_metadata_collection( $dist_path, $manifest_path );
		}
	}

	/**
	 * Register individual block types with validation
	 *
	 * @since  0.4.5
	 * @return void
	 */
	private function register_individual_blocks() {
		foreach ( $this->block_types as $block_type ) {
			$this->register_single_block( $block_type );
		}
	}

	/**
	 * Register a single block type with validation and error handling
	 *
	 * @since  0.4.5
	 * @param  string $block_type The block type identifier.
	 * @return void
	 */
	private function register_single_block( $block_type ) {
		// Skip if already registered.
		if ( isset( $this->registered_blocks[ $block_type ] ) ) {
			return;
		}

		$block_path = PEACHES_ECWID_PLUGIN_DIR . 'build/' . $block_type;

		// Validate block path exists.
		if ( ! is_dir( $block_path ) ) {
			$this->log_info( 'Block directory not found: ' . $block_path );
			return;
		}

		// Validate block.json exists.
		$block_json_path = $block_path . '/block.json';
		if ( ! file_exists( $block_json_path ) ) {
			$this->log_info( 'block.json not found: ' . $block_json_path );
			return;
		}

		// Register the block.
		try {
			$result = register_block_type_from_metadata( $block_path );

			if ( $result instanceof WP_Block_Type ) {
				$this->registered_blocks[ $block_type ] = true;
			}
		} catch ( Exception $e ) {
			$this->log_error( 'Failed to register ' . $block_type . ' - ' . $e->getMessage() );
		}
	}

	/**
	 * Get list of successfully registered blocks
	 *
	 * @since  0.3.3
	 * @return array Array of registered block type names.
	 */
	public function get_registered_blocks() {
		return array_keys( $this->registered_blocks );
	}

	/**
	 * Check if a specific block type is registered
	 *
	 * @since  0.4.5
	 * @param  string $block_type Block type to check.
	 * @return bool True if registered, false otherwise.
	 */
	public function is_block_registered( $block_type ) {
		return isset( $this->registered_blocks[ $block_type ] );
	}

	/**
	 * Register blocks for multilingual support.
	 *
	 * This method is called when the multilingual system is initialized.
	 *
	 * @since  0.3.3
	 * @return void
	 */
	public function register_multilingual_blocks() {
		// Check if multilingual registry is available.
		if ( ! class_exists( 'Peaches_Multilingual_Block_Registry' ) ) {
			return;
		}

		$registry = Peaches_Multilingual_Block_Registry::get_instance();

		foreach ( $this->multilingual_blocks as $block_name => $multilingual_config ) {
			$full_block_name = 'peaches-ecwid/' . $block_name;

			// Convert labels to translatable strings.
			$processed_config = array();
			foreach ( $multilingual_config as $attr_name => $attr_config ) {
				$processed_config[ $attr_name ]           = $attr_config;
				$processed_config[ $attr_name ]['label'] = __( $attr_config['label'], 'peaches' );
			}

			// Register the block with the multilingual registry.
			$success = $registry->register_block( $full_block_name, $processed_config );

			// Log registration for debugging.
			if ( $success ) {
				$this->log_info( 'Registered multilingual support for ' . $full_block_name );
			} else {
				$this->log_error( 'Failed to register multilingual support for ' . $full_block_name );
			}
		}
	}

	/**
	 * Add JavaScript multilingual configuration.
	 *
	 * @since  0.3.3
	 * @param  array $config Existing multilingual configuration.
	 * @return array Modified configuration.
	 */
	public function add_js_multilingual_config( $config ) {
		if ( ! isset( $config['registeredBlocks'] ) ) {
			$config['registeredBlocks'] = array();
		}

		foreach ( $this->multilingual_blocks as $block_name => $multilingual_config ) {
			$full_block_name = 'peaches-ecwid/' . $block_name;

			// Add to JavaScript configuration.
			$config['registeredBlocks'][ $full_block_name ] = array(
				'attributes' => array(),
			);

			foreach ( $multilingual_config as $attr_name => $attr_config ) {
				$config['registeredBlocks'][ $full_block_name ]['attributes'][ $attr_name ] = array(
					'label' => __( $attr_config['label'], 'peaches' ),
				);
			}
		}

		return $config;
	}

	/**
	 * Apply translations to Ecwid blocks during rendering.
	 *
	 * @since  0.3.3
	 * @param  string $block_content The block content.
	 * @param  array  $block         The full block, including name and attributes.
	 * @return string Modified block content.
	 */
	public function apply_block_translations( $block_content, $block ) {
		// Ensure block_content is a string.
		if ( null === $block_content ) {
			$block_content = '';
		}

		// Validate block array and blockName - fix for PHP 8.1+ null parameter warnings.
		if ( ! is_array( $block ) || empty( $block['blockName'] ) || ! is_string( $block['blockName'] ) ) {
			return (string) $block_content;
		}

		// Only process our Ecwid blocks.
		if ( 0 !== strpos( $block['blockName'], 'peaches-ecwid/' ) ) {
			return (string) $block_content;
		}

		// Check if multilingual registry is available.
		if ( ! class_exists( 'Peaches_Multilingual_Block_Registry' ) ) {
			return (string) $block_content;
		}

		// Check if this block has multilingual support.
		$block_key = str_replace( 'peaches-ecwid/', '', $block['blockName'] );
		if ( ! isset( $this->multilingual_blocks[ $block_key ] ) ) {
			return (string) $block_content;
		}

		$registry = Peaches_Multilingual_Block_Registry::get_instance();
		$result   = $registry->apply_translations( (string) $block_content, $block['attrs'] ?? array(), $block['blockName'] );

		return (string) $result;
	}

	/**
	 * Check if a block has multilingual support.
	 *
	 * @since  0.3.3
	 * @param  string $block_name Block name (without namespace).
	 * @return bool True if block has multilingual support.
	 */
	public function has_multilingual_support( $block_name ) {
		return isset( $this->multilingual_blocks[ $block_name ] );
	}

	/**
	 * Get multilingual configuration for a specific block.
	 *
	 * @since  0.3.3
	 * @param  string $block_name Block name (without namespace).
	 * @return array|false Multilingual configuration or false if not found.
	 */
	public function get_block_multilingual_config( $block_name ) {
		return $this->multilingual_blocks[ $block_name ] ?? false;
	}

	/**
	 * Log informational messages.
	 *
	 * @since  0.3.3
	 * @param  string $message Log message.
	 * @param  array  $context Additional context data.
	 * @return void
	 */
	private function log_info( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) && Peaches_Ecwid_Utilities::is_debug_mode() ) {
			Peaches_Ecwid_Utilities::log_error( '[INFO] [Block Registration] ' . $message, $context );
		}
	}

	/**
	 * Log error messages.
	 *
	 * @since  0.3.3
	 * @param  string $message Error message.
	 * @param  array  $context Additional context data.
	 * @return void
	 */
	private function log_error( $message, $context = array() ) {
		if ( class_exists( 'Peaches_Ecwid_Utilities' ) ) {
			Peaches_Ecwid_Utilities::log_error( '[Block Registration] ' . $message, $context );
		}
	}
}
