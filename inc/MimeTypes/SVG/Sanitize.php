<?php

/**
 * ------------------------------------------------------------------
 * SVG Sanitization
 * ------------------------------------------------------------------
 * 
 * Sanitize SVG uploads.
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace BuiltNorth\WPBaseline\MimeTypes\SVG;

use enshrined\svgSanitize\Sanitizer;

class Sanitize
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		add_filter('wp_handle_upload_prefilter', [$this, 'sanitize_svg']);
	}

	/**
	 * Sanitize SVG uploads.
	 *
	 * Sanitization is decided by the *filename extension*, not by
	 * `$file['type']`. This filter runs on `wp_handle_upload_prefilter`, which
	 * fires before WordPress verifies the real type in
	 * `wp_check_filetype_and_ext()`, so at this point `$file['type']` is still
	 * the Content-Type the client declared in the multipart request — entirely
	 * attacker-controlled.
	 *
	 * Keying off it meant uploading `payload.svg` while declaring
	 * `Content-Type: image/png` skipped sanitization here, and
	 * {@see Upload::check_svg_filetype()} then accepted the file as
	 * `image/svg+xml` on the strength of its extension alone. The result was an
	 * unsanitized SVG stored and later served same-origin, with `<script>` and
	 * `javascript:` hrefs intact.
	 *
	 * Sanitizing whatever will be *accepted* as an SVG closes that gap: the
	 * extension is the same signal `check_svg_filetype()` trusts.
	 *
	 * @param array $file Upload array from `$_FILES`, as passed by the prefilter.
	 * @return array
	 */
	public function sanitize_svg($file)
	{
		$extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
		$declared  = (string) ($file['type'] ?? '');

		if ('svg' !== $extension && 'image/svg+xml' !== $declared && 'image/svg' !== $declared) {
			return $file;
		}

		$tmp_name = (string) ($file['tmp_name'] ?? '');
		if ('' === $tmp_name || ! is_readable($tmp_name)) {
			$file['error'] = __('SVG file could not be read for sanitization.', 'wp-baseline');

			return $file;
		}

		$dirty_svg = file_get_contents($tmp_name);
		if (false === $dirty_svg) {
			$file['error'] = __('SVG file could not be read for sanitization.', 'wp-baseline');

			return $file;
		}

		$sanitizer = new Sanitizer();
		$clean_svg = $sanitizer->sanitize($dirty_svg);

		// Fail closed: a file that cannot be sanitized is never stored.
		if (false === $clean_svg || '' === trim((string) $clean_svg)) {
			$file['error'] = __('SVG file could not be sanitized.', 'wp-baseline');

			return $file;
		}

		if (false === file_put_contents($tmp_name, $clean_svg)) {
			$file['error'] = __('Sanitized SVG could not be written.', 'wp-baseline');
		}

		return $file;
	}
}
