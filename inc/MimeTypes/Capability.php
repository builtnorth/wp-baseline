<?php

/**
 * ------------------------------------------------------------------
 * Mime Upload Capability
 * ------------------------------------------------------------------
 *
 * Shared capability gate for mime types wp-baseline unlocks beyond core
 * defaults (SVG, JSON, Lottie).
 *
 * @package WPBaseline
 * @since 3.0.0
 */

namespace BuiltNorth\WPBaseline\MimeTypes;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class Capability
{
	/**
	 * Check whether the current user may upload the given mime type.
	 *
	 * SVG, JSON and Lottie are not allowed by WordPress core; wp-baseline is
	 * the thing choosing to unlock them, so the default capability required
	 * is `manage_options`. A site that wants broader access (e.g. Authors
	 * uploading SVGs) opts down via the filter.
	 *
	 * @param string $mime_key One of 'svg', 'json', 'lottie'.
	 * @return bool
	 */
	public static function current_user_can_upload($mime_key)
	{
		/**
		 * Filter the capability required to upload a wp-baseline-enabled mime type.
		 *
		 * @param string $capability Required capability. Default 'manage_options'.
		 * @param string $mime_key   One of 'svg', 'json', 'lottie'.
		 */
		$capability = apply_filters('wpbaseline_mime_upload_capability', 'manage_options', $mime_key);

		return current_user_can($capability);
	}
}
