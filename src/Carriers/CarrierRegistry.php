<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Carriers;

use WPCalibrate\ShipmentTracker\Settings\SettingsManager;

defined('ABSPATH') || exit;

/**
 * Registry for built-in and custom shipping carriers.
 */
class CarrierRegistry {
	/**
	 * Option name storing carrier overrides and custom carriers.
	 */
	public const OPTION_NAME = 'wpcalibrate_shipment_tracker_carriers';

	/**
	 * Settings manager reference.
	 *
	 * @var SettingsManager|null
	 */
	private ?SettingsManager $settings_manager = null;

	/**
	 * Cached carriers list.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private ?array $carriers = null;

	/**
	 * Constructor.
	 *
	 * @param SettingsManager|null $settings_manager Settings manager.
	 */
	public function __construct(?SettingsManager $settings_manager = null) {
		$this->settings_manager = $settings_manager;
	}

	/**
	 * Initialize registry hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Hook for clearing cache when option is updated.
		add_action('update_option_' . self::OPTION_NAME, [$this, 'clear_cache']);
	}

	/**
	 * Clear memory cache.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$this->carriers = null;
	}

	/**
	 * Return the default predefined carriers.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_default_carriers(): array {
		return [
			'ups' => [
				'key'          => 'ups',
				'name'         => 'UPS',
				'url_template' => 'https://www.ups.com/track?tracknum={tracking_number}',
				'enabled'      => true,
				'is_custom'    => false,
			],
			'fedex' => [
				'key'          => 'fedex',
				'name'         => 'FedEx',
				'url_template' => 'https://www.fedex.com/fedextrack/?trknbr={tracking_number}',
				'enabled'      => true,
				'is_custom'    => false,
			],
			'usps' => [
				'key'          => 'usps',
				'name'         => 'USPS',
				'url_template' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking_number}',
				'enabled'      => true,
				'is_custom'    => false,
			],
			'dhl_express' => [
				'key'          => 'dhl_express',
				'name'         => 'DHL Express',
				'url_template' => 'https://www.dhl.com/en/express/tracking.html?AWB={tracking_number}',
				'enabled'      => true,
				'is_custom'    => false,
			],
			'royal_mail' => [
				'key'          => 'royal_mail',
				'name'         => 'Royal Mail',
				'url_template' => 'https://www.royalmail.com/track-your-item#/tracking-details/{tracking_number}',
				'enabled'      => true,
				'is_custom'    => false,
			],
			'canada_post' => [
				'key'          => 'canada_post',
				'name'         => 'Canada Post',
				'url_template' => 'https://www.canadapost-postescanada.ca/track-reperage/en#/resultList?searchFor={tracking_number}',
				'enabled'      => true,
				'is_custom'    => false,
			],
			'australia_post' => [
				'key'          => 'australia_post',
				'name'         => 'Australia Post',
				'url_template' => 'https://auspost.com.au/mypost/track/#/details/{tracking_number}',
				'enabled'      => true,
				'is_custom'    => false,
			],
			'custom' => [
				'key'          => 'custom',
				'name'         => __('Custom Carrier', 'wpcalibrate-shipment-tracker'),
				'url_template' => '',
				'enabled'      => true,
				'is_custom'    => false,
			],
		];
	}

	/**
	 * Get all carriers merged with stored overrides and custom carriers.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_all(): array {
		if ($this->carriers !== null) {
			return $this->carriers;
		}

		$defaults = self::get_default_carriers();
		$stored = get_option(self::OPTION_NAME, []);

		if (!is_array($stored)) {
			$stored = [];
		}

		$merged = $defaults;

		// Merge stored changes and custom carriers.
		foreach ($stored as $key => $carrier_data) {
			$clean_key = sanitize_key((string)$key);
			if (isset($merged[$clean_key])) {
				// Built-in carrier override (e.g. enabled flag or custom template).
				if (isset($carrier_data['enabled'])) {
					$merged[$clean_key]['enabled'] = (bool) $carrier_data['enabled'];
				}
				if (!empty($carrier_data['url_template']) && TrackingUrlGenerator::is_valid_template($carrier_data['url_template'])) {
					$merged[$clean_key]['url_template'] = esc_url_raw($carrier_data['url_template']);
				}
			} elseif (!empty($carrier_data['is_custom'])) {
				// Admin defined custom carrier.
				$merged[$clean_key] = [
					'key'          => $clean_key,
					'name'         => sanitize_text_field($carrier_data['name'] ?? $clean_key),
					'url_template' => esc_url_raw($carrier_data['url_template'] ?? ''),
					'enabled'      => !empty($carrier_data['enabled']),
					'is_custom'    => true,
				];
			}
		}

		/**
		 * Filter the full carrier registry.
		 *
		 * @param array<string, array<string, mixed>> $merged List of carriers.
		 */
		$filtered = (array) apply_filters('wpcalibrate_shipment_tracker_carrier_registry', $merged);
		$this->carriers = $filtered;

