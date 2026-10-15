<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Database;

defined('ABSPATH') || exit;

/**
 * Manages database schema installations, upgrades, and table definitions.
 */
class MigrationManager {
	/**
	 * Option key storing installed schema version.
	 */
	public const SCHEMA_VERSION_OPTION = 'wpcalibrate_shipment_tracker_schema_version';

	/**
	 * Table name constants (unprefixed logical names).
	 */
	public const TABLE_SHIPMENTS = 'wpcalibrate_shipments';
	public const TABLE_SHIPMENT_ITEMS = 'wpcalibrate_shipment_items';
	public const TABLE_SHIPMENT_EVENTS = 'wpcalibrate_shipment_events';

	/**
	 * Initialize runtime migration checks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Run upgrade check on admin_init if needed.
		add_action('admin_init', [$this, 'check_schema_version']);
	}

	/**
	 * Get the full prefixed table name.
	 *
	 * @param string $table Logical table name.
	 * @return string Prefixed table name.
	 */
	public static function get_table_name(string $table): string {
		global $wpdb;
		return $wpdb->prefix . $table;
	}

	/**
	 * Check schema version and run migrations if an update is detected.
	 *
	 * @return void
	 */
	public function check_schema_version(): void {
		$installed_version = get_option(self::SCHEMA_VERSION_OPTION, '0.0.0');
		if (version_compare((string) $installed_version, WPC_SHIPMENT_TRACKER_SCHEMA_VERSION, '<')) {
			self::migrate();
		}
	}

	/**
	 * Run migrations to create or update custom database tables.
	 *
	 * @return void
	 */
	public static function migrate(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$shipments_table = self::get_table_name(self::TABLE_SHIPMENTS);
		$shipment_items_table = self::get_table_name(self::TABLE_SHIPMENT_ITEMS);
		$shipment_events_table = self::get_table_name(self::TABLE_SHIPMENT_EVENTS);

		// Schema definitions: two spaces before primary keys, exactly one space after key definitions as required by dbDelta.
		$sql_shipments = "CREATE TABLE {$shipments_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			carrier_key varchar(64) NOT NULL,
			carrier_name varchar(128) NOT NULL,
			tracking_number varchar(128) NOT NULL,
			tracking_url varchar(1024) NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'preparing',
			shipped_at datetime DEFAULT NULL,
			estimated_delivery_at datetime DEFAULT NULL,
			delivered_at datetime DEFAULT NULL,
			internal_note text DEFAULT NULL,
			customer_note text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY carrier_key (carrier_key),
			KEY status (status)
		) {$charset_collate};";

		$sql_shipment_items = "CREATE TABLE {$shipment_items_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			shipment_id bigint(20) unsigned NOT NULL,
			order_item_id bigint(20) unsigned NOT NULL,
			quantity decimal(10,4) NOT NULL DEFAULT '1.0000',
			created_at datetime NOT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY shipment_id (shipment_id),
			KEY order_item_id (order_item_id),
			UNIQUE KEY shipment_order_item (shipment_id, order_item_id)
		) {$charset_collate};";

		$sql_shipment_events = "CREATE TABLE {$shipment_events_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			shipment_id bigint(20) unsigned NOT NULL,
			event_type varchar(64) NOT NULL,
			previous_status varchar(32) DEFAULT NULL,
			new_status varchar(32) DEFAULT NULL,
			event_at datetime NOT NULL,
			actor_type varchar(32) DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY shipment_id (shipment_id)
		) {$charset_collate};";

		dbDelta($sql_shipments);
		dbDelta($sql_shipment_items);
		dbDelta($sql_shipment_events);

		update_option(self::SCHEMA_VERSION_OPTION, WPC_SHIPMENT_TRACKER_SCHEMA_VERSION);
	}

	/**
	 * Activation callback: Run migrations and set default options.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::migrate();

		// Set default settings if not already present.
		if (false === get_option('wpcalibrate_shipment_tracker_settings')) {
			$default_settings = [
				'tracking_page_id' => 0,
				'auto_complete_order' => 'no',
				'enable_guest_tracking' => 'yes',
				'show_carrier_link' => 'yes',
				'show_items_in_tracking' => 'yes',
				'show_timeline' => 'yes',
				'customer_email_notification' => 'yes',
				'delete_data_on_uninstall' => 'no',
				'enable_logging' => 'no',
			];
			update_option('wpcalibrate_shipment_tracker_settings', $default_settings);
		}
	}

	/**
	 * Deactivation callback: Safely preserve data.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// No data is removed on deactivation to preserve user records.
	}
}
