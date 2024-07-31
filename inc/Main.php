<?php

/**
 * ------------------------------------------------------------------
 * Main
 * ------------------------------------------------------------------
 *
 * Main class to initialize all other classes
 *
 * @package Core
 * @since Core 4.3.1
 *
 */

namespace BuiltNorth\Core;

/**
 * If called directly, abort.
 */
if (!defined('WPINC')) {
	die;
}

class Main
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
}