		return $this->carriers;
	}

	/**
	 * Get enabled carriers only.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_enabled(): array {
		return array_filter($this->get_all(), static fn(array $c) => !empty($c['enabled']));
	}

	/**
	 * Get enabled carriers only (alias for get_enabled).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_enabled_carriers(): array {
		return $this->get_enabled();
	}

	/**
	 * Get a specific carrier by key.
	 *
	 * @param string $key Carrier key.
	 * @return array<string, mixed>|null
	 */
	public function get_carrier(string $key): ?array {
		$all = $this->get_all();
		$key = sanitize_key($key);

		return $all[$key] ?? null;
	}

	/**
	 * Add or update a custom carrier.
	 *
	 * @param string $name Carrier display name.
	 * @param string $url_template Tracking URL template.
	 * @param bool $enabled Status.
	 * @param string|null $existing_key If editing existing.
	 * @return string|false Carrier key on success, false on validation failure.
	 */
	public function save_custom_carrier(string $name, string $url_template, bool $enabled = true, ?string $existing_key = null): string|false {
		$name = sanitize_text_field($name);
		$url_template = trim($url_template);

		if (empty($name)) {
			return false;
		}

		if (!empty($url_template) && !TrackingUrlGenerator::is_valid_template($url_template)) {
			return false;
		}

		$key = !empty($existing_key) ? sanitize_key($existing_key) : sanitize_key('custom_' . sanitize_title($name));
		if (empty($key)) {
			$key = 'custom_' . wp_generate_password(6, false, false);
		}

		$stored = get_option(self::OPTION_NAME, []);
		if (!is_array($stored)) {
			$stored = [];
		}

		$stored[$key] = [
			'key'          => $key,
			'name'         => $name,
			'url_template' => !empty($url_template) ? esc_url_raw($url_template) : '',
			'enabled'      => $enabled,
			'is_custom'    => true,
		];

		update_option(self::OPTION_NAME, $stored);
		$this->clear_cache();

		return $key;
	}

	/**
	 * Delete a custom carrier.
	 *
	 * @param string $key Carrier key.
	 * @return bool
	 */
	public function delete_custom_carrier(string $key): bool {
		$key = sanitize_key($key);
		$stored = get_option(self::OPTION_NAME, []);

		if (!is_array($stored) || !isset($stored[$key])) {
			return false;
		}

		unset($stored[$key]);
		update_option(self::OPTION_NAME, $stored);
		$this->clear_cache();

		return true;
	}

	/**
	 * Toggle or update the enabled status of a carrier.
	 *
	 * @param string $key Carrier key.
	 * @param bool $enabled Status.
	 * @return bool
	 */
	public function set_carrier_enabled(string $key, bool $enabled): bool {
		$key = sanitize_key($key);
		$stored = get_option(self::OPTION_NAME, []);
		if (!is_array($stored)) {
			$stored = [];
		}

		if (!isset($stored[$key])) {
			$stored[$key] = [];
		}
		$stored[$key]['enabled'] = $enabled;

		update_option(self::OPTION_NAME, $stored);
		$this->clear_cache();

		return true;
	}
}
