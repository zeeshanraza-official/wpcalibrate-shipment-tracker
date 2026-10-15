<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Database;

defined('ABSPATH') || exit;

/**
 * Repository for recording and retrieving shipment lifecycle events.
 */
class ShipmentEventRepository {
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
		$this->table = MigrationManager::get_table_name(MigrationManager::TABLE_SHIPMENT_EVENTS);
	}

	/**
	 * Log a shipment event.
	 *
	 * @param int $shipment_id Shipment ID.
	 * @param string $event_type Event type (e.g. 'status_change', 'created', 'updated').
	 * @param string|null $previous_status Previous status code.
	 * @param string|null $new_status New status code.
	 * @param string|null $actor_type Actor type ('admin', 'system', 'customer').
	 * @return int Inserted event ID.
	 */
	public function log_event(
		int $shipment_id,
		string $event_type,
		?string $previous_status = null,
		?string $new_status = null,
		?string $actor_type = 'admin'
	): int {
		global $wpdb;

		$shipment_id = absint($shipment_id);
		if ($shipment_id <= 0) {
			return 0;
		}

		$now = current_time('mysql', true);

		$result = $wpdb->insert(
			$this->table,
			[
				'shipment_id'     => $shipment_id,
				'event_type'      => sanitize_key($event_type),
				'previous_status' => $previous_status !== null ? sanitize_key($previous_status) : null,
				'new_status'      => $new_status !== null ? sanitize_key($new_status) : null,
				'event_at'        => $now,
				'actor_type'      => $actor_type !== null ? sanitize_key($actor_type) : 'admin',
			],
			['%d', '%s', '%s', '%s', '%s', '%s']
		);

		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Get all chronological events for a specific shipment.
	 *
	 * @param int $shipment_id Shipment ID.
	 * @return array<int, array<string, mixed>> List of event rows.
	 */
	public function get_by_shipment_id(int $shipment_id): array {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table} WHERE shipment_id = %d ORDER BY event_at ASC, id ASC",
			$shipment_id
		);
		$rows = $wpdb->get_results($sql, ARRAY_A);

		if (!is_array($rows)) {
			return [];
		}

		return array_map([$this, 'normalize_row'], $rows);
	}

	/**
	 * Get events for multiple shipments in a batch query.
	 *
	 * @param array<int> $shipment_ids Array of shipment IDs.
	 * @return array<int, array<int, array<string, mixed>>> Grouped events by shipment_id.
	 */
	public function get_by_shipment_ids(array $shipment_ids): array {
		global $wpdb;

		$clean_ids = array_filter(array_map('absint', $shipment_ids));
		if (empty($clean_ids)) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($clean_ids), '%d'));
		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table} WHERE shipment_id IN ({$placeholders}) ORDER BY event_at ASC, id ASC",
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
	 * Delete all events for a specific shipment.
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
			'id'              => (int) $row['id'],
			'shipment_id'     => (int) $row['shipment_id'],
			'event_type'      => (string) $row['event_type'],
			'previous_status' => $row['previous_status'] !== null ? (string) $row['previous_status'] : null,
			'new_status'      => $row['new_status'] !== null ? (string) $row['new_status'] : null,
			'event_at'        => (string) $row['event_at'],
			'actor_type'      => $row['actor_type'] !== null ? (string) $row['actor_type'] : null,
		];
	}
}
