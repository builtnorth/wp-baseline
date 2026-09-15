<?php
/**
 * Tests for Lottie Validation
 *
 * @package BuiltNorth\WPBaseline\Tests\Unit\MimeTypes\Lottie
 */

namespace BuiltNorth\WPBaseline\Tests\Unit\MimeTypes\Lottie;

use BuiltNorth\WPBaseline\MimeTypes\Lottie\Validate;
use BuiltNorth\WPBaseline\Tests\WPMockTestCase;
use WP_Mock;

/**
 * Validate test case
 */
class ValidateTest extends WPMockTestCase {

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

	/**
	 * Helper to invoke a private method via reflection.
	 */
	protected function invoke( $object, $method, array $args = [] ) {
		$reflection = new \ReflectionMethod( get_class( $object ), $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $object, $args );
	}

	/**
	 * Test a real dotLottie archive (manifest.json + animations/) passes.
	 *
	 * Guards the actual fix: a prior version accepted any non-JSON file
	 * with content, with no structural check at all.
	 */
	public function test_validate_lottie_archive_accepts_real_dotlottie_structure() {
		$path = $this->make_zip( [
			'manifest.json'          => '{"generator":"test"}',
			'animations/data.json'   => '{"v":"5.0.0","layers":[]}',
		] );

		$validate = new Validate();
		$result = $this->invoke( $validate, 'validate_lottie_archive', [ $path ] );

		$this->assertTrue( $result );
	}

	/**
	 * Test an arbitrary binary renamed to .lottie is rejected.
	 *
	 * This is the exact gap being fixed: previously
	 * strlen($content) > 0 was the only check on this path.
	 */
	public function test_validate_lottie_archive_rejects_arbitrary_binary() {
		$path = tempnam( sys_get_temp_dir(), 'wpbaseline_test_' );
		file_put_contents( $path, "not a zip file at all, just bytes\x00\x01\x02" );
		$this->temp_files[] = $path;

		$validate = new Validate();
		$result = $this->invoke( $validate, 'validate_lottie_archive', [ $path ] );

		$this->assertFalse( $result );
	}

	/**
	 * Test a real ZIP missing the expected dotLottie internals is rejected.
	 */
	public function test_validate_lottie_archive_rejects_zip_without_expected_entries() {
		$path = $this->make_zip( [
			'readme.txt' => 'just a plain zip, not a dotLottie archive',
		] );

		$validate = new Validate();
		$result = $this->invoke( $validate, 'validate_lottie_archive', [ $path ] );

		$this->assertFalse( $result );
	}

	/**
	 * Test a ZIP with only manifest.json (no animations/) is rejected.
	 */
	public function test_validate_lottie_archive_rejects_missing_animations_dir() {
		$path = $this->make_zip( [
			'manifest.json' => '{"generator":"test"}',
		] );

		$validate = new Validate();
		$result = $this->invoke( $validate, 'validate_lottie_archive', [ $path ] );

		$this->assertFalse( $result );
	}

	/**
	 * Build a temp ZIP file with the given entries and return its path.
	 *
	 * @param array<string, string> $entries Relative path => file contents.
	 * @return string Path to the temp ZIP file.
	 */
	protected function make_zip( array $entries ) {
		$path = tempnam( sys_get_temp_dir(), 'wpbaseline_test_' ) . '.zip';
		$this->temp_files[] = $path;

		$zip = new \ZipArchive();
		$zip->open( $path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );

		foreach ( $entries as $name => $contents ) {
			$zip->addFromString( $name, $contents );
		}

		$zip->close();

		return $path;
	}
}
