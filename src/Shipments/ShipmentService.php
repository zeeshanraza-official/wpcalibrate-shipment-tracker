<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Shipments;

use WC_Order;
use WP_Error;
use WPCalibrate\ShipmentTracker\Carriers\CarrierRegistry;
use WPCalibrate\ShipmentTracker\Carriers\TrackingUrlGenerator;
use WPCalibrate\ShipmentTracker\Database\ShipmentRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentItemRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentEventRepository;
use WPCalibrate\ShipmentTracker\Settings\SettingsManager;

defined('ABSPATH') || exit;

/**
 * Core domain service for managing shipment lifecycles, allocations, and events.
 */
class ShipmentService {
	/**
	 * Repositories and collaborators.
	 */
	private ShipmentRepository $shipment_repo;
	private ShipmentItemRepository $item_repo;
	private ShipmentEventRepository $event_repo;
	private CarrierRegistry $carrier_registry;
	private AllocationManager $allocation_manager;
	private ?SettingsManager $settings_manager;

	/**
	 * Constructor.
	 */
	public function __construct(
		?ShipmentRepository $shipment_repo = null,
		?ShipmentItemRepository $item_repo = null,
		?ShipmentEventRepository $event_repo = null,
		?CarrierRegistry $carrier_registry = null,
		?AllocationManager $allocation_manager = null,
		?SettingsManager $settings_manager = null
	) {
		$this->shipment_repo = $shipment_repo ?? new ShipmentRepository();
		$this->item_repo = $item_repo ?? new ShipmentItemRepository();
		$this->event_repo = $event_repo ?? new ShipmentEventRepository();
		$this->carrier_registry = $carrier_registry ?? new CarrierRegistry();
		$this->allocation_manager = $allocation_manager ?? new AllocationManager($this->item_repo);
		$this->settings_manager = $settings_manager;
	}

	/**
	 * Create a new shipment for an order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @param array<string, mixed> $data Shipment fields.
	 * @param array<int, float|int|string> $items Item allocations (order_item_id => qty).
	 * @param string $actor Actor performing creation ('admin', 'system', 'api').
	 * @return int|WP_Error Shipment ID on success or WP_Error on failure.
	 */
	public function create_shipment(int $order_id, array $data, array $items, string $actor = 'admin'): int|WP_Error {
		$order = wc_get_order($order_id);
		if (!$order instanceof WC_Order) {
			return new WP_Error('invalid_order', __('Invalid WooCommerce order.', 'wpcalibrate-shipment-tracker'));
		}

		// Validate status.
		$status = sanitize_key($data['status'] ?? ShipmentStatus::STATUS_PREPARING);
		if (!ShipmentStatus::is_valid($status)) {
			return new WP_Error('invalid_status', __('Invalid shipment status supplied.', 'wpcalibrate-shipment-tracker'));
		}

		// Validate item allocation quantities.
		$allocation_check = $this->allocation_manager->validate_allocation($order, $items);
		if (is_wp_error($allocation_check)) {
			return $allocation_check;
		}

		// Resolve carrier details and tracking URL.
		$carrier_key = sanitize_key($data['carrier_key'] ?? 'custom');
		$carrier = $this->carrier_registry->get_carrier($carrier_key);
		$carrier_name = sanitize_text_field($data['carrier_name'] ?? ($carrier['name'] ?? ucfirst($carrier_key)));
		$tracking_number = sanitize_text_field($data['tracking_number'] ?? '');

		// Compute tracking URL if not explicitly supplied or if carrier has a template.
		$tracking_url = '';
		if (!empty($carrier['url_template']) && !empty($tracking_number)) {
			$tracking_url = TrackingUrlGenerator::generate($carrier['url_template'], $tracking_number, $carrier_key);
		} elseif (!empty($data['tracking_url']) && TrackingUrlGenerator::is_valid_template((string) $data['tracking_url'])) {
			$tracking_url = esc_url_raw((string) $data['tracking_url']);
		}

		// Handle delivered_at automatically.
		$now = current_time('mysql', true);
		$delivered_at = null;
		if ($status === ShipmentStatus::STATUS_DELIVERED) {
			$delivered_at = !empty($data['delivered_at']) ? sanitize_text_field((string)$data['delivered_at']) : $now;
		}

		$shipped_at = null;
		if (in_array($status, [ShipmentStatus::STATUS_SHIPPED, ShipmentStatus::STATUS_IN_TRANSIT, ShipmentStatus::STATUS_OUT_FOR_DELIVERY, ShipmentStatus::STATUS_DELIVERED], true)) {
			$shipped_at = !empty($data['shipped_at']) ? sanitize_text_field((string)$data['shipped_at']) : $now;
		}

		$shipment_record = [
			'order_id'              => $order_id,
			'carrier_key'           => $carrier_key,
			'carrier_name'          => $carrier_name,
			'tracking_number'       => $tracking_number,
			'tracking_url'          => $tracking_url,
			'status'                => $status,
			'shipped_at'            => $shipped_at,
			'estimated_delivery_at' => !empty($data['estimated_delivery_at']) ? sanitize_text_field((string)$data['estimated_delivery_at']) : null,
			'delivered_at'          => $delivered_at,
			'internal_note'         => !empty($data['internal_note']) ? sanitize_textarea_field((string)$data['internal_note']) : null,
			'customer_note'         => !empty($data['customer_note']) ? sanitize_textarea_field((string)$data['customer_note']) : null,
		];

		$shipment_id = $this->shipment_repo->create($shipment_record);
		if ($shipment_id <= 0) {
			return new WP_Error('db_insert_failed', __('Failed to create shipment record in database.', 'wpcalibrate-shipment-tracker'));
		}

		// Persist item allocations.
		$this->item_repo->set_items_for_shipment($shipment_id, $items);

		// Record creation and initial status event.
		$this->event_repo->log_event($shipment_id, 'created', null, $status, $actor);

		/**
		 * Action triggered when a shipment is created.
		 *
		 * @param int $shipment_id Created shipment ID.
		 * @param array<string, mixed> $shipment_record Shipment data.
		 */
		do_action('wpcalibrate_shipment_tracker_shipment_created', $shipment_id, $shipment_record);

		// Check if order should be automatically marked as Completed.
		$this->maybe_auto_complete_order($order);

		return $shipment_id;
	}

