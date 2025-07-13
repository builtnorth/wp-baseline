<?php

/**
 * ------------------------------------------------------------------
 * Lottie Upload
 * ------------------------------------------------------------------
 *
 * Allow Lottie animation file uploads.
 *
 * @package WPBaseline
 * @since 2.3.0
 */

namespace WPBaseline\MimeTypes\Lottie;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class Upload
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		add_filter('upload_mimes', [$this, 'add_lottie_mime_type']);
	}

	/**
	 * Add Lottie mime type.
	 * 
	 * @param array $mimes Existing mime types.
	 * @return array Modified mime types.
	 */
	public function add_lottie_mime_type($mimes)
	{
		// .lottie files can be JSON or binary (compressed)
		$mimes['lottie'] = 'application/octet-stream';
		return $mimes;
	}
}