<?php

/**
 * ------------------------------------------------------------------
 * Core
 * ------------------------------------------------------------------
 *
 * Core class to initialize all other classes
 *
 * @package Baseline
 * @since Baseline 4.3.1
 *
 */

namespace BuiltNorth\Baseline;

/**
 * If called directly, abort.
 */
if (!defined('WPINC')) {
	die;
}

class Core
{
	private $cleanup;
	private $security;

	public function __construct()
	{
		$this->cleanup = new Cleanup();
		$this->security = new Security();
	}

	public function init()
	{
		$this->cleanup->init();
		$this->security->init();
	}

	public static function boot($hook = 'plugins_loaded')
	{
		add_action($hook, function () {
			$instance = new self();
			$instance->init();
		});
	}
}
