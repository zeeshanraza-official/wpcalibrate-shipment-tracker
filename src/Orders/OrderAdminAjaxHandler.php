<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Orders;

use WC_Order;
use WPCalibrate\ShipmentTracker\Shipments\ShipmentService;
use WPCalibrate\ShipmentTracker\Shipments\AllocationManager;
use WPCalibrate\ShipmentTracker\Database\ShipmentRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentItemRepository;
use WPCalibrate\ShipmentTracker\Carriers\CarrierRegistry;
use WPCalibrate\ShipmentTracker\Emails\EmailManager;

defined('ABSPATH') || exit;

/**
 * Handles AJAX requests from the order administration screen for shipment management.
 */
class OrderAdminAjaxHandler {
	public const NONCE_ACTION = 'wpcalibrate_shipment_tracker_admin';

	private ShipmentService $shipment_service;
	private ShipmentRepository $shipment_repo;
	private ShipmentItemRepository $item_repo;
	private AllocationManager $allocation_manager;
	private CarrierRegistry $carrier_registry;

	/**
	 * Constructor.
	 */
	public function __construct(
		?ShipmentService $shipment_service = null,
		?ShipmentRepository $shipment_repo = null,
		?ShipmentItemRepository $item_repo = null,
		?AllocationManager $allocation_manager = null,
		?CarrierRegistry $carrier_registry = null
	) {
		$this->shipment_service = $shipment_service ?? new ShipmentService();
		$this->shipment_repo = $shipment_repo ?? new ShipmentRepository();
		$this->item_repo = $item_repo ?? new ShipmentItemRepository();
		$this->allocation_manager = $allocation_manager ?? new AllocationManager($this->item_repo);
		$this->carrier_registry = $carrier_registry ?? new CarrierRegistry();
	}

