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

namespace BuiltNorth\WPBaseline\MimeTypes\JSON;

use BuiltNorth\WPBaseline\MimeTypes\Capability;

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
	 * Validate JSON files before upload.
	 *
	 * application/json is never executed by a browser or server -- it is
	 * not HTML, SVG, or script, so there is no upload-time transform that
	 * makes it "safe" the way sanitizing an SVG does. The actual control
	 * against stored XSS via JSON field values is output escaping by
	 * whatever later reads and renders them, which this class cannot see
	 * or enforce. A prior version of this method ran a blocklist regex
	 * (stripping `<script>`, `javascript:`, `on*=`, etc.) over every
	 * string value and re-encoded the file -- easily bypassed by any
	 * pattern not on the list, and destructive to legitimate content
	 * (e.g. a string containing "on = " unrelated to an event handler).
	 * This now only confirms the upload is well-formed JSON and leaves
	 * content untouched.
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

		if (!Capability::current_user_can_upload('json')) {
			$file['error'] = __('You do not have permission to upload JSON files.', 'wp-baseline');
			return $file;
		}

		// Read the file content
		$content = file_get_contents($file['tmp_name']);

		if ($content === false) {
			$file['error'] = __('Could not read JSON file.', 'wp-baseline');
			return $file;
		}

		// Validate JSON structure
		json_decode($content, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$file['error'] = __('Invalid JSON file format.', 'wp-baseline');
			return $file;
		}

		return $file;
	}
}
