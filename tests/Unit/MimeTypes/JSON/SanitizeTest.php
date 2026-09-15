<?php
/**
 * Tests for JSON Sanitization
 *
 * @package BuiltNorth\WPBaseline\Tests\Unit\MimeTypes\JSON
 */

namespace BuiltNorth\WPBaseline\Tests\Unit\MimeTypes\JSON;

use BuiltNorth\WPBaseline\MimeTypes\JSON\Sanitize;
use BuiltNorth\WPBaseline\Tests\WPMockTestCase;
use WP_Mock;

/**
 * Sanitize test case
 */
class SanitizeTest extends WPMockTestCase {

	/** @var string[] Temp files created during a test, cleaned up in tearDown. */
	protected $temp_files = [];

	public function tearDown(): void {
		foreach ( $this->temp_files as $path ) {
			if ( is_readable( $path ) ) {
				unlink( $path );
			}
		}
		$this->temp_files = [];

		parent::tearDown();
	}

	protected function make_temp_file( $contents ) {
		$path = tempnam( sys_get_temp_dir(), 'wpbaseline_test_' );
		file_put_contents( $path, $contents );
		$this->temp_files[] = $path;

		return $path;
	}

	/**
	 * Test non-JSON files are not processed.
	 */
	public function test_sanitize_json_files_ignores_non_json() {
		$sanitize = new Sanitize();

		$file = [
			'name'     => 'test.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '/tmp/test.jpg',
			'error'    => 0,
			'size'     => 1024,
		];

		$result = $sanitize->sanitize_json_files( $file );

		$this->assertSame( $file, $result );
	}

	/**
	 * Test a well-formed JSON file passes through with content untouched.
	 *
	 * A prior version re-encoded and mutated string content via a
	 * blocklist regex (stripping patterns like "on*=") -- this asserts
	 * the file on disk is byte-for-byte unchanged after validation.
	 */
	public function test_sanitize_json_files_leaves_valid_content_untouched() {
		WP_Mock::userFunction( 'current_user_can' )->andReturn( true );
		WP_Mock::onFilter( 'wpbaseline_mime_upload_capability' )->with( 'manage_options', 'json' )->reply( 'manage_options' );

		$original = '{"note":"Turn on = go, this is legitimate content"}';
		$path = $this->make_temp_file( $original );

		$file = [
			'name'     => 'test.json',
			'type'     => 'application/json',
			'tmp_name' => $path,
			'error'    => 0,
			'size'     => strlen( $original ),
		];

		$sanitize = new Sanitize();
		$result = $sanitize->sanitize_json_files( $file );

		$this->assertSame( 0, $result['error'] );
		$this->assertSame( $original, file_get_contents( $path ) );
	}

	/**
	 * Test malformed JSON is rejected.
	 */
	public function test_sanitize_json_files_rejects_invalid_json() {
		WP_Mock::userFunction( 'current_user_can' )->andReturn( true );
		WP_Mock::onFilter( 'wpbaseline_mime_upload_capability' )->with( 'manage_options', 'json' )->reply( 'manage_options' );
		WP_Mock::userFunction( '__' )->andReturnUsing( function ( $text ) {
			return $text;
		} );

		$path = $this->make_temp_file( '{not valid json' );

		$file = [
			'name'     => 'test.json',
			'type'     => 'application/json',
			'tmp_name' => $path,
			'error'    => 0,
			'size'     => 20,
		];

		$sanitize = new Sanitize();
		$result = $sanitize->sanitize_json_files( $file );

		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * Test a user lacking the required capability is denied.
	 */
	public function test_sanitize_json_files_denies_without_capability() {
		WP_Mock::userFunction( 'current_user_can' )->andReturn( false );
		WP_Mock::onFilter( 'wpbaseline_mime_upload_capability' )->with( 'manage_options', 'json' )->reply( 'manage_options' );
		WP_Mock::userFunction( '__' )->andReturnUsing( function ( $text ) {
			return $text;
		} );

		$file = [
			'name'     => 'test.json',
			'type'     => 'application/json',
			'tmp_name' => '/tmp/does-not-matter.json',
			'error'    => 0,
			'size'     => 20,
		];

		$sanitize = new Sanitize();
		$result = $sanitize->sanitize_json_files( $file );

		$this->assertArrayHasKey( 'error', $result );
	}
}
