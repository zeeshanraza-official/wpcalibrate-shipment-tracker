<?php
/**
 * Admin Order Meta Box Template.
 *
 * Variables available:
 * @var WC_Order $order
 * @var array<int, array<string, mixed>> $shipments
 * @var array<int, array<int, array<string, mixed>>> $shipment_items_map
 * @var array<int, array<int, array<string, mixed>>> $shipment_events_map
 * @var array<int, array<string, mixed>> $allocation_status
 * @var array<string, array<string, mixed>> $carriers
 */

declare(strict_types=1);

use WPCalibrate\ShipmentTracker\Shipments\ShipmentStatus;

defined('ABSPATH') || exit;

$order_id = $order->get_id();
$has_unallocated_items = false;

foreach ($allocation_status as $item_status) {
	if ($item_status['available_qty'] > 0.0001) {
		$has_unallocated_items = true;
		break;
	}
}
?>

<div class="wpc-st-meta-box-wrap" id="wpc-st-meta-box-<?php echo esc_attr((string) $order_id); ?>" data-order-id="<?php echo esc_attr((string) $order_id); ?>">
	<!-- Header Bar -->
	<div class="wpc-st-box-header">
		<div class="wpc-st-box-title">
			<span class="dashicons dashicons-location-alt" aria-hidden="true"></span>
			<strong><?php esc_html_e('Shipments for this Order', 'wpcalibrate-shipment-tracker'); ?></strong>
			<span class="wpc-st-badge-count"><?php echo esc_html((string) count($shipments)); ?></span>
		</div>
		<div class="wpc-st-box-actions">
			<button type="button" class="button button-primary wpc-st-btn-add-shipment" id="wpc-st-add-shipment-btn">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
				<?php esc_html_e('Add Shipment', 'wpcalibrate-shipment-tracker'); ?>
			</button>
		</div>
	</div>

	<!-- Shipment Cards / List -->
	<div class="wpc-st-shipments-list" id="wpc-st-shipments-container">
		<?php if (empty($shipments)) : ?>
			<div class="wpc-st-empty-state">
				<p class="wpc-st-empty-text">
					<?php esc_html_e('No shipments created yet for this order.', 'wpcalibrate-shipment-tracker'); ?>
				</p>
				<?php if ($has_unallocated_items) : ?>
					<p class="wpc-st-empty-hint">
						<?php esc_html_e('Order items are available to fulfill. Click "Add Shipment" to create the first shipment.', 'wpcalibrate-shipment-tracker'); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<?php foreach ($shipments as $shipment) : 
				$sid = (int) $shipment['id'];
				$status_key = $shipment['status'];
				$status_label = ShipmentStatus::get_label($status_key);
				$badge_class = ShipmentStatus::get_badge_class($status_key);
				$items = $shipment_items_map[$sid] ?? [];
			?>
				<div class="wpc-st-shipment-card" id="wpc-st-card-<?php echo esc_attr((string) $sid); ?>" data-shipment-id="<?php echo esc_attr((string) $sid); ?>">
					<div class="wpc-st-card-header">
						<div class="wpc-st-card-main-info">
							<span class="wpc-st-carrier-name"><?php echo esc_html($shipment['carrier_name']); ?></span>
							<span class="wpc-st-tracking-num">
								<?php if (!empty($shipment['tracking_url'])) : ?>
									<a href="<?php echo esc_url($shipment['tracking_url']); ?>" target="_blank" rel="noopener noreferrer" class="wpc-st-tracking-link" title="<?php esc_attr_e('Track with carrier (opens in a new tab)', 'wpcalibrate-shipment-tracker'); ?>">
										<?php echo esc_html($shipment['tracking_number']); ?>
										<span class="dashicons dashicons-external" aria-hidden="true"></span>
									</a>
								<?php else : ?>
									<code><?php echo esc_html($shipment['tracking_number']); ?></code>
								<?php endif; ?>
							</span>
							<span class="wpc-st-badge <?php echo esc_attr($badge_class); ?>">
								<?php echo esc_html($status_label); ?>
							</span>
						</div>
						<div class="wpc-st-card-actions">
							<button type="button" class="button button-small wpc-st-btn-edit" data-id="<?php echo esc_attr((string) $sid); ?>" aria-label="<?php esc_attr_e('Edit this shipment', 'wpcalibrate-shipment-tracker'); ?>">
								<span class="dashicons dashicons-edit" aria-hidden="true"></span>
								<?php esc_html_e('Edit', 'wpcalibrate-shipment-tracker'); ?>
							</button>
							<button type="button" class="button button-small button-link-delete wpc-st-btn-delete" data-id="<?php echo esc_attr((string) $sid); ?>" aria-label="<?php esc_attr_e('Delete this shipment', 'wpcalibrate-shipment-tracker'); ?>">
								<span class="dashicons dashicons-trash" aria-hidden="true"></span>
								<?php esc_html_e('Delete', 'wpcalibrate-shipment-tracker'); ?>
							</button>
						</div>
					</div>

					<div class="wpc-st-card-body">
						<div class="wpc-st-card-meta-grid">
							<?php if (!empty($shipment['shipped_at'])) : ?>
								<div class="wpc-st-meta-item">
									<span class="wpc-st-meta-label"><?php esc_html_e('Shipped Date:', 'wpcalibrate-shipment-tracker'); ?></span>
									<span class="wpc-st-meta-value"><?php echo esc_html($shipment['shipped_at']); ?></span>
								</div>
							<?php endif; ?>
							<?php if (!empty($shipment['estimated_delivery_at'])) : ?>
								<div class="wpc-st-meta-item">
									<span class="wpc-st-meta-label"><?php esc_html_e('Estimated Delivery:', 'wpcalibrate-shipment-tracker'); ?></span>
									<span class="wpc-st-meta-value"><?php echo esc_html($shipment['estimated_delivery_at']); ?></span>
								</div>
							<?php endif; ?>
							<?php if (!empty($shipment['delivered_at'])) : ?>
								<div class="wpc-st-meta-item">
									<span class="wpc-st-meta-label"><?php esc_html_e('Delivered Date:', 'wpcalibrate-shipment-tracker'); ?></span>
									<span class="wpc-st-meta-value"><?php echo esc_html($shipment['delivered_at']); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<!-- Items in this shipment -->
						<?php if (!empty($items)) : ?>
							<div class="wpc-st-items-assigned">
								<h4 class="wpc-st-section-subheading"><?php esc_html_e('Assigned Items in Shipment:', 'wpcalibrate-shipment-tracker'); ?></h4>
								<ul class="wpc-st-item-chips">
									<?php foreach ($items as $item_row) : 
										$item_id = (int) $item_row['order_item_id'];
										$item_info = $allocation_status[$item_id] ?? null;
										$item_title = $item_info ? $item_info['name'] : sprintf(__('Item #%d', 'wpcalibrate-shipment-tracker'), $item_id);
									?>
										<li class="wpc-st-item-chip">
											<span class="wpc-st-chip-qty"><?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $item_row['quantity']), '0'), '.')); ?> &times;</span>
											<span class="wpc-st-chip-title"><?php echo esc_html($item_title); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<!-- Notes -->
						<?php if (!empty($shipment['customer_note']) || !empty($shipment['internal_note'])) : ?>
							<div class="wpc-st-card-notes">
								<?php if (!empty($shipment['customer_note'])) : ?>
									<div class="wpc-st-note wpc-st-customer-note">
										<span class="wpc-st-note-title"><?php esc_html_e('Customer Note:', 'wpcalibrate-shipment-tracker'); ?></span>
										<p><?php echo esc_html($shipment['customer_note']); ?></p>
									</div>
								<?php endif; ?>
								<?php if (!empty($shipment['internal_note'])) : ?>
									<div class="wpc-st-note wpc-st-internal-note">
										<span class="wpc-st-note-title"><?php esc_html_e('Internal Note:', 'wpcalibrate-shipment-tracker'); ?></span>
										<p><?php echo esc_html($shipment['internal_note']); ?></p>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<!-- Allocation Summary Status -->
	<div class="wpc-st-allocation-summary">
		<h4 class="wpc-st-section-subheading"><?php esc_html_e('Order Fulfillment Status:', 'wpcalibrate-shipment-tracker'); ?></h4>
		<table class="widefat fixed striped wpc-st-allocation-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Product', 'wpcalibrate-shipment-tracker'); ?></th>
					<th class="wpc-st-col-qty"><?php esc_html_e('Ordered', 'wpcalibrate-shipment-tracker'); ?></th>
					<th class="wpc-st-col-qty"><?php esc_html_e('Refunded', 'wpcalibrate-shipment-tracker'); ?></th>
					<th class="wpc-st-col-qty"><?php esc_html_e('Assigned to Shipments', 'wpcalibrate-shipment-tracker'); ?></th>
					<th class="wpc-st-col-qty"><?php esc_html_e('Remaining to Ship', 'wpcalibrate-shipment-tracker'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($allocation_status as $alloc) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html($alloc['name']); ?></strong>
							<?php if (!empty($alloc['sku'])) : ?>
								<span class="wpc-st-sku"><?php echo esc_html('(' . $alloc['sku'] . ')'); ?></span>
							<?php endif; ?>
						</td>
						<td class="wpc-st-col-qty"><?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $alloc['ordered_qty']), '0'), '.')); ?></td>
						<td class="wpc-st-col-qty"><?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $alloc['refunded_qty']), '0'), '.')); ?></td>
						<td class="wpc-st-col-qty"><?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $alloc['allocated_qty']), '0'), '.')); ?></td>
						<td class="wpc-st-col-qty">
							<?php if ($alloc['available_qty'] > 0.0001) : ?>
								<span class="wpc-st-qty-pending">
									<?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $alloc['available_qty']), '0'), '.')); ?>
								</span>
							<?php else : ?>
								<span class="wpc-st-qty-done">
									<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
									<?php esc_html_e('Fulfilled', 'wpcalibrate-shipment-tracker'); ?>
								</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- Modal Container for Adding/Editing Shipment -->
	<?php include WPC_SHIPMENT_TRACKER_PATH . 'templates/admin/shipment-modal-form.php'; ?>
</div>