	/**
	 * Register AJAX endpoints.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action('wp_ajax_wpc_st_save_shipment', [$this, 'handle_save_shipment']);
		add_action('wp_ajax_wpc_st_delete_shipment', [$this, 'handle_delete_shipment']);
		add_action('wp_ajax_wpc_st_get_shipment', [$this, 'handle_get_shipment']);
		add_action('wp_ajax_wpc_st_get_allocations', [$this, 'handle_get_allocations']);
	}

	/**
	 * Check authorization and CSRF nonce.
	 *
	 * @return void
	 */
	private function verify_permissions(): void {
		check_ajax_referer(self::NONCE_ACTION, 'security');

		if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
			wp_send_json_error([
				'message' => __('You do not have permission to manage shipments for this order.', 'wpcalibrate-shipment-tracker'),
			], 403);
		}
	}

	/**
	 * AJAX handler to create or update a shipment.
	 *
	 * @return void
	 */
	public function handle_save_shipment(): void {
		$this->verify_permissions();

		$shipment_id = isset($_POST['shipment_id']) ? absint($_POST['shipment_id']) : 0;
		$order_id    = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

		$order = wc_get_order($order_id);
		if (!$order instanceof WC_Order) {
			wp_send_json_error(['message' => __('Invalid order ID.', 'wpcalibrate-shipment-tracker')], 400);
		}

		$carrier_key     = isset($_POST['carrier_key']) ? sanitize_key($_POST['carrier_key']) : 'custom';
		$carrier_name    = isset($_POST['carrier_name']) ? sanitize_text_field(wp_unslash($_POST['carrier_name'])) : '';
		$tracking_number = isset($_POST['tracking_number']) ? sanitize_text_field(wp_unslash($_POST['tracking_number'])) : '';
		$tracking_url    = isset($_POST['tracking_url']) ? esc_url_raw(wp_unslash($_POST['tracking_url'])) : '';
		$status          = isset($_POST['status']) ? sanitize_key($_POST['status']) : 'preparing';
		$shipped_at      = !empty($_POST['shipped_at']) ? sanitize_text_field(wp_unslash($_POST['shipped_at'])) : null;
		$estimated_del   = !empty($_POST['estimated_delivery_at']) ? sanitize_text_field(wp_unslash($_POST['estimated_delivery_at'])) : null;
		$delivered_at    = !empty($_POST['delivered_at']) ? sanitize_text_field(wp_unslash($_POST['delivered_at'])) : null;
		$internal_note   = !empty($_POST['internal_note']) ? sanitize_textarea_field(wp_unslash($_POST['internal_note'])) : null;
		$customer_note   = !empty($_POST['customer_note']) ? sanitize_textarea_field(wp_unslash($_POST['customer_note'])) : null;
		$notify_customer = !empty($_POST['notify_customer']) && $_POST['notify_customer'] === 'yes';

		// Parse item allocations.
		$raw_items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];
		$items = [];
		foreach ($raw_items as $item_id => $qty) {
			$clean_id = absint($item_id);
			$clean_qty = (float) $qty;
			if ($clean_id > 0 && $clean_qty > 0) {
				$items[$clean_id] = $clean_qty;
			}
		}

		$shipment_data = [
			'carrier_key'           => $carrier_key,
			'carrier_name'          => $carrier_name,
			'tracking_number'       => $tracking_number,
			'tracking_url'          => $tracking_url,
			'status'                => $status,
			'shipped_at'            => $shipped_at,
			'estimated_delivery_at' => $estimated_del,
			'delivered_at'          => $delivered_at,
			'internal_note'         => $internal_note,
			'customer_note'         => $customer_note,
		];

		if ($shipment_id > 0) {
			// Update existing shipment.
			$result = $this->shipment_service->update_shipment($shipment_id, $shipment_data, $items);
			if (is_wp_error($result)) {
				wp_send_json_error(['message' => $result->get_error_message()], 400);
			}
			$final_id = $shipment_id;
			$message = __('Shipment updated successfully.', 'wpcalibrate-shipment-tracker');
		} else {
			// Create new shipment.
			$result = $this->shipment_service->create_shipment($order_id, $shipment_data, $items);
			if (is_wp_error($result)) {
				wp_send_json_error(['message' => $result->get_error_message()], 400);
			}
			$final_id = $result;
			$message = __('Shipment created successfully.', 'wpcalibrate-shipment-tracker');
		}

		// Dispatch customer notification email if requested.
		if ($notify_customer && class_exists(EmailManager::class)) {
			EmailManager::send_customer_notification($final_id);
		}

		wp_send_json_success([
			'message'     => $message,
			'shipment_id' => $final_id,
		]);
	}

	/**
	 * AJAX handler to delete a shipment.
	 *
	 * @return void
	 */
	public function handle_delete_shipment(): void {
		$this->verify_permissions();

		$shipment_id = isset($_POST['shipment_id']) ? absint($_POST['shipment_id']) : 0;
		if ($shipment_id <= 0) {
			wp_send_json_error(['message' => __('Invalid shipment ID.', 'wpcalibrate-shipment-tracker')], 400);
		}

		$result = $this->shipment_service->delete_shipment($shipment_id);
		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()], 400);
		}

		wp_send_json_success([
			'message' => __('Shipment deleted successfully and allocated quantities released.', 'wpcalibrate-shipment-tracker'),
		]);
	}

	/**
	 * AJAX handler to retrieve single shipment details for editing.
	 *
	 * @return void
	 */
	public function handle_get_shipment(): void {
		$this->verify_permissions();

		$shipment_id = isset($_POST['shipment_id']) ? absint($_POST['shipment_id']) : 0;
		$shipment = $this->shipment_repo->get_by_id($shipment_id);

		if (!$shipment) {
			wp_send_json_error(['message' => __('Shipment not found.', 'wpcalibrate-shipment-tracker')], 404);
		}

		$items = $this->item_repo->get_by_shipment_id($shipment_id);
		$shipment['items'] = $items;

		wp_send_json_success(['shipment' => $shipment]);
	}

	/**
	 * AJAX handler to retrieve allocation status for an order.
	 *
	 * @return void
	 */
	public function handle_get_allocations(): void {
		$this->verify_permissions();

		$order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
		$exclude_id = isset($_POST['exclude_shipment_id']) ? absint($_POST['exclude_shipment_id']) : null;

		$order = wc_get_order($order_id);
		if (!$order instanceof WC_Order) {
			wp_send_json_error(['message' => __('Invalid order ID.', 'wpcalibrate-shipment-tracker')], 400);
		}

		$allocations = $this->allocation_manager->get_order_items_allocation_status($order, $exclude_id);
		wp_send_json_success(['allocations' => $allocations]);
	}
}
