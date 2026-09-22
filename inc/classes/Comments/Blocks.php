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
		// Check if comments should be disabled
		if (!apply_filters('wpbaseline_disable_comments', false)) {
			return;
		}

		add_filter('allowed_block_types_all', [$this, 'disable_comment_blocks'], 999, 2);
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
		// If another filter has already limited the blocks, respect that
		if ($allowed_block_types === false) {
			// No blocks allowed, return as is
			return $allowed_block_types;
		}

		// Get the list of comment-related blocks
		$comment_blocks = $this->get_comment_blocks();

		// If all blocks are allowed (true), we need to be more careful
		if ($allowed_block_types === true) {
			// Instead of converting to array, use a different approach
			// Register a separate filter to unregister comment blocks
			add_action('enqueue_block_editor_assets', function() use ($comment_blocks) {
				wp_add_inline_script(
					'wp-blocks',
					'wp.domReady(function() {
						const commentBlocks = ' . json_encode($comment_blocks) . ';
						commentBlocks.forEach(function(blockName) {
							if (wp.blocks.getBlockType(blockName)) {
								wp.blocks.unregisterBlockType(blockName);
							}
						});
					});'
				);
			});
			return $allowed_block_types;
		}

		// If we have an array of allowed blocks, remove comment blocks
		if (is_array($allowed_block_types)) {
			$allowed_block_types = array_values(array_diff($allowed_block_types, $comment_blocks));
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
			'core/post-comments',
			'core/post-comments-form',
		];
	}
}