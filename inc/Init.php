<?php

/**
 * ------------------------------------------------------------------
 * Init
 * ------------------------------------------------------------------
 *
 * Init class to initialize all other classes
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

class Init
{
	private $cleanup;
	private $security;
	private $comments;

	public function __construct()
	{
		$this->cleanup = new Cleanup();
		$this->security = new Security();
		$this->comments = new DisableComments();
	}

	public function init()
	{
		$this->cleanup->init();
		$this->security->init();
		$this->comments->init();
	}

	public static function boot($hook = 'init')
	{
		add_action($hook, function () {
			$instance = new self();
			$instance->init();
		}, 10);
	}
}
