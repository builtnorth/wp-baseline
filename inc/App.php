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

namespace WPBaseline;

use WPBaseline\Cleanup\Init as CleanupInit;
use WPBaseline\Comments\Init as CommentsInit;
use WPBaseline\Security\Init as SecurityInit;
use WPBaseline\MimeTypes\Init as MimeTypesInit;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

class App
{
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
