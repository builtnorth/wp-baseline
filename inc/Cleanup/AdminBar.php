<?php

/**
 * ------------------------------------------------------------------
 * AdminBar
 * ------------------------------------------------------------------
 *
 * Remove items from the admin bar
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace BuiltNorth\WPBaseline\Cleanup;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class AdminBar
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		// Run after core registers all nodes (search is added at priority 9999).
		add_action('admin_bar_menu', [$this, 'replace_wordpress_howdy'], 9992);
		add_action('admin_bar_menu', [$this, 'remove_admin_nodes'], 10000);
	}

	/**
	 * Remove/Change "Howdy" text in admin menu
	 */
	public function replace_wordpress_howdy($wp_admin_bar)
	{
		$my_account = $wp_admin_bar->get_node('my-account');
		if (isset($my_account->title)) {
			// Apply a filter to allow customization of the "Howdy" text
			$howdy_text = apply_filters('wpbaseline_howdy_text', '');
			$newtitle = str_replace('Howdy, ', $howdy_text, $my_account->title);
			$wp_admin_bar->add_node(array(
				'id' => 'my-account',
				'title' => $newtitle,
			));
		}
	}



	/**
	 * Custom Admin Bar Menu
	 */
	public function remove_admin_nodes($wp_admin_bar)
	{
		// Check if the functionality should be enabled
		$enable_removal = apply_filters('wpbaseline_clean_admin_bar', true);

		// If the functionality is disabled, return the admin bar
		if (!$enable_removal) {
			return $wp_admin_bar;
		}

		$nodes_to_remove = [
			'wp-logo',
			'search',
			'updates',
		];

		$nodes_to_remove = apply_filters('wpbaseline_admin_bar_nodes_to_remove', $nodes_to_remove);

		foreach ($nodes_to_remove as $node) {
			if (! is_string($node) || $node === '') {
				continue;
			}

			$wp_admin_bar->remove_node($node);
		}

		if (defined('DISALLOW_FILE_MODS')) {
			$wp_admin_bar->remove_node('plugins');
			$wp_admin_bar->remove_node('themes');
		}

		return $wp_admin_bar;
	}
}
