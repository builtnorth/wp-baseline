<?php

/**
 * ------------------------------------------------------------------
 * Comment Blocks
 * ------------------------------------------------------------------
 *
 * Disable comment-related blocks when comments are disabled
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace BuiltNorth\WPBaseline\Comments;

class Blocks
{
	/**
	 * Initialize the class.
	 */
	public function init()
	{
		add_filter('allowed_block_types_all', [$this, 'disable_comment_blocks'], 10, 2);
	}

	/**
	 * Disable comment-related blocks when comments are disabled.
	 *
	 * @param bool|string[] $allowed_block_types Array of allowed block types or boolean to allow all.
	 * @param object $block_editor_context The current block editor context.
	 * @return bool|string[] Array of allowed block types or boolean.
	 */
	public function disable_comment_blocks($allowed_block_types, $block_editor_context)
	{
		// Get the list of comment-related blocks
		$comment_blocks = $this->get_comment_blocks();

		// If allowed_block_types is true (all blocks allowed), we need to get all registered blocks
		if ($allowed_block_types === true) {
			$registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
			$allowed_block_types = array_keys($registered_blocks);
		}

		// Remove comment blocks from the allowed list
		if (is_array($allowed_block_types)) {
			$allowed_block_types = array_diff($allowed_block_types, $comment_blocks);
		}

		return $allowed_block_types;
	}

	/**
	 * Get the list of comment-related blocks to disable.
	 *
	 * @return array List of comment block names.
	 */
	private function get_comment_blocks()
	{
		return [
			'core/comment-author-name',
			'core/comment-content',
			'core/comment-date',
			'core/comment-edit-link',
			'core/comment-reply-link',
			'core/comment-template',
			'core/comments',
			'core/comments-pagination',
			'core/comments-pagination-next',
			'core/comments-pagination-numbers',
			'core/comments-pagination-previous',
			'core/comments-title',
			'core/latest-comments',
			'core/post-comments-count',
			'core/post-comments-form',
			'core/post-comments-link',
			'core/post-comment-author',
			'core/post-comment-author-avatar',
			'core/post-comment-content',
			'core/post-comment-date',
		];
	}
}