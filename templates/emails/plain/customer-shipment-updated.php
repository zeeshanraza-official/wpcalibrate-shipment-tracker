<?php
/**
 * Customer Shipment Updated Email (Plain Text).
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

echo "= " . esc_html($email_heading) . " =\n\n";

printf(
	/* translators: %s: Customer first name */
	esc_html__('Hi %s,', 'wpcalibrate-shipment-tracker') . "\n\n",
	esc_html($order->get_billing_first_name())
);

printf(
	/* translators: 1: Order number, 2: Carrier name */
	esc_html__('We have a shipment update for your order #%1$s handled by %2$s.', 'wpcalibrate-shipment-tracker') . "\n\n",
	esc_html($order->get_order_number()),
	esc_html($shipment['carrier_name'])
);

echo "----------------------------------------\n";
echo esc_html__('Carrier:', 'wpcalibrate-shipment-tracker') . ' ' . esc_html($shipment['carrier_name']) . "\n";
echo esc_html__('Tracking Number:', 'wpcalibrate-shipment-tracker') . ' ' . esc_html($shipment['tracking_number']) . "\n";
echo esc_html__('Status:', 'wpcalibrate-shipment-tracker') . ' ' . esc_html(ShipmentStatus::get_label($shipment['status'])) . "\n";

if (!empty($shipment['tracking_url'])) {
	echo esc_html__('Tracking Link:', 'wpcalibrate-shipment-tracker') . ' ' . esc_url_raw($shipment['tracking_url']) . "\n";
}

if (!empty($shipment['estimated_delivery_at'])) {
	echo esc_html__('Estimated Delivery:', 'wpcalibrate-shipment-tracker') . ' ' . esc_html(date_i18n(get_option('date_format'), strtotime($shipment['estimated_delivery_at']))) . "\n";
}

if (!empty($shipment['customer_note'])) {
	echo esc_html__('Note from Store:', 'wpcalibrate-shipment-tracker') . ' ' . esc_html($shipment['customer_note']) . "\n";
}
echo "----------------------------------------\n\n";

if (!empty($shipment_items)) {
	echo esc_html__('Items in this package:', 'wpcalibrate-shipment-tracker') . "\n";
	foreach ($shipment_items as $item_row) {
		$item = $order->get_item((int) $item_row['order_item_id']);
		$item_name = $item ? $item->get_name() : sprintf(__('Item #%d', 'wpcalibrate-shipment-tracker'), $item_row['order_item_id']);
		echo sprintf("- %s (Qty: %s)\n", esc_html($item_name), esc_html(rtrim(rtrim(sprintf('%.2f', $item_row['quantity']), '0'), '.')));
	}
	echo "\n";
}

echo esc_html__('If you have any questions, please reply to this email.', 'wpcalibrate-shipment-tracker') . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
