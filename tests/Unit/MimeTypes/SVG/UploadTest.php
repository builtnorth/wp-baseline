<?php
/**
 * Tests for SVG Upload handling
 *
 * @package BuiltNorth\WPBaseline\Tests\Unit\MimeTypes\SVG
 */

namespace BuiltNorth\WPBaseline\Tests\Unit\MimeTypes\SVG;

use BuiltNorth\WPBaseline\MimeTypes\SVG\Upload;
use BuiltNorth\WPBaseline\Tests\WPMockTestCase;
use WP_Mock;

/**
 * Upload test case
 */
class UploadTest extends WPMockTestCase {

	/**
	 * Test non-SVG files are not changed.
	 */
	public function test_check_svg_filetype_ignores_non_svg() {
		$upload = new Upload();

		$data = [
			'ext'             => 'jpg',
			'type'            => 'image/jpeg',
			'proper_filename' => null,
		];

		$result = $upload->check_svg_filetype( $data, '/tmp/file.jpg', 'file.jpg', [] );

		$this->assertSame( $data, $result );
	}

	/**
	 * Test SVG filetype is corrected.
	 */
	public function test_check_svg_filetype_sets_svg_type() {
		$upload = new Upload();

		WP_Mock::userFunction( 'wp_check_filetype' )
			->with( 'icon.svg', [ 'svg' => 'image/svg+xml' ] )
			->andReturn(
				[
					'ext'  => 'svg',
					'type' => 'image/svg+xml',
				]
			);

		$result = $upload->check_svg_filetype(
			[
				'ext'             => false,
				'type'            => false,
				'proper_filename' => null,
			],
			'/tmp/icon.svg',
			'icon.svg',
			[ 'svg' => 'image/svg+xml' ]
		);

		$this->assertSame( 'svg', $result['ext'] );
		$this->assertSame( 'image/svg+xml', $result['type'] );
	}
}

