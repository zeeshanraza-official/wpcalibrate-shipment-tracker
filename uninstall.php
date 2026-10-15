<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Respects administrator data-retention settings:
 * Only purges tables and plugin options if 'delete_data_on_uninstall' is explicitly enabled ('yes').
 */

declare(strict_types=1);

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

global $wpdb;

// Retrieve plugin settings to check data retention preference.
$settings = get_option('wpcalibrate_shipment_tracker_settings', []);
$should_delete = is_array($settings) && isset($settings['delete_data_on_uninstall']) && $settings['delete_data_on_uninstall'] === 'yes';

if ($should_delete) {
	// Drop plugin-owned custom database tables.
	// We drop child/dependent tables first.
	$events_table = $wpdb->prefix . 'wpcalibrate_shipment_events';
	$items_table  = $wpdb->prefix . 'wpcalibrate_shipment_items';
	$shipments_table = $wpdb->prefix . 'wpcalibrate_shipments';

	$wpdb->query("DROP TABLE IF EXISTS {$events_table}");
	$wpdb->query("DROP TABLE IF EXISTS {$items_table}");
	$wpdb->query("DROP TABLE IF EXISTS {$shipments_table}");

	// Delete plugin-specific options.
	delete_option('wpcalibrate_shipment_tracker_settings');
	delete_option('wpcalibrate_shipment_tracker_schema_version');
	delete_option('wpcalibrate_shipment_tracker_carriers');
	delete_option('wpcalibrate_shipment_tracker_license');

	// Clean up transients created by the plugin.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_wpc_st_%',
			'_transient_timeout_wpc_st_%'
		)
	);
}
