<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Licensing;

defined('ABSPATH') || exit;

/**
 * Interface for WPCalibrate licensing providers.
 * Decouples core plugin logic from any specific marketplace or licensing backend.
 */
interface LicenseProviderInterface {
	/**
	 * Attempt license activation.
	 *
	 * @param string $license_key Raw license key.
	 * @return LicenseResult
	 */
	public function activate(string $license_key): LicenseResult;

	/**
	 * Attempt license deactivation.
	 *
	 * @return LicenseResult
	 */
	public function deactivate(): LicenseResult;

	/**
	 * Check and return the current license status.
	 *
	 * @return string ('not_configured', 'active', 'inactive', 'expired', 'invalid').
	 */
	public function get_status(): string;

	/**
	 * Return a masked representation of the stored license key (e.g. '••••••••-1234').
	 *
	 * @return string
	 */
	public function get_masked_key(): string;

	/**
	 * Return the license expiry date if available.
	 *
	 * @return string|null
	 */
	public function get_expiry(): ?string;

	/**
	 * Return the renewal URL if provided by the licensing backend.
	 *
	 * @return string|null
	 */
	public function get_renewal_url(): ?string;
}
