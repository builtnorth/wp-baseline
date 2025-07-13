<?php

/**
 * ------------------------------------------------------------------
 * JSON Upload
 * ------------------------------------------------------------------
 *
 * Allow JSON uploads.
 *
 * @package WPBaseline
 * @since 2.3.0
 */

namespace WPBaseline\MimeTypes\JSON;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class Upload
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		add_filter('upload_mimes', [$this, 'add_json_mime_type']);
	}

	/**
	 * Add JSON mime type.
	 * 
	 * @param array $mimes Existing mime types.
	 * @return array Modified mime types.
	 */
	public function add_json_mime_type($mimes)
	{
		$mimes['json'] = 'application/json';
		return $mimes;
	}
}