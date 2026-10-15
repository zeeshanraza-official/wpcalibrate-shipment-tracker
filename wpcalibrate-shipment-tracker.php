<?php
/**
 * Plugin Name: WPCalibrate Shipment Tracker
 * Plugin URI: https://marketplace.wpcalibrate.com/
 * Description: Production-quality WooCommerce shipment and order tracking plugin supporting multiple shipments, partial fulfillment, carrier URL templates, and secure customer tracking.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * WC requires at least: 8.5
 * WC tested up to: 11.2.1
 * Author: WPCalibrate
 * Author URI: https://wpcalibrate.com
 * Text Domain: wpcalibrate-shipment-tracker
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/zeeshanraza-official/wpcalibrate-shipment-tracker
 * Primary Branch: main
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// Plugin constants
define('WPC_SHIPMENT_TRACKER_VERSION', '1.0.0');
define('WPC_SHIPMENT_TRACKER_SCHEMA_VERSION', '1.0.0');
define('WPC_SHIPMENT_TRACKER_FILE', __FILE__);
define('WPC_SHIPMENT_TRACKER_PATH', plugin_dir_path(__FILE__));
define('WPC_SHIPMENT_TRACKER_URL', plugin_dir_url(__FILE__));
define('WPC_SHIPMENT_TRACKER_BASENAME', plugin_basename(__FILE__));

// Require and register PSR-4 autoloader
require_once WPC_SHIPMENT_TRACKER_PATH . 'src/Autoloader.php';
\WPCalibrate\ShipmentTracker\Autoloader::register(WPC_SHIPMENT_TRACKER_PATH . 'src');

/**
 * Declare High-Performance Order Storage (HPOS) and Blocks compatibility with WooCommerce.
 */
add_action('before_woocommerce_init', static function (): void {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
	}
});

/**
 * Check dependencies and bootstrap the plugin on plugins_loaded.
 */
add_action('plugins_loaded', static function (): void {
	// Verify WooCommerce is active.
	if (!function_exists('WC') && !class_exists('WooCommerce')) {
		add_action('admin_notices', static function (): void {
			if (!current_user_can('activate_plugins')) {
				return;
			}
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php esc_html_e('WPCalibrate Shipment Tracker', 'wpcalibrate-shipment-tracker'); ?></strong>:
					<?php esc_html_e('This plugin requires WooCommerce to be installed and activated. Please install and activate WooCommerce to enable shipment tracking.', 'wpcalibrate-shipment-tracker'); ?>
				</p>
			</div>
			<?php
		});
		return;
	}

	// Bootstrap the plugin lifecycle.
	\WPCalibrate\ShipmentTracker\Plugin::instance()->init();
});

/**
 * Activation hook: Trigger database migration and default settings initialization.
 */
register_activation_hook(__FILE__, static function (): void {
	if (class_exists(\WPCalibrate\ShipmentTracker\Database\MigrationManager::class)) {
		\WPCalibrate\ShipmentTracker\Database\MigrationManager::activate();
	}
});

/**
 * Deactivation hook: Safe deactivation preserving all user data and settings.
 */
register_deactivation_hook(__FILE__, static function (): void {
	if (class_exists(\WPCalibrate\ShipmentTracker\Database\MigrationManager::class)) {
		\WPCalibrate\ShipmentTracker\Database\MigrationManager::deactivate();
	}
});
