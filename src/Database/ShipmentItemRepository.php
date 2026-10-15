<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Database;

defined('ABSPATH') || exit;

/**
 * Repository for CRUD operations on shipment items.
 */
class ShipmentItemRepository {
	/**
	 * Table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->table = MigrationManager::get_table_name(MigrationManager::TABLE_SHIPMENT_ITEMS);
	}

	/**
	 * Get all items assigned to a specific shipment.
	 *
	 * @param int $shipment_id Shipment ID.
	 * @return array<int, array<string, mixed>> List of items.
	 */
	public function get_by_shipment_id(int $shipment_id): array {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table} WHERE shipment_id = %d ORDER BY id ASC",
			$shipment_id
		);
		$rows = $wpdb->get_results($sql, ARRAY_A);

		if (!is_array($rows)) {
			return [];
		}

		return array_map([$this, 'normalize_row'], $rows);
	}

	/**
	 * Get items for multiple shipments in a batch query.
	 *
	 * @param array<int> $shipment_ids Array of shipment IDs.
	 * @return array<int, array<int, array<string, mixed>>> Items grouped by shipment_id.
	 */
	public function get_by_shipment_ids(array $shipment_ids): array {
		global $wpdb;

		$clean_ids = array_filter(array_map('absint', $shipment_ids));
		if (empty($clean_ids)) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($clean_ids), '%d'));
		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table} WHERE shipment_id IN ({$placeholders}) ORDER BY id ASC",
			...$clean_ids
		);

		$rows = $wpdb->get_results($sql, ARRAY_A);
		$grouped = [];

		foreach ($clean_ids as $sid) {
			$grouped[$sid] = [];
		}

		if (is_array($rows)) {
			foreach ($rows as $row) {
				$norm = $this->normalize_row($row);
				$sid = (int) $norm['shipment_id'];
				$grouped[$sid][] = $norm;
			}
		}

		return $grouped;
	}

	/**
	 * Calculate total allocated quantities grouped by order_item_id.
	 *
	 * @param array<int> $order_item_ids WooCommerce order line item IDs.
	 * @param int|null $exclude_shipment_id Optional shipment ID to exclude (e.g. when updating).
	 * @return array<int, float> Map of order_item_id => total allocated quantity.
	 */
	public function get_allocated_quantities_by_order_items(array $order_item_ids, ?int $exclude_shipment_id = null): array {
		global $wpdb;

		$clean_ids = array_filter(array_map('absint', $order_item_ids));
		if (empty($clean_ids)) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($clean_ids), '%d'));
		$args = $clean_ids;

		$sql = "SELECT order_item_id, SUM(quantity) as total_qty FROM {$this->table} WHERE order_item_id IN ({$placeholders})";

		if ($exclude_shipment_id !== null && $exclude_shipment_id > 0) {
			$sql .= " AND shipment_id != %d";
			$args[] = $exclude_shipment_id;
		}

		$sql .= " GROUP BY order_item_id";

		$prepared = $wpdb->prepare($sql, ...$args);
		$results = $wpdb->get_results($prepared, ARRAY_A);

		$allocations = [];
		foreach ($clean_ids as $oid) {
			$allocations[$oid] = 0.0;
		}

		if (is_array($results)) {
			foreach ($results as $row) {
				$item_id = (int) $row['order_item_id'];
				$allocations[$item_id] = (float) $row['total_qty'];
			}
		}

		return $allocations;
	}

	/**
	 * Set or synchronize items for a given shipment.
	 *
	 * @param int $shipment_id Shipment ID.
	 * @param array<int, float|int|string> $items Map of order_item_id => quantity.
	 * @return bool True on success.
	 */
	public function set_items_for_shipment(int $shipment_id, array $items): bool {
		global $wpdb;

		$shipment_id = absint($shipment_id);
		if ($shipment_id <= 0) {
			return false;
		}

		// Delete existing allocations for this shipment.
		$wpdb->delete($this->table, ['shipment_id' => $shipment_id], ['%d']);

		$now = current_time('mysql', true);

		foreach ($items as $order_item_id => $qty) {
			$quantity = (float) $qty;
			$order_item_id = absint($order_item_id);

			if ($quantity > 0 && $order_item_id > 0) {
				$wpdb->insert(
					$this->table,
					[
						'shipment_id'   => $shipment_id,
						'order_item_id' => $order_item_id,
						'quantity'      => $quantity,
						'created_at'    => $now,
						'updated_at'    => $now,
					],
					['%d', '%d', '%f', '%s', '%s']
				);
			}
		}

		return true;
	}

	/**
	 * Delete all item rows for a given shipment.
	 *
	 * @param int $shipment_id Shipment ID.
	 * @return bool True on success.
	 */
	public function delete_by_shipment_id(int $shipment_id): bool {
		global $wpdb;

		$shipment_id = absint($shipment_id);
		if ($shipment_id <= 0) {
			return false;
		}

		$result = $wpdb->delete($this->table, ['shipment_id' => $shipment_id], ['%d']);
		return $result !== false;
	}

	/**
	 * Normalize row data types.
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return array<string, mixed>
	 */
	private function normalize_row(array $row): array {
		return [
			'id'            => (int) $row['id'],
			'shipment_id'   => (int) $row['shipment_id'],
			'order_item_id' => (int) $row['order_item_id'],
			'quantity'      => (float) $row['quantity'],
			'created_at'    => (string) $row['created_at'],
			'updated_at'    => $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
		];
	}
}
