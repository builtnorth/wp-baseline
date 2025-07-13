<?php

/**
 * ------------------------------------------------------------------
 * MimeTypes Init
 * ------------------------------------------------------------------
 *
 * Initialize all mime type handling classes
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace WPBaseline\MimeTypes;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class Init
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		// Initialize SVG support if not disabled
		if (apply_filters('wpbaseline_enable_svg_uploads', true)) {
			$svg_upload = new SVG\Upload();
			$svg_upload->init();
			
			$svg_sanitize = new SVG\Sanitize();
			$svg_sanitize->init();
		}

		// Initialize JSON support if explicitly enabled
		if (apply_filters('wpbaseline_enable_json_uploads', false)) {
			$json_upload = new JSON\Upload();
			$json_upload->init();
			
			$json_sanitize = new JSON\Sanitize();
			$json_sanitize->init();
		}

		// Initialize Lottie support if explicitly enabled
		if (apply_filters('wpbaseline_enable_lottie_uploads', false)) {
			$lottie_upload = new Lottie\Upload();
			$lottie_upload->init();
			
			$lottie_validate = new Lottie\Validate();
			$lottie_validate->init();
		}
	}
}