	/**
	 * Update an existing shipment.
	 *
	 * @param int $shipment_id Shipment ID.
	 * @param array<string, mixed> $data Updated fields.
	 * @param array<int, float|int|string>|null $items Optional updated items map.
	 * @param string $actor Actor performing update.
	 * @return true|WP_Error
	 */
	public function update_shipment(int $shipment_id, array $data, ?array $items = null, string $actor = 'admin'): true|WP_Error {
		$existing = $this->shipment_repo->get_by_id($shipment_id);
		if (!$existing) {
			return new WP_Error('not_found', __('Shipment not found.', 'wpcalibrate-shipment-tracker'));
		}

		$order_id = (int) $existing['order_id'];
		$order = wc_get_order($order_id);
		if (!$order instanceof WC_Order) {
			return new WP_Error('invalid_order', __('Invalid WooCommerce order.', 'wpcalibrate-shipment-tracker'));
		}

		// If items are being updated, validate allocations.
		if ($items !== null) {
			$allocation_check = $this->allocation_manager->validate_allocation($order, $items, $shipment_id);
			if (is_wp_error($allocation_check)) {
				return $allocation_check;
			}
		}

		$prev_status = $existing['status'];
		$new_status = isset($data['status']) ? sanitize_key($data['status']) : $prev_status;

		if (!ShipmentStatus::is_valid($new_status)) {
			return new WP_Error('invalid_status', __('Invalid shipment status supplied.', 'wpcalibrate-shipment-tracker'));
		}

		// Handle delivered_at logic.
		$now = current_time('mysql', true);
		if ($new_status === ShipmentStatus::STATUS_DELIVERED && $prev_status !== ShipmentStatus::STATUS_DELIVERED) {
			if (empty($data['delivered_at']) && empty($existing['delivered_at'])) {
				$data['delivered_at'] = $now;
			}
		}

		// Recompute tracking URL if carrier or tracking number changed.
		$carrier_key = sanitize_key($data['carrier_key'] ?? $existing['carrier_key']);
		$tracking_number = sanitize_text_field($data['tracking_number'] ?? $existing['tracking_number']);
		$carrier = $this->carrier_registry->get_carrier($carrier_key);

		if (!empty($carrier['url_template']) && !empty($tracking_number)) {
			$data['tracking_url'] = TrackingUrlGenerator::generate($carrier['url_template'], $tracking_number, $carrier_key);
		}

		$updated = $this->shipment_repo->update($shipment_id, $data);
		if (!$updated) {
			return new WP_Error('update_failed', __('Failed to update shipment.', 'wpcalibrate-shipment-tracker'));
		}

		if ($items !== null) {
			$this->item_repo->set_items_for_shipment($shipment_id, $items);
		}

		// Log status change event if status changed.
		if ($prev_status !== $new_status) {
			$this->event_repo->log_event($shipment_id, 'status_change', $prev_status, $new_status, $actor);

			/**
			 * Action triggered when a shipment status changes.
			 *
			 * @param int $shipment_id Shipment ID.
			 * @param string $prev_status Previous status.
			 * @param string $new_status New status.
			 * @param int $order_id Order ID.
			 */
			do_action('wpcalibrate_shipment_tracker_status_changed', $shipment_id, $prev_status, $new_status, $order_id);
		}

		/**
		 * Action triggered when a shipment is updated.
		 *
		 * @param int $shipment_id Shipment ID.
		 * @param array<string, mixed> $data Updated data.
		 */
		do_action('wpcalibrate_shipment_tracker_shipment_updated', $shipment_id, $data);

		// Check if order should be automatically marked as Completed.
		$this->maybe_auto_complete_order($order);

		return true;
	}

