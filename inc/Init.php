<?php

/**
 * ------------------------------------------------------------------
 * Class: Init
 * ------------------------------------------------------------------
 *
 * This class is responsible for loading and initializing classes.
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

/**
 * Class Init
 */
class Init
{
	/**
	 * Holds the single instance of this class.
	 *
	 * @var Init|null
	 */
	protected static $instance = null;

	/**
	 * List of helper classes to be loaded.
	 *
	 * @var array
	 */
	protected $classes = [
		'Security',
		'Cleanup',
		'DisableComments'
	];

	/**
	 * Get the single instance of this class.
	 *
	 * @return Init
	 */
	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 * Triggers the init method.
	 */
	private function __construct()
	{
		$this->init();
	}

	/**
	 * Initialize the Init.
	 * This method is called automatically when the class is instantiated.
	 */
	private function init()
	{
		$this->load_classes();
	}

	/**
	 * Load and initialize all classes.
	 */
	protected function load_classes()
	{
		foreach ($this->classes as $class) {
			$full_class_name = __NAMESPACE__ . '\\' . $class;

			// Check if the class exists
			if (class_exists($full_class_name)) {
				// Instantiate the class
				$instance = new $full_class_name();

				// If the class has an init method, call it
				if (method_exists($instance, 'init')) {
					$instance->init();
				}
			} else {
				// Log an error if the class is not found
				error_log("Class $full_class_name not found.");
			}
		}
	}
}
