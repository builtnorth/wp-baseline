<?php
/**
 * Tests for Security Headers
 *
 * @package BuiltNorth\WPBaseline\Tests\Unit\Security
 */

namespace BuiltNorth\WPBaseline\Tests\Unit\Security;

use BuiltNorth\WPBaseline\Security\Headers;
use BuiltNorth\WPBaseline\Tests\WPMockTestCase;
use WP_Mock;

/**
 * Headers test case
 */
class HeadersTest extends WPMockTestCase {

	/**
	 * Test constructor registers hooks
	 */
	public function test_constructor_registers_hooks() {
		$this->expect_action_added( 'send_headers', [ Headers::class, 'add_headers' ] );
		
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers', \Mockery::type( 'array' ) )
			->andReturnUsing( function( $filter, $headers ) {
				return $headers;
			});

		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers_csp', \Mockery::type( 'array' ) )
			->andReturnUsing( function( $filter, $csp ) {
				return $csp;
			});

		new Headers();
		
		$this->assertConditionsMet();
	}

	/**
	 * Test default security headers
	 */
	public function test_default_security_headers() {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers', \Mockery::type( 'array' ) )
			->andReturnUsing( function( $filter, $headers ) {
				// Check that default headers are present
				$this->assertArrayHasKey( 'X-Content-Type-Options', $headers );
				$this->assertArrayHasKey( 'X-Frame-Options', $headers );
				$this->assertArrayHasKey( 'Referrer-Policy', $headers );
				$this->assertArrayHasKey( 'Permissions-Policy', $headers );
				return $headers;
			});

		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers_csp', \Mockery::type( 'array' ) )
			->andReturnUsing( function( $filter, $csp ) {
				return $csp;
			});

		new Headers();
		
		$this->assertConditionsMet();
	}

	/**
	 * Test CSP directives structure
	 */
	public function test_csp_directives_structure() {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers', \Mockery::type( 'array' ) )
			->andReturnUsing( function( $filter, $headers ) {
				return $headers;
			});

		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers_csp', \Mockery::type( 'array' ) )
			->andReturnUsing( function( $filter, $csp ) {
				// Check CSP structure
				$this->assertIsArray( $csp );
				$this->assertArrayHasKey( 'default-src', $csp );
				$this->assertArrayHasKey( 'script-src', $csp );
				$this->assertArrayHasKey( 'style-src', $csp );
				return $csp;
			});

		new Headers();
		
		$this->assertConditionsMet();
	}

	/**
	 * Test headers can be filtered
	 */
	public function test_headers_can_be_filtered() {
		$custom_headers = [
			'X-Custom-Header' => 'custom-value',
		];

		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers', \Mockery::type( 'array' ) )
			->andReturn( $custom_headers );

		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers_csp', \Mockery::type( 'array' ) )
			->andReturn( [] );

		new Headers();
		
		$this->assertConditionsMet();
	}

	/**
	 * Test CSP can be filtered
	 */
	public function test_csp_can_be_filtered() {
		$custom_csp = [
			'default-src' => "'self' custom.com",
		];

		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers', \Mockery::type( 'array' ) )
			->andReturnUsing( function( $filter, $headers ) {
				return $headers;
			});

		WP_Mock::userFunction( 'apply_filters' )
			->with( 'wpbaseline_security_headers_csp', \Mockery::type( 'array' ) )
			->andReturn( $custom_csp );

		new Headers();
		
		$this->assertConditionsMet();
	}
}