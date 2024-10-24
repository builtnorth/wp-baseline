<?php

/**
 * ------------------------------------------------------------------
 * Init
 * ------------------------------------------------------------------
 *
 * Initialize the cleanup classes
 *
 * @package WPBaseline
 * @since 1.0.0
 */

namespace WPBaseline\Comments;

// Don't load directly.
defined('ABSPATH') || exit;

class Init
{
	/**
	 * Initialize all cleanup classes.
	 */
	public function init()
	{
		// Check if comments should be disabled
		$disable_comments = apply_filters('wpbaseline_disable_comments', false);

		if ($disable_comments) {
			$classes = [
				'Actions',
				'Filters'
			];

			// Loop through each class and initialize it
			foreach ($classes as $class) {
				$class_name = __NAMESPACE__ . '\\' . $class;
				if (class_exists($class_name)) {
					$instance = new $class_name();
					if (method_exists($instance, 'init')) {
						$instance->init();
					}
				}
			}
		}
	}
}
