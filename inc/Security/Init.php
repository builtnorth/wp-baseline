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

namespace WPBaseline\Security;

// Don't load directly.
defined('ABSPATH') || exit;

class Init
{
	/**
	 * Initialize all cleanup classes.
	 */
	public function init()
	{
		$classes = [
			'Constants',
			'Pingbacks',
			'Version',
			'XMLRPC',
			'Headers',
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
