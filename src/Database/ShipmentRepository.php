<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Database;

defined('ABSPATH') || exit;

/**
 * Repository for CRUD operations on the shipments table.
 */
class ShipmentRepository {
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
		$this->table = MigrationManager::get_table_name(MigrationManager::TABLE_SHIPMENTS);
	}

	/**
	 * Retrieve a single shipment by its primary ID.
	 *
	 * @param int $id Shipment ID.
	 * @return array<string, mixed>|null Associative row array or null if not found.
	 */
	public function get_by_id(int $id): ?array {
		global $wpdb;

		$sql = $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d LIMIT 1", $id);
		$row = $wpdb->get_row($sql, ARRAY_A);

		return is_array($row) ? $this->normalize_row($row) : null;
	}

	/**
	 * Retrieve all shipments for a specific WooCommerce order.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return array<int, array<string, mixed>> List of shipment rows.
	 */
	public function get_by_order_id(int $order_id): array {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table} WHERE order_id = %d ORDER BY created_at ASC, id ASC",
			$order_id
		);
		$rows = $wpdb->get_results($sql, ARRAY_A);

		if (!is_array($rows)) {
			return [];
		}

		return array_map([$this, 'normalize_row'], $rows);
	}

	/**
	 * Retrieve all shipments for a list of WooCommerce order IDs in a single batch query.
	 * Designed specifically to prevent N+1 queries on order listing pages.
	 *
	 * @param array<int> $order_ids Array of order IDs.
	 * @return array<int, array<int, array<string, mixed>>> Shipments grouped by order_id.
	 */
	public function get_by_order_ids(array $order_ids): array {
		global $wpdb;

		$clean_ids = array_filter(array_map('absint', $order_ids));
		if (empty($clean_ids)) {
			return [];
		}

		// Prepare placeholders for the IN clause.
		$placeholders = implode(',', array_fill(0, count($clean_ids), '%d'));
		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table} WHERE order_id IN ({$placeholders}) ORDER BY created_at ASC, id ASC",
			...$clean_ids
		);

		$rows = $wpdb->get_results($sql, ARRAY_A);
		$grouped = [];

		// Initialize all requested order IDs with empty arrays.
		foreach ($clean_ids as $oid) {
			$grouped[$oid] = [];
		}

		if (is_array($rows)) {
			foreach ($rows as $row) {
				$norm = $this->normalize_row($row);
				$oid = (int) $norm['order_id'];
				$grouped[$oid][] = $norm;
			}
		}

		return $grouped;
	}

	/**
	 * Create a new shipment.
	 *
	 * @param array<string, mixed> $data Shipment data.
	 * @return int Newly created shipment ID, or 0 on failure.
	 */
	public function create(array $data): int {
		global $wpdb;

		$now = current_time('mysql', true);

		$insert_data = [
			'order_id'              => absint($data['order_id'] ?? 0),
			'carrier_key'           => sanitize_key($data['carrier_key'] ?? 'custom'),
			'carrier_name'          => sanitize_text_field($data['carrier_name'] ?? ''),
			'tracking_number'       => sanitize_text_field($data['tracking_number'] ?? ''),
			'tracking_url'          => esc_url_raw($data['tracking_url'] ?? ''),
			'status'                => sanitize_key($data['status'] ?? 'preparing'),
			'shipped_at'            => !empty($data['shipped_at']) ? sanitize_text_field((string)$data['shipped_at']) : null,
			'estimated_delivery_at' => !empty($data['estimated_delivery_at']) ? sanitize_text_field((string)$data['estimated_delivery_at']) : null,
			'delivered_at'          => !empty($data['delivered_at']) ? sanitize_text_field((string)$data['delivered_at']) : null,
			'internal_note'         => !empty($data['internal_note']) ? sanitize_textarea_field((string)$data['internal_note']) : null,
			'customer_note'         => !empty($data['customer_note']) ? sanitize_textarea_field((string)$data['customer_note']) : null,
			'created_at'            => $data['created_at'] ?? $now,
			'updated_at'            => $data['updated_at'] ?? $now,
		];

		$formats = [
			'%d', // order_id
			'%s', // carrier_key
			'%s', // carrier_name
			'%s', // tracking_number
			'%s', // tracking_url
			'%s', // status
			'%s', // shipped_at
			'%s', // estimated_delivery_at
			'%s', // delivered_at
			'%s', // internal_note
			'%s', // customer_note
			'%s', // created_at
			'%s', // updated_at
		];

		$result = $wpdb->insert($this->table, $insert_data, $formats);

		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Update an existing shipment.
	 *
	 * @param int $id Shipment ID.
	 * @param array<string, mixed> $data Updated shipment data.
	 * @return bool True on success, false on failure.
	 */
	public function update(int $id, array $data): bool {
		global $wpdb;

		$id = absint($id);
		if ($id <= 0) {
			return false;
		}

		$allowed_fields = [
			'carrier_key'           => '%s',
			'carrier_name'          => '%s',
			'tracking_number'       => '%s',
			'tracking_url'          => '%s',
			'status'                => '%s',
			'shipped_at'            => '%s',
			'estimated_delivery_at' => '%s',
			'delivered_at'          => '%s',
			'internal_note'         => '%s',
			'customer_note'         => '%s',
		];

		$update_data = [];
		$formats = [];

		foreach ($allowed_fields as $field => $fmt) {
			if (array_key_exists($field, $data)) {
				$val = $data[$field];
				if ($field === 'carrier_key' || $field === 'status') {
					$update_data[$field] = sanitize_key((string)$val);
				} elseif ($field === 'tracking_url') {
					$update_data[$field] = esc_url_raw((string)$val);
				} elseif ($field === 'internal_note' || $field === 'customer_note') {
					$update_data[$field] = $val !== null ? sanitize_textarea_field((string)$val) : null;
				} else {
					$update_data[$field] = $val !== null ? sanitize_text_field((string)$val) : null;
				}
				$formats[] = $fmt;
			}
		}

		$update_data['updated_at'] = current_time('mysql', true);
		$formats[] = '%s';

		$result = $wpdb->update($this->table, $update_data, ['id' => $id], $formats, ['%d']);

		return $result !== false;
	}

	/**
	 * Delete a shipment by ID.
	 *
	 * @param int $id Shipment ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete(int $id): bool {
		global $wpdb;

		$id = absint($id);
		if ($id <= 0) {
			return false;
		}

		$result = $wpdb->delete($this->table, ['id' => $id], ['%d']);

		return $result !== false;
	}

	/**
	 * Normalize row data types.
	 *
	 * @param array<string, mixed> $row Raw database row.
	 * @return array<string, mixed> Normalized row.
	 */
	private function normalize_row(array $row): array {
		return [
			'id'                    => (int) $row['id'],
			'order_id'              => (int) $row['order_id'],
			'carrier_key'           => (string) $row['carrier_key'],
			'carrier_name'          => (string) $row['carrier_name'],
			'tracking_number'       => (string) $row['tracking_number'],
			'tracking_url'          => (string) $row['tracking_url'],
			'status'                => (string) $row['status'],
			'shipped_at'            => $row['shipped_at'] !== null ? (string) $row['shipped_at'] : null,
			'estimated_delivery_at' => $row['estimated_delivery_at'] !== null ? (string) $row['estimated_delivery_at'] : null,
			'delivered_at'          => $row['delivered_at'] !== null ? (string) $row['delivered_at'] : null,
			'internal_note'         => $row['internal_note'] !== null ? (string) $row['internal_note'] : null,
			'customer_note'         => $row['customer_note'] !== null ? (string) $row['customer_note'] : null,
			'created_at'            => (string) $row['created_at'],
			'updated_at'            => (string) $row['updated_at'],
		];
	}
}
