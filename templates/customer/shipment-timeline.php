<?php
/**
 * Customer Shipment Event Timeline Template.
 *
 * Variables available from caller:
 * @var array<int, array<string, mixed>> $events
 * @var string $current_status
 */

declare(strict_types=1);

use WPCalibrate\ShipmentTracker\Shipments\ShipmentStatus;

defined('ABSPATH') || exit;

if (empty($events)) {
	return;
}
?>

<div class="wpc-st-timeline-wrap" aria-label="<?php esc_attr_e('Shipment history timeline', 'wpcalibrate-shipment-tracker'); ?>">
	<ol class="wpc-st-timeline-list">
		<?php 
		$total_events = count($events);
		foreach ($events as $index => $event) : 
			$is_latest = ($index === $total_events - 1);
			$status_to_show = $event['new_status'] ?? $event['previous_status'] ?? 'preparing';
			$status_label = ShipmentStatus::get_label($status_to_show);
			$event_time = !empty($event['event_at']) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event['event_at'])) : '';
		?>
			<li class="wpc-st-timeline-step <?php echo $is_latest ? 'wpc-st-step-active' : 'wpc-st-step-completed'; ?>">
				<div class="wpc-st-timeline-dot" aria-hidden="true"></div>
				<div class="wpc-st-timeline-content">
					<div class="wpc-st-timeline-status">
						<strong><?php echo esc_html($status_label); ?></strong>
						<?php if ($is_latest) : ?>
							<span class="wpc-st-current-tag"><?php esc_html_e('(Current)', 'wpcalibrate-shipment-tracker'); ?></span>
						<?php endif; ?>
					</div>
					<?php if (!empty($event_time)) : ?>
						<time class="wpc-st-timeline-time" datetime="<?php echo esc_attr($event['event_at']); ?>">
							<?php echo esc_html($event_time); ?>
						</time>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
