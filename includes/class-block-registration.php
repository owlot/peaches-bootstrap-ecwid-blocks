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

if (!defined('ABSPATH')) {
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
		'ecwid-product',
		'ecwid-product-add-to-cart',
		'ecwid-product-description',
		'ecwid-product-detail',
		'ecwid-product-field',
		'ecwid-product-gallery-image',
		'ecwid-product-images',
		'ecwid-product-ingredients',
		'ecwid-product-related-products',
	);

	/**
	 * Multilingual configuration for blocks that support translations.
	 *
	 * @since  0.3.3
	 * @access private
	 * @var    array
	 */
	private $multilingual_blocks = array(
		'ecwid-product' => array(
			'buttonText' => array(
				'method' => 'button_content',
				'selector' => 'button.add-to-cart',
				'label' => 'Button Text',
			),
		),
		'ecwid-product-add-to-cart' => array(
			'buttonText' => array(
				'method' => 'button_content',
				'selector' => 'button.add-to-cart',
				'label' => 'Button Text',
			),
			'outOfStockText' => array(
				'method' => 'button_content',
				'selector' => 'button.add-to-cart',
				'label' => 'Out of Stock Text',
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
		add_action('init', array($this, 'register_blocks'), 20);
		add_action('peaches_multilingual_init', array($this, 'register_multilingual_blocks'), 10);
		add_filter('peaches_multilingual_js_config', array($this, 'add_js_multilingual_config'));
		add_filter('render_block', array($this, 'apply_block_translations'), 10, 2);
	}

	/**
	 * Register all block types.
	 *
	 * Uses wp_register_block_metadata_collection and register_block_type_from_metadata
	 * for optimal performance and automatic asset handling.
	 *
	 * @since 0.3.3
	 */
	public function register_blocks() {
		// Register block metadata collection for better performance
		if (function_exists('wp_register_block_metadata_collection')) {
			wp_register_block_metadata_collection(
				PEACHES_ECWID_PLUGIN_DIR . 'dist',
				PEACHES_ECWID_PLUGIN_DIR . 'dist/blocks-manifest.php'
			);
		}

		// Register each block type from metadata.
		foreach ($this->block_types as $block_type) {
			$this->register_single_block($block_type);
		}
	}

	/**
	 * Register a single block using metadata.
	 *
	 * @since 0.3.3
	 *
	 * @param string $block_type The block type name.
	 *
	 * @return void
	 */
	private function register_single_block($block_type) {
		// Try build directory first
		$build_path = PEACHES_ECWID_PLUGIN_DIR . 'build/' . $block_type;
		$src_path = PEACHES_ECWID_PLUGIN_DIR . 'src/' . $block_type;

		// Use the path that exists
		$block_path = file_exists($build_path . '/block.json') ? $build_path : $src_path;

		if (file_exists($block_path . '/block.json')) {
			if (function_exists('register_block_type_from_metadata')) {
				register_block_type_from_metadata($block_path);
			} else {
				// Fallback for older WordPress versions
				register_block_type($block_path);
			}

			$this->log_info("Registered block peaches-ecwid/{$block_type}.");
		} else {
			$this->log_error("Block definition not found for {$block_type}", array(
				'build_path' => $build_path,
				'src_path' => $src_path,
			));
		}
	}

	/**
	 * Register blocks for multilingual support.
	 *
	 * This method is called when the multilingual system is initialized.
	 *
	 * @since 0.3.3
	 *
	 * @return void
	 */
	public function register_multilingual_blocks() {
		// Check if multilingual registry is available
		if (!class_exists('Peaches_Multilingual_Block_Registry')) {
			return;
		}

		$registry = Peaches_Multilingual_Block_Registry::get_instance();

		foreach ($this->multilingual_blocks as $block_name => $multilingual_config) {
			$full_block_name = "peaches-ecwid/{$block_name}";

			// Convert labels to translatable strings
			$processed_config = array();
			foreach ($multilingual_config as $attr_name => $attr_config) {
				$processed_config[$attr_name] = $attr_config;
				$processed_config[$attr_name]['label'] = __($attr_config['label'], 'peaches');
			}

			// Register the block with the multilingual registry
			$success = $registry->register_block($full_block_name, $processed_config);

			// Log registration for debugging
			if ($success) {
				$this->log_info("Registered multilingual support for {$full_block_name}");
			} else {
				$this->log_error("Failed to register multilingual support for {$full_block_name}");
			}
		}
	}

	/**
	 * Add JavaScript multilingual configuration.
	 *
	 * @since 0.3.3
	 *
	 * @param array $config Existing multilingual configuration.
	 *
	 * @return array Modified configuration.
	 */
	public function add_js_multilingual_config($config) {
		if (!isset($config['registeredBlocks'])) {
			$config['registeredBlocks'] = array();
		}

		foreach ($this->multilingual_blocks as $block_name => $multilingual_config) {
			$full_block_name = "peaches-ecwid/{$block_name}";

			// Add to JavaScript configuration
			$config['registeredBlocks'][$full_block_name] = array(
				'attributes' => array(),
			);

			foreach ($multilingual_config as $attr_name => $attr_config) {
				$config['registeredBlocks'][$full_block_name]['attributes'][$attr_name] = array(
					'label' => __($attr_config['label'], 'peaches'),
				);
			}
		}

		return $config;
	}

	/**
	 * Apply translations to Ecwid blocks during rendering.
	 *
	 * @since 0.3.3
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 *
	 * @return string Modified block content.
	 */
	public function apply_block_translations($block_content, $block) {
		// Ensure block_content is a string
		if ($block_content === null) {
			$block_content = '';
		}

		// Validate block array and blockName - fix for PHP 8.1+ null parameter warnings
		if (!is_array($block) || empty($block['blockName']) || !is_string($block['blockName'])) {
			return (string)$block_content;
		}

		// Only process our Ecwid blocks
		if (strpos($block['blockName'], 'peaches-ecwid/') !== 0) {
			return (string)$block_content;
		}

		// Check if multilingual registry is available
		if (!class_exists('Peaches_Multilingual_Block_Registry')) {
			return (string)$block_content;
		}

		// Check if this block has multilingual support
		$block_key = str_replace('peaches-ecwid/', '', $block['blockName']);
		if (!isset($this->multilingual_blocks[$block_key])) {
			return (string)$block_content;
		}

		$registry = Peaches_Multilingual_Block_Registry::get_instance();
		$result = $registry->apply_translations((string)$block_content, $block['attrs'] ?? array(), $block['blockName']);

		return (string)$result;
	}

	/**
	 * Get list of registered block names.
	 *
	 * @since 0.3.3
	 *
	 * @return array Array of block names.
	 */
	public function get_registered_blocks() {
		return $this->block_types;
	}

	/**
	 * Check if a block has multilingual support.
	 *
	 * @since 0.3.3
	 *
	 * @param string $block_name Block name (without namespace).
	 *
	 * @return bool True if block has multilingual support.
	 */
	public function has_multilingual_support($block_name) {
		return isset($this->multilingual_blocks[$block_name]);
	}

	/**
	 * Get multilingual configuration for a specific block.
	 *
	 * @since 0.3.3
	 *
	 * @param string $block_name Block name (without namespace).
	 *
	 * @return array|false Multilingual configuration or false if not found.
	 */
	public function get_block_multilingual_config($block_name) {
		return $this->multilingual_blocks[$block_name] ?? false;
	}

	/**
	 * Log informational messages.
	 *
	 * @since 0.3.3
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_info($message, $context = array()) {
		if (class_exists('Peaches_Ecwid_Utilities') && Peaches_Ecwid_Utilities::is_debug_mode()) {
			Peaches_Ecwid_Utilities::log_error('[INFO] [Block Registration] ' . $message, $context);
		}
	}

	/**
	 * Log error messages.
	 *
	 * @since 0.3.3
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context data.
	 *
	 * @return void
	 */
	private function log_error($message, $context = array()) {
		if (class_exists('Peaches_Ecwid_Utilities')) {
			Peaches_Ecwid_Utilities::log_error('[Block Registration] ' . $message, $context);
		}
	}
}
