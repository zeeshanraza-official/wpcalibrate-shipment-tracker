<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Shipments;

defined('ABSPATH') || exit;

/**
 * Manages shipment status codes, labels, and status validation.
 */
class ShipmentStatus {
	public const STATUS_PREPARING          = 'preparing';
	public const STATUS_SHIPPED            = 'shipped';
	public const STATUS_IN_TRANSIT         = 'in_transit';
	public const STATUS_OUT_FOR_DELIVERY   = 'out_for_delivery';
	public const STATUS_DELIVERED          = 'delivered';
	public const STATUS_DELIVERY_EXCEPTION = 'delivery_exception';
	public const STATUS_RETURNED           = 'returned';

	/**
	 * Get all allowed shipment status keys and translated labels.
	 *
	 * @return array<string, string> Map of status_key => translated label.
	 */
	public static function get_all(): array {
		$statuses = [
			self::STATUS_PREPARING          => __('Preparing', 'wpcalibrate-shipment-tracker'),
			self::STATUS_SHIPPED            => __('Shipped', 'wpcalibrate-shipment-tracker'),
			self::STATUS_IN_TRANSIT         => __('In Transit', 'wpcalibrate-shipment-tracker'),
			self::STATUS_OUT_FOR_DELIVERY   => __('Out for Delivery', 'wpcalibrate-shipment-tracker'),
			self::STATUS_DELIVERED          => __('Delivered', 'wpcalibrate-shipment-tracker'),
			self::STATUS_DELIVERY_EXCEPTION => __('Delivery Exception', 'wpcalibrate-shipment-tracker'),
			self::STATUS_RETURNED           => __('Returned', 'wpcalibrate-shipment-tracker'),
		];

		/**
		 * Filter allowed shipment statuses.
		 *
		 * @param array<string, string> $statuses Status key to label mapping.
		 */
		return (array) apply_filters('wpcalibrate_shipment_tracker_statuses', $statuses);
	}

	/**
	 * Verify if a status key is valid.
	 *
	 * @param string $status Status key.
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid(string $status): bool {
		$allowed = self::get_all();
		return array_key_exists(sanitize_key($status), $allowed);
	}

	/**
	 * Get the human-readable label for a status key.
	 *
	 * @param string $status Status key.
	 * @return string Translated label or fallback.
	 */
	public static function get_label(string $status): string {
		$statuses = self::get_all();
		$key = sanitize_key($status);

		return $statuses[$key] ?? ucfirst(str_replace('_', ' ', $key));
	}

	/**
	 * Get CSS badge color classes for visual status rendering.
	 *
	 * @param string $status Status key.
	 * @return string CSS class name suffix.
	 */
	public static function get_badge_class(string $status): string {
		return match (sanitize_key($status)) {
			self::STATUS_DELIVERED => 'wpc-status-delivered',
			self::STATUS_SHIPPED, self::STATUS_IN_TRANSIT, self::STATUS_OUT_FOR_DELIVERY => 'wpc-status-shipping',
			self::STATUS_DELIVERY_EXCEPTION => 'wpc-status-exception',
			self::STATUS_RETURNED => 'wpc-status-returned',
			default => 'wpc-status-preparing',
		};
	}
}
