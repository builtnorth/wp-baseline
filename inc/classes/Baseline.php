<?php

/**
 * Package entry point.
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace BuiltNorth\WPBaseline;

use BuiltNorth\WPBaseline\Cleanup\Init as CleanupInit;
use BuiltNorth\WPBaseline\Comments\Init as CommentsInit;
use BuiltNorth\WPBaseline\Security\Init as SecurityInit;
use BuiltNorth\WPBaseline\MimeTypes\Init as MimeTypesInit;
use BuiltNorth\WPBaseline\Utilities\Init as UtilitiesInit;

// Don't load directly.
defined('ABSPATH') || defined('WP_CLI') || exit;

/**
 * Baseline: single entry point for the package.
 *
 * Named for what it is rather than for its role. `App` is the name every
 * consuming plugin and theme uses for its own root class, so importing this
 * package's entry point forced an alias at the call site — Polaris core's
 * `Dependencies` carried `use BuiltNorth\WPBaseline\App as Baseline;`,
 * aliasing it to this very name alongside two other packages' `App` classes,
 * all three in the same file.
 *
 * Call `Baseline::instance()->boot()` once; `boot()` hands off to the per-area
 * `Init` classes (cleanup, comments, security, mime types, utilities).
 */
class Baseline
{
	/**
	 * Holds the single instance of this class.
	 *
	 * @var Baseline|null
	 */
	protected static $instance = null;

	/**
	 * Get the single instance of this class.
	 *
	 * @return Baseline
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

		// Initialize utilities classes
		$utilities = new UtilitiesInit();
		$utilities->init();
	}
}
