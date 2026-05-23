<?php

declare(strict_types=1);

/**
 * Locks open registration and default role on managed WaaS sites.
 *
 * @package WPBaseline\Security
 */

namespace BuiltNorth\WPBaseline\Security;

defined('ABSPATH') || exit;

/**
 * Prevents tenants from enabling public registration via Settings → General.
 */
final class UserRegistrationPolicy
{
	public function init(): void
	{
		add_filter('pre_option_users_can_register', [$this, 'filter_users_can_register']);
		add_filter('pre_update_option_users_can_register', [$this, 'block_users_can_register_update'], 10, 2);
		add_filter('pre_option_default_role', [$this, 'filter_default_role']);
		add_filter('pre_update_option_default_role', [$this, 'block_default_role_update'], 10, 2);
	}

	public function filter_users_can_register(mixed $value): string
	{
		if (! $this->should_lock()) {
			return $value;
		}

		return $this->users_can_register_value();
	}

	/**
	 * @param mixed $value
	 * @param mixed $old_value
	 */
	public function block_users_can_register_update($value, $old_value): mixed
	{
		if (! $this->should_lock()) {
			return $value;
		}

		return $old_value;
	}

	public function filter_default_role(mixed $value): string
	{
		if (! $this->should_lock()) {
			return is_string($value) ? $value : 'subscriber';
		}

		return $this->default_role_value();
	}

	/**
	 * @param mixed $value
	 * @param mixed $old_value
	 */
	public function block_default_role_update($value, $old_value): mixed
	{
		if (! $this->should_lock()) {
			return $value;
		}

		return $old_value;
	}

	private function should_lock(): bool
	{
		return (bool) apply_filters('wpbaseline_lock_site_registration', $this->default_lock_enabled());
	}

	private function default_lock_enabled(): bool
	{
		if (! class_exists(\Polaris\API::class)) {
			return false;
		}

		try {
			return \Polaris\API::Admin()->is_managed_site();
		} catch (\Throwable $e) {
			return false;
		}
	}

	private function users_can_register_value(): string
	{
		$allowed = apply_filters('wpbaseline_users_can_register', false);

		return $allowed ? '1' : '0';
	}

	private function default_role_value(): string
	{
		$role = apply_filters('wpbaseline_default_role', 'subscriber');

		return is_string($role) && $role !== '' ? $role : 'subscriber';
	}
}
