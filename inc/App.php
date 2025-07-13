<?php

/**
 * ------------------------------------------------------------------
 * App
 * ------------------------------------------------------------------
 * 
 * Bootstrap all classes.
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace BuiltNorth\WPBaseline;

use BuiltNorth\WPBaseline\Cleanup\Init as CleanupInit;
use BuiltNorth\WPBaseline\Comments\Init as CommentsInit;
use BuiltNorth\WPBaseline\Security\Init as SecurityInit;
use BuiltNorth\WPBaseline\MimeTypes\Init as MimeTypesInit;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class App
{
	/**
	 * Holds the single instance of this class.
	 *
	 * @var App|null
	 */
	protected static $instance = null;

	/**
	 * Get the single instance of this class.
	 *
	 * @return App
	 */
	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - make it public temporarily for testing
	 */
	public function __construct()
	{
		// Constructor does nothing - initialization happens in boot()
	}

	/**
	 * Bootstrap all classes.
	 */
	public function boot()
	{
		// Initialize cleanup classes
		$cleanup = new CleanupInit();
		$cleanup->init();

		// Initialize comments classes
		$comments = new CommentsInit();
		$comments->init();

		// Initialize security classes
		$security = new SecurityInit();
		$security->init();

		// Initialize mime types classes
		$mime_types = new MimeTypesInit();
		$mime_types->init();
	}
}
