<?php
/**
 * Tests for Duplicate Post utility
 *
 * @package BuiltNorth\WPBaseline\Tests\Unit\Utilities
 */

namespace BuiltNorth\WPBaseline\Tests\Unit\Utilities;

use BuiltNorth\WPBaseline\Tests\WPMockTestCase;
use BuiltNorth\WPBaseline\Utilities\DuplicatePost;
use WP_Mock;

/**
 * DuplicatePost test case
 */
class DuplicatePostTest extends WPMockTestCase {

	/**
	 * Test non-WP_Post input is ignored.
	 */
	public function test_add_duplicate_link_ignores_non_post_input() {
		$duplicate_post = new DuplicatePost();
		$actions        = [ 'edit' => '<a href="#">Edit</a>' ];

		$result = $duplicate_post->add_duplicate_link( $actions, new \stdClass() );

		$this->assertSame( $actions, $result );
	}

	/**
	 * Test permission check safely handles invalid post type object.
	 */
	public function test_can_duplicate_post_returns_false_for_invalid_post_type_object() {
		$duplicate_post = new DuplicatePost();

		WP_Mock::userFunction( 'get_post_type_object' )
			->with( 'page' )
			->andReturn( false );

		$method = new \ReflectionMethod( DuplicatePost::class, 'can_duplicate_post' );
		$method->setAccessible( true );

		$result = $method->invoke(
			$duplicate_post,
			(object) [
				'post_type' => 'page',
			]
		);

		$this->assertFalse( $result );
	}
}
