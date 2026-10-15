<?php
/**
 * Customer My Account Shipments Display Template.
 *
 * Variables available from caller:
 * @var WC_Order $order
 * @var array<int, array<string, mixed>> $shipments
 * @var array<int, array<int, array<string, mixed>>> $shipment_items_map
 * @var array<int, array<int, array<string, mixed>>> $shipment_events_map
 * @var array<int, array<string, mixed>> $allocation_status
 */

declare(strict_types=1);

use WPCalibrate\ShipmentTracker\Shipments\ShipmentStatus;

defined('ABSPATH') || exit;

// Check if any items are unshipped for partial shipment display.
$unshipped_items = [];
foreach ($allocation_status as $item) {
	if ($item['available_qty'] > 0.0001) {
		$unshipped_items[] = $item;
	}
}
?>

<section class="woocommerce-columns woocommerce-columns--1 woocommerce-shipment-tracking" id="wpc-st-customer-tracking">
	<h2 class="woocommerce-column__title"><?php esc_html_e('Shipment Tracking', 'wpcalibrate-shipment-tracker'); ?></h2>

	<div class="wpc-st-customer-cards">
		<?php foreach ($shipments as $shipment) : 
			$sid = (int) $shipment['id'];
			$status_key = $shipment['status'];
			$status_label = ShipmentStatus::get_label($status_key);
			$badge_class = ShipmentStatus::get_badge_class($status_key);
			$items = $shipment_items_map[$sid] ?? [];
			$events = $shipment_events_map[$sid] ?? [];
		?>
			<div class="wpc-st-cust-card">
				<div class="wpc-st-cust-card-header">
					<div class="wpc-st-cust-carrier-group">
						<span class="wpc-st-cust-carrier"><?php echo esc_html($shipment['carrier_name']); ?></span>
						<span class="wpc-st-cust-tracking">
							<?php if (!empty($shipment['tracking_url'])) : ?>
								<a href="<?php echo esc_url($shipment['tracking_url']); ?>" target="_blank" rel="noopener noreferrer" class="button wpc-st-track-external-btn">
									<?php echo esc_html(sprintf(__('Track: %s', 'wpcalibrate-shipment-tracker'), $shipment['tracking_number'])); ?>
									<span class="dashicons dashicons-external" aria-hidden="true"></span>
								</a>
							<?php else : ?>
								<code><?php echo esc_html($shipment['tracking_number']); ?></code>
							<?php endif; ?>
						</span>
					</div>
					<div class="wpc-st-cust-status">
						<span class="wpc-st-badge <?php echo esc_attr($badge_class); ?>">
							<?php echo esc_html($status_label); ?>
						</span>
					</div>
				</div>

				<div class="wpc-st-cust-card-meta">
					<?php if (!empty($shipment['shipped_at'])) : ?>
						<div class="wpc-st-cust-meta-col">
							<span class="wpc-st-cust-meta-lbl"><?php esc_html_e('Shipped Date', 'wpcalibrate-shipment-tracker'); ?></span>
							<span class="wpc-st-cust-meta-val">
								<?php echo esc_html(date_i18n(get_option('date_format'), strtotime($shipment['shipped_at']))); ?>
							</span>
						</div>
					<?php endif; ?>

					<?php if (!empty($shipment['estimated_delivery_at'])) : ?>
						<div class="wpc-st-cust-meta-col">
							<span class="wpc-st-cust-meta-lbl"><?php esc_html_e('Estimated Delivery', 'wpcalibrate-shipment-tracker'); ?></span>
							<span class="wpc-st-cust-meta-val">
								<?php echo esc_html(date_i18n(get_option('date_format'), strtotime($shipment['estimated_delivery_at']))); ?>
							</span>
						</div>
					<?php endif; ?>

					<?php if (!empty($shipment['delivered_at'])) : ?>
						<div class="wpc-st-cust-meta-col">
							<span class="wpc-st-cust-meta-lbl"><?php esc_html_e('Delivered On', 'wpcalibrate-shipment-tracker'); ?></span>
							<span class="wpc-st-cust-meta-val">
								<?php echo esc_html(date_i18n(get_option('date_format'), strtotime($shipment['delivered_at']))); ?>
							</span>
						</div>
					<?php endif; ?>
				</div>

				<!-- Assigned Products in this shipment -->
				<?php if (!empty($items)) : ?>
					<div class="wpc-st-cust-items">
						<h4 class="wpc-st-cust-subheading"><?php esc_html_e('Items in this shipment:', 'wpcalibrate-shipment-tracker'); ?></h4>
						<ul class="wpc-st-cust-items-list">
							<?php foreach ($items as $item_row) : 
								$item_id = (int) $item_row['order_item_id'];
								$item_info = $allocation_status[$item_id] ?? null;
								$item_title = $item_info ? $item_info['name'] : sprintf(__('Item #%d', 'wpcalibrate-shipment-tracker'), $item_id);
							?>
								<li>
									<span class="wpc-st-cust-item-qty"><?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $item_row['quantity']), '0'), '.')); ?> &times;</span>
									<span class="wpc-st-cust-item-title"><?php echo esc_html($item_title); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<!-- Customer Note -->
				<?php if (!empty($shipment['customer_note'])) : ?>
					<div class="wpc-st-cust-note">
						<strong><?php esc_html_e('Note from store:', 'wpcalibrate-shipment-tracker'); ?></strong>
						<p><?php echo esc_html($shipment['customer_note']); ?></p>
					</div>
				<?php endif; ?>

				<!-- Visual Timeline -->
				<?php if (!empty($events)) : ?>
					<div class="wpc-st-cust-timeline-container">
						<h4 class="wpc-st-cust-subheading"><?php esc_html_e('Shipment Progress', 'wpcalibrate-shipment-tracker'); ?></h4>
						<?php 
							$current_status = $status_key;
							include WPC_SHIPMENT_TRACKER_PATH . 'templates/customer/shipment-timeline.php'; 
						?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<!-- Partial Shipment Notice if items are still pending -->
	<?php if (!empty($unshipped_items)) : ?>
		<div class="wpc-st-partial-notice">
			<h4><?php esc_html_e('Remaining Items Yet to Ship', 'wpcalibrate-shipment-tracker'); ?></h4>
			<p><?php esc_html_e('The following items from your order are still being prepared and will ship separately:', 'wpcalibrate-shipment-tracker'); ?></p>
			<ul>
				<?php foreach ($unshipped_items as $unshipped) : ?>
					<li>
						<?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $unshipped['available_qty']), '0'), '.')); ?> &times;
						<strong><?php echo esc_html($unshipped['name']); ?></strong>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
