<?php
/**
 * Handles pattern registration
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Peaches_Ecwid_Block_Patterns
 *
 * Implements pattern registration functionality.
 *
 * @package PeachesEcwidBlocks
 * @since   0.1.2
 */
class Peaches_Ecwid_Block_Patterns implements Peaches_Ecwid_Block_Patterns_Interface {

	/**
	 * Pattern category slug.
	 *
	 * @var string
	 */
	private $category_slug = 'peaches-ecwid';

	/**
	 * Pattern directory path.
	 *
	 * @var string
	 */
	private $patterns_dir;

	/**
	 * Image directory URL.
	 *
	 * @var string
	 */
	private $image_url;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->patterns_dir = PEACHES_ECWID_PLUGIN_DIR . 'patterns/';
		$this->image_url = PEACHES_ECWID_ASSETS_URL . 'img/';

		// Only register if pattern functions exist
		if (function_exists('register_block_pattern_category') && function_exists('register_block_pattern')) {
			add_action('init', array($this, 'register_pattern_category'));
			add_action('init', array($this, 'register_patterns'));
		}
	}

	/**
	 * Register pattern category.
	 */
	public function register_pattern_category() {
		register_block_pattern_category(
			$this->category_slug,
			array('label' => __('Peaches Ecwid', 'peaches'))
		);
	}

	/**
	 * Register all patterns.
	 */
	public function register_patterns() {
		// Check if directory exists
		if (!is_dir($this->patterns_dir)) {
			error_log('Peaches Ecwid: Patterns directory does not exist: ' . $this->patterns_dir);
			if (!wp_mkdir_p($this->patterns_dir)) {
				return;
			}
		}

		// Get all pattern files
		$pattern_files = glob($this->patterns_dir . '*.php');

		if (!is_array($pattern_files) || empty($pattern_files)) {
			error_log('Peaches Ecwid: No pattern files found in: ' . $this->patterns_dir);
			return;
		}

		foreach ($pattern_files as $pattern_file) {
			try {
				// Extract pattern data
				$pattern_data = $this->get_pattern_data($pattern_file);

				if (empty($pattern_data) || empty($pattern_data['slug'])) {
					error_log('Peaches Ecwid: Invalid pattern data in file: ' . $pattern_file);
					continue;
				}

				// Extra validation for categories
				if (!isset($pattern_data['categories']) || !is_array($pattern_data['categories'])) {
					$pattern_data['categories'] = array($this->category_slug);
				}

				// Register the pattern
				register_block_pattern(
					$pattern_data['slug'],
					array(
						'title'         => $pattern_data['title'],
						'description'   => $pattern_data['description'],
						'categories'    => $pattern_data['categories'], // Make sure this is an array
						'content'       => $pattern_data['content'],
						'viewportWidth' => $pattern_data['viewport_width'],
					)
				);
			} catch (Exception $e) {
				error_log('Peaches Ecwid: Error processing pattern file ' . $pattern_file . ': ' . $e->getMessage());
			}
		}
	}

	/**
	 * Extract pattern data from file.
	 *
	 * @param string $file Pattern file path.
	 * @return array|bool Pattern data or false.
	 */
	private function get_pattern_data($file) {
		if (!file_exists($file) || !is_readable($file)) {
			return false;
		}

		// Get file content
		$content = file_get_contents($file);

		if (false === $content) {
			return false;
		}

		// Extract header data
		preg_match('/Title:\s*(.+)[\r\n]+/i', $content, $title_matches);
		preg_match('/Description:\s*(.+)[\r\n]+/i', $content, $desc_matches);
		preg_match('/Slug:\s*(.+)[\r\n]+/i', $content, $slug_matches);
		preg_match('/Categories:\s*(.+)[\r\n]+/i', $content, $cat_matches);
		preg_match('/Viewport Width:\s*([0-9]+)[\r\n]+/i', $content, $width_matches);

		// Return if required data is missing
		if (empty($title_matches) || empty($slug_matches)) {
			return false;
		}

		// Extract pattern content (everything after the header)
		$pattern_content = '';
		$lines = explode("\n", $content);
		$header_ended = false;

		foreach ($lines as $line) {
			if ($header_ended) {
				$pattern_content .= $line . "\n";
			} elseif (strpos($line, '?>') !== false) {
				$header_ended = true;
			}
		}

		// Process placeholders in the pattern content
		$pattern_content = $this->process_placeholders($pattern_content);

		$categories = array($this->category_slug);

		if (!empty($cat_matches[1])) {
			$additional_cats = array_map('trim', explode(',', $cat_matches[1]));

			// Ensure each category is a valid string
			$additional_cats = array_filter($additional_cats, function($cat) {
				return is_string($cat) && !empty($cat);
			});

			// Merge arrays
			$categories = array_merge($categories, $additional_cats);

			// Remove duplicates and ensure sequential array keys (not associative)
			$categories = array_values(array_unique($categories));
		}

		return array(
			'title'         => trim($title_matches[1]),
			'description'   => isset($desc_matches[1]) ? trim($desc_matches[1]) : '',
			'slug'          => trim($slug_matches[1]),
			'categories'    => $categories, // This must be an array of strings
			'content'       => trim($pattern_content),
			'viewport_width' => isset($width_matches[1]) ? (int) $width_matches[1] : 1200,
		);
	}

	/**
	 * Process placeholder tags in pattern content.
	 *
	 * @param string $content The pattern content.
	 * @return string Processed content.
	 */
	private function process_placeholders($content) {
		if (empty($content)) {
			return '';
		}

		// Ensure the image URL ends with a slash
		$image_url = trailingslashit($this->image_url);

		// Replace {PLUGIN_IMG_URL} placeholder with the actual URL
		$content = str_replace('{PLUGIN_IMG_URL}', esc_url($image_url), $content);

		// Also handle the PHP template tag format with better regex
		$content = preg_replace(
			'/<\?php\s+echo\s+esc_url\(\s*get_theme_file_uri\(\s*[\'"]\/assets\/img\/([^\'"]+)[\'"]\s*\)\s*\);\s*\?>/',
			esc_url($image_url) . '$1',
			$content
		);

		return $content;
	}
}