	/**
	 * Delete a shipment and release allocated items.
	 *
	 * @param int $shipment_id Shipment ID.
	 * @param string $actor Actor deleting the shipment.
	 * @return bool|WP_Error True on success or WP_Error.
	 */
	public function delete_shipment(int $shipment_id, string $actor = 'admin'): bool|WP_Error {
		$existing = $this->shipment_repo->get_by_id($shipment_id);
		if (!$existing) {
			return new WP_Error('not_found', __('Shipment not found.', 'wpcalibrate-shipment-tracker'));
		}

		$order_id = (int) $existing['order_id'];

		// Remove assigned items first to maintain referential integrity.
		$this->item_repo->delete_by_shipment_id($shipment_id);

		// Remove associated events.
		$this->event_repo->delete_by_shipment_id($shipment_id);

		// Delete the shipment row.
		$deleted = $this->shipment_repo->delete($shipment_id);

		if ($deleted) {
			/**
			 * Action triggered when a shipment is deleted.
			 *
			 * @param int $shipment_id Deleted shipment ID.
			 * @param int $order_id Associated order ID.
			 */
			do_action('wpcalibrate_shipment_tracker_shipment_deleted', $shipment_id, $order_id);
		}

		return $deleted;
	}

	/**
	 * Check and automatically transition order to Completed if configured.
	 *
	 * @param WC_Order $order Order instance.
	 * @return void
	 */
	public function maybe_auto_complete_order(WC_Order $order): void {
		$settings = get_option('wpcalibrate_shipment_tracker_settings', []);
		$enabled = is_array($settings) && isset($settings['auto_complete_order']) && $settings['auto_complete_order'] === 'yes';

		if (!$enabled) {
			return;
		}

		// Only transition orders that are currently processing.
		if ($order->get_status() !== 'processing') {
			return;
		}

		// Verify every line item has 0 remaining available quantity.
		$allocation_status = $this->allocation_manager->get_order_items_allocation_status($order);
		foreach ($allocation_status as $item) {
			if ($item['available_qty'] > 0.0001) {
				return; // Not all shippable items have been allocated to shipments.
			}
		}

		// Verify all shipments for this order are Delivered.
		$shipments = $this->shipment_repo->get_by_order_id($order->get_id());
		if (empty($shipments)) {
			return;
		}

		foreach ($shipments as $shipment) {
			if ($shipment['status'] !== ShipmentStatus::STATUS_DELIVERED) {
				return; // Found a shipment that is not delivered yet.
			}
		}

		// All items are allocated and all shipments are Delivered: mark Completed!
		$order->update_status(
			'completed',
			__('Order automatically marked Completed by WPCalibrate Shipment Tracker because all shipments are delivered.', 'wpcalibrate-shipment-tracker')
		);
	}
}
