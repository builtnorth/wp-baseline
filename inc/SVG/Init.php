<?php

/**
 * ------------------------------------------------------------------
 * Init
 * ------------------------------------------------------------------
 *
 * Initialize the SVG classes
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace WPBaseline\SVG;

// Don't load directly.
defined('ABSPATH') || exit;

class Init
{
	/**
	 * Initialize all SVG classes.
	 */
	public function init()
	{
		// Check if the SVG support should be enabled
		$enable_svg_support = apply_filters('wpbaseline_enable_svg_support', true);

		if ($enable_svg_support) {
			$classes = [
				'Upload',
				'Sanitize'
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
