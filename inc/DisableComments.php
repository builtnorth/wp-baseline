<?php

/**
 * ------------------------------------------------------------------
 * Class: DisableComments
 * ------------------------------------------------------------------
 *
 * Disables all commenting functionality in WordPress
 * 
 * @link https://github.com/prolific-digital/wp-disable-comments/blob/main/index.php
 *
 * @package BuiltStarter
 * @since BuiltStarter 1.0.0
 * 
 **/

namespace BuiltNorth\Baseline;

/**
 * If this file is called directly, abort.
 */
if (!defined('WPINC')) {
	die;
}

class DisableComments
{
	/**
	 * Checks if the disable comments functionality is enabled
	 *
	 * @return bool True if enabled, false otherwise
	 */
	public static function is_not_enabled()
	{
		// Allow themes or plugins to disable this functionality
		return apply_filters('built_baseline_disable_comments', false);
	}

	/**
	 * Initializes the disable comments functionality
	 */
	public function init()
	{
		if (!self::is_not_enabled()) {
			return;
		}
		// Add various action and filter hooks to disable comments
		add_action('init', array($this, 'disable_comments'));
		add_action('admin_menu', array($this, 'remove_dashboard_sections'));
		add_action('wp_before_admin_bar_render', array($this, 'hide_admin_toolbar_link'));
		add_action('init', array($this, 'disable_comment_feeds'));
		add_action('widgets_init', array($this, 'disable_comment_widgets'));
		add_action('wp_enqueue_scripts', array($this, 'disable_comment_assets'), 100);
		add_action('admin_enqueue_scripts', array($this, 'disable_comment_assets'), 100);
		add_filter('comment_notification_recipients', array($this, 'disable_comment_notifications'), 10, 2);
		add_action('wp_dashboard_setup', array($this, 'remove_dashboard_comments_widget'));
		add_filter('rest_endpoints', array($this, 'disable_comment_rest_endpoints'));
		add_filter('comment_form_defaults', array($this, 'remove_comment_form'));
		add_action('admin_init', array($this, 'redirect_comments_page'));
		add_filter('admin_bar_menu', array($this, 'adjust_admin_bar'), 999);
		add_filter('manage_pages_columns', array($this, 'remove_pages_count_columns'));
		add_filter('manage_posts_columns', array($this, 'remove_pages_count_columns'));
		add_action('enqueue_block_editor_assets', array($this, 'remove_discussion_panel'));
	}

	public function remove_pages_count_columns($defaults)
	{
		unset($defaults['comments']);
		return $defaults;
	}

	/**
	 * Disables comments and trackbacks for all post types
	 */
	public function disable_comments()
	{
		// Remove support for comments and trackbacks from all post types
		$post_types = get_post_types();
		foreach ($post_types as $post_type) {
			if (post_type_supports($post_type, 'comments')) {
				remove_post_type_support($post_type, 'comments');
				remove_post_type_support($post_type, 'trackbacks');
			}
		}

		// Close comments on all existing posts
		$wpdb = $GLOBALS['wpdb'];
		$wpdb->query("UPDATE $wpdb->posts SET comment_status = 'closed'");

		// Disable the display of comments in the WP API
		add_filter('rest_allow_anonymous_comments', '__return_false');
		add_filter('comments_open', '__return_false', 20, 2);
		add_filter('pings_open', '__return_false', 20, 2);
	}

	/**
	 * Removes comment-related sections from the dashboard
	 */
	public function remove_dashboard_sections()
	{
		remove_menu_page('edit-comments.php');
		remove_submenu_page('options-general.php', 'options-discussion.php');
	}

	/**
	 * Hides the comments link from the admin toolbar
	 */
	public function hide_admin_toolbar_link()
	{
		if (is_admin_bar_showing()) {
			remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
		}
	}

	/**
	 * Disables comment feeds
	 */
	public function disable_comment_feeds()
	{
		add_filter('feed_links_show_comments_feed', '__return_false');
	}

	/**
	 * Disables comment-related widgets
	 */
	public function disable_comment_widgets()
	{
		unregister_widget('WP_Widget_Recent_Comments');
	}

	/**
	 * Dequeues comment-related assets
	 */
	public function disable_comment_assets()
	{
		wp_dequeue_script('comment-reply');
		wp_dequeue_style('wp-admin');
	}

	/**
	 * Disables comment notifications
	 *
	 * @param array $notify    List of email addresses to notify
	 * @param int   $comment_id ID of the comment
	 * @return bool Always returns false to disable notifications
	 */
	public function disable_comment_notifications($notify, $comment_id)
	{
		return false;
	}

	/**
	 * Removes the recent comments widget from the dashboard
	 */
	public function remove_dashboard_comments_widget()
	{
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
	}

	/**
	 * Disables comment-related REST API endpoints
	 *
	 * @param array $endpoints List of REST API endpoints
	 * @return array Modified list of endpoints
	 */
	public function disable_comment_rest_endpoints($endpoints)
	{
		if (isset($endpoints['/wp/v2/comments'])) {
			unset($endpoints['/wp/v2/comments']);
		}
		if (isset($endpoints['/wp/v2/comments/(?P<id>[\d]+)'])) {
			unset($endpoints['/wp/v2/comments/(?P<id>[\d]+)']);
		}
		return $endpoints;
	}

	/**
	 * Removes the comment form from posts
	 *
	 * @param array $defaults Default comment form settings
	 * @return array Modified comment form settings
	 */
	public function remove_comment_form($defaults)
	{
		$defaults['comment_form'] = null;
		return $defaults;
	}

	/**
	 * Redirects users away from the comments page in the admin
	 */
	public function redirect_comments_page()
	{
		global $pagenow;

		if ($pagenow === 'edit-comments.php') {
			wp_redirect(admin_url());
			exit;
		}
	}

	/**
	 * Adjusts the admin bar to remove comments-related items
	 *
	 * @param WP_Admin_Bar $wp_toolbar The WordPress admin bar object
	 * @return WP_Admin_Bar Modified admin bar
	 */
	public function adjust_admin_bar($wp_toolbar)
	{
		$wp_toolbar->remove_node('comments');  // disable comments
		return $wp_toolbar;
	}

	/**
	 * Removes the Discussion panel from the Gutenberg editor
	 */
	public function remove_discussion_panel()
	{
		wp_add_inline_script(
			'wp-edit-post',
			'wp.domReady(function () {
				wp.data.dispatch("core/edit-post").removeEditorPanel("discussion-panel");
			});'
		);
	}
}
