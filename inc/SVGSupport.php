<?php

/**
 * ------------------------------------------------------------------
 * Class: SVG Support
 * ------------------------------------------------------------------
 *
 * Allow SVG uploads and sanitizate them.
 *
 * @package BuiltStarter
 * @since BuiltStarter 1.0.0
 * 
 **/

namespace BuiltNorth\Baseline;

use enshrined\svgSanitize\Sanitizer;

/**
 * If this file is called directly, abort.
 */
if (!defined('WPINC')) {
	die;
}


/**
 * Class SVGSupport
 */
/**
 * Class SVGSupport
 * Handles SVG upload and sanitization in WordPress.
 */
class SVGSupport
{
	/**
	 * Initialize the SVG Support.
	 */
	public function init()
	{
		add_filter('upload_mimes', [$this, 'add_svg_mime_type']);
		add_filter('wp_check_filetype_and_ext', [$this, 'check_svg_filetype'], 10, 4);
		add_action('admin_head', [$this, 'fix_svg_thumbnail_display']);
		add_filter('wp_handle_upload_prefilter', [$this, 'sanitize_svg']);
	}

	/**
	 * Add SVG mime type to allowed upload types.
	 *
	 * @param array $mimes Array of mime types.
	 * @return array Modified array of mime types.
	 */
	public function add_svg_mime_type($mimes)
	{
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Check SVG filetype.
	 *
	 * @param array  $data     File data.
	 * @param string $file     Full path to the file.
	 * @param string $filename The name of the file (may differ from $file due to $file being in a tmp directory).
	 * @param array  $mimes    Array of mime types.
	 * @return array Modified file data.
	 */
	public function check_svg_filetype($data, $file, $filename, $mimes)
	{
		$filetype = wp_check_filetype($filename, $mimes);
		return [
			'ext'             => $filetype['ext'],
			'type'            => $filetype['type'],
			'proper_filename' => $data['proper_filename']
		];
	}

	/**
	 * Fix SVG thumbnail display in media library.
	 */
	public function fix_svg_thumbnail_display()
	{
		echo '<style type="text/css">
                .attachment-266x266, .thumbnail img {
                    width: 100% !important;
                    height: auto !important;
                }
              </style>';
	}

	/**
	 * Sanitize SVG on upload.
	 *
	 * @param array $file Array of uploaded file data.
	 * @return array Modified array of uploaded file data.
	 */
	public function sanitize_svg($file)
	{
		if ($file['type'] === 'image/svg+xml') {
			$sanitizer = new Sanitizer();
			$dirty_svg = file_get_contents($file['tmp_name']);
			$clean_svg = $sanitizer->sanitize($dirty_svg);

			if ($clean_svg === false) {
				$file['error'] = __('SVG file could not be sanitized.', 'wdsbt');
			} else {
				file_put_contents($file['tmp_name'], $clean_svg);
			}
		}
		return $file;
	}
}
