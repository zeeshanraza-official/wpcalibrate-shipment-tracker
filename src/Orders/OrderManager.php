<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Orders;

use WPCalibrate\ShipmentTracker\Carriers\CarrierRegistry;
use WPCalibrate\ShipmentTracker\Database\ShipmentRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentItemRepository;
use WPCalibrate\ShipmentTracker\Database\ShipmentEventRepository;
use WPCalibrate\ShipmentTracker\Shipments\ShipmentService;
use WPCalibrate\ShipmentTracker\Shipments\AllocationManager;
use WPCalibrate\ShipmentTracker\Settings\SettingsManager;

defined('ABSPATH') || exit;

/**
 * Coordinates all WooCommerce Order screen integrations (HPOS and classic).
 */
class OrderManager {
	private CarrierRegistry $carrier_registry;
	private ?SettingsManager $settings_manager;
	private ShipmentRepository $shipment_repo;
	private ShipmentItemRepository $item_repo;
	private ShipmentEventRepository $event_repo;
	private AllocationManager $allocation_manager;
	private ShipmentService $shipment_service;

	private OrderMetaBox $meta_box;
	private OrderAdminAjaxHandler $ajax_handler;
	private OrderListColumn $list_column;

	/**
	 * Constructor.
	 */
	public function __construct(
		CarrierRegistry $carrier_registry,
		?SettingsManager $settings_manager = null
	) {
		$this->carrier_registry = $carrier_registry;
		$this->settings_manager = $settings_manager;

		$this->shipment_repo = new ShipmentRepository();
		$this->item_repo = new ShipmentItemRepository();
		$this->event_repo = new ShipmentEventRepository();
		$this->allocation_manager = new AllocationManager($this->item_repo);

		$this->shipment_service = new ShipmentService(
			$this->shipment_repo,
			$this->item_repo,
			$this->event_repo,
			$this->carrier_registry,
			$this->allocation_manager,
			$this->settings_manager
		);

		$this->meta_box = new OrderMetaBox(
			$this->shipment_repo,
			$this->item_repo,
			$this->event_repo,
			$this->carrier_registry,
			$this->allocation_manager,
			$this->settings_manager
		);

		$this->ajax_handler = new OrderAdminAjaxHandler(
			$this->shipment_service,
			$this->shipment_repo,
			$this->item_repo,
			$this->allocation_manager,
			$this->carrier_registry
		);

		$this->list_column = new OrderListColumn($this->shipment_repo);
	}

	/**
	 * Initialize order integration components.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->meta_box->init();
		$this->ajax_handler->init();
		$this->list_column->init();
	}

	/**
	 * Getter for ShipmentService.
	 *
	 * @return ShipmentService
	 */
	public function get_shipment_service(): ShipmentService {
		return $this->shipment_service;
	}

	/**
	 * Getter for ShipmentRepository.
	 *
	 * @return ShipmentRepository
	 */
	public function get_shipment_repository(): ShipmentRepository {
		return $this->shipment_repo;
	}
}
