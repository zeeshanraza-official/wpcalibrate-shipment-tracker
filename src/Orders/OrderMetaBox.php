<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Orders;

use WC_Order;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WPCalibrate\ShipmentTracker\Carriers\CarrierRegistry;
use WPCalibrate\ShipmentTracker\Database\ShipmentRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentItemRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentEventRepository;
use WPCalibrate\ShipmentTracker\Shipments\AllocationManager;
use WPCalibrate\ShipmentTracker\Settings\SettingsManager;

defined('ABSPATH') || exit;

/**
 * Renders and manages the Shipment Tracking meta box on HPOS and classic order screens.
 */
class OrderMetaBox {
	/**
	 * Meta box ID.
	 */
	public const META_BOX_ID = 'wpcalibrate-shipment-tracker-panel';

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
	 * Register meta box hooks for HPOS and legacy order edit screens.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action('add_meta_boxes', [$this, 'register_meta_box']);
	}

	/**
	 * Register the meta box on WooCommerce order screens.
	 *
	 * @return void
	 */
	public function register_meta_box(): void {
		$screens = ['shop_order'];

		// HPOS screen support.
		if (class_exists(OrderUtil::class) && OrderUtil::custom_orders_table_usage_is_enabled()) {
			$screens[] = wc_get_page_screen_id('shop-order');
		}

		foreach (array_unique($screens) as $screen) {
			add_meta_box(
				self::META_BOX_ID,
				__('Shipment Tracking', 'wpcalibrate-shipment-tracker'),
				[$this, 'render_meta_box'],
				$screen,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the meta box content.
	 *
	 * @param \WP_Post|WC_Order $post_or_order Post or Order object provided by WordPress/WooCommerce.
	 * @return void
	 */
	public function render_meta_box($post_or_order): void {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order($post_or_order);
		if (!$order instanceof WC_Order) {
			echo '<p>' . esc_html__('Unable to load WooCommerce order data.', 'wpcalibrate-shipment-tracker') . '</p>';
			return;
		}

		$order_id = $order->get_id();
		$shipments = $this->shipment_repo->get_by_order_id($order_id);

		// Batch load items for all shipments on this order.
		$shipment_ids = array_map(static fn($s) => (int) $s['id'], $shipments);
		$shipment_items_map = $this->item_repo->get_by_shipment_ids($shipment_ids);
		$shipment_events_map = $this->event_repo->get_by_shipment_ids($shipment_ids);

		// Load allocation states for order items.
		$allocation_status = $this->allocation_manager->get_order_items_allocation_status($order);
		$carriers = $this->carrier_registry->get_all();

		// Pass variables to template.
		$template_path = WPC_SHIPMENT_TRACKER_PATH . 'templates/admin/order-meta-box.php';
		if (file_exists($template_path)) {
			include $template_path;
		}
	}
}
