<?php

/**
 * ------------------------------------------------------------------
 * XMLRPC
 * ------------------------------------------------------------------
 *
 * Disable XMLRPC
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace WPBaseline\Security;

// Don't load directly.
defined('ABSPATH') || exit;

class XMLRPC
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{

		add_filter('wp_xmlrpc_server_class', '__return_false');
		add_filter('xmlrpc_enabled', '__return_false');
		add_filter('pre_update_option_enable_xmlrpc', '__return_false');
		add_filter('pre_option_enable_xmlrpc', '__return_zero');
	}
}
