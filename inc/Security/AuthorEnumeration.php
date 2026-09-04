<?php

/**
 * ------------------------------------------------------------------
 * AuthorEnumeration
 * ------------------------------------------------------------------
 *
 * Blocks username enumeration via ?author=N
 *
 * @package WPBaseline
 * @since 2.2.0
 */

namespace BuiltNorth\WPBaseline\Security;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class AuthorEnumeration
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		if ((bool) apply_filters('wpbaseline_block_author_enumeration', true)) {
			// Priority 0: WordPress core's own redirect_canonical() runs on
			// template_redirect at the default priority (10) and would
			// otherwise 301 straight to /author/username/ before this ever
			// runs, revealing the username the request was trying to block.
			add_action('template_redirect', [$this, 'block_numeric_author_lookup'], 0);
		}
	}

	/**
	 * WordPress resolves `?author=N` requests to the author's archive URL,
	 * revealing a valid username for anyone enumerating IDs. The pretty
	 * permalink form (`/author/username/`) never sets `author` as a raw
	 * query-string param — it resolves to `author_name` before `WP_Query`
	 * ever runs — so checking for `author` in $_GET reliably targets only
	 * the numeric-ID lookup, not legitimate author archive requests.
	 */
	public function block_numeric_author_lookup()
	{
		// Matches the capability RestAPI.php already requires to list users
		// via the REST endpoint — a logged-in Subscriber has no more
		// legitimate need to enumerate user IDs than an anonymous visitor.
		if (current_user_can('list_users')) {
			return;
		}

		if (!isset($_GET['author']) || '' === $_GET['author']) {
			return;
		}

		if (!is_author()) {
			return;
		}

		wp_die(
			esc_html__('Not found.', 'wp-baseline'),
			esc_html__('Not Found', 'wp-baseline'),
			['response' => 404]
		);
	}
}
