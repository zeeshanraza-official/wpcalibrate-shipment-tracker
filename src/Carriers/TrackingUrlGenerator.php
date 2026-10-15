<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Carriers;

defined('ABSPATH') || exit;

/**
 * Service for safely generating external carrier tracking URLs.
 */
class TrackingUrlGenerator {
	/**
	 * Tracking number placeholder token.
	 */
	public const PLACEHOLDER = '{tracking_number}';

	/**
	 * Generate a safe tracking URL from a template and tracking number.
	 *
	 * @param string $template URL template containing {tracking_number}.
	 * @param string $tracking_number Raw tracking number.
	 * @param string $carrier_key Associated carrier key.
	 * @return string Safe tracking URL or empty string if invalid.
	 */
	public static function generate(string $template, string $tracking_number, string $carrier_key = ''): string {
		$tracking_number = trim($tracking_number);
		$template = trim($template);

		if (empty($template) || empty($tracking_number)) {
			return '';
		}

		// Validate URL template scheme. Must be http or https only.
		$scheme = wp_parse_url($template, PHP_URL_SCHEME);
		if (!in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
			return '';
		}

		// Reject dangerous schemes or control characters.
		if (preg_match('/^(javascript|data|vbscript|file):/i', $template)) {
			return '';
		}

		// Encode tracking number safely.
		$encoded_number = rawurlencode($tracking_number);

		// Replace placeholder or append if placeholder not present.
		if (str_contains($template, self::PLACEHOLDER)) {
			$url = str_replace(self::PLACEHOLDER, $encoded_number, $template);
		} else {
			$url = $template . $encoded_number;
		}

		// Ensure final URL is sanitized and valid.
		$clean_url = esc_url_raw($url, ['http', 'https']);

		/**
		 * Filter the generated tracking URL.
		 *
		 * @param string $clean_url Generated and sanitized URL.
		 * @param string $tracking_number Raw tracking number.
		 * @param string $carrier_key Carrier key identifier.
		 * @param string $template Original URL template.
		 */
		return (string) apply_filters(
			'wpcalibrate_shipment_tracker_generated_url',
			$clean_url,
			$tracking_number,
			$carrier_key,
			$template
		);
	}

	/**
	 * Validate a URL template string for security and correctness.
	 *
	 * @param string $template URL template string.
	 * @return bool True if valid, false if invalid or dangerous.
	 */
	public static function is_valid_template(string $template): bool {
		$template = trim($template);
		if (empty($template)) {
			return false;
		}

		$scheme = wp_parse_url($template, PHP_URL_SCHEME);
		if (!in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
			return false;
		}

		if (preg_match('/^(javascript|data|vbscript|file):/i', $template)) {
			return false;
		}

		return true;
	}
}
