<?php
/**
 * Customer Shipment Updated Email (HTML).
 *
 * @var WC_Order $order
 * @var array<string, mixed> $shipment
 * @var array<int, array<string, mixed>> $shipment_items
 * @var string $email_heading
 * @var bool $sent_to_admin
 * @var bool $plain_text
 * @var WC_Email $email
 */

declare(strict_types=1);

use WPCalibrate\ShipmentTracker\Shipments\ShipmentStatus;

defined('ABSPATH') || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);

$status_label = ShipmentStatus::get_label($shipment['status']);
?>

<p>
	<?php 
	printf(
		/* translators: %s: Customer first name */
		esc_html__('Hi %s,', 'wpcalibrate-shipment-tracker'),
		esc_html($order->get_billing_first_name())
	); 
	?>
</p>

<p>
	<?php 
	printf(
		/* translators: 1: Order number, 2: Carrier name */
		esc_html__('We have a shipment update for your order #%1$s handled by %2$s.', 'wpcalibrate-shipment-tracker'),
		esc_html($order->get_order_number()),
		esc_html($shipment['carrier_name'])
	); 
	?>
</p>

<!-- Shipment Details Card -->
<div style="margin: 25px 0; border: 1px solid #e0e0e0; border-radius: 6px; padding: 18px; background-color: #fcfcfc;">
	<table style="width: 100%; border-collapse: collapse; font-size: 14px;" cellpadding="6">
		<tr>
			<td style="font-weight: 600; width: 40%; color: #555555;"><?php esc_html_e('Shipping Carrier:', 'wpcalibrate-shipment-tracker'); ?></td>
			<td style="font-weight: bold;"><?php echo esc_html($shipment['carrier_name']); ?></td>
		</tr>
		<tr>
			<td style="font-weight: 600; color: #555555;"><?php esc_html_e('Tracking Number:', 'wpcalibrate-shipment-tracker'); ?></td>
			<td>
				<?php if (!empty($shipment['tracking_url'])) : ?>
					<a href="<?php echo esc_url($shipment['tracking_url']); ?>" target="_blank" rel="noopener noreferrer" style="color: #7f54b3; font-weight: bold; text-decoration: underline;">
						<?php echo esc_html($shipment['tracking_number']); ?>
					</a>
				<?php else : ?>
					<code><?php echo esc_html($shipment['tracking_number']); ?></code>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td style="font-weight: 600; color: #555555;"><?php esc_html_e('Shipment Status:', 'wpcalibrate-shipment-tracker'); ?></td>
			<td style="font-weight: bold; color: #2271b1;"><?php echo esc_html($status_label); ?></td>
		</tr>
		<?php if (!empty($shipment['estimated_delivery_at'])) : ?>
			<tr>
				<td style="font-weight: 600; color: #555555;"><?php esc_html_e('Estimated Delivery:', 'wpcalibrate-shipment-tracker'); ?></td>
				<td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($shipment['estimated_delivery_at']))); ?></td>
			</tr>
		<?php endif; ?>
		<?php if (!empty($shipment['customer_note'])) : ?>
			<tr>
				<td style="font-weight: 600; color: #555555;"><?php esc_html_e('Note from Store:', 'wpcalibrate-shipment-tracker'); ?></td>
				<td><em><?php echo esc_html($shipment['customer_note']); ?></em></td>
			</tr>
		<?php endif; ?>
	</table>

	<?php if (!empty($shipment['tracking_url'])) : ?>
		<div style="margin-top: 18px; text-align: center;">
			<a href="<?php echo esc_url($shipment['tracking_url']); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-block; background-color: #7f54b3; color: #ffffff; text-decoration: none; padding: 10px 22px; border-radius: 4px; font-weight: bold; font-size: 14px;">
				<?php esc_html_e('Track Package with Carrier &rarr;', 'wpcalibrate-shipment-tracker'); ?>
			</a>
		</div>
	<?php endif; ?>
</div>

<!-- Included Items in Package -->
<?php if (!empty($shipment_items)) : ?>
	<h3 style="color: #333333; font-size: 15px; margin-top: 25px; margin-bottom: 10px;">
		<?php esc_html_e('Items included in this package:', 'wpcalibrate-shipment-tracker'); ?>
	</h3>
	<table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 25px;" cellpadding="6">
		<thead>
			<tr style="border-bottom: 2px solid #e0e0e0; text-align: left; background-color: #f7f7f7;">
				<th style="padding: 8px;"><?php esc_html_e('Product', 'wpcalibrate-shipment-tracker'); ?></th>
				<th style="padding: 8px; text-align: right;"><?php esc_html_e('Quantity', 'wpcalibrate-shipment-tracker'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($shipment_items as $item_row) : 
				$item = $order->get_item((int) $item_row['order_item_id']);
				$item_name = $item ? $item->get_name() : sprintf(__('Item #%d', 'wpcalibrate-shipment-tracker'), $item_row['order_item_id']);
			?>
				<tr style="border-bottom: 1px solid #eeeeee;">
					<td style="padding: 8px;"><?php echo esc_html($item_name); ?></td>
					<td style="padding: 8px; text-align: right;"><?php echo esc_html(rtrim(rtrim(sprintf('%.2f', $item_row['quantity']), '0'), '.')); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<p style="font-size: 13px; color: #777777;">
	<?php esc_html_e('If you have any questions about this shipment, simply reply to this email to reach our customer support team.', 'wpcalibrate-shipment-tracker'); ?>
</p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
