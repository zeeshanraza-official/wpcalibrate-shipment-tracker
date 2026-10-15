<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Tracking;

defined('ABSPATH') || exit;

/**
 * Throttles public guest tracking lookups to prevent brute-force order enumeration.
 * Uses hashed request fingerprints and WordPress transients without storing raw IP addresses.
 */
class RateLimiter {
	/**
	 * Max allowed attempts before lockout.
	 */
	public const MAX_ATTEMPTS = 5;

	/**
	 * Lockout window in seconds (15 minutes).
	 */
	public const LOCKOUT_SECONDS = 900;

	/**
	 * Check if the current client is rate-limited.
	 *
	 * @param string $order_ref Order reference submitted.
	 * @param string $billing_email Email submitted.
	 * @return bool True if rate limit is exceeded, false otherwise.
	 */
	public function is_rate_limited(string $order_ref, string $billing_email): bool {
		$hash = $this->generate_hash($order_ref, $billing_email);
		$transient_key = 'wpc_st_rl_' . $hash;

		$attempts = (int) get_transient($transient_key);
		$is_limited = $attempts >= self::MAX_ATTEMPTS;

		/**
		 * Filter rate limit check result.
		 *
		 * @param bool $is_limited Is client blocked.
		 * @param string $hash Anonymized request hash.
		 */
		return (bool) apply_filters('wpcalibrate_shipment_tracker_guest_rate_limit_exceeded', $is_limited, $hash);
	}

	/**
	 * Record a failed lookup attempt.
	 *
	 * @param string $order_ref Order reference.
	 * @param string $billing_email Email.
	 * @return int Updated attempt count.
	 */
	public function record_failure(string $order_ref, string $billing_email): int {
		$hash = $this->generate_hash($order_ref, $billing_email);
		$transient_key = 'wpc_st_rl_' . $hash;

		$attempts = (int) get_transient($transient_key);
		$attempts++;

		set_transient($transient_key, $attempts, self::LOCKOUT_SECONDS);

		return $attempts;
	}

	/**
	 * Reset rate limiter on successful authentication.
	 *
	 * @param string $order_ref Order reference.
	 * @param string $billing_email Email.
	 * @return void
	 */
	public function reset(string $order_ref, string $billing_email): void {
		$hash = $this->generate_hash($order_ref, $billing_email);
		delete_transient('wpc_st_rl_' . $hash);
	}

	/**
	 * Generate an anonymized SHA-256 HMAC hash from client context without storing raw IP.
	 *
	 * @param string $order_ref Order reference.
	 * @param string $billing_email Email.
	 * @return string Hashed string token.
	 */
	private function generate_hash(string $order_ref, string $billing_email): string {
		$client_ip = '';
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$client_ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
		}

		$user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
		$secret_salt = wp_salt('auth');

		$raw_data = $client_ip . '|' . strtolower(trim($billing_email)) . '|' . $user_agent;

		return hash_hmac('sha256', $raw_data, $secret_salt);
	}
}
