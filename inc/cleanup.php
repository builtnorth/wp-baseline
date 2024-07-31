<?php

/**
 * ------------------------------------------------------------------
 * Functions: Cleanup
 * ------------------------------------------------------------------
 *
 * Clean up WordPress and remove unneeded functionality
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

class Cleanup
{
	public function init()
	{
		add_filter('admin_bar_menu', [$this, 'replaceWordpressHowdy'], 25);
		add_action('wp_before_admin_bar_render', [$this, 'removeWpIconAdminBar']);
		add_action('login_head', [$this, 'customLoginLogo'], 100);
		add_filter('login_headerurl', [$this, 'updateLoginLogoUrl']);
		add_filter('login_headertext', [$this, 'updateLoginLogoText']);
		add_filter('wp_mail_from_name', [$this, 'mailFromName']);
		add_filter('wp_mail_from', [$this, 'mailFromEmail']);
		add_filter('auto_core_update_send_email', [$this, 'disableAutoUpdateEmails']);
		add_filter('auto_plugin_update_send_email', [$this, 'disableAutoUpdateEmails']);
		add_filter('auto_theme_update_send_email', [$this, 'disableAutoUpdateEmails']);
		add_action('init', [$this, 'removeActionsFromHead']);
		add_action('widgets_init', [$this, 'unregisterWidgets'], 11);
		add_action('after_setup_theme', [$this, 'removeWidgetsThemeSupport']);
		add_action('admin_menu', [$this, 'removeMetaboxes']);
		add_action('admin_init', [$this, 'removeDashboards']);
		add_action('init', [$this, 'disableEmojis']);
		add_filter('tiny_mce_plugins', [$this, 'disableEmojisTinymce']);
		add_filter('wp_resource_hints', [$this, 'disableEmojisRemoveDnsPrefetch'], 10, 2);
	}

	/**
	 * Remove/Change "Howdy" text in admin menu
	 */
	public function replaceWordpressHowdy($wp_admin_bar)
	{
		$my_account = $wp_admin_bar->get_node('my-account');
		$newtext = str_replace('Howdy,', '', $my_account->title);
		$wp_admin_bar->add_node([
			'id' => 'my-account',
			'title' => $newtext,
		]);
	}

	/**
	 * Remove the WP logo from the admin bar
	 */
	public function removeWpIconAdminBar()
	{
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu("wp-logo");
	}

	/**
	 * Update login logo image
	 */
	public function customLoginLogo()
	{
		if (has_custom_logo()) :
			$image = wp_get_attachment_image_src(get_option('site_logo'), 'full');
?>
			<style type="text/css">
				.login h1 a {
					background-image: url(<?php echo esc_url($image[0]); ?>);
					background-repeat: no-repeat;
					background-size: contain;
					width: auto;
					height: 80px;
					margin-bottom: 30px;
				}
			</style>
<?php
		endif;
	}

	/**
	 * Update login logo link URL
	 */
	public function updateLoginLogoUrl()
	{
		return home_url();
	}

	/**
	 * Update login logo link title
	 */
	public function updateLoginLogoText()
	{
		return get_option("blogname");
	}

	/**
	 * Change WP Mail from name
	 */
	public function mailFromName()
	{
		return get_option("blogname");
	}

	/**
	 * Change WP Mail from email address
	 */
	public function mailFromEmail()
	{
		return get_option("admin_email");
	}

	/**
	 * Disable auto update emails
	 */
	public function disableAutoUpdateEmails()
	{
		return false;
	}

	/**
	 * Remove unnecessary code from wp_head
	 */
	public function removeActionsFromHead()
	{
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'feed_links', 2);
		remove_action('wp_head', 'index_rel_link');
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'start_post_rel_link');
		remove_action('wp_head', 'index_rel_link');
		remove_action('wp_head', 'adjacent_posts_rel_link');
		remove_action('wp_head', 'feed_links_extra', 3);
		remove_action('wp_head', 'start_post_rel_link', 10, 0);
		remove_action('wp_head', 'parent_post_rel_link', 10, 0);
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
	}

	/**
	 * Unregister all widgets
	 */
	public function unregisterWidgets()
	{
		unregister_widget('WP_Widget_Archives');
		unregister_widget('WP_Widget_Calendar');
		unregister_widget('WP_Widget_Categories');
		unregister_widget('WP_Widget_Custom_HTML');
		unregister_widget('WP_Widget_Links');
		unregister_widget('WP_Widget_Meta');
		unregister_widget('WP_Widget_Media_Audio');
		unregister_widget('WP_Widget_Media_Gallery');
		unregister_widget('WP_Widget_Media_Video');
		unregister_widget('WP_Widget_Media_Image');
		unregister_widget('WP_Nav_Menu_Widget');
		unregister_widget('WP_Widget_Pages');
		unregister_widget('WP_Widget_Recent_Posts');
		unregister_widget('WP_Widget_Recent_Comments');
		unregister_widget('WP_Widget_RSS');
		unregister_widget('WP_Widget_Search');
		unregister_widget('WP_Widget_Tag_Cloud');
		unregister_widget('WP_Widget_Text');
	}

	/**
	 * Remove widgets from block editor
	 */
	public function removeWidgetsThemeSupport()
	{
		remove_theme_support('widgets-block-editor');
		add_filter('use_widgets_block_editor', '__return_false');
		add_filter('gutenberg_use_widgets_block_editor', '__return_false', 100);
	}

	/**
	 * Remove default meta boxes
	 */
	public function removeMetaboxes()
	{
		remove_meta_box('commentstatusdiv', 'page', 'normal');
		remove_meta_box('commentsdiv', 'page', 'normal');
		remove_meta_box('authordiv', 'page', 'normal');
	}

	/**
	 * Remove dashboards
	 */
	public function removeDashboards()
	{
		remove_action('welcome_panel', 'wp_welcome_panel');
		remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
		remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
		remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
		remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
		remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
		remove_meta_box('dashboard_primary', 'dashboard', 'side');
		remove_meta_box('dashboard_secondary', 'dashboard', 'side');
		remove_meta_box('dashboard_activity', 'dashboard', 'normal');
	}

	/**
	 * Remove emoji filter & action hooks
	 */
	public function disableEmojis()
	{
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('admin_print_scripts', 'print_emoji_detection_script');
		remove_action('wp_print_styles', 'print_emoji_styles');
		remove_action('admin_print_styles', 'print_emoji_styles');
		remove_filter('the_content_feed', 'wp_staticize_emoji');
		remove_filter('comment_text_rss', 'wp_staticize_emoji');
		remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	}

	/**
	 * Disable tinyMCE emojis
	 */
	public function disableEmojisTinymce($plugins)
	{
		if (is_array($plugins)) {
			return array_diff($plugins, ['wpemoji']);
		} else {
			return [];
		}
	}

	/**
	 * Remove emoji CDN hostname from DNS prefetching hints.
	 */
	public function disableEmojisRemoveDnsPrefetch($urls, $relation_type)
	{
		if ('dns-prefetch' == $relation_type) {
			// This filter is documented in wp-includes/formatting.php
			$emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
			$urls = array_diff($urls, [$emoji_svg_url]);
		}
		return $urls;
	}
}
