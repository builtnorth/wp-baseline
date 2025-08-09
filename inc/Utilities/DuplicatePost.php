<?php

/**
 * ------------------------------------------------------------------
 * Duplicate Post Functionality
 * ------------------------------------------------------------------
 *
 * This class handles the duplicate post functionality for the Polaris plugin.
 * It provides the ability to duplicate posts and pages with all their data.
 *
 * @package Polaris\Admin
 * @since 1.0.0
 */

namespace BuiltNorth\WPBaseline\Utilities;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class DuplicatePost
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		// Empty constructor - initialization happens via init()
	}

	/**
	 * Initialize the duplicate post functionality.
	 */
	public function init()
	{
		// Check if duplicate post functionality should be enabled
		$config = $this->get_config();
		
		if (!$config['enabled']) {
			return;
		}
		
		add_action('admin_action_duplicate_post', [$this, 'duplicate_post']);
		add_filter('post_row_actions', [$this, 'add_duplicate_link'], 10, 2);
		add_filter('page_row_actions', [$this, 'add_duplicate_link'], 10, 2);
		add_action('admin_notices', [$this, 'show_duplicate_notice']);
	}
	
	/**
	 * Get configuration for duplicate post functionality.
	 *
	 * @return array Configuration array with 'enabled' and 'post_types' keys
	 */
	protected function get_config()
	{
		$defaults = [
			'enabled' => true,
			'post_types' => [], // Empty array means all post types
		];
		
		/**
		 * Filter the duplicate post configuration.
		 *
		 * @param array $config {
		 *     Configuration array for duplicate post functionality.
		 *
		 *     @type bool  $enabled     Whether duplicate post is enabled. Default true.
		 *     @type array $post_types  Array of post types to enable duplicate for. 
		 *                              Empty array means all post types. Default empty array.
		 * }
		 */
		return apply_filters('wp_baseline_duplicate_post_config', $defaults);
	}


	/**
	 * Add duplicate link to post/page actions.
	 *
	 * @param array    $actions Array of row action links.
	 * @param \WP_Post $post   The post object.
	 * @return array
	 */
	public function add_duplicate_link($actions, $post)
	{
		// Check if this post type is allowed
		if (!$this->is_post_type_allowed($post->post_type)) {
			return $actions;
		}
		
		if (!$this->can_duplicate_post($post)) {
			return $actions;
		}

		$actions['duplicate'] = sprintf(
			'<a href="%s" title="%s" rel="permalink">%s</a>',
			wp_nonce_url(
				admin_url('admin.php?action=duplicate_post&post=' . $post->ID),
				'duplicate_post_' . $post->ID,
				'duplicate_nonce'
			),
			esc_attr__('Duplicate this item', 'wp-baseline'),
			esc_html__('Duplicate', 'wp-baseline')
		);

		return $actions;
	}

	/**
	 * Check if a post type is allowed for duplication.
	 *
	 * @param string $post_type The post type to check.
	 * @return bool
	 */
	protected function is_post_type_allowed($post_type)
	{
		$config = $this->get_config();
		
		// If post_types is empty, allow all post types
		if (empty($config['post_types'])) {
			return true;
		}
		
		// Check if this post type is in the allowed list
		return in_array($post_type, $config['post_types'], true);
	}
	
	/**
	 * Check if user can duplicate the post.
	 *
	 * @param \WP_Post $post The post object.
	 * @return bool
	 */
	protected function can_duplicate_post($post)
	{
		$post_type_object = get_post_type_object($post->post_type);
		return current_user_can($post_type_object->cap->edit_posts);
	}

	/**
	 * Handle the duplicate post action.
	 */
	public function duplicate_post()
	{
		// Verify nonce
		if (!isset($_GET['duplicate_nonce']) || !wp_verify_nonce($_GET['duplicate_nonce'], 'duplicate_post_' . $_GET['post'])) {
			wp_die(__('Security check failed.', 'wp-baseline'));
		}

		// Get post ID
		$post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

		if (!$post_id) {
			wp_die(__('No post to duplicate has been supplied!', 'wp-baseline'));
		}

		// Get the original post
		$post = get_post($post_id);

		if (!$post) {
			wp_die(sprintf(__('Post creation failed, could not find original post: %d', 'wp-baseline'), $post_id));
		}
		
		// Check if this post type is allowed
		if (!$this->is_post_type_allowed($post->post_type)) {
			wp_die(__('This post type cannot be duplicated.', 'wp-baseline'));
		}

		// Check permissions
		if (!$this->can_duplicate_post($post)) {
			wp_die(__('You do not have permission to duplicate this post.', 'wp-baseline'));
		}

		// Create the duplicate
		$new_post_id = $this->create_duplicate($post);

		if ($new_post_id) {
			// Redirect back to the posts list with success message
			$redirect_url = wp_get_referer();
			if (!$redirect_url) {
				$redirect_url = admin_url('edit.php?post_type=' . $post->post_type);
			}
			
			// Add success message parameter
			$redirect_url = add_query_arg([
				'duplicated' => 1,
				'duplicate_id' => $new_post_id
			], $redirect_url);
			
			wp_redirect($redirect_url);
			exit;
		} else {
			wp_die(__('Post creation failed', 'wp-baseline'));
		}
	}

	/**
	 * Create the actual duplicate.
	 *
	 * @param \WP_Post $post The original post object.
	 * @return int|false The new post ID on success, false on failure.
	 */
	protected function create_duplicate($post)
	{
		// Prepare post data
		$new_post_args = [
			'post_title'   => $post->post_title . ' (Copy)',
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			'post_status'  => 'draft', // Set as draft
			'post_type'    => $post->post_type,
			'post_author'  => get_current_user_id(),
			'post_parent'  => $post->post_parent,
			'menu_order'   => $post->menu_order,
		];

		// Insert the post
		$new_post_id = wp_insert_post($new_post_args);

		if (!$new_post_id) {
			return false;
		}

		// Copy taxonomies (categories, tags, etc.)
		$this->duplicate_taxonomies($post->ID, $new_post_id, $post->post_type);

		// Copy meta fields
		$this->duplicate_meta_fields($post->ID, $new_post_id);

		// Allow other plugins to hook into the duplication process
		do_action('wp_baseline_post_duplicated', $post->ID, $new_post_id, $post);

		return $new_post_id;
	}

	/**
	 * Copy taxonomies from original to duplicate.
	 *
	 * @param int    $old_id     The original post ID.
	 * @param int    $new_id     The new post ID.
	 * @param string $post_type  The post type.
	 */
	protected function duplicate_taxonomies($old_id, $new_id, $post_type)
	{
		$taxonomies = get_object_taxonomies($post_type);

		foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($old_id, $taxonomy, ['fields' => 'slugs']);
			if (!is_wp_error($post_terms)) {
				wp_set_object_terms($new_id, $post_terms, $taxonomy, false);
			}
		}
	}

	/**
	 * Copy meta fields from original to duplicate.
	 *
	 * @param int $old_id The original post ID.
	 * @param int $new_id The new post ID.
	 */
	protected function duplicate_meta_fields($old_id, $new_id)
	{
		$post_meta_infos = get_post_meta($old_id);

		if (count($post_meta_infos) != 0) {
			foreach ($post_meta_infos as $meta_key => $meta_value) {
				// Skip certain meta keys
				if ($this->should_skip_meta($meta_key)) {
					continue;
				}

				// Handle serialized data
				$meta_value = maybe_unserialize($meta_value[0]);
				add_post_meta($new_id, $meta_key, $meta_value);
			}
		}
	}

	/**
	 * Check if meta key should be skipped.
	 *
	 * @param string $meta_key The meta key to check.
	 * @return bool
	 */
	protected function should_skip_meta($meta_key)
	{
		$skip_meta = [
			'_edit_lock',
			'_edit_last',
			'_wp_old_slug',
			'_wp_page_template',
		];

		return in_array($meta_key, $skip_meta, true);
	}

	/**
	 * Show duplicate success notice.
	 */
	public function show_duplicate_notice()
	{
		if (isset($_GET['duplicated']) && $_GET['duplicated'] == '1') {
			$message = __('Post duplicated successfully.', 'wp-baseline');
			if (isset($_GET['duplicate_id'])) {
				$edit_link = get_edit_post_link($_GET['duplicate_id']);
				if ($edit_link) {
					$message .= ' <a href="' . esc_url($edit_link) . '">' . __('Edit duplicate', 'wp-baseline') . '</a>';
				}
			}
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				$message
			);
		}
	}
}
