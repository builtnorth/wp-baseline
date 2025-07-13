<?php

/**
 * ------------------------------------------------------------------
 * Lottie Validation
 * ------------------------------------------------------------------
 * 
 * Validate Lottie animation file uploads.
 *
 * @package WPBaseline
 * @since 2.3.0
 */

namespace BuiltNorth\WPBaseline\MimeTypes\Lottie;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class Validate
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		// Only add validation if enabled (default: true)
		if (apply_filters('wpbaseline_validate_lottie_uploads', true)) {
			add_filter('wp_handle_upload_prefilter', [$this, 'validate_lottie_files']);
		}
	}

	/**
	 * Validate Lottie files before upload.
	 * 
	 * @param array $file File upload data.
	 * @return array Modified file data.
	 */
	public function validate_lottie_files($file)
	{
		// Check if this is a Lottie file
		if (!$this->is_lottie_file($file)) {
			return $file;
		}

		// Check file size limit
		$max_size = apply_filters('wpbaseline_lottie_max_file_size', 10 * 1024 * 1024); // 10MB default
		if ($file['size'] > $max_size) {
			$file['error'] = sprintf(
				__('Lottie file is too large. Maximum size is %s.', 'built-wp-baseline'),
				size_format($max_size)
			);
			return $file;
		}

		// Validate file structure
		if (!$this->validate_lottie_structure($file)) {
			$file['error'] = __('Invalid Lottie file format.', 'built-wp-baseline');
			return $file;
		}

		return $file;
	}

	/**
	 * Check if the file is a Lottie file based on extension and content.
	 * 
	 * @param array $file File upload data.
	 * @return bool True if it's a Lottie file.
	 */
	private function is_lottie_file($file)
	{
		$filename = $file['name'];
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		
		// Only validate files with .lottie extension
		// This avoids interfering with regular JSON uploads
		return $extension === 'lottie';
	}

	/**
	 * Check if JSON content has Lottie structure.
	 * 
	 * @param string $content File content.
	 * @return bool True if it appears to be Lottie JSON.
	 */
	private function has_lottie_json_structure($content)
	{
		// Decode JSON
		$data = json_decode($content, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}

		// Check for Lottie-specific properties
		$lottie_indicators = [
			'v', // version
			'fr', // frameRate
			'ip', // inPoint
			'op', // outPoint
			'w', // width
			'h', // height
			'layers', // layers array
			'assets', // assets array
		];

		$found_indicators = 0;
		foreach ($lottie_indicators as $indicator) {
			if (isset($data[$indicator])) {
				$found_indicators++;
			}
		}

		// If we find at least 3 Lottie indicators, it's likely a Lottie file
		return $found_indicators >= 3;
	}

	/**
	 * Validate Lottie file structure.
	 * 
	 * @param array $file File upload data.
	 * @return bool True if structure is valid.
	 */
	private function validate_lottie_structure($file)
	{
		$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		
		// .lottie files can be either JSON or compressed (ZIP) format
		if ($extension === 'lottie') {
			// Try to read as JSON first
			$content = file_get_contents($file['tmp_name']);
			if ($content === false) {
				return false;
			}
			
			// Check if it's JSON format
			$data = json_decode($content, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
				// It's JSON format, validate as Lottie JSON
				return $this->validate_lottie_json($data);
			}
			
			// If not JSON, it might be a compressed .lottie file (ZIP format)
			// For now, we'll accept it if it's not JSON but has content
			// More sophisticated ZIP validation could be added later
			return strlen($content) > 0;
		}

		return false;
	}

	/**
	 * Validate Lottie JSON structure.
	 * 
	 * @param array $data Decoded JSON data.
	 * @return bool True if valid Lottie JSON.
	 */
	private function validate_lottie_json($data)
	{
		// Check for required top-level properties
		if (!is_array($data)) {
			return false;
		}

		// More flexible validation - check for common Lottie properties
		$has_version = isset($data['v']);
		$has_layers = isset($data['layers']) && is_array($data['layers']);
		
		// At minimum, a Lottie file should have version and layers
		if (!$has_version || !$has_layers) {
			return false;
		}

		// Check for some common properties (not all required)
		$common_props = ['fr', 'ip', 'op', 'w', 'h'];
		$found_props = 0;
		
		foreach ($common_props as $prop) {
			if (isset($data[$prop])) {
				$found_props++;
			}
		}
		
		// If it has version, layers, and at least 2 other common properties, it's likely valid
		return $found_props >= 2;
	}
}