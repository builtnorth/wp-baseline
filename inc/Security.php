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
		add_filter('the_generator', [$this, 'removeWpVersionHeadAndFeeds']);
		add_action('admin_menu', [$this, 'removeWpVersionFooter']);
		add_filter('style_loader_src', [$this, 'removeWpVersionFiles'], 9999);
		add_filter('script_loader_src', [$this, 'removeWpVersionFiles'], 9999);
		add_action('pre_ping', [$this, 'noSelfPing']);
		add_action('init', [$this, 'disableXmlrpc']);
		add_filter('wp_headers', [$this, 'removeXPingback']);
		add_action('init', [$this, 'defineSettings']);
	}

	/**
	 * Remove WP version info from head and feeds
	 */
	public function removeWpVersionHeadAndFeeds()
	{
		return '';
	}

	/**
	 * Remove WP version number from admin footer
	 */
	public function removeWpVersionFooter()
	{
		remove_filter('update_footer', 'core_update_footer');
	}

	/**
	 * Remove WP version param from any enqueued scripts
	 */
	public function removeWpVersionFiles($src)
	{
		if (strpos($src, 'ver=')) {
			$src = remove_query_arg('ver', $src);
		}
		return $src;
	}

	/**
	 * Remove pings to self
	 */
	public function noSelfPing(&$links)
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
	public function disableXmlrpc()
	{
		add_filter('wp_xmlrpc_server_class', '__return_false');
		add_filter('xmlrpc_enabled', '__return_false');
		add_filter('pre_update_option_enable_xmlrpc', '__return_false');
		add_filter('pre_option_enable_xmlrpc', '__return_zero');
	}

	/**
	 * Remove xpinback header
	 */
	public function removeXPingback($headers)
	{
		unset($headers['X-Pingback']);
		return $headers;
	}

	/**
	 * Define Settings
	 */
	public function defineSettings()
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
