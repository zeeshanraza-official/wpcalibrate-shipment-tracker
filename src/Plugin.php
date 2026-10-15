<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker;

use WPCalibrate\ShipmentTracker\Admin\MenuManager;
use WPCalibrate\ShipmentTracker\Database\MigrationManager;
use WPCalibrate\ShipmentTracker\Carriers\CarrierRegistry;
use WPCalibrate\ShipmentTracker\Settings\SettingsManager;
use WPCalibrate\ShipmentTracker\Licensing\LicenseManager;

defined('ABSPATH') || exit;

/**
 * Main Plugin Orchestrator for WPCalibrate Shipment Tracker.
 */
final class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Menu Manager.
	 *
	 * @var MenuManager|null
	 */
	private ?MenuManager $menu_manager = null;

	/**
	 * Settings Manager.
	 *
	 * @var SettingsManager|null
	 */
	private ?SettingsManager $settings_manager = null;

	/**
	 * Carrier Registry.
	 *
	 * @var CarrierRegistry|null
	 */
	private ?CarrierRegistry $carrier_registry = null;

	/**
	 * License Manager.
	 *
	 * @var LicenseManager|null
	 */
	private ?LicenseManager $license_manager = null;

	/**
	 * Migration Manager.
	 *
	 * @var MigrationManager|null
	 */
	private ?MigrationManager $migration_manager = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		// Enforce singleton pattern.
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup() {
		throw new \Exception('Cannot unserialize singleton');
	}

	/**
	 * Initialize plugin components and hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Load text domain.
		add_action('init', [$this, 'load_textdomain']);

		// Add settings link to the plugin row in plugins.php.
		add_filter('plugin_action_links_' . plugin_basename(WPC_SHIPMENT_TRACKER_FILE), [$this, 'add_plugin_action_links']);

		// Initialize Database & Migrations.
		$this->migration_manager = new MigrationManager();
		$this->migration_manager->init();

		// Initialize Settings.
		$this->settings_manager = new SettingsManager();
		$this->settings_manager->init();

		// Initialize Carrier Registry.
		$this->carrier_registry = new CarrierRegistry($this->settings_manager);
		$this->carrier_registry->init();

		// Initialize Licensing.
		$this->license_manager = new LicenseManager();
		$this->license_manager->init();

		// Initialize Admin Menu.
		$this->menu_manager = new MenuManager([$this->settings_manager, 'render']);
		$this->menu_manager->init();

		// Initialize Subsystems (Admin, Orders, Tracking, Customer, Emails, Assets).
		$this->init_subsystems();
	}

	/**
	 * Initialize domain subsystems.
	 *
	 * @return void
	 */
	private function init_subsystems(): void {
		// Admin / Orders / HPOS Integration
		if (class_exists('WPCalibrate\\ShipmentTracker\\Orders\\OrderManager')) {
			$order_manager = new \WPCalibrate\ShipmentTracker\Orders\OrderManager(
				$this->carrier_registry,
				$this->settings_manager
			);
			$order_manager->init();
		}

		// Customer My Account & Tracking Display
		if (class_exists('WPCalibrate\\ShipmentTracker\\Customer\\CustomerManager')) {
			$customer_manager = new \WPCalibrate\ShipmentTracker\Customer\CustomerManager();
			$customer_manager->init();
		}

		// Public Tracking Page & Shortcode Handler
		if (class_exists('WPCalibrate\\ShipmentTracker\\Tracking\\TrackingPageHandler')) {
			$tracking_page_handler = new \WPCalibrate\ShipmentTracker\Tracking\TrackingPageHandler($this->settings_manager);
			$tracking_page_handler->init();
		}

		// WooCommerce Email Notifications
		if (class_exists('WPCalibrate\\ShipmentTracker\\Emails\\EmailManager')) {
			$email_manager = new \WPCalibrate\ShipmentTracker\Emails\EmailManager($this->settings_manager);
			$email_manager->init();
		}

		// Asset Manager
		if (class_exists('WPCalibrate\\ShipmentTracker\\Admin\\AssetManager')) {
			$asset_manager = new \WPCalibrate\ShipmentTracker\Admin\AssetManager();
			$asset_manager->init();
		}
	}

	/**
	 * Load translation files.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wpcalibrate-shipment-tracker',
			false,
			dirname(plugin_basename(WPC_SHIPMENT_TRACKER_FILE)) . '/languages'
		);
	}

	/**
	 * Add custom links to the plugins page.
	 *
	 * @param array<string, string> $links Existing action links.
	 * @return array<string, string>
	 */
	public function add_plugin_action_links(array $links): array {
		$settings_url = admin_url('admin.php?page=' . MenuManager::SUBMENU_SLUG);
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url($settings_url),
			esc_html__('Settings', 'wpcalibrate-shipment-tracker')
		);

		// Place Settings at the beginning of the action links.
		array_unshift($links, $settings_link);

		return $links;
	}

	/**
	 * Getter for Carrier Registry.
	 *
	 * @return CarrierRegistry|null
	 */
	public function get_carrier_registry(): ?CarrierRegistry {
		return $this->carrier_registry;
	}

	/**
	 * Getter for Settings Manager.
	 *
	 * @return SettingsManager|null
	 */
	public function get_settings_manager(): ?SettingsManager {
		return $this->settings_manager;
	}

	/**
	 * Getter for License Manager.
	 *
	 * @return LicenseManager|null
	 */
	public function get_license_manager(): ?LicenseManager {
		return $this->license_manager;
	}
}
