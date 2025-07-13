<?php

/**
 * ------------------------------------------------------------------
 * JSON Sanitization
 * ------------------------------------------------------------------
 * 
 * Sanitize JSON uploads.
 *
 * @package WPBaseline
 * @since 2.3.0
 */

namespace WPBaseline\MimeTypes\JSON;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class Sanitize
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		// Only add sanitization if explicitly enabled (opt-in)
		if (apply_filters('wpbaseline_sanitize_json_uploads', false)) {
			add_filter('wp_handle_upload_prefilter', [$this, 'sanitize_json_files']);
		}
	}

	/**
	 * Sanitize JSON files before upload.
	 * 
	 * @param array $file File upload data.
	 * @return array Modified file data.
	 */
	public function sanitize_json_files($file)
	{
		// Only process JSON files
		if ($file['type'] !== 'application/json') {
			return $file;
		}

		// Read the file content
		$content = file_get_contents($file['tmp_name']);
		
		if ($content === false) {
			$file['error'] = __('Could not read JSON file.', 'built-wp-baseline');
			return $file;
		}

		// Validate JSON structure
		$decoded = json_decode($content, true);
		
		if (json_last_error() !== JSON_ERROR_NONE) {
			$file['error'] = __('Invalid JSON file format.', 'built-wp-baseline');
			return $file;
		}

		// Sanitize the JSON content
		$sanitized_content = $this->sanitize_json_content($decoded);
		
		// Re-encode the sanitized content
		$sanitized_json = json_encode($sanitized_content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		
		if ($sanitized_json === false) {
			$file['error'] = __('Could not process JSON file.', 'built-wp-baseline');
			return $file;
		}

		// Write the sanitized content back to the temporary file
		if (file_put_contents($file['tmp_name'], $sanitized_json) === false) {
			$file['error'] = __('Could not save sanitized JSON file.', 'built-wp-baseline');
			return $file;
		}

		return $file;
	}

	/**
	 * Recursively sanitize JSON content.
	 * 
	 * @param mixed $data The data to sanitize.
	 * @return mixed Sanitized data.
	 */
	private function sanitize_json_content($data)
	{
		if (is_array($data)) {
			$sanitized = [];
			foreach ($data as $key => $value) {
				// Sanitize array keys
				$sanitized_key = sanitize_text_field($key);
				$sanitized[$sanitized_key] = $this->sanitize_json_content($value);
			}
			return $sanitized;
		}

		if (is_string($data)) {
			// Remove potentially dangerous content
			$data = $this->remove_dangerous_content($data);
			return sanitize_textarea_field($data);
		}

		if (is_numeric($data)) {
			return $data;
		}

		if (is_bool($data)) {
			return $data;
		}

		if (is_null($data)) {
			return $data;
		}

		// For any other type, convert to string and sanitize
		return sanitize_textarea_field((string) $data);
	}

	/**
	 * Remove potentially dangerous content from strings.
	 * 
	 * @param string $content The content to clean.
	 * @return string Cleaned content.
	 */
	private function remove_dangerous_content($content)
	{
		// Remove script tags and their content
		$content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
		
		// Remove javascript: protocol
		$content = preg_replace('/javascript:/i', '', $content);
		
		// Remove data: protocol (except for common safe formats)
		$content = preg_replace('/data:(?!image\/png|image\/jpg|image\/jpeg|image\/gif|image\/webp|image\/svg\+xml)/i', '', $content);
		
		// Remove vbscript: protocol
		$content = preg_replace('/vbscript:/i', '', $content);
		
		// Remove on* event handlers
		$content = preg_replace('/\bon\w+\s*=/i', '', $content);
		
		return $content;
	}
}