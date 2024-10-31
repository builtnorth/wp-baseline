<?php

/**
 * ------------------------------------------------------------------
 * Headers
 * ------------------------------------------------------------------
 *
 * Implements security headers for WordPress.
 *
 * @package WPBaseline
 * @since 2.0.0
 */

namespace WPBaseline\Security;

// Don't load directly.
defined('ABSPATH') || exit;

class Headers
{
	/**
	 * Initialize the class.
	 */
	public function init(): void
	{
		// Check if security headers should be applied
		if (apply_filters('wpbaseline_enable_security_headers', true)) {
			add_action('send_headers', [$this, 'apply_headers']);
		}
	}

	/**
	 * Apply security headers.
	 */
	public function apply_headers(): void
	{
		$headers = $this->get_headers();

		foreach ($headers as $header => $value) {
			header("$header: $value");
		}

		if (!headers_sent()) {
			$this->apply_csp();
		}
	}

	/**
	 * Get all security headers.
	 *
	 * @return array Array of security headers
	 */
	private function get_headers(): array
	{
		$defaults = [
			'X-Content-Type-Options' => 'nosniff',
			'X-Frame-Options' => 'SAMEORIGIN',
			'X-XSS-Protection' => '1; mode=block',
			'Referrer-Policy' => 'strict-origin-when-cross-origin',
			'Permissions-Policy' => 'geolocation=(), microphone=()',
			'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
		];

		/**
		 * Filter the security headers.
		 *
		 * @param array $headers Array of security headers
		 */
		return apply_filters('wpbaseline_security_headers', $defaults);
	}

	/**
	 * Apply Content Security Policy.
	 */
	private function apply_csp(): void
	{
		$defaults = [
			'default-src'  => "'self'",
			'script-src'   => "'self' 'unsafe-inline' 'unsafe-eval' https: *.googleapis.com *.gstatic.com *.google.com *.google-analytics.com *.doubleclick.net *.wordpress.org *.wp.com",
			'style-src'    => "'self' 'unsafe-inline' https:",
			'img-src'      => "'self' data: https: *",
			'font-src'     => "'self' data: https:",
			'connect-src'  => "'self' https:",
			'media-src'    => "'self' https:",
			'object-src'   => "'none'",
			'frame-src'    => "'self' https:",
			'base-uri'     => "'self'",
			'form-action'  => "'self'",
		];

		/**
		 * Filter the Content Security Policy.
		 *
		 * @param array $csp Array of CSP directives
		 */
		$csp = apply_filters('wpbaseline_security_headers_csp', $defaults);

		$header = '';
		foreach ($csp as $directive => $value) {
			$header .= "$directive $value; ";
		}

		if (!empty($header)) {
			header('Content-Security-Policy: ' . trim($header));
		}
	}
}
