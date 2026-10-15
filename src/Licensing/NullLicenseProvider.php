<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Licensing;

defined('ABSPATH') || exit;

/**
 * Default unconfigured licensing provider.
 * Strictly avoids fake activation and clearly reports an unconfigured state.
 */
class NullLicenseProvider implements LicenseProviderInterface {
	public function activate(string $license_key): LicenseResult {
		return new LicenseResult(
			false,
			'not_configured',
			__('A remote licensing service is not configured in this release. Core shipment tracking remains fully operational.', 'wpcalibrate-shipment-tracker')
		);
	}

	public function deactivate(): LicenseResult {
		return new LicenseResult(
			false,
			'not_configured',
			__('A remote licensing service is not configured.', 'wpcalibrate-shipment-tracker')
		);
	}

	public function get_status(): string {
		return 'not_configured';
	}

	public function get_masked_key(): string {
		return '';
	}

	public function get_expiry(): ?string {
		return null;
	}

	public function get_renewal_url(): ?string {
		return null;
	}
}
