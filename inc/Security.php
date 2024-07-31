<?php

/**
 * ------------------------------------------------------------------
 * Functions: Security & Hardening
 * ------------------------------------------------------------------
 *
 * Further secure and harden WordPress
 * 
 * @package Core
 * @since Core 1.0.0
 */

namespace BuiltNorth\Core;

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
		add_action('admin_menu', [$this, 'remove_wp_version_footer']);
		add_filter('style_loader_src', [$this, 'remove_wp_version_files'], 9999);
		add_filter('script_loader_src', [$this, 'remove_wp_version_files'], 9999);
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
		remove_filter('update_footer', 'core_update_footer');
	}

	/**
	 * Remove WP version param from any enqueued scripts
	 */
	public function remove_wp_version_files($src)
	{
		if (strpos($src, 'ver=')) {
			$src = remove_query_arg('ver', $src);
		}
		return $src;
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
