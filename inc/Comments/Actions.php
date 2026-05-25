<?php

/**
 * ------------------------------------------------------------------
 * Comment Actions
 * ------------------------------------------------------------------
 *
 * Disable comments and remove related actions
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace BuiltNorth\WPBaseline\Comments;

class Actions
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		// Check if comments should be disabled
		if (!apply_filters('wpbaseline_disable_comments', false)) {
			return;
		}

		add_filter('register_post_type_args', [$this, 'strip_editor_notes_from_post_type_args'], 10, 2);
		add_action('registered_post_type', [$this, 'disable_editor_notes_support'], 999, 2);

		// Run late on init so post types registered after priority 0 are included.
		if (did_action('init')) {
			$this->disable_comments();
			$this->disable_comment_feeds();
		} else {
			add_action('init', [$this, 'disable_comments'], 999);
			add_action('init', [$this, 'disable_comment_feeds']);
		}

		add_action('admin_menu', [$this, 'remove_dashboard_sections']);
		add_action('wp_before_admin_bar_render', [$this, 'hide_admin_toolbar_link']);
		add_action('widgets_init', [$this, 'disable_comment_widgets']);
		add_action('wp_enqueue_scripts', [$this, 'disable_comment_assets'], 100);
		add_action('admin_enqueue_scripts', [$this, 'disable_comment_assets'], 100);
		add_action('wp_dashboard_setup', [$this, 'remove_dashboard_comments_widget']);
		add_action('admin_init', [$this, 'redirect_comments_page']);
		add_action('enqueue_block_editor_assets', [$this, 'remove_discussion_panel']);
	}

	/**
	 * Disable comments for all post types.
	 */
	public function disable_comments()
	{
		$post_types = get_post_types();
		foreach ($post_types as $post_type) {
			if (post_type_supports($post_type, 'comments')) {
				remove_post_type_support($post_type, 'comments');
				remove_post_type_support($post_type, 'trackbacks');
			}

			$this->disable_editor_notes_support($post_type);
		}

		$wpdb = $GLOBALS['wpdb'];
		$has_open_comments = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE comment_status = 'open' LIMIT 1");
		if (!empty($has_open_comments)) {
			$wpdb->query("UPDATE {$wpdb->posts} SET comment_status = 'closed' WHERE comment_status = 'open'");
		}

		add_filter('rest_allow_anonymous_comments', '__return_false');
		add_filter('comments_open', '__return_false', 20, 2);
		add_filter('pings_open', '__return_false', 20, 2);
	}

	/**
	 * Strip editor notes from post type args before registration.
	 *
	 * @param array  $args      Post type registration arguments.
	 * @param string $post_type Post type name.
	 */
	public function strip_editor_notes_from_post_type_args(array $args, string $post_type): array
	{
		if (empty($args['supports']) || !is_array($args['supports'])) {
			return $args;
		}

		$supports = [];

		foreach ($args['supports'] as $key => $value) {
			if ('editor' === $key && is_array($value)) {
				unset($value['notes']);
				if ($value === []) {
					$supports[] = 'editor';
					continue;
				}
				$supports[$key] = $value;
				continue;
			}

			if (is_int($key)) {
				$supports[] = $value;
				continue;
			}

			$supports[$key] = $value;
		}

		$args['supports'] = $supports;

		return $args;
	}

	/**
	 * Remove block editor notes support so the editor does not call the comments REST API.
	 *
	 * Core registers posts/pages with `editor => [ 'notes' => true ]`, which mounts the
	 * collaboration sidebar and requests /wp/v2/comments?type=note even when discussion
	 * comments are closed. That conflicts with disabled comment REST routes.
	 */
	public function disable_editor_notes_support(string $post_type): void
	{
		if (!post_type_supports($post_type, 'editor')) {
			return;
		}

		$supports = get_all_post_type_supports($post_type);
		$editor_support = $supports['editor'] ?? null;

		if (!is_array($editor_support)) {
			return;
		}

		foreach ($editor_support as $item) {
			if (!empty($item['notes'])) {
				remove_post_type_support($post_type, 'editor');
				add_post_type_support($post_type, 'editor');
				return;
			}
		}
	}

	/**
	 * Remove the comments menu page and submenu page.
	 */
	public function remove_dashboard_sections()
	{
		remove_menu_page('edit-comments.php');
		remove_submenu_page('options-general.php', 'options-discussion.php');
	}

	/**
	 * Hide the comments link from the admin toolbar.
	 */
	public function hide_admin_toolbar_link()
	{
		if (is_admin_bar_showing()) {
			remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
		}
	}

	/**
	 * Disable comment feeds.
	 */
	public function disable_comment_feeds()
	{
		add_filter('feed_links_show_comments_feed', '__return_false');
	}

	/**
	 * Disable comment widgets.
	 */
	public function disable_comment_widgets()
	{
		unregister_widget('WP_Widget_Recent_Comments');
	}

	/**
	 * Disable comment assets.
	 */
	public function disable_comment_assets()
	{
		wp_dequeue_script('comment-reply');
	}

	/**
	 * Remove the dashboard comments widget.
	 */
	public function remove_dashboard_comments_widget()
	{
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
	}

	/**
	 * Redirect to the dashboard when accessing the comments page.
	 */
	public function redirect_comments_page()
	{
		global $pagenow;

		if ($pagenow === 'edit-comments.php') {
			wp_safe_redirect(admin_url());
			exit;
		}
	}

	/**
	 * Remove the discussion panel from the block editor.
	 */
	public function remove_discussion_panel()
	{
		wp_add_inline_script(
			'wp-edit-post',
			'wp.domReady(function () {
                wp.data.dispatch("core/editor").removeEditorPanel("discussion-panel");
            });'
		);
	}
}
