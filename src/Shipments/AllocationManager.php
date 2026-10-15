<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Shipments;

use WC_Order;
use WC_Order_Item_Product;
use WP_Error;
use WPCalibrate\ShipmentTracker\Database\ShipmentItemRepository;

defined('ABSPATH') || exit;

/**
 * Calculates and validates shipment item allocations and quantities.
 * Prevents over-allocation and accounts for refunds and multi-shipment distribution.
 */
class AllocationManager {
	/**
	 * Shipment item repository.
	 *
	 * @var ShipmentItemRepository
	 */
	private ShipmentItemRepository $item_repo;

	/**
	 * Constructor.
	 *
	 * @param ShipmentItemRepository|null $item_repo Repository instance.
	 */
	public function __construct(?ShipmentItemRepository $item_repo = null) {
		$this->item_repo = $item_repo ?? new ShipmentItemRepository();
	}

	/**
	 * Get detailed allocation status for all line items in an order.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @param int|null $exclude_shipment_id Optional shipment ID to exclude from allocated sum.
	 * @return array<int, array<string, mixed>> List of items with detailed quantity allocation states.
	 */
	public function get_order_items_allocation_status(WC_Order $order, ?int $exclude_shipment_id = null): array {
		$items = $order->get_items('line_item');
		if (empty($items)) {
			return [];
		}

		$item_ids = array_map(static fn($item) => (int) $item->get_id(), array_values($items));
		$allocated_map = $this->item_repo->get_allocated_quantities_by_order_items($item_ids, $exclude_shipment_id);

		$result = [];

		foreach ($items as $item) {
			/** @var WC_Order_Item_Product $item */
			$item_id = (int) $item->get_id();
			$ordered_qty = (float) $item->get_quantity();

			// Account for valid WooCommerce refunds.
			$refunded_qty = abs((float) $order->get_qty_refunded_for_item($item_id));
			$net_shippable = max(0.0, $ordered_qty - $refunded_qty);

			$allocated_qty = $allocated_map[$item_id] ?? 0.0;
			$available_qty = max(0.0, $net_shippable - $allocated_qty);

			// Safely retrieve product details, handling deleted products gracefully.
			$product = $item->get_product();
			$product_name = $item->get_name();
			$sku = $product ? $product->get_sku() : '';
			$image_url = '';

			if ($product) {
				$image_id = $product->get_image_id();
				if ($image_id) {
					$image_src = wp_get_attachment_image_src($image_id, 'thumbnail');
					$image_url = $image_src ? $image_src[0] : '';
				}
			}

			$result[$item_id] = [
				'item_id'           => $item_id,
				'name'              => $product_name,
				'sku'               => $sku,
				'image_url'         => $image_url,
				'ordered_qty'       => $ordered_qty,
				'refunded_qty'      => $refunded_qty,
				'net_shippable_qty' => $net_shippable,
				'allocated_qty'     => $allocated_qty,
				'available_qty'     => $available_qty,
				'is_fully_shipped'  => $available_qty <= 0.0001,
			];
		}

		return $result;
	}

	/**
	 * Validate that requested item quantities do not exceed available shippable quantities.
	 *
	 * @param WC_Order $order WooCommerce order instance.
	 * @param array<int, float|int|string> $requested_items Map of order_item_id => quantity.
	 * @param int|null $exclude_shipment_id Optional shipment ID to exclude when updating.
	 * @return true|WP_Error Returns true if valid, WP_Error with specific item message on violation.
	 */
	public function validate_allocation(WC_Order $order, array $requested_items, ?int $exclude_shipment_id = null): true|WP_Error {
		$statuses = $this->get_order_items_allocation_status($order, $exclude_shipment_id);

		// Must assign at least one item.
		$has_positive_qty = false;

		foreach ($requested_items as $raw_item_id => $raw_qty) {
			$item_id = absint($raw_item_id);
			$qty = (float) $raw_qty;

			// Skip zero or empty quantities.
			if ($qty <= 0) {
				if ($qty < 0) {
					return new WP_Error(
						'invalid_quantity',
						__('Item quantity cannot be negative.', 'wpcalibrate-shipment-tracker')
					);
				}
				continue;
			}

			$has_positive_qty = true;

			// Validate item belongs to this order.
			if (!isset($statuses[$item_id])) {
				return new WP_Error(
					'item_not_found',
					sprintf(
						/* translators: %d: order item ID */
						__('Order item #%d does not belong to this order.', 'wpcalibrate-shipment-tracker'),
						$item_id
					)
				);
			}

			$available = $statuses[$item_id]['available_qty'];
			$item_name = $statuses[$item_id]['name'];

			// Check over-allocation with small float epsilon tolerance.
			if (($qty - $available) > 0.0001) {
				return new WP_Error(
					'over_allocation',
					sprintf(
						/* translators: 1: item name, 2: requested quantity, 3: available quantity */
						__('Cannot allocate %2$s of "%1$s". Only %3$s available for shipment.', 'wpcalibrate-shipment-tracker'),
						esc_html($item_name),
						$qty,
						$available
					)
				);
			}
		}

		if (!$has_positive_qty) {
			return new WP_Error(
				'empty_shipment',
				__('A shipment must contain at least one item with a quantity greater than zero.', 'wpcalibrate-shipment-tracker')
			);
		}

		return true;
	}
}
