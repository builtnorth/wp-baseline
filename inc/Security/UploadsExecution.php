<?php

/**
 * ------------------------------------------------------------------
 * UploadsExecution
 * ------------------------------------------------------------------
 *
 * Blocks PHP execution inside wp-content/uploads
 *
 * @package WPBaseline
 * @since 2.2.0
 */

namespace BuiltNorth\WPBaseline\Security;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class UploadsExecution
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		if ((bool) apply_filters('wpbaseline_block_uploads_php_execution', true)) {
			add_filter('mod_rewrite_rules', [$this, 'block_uploads_php_execution']);
		}
	}

	/**
	 * Denies PHP execution under wp-content/uploads.
	 *
	 * WordPress's `mod_rewrite_rules` filter only ever writes to the site's
	 * ROOT .htaccess, where `<Directory>`/`<Location>` blocks aren't valid
	 * (Apache only allows `<Files>`/`<FilesMatch>`/mod_rewrite directives in
	 * .htaccess context — a `<Directory>` block here would 500 the whole
	 * site). A path-matching RewriteRule is the correct way to scope a deny
	 * to a subdirectory from the root file, and is the same approach other
	 * WordPress security plugins use for this exact rule.
	 */
	public function block_uploads_php_execution($rules)
	{
		$uploads = wp_get_upload_dir();
		$basedir = wp_normalize_path($uploads['basedir']);
		$abspath = wp_normalize_path(ABSPATH);

		// Uploads can be configured outside the webroot; only add the rule
		// when it's reachable from a path relative to the root .htaccess.
		if (!str_starts_with($basedir, $abspath)) {
			return $rules;
		}

		$relative = trim(substr($basedir, strlen($abspath)), '/');

		if ('' === $relative) {
			return $rules;
		}

		$pattern = preg_quote($relative, '#');

		return "
		# Block PHP execution in uploads
		RewriteEngine On
		RewriteRule ^{$pattern}/.*\.(?:php|phtml|php\d?|phar)$ - [F,L,NC]\n\n" . $rules;
	}
}
