<?php
declare(strict_types=1);

namespace WPCalibrate\ShipmentTracker\Emails;

use WC_Order;
use WPCalibrate\ShipmentTracker\Database\ShipmentRepository;
use WPCalibrate\ShipmentTracker\Shipments\ShipmentStatus;
use WPCalibrate\ShipmentTracker\Settings\SettingsManager;

defined('ABSPATH') || exit;

/**
 * Manages email integrations, hooks into WooCommerce transactional emails,
 * and guarantees notification idempotency.
 */
class EmailManager {
	private ?SettingsManager $settings_manager;

	/**
	 * Constructor.
	 */
	public function __construct(?SettingsManager $settings_manager = null) {
		$this->settings_manager = $settings_manager;
	}

	/**
	 * Register WooCommerce email hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register custom email class.
		add_filter('woocommerce_email_classes', [$this, 'register_email_class']);

		// Inject tracking information into existing WooCommerce transactional emails.
		add_action('woocommerce_email_after_order_table', [$this, 'inject_tracking_into_wc_emails'], 15, 4);
	}

	/**
	 * Register WPC_Shipment_Updated_Email into WooCommerce emails registry.
	 *
	 * @param array<string, object> $emails Existing email classes.
	 * @return array<string, object>
	 */
	public function register_email_class(array $emails): array {
		$emails['WPC_Shipment_Updated_Email'] = new WPC_Shipment_Updated_Email();
		return $emails;
	}

	/**
	 * Send customer shipment notification with idempotency check.
	 *
	 * @param int $shipment_id Shipment ID.
	 * @return bool True if sent, false otherwise.
	 */
	public static function send_customer_notification(int $shipment_id): bool {
		$shipment_id = absint($shipment_id);
		if ($shipment_id <= 0) {
			return false;
		}

		$repo = new ShipmentRepository();
		$shipment = $repo->get_by_id($shipment_id);
		if (!$shipment) {
			return false;
		}

		// Idempotency safeguard: Prevent duplicate sends within 60 seconds for the same status.
		$transient_key = sprintf('wpc_st_email_%d_%s', $shipment_id, md5($shipment['status'] . ($shipment['updated_at'] ?? '')));
		if (get_transient($transient_key)) {
			return false; // Already sent recently
		}

		$wc_emails = \WC_Emails::instance();
		$emails = $wc_emails->get_emails();

		if (isset($emails['WPC_Shipment_Updated_Email']) && $emails['WPC_Shipment_Updated_Email'] instanceof WPC_Shipment_Updated_Email) {
			$sent = $emails['WPC_Shipment_Updated_Email']->trigger($shipment_id);
			if ($sent) {
				set_transient($transient_key, 1, 60);
			}
			return $sent;
		}

		return false;
	}

	/**
	 * Inject tracking table into core WooCommerce customer emails (e.g. Completed Order).
	 *
	 * @param WC_Order $order WooCommerce Order object.
	 * @param bool $sent_to_admin Whether sent to admin.
	 * @param bool $plain_text Whether email is plain text.
	 * @param object $email Current email instance.
	 * @return void
	 */
	public function inject_tracking_into_wc_emails($order, bool $sent_to_admin, bool $plain_text, $email): void {
		if ($sent_to_admin || !$order instanceof WC_Order) {
			return;
		}

		$repo = new ShipmentRepository();
		$shipments = $repo->get_by_order_id($order->get_id());

		if (empty($shipments)) {
			return;
		}

		if ($plain_text) {
			echo "\n" . esc_html__('--- SHIPMENT TRACKING ---', 'wpcalibrate-shipment-tracker') . "\n";
			foreach ($shipments as $s) {
				$status_lbl = ShipmentStatus::get_label($s['status']);
				echo sprintf(
					"%s: %s (%s)\n",
					esc_html($s['carrier_name']),
					esc_html($s['tracking_number']),
					esc_html($status_lbl)
				);
				if (!empty($s['tracking_url'])) {
					echo esc_url_raw($s['tracking_url']) . "\n";
				}
				if (!empty($s['customer_note'])) {
					echo sprintf("%s: %s\n", esc_html__('Note', 'wpcalibrate-shipment-tracker'), esc_html($s['customer_note']));
				}
			}
			echo "\n";
		} else {
			?>
			<div style="margin: 25px 0; border: 1px solid #e5e5e5; border-radius: 6px; padding: 16px; background-color: #fafafa;">
				<h3 style="margin-top: 0; color: #333333; font-size: 16px;">
					<?php esc_html_e('Shipment Tracking Details', 'wpcalibrate-shipment-tracker'); ?>
				</h3>
				<table style="width: 100%; border-collapse: collapse; font-size: 14px;" cellpadding="6">
					<thead>
						<tr style="border-bottom: 2px solid #e5e5e5; text-align: left;">
							<th><?php esc_html_e('Carrier', 'wpcalibrate-shipment-tracker'); ?></th>
							<th><?php esc_html_e('Tracking Number', 'wpcalibrate-shipment-tracker'); ?></th>
							<th><?php esc_html_e('Status', 'wpcalibrate-shipment-tracker'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($shipments as $s) : 
							$status_lbl = ShipmentStatus::get_label($s['status']);
						?>
							<tr style="border-bottom: 1px solid #e5e5e5;">
								<td style="font-weight: 600;"><?php echo esc_html($s['carrier_name']); ?></td>
								<td>
									<?php if (!empty($s['tracking_url'])) : ?>
										<a href="<?php echo esc_url($s['tracking_url']); ?>" target="_blank" rel="noopener noreferrer" style="color: #7f54b3; text-decoration: underline;">
											<?php echo esc_html($s['tracking_number']); ?>
										</a>
									<?php else : ?>
										<code><?php echo esc_html($s['tracking_number']); ?></code>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html($status_lbl); ?></td>
							</tr>
							<?php if (!empty($s['customer_note'])) : ?>
								<tr>
									<td colspan="3" style="font-size: 12px; color: #666666; padding-top: 0;">
										<em><?php echo esc_html($s['customer_note']); ?></em>
									</td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php
		}
	}
}
