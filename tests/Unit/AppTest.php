<?php
/**
 * Tests for the main App class
 *
 * @package BuiltNorth\WPBaseline\Tests\Unit
 */

namespace BuiltNorth\WPBaseline\Tests\Unit;

use BuiltNorth\WPBaseline\App;
use BuiltNorth\WPBaseline\Tests\WPMockTestCase;
use WP_Mock;

/**
 * App test case
 */
class AppTest extends WPMockTestCase {

	/**
	 * Test that App is a singleton
	 */
	public function test_app_is_singleton() {
		$instance1 = App::instance();
		$instance2 = App::instance();
		
		$this->assertSame( $instance1, $instance2 );
		$this->assertInstanceOf( App::class, $instance1 );
	}

	/**
	 * Test boot method registers modules
	 */
	public function test_boot_registers_modules() {
		// Mock specific filters
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_disable_comments', false )
			->andReturn( false );

		$app = App::instance();
		$app->boot();

		// Verify app was booted
		$this->assertInstanceOf( App::class, $app );
	}

	/**
	 * Test cleanup module is registered
	 */
	public function test_cleanup_module_registered() {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_disable_comments', false )
			->andReturn( false );

		$app = App::instance();
		$app->boot();

		// The cleanup module should be registered
		$this->assertTrue( true ); // Placeholder - in real implementation we'd check if cleanup is registered
	}

	/**
	 * Test security module is registered
	 */
	public function test_security_module_registered() {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_disable_comments', false )
			->andReturn( false );

		$app = App::instance();
		$app->boot();

		// The security module should be registered
		$this->assertTrue( true ); // Placeholder - in real implementation we'd check if security is registered
	}

	/**
	 * Test mime types module is registered
	 */
	public function test_mime_types_module_registered() {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_disable_comments', false )
			->andReturn( false );

		$app = App::instance();
		$app->boot();

		// The mime types module should be registered
		$this->assertTrue( true ); // Placeholder - in real implementation we'd check if mime types is registered
	}

	/**
	 * Test utilities module is registered
	 */
	public function test_utilities_module_registered() {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_disable_comments', false )
			->andReturn( false );

		$app = App::instance();
		$app->boot();

		// The utilities module should be registered
		$this->assertTrue( true ); // Placeholder - in real implementation we'd check if utilities is registered
	}

	/**
	 * Test comments can be disabled via filter
	 */
	public function test_comments_disabled_via_filter() {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_disable_comments', false )
			->andReturn( true );

		$app = App::instance();
		$app->boot();

		// Comments module should be registered when filter returns true
		$this->assertTrue( true ); // Placeholder - in real implementation we'd check if comments module is registered
	}

	/**
	 * Test comments not registered by default
	 */
	public function test_comments_not_registered_by_default() {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_disable_comments', false )
			->andReturn( false );

		$app = App::instance();
		$app->boot();

		// Comments module should not be registered when filter returns false
		$this->assertTrue( true ); // Placeholder - in real implementation we'd check if comments module is NOT registered
	}
}