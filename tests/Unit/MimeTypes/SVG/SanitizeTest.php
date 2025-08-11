<?php
/**
 * Tests for SVG Sanitization
 *
 * @package BuiltNorth\WPBaseline\Tests\Unit\MimeTypes\SVG
 */

namespace BuiltNorth\WPBaseline\Tests\Unit\MimeTypes\SVG;

use BuiltNorth\WPBaseline\MimeTypes\SVG\Sanitize;
use BuiltNorth\WPBaseline\Tests\WPMockTestCase;
use WP_Mock;

/**
 * Sanitize test case
 */
class SanitizeTest extends WPMockTestCase {

	/**
	 * Test constructor registers hooks
	 */
	public function test_constructor_registers_hooks() {
		$this->expect_filter_added( 'wp_handle_upload_prefilter', [ Sanitize::class, 'sanitize_svg' ] );
		$this->expect_filter_added( 'wp_check_filetype_and_ext', [ Sanitize::class, 'fix_mime_type' ], 10, 4 );
		
		new Sanitize();
		
		$this->assertConditionsMet();
	}

	/**
	 * Test SVG mime type is fixed
	 */
	public function test_fix_mime_type_for_svg() {
		$sanitize = new Sanitize();
		
		$file = '/path/to/file.svg';
		$filename = 'file.svg';
		
		// Mock wp_check_filetype_and_ext behavior
		$data = [
			'ext' => false,
			'type' => false,
			'proper_filename' => false,
		];
		
		$mimes = [ 'svg' => 'image/svg+xml' ];
		
		WP_Mock::userFunction( 'wp_check_filetype' )
			->with( $filename, $mimes )
			->andReturn( [
				'ext' => 'svg',
				'type' => 'image/svg+xml',
			] );
		
		$result = $sanitize->fix_mime_type( $data, $file, $filename, $mimes );
		
		$this->assertEquals( 'svg', $result['ext'] );
		$this->assertEquals( 'image/svg+xml', $result['type'] );
	}

	/**
	 * Test non-SVG files are not modified
	 */
	public function test_non_svg_files_not_modified() {
		$sanitize = new Sanitize();
		
		$file = '/path/to/file.jpg';
		$filename = 'file.jpg';
		
		$data = [
			'ext' => 'jpg',
			'type' => 'image/jpeg',
			'proper_filename' => false,
		];
		
		$mimes = [ 'jpg' => 'image/jpeg' ];
		
		$result = $sanitize->fix_mime_type( $data, $file, $filename, $mimes );
		
		$this->assertEquals( $data, $result );
	}

	/**
	 * Test SVG sanitization for non-SVG files
	 */
	public function test_sanitize_svg_ignores_non_svg() {
		$sanitize = new Sanitize();
		
		$file = [
			'name' => 'test.jpg',
			'type' => 'image/jpeg',
			'tmp_name' => '/tmp/test.jpg',
			'error' => 0,
			'size' => 1024,
		];
		
		$result = $sanitize->sanitize_svg( $file );
		
		$this->assertEquals( $file, $result );
	}

	/**
	 * Test SVG sanitization error handling
	 */
	public function test_sanitize_svg_with_invalid_file() {
		$sanitize = new Sanitize();
		
		$file = [
			'name' => 'test.svg',
			'type' => 'image/svg+xml',
			'tmp_name' => '/tmp/nonexistent.svg',
			'error' => 0,
			'size' => 1024,
		];
		
		WP_Mock::userFunction( 'file_get_contents' )
			->with( $file['tmp_name'] )
			->andReturn( false );
		
		WP_Mock::userFunction( '__' )
			->with( 'Failed to read SVG file', 'wpbaseline' )
			->andReturn( 'Failed to read SVG file' );
		
		$result = $sanitize->sanitize_svg( $file );
		
		$this->assertArrayHasKey( 'error', $result );
		$this->assertEquals( 'Failed to read SVG file', $result['error'] );
	}

	/**
	 * Test valid SVG is sanitized
	 */
	public function test_valid_svg_is_sanitized() {
		$sanitize = new Sanitize();
		
		$svg_content = '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40"/></svg>';
		
		$file = [
			'name' => 'test.svg',
			'type' => 'image/svg+xml',
			'tmp_name' => '/tmp/test.svg',
			'error' => 0,
			'size' => strlen( $svg_content ),
		];
		
		WP_Mock::userFunction( 'file_get_contents' )
			->with( $file['tmp_name'] )
			->andReturn( $svg_content );
		
		WP_Mock::userFunction( 'file_put_contents' )
			->with( $file['tmp_name'], \Mockery::type( 'string' ) )
			->andReturn( strlen( $svg_content ) );
		
		$result = $sanitize->sanitize_svg( $file );
		
		$this->assertEquals( $file, $result );
	}
}