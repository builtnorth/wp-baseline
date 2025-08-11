<?php
/**
 * Base test case for WP_Mock tests
 *
 * @package BuiltNorth\WPBaseline\Tests
 */

namespace BuiltNorth\WPBaseline\Tests;

use WP_Mock;
use WP_Mock\Tools\TestCase as BaseTestCase;

/**
 * Base test case class
 */
abstract class WPMockTestCase extends BaseTestCase {

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
	}

	/**
	 * Tear down after each test
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Helper to mock WordPress hooks
	 *
	 * @param string $hook_name Hook name.
	 * @param mixed  $return_value Return value.
	 */
	protected function mock_filter( $hook_name, $return_value ) {
		WP_Mock::onFilter( $hook_name )
			->with( \Mockery::any() )
			->reply( $return_value );
	}

	/**
	 * Helper to expect an action to be added
	 *
	 * @param string $action Action name.
	 * @param mixed  $callback Callback.
	 * @param int    $priority Priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 */
	protected function expect_action_added( $action, $callback, $priority = 10, $accepted_args = 1 ) {
		WP_Mock::expectActionAdded( $action, $callback, $priority, $accepted_args );
	}

	/**
	 * Helper to expect a filter to be added
	 *
	 * @param string $filter Filter name.
	 * @param mixed  $callback Callback.
	 * @param int    $priority Priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 */
	protected function expect_filter_added( $filter, $callback, $priority = 10, $accepted_args = 1 ) {
		WP_Mock::expectFilterAdded( $filter, $callback, $priority, $accepted_args );
	}
}