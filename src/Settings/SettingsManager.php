<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Settings;

use WPCalibrate\ShipmentTracker\Carriers\CarrierRegistry;
use WPCalibrate\ShipmentTracker\Carriers\TrackingUrlGenerator;
use WPCalibrate\ShipmentTracker\Licensing\LicenseManager;

defined('ABSPATH') || exit;

/**
 * Manages plugin configuration, options persistence, tabs, and administrative settings views.
 */
class SettingsManager {
	public const OPTION_KEY = 'wpcalibrate_shipment_tracker_settings';

	/**
	 * Cached settings array.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $settings = null;

	/**
	 * Initialize settings hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action('admin_post_wpc_st_save_settings', [$this, 'handle_save_settings']);
		add_action('admin_post_wpc_st_carrier_action', [$this, 'handle_carrier_action']);
		add_action('admin_post_wpc_st_seed_demo_products', [$this, 'handle_seed_demo_products']);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		if ($this->settings !== null) {
			return $this->settings;
		}

		$defaults = [
			'tracking_page_id'            => 0,
			'auto_complete_order'         => 'no',
			'enable_guest_tracking'       => 'yes',
			'show_carrier_link'           => 'yes',
			'show_items_in_tracking'      => 'yes',
			'show_timeline'               => 'yes',
			'customer_email_notification' => 'yes',
			'delete_data_on_uninstall'    => 'no',
			'enable_logging'              => 'no',
		];

		$stored = get_option(self::OPTION_KEY, []);
		$this->settings = wp_parse_args(is_array($stored) ? $stored : [], $defaults);

		return $this->settings;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed $default Fallback value.
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed {
		$all = $this->get_all();
		return $all[$key] ?? $default;
	}

	/**
	 * Handle settings POST save.
	 *
	 * @return void
	 */
	public function handle_save_settings(): void {
		if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('Unauthorized access.', 'wpcalibrate-shipment-tracker'));
		}

		check_admin_referer('wpc_st_save_settings', 'wpc_st_nonce');

		$tab = sanitize_key($_POST['current_tab'] ?? 'general');
		$current_settings = $this->get_all();

		if ($tab === 'general') {
			$current_settings['tracking_page_id']    = isset($_POST['tracking_page_id']) ? absint($_POST['tracking_page_id']) : 0;
			$current_settings['auto_complete_order'] = !empty($_POST['auto_complete_order']) && $_POST['auto_complete_order'] === 'yes' ? 'yes' : 'no';
		} elseif ($tab === 'tracking') {
			$current_settings['enable_guest_tracking']  = !empty($_POST['enable_guest_tracking']) && $_POST['enable_guest_tracking'] === 'yes' ? 'yes' : 'no';
			$current_settings['show_carrier_link']      = !empty($_POST['show_carrier_link']) && $_POST['show_carrier_link'] === 'yes' ? 'yes' : 'no';
			$current_settings['show_items_in_tracking'] = !empty($_POST['show_items_in_tracking']) && $_POST['show_items_in_tracking'] === 'yes' ? 'yes' : 'no';
			$current_settings['show_timeline']          = !empty($_POST['show_timeline']) && $_POST['show_timeline'] === 'yes' ? 'yes' : 'no';
		} elseif ($tab === 'emails') {
			$current_settings['customer_email_notification'] = !empty($_POST['customer_email_notification']) && $_POST['customer_email_notification'] === 'yes' ? 'yes' : 'no';
		} elseif ($tab === 'advanced') {
			$current_settings['delete_data_on_uninstall'] = !empty($_POST['delete_data_on_uninstall']) && $_POST['delete_data_on_uninstall'] === 'yes' ? 'yes' : 'no';
			$current_settings['enable_logging']           = !empty($_POST['enable_logging']) && $_POST['enable_logging'] === 'yes' ? 'yes' : 'no';
		}

		update_option(self::OPTION_KEY, $current_settings);
		$this->settings = null;

		add_settings_error('wpc_st_settings', 'settings_updated', __('Settings saved successfully.', 'wpcalibrate-shipment-tracker'), 'success');
		set_transient('settings_errors', get_settings_errors(), 30);

		wp_safe_redirect(admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=' . $tab));
		exit;
	}

	/**
	 * Handle Carrier actions (toggle status, add custom carrier, delete custom carrier).
	 *
	 * @return void
	 */
	public function handle_carrier_action(): void {
		if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('Unauthorized access.', 'wpcalibrate-shipment-tracker'));
		}

		check_admin_referer('wpc_st_carrier_nonce', 'wpc_st_carrier_security');

		$registry = new CarrierRegistry();
		$action   = sanitize_key($_POST['carrier_action'] ?? '');
		$tab_url  = admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=carriers');

		if ($action === 'add_custom') {
			$name     = sanitize_text_field(wp_unslash($_POST['custom_carrier_name'] ?? ''));
			$template = sanitize_text_field(wp_unslash($_POST['custom_url_template'] ?? ''));
			$enabled  = !empty($_POST['custom_carrier_enabled']);

			if (empty($name)) {
				add_settings_error('wpc_st_carriers', 'missing_name', __('Carrier name is required.', 'wpcalibrate-shipment-tracker'), 'error');
			} elseif (!empty($template) && !TrackingUrlGenerator::is_valid_template($template)) {
				add_settings_error('wpc_st_carriers', 'invalid_url', __('Invalid tracking URL template. Scheme must be http or https.', 'wpcalibrate-shipment-tracker'), 'error');
			} else {
				$saved = $registry->save_custom_carrier($name, $template, $enabled);
				if ($saved) {
					add_settings_error('wpc_st_carriers', 'carrier_added', __('Custom carrier added successfully.', 'wpcalibrate-shipment-tracker'), 'success');
				} else {
					add_settings_error('wpc_st_carriers', 'add_failed', __('Failed to add custom carrier.', 'wpcalibrate-shipment-tracker'), 'error');
				}
			}
		} elseif ($action === 'delete_custom') {
			$key = sanitize_key($_POST['carrier_key'] ?? '');
			if ($registry->delete_custom_carrier($key)) {
				add_settings_error('wpc_st_carriers', 'carrier_deleted', __('Custom carrier removed.', 'wpcalibrate-shipment-tracker'), 'success');
			}
		} elseif ($action === 'toggle_status') {
			$key = sanitize_key($_POST['carrier_key'] ?? '');
			$enabled = !empty($_POST['carrier_enabled']) && $_POST['carrier_enabled'] === '1';
			$registry->set_carrier_enabled($key, $enabled);
			add_settings_error('wpc_st_carriers', 'status_updated', __('Carrier status updated.', 'wpcalibrate-shipment-tracker'), 'success');
		}

		set_transient('settings_errors', get_settings_errors(), 30);
		wp_safe_redirect($tab_url);
		exit;
	}

	/**
	 * Handle one-click seeding of 5 demo products.
	 *
	 * @return void
	 */
	public function handle_seed_demo_products(): void {
		if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('Unauthorized access.', 'wpcalibrate-shipment-tracker'));
		}

		check_admin_referer('wpc_st_seed_demo_nonce', 'wpc_st_seed_security');

		$result = \WPCalibrate\ShipmentTracker\Database\DemoDataSeeder::seed();
		$tab_url = admin_url('admin.php?page=wpcalibrate-shipment-tracker&tab=advanced');

		if ($result['created'] > 0) {
			/* translators: %d: number of created products */
			add_settings_error('wpc_st_demo_seeder', 'demo_seeded', sprintf(__('Successfully created %d demo products in WooCommerce.', 'wpcalibrate-shipment-tracker'), $result['created']), 'success');
		} elseif ($result['skipped'] > 0) {
			add_settings_error('wpc_st_demo_seeder', 'demo_skipped', __('Demo products already exist in your WooCommerce catalog.', 'wpcalibrate-shipment-tracker'), 'info');
		}

		if (!empty($result['errors'])) {
			foreach ($result['errors'] as $error) {
				add_settings_error('wpc_st_demo_seeder', 'demo_error', esc_html($error), 'error');
			}
		}

		set_transient('settings_errors', get_settings_errors(), 30);
		wp_safe_redirect($tab_url);
		exit;
	}

	/**
	 * Render settings page template.
	 *
	 * @return void
	 */
	public function render(): void {
		$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
		$settings = $this->get_all();

		$carrier_registry = new CarrierRegistry($this);
		$carriers = $carrier_registry->get_all();

		$license_manager = new LicenseManager();
		$license_provider = $license_manager->get_provider();

		$template_path = WPC_SHIPMENT_TRACKER_PATH . 'templates/admin/settings-page.php';
		if (file_exists($template_path)) {
			include $template_path;
		}
	}
}
