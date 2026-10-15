<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Licensing;

defined('ABSPATH') || exit;

/**
 * Manages plugin licensing state and delegates to the configured LicenseProviderInterface.
 */
class LicenseManager {
	private LicenseProviderInterface $provider;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Provide extension point for attaching a real marketplace licensing provider.
		$provider = apply_filters('wpcalibrate_shipment_tracker_license_provider', null);

		if ($provider instanceof LicenseProviderInterface) {
			$this->provider = $provider;
		} else {
			$this->provider = new NullLicenseProvider();
		}
	}

	/**
	 * Initialize licensing hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action('admin_post_wpc_st_license_action', [$this, 'handle_form_action']);
	}

	/**
	 * Handle admin license submission.
	 *
	 * @return void
	 */
	public function handle_form_action(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Unauthorized access.', 'wpcalibrate-shipment-tracker'));
		}

		check_admin_referer('wpc_st_license_nonce', 'wpc_st_license_security');

		$license_action = sanitize_key($_POST['license_action'] ?? '');
		$redirect_url   = admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=license');

		if ($license_action === 'activate') {
			$raw_key = sanitize_text_field(wp_unslash($_POST['license_key'] ?? ''));
			$result  = $this->provider->activate($raw_key);

			if ($result->is_success()) {
				add_settings_error('wpc_st_license', 'activated', $result->get_message(), 'success');
			} else {
				add_settings_error('wpc_st_license', 'failed', $result->get_message(), 'error');
			}
		} elseif ($license_action === 'deactivate') {
			$result = $this->provider->deactivate();
			add_settings_error('wpc_st_license', 'deactivated', $result->get_message(), 'info');
		}

		set_transient('settings_errors', get_settings_errors(), 30);
		wp_safe_redirect($redirect_url);
		exit;
	}

	/**
	 * Get active provider.
	 *
	 * @return LicenseProviderInterface
	 */
	public function get_provider(): LicenseProviderInterface {
		return $this->provider;
	}
}
