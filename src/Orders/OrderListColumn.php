<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Orders;

use WC_Order;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WPCalibrate\ShipmentTracker\Database\ShipmentRepository;
use WPCalibrate\ShipmentTracker\Shipments\ShipmentStatus;

defined('ABSPATH') || exit;

/**
 * Adds a concise, high-performance Shipment column to the WooCommerce Orders list table.
 * Implements batch-loading to eliminate N+1 queries across both HPOS and legacy tables.
 */
class OrderListColumn {
	public const COLUMN_KEY = 'wpc_st_shipment';

	private ShipmentRepository $shipment_repo;

	/**
	 * In-memory pre-fetched shipments cache for the current page of orders.
	 *
	 * @var array<int, array<int, array<string, mixed>>>|null
	 */
	private ?array $batched_shipments = null;

	/**
	 * Constructor.
	 *
	 * @param ShipmentRepository $shipment_repo Repository instance.
	 */
	public function __construct(ShipmentRepository $shipment_repo) {
		$this->shipment_repo = $shipment_repo;
	}

	/**
	 * Register hooks for HPOS and legacy order list tables.
	 *
	 * @return void
	 */
	public function init(): void {
		// HPOS columns hooks
		add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_column'], 20);
		add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'render_hpos_column'], 10, 2);

		// Legacy CPT columns hooks
		add_filter('manage_edit-shop_order_columns', [$this, 'add_column'], 20);
		add_action('manage_shop_order_posts_custom_column', [$this, 'render_legacy_column'], 10, 2);
	}

	/**
	 * Add custom column header.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function add_column(array $columns): array {
		$new_columns = [];
		foreach ($columns as $key => $title) {
			$new_columns[$key] = $title;
			// Place our column after order_status or order_total
			if ($key === 'order_status') {
				$new_columns[self::COLUMN_KEY] = __('Shipment', 'wpcalibrate-shipment-tracker');
			}
		}

		if (!isset($new_columns[self::COLUMN_KEY])) {
			$new_columns[self::COLUMN_KEY] = __('Shipment', 'wpcalibrate-shipment-tracker');
		}

		return $new_columns;
	}

	/**
	 * Render column content for HPOS order tables.
	 *
	 * @param string $column Column name.
	 * @param WC_Order $order WooCommerce Order object.
	 * @return void
	 */
	public function render_hpos_column(string $column, $order): void {
		if ($column !== self::COLUMN_KEY) {
			return;
		}

		$order_obj = $order instanceof WC_Order ? $order : wc_get_order($order);
		if (!$order_obj instanceof WC_Order) {
			echo '&ndash;';
			return;
		}

		$this->maybe_batch_load_hpos();
		$this->render_cell_content($order_obj->get_id());
	}

	/**
	 * Render column content for legacy CPT shop_order tables.
	 *
	 * @param string $column Column name.
	 * @param int $post_id Post/Order ID.
	 * @return void
	 */
	public function render_legacy_column(string $column, int $post_id): void {
		if ($column !== self::COLUMN_KEY) {
			return;
		}

		$this->maybe_batch_load_legacy();
		$this->render_cell_content($post_id);
	}

	/**
	 * Pre-fetch shipments for all visible HPOS orders in one single database query.
	 *
	 * @return void
	 */
	private function maybe_batch_load_hpos(): void {
		if ($this->batched_shipments !== null) {
			return;
		}

		$order_ids = [];

		// Inspect the global orders table if available.
		if (isset($GLOBALS['orders_table']) && is_object($GLOBALS['orders_table'])) {
			$items = $GLOBALS['orders_table']->items ?? [];
			if (is_iterable($items)) {
				foreach ($items as $item) {
					if ($item instanceof WC_Order) {
						$order_ids[] = $item->get_id();
					} elseif (is_numeric($item)) {
						$order_ids[] = (int) $item;
					}
				}
			}
		}

		$this->batched_shipments = !empty($order_ids)
			? $this->shipment_repo->get_by_order_ids($order_ids)
			: [];
	}

	/**
	 * Pre-fetch shipments for all visible legacy post orders in one single database query.
	 *
	 * @return void
	 */
	private function maybe_batch_load_legacy(): void {
		if ($this->batched_shipments !== null) {
			return;
		}

		global $wp_query;
		$order_ids = [];

		if (!empty($wp_query->posts)) {
			foreach ($wp_query->posts as $post) {
				$order_ids[] = (int) $post->ID;
			}
		}

		$this->batched_shipments = !empty($order_ids)
			? $this->shipment_repo->get_by_order_ids($order_ids)
			: [];
	}

	/**
	 * Render the HTML for the shipment cell.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	private function render_cell_content(int $order_id): void {
		// Retrieve from batch cache if available, or fallback to single lookup.
		if ($this->batched_shipments !== null && array_key_exists($order_id, $this->batched_shipments)) {
			$shipments = $this->batched_shipments[$order_id];
		} else {
			$shipments = $this->shipment_repo->get_by_order_id($order_id);
		}

		if (empty($shipments)) {
			echo '<span class="wpc-st-list-empty" style="color: #8c8f94;">&ndash;</span>';
			return;
		}

		$count = count($shipments);
		// Display latest shipment status
		$latest = end($shipments);
		$status_label = ShipmentStatus::get_label($latest['status']);
		$badge_class = ShipmentStatus::get_badge_class($latest['status']);

		echo '<div class="wpc-st-list-cell">';
		echo '<span class="wpc-st-badge ' . esc_attr($badge_class) . '">' . esc_html($status_label) . '</span>';

		if (!empty($latest['tracking_number'])) {
			echo '<div class="wpc-st-list-tracking" style="margin-top: 4px; font-size: 11px;">';
			echo '<span class="wpc-st-list-carrier">' . esc_html($latest['carrier_name']) . ': </span>';
			if (!empty($latest['tracking_url'])) {
				echo '<a href="' . esc_url($latest['tracking_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($latest['tracking_number']) . '</a>';
			} else {
				echo '<code>' . esc_html($latest['tracking_number']) . '</code>';
			}
			echo '</div>';
		}

		if ($count > 1) {
			echo '<span class="wpc-st-list-multi" style="display: block; font-size: 11px; color: #646970; margin-top: 2px;">';
			printf(
				/* translators: %d: number of shipments */
				esc_html(_n('+%d more shipment', '+%d more shipments', $count - 1, 'wpcalibrate-shipment-tracker')),
				$count - 1
			);
			echo '</span>';
		}

		echo '</div>';
	}
}
