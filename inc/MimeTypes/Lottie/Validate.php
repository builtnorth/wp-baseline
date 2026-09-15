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

use BuiltNorth\WPBaseline\MimeTypes\Capability;

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

		if (!Capability::current_user_can_upload('lottie')) {
			$file['error'] = __('You do not have permission to upload Lottie files.', 'wp-baseline');
			return $file;
		}

		// Check file size limit
		$max_size = apply_filters('wpbaseline_lottie_max_file_size', 10 * 1024 * 1024); // 10MB default
		if ($file['size'] > $max_size) {
			$file['error'] = sprintf(
				__('Lottie file is too large. Maximum size is %s.', 'wp-baseline'),
				size_format($max_size)
			);
			return $file;
		}

		// Validate file structure
		if (!$this->validate_lottie_structure($file)) {
			$file['error'] = __('Invalid Lottie file format.', 'wp-baseline');
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

		if ('lottie' !== $extension) {
			return false;
		}

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

		// Not JSON: a .lottie file is otherwise a dotLottie archive, i.e. a
		// real ZIP containing a manifest.json and an animations/ directory.
		// A prior version accepted any non-JSON file with content > 0 here
		// -- any arbitrary binary renamed to .lottie passed unchecked, since
		// wp-baseline is what registers this extension as an allowed upload
		// mime type in the first place. Validate the archive structure
		// instead of trusting the extension.
		return $this->validate_lottie_archive($file['tmp_name']);
	}

	/**
	 * Validate that a file is a real dotLottie archive (ZIP containing
	 * manifest.json and an animations/ directory), not just any binary
	 * wearing a .lottie extension.
	 *
	 * @param string $tmp_name Path to the uploaded file.
	 * @return bool True if the archive has the expected dotLottie structure.
	 */
	private function validate_lottie_archive($tmp_name)
	{
		if (!class_exists('ZipArchive')) {
			// Fail closed: without ext-zip there is no way to verify the
			// archive structure, and the prior behavior of accepting any
			// non-empty file is the gap being fixed here.
			return false;
		}

		$zip = new \ZipArchive();
		$opened = $zip->open($tmp_name, \ZipArchive::RDONLY);

		if (true !== $opened) {
			return false;
		}

		$has_manifest = false;
		$has_animations = false;

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entry_name = $zip->getNameIndex($i);

			if ('manifest.json' === $entry_name) {
				$has_manifest = true;
			}

			if (0 === strpos($entry_name, 'animations/')) {
				$has_animations = true;
			}
		}

		$zip->close();

		return $has_manifest && $has_animations;
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
