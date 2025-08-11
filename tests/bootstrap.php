<?php
/**
 * PHPUnit bootstrap file for WP Baseline
 *
 * @package BuiltNorth\WPBaseline
 */

// Load Composer autoloader
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Bootstrap WP_Mock
WP_Mock::bootstrap();

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