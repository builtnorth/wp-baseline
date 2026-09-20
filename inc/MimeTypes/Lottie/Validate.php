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
	 * Also rejects path-traversal entry names and members with
	 * executable-ish extensions, and smoke-checks that the required
	 * JSON members are well-formed (names alone are not enough).
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
		$animation_json_entry = null;

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entry_name = $zip->getNameIndex($i);

			if (false === $entry_name || '' === $entry_name) {
				$zip->close();
				return false;
			}

			// Directory markers are fine; only file members are policed.
			if (str_ends_with($entry_name, '/')) {
				continue;
			}

			if ($this->is_unsafe_zip_entry_name($entry_name)) {
				$zip->close();
				return false;
			}

			if ('manifest.json' === $entry_name) {
				$has_manifest = true;
			}

			if (0 === strpos($entry_name, 'animations/')) {
				$has_animations = true;
				if (null === $animation_json_entry && str_ends_with(strtolower($entry_name), '.json')) {
					$animation_json_entry = $entry_name;
				}
			}
		}

		if (!$has_manifest || !$has_animations || null === $animation_json_entry) {
			$zip->close();
			return false;
		}

		// Names alone aren't enough — required JSON members must decode.
		if (!$this->zip_entry_is_json($zip, 'manifest.json')) {
			$zip->close();
			return false;
		}

		if (!$this->zip_entry_is_json($zip, $animation_json_entry)) {
			$zip->close();
			return false;
		}

		$zip->close();

		return true;
	}

	/**
	 * Whether a ZIP entry name is unsafe (traversal or executable-ish).
	 *
	 * @param string $entry_name Archive member path.
	 * @return bool
	 */
	private function is_unsafe_zip_entry_name($entry_name)
	{
		$normalized = str_replace('\\', '/', $entry_name);

		if ('' === $normalized || '/' === $normalized[0]) {
			return true;
		}

		foreach (explode('/', $normalized) as $segment) {
			if ('..' === $segment) {
				return true;
			}
		}

		$basename = strtolower(basename($normalized));

		// Hidden Apache config dropped into the archive.
		if ('.htaccess' === $basename || '.htpasswd' === $basename) {
			return true;
		}

		$extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
		if ('' === $extension) {
			return false;
		}

		$dangerous = [
			'php', 'phtml', 'phar', 'pht', 'phps', 'pgif',
			'exe', 'sh', 'bash', 'bat', 'cmd', 'cgi', 'pl', 'py', 'rb',
			'js', 'mjs', 'shtml',
		];

		if (in_array($extension, $dangerous, true)) {
			return true;
		}

		// php7, php81, php74, etc.
		if (1 === preg_match('/^php\d+$/', $extension)) {
			return true;
		}

		return false;
	}

	/**
	 * Read a ZIP entry and confirm it is well-formed JSON.
	 *
	 * @param \ZipArchive $zip        Open archive.
	 * @param string      $entry_name Member path.
	 * @return bool
	 */
	private function zip_entry_is_json(\ZipArchive $zip, $entry_name)
	{
		$contents = $zip->getFromName($entry_name);
		if (false === $contents || '' === $contents) {
			return false;
		}

		json_decode($contents, true);

		return JSON_ERROR_NONE === json_last_error();
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
