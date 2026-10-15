<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Customer;

use WC_Order;
use WPCalibrate\ShipmentTracker\Database\ShipmentRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentItemRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentEventRepository;
use WPCalibrate\ShipmentTracker\Shipments\AllocationManager;
use WPCalibrate\ShipmentTracker\Settings\SettingsManager;

defined('ABSPATH') || exit;

/**
 * Manages customer-facing tracking views in WooCommerce My Account and Order Details.
 */
class CustomerManager {
	private ShipmentRepository $shipment_repo;
	private ShipmentItemRepository $item_repo;
	private ShipmentEventRepository $event_repo;
	private AllocationManager $allocation_manager;
	private ?SettingsManager $settings_manager;

	/**
	 * Constructor.
	 */
	public function __construct(
		?ShipmentRepository $shipment_repo = null,
		?ShipmentItemRepository $item_repo = null,
		?ShipmentEventRepository $event_repo = null,
		?AllocationManager $allocation_manager = null,
		?SettingsManager $settings_manager = null
	) {
		$this->shipment_repo = $shipment_repo ?? new ShipmentRepository();
		$this->item_repo = $item_repo ?? new ShipmentItemRepository();
		$this->event_repo = $event_repo ?? new ShipmentEventRepository();
		$this->allocation_manager = $allocation_manager ?? new AllocationManager($this->item_repo);
		$this->settings_manager = $settings_manager;
	}

	/**
	 * Register customer integration hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Add "Track" action button to My Account > Orders table.
		add_filter('woocommerce_my_account_my_orders_actions', [$this, 'add_order_track_action'], 10, 2);

		// Render shipment details on My Account > View Order page.
		add_action('woocommerce_order_details_after_order_table', [$this, 'render_order_shipments'], 15, 1);
	}

	/**
	 * Add "Track" button in My Account Orders list if tracking is available.
	 *
	 * @param array<string, array<string, string>> $actions Order action buttons.
	 * @param WC_Order $order Current order.
	 * @return array<string, array<string, string>>
	 */
	public function add_order_track_action(array $actions, WC_Order $order): array {
		$order_id = $order->get_id();
		$shipments = $this->shipment_repo->get_by_order_id($order_id);

		if (!empty($shipments)) {
			$track_url = $order->get_view_order_url() . '#wpc-st-customer-tracking';

			$actions['track_shipment'] = [
				'url'  => $track_url,
				'name' => __('Track', 'wpcalibrate-shipment-tracker'),
			];
		}

		return $actions;
	}

	/**
	 * Render shipment information on the View Order page.
	 *
	 * @param WC_Order $order WooCommerce Order object.
	 * @return void
	 */
	public function render_order_shipments(WC_Order $order): void {
		// Strict ownership verification:
		// Ensure current user is the owner of the order, or an authorized store manager.
		$current_user_id = get_current_user_id();
		$order_customer_id = (int) $order->get_customer_id();

		$can_view = false;
		if ($current_user_id > 0 && $current_user_id === $order_customer_id) {
			$can_view = true;
		} elseif (current_user_can('manage_woocommerce') || current_user_can('edit_shop_orders')) {
			$can_view = true;
		}

		if (!$can_view) {
			return;
		}

		$order_id = $order->get_id();
		$shipments = $this->shipment_repo->get_by_order_id($order_id);

		if (empty($shipments)) {
			return;
		}

		$shipment_ids = array_map(static fn($s) => (int) $s['id'], $shipments);
		$shipment_items_map = $this->item_repo->get_by_shipment_ids($shipment_ids);
		$shipment_events_map = $this->event_repo->get_by_shipment_ids($shipment_ids);
		$allocation_status = $this->allocation_manager->get_order_items_allocation_status($order);

		// Render customer template.
		$template_path = WPC_SHIPMENT_TRACKER_PATH . 'templates/customer/my-account-shipments.php';
		if (file_exists($template_path)) {
			include $template_path;
		}
	}
}
