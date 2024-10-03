<?php

/**
 * ------------------------------------------------------------------
 * Functions: Security & Hardening
 * ------------------------------------------------------------------
 *
 * Further secure and harden WordPress
 * 
 * @package Baseline
 * @since Baseline 1.0.0
 */

namespace BuiltNorth\Baseline;

/**
 * If called directly, abort.
 */
if (!defined('WPINC')) {
	die;
}

class Security
{
	public function init()
	{
		add_filter('the_generator', [$this, 'remove_wp_version_head_and_feeds']);
		add_action('admin_menu', [$this, 'remove_wp_version_footer'], 9999);
		add_filter('style_loader_src', [$this, 'replace_wp_version_in_files'], 9999);
		add_filter('script_loader_src', [$this, 'replace_wp_version_in_files'], 9999);
		add_action('pre_ping', [$this, 'no_self_ping']);
		add_action('init', [$this, 'disable_xmlrpc']);
		add_filter('wp_headers', [$this, 'remove_x_pingback']);
		add_action('init', [$this, 'define_settings']);
	}

	/**
	 * Remove WP version info from head and feeds
	 */
	public function remove_wp_version_head_and_feeds()
	{
		return '';
	}

	/**
	 * Remove WP version number from admin footer
	 */
	public function remove_wp_version_footer()
	{
		add_filter('admin_footer_text', '__return_empty_string', 11);
		add_filter('update_footer', '__return_empty_string', 11);
	}

	/**
	 * Replace WP version param with custom version for any enqueued scripts and styles
	 * only if a custom version is not already set
	 */
	public function replace_wp_version_in_files($src)
	{
		if (strpos($src, 'ver=')) {
			$version = $this->get_query_arg_value('ver', $src);
			if ($version === get_bloginfo('version')) {
				$src = remove_query_arg('ver', $src);
				$src = add_query_arg('ver', $this->get_asset_version(), $src);
			}
		}
		return $src;
	}

	/**
	 * Get the value of a query argument from a URL
	 * 
	 * @param string $arg The query argument to get
	 * @param string $url The URL to parse
	 * @return string|null The value of the query argument, or null if not found
	 */
	private function get_query_arg_value($arg, $url)
	{
		$parts = parse_url($url);
		if (!isset($parts['query'])) {
			return null;
		}
		parse_str($parts['query'], $query);
		return isset($query[$arg]) ? $query[$arg] : null;
	}

	/**
	 * Get asset version
	 * 
	 * @return string
	 */
	private function get_asset_version()
	{
		// Option 1: Use a constant defined in your theme or plugin
		if (defined('YOUR_THEME_VERSION')) {
			return YOUR_THEME_VERSION;
		}

		// Option 2: Use the last modified time of your main CSS or JS file
		$theme_file = get_template_directory() . '/style.css';
		if (file_exists($theme_file)) {
			return filemtime($theme_file);
		}

		// Option 3: Use a timestamp that updates daily
		return date('Ymd');
	}

	/**
	 * Remove pings to self
	 */
	public function no_self_ping(&$links)
	{
		$home = get_option('home');
		foreach ($links as $l => $link) {
			if (0 === strpos($link, $home)) {
				unset($links[$l]);
			}
		}
	}

	/**
	 * Disable XMLRPC
	 */
	public function disable_xmlrpc()
	{
		add_filter('wp_xmlrpc_server_class', '__return_false');
		add_filter('xmlrpc_enabled', '__return_false');
		add_filter('pre_update_option_enable_xmlrpc', '__return_false');
		add_filter('pre_option_enable_xmlrpc', '__return_zero');
	}

	/**
	 * Remove xpinback header
	 */
	public function remove_x_pingback($headers)
	{
		unset($headers['X-Pingback']);
		return $headers;
	}

	/**
	 * Define Settings
	 */
	public function define_settings()
	{
		// Make WP use 'direct' dowload method for install/update
		if (!defined('FS_METHOD')) {
			define('FS_METHOD', 'direct');
		}
		// Block file editing
		if (!defined('DISALLOW_FILE_EDIT')) {
			define('DISALLOW_FILE_EDIT', true);
		}
	}
}
