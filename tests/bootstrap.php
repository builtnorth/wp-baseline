<?php
/**
 * PHPUnit bootstrap file for WP Baseline
 *
 * @package BuiltNorth\WPBaseline
 */

// Load Composer autoloader, falling back to the monorepo root's autoloader
// when this package has no standalone vendor/ install (the normal dev setup —
// see "Autoloader Architecture" in the root CLAUDE.md).
$autoloader             = dirname( __DIR__ ) . '/vendor/autoload.php';
$using_root_autoloader = ! file_exists( $autoloader );
if ( $using_root_autoloader ) {
	$autoloader = dirname( __DIR__, 3 ) . '/vendor/autoload.php';
}
require_once $autoloader;

// The root autoloader only carries this package's own runtime `autoload` PSR-4
// mapping, never a dependency's `autoload-dev` — register the Tests namespace
// by hand when running under the root autoloader.
if ( $using_root_autoloader ) {
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = 'BuiltNorth\\WPBaseline\\Tests\\';
			if ( ! str_starts_with( $class, $prefix ) ) {
				return;
			}
			$relative = substr( $class, strlen( $prefix ) );
			$file     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);
}

// Enable Patchwork for better function mocking in namespaces
WP_Mock::setUsePatchwork( true );

// Bootstrap WP_Mock
WP_Mock::bootstrap();

// Pre-create WordPress functions in the namespaces to prevent undefined function errors
// This is needed because the classes are autoloaded and call functions before we can mock them
require_once __DIR__ . '/namespace-functions.php';

// Define WordPress constants that might be used
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', 'http://example.org/wp-content' );
}

// Mock WordPress functions that might be needed globally
if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir() {
		return array(
			'path'    => '/tmp/uploads',
			'url'     => 'http://example.org/uploads',
			'basedir' => '/tmp/uploads',
			'baseurl' => 'http://example.org/uploads',
		);
	}
}

// Mock common WordPress classes if needed
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $errors = array();
		private $error_data = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( ! empty( $code ) ) {
				$this->errors[ $code ][] = $message;
				if ( ! empty( $data ) ) {
					$this->error_data[ $code ] = $data;
				}
			}
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return $codes ? $codes[0] : '';
		}

		public function get_error_message( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			$messages = isset( $this->errors[ $code ] ) ? $this->errors[ $code ] : array();
			return $messages ? $messages[0] : '';
		}

		public function has_errors() {
			return ! empty( $this->errors );
		}
	}
}